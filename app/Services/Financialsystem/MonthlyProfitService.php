<?php

namespace App\Services\FinancialSystem;

use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\TransactionRepositoryInterface;
use App\Contracts\Repositories\WalletRepositoryInterface;
use App\Contracts\Repositories\WithdrawalRepositoryInterface;
use App\Traits\AuditableTrait;
use App\Traits\ConfigurableTrait;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class MonthlyProfitService
{
    use AuditableTrait, ConfigurableTrait;

    public function __construct(
        protected WithdrawalRepositoryInterface $withdrawalRepo,
        protected WalletRepositoryInterface $walletRepo,
        protected TransactionRepositoryInterface $transactionRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    // ------------------- تنفيذ الدوال المجردة -------------------
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    /**
     * حساب إجمالي أرباح النظام (العمولات) لشهر معين
     */
    public function getTotalProfitForMonth(string $yearMonth): float
    {
        $start = Carbon::parse($yearMonth)->startOfMonth();
        $end   = Carbon::parse($yearMonth)->endOfMonth();

        return \App\Models\Withdrawal::where('status', 'completed')
            ->whereBetween('completed_at', [$start, $end])
            ->sum('commission_amount');
    }

    /**
     * توزيع أرباح الشهر: تحويل إجمالي العمولات إلى معاملة أرباح واحدة
     */
    public function distributeMonthlyProfit(string $yearMonth): array
    {
        // التحقق من عدم التوزيع المسبق
        $existingTransaction = $this->transactionRepo->getAll([
            'type'        => 'profit_distribution',
            'description' => "Monthly profit for {$yearMonth}"
        ], 1);

        if ($existingTransaction->isNotEmpty()) {
            return [
                'total_profit'          => 0,
                'transaction_id'        => null,
                'already_distributed'   => true,
                'message'               => "Profit for {$yearMonth} already distributed."
            ];
        }

        $totalProfit = $this->getTotalProfitForMonth($yearMonth);
        if ($totalProfit <= 0) {
            return [
                'total_profit'          => 0,
                'transaction_id'        => null,
                'already_distributed'   => false,
                'message'               => "No profit to distribute for {$yearMonth}."
            ];
        }

        return DB::transaction(function () use ($totalProfit, $yearMonth) {
            $systemWalletId = $this->getSystemWalletId();

            $transaction = $this->transactionRepo->create([
                'sender_wallet_id'   => $systemWalletId,
                'receiver_wallet_id' => null,
                'amount'             => $totalProfit,
                'type'               => 'profit_distribution',
                'status'             => 'completed',
                'transaction_uuid'   => (string) Str::uuid(),
                'description'        => "Monthly profit for {$yearMonth}",
            ]);

            // تسجيل الحدث
            $this->logAudit(
                action: 'monthly_profit_distributed',
                entity: 'system',
                entityId: $systemWalletId,
                userId: null,
                oldData: null,
                newData: [
                    'year_month'    => $yearMonth,
                    'total_profit'  => $totalProfit,
                    'transaction_id'=> $transaction->id,
                ]
            );

            return [
                'total_profit'       => $totalProfit,
                'transaction_id'     => $transaction->id,
                'already_distributed'=> false,
            ];
        });
    }

    public function distributePreviousMonthProfit(): array
    {
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');
        return $this->distributeMonthlyProfit($previousMonth);
    }

    public function getLastTwelveMonthsProfit(): array
    {
        $months = [];
        for ($i = 11; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i)->format('Y-m');
            $months[] = [
                'year_month'   => $month,
                'total_profit' => $this->getTotalProfitForMonth($month),
            ];
        }
        return $months;
    }
}