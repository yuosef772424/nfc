<?php

namespace App\Repositories;

use App\Models\Session;
use App\Contracts\Repositories\SessionRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class SessionRepository implements SessionRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- Retrieval -------------------
    public function findById(int $id, array $with = []): ?Session
    {
        return Session::with($with)->find($id);
    }

    public function getByTokenHash(string $tokenHash, array $with = []): ?Session
    {
        return Session::with($with)->where('token_hash', $tokenHash)->first();
    }

    public function getActiveByUserId(int $userId, array $with = []): Collection
    {
        return Session::with($with)
            ->where('user_id', $userId)
            ->where('expires_at', '>', now())
            ->get();
    }

    public function getAllByUserId(int $userId, array $with = []): Collection
    {
        return Session::with($with)->where('user_id', $userId)->get();
    }

    public function isExpired(int $id): bool
    {
        $session = $this->findById($id);
        return $session && $session->expires_at->isPast();
    }

    // ------------------- Write -------------------
    protected function getSessionExpiryMinutes(): int
    {
        $value = $this->configRepo->getValue('policy', 'session.expiry_minutes', ['scope' => 'global']);
        return is_numeric($value) ? (int) $value : 120;
    }

    public function create(
        int $userId,
        string $tokenHash,
        array $deviceInfo,
        ?array $location,
        ?\DateTimeInterface $expiresAt = null
    ): Session {
        $resolvedExpiry = $expiresAt ?? now()->addMinutes($this->getSessionExpiryMinutes());

        return Session::create([
            'user_id'     => $userId,
            'token_hash'  => $tokenHash,
            'device_info' => $deviceInfo,
            'location'    => $location,
            'expires_at'  => $resolvedExpiry,
        ]);
    }

    public function deleteById(int $id): bool
    {
        $session = $this->findById($id);
        if (!$session) return false;
        return (bool) $session->delete();
    }

    public function deleteAllByUserId(int $userId): int
    {
        return Session::where('user_id', $userId)->delete();
    }

    public function deleteExpired(): int
    {
        return Session::where('expires_at', '<=', now())->delete();
    }

    // ------------------- Checks -------------------
    public function isValid(string $tokenHash): bool
    {
        $session = $this->getByTokenHash($tokenHash);
        return $session && !$session->expires_at->isPast();
    }
}