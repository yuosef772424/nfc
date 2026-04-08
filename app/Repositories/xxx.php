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

    protected function getWithdrawalStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "withdrawal_status.{$statusKey}");
    }

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
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['agent_id'])) $query->where('agent_id', $filters['agent_id']);
        return $query->paginate($perPage);
    }

    public function getPendingExpired(array $with = []): Collection
    {
        $pendingStatus = $this->getWithdrawalStatusConstant('pending') ?? 'pending';
        return Withdrawal::with($with)->where('status', $pendingStatus)->where('expires_at', '<', now())->get();
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
        if (!$withdrawal) return false;
        $pendingStatus = $this->getWithdrawalStatusConstant('pending') ?? 'pending';
        return $withdrawal->expires_at->isPast() && $withdrawal->status === $pendingStatus;
    }

    public function isPending(int $id): bool
    {
        $withdrawal = $this->findById($id);
        if (!$withdrawal) return false;
        $pendingStatus = $this->getWithdrawalStatusConstant('pending') ?? 'pending';
        return $withdrawal->status === $pendingStatus;
    }

    public function verifyCode(int $id, string $code): bool
    {
        $withdrawal = $this->findById($id);
        if (!$withdrawal) return false;
        return Hash::check($code, $withdrawal->verification_code);
    }

    public function markCompleted(int $id): bool
    {
        $withdrawal = $this->findById($id);
        if (!$withdrawal) return false;
        $completedStatus = $this->getWithdrawalStatusConstant('completed') ?? 'completed';
        return $withdrawal->update(['status' => $completedStatus, 'completed_at' => now()]);
    }

    public function sumByAgent(int $agentId, string $status = 'completed'): float
    {
        $statusValue = $this->getWithdrawalStatusConstant($status) ?? $status;
        return (float) Withdrawal::where('agent_id', $agentId)->where('status', $statusValue)->sum('total_amount');
    }

    public function countByAgent(int $agentId, string $status): int
    {
        $statusValue = $this->getWithdrawalStatusConstant($status) ?? $status;
        return Withdrawal::where('agent_id', $agentId)->where('status', $statusValue)->count();
    }

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
        return Withdrawal::where('status', $pendingStatus)->where('expires_at', '<', now())->update(['status' => $cancelledStatus]);
    }
}


use App\Models\Wallet;
use App\Contracts\Repositories\WalletRepositoryInterface;
use Illuminate\Support\Facades\DB;

class WalletRepository implements WalletRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    protected function getWalletStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "wallet_status.{$statusKey}");
    }

    public function findById(int $id, array $with = []): ?Wallet
    {
        return Wallet::with($with)->find($id);
    }

    public function getByUserId(int $userId, array $with = []): ?Wallet
    {
        return Wallet::with($with)->where('user_id', $userId)->first();
    }

    public function getAllByUserId(int $userId, array $with = []): Collection
    {
        return Wallet::with($with)->where('user_id', $userId)->get();
    }

    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = Wallet::with($with);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['currency'])) $query->where('currency', $filters['currency']);
        return $query->paginate($perPage);
    }

    public function create(int $userId, array $data): Wallet
    {
        $data['user_id'] = $userId;
        return Wallet::create($data);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    public function updateBalance(int $id, float $availableBalance, ?float $pendingBalance = null): bool
    {
        $data = ['available_balance' => $availableBalance];
        if ($pendingBalance !== null) $data['pending_balance'] = $pendingBalance;
        return $this->update($id, $data);
    }

    public function incrementBalance(int $id, float $amount): bool
    {
        return (bool) Wallet::where('id', $id)->increment('available_balance', $amount);
    }

    public function decrementBalance(int $id, float $amount): bool
    {
        return (bool) Wallet::where('id', $id)->decrement('available_balance', $amount);
    }

    public function incrementPending(int $id, float $amount): bool
    {
        return (bool) Wallet::where('id', $id)->increment('pending_balance', $amount);
    }

    public function decrementPending(int $id, float $amount): bool
    {
        return (bool) Wallet::where('id', $id)->decrement('pending_balance', $amount);
    }

    public function settlePending(int $id, float $amount): bool
    {
        return DB::transaction(function () use ($id, $amount): bool {
            $wallet = Wallet::where('id', $id)->lockForUpdate()->first();
            if (!$wallet || $wallet->pending_balance < $amount) return false;
            return $wallet->update([
                'pending_balance' => $wallet->pending_balance - $amount,
                'available_balance' => $wallet->available_balance + $amount,
            ]);
        });
    }

    public function delete(int $id): bool
    {
        $wallet = $this->findById($id);
        return $wallet ? (bool) $wallet->delete() : false;
    }

    public function hasSufficientBalance(int $id, float $amount): bool
    {
        $wallet = $this->findById($id);
        return $wallet && $wallet->available_balance >= $amount;
    }

    public function isActive(int $id): bool
    {
        $wallet = $this->findById($id);
        if (!$wallet) return false;
        $activeStatus = $this->getWalletStatusConstant('active') ?? 'active';
        return $wallet->status === $activeStatus;
    }

    public function existsByUserId(int $userId): bool
    {
        return Wallet::where('user_id', $userId)->exists();
    }

    private function update(int $id, array $data): bool
    {
        $wallet = $this->findById($id);
        return $wallet ? $wallet->update($data) : false;
    }
}


