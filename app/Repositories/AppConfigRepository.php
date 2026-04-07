<?php

namespace App\Repositories;

use App\Models\AppConfig;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

class AppConfigRepository implements AppConfigRepositoryInterface
{
    protected string $cacheKey = 'app_config_all';

    // جلب قيمة محددة
    public function getValue(string $group, string $key, array $metaFilters = []): mixed
    {
        $all = $this->getAllGrouped();
        $items = $all[$group] ?? [];

        foreach ($items as $item) {
            if ($item['key'] !== $key) continue;

            // التحقق من توافق meta filters
            $metaMatch = true;
            foreach ($metaFilters as $k => $v) {
                if (($item['meta'][$k] ?? null) != $v) {
                    $metaMatch = false;
                    break;
                }
            }
            if ($metaMatch) {
                return $item['casted_value'];
            }
        }
        return null;
    }

    // جلب مجموعة كاملة مع فلترة meta
    public function getGroup(string $group, array $metaFilters = []): Collection
    {
        $all = $this->getAllGrouped();
        $items = $all[$group] ?? [];

        if (empty($metaFilters)) {
            return collect($items);
        }

        return collect($items)->filter(function ($item) use ($metaFilters) {
            foreach ($metaFilters as $k => $v) {
                if (($item['meta'][$k] ?? null) != $v) return false;
            }
            return true;
        })->values();
    }

    // جلب كل الإعدادات مع التجميع (يُخزن في cache)
    public function getAllGrouped(): array
    {
        return Cache::rememberForever($this->cacheKey, function () {
            $configs = AppConfig::where('is_active', true)
                ->orderBy('sort_order')
                ->get();

            $grouped = [];
            foreach ($configs as $config) {
                $grouped[$config->group][] = [
                    'key'          => $config->key,
                    'value'        => $config->value,
                    'casted_value' => $config->casted_value,
                    'data_type'    => $config->data_type,
                    'label'        => $config->label,
                    'sort_order'   => $config->sort_order,
                    'meta'         => $config->meta,
                ];
            }
            return $grouped;
        });
    }

    // إنشاء أو تحديث إعداد
    public function set(string $group, string $key, mixed $value, array $meta = []): AppConfig
    {
        $dataType = $this->detectDataType($value);
        $stringValue = match($dataType) {
            'json'    => json_encode($value),
            'boolean' => $value ? 'true' : 'false',
            default   => (string) $value,
        };

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

    // مسح الكاش
    public function clearCache(): void
    {
        Cache::forget($this->cacheKey);
    }

    // كشف نوع البيانات تلقائياً
    private function detectDataType(mixed $value): string
{
    if (is_bool($value))    return 'boolean';
    if (is_array($value))   return 'json';
    if (is_int($value) || is_float($value)) return 'number';

    // string رقمية تبقى string — فقط الأرقام الحرفية الصريحة تُصنَّف number
    return 'string';
}


}