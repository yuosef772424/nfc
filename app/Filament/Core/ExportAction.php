<?php

namespace App\Filament\Core;

use Filament\Tables\Actions\Action;
use Filament\Notifications\Notification;
use Closure;

class ExportAction extends Action
{
    protected ?Closure $exportQuery = null;

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'export');
    }

    public function exportQuery(Closure $query): static
    {
        $this->exportQuery = $query;
        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();
        $this->label('تصدير')
             ->icon('heroicon-o-arrow-down-tray')
             ->color('gray')
             ->action(function () {
                 Notification::make()->title('التصدير غير مفعل')->warning()->send();
             });
    }
}