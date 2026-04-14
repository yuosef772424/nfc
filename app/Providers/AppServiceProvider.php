<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
   use Filament\Support\Facades\FilamentView;
use Illuminate\Contracts\View\View;

// استيراد الواجهات
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\WalletRepositoryInterface;
use App\Contracts\Repositories\AgentProfileRepositoryInterface;
use App\Contracts\Repositories\MerchantProfileRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\CacheRepositoryInterface;
use App\Contracts\Repositories\SessionRepositoryInterface;
use App\Contracts\Repositories\CardRepositoryInterface;
use App\Contracts\Repositories\TransactionRepositoryInterface;
use App\Contracts\Repositories\LedgerEntryRepositoryInterface;
use App\Contracts\Repositories\WithdrawalRepositoryInterface;
use App\Contracts\Repositories\CommissionLogRepositoryInterface;
use App\Contracts\Repositories\NfcDeviceRepositoryInterface;
use App\Contracts\Repositories\MobileDeviceDetailRepositoryInterface;
use App\Contracts\Repositories\PhysicalDeviceDetailRepositoryInterface;
use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Contracts\Repositories\UserKycRepositoryInterface;

// استيراد المستودعات (التنفيذات)
use App\Repositories\UserRepository;
use App\Repositories\WalletRepository;
use App\Repositories\AgentProfileRepository;
use App\Repositories\MerchantProfileRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\AppConfigRepository;
use App\Repositories\CacheRepository;
use App\Repositories\SessionRepository;
use App\Repositories\CardRepository;
use App\Repositories\TransactionRepository;
use App\Repositories\LedgerEntryRepository;
use App\Repositories\WithdrawalRepository;
use App\Repositories\CommissionLogRepository;
use App\Repositories\NfcDeviceRepository;
use App\Repositories\MobileDeviceDetailRepository;
use App\Repositories\PhysicalDeviceDetailRepository;
use App\Repositories\NotificationRepository;
use App\Repositories\UserKycRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // ربط كل Interface بالـ Implementation المناسب
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(WalletRepositoryInterface::class, WalletRepository::class);
        $this->app->bind(AgentProfileRepositoryInterface::class, AgentProfileRepository::class);
        $this->app->bind(MerchantProfileRepositoryInterface::class, MerchantProfileRepository::class);
        $this->app->bind(AuditLogRepositoryInterface::class, AuditLogRepository::class);
        $this->app->bind(AppConfigRepositoryInterface::class, AppConfigRepository::class);
        $this->app->bind(CacheRepositoryInterface::class, CacheRepository::class);
        $this->app->bind(SessionRepositoryInterface::class, SessionRepository::class);
        $this->app->bind(CardRepositoryInterface::class, CardRepository::class);
        $this->app->bind(TransactionRepositoryInterface::class, TransactionRepository::class);
        $this->app->bind(LedgerEntryRepositoryInterface::class, LedgerEntryRepository::class);
        $this->app->bind(WithdrawalRepositoryInterface::class, WithdrawalRepository::class);
        $this->app->bind(CommissionLogRepositoryInterface::class, CommissionLogRepository::class);
        $this->app->bind(NfcDeviceRepositoryInterface::class, NfcDeviceRepository::class);
        $this->app->bind(MobileDeviceDetailRepositoryInterface::class, MobileDeviceDetailRepository::class);
        $this->app->bind(PhysicalDeviceDetailRepositoryInterface::class, PhysicalDeviceDetailRepository::class);
        $this->app->bind(NotificationRepositoryInterface::class, NotificationRepository::class);
        $this->app->bind(UserKycRepositoryInterface::class, UserKycRepository::class);
    }

    /**
     * Bootstrap any application services.
     */

public function boot(): void
{
    FilamentView::registerRenderHook(
        'panels::topbar.start',
        fn (): View => view('filament.hooks.topbar-widget'),
    );

    FilamentView::registerRenderHook(
        'panels::content.end',
        fn (): View => view('filament.hooks.bottom-bar'),
    );
}
}