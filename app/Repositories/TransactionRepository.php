<?php

namespace App\Repositories;

use App\Models\WalletTransaction;
use App\Contracts\Repositories\TransactionRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class TransactionRepository implements TransactionRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- Helpers -------------------
    protected function getTransactionTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "transaction_type.{$typeKey}");
    }

    protected function getTransactionStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "transaction_status.{$statusKey}");
    }

    // ------------------- Retrieval -------------------
    public function findById(int $id, array $with = []): ?WalletTransaction
    {
        return WalletTransaction::with($with)->find($id);
    }

    public function getByUuid(string $uuid, array $with = []): ?WalletTransaction
    {
        return WalletTransaction::with($with)->where('transaction_uuid', $uuid)->first();
    }

    public function getByWalletId(int $walletId, array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = WalletTransaction::with($with)
            ->where(function ($q) use ($walletId) {
                $q->where('sender_wallet_id', $walletId)
                  ->orWhere('receiver_wallet_id', $walletId);
            });

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getSentByWallet(int $walletId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return WalletTransaction::with($with)
            ->where('sender_wallet_id', $walletId)
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getReceivedByWallet(int $walletId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return WalletTransaction::with($with)
            ->where('receiver_wallet_id', $walletId)
            ->orderBy('id', 'desc')
            ->paginate($perPage);
    }

    public function getByType(string $type, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return WalletTransaction::with($with)->where('type', $type)->paginate($perPage);
    }

    public function getByStatus(string $status, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return WalletTransaction::with($with)->where('status', $status)->paginate($perPage);
    }

    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = WalletTransaction::with($with);

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['sender_wallet_id'])) {
            $query->where('sender_wallet_id', $filters['sender_wallet_id']);
        }

        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getPending(array $with = []): Collection
    {
        $pendingStatus = $this->getTransactionStatusConstant('pending') ?? 'pending';
        return WalletTransaction::with($with)->where('status', $pendingStatus)->get();
    }

    public function getCompleted(array $with = []): Collection
    {
        $completedStatus = $this->getTransactionStatusConstant('completed') ?? 'completed';
        return WalletTransaction::with($with)->where('status', $completedStatus)->get();
    }

    public function getFailed(array $with = []): Collection
    {
        $failedStatus = $this->getTransactionStatusConstant('failed') ?? 'failed';
        return WalletTransaction::with($with)->where('status', $failedStatus)->get();
    }

    public function getByTransactionType(string $type, array $with = []): Collection
    {
        $typeValue = $this->getTransactionTypeConstant($type) ?? $type;
        return WalletTransaction::with($with)->where('type', $typeValue)->get();
    }

    // ------------------- Status Checks -------------------
    public function isCompleted(int $id): bool
    {
        $transaction = $this->findById($id);
        if (!$transaction) return false;
        $completedStatus = $this->getTransactionStatusConstant('completed') ?? 'completed';
        return $transaction->status === $completedStatus;
    }

    public function isPending(int $id): bool
    {
        $transaction = $this->findById($id);
        if (!$transaction) return false;
        $pendingStatus = $this->getTransactionStatusConstant('pending') ?? 'pending';
        return $transaction->status === $pendingStatus;
    }

    public function isFailed(int $id): bool
    {
        $transaction = $this->findById($id);
        if (!$transaction) return false;
        $failedStatus = $this->getTransactionStatusConstant('failed') ?? 'failed';
        return $transaction->status === $failedStatus;
    }

    public function isRefundable(int $id): bool
    {
        $transaction = $this->findById($id);
        if (!$transaction) return false;
        $completedStatus = $this->getTransactionStatusConstant('completed') ?? 'completed';
        $paymentType = $this->getTransactionTypeConstant('payment') ?? 'payment';
        return $transaction->status === $completedStatus && $transaction->type === $paymentType;
    }

    // ------------------- Aggregates -------------------
    public function sumByWallet(int $walletId, string $type, string $status = 'completed'): float
    {
        $typeValue = $this->getTransactionTypeConstant($type) ?? $type;
        $statusValue = $this->getTransactionStatusConstant($status) ?? $status;

        return (float) WalletTransaction::where(function ($query) use ($walletId) {
                $query->where('sender_wallet_id', $walletId)
                      ->orWhere('receiver_wallet_id', $walletId);
            })
            ->where('type', $typeValue)
            ->where('status', $statusValue)
            ->sum('amount');
    }

    public function countByWallet(int $walletId, array $filters = []): int
    {
        $query = WalletTransaction::where('sender_wallet_id', $walletId)
            ->orWhere('receiver_wallet_id', $walletId);

        if (!empty($filters['type'])) {
            $typeValue = $this->getTransactionTypeConstant($filters['type']) ?? $filters['type'];
            $query->where('type', $typeValue);
        }
        if (!empty($filters['status'])) {
            $statusValue = $this->getTransactionStatusConstant($filters['status']) ?? $filters['status'];
            $query->where('status', $statusValue);
        }
        return $query->count();
    }

    // ------------------- Write -------------------
    public function create(array $data): WalletTransaction
    {
        return WalletTransaction::create($data);
    }

    public function updateStatus(int $id, string $status, ?string $failureReason = null, ?string $failureCode = null): bool
    {
        $transaction = $this->findById($id);
        if (!$transaction) return false;

        $updateData = ['status' => $status];
        if ($failureReason !== null) {
            $updateData['failure_reason'] = $failureReason;
        }
        if ($failureCode !== null) {
            $updateData['failure_code'] = $failureCode;
        }
        return $transaction->update($updateData);
    }

    public function markCompleted(int $id): bool
    {
        $completedStatus = $this->getTransactionStatusConstant('completed') ?? 'completed';
        return $this->updateStatus($id, $completedStatus);
    }

    public function markFailed(int $id, string $reason, ?string $code = null): bool
    {
        $failedStatus = $this->getTransactionStatusConstant('failed') ?? 'failed';
        return $this->updateStatus($id, $failedStatus, $reason, $code);
    }

    public function markCancelled(int $id): bool
    {
        $cancelledStatus = $this->getTransactionStatusConstant('cancelled') ?? 'cancelled';
        return $this->updateStatus($id, $cancelledStatus);
    }
}