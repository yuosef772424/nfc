<?php

namespace App\Repositories;

use App\Models\MerchantProfile;
use App\Contracts\Repositories\MerchantProfileRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class MerchantProfileRepository implements MerchantProfileRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- Retrieval -------------------
    public function getByUserId(int $userId, array $with = []): ?MerchantProfile
    {
        return MerchantProfile::with($with)->where('user_id', $userId)->first();
    }

    public function getAll(int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return MerchantProfile::with($with)->paginate($perPage);
    }

    public function getActive(array $with = []): Collection
    {
        return MerchantProfile::with($with)->where('is_active', true)->get();
    }

    public function getByBusinessType(string $businessType, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return MerchantProfile::with($with)->where('business_type', $businessType)->paginate($perPage);
    }

    // ------------------- Write -------------------
    public function create(int $userId, array $data): MerchantProfile
    {
        $data['user_id'] = $userId;
        return MerchantProfile::create($data);
    }

    public function update(int $userId, array $data): bool
    {
        $profile = $this->getByUserId($userId);
        if (!$profile) {
            return false;
        }
        return $profile->update($data);
    }

    public function setActive(int $userId, bool $isActive): bool
    {
        return $this->update($userId, ['is_active' => $isActive]);
    }

    public function delete(int $userId): bool
    {
        $profile = $this->getByUserId($userId);
        if (!$profile) {
            return false;
        }
        return (bool) $profile->delete();
    }

    // ------------------- Checks -------------------
    public function exists(int $userId): bool
    {
        return MerchantProfile::where('user_id', $userId)->exists();
    }

    public function isActive(int $userId): bool
    {
        $profile = $this->getByUserId($userId);
        return $profile && $profile->is_active;
    }
}