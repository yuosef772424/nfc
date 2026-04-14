<?php

namespace App\Filament\Core;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Filament\Core\ConfigConstants;

class MoneyField extends TextInput
{
    public static function make(string $name = 'amount'): static
    {
        return parent::make($name)
            ->numeric()
            ->minValue(0.01)
            ->prefix(ConfigConstants::get('system', 'default_currency', 'YER'));
    }
}

