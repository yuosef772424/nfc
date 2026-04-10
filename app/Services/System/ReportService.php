<?php

namespace App\Services\System;

use App\Contracts\Repositories\TransactionRepositoryInterface;
use App\Contracts\Repositories\WithdrawalRepositoryInterface;
use App\Contracts\Repositories\CommissionLogRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\WalletRepositoryInterface;
use App\Contracts\Repositories\LedgerEntryRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Traits\ConfigurableTrait;
use Carbon\Carbon;
use Illuminate\Support\Collection;

class ReportService
{
    use ConfigurableTrait;

    public function __construct(
        protected TransactionRepositoryInterface $transactionRepo,
        protected WithdrawalRepositoryInterface $withdrawalRepo,
        protected CommissionLogRepositoryInterface $commissionLogRepo,
        protected UserRepositoryInterface $userRepo,
        protected WalletRepositoryInterface $walletRepo,
        protected LedgerEntryRepositoryInterface $ledgerRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    // ==================== التقارير المالية العامة ====================

    /**
     * ملخص مالي للفترة المحددة (إجمالي الإيداعات، السحوبات، التحويلات، العمولات).
     */
    public function financialSummary(?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        // تجميع المعاملات حسب النوع
        $transactions = $this->transactionRepo->getAll([
            'created_at_from' => $startDate,
            'created_at_to'   => $endDate,
        ], 10000); // نجلب عدد كبير للتجميع

        $totalDeposits = $transactions->where('type', 'deposit')->sum('amount');
        $totalWithdrawals = $transactions->where('type', 'withdrawal')->sum('amount');
        $totalTransfers = $transactions->where('type', 'transfer')->sum('amount');
        $totalCommissions = $transactions->where('type', 'commission_payout')->sum('amount');

        // السحوبات من جدول السحوبات (المؤكدة)
        $completedWithdrawals = $this->withdrawalRepo->getCompleted();
        $withdrawalTotal = $completedWithdrawals->whereBetween('completed_at', [$startDate, $endDate])->sum('total_amount');
        $withdrawalCommissionTotal = $completedWithdrawals->whereBetween('completed_at', [$startDate, $endDate])->sum('commission_amount');

        return [
            'period' => [
                'start' => $startDate->toDateString(),
                'end'   => $endDate->toDateString(),
            ],
            'deposits' => [
                'count' => $transactions->where('type', 'deposit')->count(),
                'total' => round($totalDeposits, 2),
            ],
            'withdrawals' => [
                'count'          => $completedWithdrawals->whereBetween('completed_at', [$startDate, $endDate])->count(),
                'total_amount'   => round($withdrawalTotal, 2),
                'total_commission'=> round($withdrawalCommissionTotal, 2),
            ],
            'transfers' => [
                'count' => $transactions->where('type', 'transfer')->count(),
                'total' => round($totalTransfers, 2),
            ],
            'commissions' => [
                'total_paid' => round($totalCommissions, 2),
            ],
            'net_profit' => round($withdrawalCommissionTotal + $totalCommissions, 2),
        ];
    }

    /**
     * تقرير المعاملات اليومية (للرسم البياني).
     */
    public function dailyTransactionVolume(int $days = 30): Collection
    {
        $startDate = Carbon::now()->subDays($days)->startOfDay();
        $endDate = Carbon::now()->endOfDay();

        // استعلام مجمع حسب اليوم (باستخدام DB مباشرة لأن الـ Repository قد لا يدعم group by)
        $results = \App\Models\WalletTransaction::selectRaw('DATE(created_at) as date, type, SUM(amount) as total')
            ->whereBetween('created_at', [$startDate, $endDate])
            ->whereIn('type', ['deposit', 'withdrawal', 'transfer'])
            ->groupBy('date', 'type')
            ->orderBy('date')
            ->get();

        $grouped = $results->groupBy('date')->map(function ($dayItems) {
            return [
                'date'      => $dayItems->first()->date,
                'deposit'   => round($dayItems->where('type', 'deposit')->sum('total'), 2),
                'withdrawal'=> round($dayItems->where('type', 'withdrawal')->sum('total'), 2),
                'transfer'  => round($dayItems->where('type', 'transfer')->sum('total'), 2),
            ];
        })->values();

        return $grouped;
    }

    // ==================== تقارير الوكلاء ====================

    /**
     * ملخص أداء وكيل معين.
     */
    public function agentPerformance(int $agentId, ?Carbon $startDate = null, ?Carbon $endDate = null): array
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $agent = $this->userRepo->findById($agentId);
        if (!$agent || $agent->user_type !== 'agent') {
            throw new \InvalidArgumentException('Invalid agent ID.');
        }

        // السحوبات التي قام بها الوكيل
        $withdrawals = $this->withdrawalRepo->getByAgentId($agentId, 1000);
        $filteredWithdrawals = $withdrawals->filter(fn($w) => $w->created_at->between($startDate, $endDate));

        $totalWithdrawalAmount = $filteredWithdrawals->sum('total_amount');
        $totalCommissionEarned = $filteredWithdrawals->sum('commission_amount');
        $completedCount = $filteredWithdrawals->where('status', 'completed')->count();
        $pendingCount = $filteredWithdrawals->where('status', 'pending')->count();

        // العمولات المسجلة في CommissionLog
        $pendingCommission = $this->commissionLogRepo->sumPendingByAgent($agentId);
        $paidCommission = $this->commissionLogRepo->sumPaidByAgent($agentId);

        return [
            'agent_id'   => $agentId,
            'agent_name' => $agent->name,
            'period'     => ['start' => $startDate->toDateString(), 'end' => $endDate->toDateString()],
            'withdrawals' => [
                'total_amount'      => round($totalWithdrawalAmount, 2),
                'total_commission'  => round($totalCommissionEarned, 2),
                'completed_count'   => $completedCount,
                'pending_count'     => $pendingCount,
            ],
            'commissions' => [
                'pending' => round($pendingCommission, 2),
                'paid'    => round($paidCommission, 2),
            ],
        ];
    }