use App\Models\User;
use App\Contracts\Repositories\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    public function getUserTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "user_type.{$typeKey}");
    }

    public function getStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "user_status.{$statusKey}");
    }

    public function getAllUserTypes(): array
    {
        $group = $this->configRepo->getGroup('constant', ['category' => 'user_type']);
        return $group->pluck('value', 'key')->toArray();
    }

    public function findById(int $id, array $with = []): ?User
    {
        return User::with($with)->find($id);
    }

    public function getByPhone(string $phone, array $with = []): ?User
    {
        return User::with($with)->where('phone', $phone)->first();
    }

    public function getByEmail(string $email, array $with = []): ?User
    {
        return User::with($with)->where('email', $email)->first();
    }

    public function getByUuid(string $uuid, array $with = []): ?User
    {
        return User::with($with)->where('uuid', $uuid)->first();
    }

    /**
     * @deprecated Use findById($id, $relations) instead
     */
    public function findWithRelations(int $id, array $relations): ?User
    {
        return User::with($relations)->find($id);
    }

    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = User::with($with);
        if (!empty($filters['user_type'])) $query->where('user_type', $filters['user_type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        return $query->paginate($perPage);
    }

    public function getByType(string $userType, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return User::with($with)->where('user_type', $userType)->paginate($perPage);
    }

    public function getActiveUsers(int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $activeStatus = $this->getStatusConstant('active') ?? 'active';
        return User::with($with)->where('status', $activeStatus)->paginate($perPage);
    }

    public function getVerified(array $with = []): Collection
    {
        return User::with($with)->where('is_verified', true)->get();
    }

    public function getAgents(array $with = []): Collection
    {
        $agentType = $this->getUserTypeConstant('agent') ?? 'agent';
        return User::with($with)->where('user_type', $agentType)->get();
    }

    public function getMerchants(array $with = []): Collection
    {
        $merchantType = $this->getUserTypeConstant('merchant') ?? 'merchant';
        return User::with($with)->where('user_type', $merchantType)->get();
    }

    public function isAgent(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        $agentType = $this->getUserTypeConstant('agent') ?? 'agent';
        return $user->user_type === $agentType;
    }

    public function isMerchant(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        $merchantType = $this->getUserTypeConstant('merchant') ?? 'merchant';
        return $user->user_type === $merchantType;
    }

    public function isVerified(int $id): bool
    {
        $user = $this->findById($id);
        return $user && $user->is_verified === true;
    }

    public function isSuspended(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        $suspendedStatus = $this->getStatusConstant('suspended') ?? 'suspended';
        return $user->status === $suspendedStatus;
    }

    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        return $user->update($data);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    public function markAsVerified(int $id): bool
    {
        return $this->update($id, ['is_verified' => true]);
    }

    public function delete(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        return (bool) $user->delete();
    }

    public function existsByPhone(string $phone): bool
    {
        return User::where('phone', $phone)->exists();
    }

    public function existsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function countByType(string $userType): int
    {
        return User::where('user_type', $userType)->count();
    }
}


use App\Models\UserKyc;
use App\Contracts\Repositories\UserKycRepositoryInterface;

class UserKycRepository implements UserKycRepositoryInterface
{
    public function getByUserId(int $userId, array $with = []): ?UserKyc
    {
        return UserKyc::with($with)->where('user_id', $userId)->first();
    }

    public function getPending(int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return UserKyc::with($with)->whereNull('verified_at')->paginate($perPage);
    }

    public function getVerified(int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return UserKyc::with($with)->whereNotNull('verified_at')->paginate($perPage);
    }

    public function createOrUpdate(int $userId, array $data): UserKyc
    {
        $kyc = $this->getByUserId($userId);
        if ($kyc) {
            $kyc->update($data);
            return $kyc;
        }
        $data['user_id'] = $userId;
        return UserKyc::create($data);
    }

    public function markVerified(int $userId): bool
    {
        $kyc = $this->getByUserId($userId);
        if (!$kyc) return false;
        return $kyc->update(['verified_at' => now()]);
    }

    public function update(int $userId, array $data): bool
    {
        $kyc = $this->getByUserId($userId);
        if (!$kyc) return false;
        return $kyc->update($data);
    }

    public function delete(int $userId): bool
    {
        $kyc = $this->getByUserId($userId);
        if (!$kyc) return false;
        return (bool) $kyc->delete();
    }

    public function isVerified(int $userId): bool
    {
        $kyc = $this->getByUserId($userId);
        return $kyc && $kyc->verified_at !== null;
    }

    public function isExpired(int $userId): bool
    {
        $kyc = $this->getByUserId($userId);
        if (!$kyc || !$kyc->id_expiry_date) return false;
        return $kyc->id_expiry_date->isPast();
    }

    public function exists(int $userId): bool
    {
        return UserKyc::where('user_id', $userId)->exists();
    }
}


use App\Models\WalletTransaction;
use App\Contracts\Repositories\TransactionRepositoryInterface;

