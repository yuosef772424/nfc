<?php

namespace App\Contracts\Services;

use App\Models\User;
use App\Models\SystemPolicy;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AdminServiceInterface
{
    // ---------------------------------------------------------------
    // User Management
    // ---------------------------------------------------------------

    public function suspendUser(int $userId, string $reason, int $adminId): bool;

    public function activateUser(int $userId, int $adminId): bool;

    public function forceVerifyUser(int $userId, int $adminId): bool;

    public function forceVerifyKyc(int $userId, int $adminId): bool;

    public function deleteUser(int $userId, int $adminId): bool;

    /** جلب كل المستخدمين مع الفلاتر (type, status, verified, date range) */
    public function getUsers(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    public function getUserDetails(int $userId): array; // {user, kyc, wallets, cards, devices, sessions}

    // ---------------------------------------------------------------
    // System Policies
    // ---------------------------------------------------------------

    /**
     * تحديث سياسة نظام (fees, limits, security)
     *
     * @throws \App\Exceptions\Admin\PolicyNotFoundException
     */
    public function updatePolicy(string $key, mixed $value, string $scopeType = 'global', ?int $scopeId = null, int $adminId = 0): SystemPolicy;

    public function getPolicies(string $category = ''): \Illuminate\Database\Eloquent\Collection;

    // ---------------------------------------------------------------
    // Financial Operations
    // ---------------------------------------------------------------

    /**
     * إيداع يدوي في محفظة (للتصحيح والمنح)
     */
    public function manualDeposit(int $walletId, float $amount, string $reason, int $adminId): bool;

    /**
     * خصم يدوي من محفظة (للتصحيح)
     */
    public function manualDeduct(int $walletId, float $amount, string $reason, int $adminId): bool;

    /**
     * تجميد محفظة
     */
    public function freezeWallet(int $walletId, string $reason, int $adminId): bool;

    public function unfreezeWallet(int $walletId, int $adminId): bool;

    // ---------------------------------------------------------------
    // Reconciliation
    // ---------------------------------------------------------------

    /**
     * فحص اتساق الأرصدة لكل المحافظ أو محفظة بعينها
     *
     * @return array<int, array{wallet_id: int, is_consistent: bool, diff: float}>
     */
    public function runBalanceReconciliation(?int $walletId = null): array;

    // ---------------------------------------------------------------
    // Audit
    // ---------------------------------------------------------------

    public function getAuditLog(array $filters = [], int $perPage = 20): LengthAwarePaginator;
}
