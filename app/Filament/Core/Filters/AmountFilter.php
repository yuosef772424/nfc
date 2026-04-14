<?php

namespace App\Filament\Core;

use Filament\Tables\Filters\Filter;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

class AmountFilter extends Filter
{
    protected string $column;

    public static function make(?string $name = null, string $column = 'amount'): static
    {
        $filter = parent::make($name ?? 'amount_filter');
        $filter->column = $column;
        return $filter;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->form([
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\TextInput::make('min')->label('الحد الأدنى')->numeric(),
                    Forms\Components\TextInput::make('max')->label('الحد الأقصى')->numeric(),
                ]),
        ])
        ->query(function (Builder $query, array $data): Builder {
            return $query
                ->when($data['min'], fn ($q, $min) => $q->where($this->column, '>=', $min))
                ->when($data['max'], fn ($q, $max) => $q->where($this->column, '<=', $max));
        });
    }
}