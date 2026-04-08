<?php

namespace App\Repositories;

use App\Models\LedgerEntry;
use App\Contracts\Repositories\LedgerEntryRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class LedgerEntryRepository implements LedgerEntryRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- Helpers -------------------
    protected function getEntryTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "ledger_entry_type.{$typeKey}");
    }

    // ------------------- Retrieval -------------------
    public function findById(int $id, array $with = []): ?LedgerEntry
    {
        return LedgerEntry::with($with)->find($id);
    }

    public function getByWalletId(int $walletId, int $perPage = 50, array $with = []): LengthAwarePaginator
    {
        return LedgerEntry::with($with)
            ->where('wallet_id', $walletId)
            ->orderBy('id')
            ->paginate($perPage);
    }

    public function getByTransactionId(int $transactionId, array $with = []): Collection
    {
        return LedgerEntry::with($with)
            ->where('transaction_id', $transactionId)
            ->get();
    }

    public function getLatestByWallet(int $walletId, array $with = []): ?LedgerEntry
    {
        return LedgerEntry::with($with)
            ->where('wallet_id', $walletId)
            ->orderBy('id', 'desc')
            ->first();
    }

    public function getDebits(int $walletId, array $with = []): Collection
    {
        $debitType = $this->getEntryTypeConstant('debit') ?? 'debit';
        return LedgerEntry::with($with)
            ->where('wallet_id', $walletId)
            ->where('entry_type', $debitType)
            ->orderBy('id')
            ->get();
    }

    public function getCredits(int $walletId, array $with = []): Collection
    {
        $creditType = $this->getEntryTypeConstant('credit') ?? 'credit';
        return LedgerEntry::with($with)
            ->where('wallet_id', $walletId)
            ->where('entry_type', $creditType)
            ->orderBy('id')
            ->get();
    }

    public function isDebit(int $id): bool
    {
        $entry = $this->findById($id);
        if (!$entry) {
            return false;
        }
        $debitType = $this->getEntryTypeConstant('debit') ?? 'debit';
        return $entry->entry_type === $debitType;
    }

    public function isCredit(int $id): bool
    {
        $entry = $this->findById($id);
        if (!$entry) {
            return false;
        }
        $creditType = $this->getEntryTypeConstant('credit') ?? 'credit';
        return $entry->entry_type === $creditType;
    }

    // ------------------- Aggregates -------------------
    public function calculateBalance(int $walletId): float
    {
        $debitType  = $this->getEntryTypeConstant('debit')  ?? 'debit';
        $creditType = $this->getEntryTypeConstant('credit') ?? 'credit';

        $balance = LedgerEntry::where('wallet_id', $walletId)
            ->selectRaw(
                'SUM(CASE WHEN entry_type = ? THEN amount WHEN entry_type = ? THEN -amount ELSE 0 END) as balance',
                [$creditType, $debitType]
            )
            ->value('balance');

        return (float) ($balance ?? 0.0);
    }

    public function sumCredits(int $walletId): float
    {
        $creditType = $this->getEntryTypeConstant('credit') ?? 'credit';
        return (float) LedgerEntry::where('wallet_id', $walletId)
            ->where('entry_type', $creditType)
            ->sum('amount');
    }

    public function sumDebits(int $walletId): float
    {
        $debitType = $this->getEntryTypeConstant('debit') ?? 'debit';
        return (float) LedgerEntry::where('wallet_id', $walletId)
            ->where('entry_type', $debitType)
            ->sum('amount');
    }

    // ------------------- Write -------------------
    public function create(int $transactionId, int $walletId, string $entryType, float $amount, float $balanceAfter): LedgerEntry
    {
        return LedgerEntry::create([
            'transaction_id' => $transactionId,
            'wallet_id'      => $walletId,
            'entry_type'     => $entryType,
            'amount'         => $amount,
            'balance_after'  => $balanceAfter,
        ]);
    }

    public function createDoubleSided(
        int $transactionId,
        int $senderWalletId,
        float $senderBalanceAfter,
        int $receiverWalletId,
        float $receiverBalanceAfter,
        float $amount
    ): array {
        $debitType = $this->getEntryTypeConstant('debit') ?? 'debit';
        $creditType = $this->getEntryTypeConstant('credit') ?? 'credit';

        $debit = $this->create(
            $transactionId,
            $senderWalletId,
            $debitType,
            $amount,
            $senderBalanceAfter
        );

        $credit = $this->create(
            $transactionId,
            $receiverWalletId,
            $creditType,
            $amount,
            $receiverBalanceAfter
        );

        return [$debit, $credit];
    }
}