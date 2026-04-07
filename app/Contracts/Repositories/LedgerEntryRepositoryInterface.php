<?php

namespace App\Contracts\Repositories;

use App\Models\LedgerEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LedgerEntryRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------
    public function findById(int $id): ?LedgerEntry;
    public function getByWalletId(int $walletId, int $perPage = 50): LengthAwarePaginator;
    public function getByTransactionId(int $transactionId): Collection;
    public function getLatestByWallet(int $walletId): ?LedgerEntry;

    // --- الدوال المنقولة من الموديل (النطاقات والدوال المساعدة) ---
    public function getDebits(int $walletId): Collection;          // كانت scopeDebits + forWallet
    public function getCredits(int $walletId): Collection;         // كانت scopeCredits + forWallet
    public function isDebit(int $id): bool;                        // كانت isDebit
    public function isCredit(int $id): bool;                       // كانت isCredit

    // ---------------------------------------------------------------
    // Aggregates
    // ---------------------------------------------------------------
    public function calculateBalance(int $walletId): float;
    public function sumCredits(int $walletId): float;
    public function sumDebits(int $walletId): float;

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------
    public function create(int $transactionId, int $walletId, string $entryType, float $amount, float $balanceAfter): LedgerEntry;
    public function createDoubleSided(
        int $transactionId,
        int $senderWalletId,
        float $senderBalanceAfter,
        int $receiverWalletId,
        float $receiverBalanceAfter,
        float $amount
    ): array; // [LedgerEntry $debit, LedgerEntry $credit]
}