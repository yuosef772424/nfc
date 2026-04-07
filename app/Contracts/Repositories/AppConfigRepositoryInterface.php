<?php

namespace App\Contracts\Repositories;

use App\Models\AppConfig;
use Illuminate\Support\Collection;

interface AppConfigRepositoryInterface
{
    // جلب قيمة محددة (مع فلترة meta اختيارية)
    public function getValue(string $group, string $key, array $metaFilters = []): mixed;

    // جلب كل عناصر مجموعة معينة (مع فلترة meta)
    public function getGroup(string $group, array $metaFilters = []): Collection;

    // جلب كل الإعدادات مجمعة حسب المجموعة (للاستخدام في cache)
    public function getAllGrouped(): array;

    // إنشاء أو تحديث إعداد
    public function set(string $group, string $key, mixed $value, array $meta = []): AppConfig;

    // مسح الكاش
    public function clearCache(): void;
}