<?php

namespace App\Repositories;

use App\Models\UserKyc;
use App\Contracts\Repositories\UserKycRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class UserKycRepository implements UserKycRepositoryInterface
{
    // جلب KYC لمستخدم بواسطة user_id
    public function findByUserId(int $userId): ?UserKyc
    {
        return UserKyc::find($userId);
    }

    // جلب طلبات KYC المعلقة (غير موثقة)
    public function getPending(int $perPage = 20): LengthAwarePaginator
    {
        return UserKyc::whereNull('verified_at')->paginate($perPage);
    }

    // جلب طلبات KYC الموثقة
    public function getVerified(int $perPage = 20): LengthAwarePaginator
    {
        return UserKyc::whereNotNull('verified_at')->paginate($perPage);
    }

    // إنشاء أو تحديث KYC لمستخدم
    public function createOrUpdate(int $userId, array $data): UserKyc
    {
        $kyc = $this->findByUserId($userId);
        if ($kyc) {
            $kyc->update($data);
            return $kyc;
        }
        $data['user_id'] = $userId;
        return UserKyc::create($data);
    }

    // تعليم KYC كموثق (تحديد verified_at)
    public function markVerified(int $userId): bool
    {
        $kyc = $this->findByUserId($userId);
        if (!$kyc) return false;
        return $kyc->update(['verified_at' => now()]);
    }

    // تحديث بيانات KYC
    public function update(int $userId, array $data): bool
    {
        $kyc = $this->findByUserId($userId);
        if (!$kyc) return false;
        return $kyc->update($data);
    }

    // حذف KYC لمستخدم
    public function delete(int $userId): bool
    {
        $kyc = $this->findByUserId($userId);
        if (!$kyc) return false;
        return (bool) $kyc->delete();
    }

    // هل المستخدم موثق؟
    public function isVerified(int $userId): bool
    {
        $kyc = $this->findByUserId($userId);
        return $kyc && $kyc->verified_at !== null;
    }

    // هل وثائق الهوية منتهية الصلاحية؟
    public function isExpired(int $userId): bool
    {
        $kyc = $this->findByUserId($userId);
        if (!$kyc || !$kyc->id_expiry_date) return false;
        return $kyc->id_expiry_date->isPast();
    }

    // هل يوجد سجل KYC لهذا المستخدم؟
    public function exists(int $userId): bool
    {
        return UserKyc::where('user_id', $userId)->exists();
    }
}