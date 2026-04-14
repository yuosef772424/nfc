<?php

namespace App\Filament\Core\Filters;

use Filament\Tables\Filters\SelectFilter as BaseSelectFilter;
use App\Filament\Core\ConfigConstants;

class SelectFilter extends BaseSelectFilter
{
    protected ?string $configGroup = null;
    protected ?string $configPrefix = null;
    protected ?array $customOptions = null;
    protected ?string $customLabel = null;

    public static function make(?string $name = null): static
    {
        return parent::make($name);
    }

    public function setOptions(array $options): static
    {
        $this->customOptions = $options;
        return $this;
    }

    public function setLabel(string $label): static
    {
        $this->customLabel = $label;
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
            $this->customOptions = ConfigConstants::options($this->configGroup, $this->configPrefix);
        }

        if ($this->customOptions !== null) {
            parent::options($this->customOptions);
        }

        if ($this->customLabel !== null) {
            parent::label($this->customLabel);
        }

        parent::setUp();
    }
}