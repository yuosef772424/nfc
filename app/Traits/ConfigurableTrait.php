<?php

namespace App\Traits;

use App\Contracts\Repositories\AppConfigRepositoryInterface;

trait ConfigurableTrait
{
    /**
     * يجب أن توفر الخدمة التي تستخدم هذا الـ Trait دالة لإرجاع الـ Config Repository.
     * @return AppConfigRepositoryInterface
     */
    abstract protected function getConfigRepo(): AppConfigRepositoryInterface;

    // ------------------- أمان (Security) -------------------
    protected function getMaxLoginAttempts(): int
    {
        return (int) $this->getConfigRepo()->getValue('security', 'login.max_attempts') ?? 5;
    }

    protected function getLoginLockoutSeconds(): int
    {
        return (int) $this->getConfigRepo()->getValue('security', 'login.lockout_seconds') ?? 300;
    }

    protected function getMaxRegistrationAttempts(): int
    {
        return (int) $this->getConfigRepo()->getValue('security', 'registration.max_attempts') ?? 3;
    }

    protected function getRegistrationLockoutSeconds(): int
    {
        return (int) $this->getConfigRepo()->getValue('security', 'registration.lockout_seconds') ?? 600;
    }

    protected function getMaxResetPasswordAttempts(): int
    {
        return (int) $this->getConfigRepo()->getValue('security', 'reset_password.max_attempts') ?? 3;
    }
    // أضف داخل ConfigurableTrait في قسم الأمان

protected function getMaxVerificationAttempts(): int
{
    return (int) $this->getConfigRepo()->getValue('security', 'verification.max_attempts') ?? 5;
}

protected function getVerificationLockoutSeconds(): int
{
    return (int) $this->getConfigRepo()->getValue('security', 'verification.lockout_seconds') ?? 3600;
}
    protected function getResetPasswordLockoutSeconds(): int
    {
        return (int) $this->getConfigRepo()->getValue('security', 'reset_password.lockout_seconds') ?? 900;
    }

    protected function getMaxEmailChangeAttempts(): int
    {
        return (int) $this->getConfigRepo()->getValue('security', 'email_change.max_attempts') ?? 3;
    }

    protected function getEmailChangeLockoutSeconds(): int
    {
        return (int) $this->getConfigRepo()->getValue('security', 'email_change.lockout_seconds') ?? 900;
    }

    protected function getMaxPinAttempts(): int
    {
        return (int) $this->getConfigRepo()->getValue('security', 'pin.max_attempts') ?? 3;
    }

    protected function getPinLockoutSeconds(): int
    {
        return (int) $this->getConfigRepo()->getValue('security', 'pin.lockout_seconds') ?? 900;
    }

    // ------------------- السحوبات (Withdrawal) -------------------
    protected function getWithdrawalVerificationCodeLength(): int
    {
        return (int) $this->getConfigRepo()->getValue('withdrawal', 'verification_code_length') ?? 6;
    }

    protected function getWithdrawalPendingExpiryMinutes(): int
    {
        return (int) $this->getConfigRepo()->getValue('withdrawal', 'pending_expiry_minutes') ?? 30;
    }

    protected function getWithdrawalCommissionType(): string
    {
        return $this->getConfigRepo()->getValue('fee', 'withdrawal_commission_type') ?? 'percentage';
    }

    protected function getWithdrawalCommissionValue(): float
    {
        return (float) $this->getConfigRepo()->getValue('fee', 'withdrawal_commission_value') ?? 0;
    }

    // ------------------- الجلسات (Session) -------------------
    protected function getSessionExpiryMinutes(): int
    {
        return (int) $this->getConfigRepo()->getValue('policy', 'session.expiry_minutes') ?? 120;
    }

    // ------------------- المحفظة (Wallet) -------------------
    protected function getSystemWalletId(): int
    {
        return (int) $this->getConfigRepo()->getValue('wallet', 'system_wallet_id') ?? 1;
    }

    // ------------------- أنواع الثوابت (Constants) -------------------
    protected function getCommissionTypePercentage(): string
    {
        return $this->getConfigRepo()->getValue('constant', 'commission_type.percentage') ?? 'percentage';
    }

    protected function getUserTypeAgent(): string
    {
        return $this->getConfigRepo()->getValue('constant', 'user_type.agent') ?? 'agent';
    }

    protected function getUserTypeMerchant(): string
    {
        return $this->getConfigRepo()->getValue('constant', 'user_type.merchant') ?? 'merchant';
    }

    protected function getWithdrawalStatusPending(): string
    {
        return $this->getConfigRepo()->getValue('constant', 'withdrawal_status.pending') ?? 'pending';
    }

    protected function getWithdrawalStatusCompleted(): string
    {
        return $this->getConfigRepo()->getValue('constant', 'withdrawal_status.completed') ?? 'completed';
    }

    // داخل ConfigurableTrait.php في قسم الأمان

protected function getMaxPasswordChangeAttempts(): int
{
    return (int) $this->getConfigRepo()->getValue('security', 'password_change.max_attempts') ?? 3;
}

protected function getPasswordChangeLockoutSeconds(): int
{
    return (int) $this->getConfigRepo()->getValue('security', 'password_change.lockout_seconds') ?? 900;
}

protected function getMaxRefreshAttempts(): int
{
    return (int) $this->getConfigRepo()->getValue('security', 'token_refresh.max_attempts') ?? 5;
}

protected function getRefreshLockoutSeconds(): int
{
    return (int) $this->getConfigRepo()->getValue('security', 'token_refresh.lockout_seconds') ?? 300;
}  
}