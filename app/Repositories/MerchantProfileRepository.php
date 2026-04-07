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
    public function findByUserId(int $userId): ?MerchantProfile
    {
        return MerchantProfile::find($userId);
    }

    public function getAll(int $perPage = 20): LengthAwarePaginator
    {
        return MerchantProfile::paginate($perPage);
    }

    public function getActive(): Collection
    {
        return MerchantProfile::where('is_active', true)->get();
    }

    public function getByBusinessType(string $businessType, int $perPage = 20): LengthAwarePaginator
    {
        return MerchantProfile::where('business_type', $businessType)->paginate($perPage);
    }

    // ------------------- Write -------------------
    public function create(int $userId, array $data): MerchantProfile
    {
        $data['user_id'] = $userId;
        return MerchantProfile::create($data);
    }

    public function update(int $userId, array $data): bool
    {
        $profile = $this->findByUserId($userId);
        if (!$profile) return false;
        return $profile->update($data);
    }

    public function setActive(int $userId, bool $isActive): bool
    {
        return $this->update($userId, ['is_active' => $isActive]);
    }

    public function delete(int $userId): bool
    {
        $profile = $this->findByUserId($userId);
        if (!$profile) return false;
        return (bool) $profile->delete();
    }

    // ------------------- Checks -------------------
    public function exists(int $userId): bool
    {
        return MerchantProfile::where('user_id', $userId)->exists();
    }

    public function isActive(int $userId): bool
    {
        $profile = $this->findByUserId($userId);
        return $profile && $profile->is_active;
    }
}

// INSERT INTO app_config (group, key, value, label, meta) VALUES
// ('constant', 'ledger_entry_type.debit', 'debit', 'مدين (خصم)', '{"category":"ledger_entry_type"}'),
// ('constant', 'ledger_entry_type.credit', 'credit', 'دائن (إضافة)', '{"category":"ledger_entry_type"}');