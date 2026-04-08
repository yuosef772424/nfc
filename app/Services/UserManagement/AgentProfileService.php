<?php

namespace App\Services\UserManagement;

use App\Contracts\Repositories\AgentProfileRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Traits\AuditableTrait;
use App\Traits\ConfigurableTrait;
use Illuminate\Validation\ValidationException;

class AgentProfileService
{
    use AuditableTrait, ConfigurableTrait;

    public function __construct(
        protected AgentProfileRepositoryInterface $agentProfileRepo,
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    // ------------------- تنفيذ الدوال المجردة من الـ Traits -------------------
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    // ------------------- إنشاء الملف -------------------
    public function createProfile(int $userId, array $data): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || $user->user_type !== 'agent') {
            throw ValidationException::withMessages(['user' => 'User is not an agent.']);
        }

        if ($this->agentProfileRepo->exists($userId)) {
            throw ValidationException::withMessages(['profile' => 'Agent profile already exists.']);
        }

        $profile = $this->agentProfileRepo->create($userId, $data);
        $this->logAudit('agent_profile_created', 'agent_profile', $profile->user_id, $userId, null, $profile->toArray());

        return $profile->toArray();
    }

    // ------------------- استعلامات -------------------
    public function getProfile(int $userId): ?array
    {
        return $this->agentProfileRepo->getByUserId($userId)?->toArray();
    }

    public function exists(int $userId): bool
    {
        return $this->agentProfileRepo->exists($userId);
    }

    public function isActive(int $userId): bool
    {
        return $this->agentProfileRepo->isActive($userId);
    }

    // ------------------- تحديث الملف -------------------
    public function updateProfile(int $userId, array $data): bool
    {
        $profile = $this->agentProfileRepo->getByUserId($userId);
        if (!$profile) {
            throw ValidationException::withMessages(['profile' => 'Agent profile not found.']);
        }

        $allowedFields = ['commission_type', 'commission_value', 'is_active'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            throw ValidationException::withMessages(['update' => 'No valid fields to update.']);
        }

        if (isset($updateData['commission_type']) && !in_array($updateData['commission_type'], ['percentage', 'fixed'])) {
            throw ValidationException::withMessages(['commission_type' => 'Commission type must be percentage or fixed.']);
        }

        if (isset($updateData['commission_value']) && $updateData['commission_value'] < 0) {
            throw ValidationException::withMessages(['commission_value' => 'Commission value must be non-negative.']);
        }

        $oldData = $profile->toArray();
        $updated = $this->agentProfileRepo->update($userId, $updateData);

        if ($updated) {
            $this->logAudit('agent_profile_updated', 'agent_profile', $userId, $userId, $oldData, $updateData);
        }
        return $updated;
    }

    public function updateCommission(int $userId, string $type, float $value): bool
    {
        return $this->updateProfile($userId, ['commission_type' => $type, 'commission_value' => $value]);
    }

    public function setActive(int $userId, bool $isActive): bool
    {
        return $this->updateProfile($userId, ['is_active' => $isActive]);
    }

    // ------------------- حساب العمولة -------------------
    public function calculateCommission(int $userId, float $amount): float
    {
        $profile = $this->agentProfileRepo->getByUserId($userId);
        if (!$profile || !$profile->is_active) return 0.0;

        $percentageType = $this->getCommissionTypePercentage(); // من ConfigurableTrait
        if ($profile->commission_type === $percentageType) {
            return round($amount * ($profile->commission_value / 100), 2);
        }
        return (float) $profile->commission_value;
    }

    // ------------------- حذف الملف -------------------
    public function deleteProfile(int $userId): bool
    {
        $profile = $this->agentProfileRepo->getByUserId($userId);
        if (!$profile) {
            throw ValidationException::withMessages(['profile' => 'Agent profile not found.']);
        }

        $deleted = $this->agentProfileRepo->delete($userId);
        if ($deleted) {
            $this->logAudit('agent_profile_deleted', 'agent_profile', $userId, $userId, $profile->toArray(), null);
        }
        return $deleted;
    }
}