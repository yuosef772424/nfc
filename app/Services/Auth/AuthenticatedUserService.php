<?php

namespace App\Services\Auth;

use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\CacheRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Services\Auth\SessionService;
use App\Traits\ConfigurableTrait;
use App\Traits\RateLimiterTrait;
use App\Traits\AuditableTrait;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AuthenticatedUserService
{
    use ConfigurableTrait,
        RateLimiterTrait,
        AuditableTrait;

    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected SessionService $sessionService,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected CacheRepositoryInterface $cacheRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    // ------------------- تنفيذ الدوال المجردة من الـ Traits -------------------
    protected function getCacheRepo(): CacheRepositoryInterface { return $this->cacheRepo; }
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    // دوال إعدادات من ConfigurableTrait
    protected function getMaxPasswordChangeAttempts(): int { return (int) $this->configRepo->getValue('security', 'password_change.max_attempts') ?? 3; }
    protected function getPasswordChangeLockoutSeconds(): int { return (int) $this->configRepo->getValue('security', 'password_change.lockout_seconds') ?? 900; }
    protected function getMaxRefreshAttempts(): int { return (int) $this->configRepo->getValue('security', 'token_refresh.max_attempts') ?? 5; }
    protected function getRefreshLockoutSeconds(): int { return (int) $this->configRepo->getValue('security', 'token_refresh.lockout_seconds') ?? 300; }
    protected function getSessionExpiryMinutes(): int { return (int) $this->configRepo->getValue('policy', 'session.expiry_minutes') ?? 120; }

    // ------------------- تسجيل الخروج -------------------
    public function logout(string $tokenHash): bool
    {
        $session = $this->sessionService->getSessionByTokenHash($tokenHash);
        if (!$session) {
            throw ValidationException::withMessages(['token' => 'Invalid session.']);
        }

        return $this->sessionService->revokeSession($session->id, $session->user_id);
    }

    // ------------------- تجديد التوكن -------------------
    public function refreshToken(string $oldTokenHash): array
    {
        $oldSession = $this->sessionService->getSessionByTokenHash($oldTokenHash);
        if (!$oldSession) {
            throw ValidationException::withMessages(['token' => 'Invalid session.']);
        }

        $userId = $oldSession->user_id;
        $attemptKey = "refresh_attempts:user:" . $userId;
        $this->checkRateLimit($attemptKey, $this->getMaxRefreshAttempts(), 'Too many refresh attempts.');

        if ($oldSession->expires_at->isPast()) {
            $this->recordFailedAttempt($attemptKey, $this->getRefreshLockoutSeconds());
            throw ValidationException::withMessages(['token' => 'Session expired. Please login again.']);
        }

        // إنشاء توكن جديد
        $newTokenHash = hash('sha256', Str::random(60));
        $newExpiresAt = now()->addMinutes($this->getSessionExpiryMinutes());

        $oldSession->update([
            'token_hash' => $newTokenHash,
            'expires_at' => $newExpiresAt,
        ]);

        $this->resetAttempts($attemptKey);

        return [
            'token'      => $newTokenHash,
            'expires_at' => $newExpiresAt->toDateTimeString(),
        ];
    }

    // ------------------- تغيير كلمة المرور -------------------
    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool
    {
        $attemptKey = "password_change_attempts:user:" . $userId;
        $this->checkRateLimit($attemptKey, $this->getMaxPasswordChangeAttempts(), 'Too many failed attempts.');

        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw ValidationException::withMessages(['user' => 'User not found.']);
        }

        if (!Hash::check($currentPassword, $user->password)) {
            $this->recordFailedAttempt($attemptKey, $this->getPasswordChangeLockoutSeconds());
            throw ValidationException::withMessages(['current_password' => 'Current password is incorrect.']);
        }

        $this->resetAttempts($attemptKey);
        $updated = $this->userRepo->update($userId, ['password' => Hash::make($newPassword)]);

        if ($updated) {
            $this->logAudit('change_password', 'user', $userId, $userId, null, null);
        }
        return $updated;
    }

    // ------------------- إبطال جميع جلسات المستخدم -------------------
    public function revokeAllSessions(int $userId, ?string $exceptTokenHash = null): int
    {
        if ($exceptTokenHash) {
            return $this->sessionService->revokeOtherSessions($userId, $exceptTokenHash);
        }
        return $this->sessionService->revokeAllSessions($userId);
    }

    // ------------------- الحصول على معلومات الجلسة الحالية -------------------
    public function getCurrentSession(string $tokenHash): ?array
    {
        $session = $this->sessionService->getSessionByTokenHash($tokenHash);
        if (!$session) return null;

        return [
            'id'          => $session->id,
            'user_id'     => $session->user_id,
            'expires_at'  => $session->expires_at->toDateTimeString(),
            'device_info' => $session->device_info,
            'location'    => $session->location,
        ];
    }

    // ------------------- الحصول على المستخدم الحالي -------------------
    public function getAuthenticatedUser(string $tokenHash): ?object
    {
        $session = $this->sessionService->getSessionByTokenHash($tokenHash);
        if (!$session) return null;

        return $this->userRepo->findById($session->user_id);
    }
}