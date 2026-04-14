<?php

namespace App\Filament\Pages;

use Filament\Pages\Dashboard as BaseDashboard;
use App\Filament\Core\PageModalAction;
use App\Services\FinancialSystem\WalletService;
use App\Services\UserManagement\UserProfileService;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;

class Dashboard extends BaseDashboard
{
    protected static ?string $navigationIcon = 'heroicon-o-home';
    protected static ?string $title = 'الرئيسية';
    protected static ?string $navigationLabel = 'لوحة التحكم';
    protected static ?string $navigationGroup = 'الرئيسية';

    public function getHeaderActions(): array
    {
        return [
            PageModalAction::make('deposit')
                ->setLabel('إيداع')
                ->setIcon('heroicon-o-arrow-down-tray')
                ->setColor('success')
                ->formSchema([
                    TextInput::make('amount')
                        ->label('المبلغ')
                        ->numeric()
                        ->required(),
                    TextInput::make('description')
                        ->label('الوصف')
                        ->default('إيداع يدوي'),
                ])
                ->handler(function (array $data, WalletService $walletService) {
                    $wallet = $walletService->getUserWallet(Auth::id());
                    if ($wallet) {
                        $walletService->deposit($wallet['id'], $data['amount'], $data['description'] ?? '');
                        Notification::make()->title('تم الإيداع بنجاح')->success()->send();
                    }
                }),

            PageModalAction::make('withdraw')
                ->setLabel('سحب')
                ->setIcon('heroicon-o-arrow-up-tray')
                ->setColor('danger')
                ->formSchema([
                    TextInput::make('amount')
                        ->label('المبلغ')
                        ->numeric()
                        ->required(),
                    TextInput::make('description')
                        ->label('الوصف')
                        ->default('سحب يدوي'),
                ])
                ->handler(function (array $data, WalletService $walletService) {
                    $wallet = $walletService->getUserWallet(Auth::id());
                    if ($wallet) {
                        $walletService->withdraw($wallet['id'], $data['amount'], $data['description'] ?? '');
                        Notification::make()->title('تم السحب بنجاح')->success()->send();
                    }
                }),

            PageModalAction::make('transfer')
                ->setLabel('تحويل')
                ->setIcon('heroicon-o-arrows-right-left')
                ->setColor('primary')
                ->formSchema([
                    TextInput::make('phone')
                        ->label('رقم هاتف المستلم')
                        ->required()
                        ->tel(),
                    TextInput::make('amount')
                        ->label('المبلغ')
                        ->numeric()
                        ->required(),
                    TextInput::make('description')
                        ->label('الوصف')
                        ->default('تحويل'),
                ])
                ->handler(function (array $data, WalletService $walletService, UserProfileService $userService) {
                    $senderWallet = $walletService->getUserWallet(Auth::id());
                    $recipient =  66 ;//$userService->findByPhone($data['phone']);
                    
                    if (!$senderWallet) {
                        Notification::make()->title('لا توجد محفظة للمرسل')->danger()->send();
                        return;
                    }
                    
                    if (!$recipient) {
                        Notification::make()->title('المستلم غير موجود')->danger()->send();
                        return;
                    }
                    
                    $receiverWallet = $walletService->getUserWallet($recipient['id']);
                    if (!$receiverWallet) {
                        Notification::make()->title('المستلم لا يملك محفظة')->danger()->send();
                        return;
                    }
                    
                    try {
                        $walletService->transfer(
                            $senderWallet['id'],
                            $receiverWallet['id'],
                            $data['amount'],
                            $data['description'] ?? 'تحويل'
                        );
                        Notification::make()->title('تم التحويل بنجاح')->success()->send();
                    } catch (\Exception $e) {
                        Notification::make()->title('فشل التحويل')->body($e->getMessage())->danger()->send();
                    }
                }),
        ];
    }

    public function getWidgets(): array
    {
        return [
            \App\Filament\Widgets\StatsOverviewWidget::class,
            \App\Filament\Widgets\LatestTransactionsWidget::class,
            \App\Filament\Widgets\TopAgentsWidget::class,
            \App\Filament\Widgets\SystemStatsWidget::class,
        ];
    }
}