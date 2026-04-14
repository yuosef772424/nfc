<?php

namespace App\Filament\Core\Columns;

use Filament\Tables\Columns\IconColumn;

class BooleanColumn extends IconColumn
{
    public static function make(string $name = 'is_active'): static
    {
        return parent::make($name)
            ->label('الحالة')
            ->boolean();
    }
}