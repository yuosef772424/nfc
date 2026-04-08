<?php

namespace App\Traits;

use App\Contracts\Repositories\CacheRepositoryInterface;
use Illuminate\Support\Facades\Cache;

trait CacheableTrait
{
    /**
     * تخزين قيمة في الكاش مع صلاحية (بالثواني).
     */
    protected function cachePut(string $key, $value, int $ttlSeconds = 3600): void
    {
        $this->getCacheRepo()->put($key, $value, $ttlSeconds);
    }

    /**
     * جلب قيمة من الكاش، أو تنفيذ Closure وتخزينها إذا لم تكن موجودة.
     */
    protected function cacheRemember(string $key, int $ttlSeconds, callable $callback)
    {
        if ($this->getCacheRepo()->has($key)) {
            return $this->getCacheRepo()->get($key);
        }
        $value = $callback();
        $this->cachePut($key, $value, $ttlSeconds);
        return $value;
    }

    /**
     * حذف مفتاح من الكاش.
     */
    protected function cacheForget(string $key): void
    {
        $this->getCacheRepo()->forget($key);
    }

    /**
     * يجب أن توفر الخدمة التي تستخدم الـ Trait هذه الدالة.
     * @return CacheRepositoryInterface
     */
    abstract protected function getCacheRepo(): CacheRepositoryInterface;
}