class TransactionRepository implements TransactionRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    protected function getTransactionTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "transaction_type.{$typeKey}");
    }

    protected function getTransactionStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "transaction_status.{$statusKey}");
    }

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
        $query = WalletTransaction::with($with)->where(function ($q) use ($walletId) {
            $q->where('sender_wallet_id', $walletId)->orWhere('receiver_wallet_id', $walletId);
        });
        if (!empty($filters['type'])) $query->where('type', $filters['type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        return $query->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getSentByWallet(int $walletId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return WalletTransaction::with($with)->where('sender_wallet_id', $walletId)->orderBy('id', 'desc')->paginate($perPage);
    }

    public function getReceivedByWallet(int $walletId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return WalletTransaction::with($with)->where('receiver_wallet_id', $walletId)->orderBy('id', 'desc')->paginate($perPage);
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
        if (!empty($filters['type'])) $query->where('type', $filters['type']);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['sender_wallet_id'])) $query->where('sender_wallet_id', $filters['sender_wallet_id']);
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

    public function sumByWallet(int $walletId, string $type, string $status = 'completed'): float
    {
        $typeValue = $this->getTransactionTypeConstant($type) ?? $type;
        $statusValue = $this->getTransactionStatusConstant($status) ?? $status;
        return (float) WalletTransaction::where(function ($q) use ($walletId) {
            $q->where('sender_wallet_id', $walletId)->orWhere('receiver_wallet_id', $walletId);
        })->where('type', $typeValue)->where('status', $statusValue)->sum('amount');
    }

    public function countByWallet(int $walletId, array $filters = []): int
    {
        $query = WalletTransaction::where('sender_wallet_id', $walletId)->orWhere('receiver_wallet_id', $walletId);
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

    public function create(array $data): WalletTransaction
    {
        return WalletTransaction::create($data);
    }

    public function updateStatus(int $id, string $status, ?string $failureReason = null, ?string $failureCode = null): bool
    {
        $transaction = $this->findById($id);
        if (!$transaction) return false;
        $updateData = ['status' => $status];
        if ($failureReason !== null) $updateData['failure_reason'] = $failureReason;
        if ($failureCode !== null) $updateData['failure_code'] = $failureCode;
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


use App\Models\Session;
use App\Contracts\Repositories\SessionRepositoryInterface;

class SessionRepository implements SessionRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    public function findById(int $id, array $with = []): ?Session
    {
        return Session::with($with)->find($id);
    }

    public function getByTokenHash(string $tokenHash, array $with = []): ?Session
    {
        return Session::with($with)->where('token_hash', $tokenHash)->first();
    }

    public function getActiveByUserId(int $userId, array $with = []): Collection
    {
        return Session::with($with)->where('user_id', $userId)->where('expires_at', '>', now())->get();
    }

    public function getAllByUserId(int $userId, array $with = []): Collection
    {
        return Session::with($with)->where('user_id', $userId)->get();
    }

    public function isExpired(int $id): bool
    {
        $session = $this->findById($id);
        return $session && $session->expires_at->isPast();
    }

    protected function getSessionExpiryMinutes(): int
    {
        $value = $this->configRepo->getValue('policy', 'session.expiry_minutes', ['scope' => 'global']);
        return is_numeric($value) ? (int) $value : 120;
    }

    public function create(int $userId, string $tokenHash, array $deviceInfo, ?array $location, ?\DateTimeInterface $expiresAt = null): Session
    {
        $resolvedExpiry = $expiresAt ?? now()->addMinutes($this->getSessionExpiryMinutes());
        return Session::create([
            'user_id' => $userId,
            'token_hash' => $tokenHash,
            'device_info' => $deviceInfo,
            'location' => $location,
            'expires_at' => $resolvedExpiry,
        ]);
    }

    public function deleteById(int $id): bool
    {
        $session = $this->findById($id);
        if (!$session) return false;
        return (bool) $session->delete();
    }

    public function deleteAllByUserId(int $userId): int
    {
        return Session::where('user_id', $userId)->delete();
    }

    public function deleteExpired(): int
    {
        return Session::where('expires_at', '<=', now())->delete();
    }

    public function isValid(string $tokenHash): bool
    {
        $session = $this->getByTokenHash($tokenHash);
        return $session && !$session->expires_at->isPast();
    }
}


use App\Models\PhysicalDeviceDetail;
use App\Contracts\Repositories\PhysicalDeviceDetailRepositoryInterface;

class PhysicalDeviceDetailRepository implements PhysicalDeviceDetailRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    public function getByDeviceId(int $deviceId, array $with = []): ?PhysicalDeviceDetail
    {
        return PhysicalDeviceDetail::with($with)->where('device_id', $deviceId)->first();
    }

    public function create(int $deviceId, array $data): PhysicalDeviceDetail
    {
        $data['device_id'] = $deviceId;
        return PhysicalDeviceDetail::create($data);
    }

    public function update(int $deviceId, array $data): bool
    {
        $detail = $this->getByDeviceId($deviceId);
        if (!$detail) return false;
        return $detail->update($data);
    }

    public function delete(int $deviceId): bool
    {
        $detail = $this->getByDeviceId($deviceId);
        if (!$detail) return false;
        return (bool) $detail->delete();
    }

    public function exists(int $deviceId): bool
    {
        return PhysicalDeviceDetail::where('device_id', $deviceId)->exists();
    }
}


use App\Models\Notification;
use App\Contracts\Repositories\NotificationRepositoryInterface;

