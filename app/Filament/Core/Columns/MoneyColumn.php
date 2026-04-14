<?php

namespace App\Filament\Core\Columns;

use Filament\Tables\Columns\TextColumn;
use App\Filament\Core\ConfigConstants;

class MoneyColumn extends TextColumn
{
    protected string $currency;

    public function currency(string $currency): static
    {
        $this->currency = $currency;
        return $this;
    }

    public static function make(string $name = 'amount'): static
    {
        $instance = parent::make($name)->label('المبلغ')->sortable();
        $instance->currency = ConfigConstants::get('system', 'default_currency', 'YER');
        $instance->formatStateUsing(fn ($state) => number_format($state ?? 0, 2) . ' ' . $instance->currency);
        return $instance;
    }
}