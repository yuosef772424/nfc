<?php

namespace App\Contracts\Services;

interface ReportServiceInterface
{
    // ---------------------------------------------------------------
    // Transaction Reports
    // ---------------------------------------------------------------

    /**
     * ملخص المعاملات لفترة زمنية
     *
     * @return array{
     *   total_count: int,
     *   total_volume: float,
     *   by_type: array,
     *   by_status: array,
     *   average_amount: float
     * }
     */
    public function getTransactionSummary(\DateTimeInterface $from, \DateTimeInterface $to, array $filters = []): array;

    /**
     * تقرير المعاملات الفاشلة مع أسبابها
     */
    public function getFailedTransactions(\DateTimeInterface $from, \DateTimeInterface $to, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    // ---------------------------------------------------------------
    // Wallet Reports
    // ---------------------------------------------------------------

    /**
     * إجمالي الأرصدة في النظام (لمراقبة السيولة)
     *
     * @return array{total_available: float, total_pending: float, by_currency: array}
     */
    public function getTotalSystemBalance(): array;

    /**
     * المحافظ الأعلى رصيداً
     */
    public function getTopWallets(int $limit = 10): \Illuminate\Database\Eloquent\Collection;

    // ---------------------------------------------------------------
    // Agent Reports
    // ---------------------------------------------------------------

    /**
     * تقرير أداء الوكلاء
     *
     * @return array{agent_id: int, name: string, withdrawal_count: int, total_volume: float, total_commission: float}[]
     */
    public function getAgentPerformance(\DateTimeInterface $from, \DateTimeInterface $to, int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    public function getAgentCommissionSummary(int $agentId, \DateTimeInterface $from, \DateTimeInterface $to): array;

    // ---------------------------------------------------------------
    // User Reports
    // ---------------------------------------------------------------

    /**
     * إحصائيات تسجيل المستخدمين
     *
     * @return array{total: int, by_type: array, by_status: array, new_this_month: int, verified: int}
     */
    public function getUserStats(): array;

    /**
     * تقرير نشاط مستخدم بعينه
     *
     * @return array{transactions: array, withdrawals: array, disputes: array, kyc_status: string}
     */
    public function getUserActivityReport(int $userId, \DateTimeInterface $from, \DateTimeInterface $to): array;

    // ---------------------------------------------------------------
    // Revenue Reports
    // ---------------------------------------------------------------

    /**
     * تقرير إيرادات النظام (رسوم المعاملات + فارق العمولات)
     *
     * @return array{total_fees: float, by_type: array, by_period: array}
     */
    public function getRevenueReport(\DateTimeInterface $from, \DateTimeInterface $to): array;

    // ---------------------------------------------------------------
    // Dispute Reports
    // ---------------------------------------------------------------

    public function getDisputeStats(\DateTimeInterface $from, \DateTimeInterface $to): array;

    // ---------------------------------------------------------------
    // Export
    // ---------------------------------------------------------------

    /**
     * تصدير تقرير كـ CSV أو Excel
     *
     * @param  string $reportType  'transactions' | 'agents' | 'users' | 'revenue'
     * @param  string $format      'csv' | 'xlsx'
     * @return string              مسار الملف المُنشأ
     */
    public function export(string $reportType, \DateTimeInterface $from, \DateTimeInterface $to, string $format = 'csv'): string;
}
