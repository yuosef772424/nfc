<?php

namespace App\Contracts\Repositories;

interface CacheRepositoryInterface
{
    public function get(string $key, mixed $default = null): mixed;
    public function put(string $key, mixed $value, int $ttlSeconds): bool;
    public function increment(string $key, int $amount = 1): int;
    public function decrement(string $key, int $amount = 1): int;
    public function forget(string $key): bool;
    public function has(string $key): bool;
    public function expire(string $key, int $ttlSeconds): bool;
}