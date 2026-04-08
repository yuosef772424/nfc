<?php

namespace App\Services\UserManagement;

use App\Contracts\Repositories\MerchantProfileRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Traits\AuditableTrait;
use Illuminate\Validation\ValidationException;

class MerchantProfileService
{
    use AuditableTrait;

    public function __construct(
        protected MerchantProfileRepositoryInterface $merchantProfileRepo,
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
    ) {}

    // ------------------- تنفيذ الدوال المجردة من الـ Traits -------------------
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }

    // ------------------- إنشاء الملف -------------------
    public function createProfile(int $userId, array $data): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->user_type !== 'merchant') {
            throw ValidationException::withMessages(['user' => 'User is not a merchant.']);
        }

        if ($this->merchantProfileRepo->exists($userId)) {
            throw ValidationException::withMessages(['profile' => 'Merchant profile already exists.']);
        }

        $requiredFields = ['business_name', 'business_type'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw ValidationException::withMessages([$field => "The $field field is required."]);
            }
        }

        $profile = $this->merchantProfileRepo->create($userId, $data);
        $this->logAudit('merchant_profile_created', 'merchant_profile', $profile->user_id, $userId, null, $profile->toArray());

        return $profile->toArray();
    }

    // ------------------- استعلامات -------------------
    public function getProfile(int $userId): ?array
    {
        return $this->merchantProfileRepo->getByUserId($userId)?->toArray();
    }

    public function exists(int $userId): bool
    {
        return $this->merchantProfileRepo->exists($userId);
    }

    public function isActive(int $userId): bool
    {
        return $this->merchantProfileRepo->isActive($userId);
    }

    // ------------------- تحديث الملف -------------------
    public function updateProfile(int $userId, array $data): bool
    {
        $profile = $this->merchantProfileRepo->getByUserId($userId);
        if (!$profile) {
            throw ValidationException::withMessages(['profile' => 'Merchant profile not found.']);
        }

        $allowedFields = ['business_name', 'business_type', 'is_active'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            throw ValidationException::withMessages(['update' => 'No valid fields to update.']);
        }

        if (isset($updateData['business_type'])) {
            $allowedTypes = ['retail', 'wholesale', 'service', 'restaurant', 'other'];
            if (!in_array($updateData['business_type'], $allowedTypes)) {
                throw ValidationException::withMessages(['business_type' => 'Invalid business type.']);
            }
        }

        $oldData = $profile->toArray();
        $updated = $this->merchantProfileRepo->update($userId, $updateData);

        if ($updated) {
            $this->logAudit('merchant_profile_updated', 'merchant_profile', $userId, $userId, $oldData, $updateData);
        }
        return $updated;
    }

    public function setActive(int $userId, bool $isActive): bool
    {
        return $this->updateProfile($userId, ['is_active' => $isActive]);
    }

    // ------------------- حذف الملف -------------------
    public function deleteProfile(int $userId): bool
    {
        $profile = $this->merchantProfileRepo->getByUserId($userId);
        if (!$profile) {
            throw ValidationException::withMessages(['profile' => 'Merchant profile not found.']);
        }

        $deleted = $this->merchantProfileRepo->delete($userId);
        if ($deleted) {
            $this->logAudit('merchant_profile_deleted', 'merchant_profile', $userId, $userId, $profile->toArray(), null);
        }
        return $deleted;
    }

    // ------------------- دوال إضافية -------------------
    public function getByBusinessType(string $businessType, int $perPage = 20): array
    {
        return $this->merchantProfileRepo->getByBusinessType($businessType, $perPage)->toArray();
    }

    public function getActiveProfiles(int $perPage = 20): array
    {
        return $this->merchantProfileRepo->getActive()->toArray(); // لاحظ أن getActive يعيد Collection، ليس Paginator
    }
}