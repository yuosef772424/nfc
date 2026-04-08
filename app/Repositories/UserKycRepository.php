<?php

namespace App\Repositories;

use App\Models\UserKyc;
use App\Contracts\Repositories\UserKycRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserKycRepository implements UserKycRepositoryInterface
{
    // ------------------- Retrieval -------------------
    public function getByUserId(int $userId, array $with = []): ?UserKyc
    {
        return UserKyc::with($with)->where('user_id', $userId)->first();
    }

    public function getPending(int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return UserKyc::with($with)->whereNull('verified_at')->paginate($perPage);
    }

    public function getVerified(int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return UserKyc::with($with)->whereNotNull('verified_at')->paginate($perPage);
    }

    // ------------------- Write -------------------
    public function createOrUpdate(int $userId, array $data): UserKyc
    {
        $kyc = $this->getByUserId($userId);
        if ($kyc) {
            $kyc->update($data);
            return $kyc;
        }
        $data['user_id'] = $userId;
        return UserKyc::create($data);
    }

    public function markVerified(int $userId): bool
    {
        $kyc = $this->getByUserId($userId);
        if (!$kyc) return false;
        return $kyc->update(['verified_at' => now()]);
    }

    public function update(int $userId, array $data): bool
    {
        $kyc = $this->getByUserId($userId);
        if (!$kyc) return false;
        return $kyc->update($data);
    }

    public function delete(int $userId): bool
    {
        $kyc = $this->getByUserId($userId);
        if (!$kyc) return false;
        return (bool) $kyc->delete();
    }

    // ------------------- Checks -------------------
    public function isVerified(int $userId): bool
    {
        $kyc = $this->getByUserId($userId);
        return $kyc && $kyc->verified_at !== null;
    }

    public function isExpired(int $userId): bool
    {
        $kyc = $this->getByUserId($userId);
        if (!$kyc || !$kyc->id_expiry_date) return false;
        return $kyc->id_expiry_date->isPast();
    }

    public function exists(int $userId): bool
    {
        return UserKyc::where('user_id', $userId)->exists();
    }
}