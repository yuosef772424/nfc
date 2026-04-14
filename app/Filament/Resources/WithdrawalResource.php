<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Filament\Resources\WithdrawalResource\RelationManagers;
use App\Models\Withdrawal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Services\FinancialSystem\WithdrawalService;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Illuminate\Database\Eloquent\Builder;
use Carbon\Carbon;

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';
    protected static ?string $navigationGroup = 'المعاملات المالية';
    protected static ?string $label = 'طلب سحب';
    protected static ?string $pluralLabel = 'عمليات السحب';
    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات السحب')
                    ->schema([
                        Forms\Components\TextInput::make('id')
                            ->label('رقم الطلب')
                            ->disabled(),
                        Forms\Components\Select::make('wallet_id')
                            ->label('المحفظة')
                            ->relationship('wallet', 'id')
                            ->searchable()
                            ->disabled(),
                        Forms\Components\Placeholder::make('user_name')
                            ->label('المستخدم')
                            ->content(fn ($record) => $record?->wallet?->user?->name ?? '-'),
                        Forms\Components\Select::make('agent_id')
                            ->label('الوكيل')
                            ->relationship('agent', 'name')
                            ->searchable()
                            ->disabled(),
                        Forms\Components\TextInput::make('requested_amount')
                            ->label('المبلغ المطلوب')
                            ->numeric()
                            ->prefix('YER')
                            ->disabled(),
                        Forms\Components\TextInput::make('commission_amount')
                            ->label('العمولة')
                            ->numeric()
                            ->prefix('YER')
                            ->disabled(),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('الإجمالي المخصوم')
                            ->numeric()
                            ->prefix('YER')
                            ->disabled(),
                        Forms\Components\TextInput::make('commission_type')
                            ->label('نوع العمولة')
                            ->disabled(),
                        Forms\Components\TextInput::make('commission_value')
                            ->label('قيمة العمولة')
                            ->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending'   => 'معلقة',
                                'completed' => 'مكتملة',
                                'failed'    => 'فاشلة',
                                'cancelled' => 'ملغية',
                            ])
                            ->required(),
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('تنتهي الصلاحية في')
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('completed_at')
                            ->label('تاريخ الإكمال')
                            ->disabled(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('wallet.user.name')
                    ->label('المستخدم')
                    ->searchable(),
                Tables\Columns\TextColumn::make('wallet.user.phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('agent.name')
                    ->label('الوكيل')
                    ->searchable(),
                Tables\Columns\TextColumn::make('requested_amount')
                    ->label('المطلوب')
                    ->money('YER')
                    ->sortable(),
                Tables\Columns\TextColumn::make('commission_amount')
                    ->label('العمولة')
                    ->money('YER')
                    ->sortable()
                    ->toggleable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('الإجمالي')
                    ->money('YER')
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'pending'   => 'معلقة',
                        'completed' => 'مكتملة',
                        'failed'    => 'فاشلة',
                        'cancelled' => 'ملغية',
                        default     => $state,
                    })
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'completed',
                        'danger'  => 'failed',
                        'gray'    => 'cancelled',
                    ]),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('تنتهي')
                    ->dateTime('Y-m-d H:i')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending'   => 'معلقة',
                        'completed' => 'مكتملة',
                        'failed'    => 'فاشلة',
                        'cancelled' => 'ملغية',
                    ]),
                SelectFilter::make('agent_id')
                    ->label('الوكيل')
                    ->relationship('agent', 'name')
                    ->searchable()
                    ->preload(),
                Filter::make('date_range')
                    ->form([
                        Forms\Components\DatePicker::make('date_from')->label('من تاريخ'),
                        Forms\Components\DatePicker::make('date_to')->label('إلى تاريخ'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when($data['date_from'], fn ($q, $date) => $q->whereDate('created_at', '>=', $date))
                            ->when($data['date_to'], fn ($q, $date) => $q->whereDate('created_at', '<=', $date));
                    }),
                Filter::make('expires_soon')
                    ->label('تنتهي صلاحيتها قريباً')
                    ->query(fn (Builder $query) => $query->where('status', 'pending')->where('expires_at', '<=', now()->addHour())),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('complete')
                    ->label('إتمام')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد إتمام السحب')
                    ->modalDescription('سيتم خصم المبلغ من المحفظة وإضافة العمولة للنظام. هل أنت متأكد؟')
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\TextInput::make('verification_code')
                            ->label('رمز التحقق')
                            ->required()
                            ->maxLength(6),
                    ])
                    ->action(function (Withdrawal $record, array $data, WithdrawalService $service) {
                        try {
                            $service->confirmWithdrawal($record->id, $data['verification_code']);
                            Notification::make()->title('تم إتمام السحب بنجاح')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('فشل الإتمام')->body($e->getMessage())->danger()->send();
                        }
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('إلغاء')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->modalHeading('تأكيد إلغاء السحب')
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->action(function (Withdrawal $record, WithdrawalService $service) {
                        $service->cancelWithdrawal($record->id, 'admin_cancelled');
                        Notification::make()->title('تم إلغاء السحب')->success()->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [
            // RelationManagers\CommissionLogsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListWithdrawals::route('/'),
            'create' => Pages\CreateWithdrawal::route('/create'),
            'edit'   => Pages\EditWithdrawal::route('/{record}/edit'),
            // 'view'   => Pages\ViewWithdrawal::route('/{record}'),
        ];
    }
}