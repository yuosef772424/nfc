<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;
use App\Services\FinancialSystem\WalletService;
use Filament\Notifications\Notification;

class WalletRelationManager extends RelationManager
{
    protected static string $relationship = 'wallets';
    protected static ?string $title = 'المحافظ';
    protected static ?string $label = 'محفظة';
    protected static ?string $pluralLabel = 'المحافظ';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('currency')
                    ->label('العملة')
                    ->default('YER')
                    ->required(),
                Forms\Components\Select::make('status')
                    ->label('الحالة')
                    ->options([
                        'active'   => 'نشطة',
                        'inactive' => 'غير نشطة',
                        'frozen'   => 'مجمدة',
                    ])
                    ->default('active')
                    ->required(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('currency')->label('العملة'),
                Tables\Columns\TextColumn::make('available_balance')
                    ->label('الرصيد المتاح')
                    ->money('YER'),
                Tables\Columns\TextColumn::make('pending_balance')
                    ->label('الرصيد المعلق')
                    ->money('YER'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'success' => 'active',
                        'gray'    => 'inactive',
                        'danger'  => 'frozen',
                    ]),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime(),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('إضافة محفظة')
                    ->using(function (array $data, RelationManager $livewire) {
                        $walletService = app(WalletService::class);
                        $result = $walletService->createWallet($livewire->getOwnerRecord()->id, $data['currency']);
                        Notification::make()->title('تم إنشاء المحفظة')->success()->send();
                        return $result;
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('deposit')
                    ->label('إيداع')
                    ->icon('heroicon-o-arrow-down-tray')
                    ->color('success')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->label('المبلغ'),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف'),
                    ])
                    ->action(function ($record, array $data, WalletService $service) {
                        $service->deposit($record->id, $data['amount'], $data['description'] ?? '');
                        Notification::make()->title('تم الإيداع بنجاح')->success()->send();
                    }),
                Tables\Actions\Action::make('withdraw')
                    ->label('سحب')
                    ->icon('heroicon-o-arrow-up-tray')
                    ->color('danger')
                    ->form([
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->label('المبلغ'),
                    ])
                    ->action(function ($record, array $data, WalletService $service) {
                        $service->withdraw($record->id, $data['amount']);
                        Notification::make()->title('تم السحب بنجاح')->success()->send();
                    }),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}