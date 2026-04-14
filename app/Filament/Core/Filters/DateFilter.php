<?php

namespace App\Filament\Core\Filters;

use Filament\Tables\Filters\Filter;
use Filament\Forms;
use Illuminate\Database\Eloquent\Builder;

class DateFilter extends Filter
{
    protected string $column;

    public static function make(?string $name = null, string $column = 'created_at'): static
    {
        $filter = parent::make($name ?? 'date_filter');
        $filter->column = $column;
        return $filter;
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->form([
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\DatePicker::make('from')->label('من تاريخ'),
                    Forms\Components\DatePicker::make('to')->label('إلى تاريخ'),
                ]),
        ])
        ->query(function (Builder $query, array $data): Builder {
            return $query
                ->when($data['from'], fn ($q, $date) => $q->whereDate($this->column, '>=', $date))
                ->when($data['to'], fn ($q, $date) => $q->whereDate($this->column, '<=', $date));
        });
    }
}