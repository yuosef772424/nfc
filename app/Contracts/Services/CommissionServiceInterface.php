<?php

namespace App\Contracts\Services;

use App\Models\AgentWallet;
use App\Models\CommissionLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CommissionServiceInterface
{
    // ---------------------------------------------------------------
    // Calculation
    // ---------------------------------------------------------------

    /**
     * حساب العمولة بناءً على إعدادات الوكيل الحالية
     *
     * @return array{commission_type: string, commission_value: float, commission_amount: float, total_amount: float}
     */
    public function calculate(int $agentId, float $requestedAmount): array;

    // ---------------------------------------------------------------
    // Recording
    // ---------------------------------------------------------------

    /**
     * تسجيل عمولة بعد إتمام عملية سحب
     * يُنشئ commission_log ويُضيف للـ agent_wallet
     */
    public function recordForWithdrawal(int $withdrawalId, int $agentId, float $amount): CommissionLog;

    /**
     * تسجيل عمولة عملية دفع (رسوم الشبكة للشركة)
     */
    public function recordForTransaction(int $transactionId, float $amount, string $recipientType = 'system', ?int $recipientId = null): CommissionLog;

    // ---------------------------------------------------------------
    // Agent Wallet
    // ---------------------------------------------------------------

    public function getAgentWallet(int $agentId): ?AgentWallet;

    public function getAgentBalance(int $agentId): float;

    public function getAgentTotalEarned(int $agentId): float;

    /** تحويل أرباح الوكيل لمحفظته الرئيسية أو حسابه البنكي */
    public function settleAgentEarnings(int $agentId): bool;

    // ---------------------------------------------------------------
    // History
    // ---------------------------------------------------------------

    public function getLogByAgent(int $agentId, int $perPage = 20): LengthAwarePaginator;

    public function getPendingByAgent(int $agentId): \Illuminate\Database\Eloquent\Collection;

    public function getSummaryByAgent(int $agentId): array; // {total_earned, pending, paid}
}
