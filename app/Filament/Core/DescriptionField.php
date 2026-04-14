<?php

namespace App\Filament\Core;

use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use App\Filament\Core\ConfigConstants;


class DescriptionField extends Textarea
{
    public static function make(string $name = 'description'): static
    {
        return parent::make($name)
            ->rows(2)
            ->maxLength(255);
    }
}