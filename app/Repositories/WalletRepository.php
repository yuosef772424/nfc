<?php

namespace App\Repositories;

use App\Models\Wallet;
use App\Contracts\Repositories\WalletRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class WalletRepository implements WalletRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- Helpers -------------------
    protected function getWalletStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "wallet_status.{$statusKey}");
    }

    // ------------------- Retrieval -------------------
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

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['currency'])) {
            $query->where('currency', $filters['currency']);
        }

        return $query->paginate($perPage);
    }

    // ------------------- Write -------------------
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
        if ($pendingBalance !== null) {
            $data['pending_balance'] = $pendingBalance;
        }
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

            if (!$wallet) {
                return false;
            }

            if ($wallet->pending_balance < $amount) {
                return false;
            }

            return $wallet->update([
                'pending_balance'   => $wallet->pending_balance - $amount,
                'available_balance' => $wallet->available_balance + $amount,
            ]);
        });
    }

    public function delete(int $id): bool
    {
        $wallet = $this->findById($id);
        return $wallet ? (bool) $wallet->delete() : false;
    }

    // ------------------- Checks -------------------
    public function hasSufficientBalance(int $id, float $amount): bool
    {
        $wallet = $this->findById($id);
        return $wallet && $wallet->available_balance >= $amount;
    }

    public function isActive(int $id): bool
    {
        $wallet = $this->findById($id);
        if (!$wallet) {
            return false;
        }
        $activeStatus = $this->getWalletStatusConstant('active') ?? 'active';
        return $wallet->status === $activeStatus;
    }

    public function existsByUserId(int $userId): bool
    {
        return Wallet::where('user_id', $userId)->exists();
    }

    // ------------------- Helper -------------------
    private function update(int $id, array $data): bool
    {
        $wallet = $this->findById($id);
        return $wallet ? $wallet->update($data) : false;
    }

    
}