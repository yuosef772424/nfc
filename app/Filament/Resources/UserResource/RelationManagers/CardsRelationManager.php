<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Filament\Core\Columns\BadgeColumn;

class CardsRelationManager extends RelationManager
{
    protected static string $relationship = 'cards';
    protected static ?string $title = 'البطاقات';
    protected static ?string $label = 'بطاقة';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('card_number')->label('رقم البطاقة'),
                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colorMap([
                        'active'  => 'success',
                        'blocked' => 'danger',
                        'expired' => 'gray',
                    ])
                    ->labelMap([
                        'active'  => 'نشطة',
                        'blocked' => 'محظورة',
                        'expired' => 'منتهية',
                    ]),
                Tables\Columns\TextColumn::make('expiry_date')->label('تاريخ الانتهاء')->date(),
                Tables\Columns\TextColumn::make('created_at')->label('تاريخ الإصدار')->dateTime(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}