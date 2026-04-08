<?php

namespace App\Services\FinancialSystem;

use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\LedgerEntryRepositoryInterface;
use App\Contracts\Repositories\TransactionRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\WalletRepositoryInterface;
use App\Traits\AuditableTrait;
use App\Traits\ConfigurableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WalletService
{
    use AuditableTrait, ConfigurableTrait;

    public function __construct(
        protected WalletRepositoryInterface $walletRepo,
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected LedgerEntryRepositoryInterface $ledgerRepo,
        protected TransactionRepositoryInterface $transactionRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    // ------------------- تنفيذ الدوال المجردة -------------------
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    // ------------------- إنشاء المحفظة -------------------
    public function createWallet(int $userId, string $currency = 'USD'): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw ValidationException::withMessages(['user' => 'User not found.']);
        }
        if ($this->walletRepo->existsByUserId($userId)) {
            throw ValidationException::withMessages(['wallet' => 'User already has a wallet.']);
        }

        $wallet = $this->walletRepo->create($userId, [
            'currency'           => $currency,
            'status'             => 'active',
            'available_balance'  => 0,
            'pending_balance'    => 0,
        ]);

        $this->logAudit('wallet_created', 'wallet', $wallet->id, $userId, null, $wallet->toArray());

        return $wallet->toArray();
    }

    // ------------------- استعلامات -------------------
    public function getUserWallet(int $userId, array $with = []): ?array
    {
        return $this->walletRepo->getByUserId($userId, $with)?->toArray();
    }

    // ------------------- تحديث حالة المحفظة -------------------
    public function updateWalletStatus(int $walletId, string $status): bool
    {
        $wallet = $this->walletRepo->findById($walletId);
        if (!$wallet) {
            throw ValidationException::withMessages(['wallet' => 'Wallet not found.']);
        }
        $allowedStatuses = ['active', 'inactive', 'frozen'];
        if (!in_array($status, $allowedStatuses)) {
            throw ValidationException::withMessages(['status' => 'Invalid wallet status.']);
        }

        $oldStatus = $wallet->status;
        $updated = $this->walletRepo->updateStatus($walletId, $status);

        if ($updated) {
            $this->logAudit('wallet_status_updated', 'wallet', $walletId, $wallet->user_id, ['status' => $oldStatus], ['status' => $status]);
        }
        return $updated;
    }

    // ------------------- العمليات المالية -------------------
    public function deposit(int $walletId, float $amount, string $description = ''): array
    {
        return DB::transaction(function () use ($walletId, $amount, $description) {
            $wallet = $this->walletRepo->findById($walletId);
            if (!$wallet || $wallet->status !== 'active') {
                throw ValidationException::withMessages(['wallet' => 'Wallet is not active.']);
            }
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Amount must be positive.']);
            }

            $oldBalance = $wallet->available_balance;
            $this->walletRepo->incrementBalance($walletId, $amount);
            $newBalance = $oldBalance + $amount;

            $transaction = $this->transactionRepo->create([
                'sender_wallet_id'   => null,
                'receiver_wallet_id' => $walletId,
                'amount'             => $amount,
                'type'               => 'deposit',
                'status'             => 'completed',
                'transaction_uuid'   => (string) Str::uuid(),
                'description'        => $description ?: "Deposit to wallet #{$walletId}",
            ]);

            $this->ledgerRepo->create(
                transactionId: $transaction->id,
                walletId: $walletId,
                entryType: 'credit',
                amount: $amount,
                balanceAfter: $newBalance
            );

            $this->checkLedgerBalance($walletId, 'deposit');

            return [
                'wallet_id'      => $walletId,
                'amount'         => $amount,
                'new_balance'    => $newBalance,
                'transaction_id' => $transaction->id,
            ];
        });
    }

    public function withdraw(int $walletId, float $amount, string $description = ''): array
    {
        return DB::transaction(function () use ($walletId, $amount, $description) {
            $wallet = $this->walletRepo->findById($walletId);
            if (!$wallet || $wallet->status !== 'active') {
                throw ValidationException::withMessages(['wallet' => 'Wallet is not active.']);
            }
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Amount must be positive.']);
            }
            if (!$this->walletRepo->hasSufficientBalance($walletId, $amount)) {
                throw ValidationException::withMessages(['amount' => 'Insufficient balance.']);
            }

            $oldBalance = $wallet->available_balance;
            $this->walletRepo->decrementBalance($walletId, $amount);
            $newBalance = $oldBalance - $amount;

            $transaction = $this->transactionRepo->create([
                'sender_wallet_id'   => $walletId,
                'receiver_wallet_id' => null,
                'amount'             => $amount,
                'type'               => 'withdrawal',
                'status'             => 'completed',
                'transaction_uuid'   => (string) Str::uuid(),
                'description'        => $description ?: "Withdrawal from wallet #{$walletId}",
            ]);

            $this->ledgerRepo->create(
                transactionId: $transaction->id,
                walletId: $walletId,
                entryType: 'debit',
                amount: $amount,
                balanceAfter: $newBalance
            );

            $this->checkLedgerBalance($walletId, 'withdraw');

            return [
                'wallet_id'      => $walletId,
                'amount'         => $amount,
                'new_balance'    => $newBalance,
                'transaction_id' => $transaction->id,
            ];
        });
    }

    public function transfer(int $fromWalletId, int $toWalletId, float $amount, string $description = ''): array
    {
        return DB::transaction(function () use ($fromWalletId, $toWalletId, $amount, $description) {
            $fromWallet = $this->walletRepo->findById($fromWalletId);
            $toWallet   = $this->walletRepo->findById($toWalletId);

            if (!$fromWallet || $fromWallet->status !== 'active') {
                throw ValidationException::withMessages(['from_wallet' => 'Sender wallet is not active.']);
            }
            if (!$toWallet || $toWallet->status !== 'active') {
                throw ValidationException::withMessages(['to_wallet' => 'Receiver wallet is not active.']);
            }
            if ($amount <= 0) {
                throw ValidationException::withMessages(['amount' => 'Amount must be positive.']);
            }
            if (!$this->walletRepo->hasSufficientBalance($fromWalletId, $amount)) {
                throw ValidationException::withMessages(['amount' => 'Insufficient balance in sender wallet.']);
            }

            $oldFromBalance = $fromWallet->available_balance;
            $oldToBalance   = $toWallet->available_balance;

            $this->walletRepo->decrementBalance($fromWalletId, $amount);
            $this->walletRepo->incrementBalance($toWalletId, $amount);

            $newFromBalance = $oldFromBalance - $amount;
            $newToBalance   = $oldToBalance + $amount;

            $transaction = $this->transactionRepo->create([
                'sender_wallet_id'   => $fromWalletId,
                'receiver_wallet_id' => $toWalletId,
                'amount'             => $amount,
                'type'               => 'transfer',
                'status'             => 'completed',
                'transaction_uuid'   => (string) Str::uuid(),
                'description'        => $description ?: "Transfer from #{$fromWalletId} to #{$toWalletId}",
            ]);

            $this->ledgerRepo->createDoubleSided(
                transactionId:        $transaction->id,
                senderWalletId:       $fromWalletId,
                senderBalanceAfter:   $newFromBalance,
                receiverWalletId:     $toWalletId,
                receiverBalanceAfter: $newToBalance,
                amount:               $amount
            );

            $this->checkLedgerBalance($fromWalletId, 'transfer_sender');
            $this->checkLedgerBalance($toWalletId, 'transfer_receiver');

            return [
                'from_wallet_id'   => $fromWalletId,
                'to_wallet_id'     => $toWalletId,
                'amount'           => $amount,
                'new_from_balance' => $newFromBalance,
                'new_to_balance'   => $newToBalance,
                'transaction_id'   => $transaction->id,
            ];
        });
    }

    public function settlePending(int $walletId, float $amount): bool
    {
        $wallet = $this->walletRepo->findById($walletId);
        if (!$wallet) {
            throw ValidationException::withMessages(['wallet' => 'Wallet not found.']);
        }
        if ($wallet->pending_balance < $amount) {
            throw ValidationException::withMessages(['amount' => 'Pending balance insufficient.']);
        }

        $success = $this->walletRepo->settlePending($walletId, $amount);
        if ($success) {
            $this->logAudit('wallet_pending_settled', 'wallet', $walletId, $wallet->user_id, null, ['settled_amount' => $amount]);
            $this->checkLedgerBalance($walletId, 'settle_pending');
        }
        return $success;
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