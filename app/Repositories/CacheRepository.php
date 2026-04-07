<?php

namespace App\Repositories;

use App\Contracts\Repositories\CacheRepositoryInterface;
use Illuminate\Support\Facades\Cache;

class CacheRepository implements CacheRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        return Cache::get($key, $default);
    }

    public function put(string $key, mixed $value, int $ttlSeconds): bool
    {
        return Cache::put($key, $value, $ttlSeconds);
    }

    public function increment(string $key, int $amount = 1): int
    {
        return Cache::increment($key, $amount);
    }

    public function decrement(string $key, int $amount = 1): int
    {
        return Cache::decrement($key, $amount);
    }

    public function forget(string $key): bool
    {
        return Cache::forget($key);
    }

    public function has(string $key): bool
    {
        return Cache::has($key);
    }


    public function expire(string $key, int $ttlSeconds): bool
{
    if (!Cache::has($key)) {
        return false;
    }

    $value = Cache::get($key);
    return Cache::put($key, $value, $ttlSeconds);
}