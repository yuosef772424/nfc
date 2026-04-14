<?php

namespace App\Filament\Widgets;

use App\Models\WalletTransaction;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class LatestTransactionsWidget extends BaseWidget
{
    protected static ?int $sort = 2;

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WalletTransaction::with(['senderWallet.user', 'receiverWallet.user'])
                    ->latest()
                    ->limit(5)
            )
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'deposit' => 'success',
                        'withdrawal' => 'danger',
                        'transfer' => 'info',
                        'payment' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('amount')->money('YER')->label('المبلغ'),
                Tables\Columns\TextColumn::make('senderWallet.user.name')->label('المرسل')->default('-'),
                Tables\Columns\TextColumn::make('receiverWallet.user.name')->label('المستقبل')->default('-'),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'completed' => 'success',
                        'pending' => 'warning',
                        'failed' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')->label('التاريخ')->dateTime(),
            ])
            ->paginated(false);
    }
}