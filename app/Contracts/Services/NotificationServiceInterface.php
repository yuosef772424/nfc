<?php

namespace App\Contracts\Services;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotificationServiceInterface
{
    // ---------------------------------------------------------------
    // Sending
    // ---------------------------------------------------------------

    /**
     * إرسال إشعار لمستخدم واحد عبر كل قنواته النشطة
     */
    public function send(int $userId, string $type, string $title, string $message, array $data = []): Notification;

    /**
     * إرسال إشعار push فقط (FCM / APNs)
     */
    public function sendPush(int $userId, string $title, string $message, array $data = []): bool;

    /**
     * إرسال SMS
     */
    public function sendSms(int $userId, string $message): bool;

    /**
     * إرسال إيميل
     */
    public function sendEmail(int $userId, string $subject, string $message, array $data = []): bool;

    /**
     * إرسال إشعار لعدة مستخدمين دفعة واحدة (Bulk)
     *
     * @param array $userIds
     */
    public function sendBulk(array $userIds, string $type, string $title, string $message, array $data = []): int;

    // ---------------------------------------------------------------
    // Transaction Notifications (pre-built)
    // ---------------------------------------------------------------

    public function notifyTransactionSent(int $userId, float $amount, string $transactionUuid): void;

    public function notifyTransactionReceived(int $userId, float $amount, string $transactionUuid): void;

    public function notifyTransactionFailed(int $userId, string $reason, string $transactionUuid): void;

    public function notifyWithdrawalCode(int $userId, string $code, float $amount): void;

    public function notifyWithdrawalCompleted(int $userId, float $amount): void;

    // ---------------------------------------------------------------
    // Management
    // ---------------------------------------------------------------

    public function getByUser(int $userId, int $perPage = 20): LengthAwarePaginator;

    public function getUnread(int $userId): Collection;

    public function countUnread(int $userId): int;

    public function markAsRead(int $notificationId): bool;

    public function markAllAsRead(int $userId): int;

    public function delete(int $notificationId): bool;

    public function deleteAllByUser(int $userId): int;

    // ---------------------------------------------------------------
    // Push Token Management
    // ---------------------------------------------------------------

    public function registerPushToken(int $userId, string $token, string $platform, ?int $deviceId = null): bool;

    public function revokePushToken(string $token): bool;

    public function revokeAllPushTokens(int $userId): int;
}
