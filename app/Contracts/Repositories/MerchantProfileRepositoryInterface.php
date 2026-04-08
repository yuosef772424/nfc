<?php

namespace App\Contracts\Repositories;

use App\Models\MerchantProfile;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface MerchantProfileRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function getByUserId(int $userId): ?MerchantProfile;

    public function getAll(int $perPage = 20): LengthAwarePaginator;

    public function getActive(): Collection;

    public function getByBusinessType(string $businessType, int $perPage = 20): LengthAwarePaginator;

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------

    public function create(int $userId, array $data): MerchantProfile;

    public function update(int $userId, array $data): bool;

    public function setActive(int $userId, bool $isActive): bool;

    public function delete(int $userId): bool;

    // ---------------------------------------------------------------
    // Checks
    // ---------------------------------------------------------------

    public function exists(int $userId): bool;

    public function isActive(int $userId): bool;
}