<?php

namespace App\Filament\Pages\Reports;

use Filament\Pages\Page;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Section;
use Filament\Notifications\Notification;
use Filament\Actions;
use Illuminate\Support\Collection;

class QueryBuilder extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-magnifying-glass-circle';
    protected static ?string $navigationGroup = 'النظام والتقارير';
    protected static ?string $title = 'مُنشئ الاستعلامات المتقدم';
    protected static ?string $navigationLabel = 'استعلام مخصص';
    protected static ?int $navigationSort = 3;

    protected static string $view = 'filament.pages.reports.query-builder';

    public ?array $data = [];
    public array $availableTables = [];
    public array $tableColumns = [];
    public bool $showResults = false;
    public ?string $errorMessage = null;

    public Collection $queryResult;
    public int $totalRecords = 0;
    public int $currentPage = 1;
    public int $perPage = 10;

    protected $rawQueryBuilder = null;

    public function mount(): void
    {
        $this->loadAvailableTables();
        $this->queryResult = collect();
        $this->form->fill([
            'conditions' => [],
            'joins' => [],
            'select_columns' => [],
        ]);
    }

    protected function loadAvailableTables(): void
    {
        $this->availableTables = [
            'users' => 'المستخدمين',
            'wallets' => 'المحافظ',
            'wallet_transactions' => 'المعاملات',
            'cards' => 'البطاقات',
            'withdrawals' => 'السحوبات',
            'commission_logs' => 'العمولات',
            'nfc_devices' => 'أجهزة NFC',
            'agent_profiles' => 'ملفات الوكلاء',
            'merchant_profiles' => 'ملفات التجار',
            'user_kyc' => 'التحقق من الهوية',
            'ledger_entries' => 'دفتر الأستاذ',
            'audit_log' => 'سجل التدقيق',
            'notifications' => 'الإشعارات',
            'sessionss' => 'الجلسات',
        ];
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Section::make('إعدادات الاستعلام')
                    ->schema([
                        Select::make('base_table')
                            ->label('الجدول الأساسي')
                            ->options($this->availableTables)
                            ->searchable()
                            ->live()
                            ->required()
                            ->afterStateUpdated(fn ($state) => $this->loadTableSchema($state)),

                        Repeater::make('joins')
                            ->label('الربط مع جداول أخرى (JOIN)')
                            ->schema([
                                Grid::make(4)
                                    ->schema([
                                        Select::make('table')
                                            ->label('الجدول')
                                            ->options($this->availableTables)
                                            ->searchable()
                                            ->live()
                                            ->required()
                                            ->afterStateUpdated(fn ($state) => $this->loadTableSchema($state)),
                                        Select::make('type')
                                            ->label('نوع الربط')
                                            ->options([
                                                'inner' => 'INNER JOIN',
                                                'left'  => 'LEFT JOIN',
                                                'right' => 'RIGHT JOIN',
                                            ])
                                            ->default('inner')
                                            ->required(),
                                        TextInput::make('first')
                                            ->label('العمود الأول')
                                            ->placeholder('مثال: users.id')
                                            ->required(),
                                        TextInput::make('second')
                                            ->label('العمود الثاني')
                                            ->placeholder('مثال: wallets.user_id')
                                            ->required(),
                                    ]),
                            ])
                            ->addActionLabel('إضافة ربط')
                            ->collapsible(),

                        Repeater::make('conditions')
                            ->label('شروط البحث (WHERE)')
                            ->schema([
                                Grid::make(5)
                                    ->schema([
                                        Select::make('table')
                                            ->label('الجدول')
                                            ->options($this->availableTables)
                                            ->live()
                                            ->required()
                                            ->afterStateUpdated(fn (callable $set, $state) => [
                                                $set('column', null),
                                                $this->loadTableSchema($state)
                                            ]),
                                        Select::make('column')
                                            ->label('الحقل')
                                            ->options(fn (callable $get) => $this->getColumnsForTable($get('table')))
                                            ->searchable()
                                            ->required()
                                            ->live(),
                                        Select::make('operator')
                                            ->label('المعامل')
                                            ->options([
                                                '=' => 'يساوي',
                                                '!=' => 'لا يساوي',
                                                '>' => 'أكبر من',
                                                '<' => 'أصغر من',
                                                '>=' => 'أكبر أو يساوي',
                                                '<=' => 'أصغر أو يساوي',
                                                'LIKE' => 'يحتوي على',
                                                'IN' => 'ضمن مجموعة',
                                                'NOT IN' => 'ليس ضمن مجموعة',
                                                'IS NULL' => 'فارغ',
                                                'IS NOT NULL' => 'غير فارغ',
                                            ])
                                            ->required()
                                            ->live(),
                                        TextInput::make('value')
                                            ->label('القيمة')
                                            ->visible(fn (callable $get) => !in_array($get('operator'), ['IS NULL', 'IS NOT NULL']))
                                            ->required(fn (callable $get) => !in_array($get('operator'), ['IS NULL', 'IS NOT NULL'])),
                                        Select::make('boolean')
                                            ->label('الربط المنطقي')
                                            ->options(['AND' => 'و', 'OR' => 'أو'])
                                            ->default('AND'),
                                    ]),
                            ])
                            ->addActionLabel('إضافة شرط')
                            ->collapsible(),

                        Repeater::make('select_columns')
                            ->label('الأعمدة المراد عرضها')
                            ->schema([
                                Grid::make(3)
                                    ->schema([
                                        Select::make('table')
                                            ->label('الجدول')
                                            ->options($this->availableTables)
                                            ->live()
                                            ->required()
                                            ->afterStateUpdated(fn (callable $set, $state) => [
                                                $set('column', null),
                                                $this->loadTableSchema($state)
                                            ]),
                                        Select::make('column')
                                            ->label('الحقل')
                                            ->options(fn (callable $get) => $this->getColumnsForTable($get('table')))
                                            ->searchable()
                                            ->required()
                                            ->live(),
                                        TextInput::make('alias')
                                            ->label('اسم مستعار (اختياري)'),
                                    ]),
                            ])
                            ->addActionLabel('إضافة عمود')
                            ->collapsible()
                            ->minItems(1),
                    ])
                    ->columns(1),
            ])
            ->statePath('data');
    }

    protected function loadTableSchema(string $table): void
    {
        $this->tableColumns[$table] = $this->getTableColumns($table);
    }

    protected function getTableColumns(string $table): array
    {
        try {
            return Schema::getColumnListing($table);
        } catch (\Exception $e) {
            return [];
        }
    }

    protected function getColumnsForTable(?string $table): array
    {
        if (!$table) return [];
        if (!isset($this->tableColumns[$table])) {
            $this->loadTableSchema($table);
        }
        $columns = $this->tableColumns[$table] ?? $this->getTableColumns($table);
        return array_combine($columns, $columns);
    }

    public function buildQuery(): void
    {
        $this->errorMessage = null;
        $this->showResults = false;
        $this->queryResult = collect();
        $this->rawQueryBuilder = null;

        try {
            $baseTable = $this->data['base_table'] ?? null;
            if (!$baseTable) {
                throw new \Exception('الرجاء اختيار جدول أساسي.');
            }

            $query = DB::table($baseTable);

            foreach ($this->data['joins'] ?? [] as $join) {
                if (empty($join['table']) || empty($join['first']) || empty($join['second'])) continue;
                $type = $join['type'] ?? 'inner';
                $query->join($join['table'], $join['first'], '=', $join['second'], $type);
            }

            foreach ($this->data['conditions'] ?? [] as $condition) {
                if (empty($condition['table']) || empty($condition['column'])) continue;
                $fullColumn = $condition['table'] . '.' . $condition['column'];
                $operator = $condition['operator'];
                $value = $condition['value'] ?? null;

                if (in_array($operator, ['IS NULL', 'IS NOT NULL'])) {
                    if ($operator === 'IS NULL') {
                        $query->whereNull($fullColumn);
                    } else {
                        $query->whereNotNull($fullColumn);
                    }
                } elseif ($operator === 'IN' || $operator === 'NOT IN') {
                    $values = array_map('trim', explode(',', $value));
                    if ($operator === 'IN') {
                        $query->whereIn($fullColumn, $values);
                    } else {
                        $query->whereNotIn($fullColumn, $values);
                    }
                } else {
                    $query->where($fullColumn, $operator, $value);
                }
            }

            $columns = [];
            foreach ($this->data['select_columns'] ?? [] as $col) {
                if (empty($col['table']) || empty($col['column'])) continue;
                $full = $col['table'] . '.' . $col['column'];
                if (!empty($col['alias'])) {
                    $full .= ' as ' . $col['alias'];
                }
                $columns[] = $full;
            }

            if (empty($columns)) {
                $columns = ['*'];
            }

            $query->select($columns);
            $this->rawQueryBuilder = $query;
            $this->showResults = true;
            $this->currentPage = 1;
            $this->loadPage();

            Notification::make()
                ->title('تم بناء الاستعلام بنجاح')
                ->success()
                ->send();

        } catch (\Exception $e) {
            $this->errorMessage = $e->getMessage();
            Notification::make()
                ->title('خطأ في الاستعلام')
                ->body($e->getMessage())
                ->danger()
                ->send();
        }
    }

    public function loadPage(): void
    {
        if (!$this->rawQueryBuilder) return;

        $query = clone $this->rawQueryBuilder;
        $this->totalRecords = $query->count();
        $results = $query
            ->offset(($this->currentPage - 1) * $this->perPage)
            ->limit($this->perPage)
            ->get();

        $this->queryResult = $results;
    }

    public function goToPage($page): void
    {
        $this->currentPage = max(1, (int)$page);
        $this->loadPage();
    }

    public function exportResults(): void
    {
        Notification::make()
            ->title('التصدير معطل مؤقتاً')
            ->body('سيتم تفعيل ميزة التصدير لاحقاً.')
            ->warning()
            ->send();
    }

    public function getColumnLabels(): array
    {
        if ($this->queryResult->isEmpty()) return [];
        $firstRow = (array) $this->queryResult->first();
        return array_keys($firstRow);
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('execute')
                ->label('تنفيذ الاستعلام')
                ->icon('heroicon-o-play')
                ->color('primary')
                ->action('buildQuery'),

            Actions\Action::make('export')
                ->label('تصدير النتائج')
                ->icon('heroicon-o-arrow-down-tray')
                ->visible(fn () => $this->showResults && $this->totalRecords > 0)
                ->action('exportResults'),
        ];
    }

    protected function getFormActions(): array
    {
        return [];
    }
}