class NotificationRepository implements NotificationRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    protected function getNotificationTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "notification_type.{$typeKey}");
    }

    protected function getNotificationChannelConstant(string $channelKey): ?string
    {
        return $this->configRepo->getValue('constant', "notification_channel.{$channelKey}");
    }

    public function findById(int $id, array $with = []): ?Notification
    {
        return Notification::with($with)->find($id);
    }

    public function getByUserId(int $userId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return Notification::with($with)->where('user_id', $userId)->paginate($perPage);
    }

    public function getUnreadByUserId(int $userId, array $with = []): Collection
    {
        return Notification::with($with)->where('user_id', $userId)->where('is_read', false)->get();
    }

    public function getByType(int $userId, string $type, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return Notification::with($with)->where('user_id', $userId)->where('type', $type)->paginate($perPage);
    }

    public function countUnread(int $userId): int
    {
        return Notification::where('user_id', $userId)->where('is_read', false)->count();
    }

    public function create(int $userId, string $type, string $title, string $message, string $channel = 'push', ?array $data = null): Notification
    {
        $defaultChannel = $this->getNotificationChannelConstant('push') ?? 'push';
        $finalChannel = $channel ?: $defaultChannel;
        return Notification::create([
            'user_id' => $userId,
            'type' => $type,
            'title' => $title,
            'message' => $message,
            'channel' => $finalChannel,
            'is_read' => false,
            'data' => $data,
        ]);
    }

    public function markAsRead(int $id): bool
    {
        $notification = $this->findById($id);
        if (!$notification) return false;
        return $notification->update(['is_read' => true]);
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)->where('is_read', false)->update(['is_read' => true]);
    }

    public function delete(int $id): bool
    {
        $notification = $this->findById($id);
        if (!$notification) return false;
        return (bool) $notification->delete();
    }

    public function deleteAllByUserId(int $userId): int
    {
        return Notification::where('user_id', $userId)->delete();
    }
}


use App\Models\NfcDevice;
use App\Models\PhysicalDeviceDetail as PhysicalDeviceDetailModel;
use App\Models\MobileDeviceDetail;
use App\Contracts\Repositories\NfcDeviceRepositoryInterface;

class NfcDeviceRepository implements NfcDeviceRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    protected function getDeviceTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "device_type.{$typeKey}");
    }

    protected function getDeviceStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "device_status.{$statusKey}");
    }

    public function getByUuid(string $uuid, array $with = []): ?NfcDevice
    {
        return NfcDevice::with($with)->where('device_uuid', $uuid)->first();
    }

    public function findById(int $id, array $with = []): ?NfcDevice
    {
        return NfcDevice::with($with)->find($id);
    }

    public function getByUserId(int $userId, array $with = []): Collection
    {
        return NfcDevice::with($with)->where('user_id', $userId)->get();
    }

    public function getByType(string $deviceType, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return NfcDevice::with($with)->where('device_type', $deviceType)->paginate($perPage);
    }

    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = NfcDevice::with($with);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['device_type'])) $query->where('device_type', $filters['device_type']);
        return $query->paginate($perPage);
    }

    public function getActive(array $with = []): Collection
    {
        $activeStatus = $this->getDeviceStatusConstant('active') ?? 'active';
        return NfcDevice::with($with)->where('status', $activeStatus)->get();
    }

    public function getPhysical(array $with = []): Collection
    {
        $physicalType = $this->getDeviceTypeConstant('physical') ?? 'physical';
        return NfcDevice::with($with)->where('device_type', $physicalType)->get();
    }

    public function getMobile(array $with = []): Collection
    {
        $mobileType = $this->getDeviceTypeConstant('mobile') ?? 'mobile';
        return NfcDevice::with($with)->where('device_type', $mobileType)->get();
    }

    public function isPhysical(int $id): bool
    {
        $device = $this->findById($id);
        if (!$device) return false;
        $physicalType = $this->getDeviceTypeConstant('physical') ?? 'physical';
        return $device->device_type === $physicalType;
    }

    public function isMobile(int $id): bool
    {
        $device = $this->findById($id);
        if (!$device) return false;
        $mobileType = $this->getDeviceTypeConstant('mobile') ?? 'mobile';
        return $device->device_type === $mobileType;
    }

    public function getDetails(int $id): ?\Illuminate\Database\Eloquent\Model
    {
        $device = $this->findById($id);
        if (!$device) return null;
        $physicalType = $this->getDeviceTypeConstant('physical') ?? 'physical';
        if ($device->device_type === $physicalType) {
            return PhysicalDeviceDetailModel::where('device_id', $id)->first();
        }
        return MobileDeviceDetail::where('device_id', $id)->first();
    }

    public function create(int $userId, array $data): NfcDevice
    {
        $data['user_id'] = $userId;
        return NfcDevice::create($data);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    public function update(int $id, array $data): bool
    {
        $device = $this->findById($id);
        if (!$device) return false;
        return $device->update($data);
    }

    public function delete(int $id): bool
    {
        $device = $this->findById($id);
        if (!$device) return false;
        return (bool) $device->delete();
    }

    public function isActive(int $id): bool
    {
        $device = $this->findById($id);
        if (!$device) return false;
        $activeStatus = $this->getDeviceStatusConstant('active') ?? 'active';
        return $device->status === $activeStatus;
    }

    public function existsByUuid(string $uuid): bool
    {
        return NfcDevice::where('device_uuid', $uuid)->exists();
    }
}


use App\Models\MobileDeviceDetail;
use App\Contracts\Repositories\MobileDeviceDetailRepositoryInterface;

