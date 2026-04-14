<?php

namespace App\Filament\Core\Columns;

use Filament\Tables\Columns\TextColumn;
use App\Filament\Core\ConfigConstants;

class BadgeColumn extends TextColumn
{
    protected array $colorMap = [];
    protected array $labelMap = [];
    protected ?string $configGroup = null;
    protected ?string $configPrefix = null;

    public function colorMap(array $map): static
    {
        $this->colorMap = $map;
        return $this;
    }

    public function labelMap(array $map): static
    {
        $this->labelMap = $map;
        return $this;
    }

    public function configGroup(string $group, ?string $prefix = null): static
    {
        $this->configGroup = $group;
        $this->configPrefix = $prefix;
        return $this;
    }

    public static function make(string $name = 'status'): static
    {
        return parent::make($name)->label('الحالة')->badge();
    }

protected function setUp(): void
{
    if ($this->configGroup) {
        $this->labelMap = ConfigConstants::options($this->configGroup, $this->configPrefix);
        // لا نحتاج لتعيين colorMap من ConfigConstants، سنولدها تلقائياً
    }

    // توليد colorMap إذا لم يتم تعيينه يدوياً
    if (empty($this->colorMap)) {
        $this->colorMap = $this->generateDefaultColorMap(array_keys($this->labelMap));
    }

    // ✅ الضمان النهائي: استخدم Closure بسيط وآمن
    $this->colors(fn ($state) => $this->colorMap[$state] ?? 'gray');
    $this->formatStateUsing(fn ($state) => $this->labelMap[$state] ?? $state);

    parent::setUp();
}
    protected function generateDefaultColorMap(array $states): array
    {
        $map = [];
        foreach ($states as $state) {
            $map[$state] = $this->guessColorForState($state);
        }
        return $map;
    }

    protected function guessColorForState(string $state): string
    {
        $state = strtolower($state);

        return match (true) {
            str_contains($state, 'active')   => 'success',
            str_contains($state, 'completed') => 'success',
            str_contains($state, 'approved')  => 'success',
            str_contains($state, 'paid')      => 'success',
            str_contains($state, 'verified')  => 'success',
            str_contains($state, 'pending')   => 'warning',
            str_contains($state, 'processing') => 'warning',
            str_contains($state, 'waiting')   => 'warning',
            str_contains($state, 'inactive')  => 'gray',
            str_contains($state, 'disabled')  => 'gray',
            str_contains($state, 'closed')    => 'gray',
            str_contains($state, 'expired')   => 'gray',
            str_contains($state, 'suspended') => 'danger',
            str_contains($state, 'blocked')   => 'danger',
            str_contains($state, 'failed')    => 'danger',
            str_contains($state, 'cancelled') => 'danger',
            str_contains($state, 'rejected')  => 'danger',
            str_contains($state, 'deleted')   => 'danger',
            str_contains($state, 'customer')  => 'success',
            str_contains($state, 'agent')     => 'primary',
            str_contains($state, 'merchant')  => 'warning',
            default => 'gray',
        };
    }
}