    /**
     * قائمة أفضل الوكلاء (حسب حجم السحوبات أو العمولات).
     */
    public function topAgents(int $limit = 10, string $orderBy = 'commission', ?Carbon $startDate = null, ?Carbon $endDate = null): Collection
    {
        $startDate = $startDate ?? Carbon::now()->startOfMonth();
        $endDate = $endDate ?? Carbon::now()->endOfDay();

        $agentIds = $this->userRepo->getAgents()->pluck('id');
        $agentsData = [];

        foreach ($agentIds as $agentId) {
            $withdrawals = $this->withdrawalRepo->getByAgentId($agentId, 1000);
            $filtered = $withdrawals->filter(fn($w) => $w->created_at->between($startDate, $endDate) && $w->status === 'completed');

            $agentsData[] = [
                'agent_id'      => $agentId,
                'agent_name'    => $this->userRepo->findById($agentId)?->name,
                'total_withdrawals' => round($filtered->sum('total_amount'), 2),
                'total_commission'  => round($filtered->sum('commission_amount'), 2),
                'transactions_count'=> $filtered->count(),
            ];
        }

        $collection = collect($agentsData);
        if ($orderBy === 'commission') {
            return $collection->sortByDesc('total_commission')->take($limit)->values();
        }
        return $collection->sortByDesc('total_withdrawals')->take($limit)->values();
    }

    // ==================== تقارير النظام ====================

    /**
     * إحصائيات عامة عن النظام (إجمالي المستخدمين، المحافظ، الأرصدة).
     */
    public function systemStats(): array
    {
        $totalUsers = $this->userRepo->getAll([], 1)->total();
        $totalAgents = $this->userRepo->getAgents()->count();
        $totalMerchants = $this->userRepo->getMerchants()->count();

        $wallets = $this->walletRepo->getAll([], 10000);
        $totalBalance = $wallets->sum('available_balance');
        $totalPendingBalance = $wallets->sum('pending_balance');

        $pendingWithdrawalsCount = $this->withdrawalRepo->getPending()->count();
        $pendingWithdrawalsAmount = $this->withdrawalRepo->getPending()->sum('total_amount');

        return [
            'users' => [
                'total'    => $totalUsers,
                'agents'   => $totalAgents,
                'merchants'=> $totalMerchants,
            ],
            'wallets' => [
                'total_balance'         => round($totalBalance, 2),
                'total_pending_balance' => round($totalPendingBalance, 2),
            ],
            'withdrawals' => [
                'pending_count'  => $pendingWithdrawalsCount,
                'pending_amount' => round($pendingWithdrawalsAmount, 2),
            ],
        ];
    }

    /**
     * تقرير الأرباح الشهرية (صافي الربح).
     */
    public function monthlyProfitReport(int $months = 12): Collection
    {
        $report = collect();
        for ($i = $months - 1; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->startOfMonth();
            $nextMonth = $month->copy()->endOfMonth();

            $completedWithdrawals = $this->withdrawalRepo->getCompleted();
            $monthlyWithdrawals = $completedWithdrawals->whereBetween('completed_at', [$month, $nextMonth]);

            $commissionFromWithdrawals = $monthlyWithdrawals->sum('commission_amount');

            // العمولات المدفوعة للوكلاء (من CommissionLog)
            $paidCommissions = $this->commissionLogRepo->getAll(['status' => 'paid'], 10000)
                ->filter(fn($log) => $log->paid_at && $log->paid_at->between($month, $nextMonth))
                ->sum('amount');

            $report->push([
                'year_month'      => $month->format('Y-m'),
                'withdrawal_commission' => round($commissionFromWithdrawals, 2),
                'agent_commissions_paid' => round($paidCommissions, 2),
                'net_profit'       => round($commissionFromWithdrawals - $paidCommissions, 2),
            ]);
        }
        return $report;
    }

    // ==================== تصدير البيانات ====================

    /**
     * تحويل Collection إلى CSV.
     */
    public function exportToCsv(Collection $data, string $filename = 'report.csv'): string
    {
        if ($data->isEmpty()) {
            return '';
        }

        $csv = fopen('php://temp', 'r+');
        fputcsv($csv, array_keys($data->first()));

        foreach ($data as $row) {
            fputcsv($csv, (array) $row);
        }

        rewind($csv);
        $content = stream_get_contents($csv);
        fclose($csv);

        return $content;
    }
}