<?php

namespace App\Contracts\Services;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserServiceInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function getById(int $id): ?User;

    public function getByPhone(string $phone): ?User;

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getAgents(int $perPage = 20): LengthAwarePaginator;

    public function getMerchants(int $perPage = 20): LengthAwarePaginator;

    // ---------------------------------------------------------------
    // Management
    // ---------------------------------------------------------------

    public function updateProfile(int $userId, array $data): User;

    public function updateStatus(int $userId, string $status): bool;

    public function suspend(int $userId, string $reason): bool;

    public function activate(int $userId): bool;

    public function delete(int $userId): bool;

    // ---------------------------------------------------------------
    // Admin: User Creation
    // ---------------------------------------------------------------

    /** إنشاء وكيل جديد مع بروفايله */
    public function createAgent(array $userData, array $agentData): User;

    /** إنشاء تاجر جديد مع بروفايله */
    public function createMerchant(array $userData, array $merchantData): User;

    // ---------------------------------------------------------------
    // Referrals
    // ---------------------------------------------------------------

    /** جلب المستخدمين الذين تم تسجيلهم بواسطة هذا المستخدم */
    public function getReferredUsers(int $userId, int $perPage = 20): LengthAwarePaginator;

    public function getReferralCount(int $userId): int;
}
