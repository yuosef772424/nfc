<?php

namespace App\Filament\Core;

use Filament\Tables\Actions\Action;
use Filament\Forms;
use Closure;
use Illuminate\Contracts\Support\Htmlable;

class ModalAction extends Action
{
    protected array|Closure $formSchema = [];
    protected ?Closure $actionHandler = null;
    protected bool|Closure $confirmation = false;

    protected mixed $customModalHeading = null;
    protected mixed $customModalDescription = null;
    protected mixed $customIcon = null;
    protected mixed $customColor = null;
    protected mixed $customLabel = null;

    public static function make(?string $name = null): static
    {
        return parent::make($name ?? 'modal_action');
    }

    public function heading(string|Htmlable|Closure|null $heading): static
    {
        $this->customModalHeading = $heading;
        return $this;
    }

    public function description(string|Htmlable|Closure|null $description): static
    {
        $this->customModalDescription = $description;
        return $this;
    }

    public function formSchema(array|Closure $schema): static
    {
        $this->formSchema = $schema;
        return $this;
    }

    public function handler(Closure $handler): static
    {
        $this->actionHandler = $handler;
        return $this;
    }

    public function setIcon(string|Htmlable|Closure|null $icon): static
    {
        $this->customIcon = $icon;
        return $this;
    }

    public function setColor(string|array|Closure|null $color): static
    {
        $this->customColor = $color;
        return $this;
    }

    public function setLabel(string|Htmlable|Closure|null $label): static
    {
        $this->customLabel = $label;
        return $this;
    }

    public function confirmation(bool|Closure $confirmation = true): static
    {
        $this->confirmation = $confirmation;
        return $this;
    }

    protected function setUp(): void
    {
        parent::setUp();

        if ($this->customLabel !== null) parent::label($this->customLabel);
        if ($this->customIcon !== null) parent::icon($this->customIcon);
        if ($this->customColor !== null) parent::color($this->customColor);
        if ($this->customModalHeading !== null) parent::modalHeading($this->customModalHeading);
        if ($this->customModalDescription !== null) parent::modalDescription($this->customModalDescription);

        if ($this->confirmation) $this->requiresConfirmation();
        if (!empty($this->formSchema)) $this->form($this->formSchema);
        if (isset($this->actionHandler)) $this->action($this->actionHandler);
    }
}