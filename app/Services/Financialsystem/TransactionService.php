<?php

namespace App\Services\FinancialSystem;

use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\LedgerEntryRepositoryInterface;
use App\Contracts\Repositories\TransactionRepositoryInterface;
use App\Contracts\Repositories\WalletRepositoryInterface;
use App\Traits\AuditableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class TransactionService
{
    use AuditableTrait;

    public function __construct(
        protected TransactionRepositoryInterface $transactionRepo,
        protected WalletRepositoryInterface $walletRepo,
        protected LedgerEntryRepositoryInterface $ledgerRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    // ------------------- تنفيذ الدوال المجردة -------------------
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }

    /**
     * إنشاء معاملة مالية جديدة (نقل أموال بين محفظتين أو من/إلى خارج النظام)
     */
    public function createTransaction(
        ?int $senderWalletId,
        ?int $receiverWalletId,
        float $amount,
        string $type,
        string $description = ''
    ): array {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be positive.']);
        }
        if ($senderWalletId === null && $receiverWalletId === null) {
            throw ValidationException::withMessages(['transaction' => 'At least one wallet must be specified.']);
        }
        if ($senderWalletId === $receiverWalletId) {
            throw ValidationException::withMessages(['transaction' => 'Sender and receiver wallets cannot be the same.']);
        }

        return DB::transaction(function () use ($senderWalletId, $receiverWalletId, $amount, $type, $description) {
            $senderWallet = $senderWalletId ? $this->walletRepo->findById($senderWalletId) : null;
            $receiverWallet = $receiverWalletId ? $this->walletRepo->findById($receiverWalletId) : null;

            if ($senderWallet && $senderWallet->status !== 'active') {
                throw ValidationException::withMessages(['sender_wallet' => 'Sender wallet is not active.']);
            }
            if ($receiverWallet && $receiverWallet->status !== 'active') {
                throw ValidationException::withMessages(['receiver_wallet' => 'Receiver wallet is not active.']);
            }
            if ($senderWallet && !$this->walletRepo->hasSufficientBalance($senderWallet->id, $amount)) {
                throw ValidationException::withMessages(['amount' => 'Insufficient balance in sender wallet.']);
            }

            $oldSenderBalance = $senderWallet?->available_balance ?? 0;
            $oldReceiverBalance = $receiverWallet?->available_balance ?? 0;

            if ($senderWallet) {
                $this->walletRepo->decrementBalance($senderWallet->id, $amount);
            }
            if ($receiverWallet) {
                $this->walletRepo->incrementBalance($receiverWallet->id, $amount);
            }

            $newSenderBalance = $senderWallet ? ($oldSenderBalance - $amount) : 0;
            $newReceiverBalance = $receiverWallet ? ($oldReceiverBalance + $amount) : 0;

            $transaction = $this->transactionRepo->create([
                'sender_wallet_id'   => $senderWalletId,
                'receiver_wallet_id' => $receiverWalletId,
                'amount'             => $amount,
                'type'               => $type,
                'status'             => 'completed',
                'transaction_uuid'   => (string) Str::uuid(),
                'description'        => $description,
            ]);

            if ($senderWallet) {
                $this->ledgerRepo->create(
                    transactionId: $transaction->id,
                    walletId: $senderWallet->id,
                    entryType: 'debit',
                    amount: $amount,
                    balanceAfter: $newSenderBalance
                );
            }
            if ($receiverWallet) {
                $this->ledgerRepo->create(
                    transactionId: $transaction->id,
                    walletId: $receiverWallet->id,
                    entryType: 'credit',
                    amount: $amount,
                    balanceAfter: $newReceiverBalance
                );
            }

            if ($senderWallet) {
                $this->checkLedgerBalance($senderWallet->id, 'transaction_' . $type);
            }
            if ($receiverWallet) {
                $this->checkLedgerBalance($receiverWallet->id, 'transaction_' . $type);
            }

            $this->logAudit(
                action: 'transaction_created',
                entity: 'transaction',
                entityId: $transaction->id,
                userId: auth()->id(),
                oldData: null,
                newData: [
                    'sender_wallet_id'   => $senderWalletId,
                    'receiver_wallet_id' => $receiverWalletId,
                    'amount'             => $amount,
                    'type'               => $type,
                ]
            );

            return [
                'transaction_id' => $transaction->id,
                'uuid'           => $transaction->transaction_uuid,
                'amount'         => $amount,
                'type'           => $type,
                'status'         => 'completed',
                'sender_wallet_id'   => $senderWalletId,
                'receiver_wallet_id' => $receiverWalletId,
                'sender_new_balance'   => $newSenderBalance ?: null,
                'receiver_new_balance' => $newReceiverBalance ?: null,
            ];
        });
    }

    /**
     * إعادة معاملة (استرداد أموال) – إنشاء معاملة عكسية
     */
    public function refundTransaction(int $originalTransactionId, string $reason = ''): array
    {
        $original = $this->transactionRepo->findById($originalTransactionId);
        if (!$original) {
            throw ValidationException::withMessages(['transaction' => 'Original transaction not found.']);
        }
        if (!$this->transactionRepo->isRefundable($originalTransactionId)) {
            throw ValidationException::withMessages(['transaction' => 'This transaction cannot be refunded.']);
        }
        if ($original->refunded_at !== null) {
            throw ValidationException::withMessages(['transaction' => 'Transaction already refunded.']);
        }

        $refund = $this->createTransaction(
            senderWalletId: $original->receiver_wallet_id,
            receiverWalletId: $original->sender_wallet_id,
            amount: $original->amount,
            type: 'refund',
            description: "Refund of transaction #{$originalTransactionId}. Reason: {$reason}"
        );

        $this->transactionRepo->update($originalTransactionId, [
            'refunded_at' => now(),
            'refund_transaction_id' => $refund['transaction_id']
        ]);

        return $refund;
    }

    /**
     * تحديث حالة معاملة (للمعاملات المعلقة مثلاً)
     */
    public function updateTransactionStatus(int $transactionId, string $status, ?string $failureReason = null): bool
    {
        $transaction = $this->transactionRepo->findById($transactionId);
        if (!$transaction) {
            throw ValidationException::withMessages(['transaction' => 'Transaction not found.']);
        }
        if ($transaction->status === 'completed') {
            throw ValidationException::withMessages(['transaction' => 'Cannot change status of completed transaction.']);
        }

        $updated = $this->transactionRepo->updateStatus($transactionId, $status, $failureReason);
        if ($updated) {
            $this->logAudit(
                action: 'transaction_status_updated',
                entity: 'transaction',
                entityId: $transactionId,
                userId: auth()->id(),
                oldData: ['status' => $transaction->status],
                newData: ['status' => $status, 'failure_reason' => $failureReason]
            );
        }
        return $updated;
    }

    // ------------------- استعلامات -------------------
    public function getTransactionByUuid(string $uuid, array $with = []): ?array
    {
        return $this->transactionRepo->findByUuid($uuid, $with)?->toArray();
    }

    public function getTransactionsByWallet(int $walletId, array $filters = [], int $perPage = 20, array $with = []): array
    {
        return $this->transactionRepo->getByWalletId($walletId, $filters, $perPage, $with)->toArray();
    }

    public function getSentTransactions(int $walletId, int $perPage = 20, array $with = []): array
    {
        return $this->transactionRepo->getSentByWallet($walletId, $perPage, $with)->toArray();
    }

    public function getReceivedTransactions(int $walletId, int $perPage = 20, array $with = []): array
    {
        return $this->transactionRepo->getReceivedByWallet($walletId, $perPage, $with)->toArray();
    }

    // ------------------- دوال مساعدة -------------------
    protected function checkLedgerBalance(int $walletId, string $action): void
    {
        if (!$this->ledgerRepo->isBalanced($walletId)) {
            $difference = $this->ledgerRepo->getBalanceDifference($walletId);
            $this->logAudit(
                action: 'ledger_imbalance',
                entity: 'wallet',
                entityId: $walletId,
                userId: null,
                oldData: null,
                newData: [
                    'action'     => $action,
                    'difference' => $difference,
                    'message'    => "Ledger imbalance detected for wallet {$walletId} after {$action}."
                ]
            );
        }
    }
}