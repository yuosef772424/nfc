<?php

namespace App\Services\UserManagement;

use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\CacheRepositoryInterface;
use App\Contracts\Repositories\SessionRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Traits\ConfigurableTrait;
use App\Traits\RateLimiterTrait;
use App\Traits\AuditableTrait;
use App\Traits\OtpVerificationTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class UserProfileService
{
    use ConfigurableTrait,
        RateLimiterTrait,
        AuditableTrait,
        OtpVerificationTrait;

    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected CacheRepositoryInterface $cacheRepo,
        protected AppConfigRepositoryInterface $configRepo,
        protected SessionRepositoryInterface $sessionRepo,
    ) {}

    // ------------------- تنفيذ الدوال المجردة -------------------
    protected function getCacheRepo(): CacheRepositoryInterface { return $this->cacheRepo; }
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }
    protected function getSessionRepo(): SessionRepositoryInterface { return $this->sessionRepo; }

    // دوال OtpVerificationTrait (نستخدم القيم الافتراضية أو من Config)
    protected function getOtpTtlSeconds(): int { return 900; } // 15 minutes
    protected function getOtpMaxResendAttempts(): int { return $this->getMaxEmailChangeAttempts(); }
    protected function getOtpResendWindowSeconds(): int { return $this->getEmailChangeLockoutSeconds(); }

    // دوال القيود من ConfigurableTrait (نعيد تعريفها للوضوح، أو يمكن نقلها إلى الـ Trait)
    protected function getMaxEmailChangeAttempts(): int
    {
        return (int) $this->configRepo->getValue('security', 'email_change.max_attempts') ?? 3;
    }
    protected function getEmailChangeLockoutSeconds(): int
    {
        return (int) $this->configRepo->getValue('security', 'email_change.lockout_seconds') ?? 900;
    }
    protected function getMaxPhoneChangeAttempts(): int
    {
        return (int) $this->configRepo->getValue('security', 'phone_change.max_attempts') ?? 3;
    }
    protected function getPhoneChangeLockoutSeconds(): int
    {
        return (int) $this->configRepo->getValue('security', 'phone_change.lockout_seconds') ?? 900;
    }
    protected function getMaxProfileUpdateAttempts(): int
    {
        return (int) $this->configRepo->getValue('security', 'profile_update.max_attempts') ?? 5;
    }
    protected function getProfileUpdateLockoutSeconds(): int
    {
        return (int) $this->configRepo->getValue('security', 'profile_update.lockout_seconds') ?? 600;
    }

    // ------------------- الملف الشخصي -------------------
    public function getUserProfile(int $userId, array $with = []): ?array
    {
        $user = $this->userRepo->findById($userId, $with);
        return $user?->toArray();
    }

    public function updateProfile(int $userId, array $data): bool
    {
        $attemptKey = "profile_update_attempts:user:" . $userId;
        $this->checkRateLimit($attemptKey, $this->getMaxProfileUpdateAttempts(), 'Too many update attempts.');

        $allowedFields = ['name', 'avatar', 'preferences'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));
        if (empty($updateData)) {
            throw ValidationException::withMessages(['profile' => 'No valid fields to update.']);
        }

        $updated = $this->userRepo->update($userId, $updateData);
        if ($updated) {
            $this->resetAttempts($attemptKey);
            $this->logAudit('profile_update', 'user', $userId, $userId, null, $updateData);
        } else {
            $this->recordFailedAttempt($attemptKey, $this->getProfileUpdateLockoutSeconds());
            throw ValidationException::withMessages(['profile' => 'Failed to update profile.']);
        }
        return $updated;
    }

    // ------------------- تغيير البريد الإلكتروني -------------------
    public function initiateEmailChange(int $userId, string $currentPassword, string $newEmail): void
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || !Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Invalid password.']);
        }
        if ($user->status === 'deleted') {
            throw ValidationException::withMessages(['account' => 'Account has been deleted.']);
        }
        if ($this->userRepo->existsByEmail($newEmail)) {
            throw ValidationException::withMessages(['new_email' => 'Email already taken.']);
        }

        $attemptKey = "email_change_attempts:user:" . $userId;
        $this->checkRateLimit($attemptKey, $this->getMaxEmailChangeAttempts(), 'Too many email change attempts.');

        $code = $this->generateOtpCode(6);
        $this->storeOtpCode("email_change:{$userId}", json_encode(['new_email' => $newEmail, 'code' => $code]), $this->getOtpTtlSeconds());

        $this->recordFailedAttempt($attemptKey, $this->getEmailChangeLockoutSeconds());
        // إرسال الكود... (حدث)
    }

    public function confirmEmailChange(int $userId, string $code): bool
    {
        $storedJson = $this->cacheRepo->get("email_change:{$userId}");
        if (!$storedJson) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }
        $stored = json_decode($storedJson, true);
        if ($stored['code'] !== $code) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }

        $newEmail = $stored['new_email'];
        $updated = $this->userRepo->update($userId, ['email' => $newEmail]);
        if ($updated) {
            $this->cacheRepo->forget("email_change:{$userId}");
            $this->logAudit('email_change', 'user', $userId, $userId, null, ['new_email' => $newEmail]);
        }
        return $updated;
    }

    // ------------------- تغيير رقم الهاتف -------------------
    public function initiatePhoneChange(int $userId, string $currentPassword, string $newPhone): void
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || !Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages(['current_password' => 'Invalid password.']);
        }
        if ($user->status === 'deleted') {
            throw ValidationException::withMessages(['account' => 'Account has been deleted.']);
        }
        if ($this->userRepo->existsByPhone($newPhone)) {
            throw ValidationException::withMessages(['new_phone' => 'Phone already taken.']);
        }

        $attemptKey = "phone_change_attempts:user:" . $userId;
        $this->checkRateLimit($attemptKey, $this->getMaxPhoneChangeAttempts(), 'Too many phone change attempts.');

        $code = $this->generateOtpCode(6);
        $this->storeOtpCode("phone_change:{$userId}", json_encode(['new_phone' => $newPhone, 'code' => $code]), $this->getOtpTtlSeconds());

        $this->recordFailedAttempt($attemptKey, $this->getPhoneChangeLockoutSeconds());
    }

    public function confirmPhoneChange(int $userId, string $code): bool
    {
        $storedJson = $this->cacheRepo->get("phone_change:{$userId}");
        if (!$storedJson) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }
        $stored = json_decode($storedJson, true);
        if ($stored['code'] !== $code) {
            throw ValidationException::withMessages(['code' => 'Invalid or expired code.']);
        }

        $newPhone = $stored['new_phone'];
        $updated = $this->userRepo->update($userId, ['phone' => $newPhone]);
        if ($updated) {
            $this->cacheRepo->forget("phone_change:{$userId}");
            $this->logAudit('phone_change', 'user', $userId, $userId, null, ['new_phone' => $newPhone]);
        }
        return $updated;
    }

    // ------------------- إدارة الحساب -------------------
    public function deactivateAccount(int $userId, string $password): bool
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['password' => 'Invalid password.']);
        }
        if ($user->status === 'deleted') {
            throw ValidationException::withMessages(['account' => 'Account is already deleted.']);
        }

        $updated = $this->userRepo->update($userId, ['status' => 'inactive']);
        if ($updated) {
            $this->sessionRepo->deleteAllByUserId($userId);
            $this->logAudit('account_deactivated', 'user', $userId, $userId, null, null);
        }
        return $updated;
    }

    public function reactivateAccount(int $userId): bool
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) return false;
        if ($user->status === 'deleted') {
            throw ValidationException::withMessages(['account' => 'Deleted account cannot be reactivated.']);
        }
        $updated = $this->userRepo->update($userId, ['status' => 'active']);
        if ($updated) {
            $this->logAudit('account_reactivated', 'user', $userId, $userId, null, null);
        }
        return $updated;
    }

    public function deleteAccount(int $userId, string $password): bool
    {
        $user = $this->userRepo->findById($userId);
        if (!$user || !Hash::check($password, $user->password)) {
            throw ValidationException::withMessages(['password' => 'Invalid password.']);
        }
        if ($user->status === 'deleted') {
            throw ValidationException::withMessages(['account' => 'Account is already deleted.']);
        }

        $updated = $this->userRepo->update($userId, ['status' => 'deleted']);
        if ($updated) {
            $this->sessionRepo->deleteAllByUserId($userId);
            $this->logAudit('account_deleted', 'user', $userId, $userId, null, null);
        }
        return $updated;
    }
}