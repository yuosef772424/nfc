<?php

namespace App\Traits;

use App\Contracts\Repositories\SessionRepositoryInterface;
use Illuminate\Support\Str;

trait SessionManagementTrait
{
    /**
     * إنشاء جلسة جديدة وتوليد توكن.
     */
    protected function createNewSession(int $userId, array $deviceInfo = [], ?array $location = null, ?int $expiryMinutes = null): array
    {
        $tokenHash = hash('sha256', Str::random(60));
        $expiresAt = now()->addMinutes($expiryMinutes ?? $this->getDefaultSessionExpiry());
        
        $session = $this->getSessionRepo()->create(
            userId: $userId,
            tokenHash: $tokenHash,
            deviceInfo: $deviceInfo ?: ['user_agent' => request()->userAgent()],
            location: $location ?: ['ip' => request()->ip()],
            expiresAt: $expiresAt
        );

        return [
            'token' => $tokenHash,
            'expires_at' => $expiresAt->toDateTimeString(),
            'session_id' => $session->id,
        ];
    }
    // داخل SessionManagementTrait
protected function getDefaultSessionExpiry(): int
{
    return $this->getConfigRepo()->getSessionExpiryMinutes(); // يحتاج وجود getConfigRepo()
}

    /**
     * @return SessionRepositoryInterface
     */
    abstract protected function getSessionRepo(): SessionRepositoryInterface;


}
