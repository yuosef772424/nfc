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

    // ------------------- Helpers -------------------
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

    // ------------------- Basic Queries -------------------
    public function findById(int $id, array $with = []): ?User
    {
        return User::with($with)->find($id);
    }

    public function getByPhone(string $phone, array $with = []): ?User
    {
        return User::with($with)->where('phone', $phone)->first();
    }

    public function getByEmail(string $email, array $with = []): ?User
    {
        return User::with($with)->where('email', $email)->first();
    }

    public function getByUuid(string $uuid, array $with = []): ?User
    {
        return User::with($with)->where('uuid', $uuid)->first();
    }

    /**
     * @deprecated Use findById($id, $relations) instead
     */
    public function findWithRelations(int $id, array $relations): ?User
    {
        return User::with($relations)->find($id);
    }

    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = User::with($with);

        if (!empty($filters['user_type'])) {
            $query->where('user_type', $filters['user_type']);
        }
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        return $query->paginate($perPage);
    }

    public function getByType(string $userType, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return User::with($with)->where('user_type', $userType)->paginate($perPage);
    }

    public function getActiveUsers(int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $activeStatus = $this->getStatusConstant('active') ?? 'active';
        return User::with($with)->where('status', $activeStatus)->paginate($perPage);
    }

    // ------------------- Collections -------------------
    public function getVerified(array $with = []): Collection
    {
        return User::with($with)->where('is_verified', true)->get();
    }

    public function getAgents(array $with = []): Collection
    {
        $agentType = $this->getUserTypeConstant('agent') ?? 'agent';
        return User::with($with)->where('user_type', $agentType)->get();
    }

    public function getMerchants(array $with = []): Collection
    {
        $merchantType = $this->getUserTypeConstant('merchant') ?? 'merchant';
        return User::with($with)->where('user_type', $merchantType)->get();
    }

    // ------------------- Checks -------------------
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

    // ------------------- Write Operations -------------------
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

    // ------------------- Existence Checks -------------------
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