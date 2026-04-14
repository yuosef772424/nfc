<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TransactionResource\Pages;
use App\Filament\Resources\TransactionResource\RelationManagers;
use App\Models\WalletTransaction;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use App\Filament\Core\Columns\BadgeColumn;

use App\Filament\Core\Columns\MoneyColumn;
// use App\Filament\Core\DateFilter;
use App\Filament\Core\Filters\DateFilter ;
use App\Filament\Core\Filters\SelectFilter;
use App\Filament\Core\ModalAction;
use App\Services\FinancialSystem\TransactionService;
use App\Filament\Core\ConfigConstants;

class TransactionResource extends Resource
{
    protected static ?string $model = WalletTransaction::class;
    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'المعاملات المالية';
    protected static ?string $label = 'معاملة';
    protected static ?string $pluralLabel = 'المعاملات';
    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات المعاملة')
                    ->schema([
                        Forms\Components\TextInput::make('transaction_uuid')
                            ->label('الرمز الفريد')
                            ->disabled()
                            ->visibleOn('edit'),
                        Forms\Components\Select::make('type')
                            ->label('نوع المعاملة')
                            ->options(ConfigConstants::options('constant', 'transaction_type'))
                            ->required()
                            ->disabled(),
                        MoneyColumn::make('amount')->label('المبلغ')->disabled(),
                        MoneyColumn::make('fee')->label('الرسوم')->disabled(),
                        MoneyColumn::make('net_amount')->label('الصافي')->disabled(),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options(ConfigConstants::options('constant', 'transaction_status'))
                            ->required(),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->disabled()
                            ->columnSpanFull(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
               Tables\Columns\TextColumn::make('id')->label('#')->sortable(),
                Tables\Columns\TextColumn::make('transaction_uuid')
                    ->label('الرمز')
                    ->copyable()
                    ->toggleable(),
                BadgeColumn::make('type')
                    ->label('النوع')
                    ->configGroup('constant', 'transaction_type'),
                MoneyColumn::make('amount'),
                Tables\Columns\TextColumn::make('senderWallet.user.name')
                    ->label('المرسل')
                    ->default('-'),
                Tables\Columns\TextColumn::make('receiverWallet.user.name')
                    ->label('المستقبل')
                    ->default('-'),
                BadgeColumn::make('status')
                    ->label('الحالة')
                    ->configGroup('constant', 'transaction_status'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime(),
            ])
            ->filters([
                SelectFilter::make('type')
                    ->label('النوع')
                    ->configGroup('constant', 'transaction_type'),
                SelectFilter::make('status')
                    ->label('الحالة')
                    ->configGroup('constant', 'transaction_status'),
                DateFilter::make('date_range', 'created_at'),
                Tables\Filters\Filter::make('min_amount')
                    ->form([Forms\Components\TextInput::make('amount')->numeric()])
                    ->query(fn ($q, $data) => $q->when($data['amount'], fn ($q, $v) => $q->where('amount', '>=', $v))),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
                ModalAction::make('refund')
                    ->setLabel('استرداد')
                    ->setIcon('heroicon-o-arrow-uturn-left')
                    ->setColor('warning')
                    ->confirmation(true)
                    ->heading('تأكيد الاسترداد')
                    ->description('سيتم إنشاء معاملة عكسية وإعادة المبلغ.')
                    ->visible(fn ($record) =>
                        $record->status === ConfigConstants::get('constant', 'transaction_status.completed')
                        && $record->type === ConfigConstants::get('constant', 'transaction_type.payment')
                        && !$record->refunded_at
                    )
                    ->handler(function ($record, TransactionService $service) {
                        try {
                            $service->refundTransaction($record->id, 'استرداد من لوحة التحكم');
                            Notification::make()->title('تم الاسترداد')->success()->send();
                        } catch (\Exception $e) {
                            Notification::make()->title('فشل الاسترداد')->body($e->getMessage())->danger()->send();
                        }
                    }),
            ])
            ->bulkActions([Tables\Actions\DeleteBulkAction::make()])
            ->defaultSort('created_at', 'desc');
    }

    public static function getRelations(): array
    {
        return [RelationManagers\LedgerEntriesRelationManager::class];
    }

    public static function getPages(): array
    {
        return [
            'index'  => Pages\ListTransactions::route('/'),
            'create' => Pages\CreateTransaction::route('/create'),
            'edit'   => Pages\EditTransaction::route('/{record}/edit'),
            // 'view'   => Pages\ViewTransaction::route('/{record}'),
        ];
    }
}