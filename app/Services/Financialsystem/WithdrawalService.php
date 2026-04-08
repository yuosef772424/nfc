<?php

namespace App\Services\FinancialSystem;

use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\LedgerEntryRepositoryInterface;
use App\Contracts\Repositories\TransactionRepositoryInterface;
use App\Contracts\Repositories\WalletRepositoryInterface;
use App\Contracts\Repositories\WithdrawalRepositoryInterface;
use App\Traits\AuditableTrait;
use App\Traits\ConfigurableTrait;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class WithdrawalService
{
    use AuditableTrait, ConfigurableTrait;

    public function __construct(
        protected WithdrawalRepositoryInterface $withdrawalRepo,
        protected WalletRepositoryInterface $walletRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected LedgerEntryRepositoryInterface $ledgerRepo,
        protected TransactionRepositoryInterface $transactionRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    // ------------------- تنفيذ الدوال المجردة -------------------
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    // ------------------- دوال الإعدادات (من ConfigurableTrait أو محلية) -------------------
    protected function getVerificationCodeLength(): int
    {
        return (int) $this->configRepo->getValue('withdrawal', 'verification_code_length') ?? 6;
    }

    protected function getPendingExpiryMinutes(): int
    {
        return (int) $this->configRepo->getValue('withdrawal', 'pending_expiry_minutes') ?? 30;
    }

    protected function getWithdrawalCommissionType(): string
    {
        return $this->configRepo->getValue('fee', 'withdrawal_commission_type') ?? 'percentage';
    }

    protected function getWithdrawalCommissionValue(): float
    {
        return (float) $this->configRepo->getValue('fee', 'withdrawal_commission_value') ?? 0;
    }

    // ------------------- طلب سحب جديد -------------------
    public function requestWithdrawal(int $walletId, int $agentId, float $requestedAmount): array
    {
        $wallet = $this->walletRepo->findById($walletId);
        if (!$wallet || $wallet->status !== 'active') {
            throw ValidationException::withMessages(['wallet' => 'Wallet is not active.']);
        }
        if ($requestedAmount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Amount must be positive.']);
        }

        $commissionAmount = $this->calculateCommission($requestedAmount);
        $totalAmount = $requestedAmount + $commissionAmount;

        if (!$this->walletRepo->hasSufficientBalance($walletId, $totalAmount)) {
            throw ValidationException::withMessages(['amount' => 'Insufficient balance to cover withdrawal amount + fees.']);
        }

        $codeLength = $this->getVerificationCodeLength();
        $verificationCode = (string) random_int(10 ** ($codeLength - 1), (10 ** $codeLength) - 1);
        $expiresAt = now()->addMinutes($this->getPendingExpiryMinutes());

        $withdrawal = $this->withdrawalRepo->create([
            'wallet_id'          => $walletId,
            'agent_id'           => $agentId,
            'requested_amount'   => $requestedAmount,
            'commission_amount'  => $commissionAmount,
            'total_amount'       => $totalAmount,
            'commission_type'    => $this->getWithdrawalCommissionType(),
            'commission_value'   => $this->getWithdrawalCommissionValue(),
            'verification_code'  => bcrypt($verificationCode),
            'expires_at'         => $expiresAt,
            'status'             => 'pending',
        ]);

        $this->logAudit(
            action: 'withdrawal_requested',
            entity: 'withdrawal',
            entityId: $withdrawal->id,
            userId: $agentId,
            oldData: null,
            newData: ['requested_amount' => $requestedAmount, 'commission' => $commissionAmount]
        );

        return [
            'withdrawal_id'      => $withdrawal->id,
            'verification_code'  => $verificationCode,
            'expires_at'         => $expiresAt->toDateTimeString(),
        ];
    }

    // ------------------- تأكيد السحب -------------------
    public function confirmWithdrawal(int $withdrawalId, string $code): array
    {
        return DB::transaction(function () use ($withdrawalId, $code) {
            $withdrawal = $this->withdrawalRepo->findById($withdrawalId);
            if (!$withdrawal || $withdrawal->status !== 'pending') {
                throw ValidationException::withMessages(['withdrawal' => 'Invalid or already processed withdrawal.']);
            }
            if ($withdrawal->expires_at->isPast()) {
                throw ValidationException::withMessages(['withdrawal' => 'Withdrawal request has expired.']);
            }
            if (!$this->withdrawalRepo->verifyCode($withdrawalId, $code)) {
                throw ValidationException::withMessages(['code' => 'Invalid verification code.']);
            }

            $wallet = $this->walletRepo->findById($withdrawal->wallet_id);
            if (!$wallet || $wallet->available_balance < $withdrawal->total_amount) {
                throw ValidationException::withMessages(['wallet' => 'Insufficient balance for withdrawal.']);
            }

            // 1. خصم total_amount من محفظة المستخدم
            $oldUserBalance = $wallet->available_balance;
            $this->walletRepo->decrementBalance($wallet->id, $withdrawal->total_amount);
            $newUserBalance = $oldUserBalance - $withdrawal->total_amount;

            // 2. إضافة commission_amount إلى محفظة النظام
            $systemWalletId = $this->getSystemWalletId();
            $this->walletRepo->incrementBalance($systemWalletId, $withdrawal->commission_amount);

            // 3. تحديث حالة السحب
            $this->withdrawalRepo->markCompleted($withdrawalId);

            // 4. قيد دفتر الأستاذ للمستخدم (debit)
            $this->ledgerRepo->create(
                transactionId: $withdrawalId,
                walletId: $wallet->id,
                entryType: 'debit',
                amount: $withdrawal->total_amount,
                balanceAfter: $newUserBalance
            );

            // 5. قيد دفتر الأستاذ للنظام (credit)
            $systemWallet = $this->walletRepo->findById($systemWalletId);
            $newSystemBalance = $systemWallet->available_balance + $withdrawal->commission_amount;
            $this->ledgerRepo->create(
                transactionId: $withdrawalId,
                walletId: $systemWalletId,
                entryType: 'credit',
                amount: $withdrawal->commission_amount,
                balanceAfter: $newSystemBalance
            );

            // 6. تسجيل معاملة منفصلة (اختياري)
            $this->transactionRepo->create([
                'sender_wallet_id'   => $wallet->id,
                'receiver_wallet_id' => null,
                'amount'             => $withdrawal->total_amount,
                'type'               => 'withdrawal',
                'status'             => 'completed',
                'transaction_uuid'   => (string) Str::uuid(),
                'description'        => "Withdrawal #{$withdrawalId}",
            ]);

            // 7. فحص توازن الدفتر
            $this->checkLedgerBalance($wallet->id, 'withdrawal_complete_user');
            $this->checkLedgerBalance($systemWalletId, 'withdrawal_complete_system');

            // 8. تسجيل حدث التدقيق
            $this->logAudit(
                action: 'withdrawal_completed',
                entity: 'withdrawal',
                entityId: $withdrawalId,
                userId: $withdrawal->agent_id,
                oldData: null,
                newData: [
                    'total_amount' => $withdrawal->total_amount,
                    'commission'   => $withdrawal->commission_amount,
                    'net_to_user'  => $withdrawal->requested_amount,
                ]
            );

            return [
                'withdrawal_id'      => $withdrawalId,
                'status'             => 'completed',
                'new_wallet_balance' => $newUserBalance,
                'commission'         => $withdrawal->commission_amount,
            ];
        });
    }

    // ------------------- إلغاء السحب -------------------
    public function cancelWithdrawal(int $withdrawalId, string $reason = 'user_cancelled'): bool
    {
        $withdrawal = $this->withdrawalRepo->findById($withdrawalId);
        if (!$withdrawal || $withdrawal->status !== 'pending') {
            throw ValidationException::withMessages(['withdrawal' => 'Cannot cancel non-pending withdrawal.']);
        }

        $cancelled = $this->withdrawalRepo->markCancelled($withdrawalId);
        if ($cancelled) {
            $this->logAudit(
                action: 'withdrawal_cancelled',
                entity: 'withdrawal',
                entityId: $withdrawalId,
                userId: $withdrawal->agent_id,
                oldData: null,
                newData: ['reason' => $reason]
            );
        }
        return $cancelled;
    }

    // ------------------- إلغاء السحوبات المنتهية -------------------
    public function expireExpiredPendingWithdrawals(): int
    {
        $expired = $this->withdrawalRepo->getPendingExpired();
        $count = 0;
        foreach ($expired as $withdrawal) {
            try {
                $this->cancelWithdrawal($withdrawal->id, 'expired');
                $count++;
            } catch (\Exception $e) {
                \Log::error("Failed to auto-cancel withdrawal {$withdrawal->id}: " . $e->getMessage());
            }
        }
        return $count;
    }

    // ------------------- حساب العمولة -------------------
    protected function calculateCommission(float $amount): float
    {
        $type = $this->getWithdrawalCommissionType();
        $value = $this->getWithdrawalCommissionValue();
        if ($type === 'percentage') {
            return round($amount * ($value / 100), 2);
        }
        return $value;
    }

    // ------------------- فحص توازن دفتر الأستاذ -------------------
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

    // ------------------- دوال استعلام -------------------
    public function getWithdrawal(int $id, array $with = []): ?array
    {
        return $this->withdrawalRepo->findById($id, $with)?->toArray();
    }

    public function getWithdrawalsByWallet(int $walletId, int $perPage = 20, array $with = []): array
    {
        return $this->withdrawalRepo->getByWalletId($walletId, $perPage, $with)->toArray();
    }

    public function getWithdrawalsByAgent(int $agentId, int $perPage = 20, array $with = []): array
    {
        return $this->withdrawalRepo->getByAgentId($agentId, $perPage, $with)->toArray();
    }

    public function getPendingWithdrawals(array $with = []): array
    {
        return $this->withdrawalRepo->getPending($with)->toArray();
    }

    public function sumCompletedByAgent(int $agentId): float
    {
        return $this->withdrawalRepo->sumByAgent($agentId, 'completed');
    }
}