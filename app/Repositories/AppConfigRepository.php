<?php

namespace App\Repositories;

use App\Models\AppConfig;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AppConfigRepository implements AppConfigRepositoryInterface
{
    public const CACHE_KEY = 'app_config_all';

    /**
     * Get a single configuration value with optional meta filtering.
     */
    public function getValue(string $group, string $key, array $metaFilters = []): mixed
    {
        $all = $this->getAllGrouped();
        $items = $all[$group] ?? [];

        foreach ($items as $item) {
            if ($item['key'] !== $key) {
                continue;
            }
            if ($this->matchesMetaFilters($item['meta'], $metaFilters)) {
                return $item['casted_value'];
            }
        }

        return null;
    }

    /**
     * Get entire group with optional meta filtering.
     */
    public function getGroup(string $group, array $metaFilters = []): Collection
    {
        return collect($this->getAllGrouped()[$group] ?? [])
            ->filter(fn($item) => $this->matchesMetaFilters($item['meta'], $metaFilters))
            ->values();
    }

    /**
     * Get all configurations grouped (cached forever until cleared).
     */
    public function getAllGrouped(): array
    {
        return Cache::rememberForever(self::CACHE_KEY, function () {
            return AppConfig::where('is_active', true)
                ->orderBy('sort_order')
                ->get(['group', 'key', 'value', 'data_type', 'label', 'sort_order', 'meta'])
                ->groupBy('group')
                ->transform(fn($group) => $group->map(fn($config) => [
                    'key'          => $config->key,
                    'value'        => $config->value,
                    'casted_value' => $config->casted_value,
                    'data_type'    => $config->data_type,
                    'label'        => $config->label,
                    'sort_order'   => $config->sort_order,
                    'meta'         => $config->meta,
                ]));
        });
    }

    /**
     * Create or update a configuration and clear cache.
     */
    public function set(string $group, string $key, mixed $value, array $meta = []): AppConfig
    {
        $dataType = $this->detectDataType($value);
        $stringValue = $this->encodeValue($value, $dataType);

        $config = AppConfig::updateOrCreate(
            ['group' => $group, 'key' => $key],
            [
                'value'     => $stringValue,
                'data_type' => $dataType,
                'meta'      => $meta,
                'is_active' => true,
            ]
        );

        $this->clearCache();
        return $config;
    }

    /**
     * Clear all cached configurations.
     */
    public function clearCache(): void
    {
        Cache::forget(self::CACHE_KEY);
    }

    // ------------------- Private Helpers -------------------

    private function detectDataType(mixed $value): string
    {
        return match (true) {
            is_bool($value)        => 'boolean',
            is_array($value)       => 'json',
            is_int($value) || is_float($value) => 'number',
            default                => 'string',
        };
    }

    private function encodeValue(mixed $value, string $dataType): string
    {
        return match ($dataType) {
            'json'    => json_encode($value),
            'boolean' => $value ? 'true' : 'false',
            default   => (string) $value,
        };
    }

    private function matchesMetaFilters(array $itemMeta, array $metaFilters): bool
    {
        if (empty($metaFilters)) {
            return true;
        }

        foreach ($metaFilters as $key => $value) {
            if (!isset($itemMeta[$key]) || $itemMeta[$key] != $value) {
                return false;
            }
        }
        return true;
    }
}