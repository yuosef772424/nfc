<?php

namespace App\Filament\Widgets;

use App\Services\System\ReportService;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class SystemStatsWidget extends BaseWidget
{
    protected static ?int $sort = 4;

    protected function getStats(): array
    {
        $reportService = app(ReportService::class);
        $systemStats = $reportService->systemStats();

        return [
            Stat::make('طلبات سحب معلقة', $systemStats['withdrawals']['pending_count'])
                ->description('القيمة: ' . number_format($systemStats['withdrawals']['pending_amount'], 2) . ' YER')
                ->icon('heroicon-o-clock')
                ->color('warning'),

            Stat::make('المستخدمين النشطين', \App\Models\User::where('status', 'active')->count())
                ->icon('heroicon-o-user-group')
                ->color('success'),

            Stat::make('بطاقات NFC نشطة', \App\Models\Card::where('status', 'active')->count())
                ->icon('heroicon-o-credit-card')
                ->color('primary'),

            Stat::make('أجهزة NFC مسجلة', \App\Models\NfcDevice::count())
                ->icon('heroicon-o-device-phone-mobile')
                ->color('gray'),
        ];
    }
}