class MobileDeviceDetailRepository implements MobileDeviceDetailRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    protected function getBiometricTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "biometric_type.{$typeKey}");
    }

    public function getByDeviceId(int $deviceId, array $with = []): ?MobileDeviceDetail
    {
        return MobileDeviceDetail::with($with)->where('device_id', $deviceId)->first();
    }

    public function getByFingerprint(string $fingerprint, array $with = []): ?MobileDeviceDetail
    {
        return MobileDeviceDetail::with($with)->where('device_fingerprint', $fingerprint)->first();
    }

    public function hasNfc(int $deviceId): bool
    {
        $detail = $this->getByDeviceId($deviceId);
        return $detail && $detail->nfc_supported === true;
    }

    public function hasBiometric(int $deviceId): bool
    {
        $detail = $this->getByDeviceId($deviceId);
        if (!$detail) return false;
        $noneType = $this->getBiometricTypeConstant('none') ?? 'none';
        return $detail->biometric_type !== $noneType;
    }

    public function create(int $deviceId, array $data): MobileDeviceDetail
    {
        $data['device_id'] = $deviceId;
        return MobileDeviceDetail::create($data);
    }

    public function update(int $deviceId, array $data): bool
    {
        $detail = $this->getByDeviceId($deviceId);
        if (!$detail) return false;
        return $detail->update($data);
    }

    public function updateNfcStatus(int $deviceId, bool $nfcEnabled): bool
    {
        return $this->update($deviceId, ['nfc_supported' => $nfcEnabled]);
    }

    public function delete(int $deviceId): bool
    {
        $detail = $this->getByDeviceId($deviceId);
        if (!$detail) return false;
        return (bool) $detail->delete();
    }

    public function exists(int $deviceId): bool
    {
        return MobileDeviceDetail::where('device_id', $deviceId)->exists();
    }
}


use App\Models\MerchantProfile;
use App\Contracts\Repositories\MerchantProfileRepositoryInterface;

class MerchantProfileRepository implements MerchantProfileRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    public function getByUserId(int $userId, array $with = []): ?MerchantProfile
    {
        return MerchantProfile::with($with)->where('user_id', $userId)->first();
    }

    public function getAll(int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return MerchantProfile::with($with)->paginate($perPage);
    }

    public function getActive(array $with = []): Collection
    {
        return MerchantProfile::with($with)->where('is_active', true)->get();
    }

    public function getByBusinessType(string $businessType, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return MerchantProfile::with($with)->where('business_type', $businessType)->paginate($perPage);
    }

    public function create(int $userId, array $data): MerchantProfile
    {
        $data['user_id'] = $userId;
        return MerchantProfile::create($data);
    }

    public function update(int $userId, array $data): bool
    {
        $profile = $this->getByUserId($userId);
        if (!$profile) return false;
        return $profile->update($data);
    }

    public function setActive(int $userId, bool $isActive): bool
    {
        return $this->update($userId, ['is_active' => $isActive]);
    }

    public function delete(int $userId): bool
    {
        $profile = $this->getByUserId($userId);
        if (!$profile) return false;
        return (bool) $profile->delete();
    }

    public function exists(int $userId): bool
    {
        return MerchantProfile::where('user_id', $userId)->exists();
    }

    public function isActive(int $userId): bool
    {
        $profile = $this->getByUserId($userId);
        return $profile && $profile->is_active;
    }
}


use App\Models\LedgerEntry;
use App\Contracts\Repositories\LedgerEntryRepositoryInterface;

