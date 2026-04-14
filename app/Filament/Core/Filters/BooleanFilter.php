<?php

namespace App\Filament\Core;

use Filament\Tables\Filters\SelectFilter;

class BooleanFilter extends SelectFilter
{
    public static function make(?string $name = null): static
    {
        return parent::make($name)
            ->options([
                1 => 'نعم',
                0 => 'لا',
            ]);
    }
}