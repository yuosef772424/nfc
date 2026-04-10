<?php

namespace App\Services\Auth;

use App\Contracts\Repositories\SessionRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Traits\AuditableTrait;
use App\Traits\ConfigurableTrait;
use Illuminate\Validation\ValidationException;

class SessionService
{
    use AuditableTrait, ConfigurableTrait;

    public function __construct(
        protected SessionRepositoryInterface $sessionRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    // ------------------- استعلامات -------------------

    /**
     * الحصول على جميع جلسات المستخدم النشطة.
     */
    public function getUserActiveSessions(int $userId): array
    {
        $sessions = $this->sessionRepo->getActiveByUserId($userId);
        return $sessions->map(fn($session) => $this->formatSession($session))->toArray();
    }

    /**
     * الحصول على جميع جلسات المستخدم (بما فيها المنتهية).
     */
    public function getAllUserSessions(int $userId): array
    {
        $sessions = $this->sessionRepo->getAllByUserId($userId);
        return $sessions->map(fn($session) => $this->formatSession($session))->toArray();
    }

    /**
     * التحقق من أن الجلسة ما زالت صالحة.
     */
    public function isValidSession(string $tokenHash): bool
    {
        return $this->sessionRepo->isValid($tokenHash);
    }

    // ------------------- إدارة الجلسات -------------------

    /**
     * إلغاء جلسة محددة (يتحقق من ملكية المستخدم).
     */
    public function revokeSession(int $sessionId, int $userId): bool
    {
        $session = $this->sessionRepo->findById($sessionId);
        if (!$session) {
            throw ValidationException::withMessages(['session' => 'Session not found.']);
        }
        if ($session->user_id !== $userId) {
            throw ValidationException::withMessages(['session' => 'You do not own this session.']);
        }

        $deleted = $this->sessionRepo->deleteById($sessionId);
        if ($deleted) {
            $this->logAudit(
                'session_revoked',
                'session',
                $sessionId,
                $userId,
                ['device_info' => $session->device_info, 'location' => $session->location],
                null
            );
        }
        return $deleted;
    }

    /**
     * إلغاء جميع جلسات المستخدم الأخرى (الاحتفاظ بالجلسة الحالية).
     */
    public function revokeOtherSessions(int $userId, string $currentTokenHash): int
    {
        $currentSession = $this->sessionRepo->getByTokenHash($currentTokenHash);
        if (!$currentSession) {
            throw ValidationException::withMessages(['token' => 'Current session invalid.']);
        }

        $allSessions = $this->sessionRepo->getAllByUserId($userId);
        $revokedCount = 0;

        foreach ($allSessions as $session) {
            if ($session->id !== $currentSession->id) {
                if ($this->sessionRepo->deleteById($session->id)) {
                    $revokedCount++;
                }
            }
        }

        if ($revokedCount > 0) {
            $this->logAudit(
                'other_sessions_revoked',
                'user',
                $userId,
                $userId,
                null,
                ['revoked_count' => $revokedCount]
            );
        }

        return $revokedCount;
    }

    /**
     * إلغاء جميع جلسات المستخدم (تسجيل خروج كامل).
     */
    public function revokeAllSessions(int $userId): int
    {
        $count = $this->sessionRepo->deleteAllByUserId($userId);
        if ($count > 0) {
            $this->logAudit(
                'all_sessions_revoked',
                'user',
                $userId,
                $userId,
                null,
                ['revoked_count' => $count]
            );
        }
        return $count;
    }

    /**
     * تنظيف الجلسات المنتهية تلقائياً (للاستخدام في مهمة مجدولة).
     */
    public function cleanupExpiredSessions(): int
    {
        return $this->sessionRepo->deleteExpired();
    }

    // ------------------- دوال مساعدة -------------------

    protected function formatSession($session): array
    {
        return [
            'id'          => $session->id,
            'device_info' => $session->device_info,
            'location'    => $session->location,
            'created_at'  => $session->created_at->toDateTimeString(),
            'expires_at'  => $session->expires_at->toDateTimeString(),
            'is_current'  => $this->isCurrentSession($session),
            'is_expired'  => $session->expires_at->isPast(),
        ];
    }

    protected function isCurrentSession($session): bool
    {
        $currentToken = request()->bearerToken();
        if (!$currentToken) {
            return false;
        }
        $currentHash = hash('sha256', $currentToken);
        return $session->token_hash === $currentHash;
    }


    // في App\Services\Auth\SessionService

public function createSession(
    int $userId,
    array $deviceInfo = [],
    ?array $location = null,
    ?int $expiryMinutes = null
): array {
    $expiryMinutes = $expiryMinutes ?? $this->getDefaultSessionExpiry();
    $token = Str::random(60);
    $tokenHash = hash('sha256', $token);
    $expiresAt = now()->addMinutes($expiryMinutes);

    $session = $this->sessionRepo->create(
        userId: $userId,
        tokenHash: $tokenHash,
        deviceInfo: $deviceInfo,
        location: $location,
        expiresAt: $expiresAt
    );

    return [
        'token'      => $token,
        'expires_at' => $expiresAt->toDateTimeString(),
        'session_id' => $session->id,
    ];
}

protected function getDefaultSessionExpiry(): int
{
    return (int) $this->configRepo->getValue('policy', 'session.expiry_minutes') ?? 120;
}
}