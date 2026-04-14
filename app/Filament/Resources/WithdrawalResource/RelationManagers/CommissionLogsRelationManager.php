<?php

namespace App\Filament\Resources\WithdrawalResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class CommissionLogsRelationManager extends RelationManager
{
    protected static string $relationship = 'commissionLogs';
    protected static ?string $title = 'سجلات العمولة';
    protected static ?string $label = 'سجل عمولة';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('recipient.name')->label('المستلم'),
                Tables\Columns\TextColumn::make('recipient_type')->label('النوع'),
                Tables\Columns\TextColumn::make('amount')->label('المبلغ')->money('YER'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'paid',
                        'gray'    => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('paid_at')->label('تاريخ الدفع')->dateTime(),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإنشاء')->dateTime(),
            ]);
    }
}