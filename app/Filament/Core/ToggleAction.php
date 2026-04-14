<?php

namespace App\Filament\Core;

use Filament\Notifications\Notification;
use Closure;

class ToggleAction extends ModalAction
{
    protected string $onState = 'active';
    protected string $offState = 'inactive';
    protected string $stateAttribute = 'status';
    protected ?Closure $stateHandler = null;
    protected ?string $configGroup = null;
    protected ?string $configPrefix = null;

    public function states(string $on, string $off): static
    {
        $this->onState = $on;
        $this->offState = $off;
        return $this;
    }

    public function stateAttribute(string $attribute): static
    {
        $this->stateAttribute = $attribute;
        return $this;
    }

    public function stateHandler(Closure $handler): static
    {
        $this->stateHandler = $handler;
        return $this;
    }

    public function configGroup(string $group, ?string $prefix = null): static
    {
        $this->configGroup = $group;
        $this->configPrefix = $prefix;
        return $this;
    }

    protected function setUp(): void
    {
        if ($this->configGroup) {
            $statuses = ConfigConstants::options($this->configGroup, $this->configPrefix);
            $values = array_keys($statuses);
            $this->onState = $values[0] ?? 'active';
            $this->offState = $values[1] ?? 'inactive';
        }

        $this->setLabel(fn ($record) => $record->{$this->stateAttribute} === $this->onState ? 'تعطيل' : 'تفعيل');
        $this->setIcon(fn ($record) => $record->{$this->stateAttribute} === $this->onState ? 'heroicon-o-x-circle' : 'heroicon-o-check-circle');
        $this->setColor(fn ($record) => $record->{$this->stateAttribute} === $this->onState ? 'danger' : 'success');
        $this->confirmation(true);

        $this->handler(function ($record) {
            $newState = $record->{$this->stateAttribute} === $this->onState ? $this->offState : $this->onState;
            if (isset($this->stateHandler)) {
                call_user_func($this->stateHandler, $record, $newState);
            } else {
                $record->update([$this->stateAttribute => $newState]);
            }
            Notification::make()->title('تم التحديث')->success()->send();
        });

        parent::setUp();
    }
}