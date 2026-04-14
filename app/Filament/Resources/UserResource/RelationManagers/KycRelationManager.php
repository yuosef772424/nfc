<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use App\Services\UserManagement\KycService;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Auth;

class KycRelationManager extends RelationManager
{
    protected static string $relationship = 'kyc';
    protected static ?string $title = 'التحقق من الهوية (KYC)';
    protected static ?string $label = 'طلب KYC';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id_type')->label('نوع الهوية'),
                Tables\Columns\TextColumn::make('id_number')->label('رقم الهوية'),
                Tables\Columns\TextColumn::make('id_expiry_date')->label('تاريخ الانتهاء')->date(),
                Tables\Columns\IconColumn::make('verified_at')
                    ->label('تم التحقق')
                    ->boolean()
                    ->trueIcon('heroicon-o-check-badge')
                    ->falseIcon('heroicon-o-clock'),
                Tables\Columns\TextColumn::make('verified_at')->label('تاريخ التحقق')->dateTime(),
            ])
            ->headerActions([
                Tables\Actions\Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record && !$record->verified_at)
                    ->action(function ($record, KycService $service) {
                        $service->approveKyc($record->user_id, Auth::id());
                        Notification::make()->title('تمت الموافقة على KYC')->success()->send();
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        \Filament\Forms\Components\Textarea::make('reason')
                            ->label('سبب الرفض')
                            ->required(),
                    ])
                    ->visible(fn ($record) => $record && !$record->verified_at)
                    ->action(function ($record, array $data, KycService $service) {
                        $service->rejectKyc($record->user_id, Auth::id(), $data['reason']);
                        Notification::make()->title('تم رفض KYC')->success()->send();
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ]);
    }
}