<?php

namespace App\Contracts\Services;

use App\Models\LedgerEntry;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface LedgerServiceInterface
{
    // ---------------------------------------------------------------
    // Entry Creation
    // ---------------------------------------------------------------

    /**
     * تسجيل قيد مزدوج (debit + credit) لمعاملة تحويل
     * يُحدّث wallet.available_balance تلقائياً عبر model hook
     *
     * @return array{debit: LedgerEntry, credit: LedgerEntry}
     * @throws \App\Exceptions\Ledger\InsufficientBalanceException
     * @throws \App\Exceptions\Ledger\WalletFrozenException
     */
    public function recordTransfer(
        int   $transactionId,
        int   $senderWalletId,
        int   $receiverWalletId,
        float $amount
    ): array;

    /**
     * تسجيل قيد إيداع (credit فقط — لا يوجد sender داخلي)
     *
     * @return LedgerEntry
     */
    public function recordDeposit(int $transactionId, int $walletId, float $amount): LedgerEntry;

    /**
     * تسجيل قيد سحب (debit فقط — لا يوجد receiver داخلي)
     *
     * @return LedgerEntry
     * @throws \App\Exceptions\Ledger\InsufficientBalanceException
     */
    public function recordWithdrawal(int $transactionId, int $walletId, float $amount): LedgerEntry;

    // ---------------------------------------------------------------
    // Reconciliation
    // ---------------------------------------------------------------

    /**
     * التحقق من أن balance في wallets == مجموع القيود في ledger
     *
     * @return array{is_consistent: bool, wallet_balance: float, ledger_balance: float, diff: float}
     */
    public function reconcile(int $walletId): array;

    /**
     * إعادة حساب وتصحيح الرصيد المخزن في wallets من ledger
     * تُستخدم فقط في عمليات الـ maintenance
     */
    public function forceRecalculate(int $walletId): float;

    // ---------------------------------------------------------------
    // History
    // ---------------------------------------------------------------

    public function getByWallet(int $walletId, int $perPage = 50): LengthAwarePaginator;

    public function getByTransaction(int $transactionId): Collection;

    public function getLatestBalance(int $walletId): float;
}