class LedgerEntryRepository implements LedgerEntryRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    protected function getEntryTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "ledger_entry_type.{$typeKey}");
    }

    public function findById(int $id, array $with = []): ?LedgerEntry
    {
        return LedgerEntry::with($with)->find($id);
    }

    public function getByWalletId(int $walletId, int $perPage = 50, array $with = []): LengthAwarePaginator
    {
        return LedgerEntry::with($with)->where('wallet_id', $walletId)->orderBy('id')->paginate($perPage);
    }

    public function getByTransactionId(int $transactionId, array $with = []): Collection
    {
        return LedgerEntry::with($with)->where('transaction_id', $transactionId)->get();
    }

    public function getLatestByWallet(int $walletId, array $with = []): ?LedgerEntry
    {
        return LedgerEntry::with($with)->where('wallet_id', $walletId)->orderBy('id', 'desc')->first();
    }

    public function getDebits(int $walletId, array $with = []): Collection
    {
        $debitType = $this->getEntryTypeConstant('debit') ?? 'debit';
        return LedgerEntry::with($with)->where('wallet_id', $walletId)->where('entry_type', $debitType)->orderBy('id')->get();
    }

    public function getCredits(int $walletId, array $with = []): Collection
    {
        $creditType = $this->getEntryTypeConstant('credit') ?? 'credit';
        return LedgerEntry::with($with)->where('wallet_id', $walletId)->where('entry_type', $creditType)->orderBy('id')->get();
    }

    public function isDebit(int $id): bool
    {
        $entry = $this->findById($id);
        if (!$entry) return false;
        $debitType = $this->getEntryTypeConstant('debit') ?? 'debit';
        return $entry->entry_type === $debitType;
    }

    public function isCredit(int $id): bool
    {
        $entry = $this->findById($id);
        if (!$entry) return false;
        $creditType = $this->getEntryTypeConstant('credit') ?? 'credit';
        return $entry->entry_type === $creditType;
    }

    public function calculateBalance(int $walletId): float
    {
        $debitType = $this->getEntryTypeConstant('debit') ?? 'debit';
        $creditType = $this->getEntryTypeConstant('credit') ?? 'credit';
        $balance = LedgerEntry::where('wallet_id', $walletId)
            ->selectRaw('SUM(CASE WHEN entry_type = ? THEN amount WHEN entry_type = ? THEN -amount ELSE 0 END) as balance', [$creditType, $debitType])
            ->value('balance');
        return (float) ($balance ?? 0.0);
    }

    public function sumCredits(int $walletId): float
    {
        $creditType = $this->getEntryTypeConstant('credit') ?? 'credit';
        return (float) LedgerEntry::where('wallet_id', $walletId)->where('entry_type', $creditType)->sum('amount');
    }

    public function sumDebits(int $walletId): float
    {
        $debitType = $this->getEntryTypeConstant('debit') ?? 'debit';
        return (float) LedgerEntry::where('wallet_id', $walletId)->where('entry_type', $debitType)->sum('amount');
    }

    public function create(int $transactionId, int $walletId, string $entryType, float $amount, float $balanceAfter): LedgerEntry
    {
        return LedgerEntry::create([
            'transaction_id' => $transactionId,
            'wallet_id' => $walletId,
            'entry_type' => $entryType,
            'amount' => $amount,
            'balance_after' => $balanceAfter,
        ]);
    }

    public function createDoubleSided(int $transactionId, int $senderWalletId, float $senderBalanceAfter, int $receiverWalletId, float $receiverBalanceAfter, float $amount): array
    {
        $debitType = $this->getEntryTypeConstant('debit') ?? 'debit';
        $creditType = $this->getEntryTypeConstant('credit') ?? 'credit';
        $debit = $this->create($transactionId, $senderWalletId, $debitType, $amount, $senderBalanceAfter);
        $credit = $this->create($transactionId, $receiverWalletId, $creditType, $amount, $receiverBalanceAfter);
        return [$debit, $credit];
    }
}


use App\Models\CommissionLog;
use App\Models\Withdrawal as WithdrawalModel;
use App\Models\WalletTransaction as WalletTransactionModel;
use App\Contracts\Repositories\CommissionLogRepositoryInterface;
use Illuminate\Database\Eloquent\Model;

class CommissionLogRepository implements CommissionLogRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    protected function getCommissionStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "commission_status.{$statusKey}");
    }

    protected function getRecipientTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "recipient_type.{$typeKey}");
    }

    public function findById(int $id, array $with = []): ?CommissionLog
    {
        return CommissionLog::with($with)->find($id);
    }

    public function getByRecipient(int $recipientId, string $recipientType, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return CommissionLog::with($with)->where('recipient_id', $recipientId)->where('recipient_type', $recipientType)->paginate($perPage);
    }

    public function getByReference(string $referenceType, int $referenceId, array $with = []): Collection
    {
        return CommissionLog::with($with)->where('reference_type', $referenceType)->where('reference_id', $referenceId)->get();
    }

    public function getPendingByAgent(int $agentId, array $with = []): Collection
    {
        $agentType = $this->getRecipientTypeConstant('agent') ?? 'agent';
        $pendingStatus = $this->getCommissionStatusConstant('pending') ?? 'pending';
        return CommissionLog::with($with)->where('recipient_type', $agentType)->where('recipient_id', $agentId)->where('status', $pendingStatus)->get();
    }

    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = CommissionLog::with($with);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['recipient_type'])) $query->where('recipient_type', $filters['recipient_type']);
        if (!empty($filters['recipient_id'])) $query->where('recipient_id', $filters['recipient_id']);
        return $query->paginate($perPage);
    }

    public function getPending(array $with = []): Collection
    {
        $pendingStatus = $this->getCommissionStatusConstant('pending') ?? 'pending';
        return CommissionLog::with($with)->where('status', $pendingStatus)->get();
    }

    public function getForAgent(int $agentId, array $with = []): Collection
    {
        $agentType = $this->getRecipientTypeConstant('agent') ?? 'agent';
        return CommissionLog::with($with)->where('recipient_type', $agentType)->where('recipient_id', $agentId)->get();
    }

    public function getReference(int $logId): ?Model
    {
        $log = $this->findById($logId);
        if (!$log) return null;
        $withdrawalRef = CommissionLog::REF_WITHDRAWAL;
        $transactionRef = CommissionLog::REF_TRANSACTION;
        return match($log->reference_type) {
            $withdrawalRef => WithdrawalModel::find($log->reference_id),
            $transactionRef => WalletTransactionModel::find($log->reference_id),
            default => null,
        };
    }

    public function markPaid(int $id): bool
    {
        $log = $this->findById($id);
        if (!$log) return false;
        $paidStatus = $this->getCommissionStatusConstant('paid') ?? 'paid';
        return $log->update(['status' => $paidStatus, 'paid_at' => now()]);
    }

    public function markCancelled(int $id): bool
    {
        $log = $this->findById($id);
        if (!$log) return false;
        $cancelledStatus = $this->getCommissionStatusConstant('cancelled') ?? 'cancelled';
        return $log->update(['status' => $cancelledStatus]);
    }

    public function sumPendingByAgent(int $agentId): float
    {
        $agentType = $this->getRecipientTypeConstant('agent') ?? 'agent';
        $pendingStatus = $this->getCommissionStatusConstant('pending') ?? 'pending';
        return (float) CommissionLog::where('recipient_type', $agentType)->where('recipient_id', $agentId)->where('status', $pendingStatus)->sum('amount');
    }

    public function sumPaidByAgent(int $agentId): float
    {
        $agentType = $this->getRecipientTypeConstant('agent') ?? 'agent';
        $paidStatus = $this->getCommissionStatusConstant('paid') ?? 'paid';
        return (float) CommissionLog::where('recipient_type', $agentType)->where('recipient_id', $agentId)->where('status', $paidStatus)->sum('amount');
    }

    public function create(array $data): CommissionLog
    {
        return CommissionLog::create($data);
    }

    public function settleAllPendingForAgent(int $agentId): int
    {
        $agentType = $this->getRecipientTypeConstant('agent') ?? 'agent';
        $pendingStatus = $this->getCommissionStatusConstant('pending') ?? 'pending';
        $paidStatus = $this->getCommissionStatusConstant('paid') ?? 'paid';
        return CommissionLog::where('recipient_type', $agentType)->where('recipient_id', $agentId)->where('status', $pendingStatus)
            ->update(['status' => $paidStatus, 'paid_at' => now()]);
    }
}


