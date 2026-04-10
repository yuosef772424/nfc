<?php

namespace App\Traits;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

trait ManageableTrait
{
    // ========== دوال الملكية (Ownership) ==========

    /**
     * التحقق من أن المستخدم هو مالك النموذج المحدد.
     * @throws ValidationException إذا لم يكن المالك.
     */
    protected function checkOwnership(Model $model, int $userId, string $relationField = 'user_id'): void
    {
        if ($model->{$relationField} !== $userId) {
            throw ValidationException::withMessages(['authorization' => 'You do not own this resource.']);
        }
    }

    /**
     * التحقق من أن المحفظة تخص المستخدم (تتطلب تنفيذ `getWalletRepo`).
     */
    protected function checkWalletOwnership(int $walletId, int $userId): void
    {
        $wallet = $this->getWalletRepo()->findById($walletId);
        if (!$wallet || $wallet->user_id !== $userId) {
            throw ValidationException::withMessages(['wallet' => 'Wallet not found or does not belong to you.']);
        }
    }

    // ========== دوال الحالة (Status) ==========

    /**
     * تفعيل نموذج (تعيين الحالة إلى active).
     */
    protected function activateModel(Model $model, string $statusColumn = 'status', string $activeValue = 'active'): bool
    {
        return $model->update([$statusColumn => $activeValue]);
    }

    /**
     * تعطيل نموذج (تعيين الحالة إلى inactive).
     */
    protected function deactivateModel(Model $model, string $statusColumn = 'status', string $inactiveValue = 'inactive'): bool
    {
        return $model->update([$statusColumn => $inactiveValue]);
    }

    /**
     * تعليق نموذج (تعيين الحالة إلى suspended).
     */
    protected function suspendModel(Model $model, string $statusColumn = 'status', string $suspendedValue = 'suspended'): bool
    {
        return $model->update([$statusColumn => $suspendedValue]);
    }

    // ========== دوال مجردة (يجب تنفيذها في الخدمة) ==========

    /**
     * يجب أن توفر الخدمة الـ Wallet Repository.
     * @return \App\Contracts\Repositories\WalletRepositoryInterface
     */
    abstract protected function getWalletRepo();
}