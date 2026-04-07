<?php

namespace App\Contracts\Services;

use App\Models\Wallet;
use App\Models\WalletTransaction;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WalletServiceInterface
{
    // ---------------------------------------------------------------
    // Wallet Management
    // ---------------------------------------------------------------

    /**
     * إنشاء محفظة لمستخدم جديد
     *
     * @throws \App\Exceptions\Wallet\WalletAlreadyExistsException
     */
    public function createForUser(int $userId, string $currency = 'YER'): Wallet;

    public function getByUserId(int $userId): ?Wallet;

    public function getBalance(int $walletId): array; // {available: float, pending: float}

    public function freeze(int $walletId, string $reason): bool;

    public function unfreeze(int $walletId): bool;

    // ---------------------------------------------------------------
    // Transfers
    // ---------------------------------------------------------------

    /**
     * تحويل بين محفظتين داخلياً
     * يُنشئ: transaction + ledger entries (debit + credit)
     *
     * @throws \App\Exceptions\Wallet\InsufficientBalanceException
     * @throws \App\Exceptions\Wallet\WalletFrozenException
     * @throws \App\Exceptions\Wallet\SameWalletTransferException
     */
    public function transfer(
        int    $senderWalletId,
        int    $receiverWalletId,
        float  $amount,
        string $description = '',
        array  $meta = []
    ): WalletTransaction;

    // ---------------------------------------------------------------
    // Deposits
    // ---------------------------------------------------------------

    /**
     * إيداع مبلغ من مصدر خارجي (bank, cash-in via agent)
     *
     * @throws \App\Exceptions\Wallet\WalletFrozenException
     */
    public function deposit(int $walletId, float $amount, string $description = '', array $meta = []): WalletTransaction;

    // ---------------------------------------------------------------
    // Balance Reconciliation
    // ---------------------------------------------------------------

    /**
     * مقارنة الرصيد في wallets مع مجموع ledger_entries
     * تُستخدم في scheduled jobs للتحقق من الاتساق
     *
     * @return array{wallet_id: int, cached: float, ledger: float, diff: float}
     */
    public function reconcileBalance(int $walletId): array;

    public function recalculateFromLedger(int $walletId): float;

    // ---------------------------------------------------------------
    // History
    // ---------------------------------------------------------------

    public function getTransactionHistory(int $walletId, array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getLedgerEntries(int $walletId, int $perPage = 50): LengthAwarePaginator;
}
