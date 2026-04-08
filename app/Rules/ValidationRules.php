<?php

namespace App\Rules;

use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Support\Facades\App;

class ValidationRules
{
    /**
     * Get the repository instance (cached).
     */
    protected static function getConfigRepo(): AppConfigRepositoryInterface
    {
        return App::make(AppConfigRepositoryInterface::class);
    }

    /**
     * Helper to extract values from config group.
     */
    protected static function getConstantValues(string $groupKey, string $default): array
    {
        $config = self::getConfigRepo()->getGroup('constant', ['category' => $groupKey]);
        if ($config->isEmpty()) {
            return explode(',', $default);
        }
        return $config->pluck('value')->toArray();
    }

    // ------------------- Wallet -------------------
    public static function walletStatus(): array
    {
        $allowed = self::getConfigRepo()->getValue('wallet', 'allowed_statuses') ?? ['active', 'inactive', 'frozen'];
        return ['string', 'in:' . implode(',', $allowed)];
    }

    // ------------------- Withdrawal -------------------
    public static function withdrawalStatus(): array
    {
        $allowed = self::getConfigRepo()->getValue('withdrawal', 'allowed_statuses') ?? ['pending', 'completed', 'failed', 'cancelled'];
        return ['string', 'in:' . implode(',', $allowed)];
    }

    // ------------------- User -------------------
    public static function userType(): array
    {
        $allowed = self::getConstantValues('user_type', 'user,agent,merchant,admin');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    public static function userStatus(): array
    {
        $allowed = self::getConstantValues('user_status', 'active,inactive,suspended');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    // ------------------- Transaction -------------------
    public static function transactionType(): array
    {
        $allowed = self::getConstantValues('transaction_type', 'payment,transfer,deposit,withdrawal,refund');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    public static function transactionStatus(): array
    {
        $allowed = self::getConstantValues('transaction_status', 'pending,completed,failed,cancelled');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    // ------------------- Commission -------------------
    public static function commissionStatus(): array
    {
        $allowed = self::getConstantValues('commission_status', 'pending,paid,cancelled');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    public static function recipientType(): array
    {
        $allowed = self::getConstantValues('recipient_type', 'agent,merchant');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    // ------------------- NFC Device -------------------
    public static function deviceType(): array
    {
        $allowed = self::getConstantValues('device_type', 'physical,mobile');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    public static function deviceStatus(): array
    {
        $allowed = self::getConstantValues('device_status', 'active,inactive,blocked');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    // ------------------- Biometric -------------------
    public static function biometricType(): array
    {
        $allowed = self::getConstantValues('biometric_type', 'fingerprint,face,none');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    // ------------------- Notification -------------------
    public static function notificationType(): array
    {
        $allowed = self::getConstantValues('notification_type', 'payment,withdrawal,alert,info');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    public static function notificationChannel(): array
    {
        $allowed = self::getConstantValues('notification_channel', 'push,email,sms');
        return ['string', 'in:' . implode(',', $allowed)];
    }

    // ------------------- Card -------------------
    public static function cardStatus(): array
    {
        // يمكن جعلها ديناميكية أو تركها ثابتة
        return ['string', 'in:active,inactive,blocked,expired'];
    }

    // ------------------- Currency -------------------
    public static function currency(): array
    {
        $allowed = self::getConfigRepo()->getValue('currency', 'supported') ?? ['USD', 'EUR', 'EGP'];
        return ['string', 'in:' . implode(',', $allowed)];
    }

    // ------------------- Generic (for any config group/key) -------------------
    public static function configValues(string $group, string $key, string $default = ''): array
    {
        $allowed = self::getConfigRepo()->getValue($group, $key) ?? explode(',', $default);
        return ['string', 'in:' . implode(',', $allowed)];
    }
}