use App\Models\Card;
use App\Contracts\Repositories\CardRepositoryInterface;

class CardRepository implements CardRepositoryInterface
{
    public function findById(int $id, array $with = []): ?Card
    {
        return Card::with($with)->find($id);
    }

    public function getByNfcUid(string $nfcUid, array $with = []): ?Card
    {
        return Card::with($with)->where('nfc_uid', $nfcUid)->first();
    }

    public function getByCardNumber(string $cardNumber, array $with = []): ?Card
    {
        return Card::with($with)->where('card_number', $cardNumber)->first();
    }

    public function getByWalletId(int $walletId, array $with = []): Collection
    {
        return Card::with($with)->where('wallet_id', $walletId)->get();
    }

    public function getByAgentId(int $agentId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return Card::with($with)->where('agent_id', $agentId)->latest('id')->paginate($perPage);
    }

    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = Card::with($with);
        if (!empty($filters['status'])) $query->where('status', $filters['status']);
        if (!empty($filters['wallet_id'])) $query->where('wallet_id', $filters['wallet_id']);
        return $query->latest('id')->paginate($perPage);
    }

    public function getActive(array $with = []): Collection
    {
        return Card::with($with)->where('status', 'active')->get();
    }

    public function getExpired(array $with = []): Collection
    {
        return Card::with($with)->where('status', 'expired')->get();
    }

    public function isActive(int $id): bool
    {
        return optional($this->findById($id))->status === 'active';
    }

    public function isExpired(int $id): bool
    {
        $card = $this->findById($id);
        if (!$card || !$card->expiry_date) return false;
        return $card->expiry_date->isPast();
    }

    public function verifyPin(int $id, string $pin): bool
    {
        $card = $this->findById($id);
        return $card && Hash::check($pin, $card->pin_hash);
    }

    public function setPin(int $id, string $pin): bool
    {
        $card = $this->findById($id);
        return $card ? $card->update(['pin_hash' => Hash::make($pin)]) : false;
    }

    public function create(array $data): Card
    {
        return Card::create($data);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $card = $this->findById($id);
        return $card ? $card->update(['status' => $status]) : false;
    }

    public function delete(int $id): bool
    {
        $card = $this->findById($id);
        return $card ? (bool) $card->delete() : false;
    }

    public function existsByNfcUid(string $nfcUid): bool
    {
        return Card::where('nfc_uid', $nfcUid)->exists();
    }
}


use App\Contracts\Repositories\CacheRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class CacheRepository implements CacheRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    public function put(string $key, mixed $value, int $ttlSeconds): bool
    {
        return Cache::put($key, $value, $ttlSeconds);
    }

    public function increment(string $key, int $amount = 1): int
    {
        return Cache::increment($key, $amount);
    }

    public function decrement(string $key, int $amount = 1): int
    {
        return Cache::decrement($key, $amount);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }

    public function expire(string $key, int $ttlSeconds): bool
    {
        if (!$this->has($key)) return false;
        $value = $this->get($key);
        return $this->put($key, $value, $ttlSeconds);
    }
}


use App\Models\AuditLog;
use App\Contracts\Repositories\AuditLogRepositoryInterface;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    public function create(string $action, string $entity, int $entityId, int $userId, string $ipAddress, ?array $oldData = null, ?array $newData = null): AuditLog
    {
        return AuditLog::create([
            'user_id' => $userId,
            'action' => $action,
            'entity' => $entity,
            'entity_id' => $entityId,
            'old_data' => $oldData,
            'new_data' => $newData,
            'ip_address' => $ipAddress,
        ]);
    }

    public function findById(int $id, array $with = []): ?AuditLog
    {
        return AuditLog::with($with)->find($id);
    }

    public function getByUserId(int $userId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return AuditLog::with($with)->where('user_id', $userId)->latest('created_at')->paginate($perPage);
    }

    public function getByEntity(string $entity, int $entityId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return AuditLog::with($with)->where('entity', $entity)->where('entity_id', $entityId)->latest('created_at')->paginate($perPage);
    }

    public function getByAction(string $action, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return AuditLog::with($with)->where('action', $action)->latest('created_at')->paginate($perPage);
    }

    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = AuditLog::with($with);
        if (!empty($filters['user_id'])) $query->where('user_id', $filters['user_id']);
        if (!empty($filters['action'])) $query->where('action', $filters['action']);
        if (!empty($filters['entity'])) $query->where('entity', $filters['entity']);
        if (!empty($filters['entity_id'])) $query->where('entity_id', $filters['entity_id']);
        return $query->latest('created_at')->paginate($perPage);
    }

    public function deleteOlderThan(\DateTimeInterface $date): int
    {
        return AuditLog::where('created_at', '<', $date)->delete();
    }

    public function deleteByUserId(int $userId): int
    {
        return AuditLog::where('user_id', $userId)->delete();
    }
}


