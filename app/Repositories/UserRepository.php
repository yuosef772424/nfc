<?php

namespace App\Repositories;

use App\Models\User;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class UserRepository implements UserRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- دوال مساعدة لقراءة الثوابت من app_config -------------------
    public function getUserTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "user_type.{$typeKey}");
    }

    public function getStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "user_status.{$statusKey}");
    }

    public function getAllUserTypes(): array
    {
        $group = $this->configRepo->getGroup('constant', ['category' => 'user_type']);
        return $group->pluck('value', 'key')->toArray();
    }

    // ------------------- دوال الاستعلام الأساسية -------------------
    public function findById(int $id): ?User
    {
        return User::find($id);
    }

    public function findByUuid(string $uuid): ?User
    {
        return User::where('uuid', $uuid)->first();
    }

    public function findByPhone(string $phone): ?User
    {
        return User::where('phone', $phone)->first();
    }

    public function findByEmail(string $email): ?User
    {
        return User::where('email', $email)->first();
    }

    public function findWithRelations(int $id, array $relations): ?User
    {
        return User::with($relations)->find($id);
    }

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = User::query();
        if (!empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->paginate($perPage);
    }

    public function getByType(string $userType, int $perPage = 20): LengthAwarePaginator
    {
        return User::where('user_type', $userType)->paginate($perPage);
    }

    public function getActiveUsers(int $perPage = 20): LengthAwarePaginator
    {
        $activeStatus = $this->getStatusConstant('active') ?? 'active';
        return User::where('status', $activeStatus)->paginate($perPage);
    }

    // ------------------- الدوال المنقولة من الموديل (تعتمد على app_config) -------------------
    public function getVerified(): Collection
    {
        return User::where('is_verified', true)->get();
    }

    public function getAgents(): Collection
    {
        $agentType = $this->getUserTypeConstant('agent') ?? 'agent';
        return User::where('user_type', $agentType)->get();
    }

    public function getMerchants(): Collection
    {
        $merchantType = $this->getUserTypeConstant('merchant') ?? 'merchant';
        return User::where('user_type', $merchantType)->get();
    }

    public function isAgent(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        $agentType = $this->getUserTypeConstant('agent') ?? 'agent';
        return $user->user_type === $agentType;
    }

    public function isMerchant(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        $merchantType = $this->getUserTypeConstant('merchant') ?? 'merchant';
        return $user->user_type === $merchantType;
    }

    public function isVerified(int $id): bool
    {
        $user = $this->findById($id);
        return $user && $user->is_verified === true;
    }

    public function isSuspended(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        $suspendedStatus = $this->getStatusConstant('suspended') ?? 'suspended';
        return $user->status === $suspendedStatus;
    }

    // ------------------- دوال الكتابة -------------------
    public function create(array $data): User
    {
        return User::create($data);
    }

    public function update(int $id, array $data): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        return $user->update($data);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    public function markAsVerified(int $id): bool
    {
        return $this->update($id, ['is_verified' => true]);
    }

    public function delete(int $id): bool
    {
        $user = $this->findById($id);
        if (!$user) return false;
        return (bool) $user->delete();
    }

    // ------------------- دوال التحقق -------------------
    public function existsByPhone(string $phone): bool
    {
        return User::where('phone', $phone)->exists();
    }

    public function existsByEmail(string $email): bool
    {
        return User::where('email', $email)->exists();
    }

    public function countByType(string $userType): int
    {
        return User::where('user_type', $userType)->count();
    }
}