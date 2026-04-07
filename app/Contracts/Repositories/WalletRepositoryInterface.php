<?php

namespace App\Contracts\Repositories;

use App\Models\Wallet;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WalletRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function findById(int $id): ?Wallet;

    public function findByUserId(int $userId): ?Wallet;

    public function getAllByUserId(int $userId): Collection;

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------

    public function create(int $userId, array $data): Wallet;

    public function updateStatus(int $id, string $status): bool;

    public function updateBalance(int $id, float $availableBalance, ?float $pendingBalance = null): bool;

    public function incrementBalance(int $id, float $amount): bool;

    public function decrementBalance(int $id, float $amount): bool;

    public function incrementPending(int $id, float $amount): bool;

    public function decrementPending(int $id, float $amount): bool;

    /** نقل مبلغ من pending إلى available */
    public function settlePending(int $id, float $amount): bool;

    public function delete(int $id): bool;

    // ---------------------------------------------------------------
    // Checks
    // ---------------------------------------------------------------

    public function hasSufficientBalance(int $id, float $amount): bool;

    public function isActive(int $id): bool;

    public function existsByUserId(int $userId): bool;
}
