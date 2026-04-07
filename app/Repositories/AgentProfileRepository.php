<?php

namespace App\Repositories;

use App\Models\AgentProfile;
use App\Contracts\Repositories\AgentProfileRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AgentProfileRepository implements AgentProfileRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- دوال مساعدة لقراءة الثوابت من app_config -------------------
    protected function getCommissionTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "commission_type.{$typeKey}");
    }

    // ------------------- Retrieval -------------------
    public function findByUserId(int $userId): ?AgentProfile
    {
        return AgentProfile::find($userId);
    }

    public function getAll(int $perPage = 20): LengthAwarePaginator
    {
        return AgentProfile::paginate($perPage);
    }

    public function getActive(): Collection
    {
        return AgentProfile::where('is_active', true)->get();
    }

    public function calculateCommission(int $userId, float $amount): float
    {
        $profile = $this->findByUserId($userId);
        if (!$profile) return 0.0;

        $percentageType = $this->getCommissionTypeConstant('percentage') ?? 'percentage';
        if ($profile->commission_type === $percentageType) {
            return round($amount * ($profile->commission_value / 100), 2);
        }
        return (float) $profile->commission_value;
    }

    // ------------------- Write -------------------
    public function create(int $userId, array $data): AgentProfile
    {
        $data['user_id'] = $userId;
        return AgentProfile::create($data);
    }

    public function update(int $userId, array $data): bool
    {
        $profile = $this->findByUserId($userId);
        if (!$profile) return false;
        return $profile->update($data);
    }

    public function updateCommission(int $userId, string $type, float $value): bool
    {
        return $this->update($userId, [
            'commission_type'  => $type,
            'commission_value' => $value,
        ]);
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
        return AgentProfile::where('user_id', $userId)->exists();
    }

    public function isActive(int $userId): bool
    {
        $profile = $this->findByUserId($userId);
        return $profile && $profile->is_active;
    }
}