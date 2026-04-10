<?php

namespace App\Contracts\Repositories;

use App\Models\AgentProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface AgentProfileRepositoryInterface
{
    // Retrieval
    public function getByUserId(int $userId, array $with = []): ?AgentProfile;
    public function getAll(int $perPage = 20, array $with = []): LengthAwarePaginator;
    public function getActive(array $with = []): Collection;

    public function calculateCommission(int $userId, float $amount): float;

    // Write
    public function create(int $userId, array $data): AgentProfile;
    public function update(int $userId, array $data): bool;
    public function updateCommission(int $userId, string $type, float $value): bool;
    public function setActive(int $userId, bool $isActive): bool;
    public function delete(int $userId): bool;

    // Checks
    public function exists(int $userId): bool;
    public function isActive(int $userId): bool;
}