<?php

namespace App\Repositories;

use App\Models\Withdrawal;
use App\Contracts\Repositories\WithdrawalRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class WithdrawalRepository implements WithdrawalRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- Helpers -------------------
    protected function getWithdrawalStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "withdrawal_status.{$statusKey}");
    }

    // ------------------- Retrieval -------------------
    public function findById(int $id, array $with = []): ?Withdrawal
    {
        return Withdrawal::with($with)->find($id);
    }

    public function getByWalletId(int $walletId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return Withdrawal::with($with)->where('wallet_id', $walletId)->paginate($perPage);
    }

    public function getByAgentId(int $agentId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return Withdrawal::with($with)->where('agent_id', $agentId)->paginate($perPage);
    }

    public function getByStatus(string $status, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return Withdrawal::with($with)->where('status', $status)->paginate($perPage);
    }

    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = Withdrawal::with($with);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['agent_id'])) {
            $query->where('agent_id', $filters['agent_id']);
        }

        return $query->paginate($perPage);
    }

    public function getPendingExpired(array $with = []): Collection
    {
        $pendingStatus = $this->getWithdrawalStatusConstant('pending') ?? 'pending';
        return Withdrawal::with($with)
            ->where('status', $pendingStatus)
            ->where('expires_at', '<', now())
            ->get();
    }

    public function getPending(array $with = []): Collection
    {
        $pendingStatus = $this->getWithdrawalStatusConstant('pending') ?? 'pending';
        return Withdrawal::with($with)->where('status', $pendingStatus)->get();
    }

    public function getCompleted(array $with = []): Collection
    {
        $completedStatus = $this->getWithdrawalStatusConstant('completed') ?? 'completed';
        return Withdrawal::with($with)->where('status', $completedStatus)->get();
    }

    public function isExpired(int $id): bool
    {
        $withdrawal = $this->findById($id);
        if (!$withdrawal) {
            return false;
        }
        $pendingStatus = $this->getWithdrawalStatusConstant('pending') ?? 'pending';
        return $withdrawal->expires_at->isPast() && $withdrawal->status === $pendingStatus;
    }

    public function isPending(int $id): bool
    {
        $withdrawal = $this->findById($id);
        if (!$withdrawal) {
            return false;
        }
        $pendingStatus = $this->getWithdrawalStatusConstant('pending') ?? 'pending';
        return $withdrawal->status === $pendingStatus;
    }

    public function verifyCode(int $id, string $code): bool
    {
        $withdrawal = $this->findById($id);
        if (!$withdrawal) {
            return false;
        }
        return Hash::check($code, $withdrawal->verification_code);
    }

    public function markCompleted(int $id): bool
    {
        $withdrawal = $this->findById($id);
        if (!$withdrawal) {
            return false;
        }
        $completedStatus = $this->getWithdrawalStatusConstant('completed') ?? 'completed';
        return $withdrawal->update([
            'status'       => $completedStatus,
            'completed_at' => now(),
        ]);
    }

    // ------------------- Aggregates -------------------
    public function sumByAgent(int $agentId, string $status = 'completed'): float
    {
        $statusValue = $this->getWithdrawalStatusConstant($status) ?? $status;
        return (float) Withdrawal::where('agent_id', $agentId)
            ->where('status', $statusValue)
            ->sum('total_amount');
    }

    public function countByAgent(int $agentId, string $status): int
    {
        $statusValue = $this->getWithdrawalStatusConstant($status) ?? $status;
        return Withdrawal::where('agent_id', $agentId)
            ->where('status', $statusValue)
            ->count();
    }

    // ------------------- Write -------------------
    public function create(array $data): Withdrawal
    {
        return Withdrawal::create($data);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $withdrawal = $this->findById($id);
        return $withdrawal ? $withdrawal->update(['status' => $status]) : false;
    }

    public function markFailed(int $id): bool
    {
        $failedStatus = $this->getWithdrawalStatusConstant('failed') ?? 'failed';
        return $this->updateStatus($id, $failedStatus);
    }

    public function markCancelled(int $id): bool
    {
        $cancelledStatus = $this->getWithdrawalStatusConstant('cancelled') ?? 'cancelled';
        return $this->updateStatus($id, $cancelledStatus);
    }

    public function expireOldPending(): int
    {
        $pendingStatus = $this->getWithdrawalStatusConstant('pending') ?? 'pending';
        $cancelledStatus = $this->getWithdrawalStatusConstant('cancelled') ?? 'cancelled';
        return Withdrawal::where('status', $pendingStatus)
            ->where('expires_at', '<', now())
            ->update(['status' => $cancelledStatus]);
    }
}