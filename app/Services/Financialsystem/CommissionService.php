<?php

namespace App\Services\FinancialSystem;

use App\Contracts\Repositories\CommissionLogRepositoryInterface;
use App\Contracts\Repositories\AgentProfileRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Traits\AuditableTrait;
use App\Traits\ConfigurableTrait;
use Illuminate\Validation\ValidationException;

class CommissionService
{
    use AuditableTrait, ConfigurableTrait;

    public function __construct(
        protected CommissionLogRepositoryInterface $commissionLogRepo,
        protected AgentProfileRepositoryInterface $agentProfileRepo,
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    // ------------------- حساب العمولة -------------------
    /**
     * حساب قيمة العمولة لمبلغ معين بناءً على إعدادات الوكيل.
     */
    public function calculateCommission(int $agentId, float $amount): float
    {
        $profile = $this->agentProfileRepo->getByUserId($agentId);
        if (!$profile || !$profile->is_active) {
            return 0.0;
        }

        return $this->agentProfileRepo->calculateCommission($agentId, $amount);
    }

    // ------------------- إنشاء سجل عمولة -------------------
    /**
     * تسجيل عمولة مستحقة لوكيل (أو أي مستلم آخر).
     */
    public function createCommissionLog(
        int $recipientId,
        string $recipientType,
        float $amount,
        string $referenceType,
        int $referenceId,
        ?string $description = null
    ): array {
        if ($amount <= 0) {
            throw ValidationException::withMessages(['amount' => 'Commission amount must be positive.']);
        }

        $allowedRecipientTypes = ['agent', 'merchant', 'system'];
        if (!in_array($recipientType, $allowedRecipientTypes)) {
            throw ValidationException::withMessages(['recipient_type' => 'Invalid recipient type.']);
        }

        $log = $this->commissionLogRepo->create([
            'recipient_id'   => $recipientId,
            'recipient_type' => $recipientType,
            'amount'         => $amount,
            'status'         => 'pending',
            'reference_type' => $referenceType,
            'reference_id'   => $referenceId,
            'description'    => $description,
        ]);

        $this->logAudit(
            'commission_created',
            'commission_log',
            $log->id,
            $recipientId,
            null,
            $log->toArray()
        );

        return $log->toArray();
    }

    // ------------------- تحديث حالة العمولة -------------------
    public function markAsPaid(int $commissionLogId): bool
    {
        $log = $this->commissionLogRepo->findById($commissionLogId);
        if (!$log) {
            throw ValidationException::withMessages(['commission' => 'Commission log not found.']);
        }
        if ($log->status === 'paid') {
            throw ValidationException::withMessages(['commission' => 'Commission already paid.']);
        }

        $updated = $this->commissionLogRepo->markPaid($commissionLogId);
        if ($updated) {
            $this->logAudit(
                'commission_paid',
                'commission_log',
                $commissionLogId,
                $log->recipient_id,
                ['status' => 'pending'],
                ['status' => 'paid']
            );
        }
        return $updated;
    }

    public function markAsCancelled(int $commissionLogId, string $reason = ''): bool
    {
        $log = $this->commissionLogRepo->findById($commissionLogId);
        if (!$log) {
            throw ValidationException::withMessages(['commission' => 'Commission log not found.']);
        }
        if (in_array($log->status, ['paid', 'cancelled'])) {
            throw ValidationException::withMessages(['commission' => 'Commission cannot be cancelled.']);
        }

        $updated = $this->commissionLogRepo->markCancelled($commissionLogId);
        if ($updated) {
            $this->logAudit(
                'commission_cancelled',
                'commission_log',
                $commissionLogId,
                $log->recipient_id,
                ['status' => $log->status],
                ['status' => 'cancelled', 'reason' => $reason]
            );
        }
        return $updated;
    }

    // ------------------- تسوية العمولات المعلقة -------------------
    /**
     * دفع جميع العمولات المعلقة لوكيل معين (عبر تحويل إلى محفظته).
     * تعتمد على TransactionService لإتمام التحويل.
     */
    public function settlePendingCommissionsForAgent(int $agentId, TransactionService $transactionService): array
    {
        $pendingLogs = $this->commissionLogRepo->getPendingByAgent($agentId);
        if ($pendingLogs->isEmpty()) {
            return ['settled_count' => 0, 'total_amount' => 0];
        }

        $totalAmount = $pendingLogs->sum('amount');
        $systemWalletId = $this->getSystemWalletId();
        $agentWallet = $this->userRepo->findById($agentId)?->wallet;

        if (!$agentWallet) {
            throw ValidationException::withMessages(['agent' => 'Agent wallet not found.']);
        }

        // إنشاء معاملة تحويل من محفظة النظام إلى محفظة الوكيل
        $transaction = $transactionService->createTransaction(
            senderWalletId: $systemWalletId,
            receiverWalletId: $agentWallet->id,
            amount: $totalAmount,
            type: 'commission_payout',
            description: "Commission payout for agent #{$agentId}"
        );

        // تحديث حالة كل سجل إلى paid
        $updatedCount = $this->commissionLogRepo->settleAllPendingForAgent($agentId);

        $this->logAudit(
            'commission_settled',
            'agent',
            $agentId,
            null,
            null,
            ['settled_count' => $updatedCount, 'total_amount' => $totalAmount, 'transaction_id' => $transaction['transaction_id']]
        );

        return [
            'settled_count'  => $updatedCount,
            'total_amount'   => $totalAmount,
            'transaction_id' => $transaction['transaction_id'],
        ];
    }

    // ------------------- استعلامات -------------------
    public function getPendingCommissionsForAgent(int $agentId): array
    {
        return $this->commissionLogRepo->getPendingByAgent($agentId)->toArray();
    }

    public function getTotalPendingForAgent(int $agentId): float
    {
        return $this->commissionLogRepo->sumPendingByAgent($agentId);
    }

    public function getTotalPaidForAgent(int $agentId): float
    {
        return $this->commissionLogRepo->sumPaidByAgent($agentId);
    }
}