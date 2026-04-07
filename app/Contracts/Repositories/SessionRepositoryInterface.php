<?php

namespace App\Contracts\Repositories;

use App\Models\Session;
use Illuminate\Database\Eloquent\Collection;

interface SessionRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function findById(int $id): ?Session;

    public function findByTokenHash(string $tokenHash): ?Session;

    public function getActiveByUserId(int $userId): Collection;  // تعوض scopeActive + فلتر user

    public function getAllByUserId(int $userId): Collection;

    // --- الدوال المنقولة من الموديل ---
    public function isExpired(int $id): bool;                    // كانت isExpired

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------

    public function create(int $userId, string $tokenHash, array $deviceInfo, ?array $location, \DateTimeInterface $expiresAt): Session;

    public function deleteById(int $id): bool;

    /** تسجيل خروج كل الجلسات لمستخدم معين */
    public function deleteAllByUserId(int $userId): int;

    /** حذف الجلسات المنتهية الصلاحية */
    public function deleteExpired(): int;

    // ---------------------------------------------------------------
    // Checks
    // ---------------------------------------------------------------

    public function isValid(string $tokenHash): bool;
}