use App\Models\AppConfig;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AppConfigRepository implements AppConfigRepositoryInterface
{
    public const CACHE_KEY = 'app_config_all';

    public function getValue(string $group, string $key, array $metaFilters = []): mixed
    {
        $all = $this->getAllGrouped();
        $items = $all[$group] ?? [];
        foreach ($items as $item) {
            if ($item['key'] !== $key) continue;
            if ($this->matchesMetaFilters($item['meta'], $metaFilters)) return $item['casted_value'];
        }
        return null;
    }

    public function getGroup(string $group, array $metaFilters = []): Collection
    {
        return collect($this->getAllGrouped()[$group] ?? [])
            ->filter(fn($item) => $this->matchesMetaFilters($item['meta'], $metaFilters))
            ->values();
    }

    public function getAllGrouped(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return AppConfig::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['group', 'key', 'value', 'data_type', 'label', 'sort_order', 'meta'])
                ->groupBy('group')
                ->transform(fn($group) => $group->map(fn($config) => [
                    'key' => $config->key,
                    'value' => $config->value,
                    'casted_value' => $config->casted_value,
                    'data_type' => $config->data_type,
                    'label' => $config->label,
                    'sort_order' => $config->sort_order,
                    'meta' => $config->meta,
                ]));
        });
    }

    public function set(string $group, string $key, mixed $value, array $meta = []): AppConfig
    {
        $dataType = $this->detectDataType($value);
        $stringValue = $this->encodeValue($value, $dataType);
        $config = AppConfig::updateOrCreate(
            ['group' => $group, 'key' => $key],
            ['value' => $stringValue, 'data_type' => $dataType, 'meta' => $meta, 'is_active' => true]
        );
        $this->clearCache();
        return $config;
    }

    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    private function detectDataType(mixed $value): string
    {
        return match (true) {
            is_bool($value) => 'boolean',
            is_array($value) => 'json',
            is_int($value) || is_float($value) => 'number',
            default => 'string',
        };
    }

    private function encodeValue(mixed $value, string $dataType): string
    {
        return match ($dataType) {
            'json' => json_encode($value),
            'boolean' => $value ? 'true' : 'false',
            default => (string) $value,
        };
    }

    private function matchesMetaFilters(array $itemMeta, array $metaFilters): bool
    {
        if (empty($metaFilters)) return true;
        foreach ($metaFilters as $key => $value) {
            if (!isset($itemMeta[$key]) || $itemMeta[$key] != $value) return false;
        }
        return true;
    }
}


use App\Models\AgentProfile;
use App\Contracts\Repositories\AgentProfileRepositoryInterface;

class AgentProfileRepository implements AgentProfileRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    protected function getCommissionTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "commission_type.{$typeKey}");
    }

    public function getByUserId(int $userId, array $with = []): ?AgentProfile
    {
        return AgentProfile::with($with)->where('user_id', $userId)->first();
    }

    public function getAll(int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return AgentProfile::with($with)->paginate($perPage);
    }

    public function getActive(array $with = []): Collection
    {
        return AgentProfile::with($with)->where('is_active', true)->get();
    }

    public function calculateCommission(int $userId, float $amount): float
    {
        $profile = $this->getByUserId($userId);
        if (!$profile) return 0.0;
        $percentageType = $this->getCommissionTypeConstant('percentage') ?? 'percentage';
        if ($profile->commission_type === $percentageType) {
            return round($amount * ($profile->commission_value / 100), 2);
        }
        return (float) $profile->commission_value;
    }

    public function create(int $userId, array $data): AgentProfile
    {
        $data['user_id'] = $userId;
        return AgentProfile::create($data);
    }

    public function update(int $userId, array $data): bool
    {
        $profile = $this->getByUserId($userId);
        if (!$profile) return false;
        return $profile->update($data);
    }

    public function updateCommission(int $userId, string $type, float $value): bool
    {
        return $this->update($userId, ['commission_type' => $type, 'commission_value' => $value]);
    }

    public function setActive(int $userId, bool $isActive): bool
    {
        return $this->update($userId, ['is_active' => $isActive]);
    }

    public function delete(int $userId): bool
    {
        $profile = $this->getByUserId($userId);
        if (!$profile) return false;
        return (bool) $profile->delete();
    }

    public function exists(int $userId): bool
    {
        return AgentProfile::where('user_id', $userId)->exists();
    }

    public function isActive(int $userId): bool
    {
        $profile = $this->getByUserId($userId);
        return $profile && $profile->is_active;
    }
}