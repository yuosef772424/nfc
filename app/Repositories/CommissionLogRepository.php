<?php

namespace App\Repositories;

use App\Models\CommissionLog;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use App\Contracts\Repositories\CommissionLogRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;

class CommissionLogRepository implements CommissionLogRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- دوال مساعدة لقراءة الثوابت من app_config -------------------
    protected function getCommissionStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "commission_status.{$statusKey}");
    }

    protected function getRecipientTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "recipient_type.{$typeKey}");
    }

    // ------------------- Retrieval -------------------
    public function findById(int $id): ?CommissionLog
    {
        return CommissionLog::find($id);
    }

    public function getByRecipient(int $recipientId, string $recipientType, int $perPage = 20): LengthAwarePaginator
    {
        return CommissionLog::where('recipient_id', $recipientId)
            ->where('recipient_type', $recipientType)
            ->paginate($perPage);
    }

    public function getByReference(string $referenceType, int $referenceId): Collection
    {
        return CommissionLog::where('reference_type', $referenceType)
            ->where('reference_id', $referenceId)
            ->get();
    }

    public function getPendingByAgent(int $agentId): Collection
    {
        $agentType = $this->getRecipientTypeConstant('agent') ?? 'agent';
        $pendingStatus = $this->getCommissionStatusConstant('pending') ?? 'pending';
        return CommissionLog::where('recipient_type', $agentType)
            ->where('recipient_id', $agentId)
            ->where('status', $pendingStatus)
            ->get();
    }

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = CommissionLog::query();
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['recipient_type'])) {
            $query->where('recipient_type', $filters['recipient_type']);
        }
        if (!empty($filters['recipient_id'])) {
            $query->where('recipient_id', $filters['recipient_id']);
        }
        return $query->paginate($perPage);
    }

    public function getPending(): Collection
    {
        $pendingStatus = $this->getCommissionStatusConstant('pending') ?? 'pending';
        return CommissionLog::where('status', $pendingStatus)->get();
    }

    public function getForAgent(int $agentId): Collection
    {
        $agentType = $this->getRecipientTypeConstant('agent') ?? 'agent';
        return CommissionLog::where('recipient_type', $agentType)
            ->where('recipient_id', $agentId)
            ->get();
    }

    public function getReference(int $logId): ?Model
    {
        $log = $this->findById($logId);
        if (!$log) return null;

        $withdrawalRef = CommissionLog::REF_WITHDRAWAL; // هذه ثابتة تقنية يمكن بقاؤها أو جلبها من config
        $transactionRef = CommissionLog::REF_TRANSACTION;
        return match($log->reference_type) {
            $withdrawalRef  => Withdrawal::find($log->reference_id),
            $transactionRef => WalletTransaction::find($log->reference_id),
            default => null,
        };
    }

    public function markPaid(int $id): bool
    {
        $log = $this->findById($id);
        if (!$log) return false;
        $paidStatus = $this->getCommissionStatusConstant('paid') ?? 'paid';
        return $log->update([
            'status'  => $paidStatus,
            'paid_at' => now(),
        ]);
    }

    public function markCancelled(int $id): bool
    {
        $log = $this->findById($id);
        if (!$log) return false;
        $cancelledStatus = $this->getCommissionStatusConstant('cancelled') ?? 'cancelled';
        return $log->update(['status' => $cancelledStatus]);
    }

    // ------------------- Aggregates -------------------
    public function sumPendingByAgent(int $agentId): float
    {
        $agentType = $this->getRecipientTypeConstant('agent') ?? 'agent';
        $pendingStatus = $this->getCommissionStatusConstant('pending') ?? 'pending';
        return (float) CommissionLog::where('recipient_type', $agentType)
            ->where('recipient_id', $agentId)
            ->where('status', $pendingStatus)
            ->sum('amount');
    }

    public function sumPaidByAgent(int $agentId): float
    {
        $agentType = $this->getRecipientTypeConstant('agent') ?? 'agent';
        $paidStatus = $this->getCommissionStatusConstant('paid') ?? 'paid';
        return (float) CommissionLog::where('recipient_type', $agentType)
            ->where('recipient_id', $agentId)
            ->where('status', $paidStatus)
            ->sum('amount');
    }

    // ------------------- Write -------------------
    public function create(array $data): CommissionLog
    {
        return CommissionLog::create($data);
    }

    public function settleAllPendingForAgent(int $agentId): int
    {
        $agentType = $this->getRecipientTypeConstant('agent') ?? 'agent';
        $pendingStatus = $this->getCommissionStatusConstant('pending') ?? 'pending';
        $paidStatus = $this->getCommissionStatusConstant('paid') ?? 'paid';
        return CommissionLog::where('recipient_type', $agentType)
            ->where('recipient_id', $agentId)
            ->where('status', $pendingStatus)
            ->update([
                'status'  => $paidStatus,
                'paid_at' => now(),
            ]);
    }
}