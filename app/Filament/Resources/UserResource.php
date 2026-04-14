<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Notifications\Notification;
use App\Services\UserManagement\UserProfileService;
use App\Services\UserManagement\KycService;
use Illuminate\Support\Facades\Auth;
use App\Filament\Core\ConfigConstants;
use App\Filament\Core\Columns\MoneyColumn;

class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $navigationIcon = 'heroicon-o-users';
    protected static ?string $navigationGroup = 'إدارة المستخدمين';
    protected static ?string $label = 'مستخدم';
    protected static ?string $pluralLabel = 'المستخدمين';
    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('المعلومات الأساسية')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label('الاسم الكامل')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('phone')
                            ->label('رقم الهاتف')
                            ->tel()
                            ->required()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('email')
                            ->label('البريد الإلكتروني')
                            ->email()
                            ->unique(ignoreRecord: true),
                        Forms\Components\Select::make('user_type')
                            ->label('نوع المستخدم')
                            ->options([
                                'customer' => 'عميل',
                                'agent'    => 'وكيل',
                                'merchant' => 'تاجر',
                            ])
                            ->required()
                            ->reactive(),
                        Forms\Components\Select::make('status')
                            ->label('حالة الحساب')
                            ->options([
                                'active'    => 'نشط',
                                'inactive'  => 'غير نشط',
                                'suspended' => 'موقوف',
                                'deleted'   => 'محذوف',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_verified')
                            ->label('مفعل')
                            ->default(false),
                    ])->columns(2),

                Forms\Components\Section::make('معلومات الوكيل')
                    ->schema([
                        Forms\Components\Select::make('commission_type')
                            ->label('نوع العمولة')
                            ->options([
                                'percentage' => 'نسبة مئوية',
                                'fixed'      => 'مبلغ ثابت',
                            ])
                            ->default('percentage'),
                        Forms\Components\TextInput::make('commission_value')
                            ->label('قيمة العمولة')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\Toggle::make('agent_active')
                            ->label('الوكيل نشط')
                            ->default(true),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('user_type') === 'agent')
                    ->columns(2),

                Forms\Components\Section::make('معلومات التاجر')
                    ->schema([
                        Forms\Components\TextInput::make('business_name')
                            ->label('اسم النشاط التجاري')
                            ->maxLength(255),
                        Forms\Components\Select::make('business_type')
                            ->label('نوع النشاط')
                            ->options([
                                'retail'     => 'تجزئة',
                                'wholesale'  => 'جملة',
                                'service'    => 'خدمات',
                                'restaurant' => 'مطعم',
                                'other'      => 'أخرى',
                            ]),
                        Forms\Components\Toggle::make('merchant_active')
                            ->label('التاجر نشط')
                            ->default(true),
                    ])
                    ->visible(fn (Forms\Get $get) => $get('user_type') === 'merchant')
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('#')
                    ->sortable(),
                Tables\Columns\TextColumn::make('name')
                    ->label('الاسم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('phone')
                    ->label('رقم الهاتف')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('email')
                    ->label('البريد الإلكتروني')
                    ->searchable()
                    ->copyable(),
                Tables\Columns\TextColumn::make('user_type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'customer' => 'عميل',
                        'agent'    => 'وكيل',
                        'merchant' => 'تاجر',
                        default    => $state,
                    })
                    ->colors([
                        'success' => 'customer',
                        'primary' => 'agent',
                        'warning' => 'merchant',
                    ]),
                Tables\Columns\TextColumn::make('status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'active'    => 'نشط',
                        'inactive'  => 'غير نشط',
                        'suspended' => 'موقوف',
                        'deleted'   => 'محذوف',
                        default     => $state,
                    })
                    ->colors([
                        'success' => 'active',
                        'gray'    => 'inactive',
                        'danger'  => 'suspended',
                        'danger'  => 'deleted',
                    ]),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label('مفعل')
                    ->boolean(),
                // استخدام MoneyColumn من Core
                MoneyColumn::make('wallet.available_balance')
                    ->label('الرصيد'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ التسجيل')
                    ->dateTime('Y-m-d')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('user_type')
                    ->label('نوع المستخدم')
                    ->options([
                        'customer' => 'عميل',
                        'agent'    => 'وكيل',
                        'merchant' => 'تاجر',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'active'    => 'نشط',
                        'inactive'  => 'غير نشط',
                        'suspended' => 'موقوف',
                    ]),
                Tables\Filters\Filter::make('is_verified')
                    ->label('مفعل')
                    ->query(fn ($query) => $query->where('is_verified', true)),
                Tables\Filters\Filter::make('has_wallet')
                    ->label('لديه محفظة')
                    ->query(fn ($query) => $query->whereHas('wallet')),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\Action::make('toggle_status')
                    ->label(fn (User $record) => $record->status === 'active' ? 'تعليق' : 'تفعيل')
                    ->icon(fn (User $record) => $record->status === 'active' ? 'heroicon-o-lock-closed' : 'heroicon-o-check-circle')
                    ->color(fn (User $record) => $record->status === 'active' ? 'warning' : 'success')
                    ->requiresConfirmation()
                    ->action(function (User $record, UserProfileService $service) {
                        $newState = $record->status === 'active' ? 'inactive' : 'active';
                        if ($newState === 'active') {
                            $service->reactivateAccount($record->id);
                        } else {
                            $service->deactivateAccount($record->id, '');
                        }
                        Notification::make()
                            ->title($newState === 'active' ? 'تم تفعيل الحساب' : 'تم تعليق الحساب')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('approve_kyc')
                    ->label('موافقة KYC')
                    ->icon('heroicon-o-shield-check')
                    ->color('primary')
                    ->requiresConfirmation()
                    ->visible(fn (User $record) => $record->kyc && !$record->kyc->verified_at)
                    ->action(function (User $record, KycService $service) {
                        $service->approveKyc($record->id, Auth::id());
                        Notification::make()
                            ->title('تمت الموافقة على KYC')
                            ->success()
                            ->send();
                    }),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\WalletRelationManager::class,
            RelationManagers\KycRelationManager::class,
            RelationManagers\CardsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'edit'   => Pages\EditUser::route('/{record}/edit'),
        ];
    }
}