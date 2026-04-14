<?php

namespace App\Filament\Core\Columns;

use Filament\Tables\Columns\TextColumn;

class DateTimeColumn extends TextColumn
{
    public static function make(string $name = 'created_at'): static
    {
        return parent::make($name)
            ->label('التاريخ')
            ->dateTime('Y-m-d H:i')
            ->sortable();
    }
}