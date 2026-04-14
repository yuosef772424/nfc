<?php

namespace App\Filament\Widgets;

use App\Services\System\ReportService;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

class TopAgentsWidget extends BaseWidget
{
    protected static ?int $sort = 3;

    public function table(Table $table): Table
    {
        $reportService = app(ReportService::class);
        $topAgents = $reportService->topAgents(5, 'commission');

        return $table
            ->query(\App\Models\User::whereIn('id', $topAgents->pluck('agent_id')))
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('#'),
                Tables\Columns\TextColumn::make('name')->label('الوكيل')->searchable(),
                Tables\Columns\TextColumn::make('email')->label('البريد'),
                Tables\Columns\TextColumn::make('agentProfile.commission_type')
                    ->label('نوع العمولة')
                    ->formatStateUsing(fn ($state) => $state === 'percentage' ? 'نسبة مئوية' : 'ثابتة'),
                Tables\Columns\TextColumn::make('agentProfile.commission_value')
                    ->label('قيمة العمولة')
                    ->formatStateUsing(fn ($state, $record) => 
                        $record->agentProfile->commission_type === 'percentage' ? $state . '%' : $state . ' YER'
                    ),
                Tables\Columns\TextColumn::make('total_commission')
                    ->label('إجمالي العمولات')
                    ->state(function ($record) use ($topAgents) {
                        $agentData = $topAgents->firstWhere('agent_id', $record->id);
                        return number_format($agentData['total_commission'] ?? 0, 2) . ' YER';
                    }),
                Tables\Columns\TextColumn::make('total_withdrawals')
                    ->label('حجم السحوبات')
                    ->state(function ($record) use ($topAgents) {
                        $agentData = $topAgents->firstWhere('agent_id', $record->id);
                        return number_format($agentData['total_withdrawals'] ?? 0, 2) . ' YER';
                    }),
            ])
            ->paginated(false);
    }
}