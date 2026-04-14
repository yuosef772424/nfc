<?php

namespace App\Filament\Widgets;

use App\Services\System\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverviewWidget extends BaseWidget
{
    protected static ?int $sort = 1;

    protected function getStats(): array
    {
        $reportService = app(ReportService::class);
        $financialSummary = $reportService->financialSummary(now()->startOfMonth(), now());
        $systemStats = $reportService->systemStats();

        return [
            Stat::make('إجمالي المستخدمين', $systemStats['users']['total'])
                ->description('الوكلاء: ' . $systemStats['users']['agents'] . ' | التجار: ' . $systemStats['users']['merchants'])
                ->icon('heroicon-o-users')
                ->color('success'),

            Stat::make('إجمالي الأرصدة', number_format($systemStats['wallets']['total_balance'], 2) . ' YER')
                ->description('المعلق: ' . number_format($systemStats['wallets']['total_pending_balance'], 2))
                ->icon('heroicon-o-currency-dollar')
                ->color('primary'),

            Stat::make('معاملات الشهر', $financialSummary['deposits']['count'] + $financialSummary['withdrawals']['count'] + $financialSummary['transfers']['count'])
                ->description('الإيداعات: ' . number_format($financialSummary['deposits']['total'], 2) . ' YER')
                ->icon('heroicon-o-arrow-path')
                ->color('warning'),

            Stat::make('صافي الربح', number_format($financialSummary['net_profit'], 2) . ' YER')
                ->description('من عمولات السحب والتحويلات')
                ->icon('heroicon-o-banknotes')
                ->color('danger'),
        ];
    }
}