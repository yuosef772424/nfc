<?php

namespace App\Services\System;

use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Events\NotificationCreated;
use App\Traits\AuditableTrait;
use App\Traits\ConfigurableTrait;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Validation\ValidationException;

class NotificationService
{
    use AuditableTrait, ConfigurableTrait;

    public function __construct(
        protected NotificationRepositoryInterface $notificationRepo,
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    // ------------------- إنشاء وإرسال إشعار -------------------
    public function send(
        int $userId,
        string $type,
        string $title,
        string $message,
        string $channel = 'push',
        ?array $data = null
    ): array {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw ValidationException::withMessages(['user' => 'User not found.']);
        }

        // التحقق من صحة القناة
        $allowedChannels = ['push', 'email', 'sms'];
        if (!in_array($channel, $allowedChannels)) {
            $channel = 'push';
        }

        $notification = $this->notificationRepo->create(
            userId: $userId,
            type: $type,
            title: $title,
            message: $message,
            channel: $channel,
            data: $data
        );

        // تشغيل حدث لإرسال الإشعار عبر القناة المناسبة (بشكل غير متزامن)
        event(new NotificationCreated($notification));

        $this->logAudit(
            'notification_sent',
            'notification',
            $notification->id,
            $userId,
            null,
            ['type' => $type, 'channel' => $channel, 'title' => $title]
        );

        return $notification->toArray();
    }

    // ------------------- إرسال إشعار لعدة مستخدمين -------------------
    public function sendToMany(
        array $userIds,
        string $type,
        string $title,
        string $message,
        string $channel = 'push',
        ?array $data = null
    ): array {
        $notifications = [];
        foreach ($userIds as $userId) {
            try {
                $notifications[] = $this->send($userId, $type, $title, $message, $channel, $data);
            } catch (\Exception $e) {
                \Log::error("Failed to send notification to user {$userId}: " . $e->getMessage());
            }
        }
        return $notifications;
    }

    // ------------------- استعلامات المستخدم -------------------
    public function getUserNotifications(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return $this->notificationRepo->getByUserId($userId, $perPage);
    }

    public function getUnreadNotifications(int $userId): array
    {
        return $this->notificationRepo->getUnreadByUserId($userId)->toArray();
    }

    public function countUnread(int $userId): int
    {
        return $this->notificationRepo->countUnread($userId);
    }

    // ------------------- تحديث حالة القراءة -------------------
    public function markAsRead(int $notificationId, int $userId): bool
    {
        $notification = $this->notificationRepo->findById($notificationId);
        if (!$notification || $notification->user_id !== $userId) {
            throw ValidationException::withMessages(['notification' => 'Notification not found or access denied.']);
        }

        $updated = $this->notificationRepo->markAsRead($notificationId);
        if ($updated) {
            $this->logAudit('notification_read', 'notification', $notificationId, $userId);
        }
        return $updated;
    }

    public function markAllAsRead(int $userId): int
    {
        $count = $this->notificationRepo->markAllAsRead($userId);
        if ($count > 0) {
            $this->logAudit('notifications_marked_read', 'notification', 0, $userId, null, ['count' => $count]);
        }
        return $count;
    }

    // ------------------- حذف -------------------
    public function deleteNotification(int $notificationId, int $userId): bool
    {
        $notification = $this->notificationRepo->findById($notificationId);
        if (!$notification || $notification->user_id !== $userId) {
            throw ValidationException::withMessages(['notification' => 'Notification not found or access denied.']);
        }

        $deleted = $this->notificationRepo->delete($notificationId);
        if ($deleted) {
            $this->logAudit('notification_deleted', 'notification', $notificationId, $userId);
        }
        return $deleted;
    }

    public function deleteAllForUser(int $userId): int
    {
        $count = $this->notificationRepo->deleteAllByUserId($userId);
        if ($count > 0) {
            $this->logAudit('notifications_cleared', 'notification', 0, $userId, null, ['count' => $count]);
        }
        return $count;
    }

    // ------------------- دوال مساعدة للإرسال المباشر من الخدمات الأخرى -------------------
    public function notifyPaymentReceived(int $userId, float $amount, string $from): void
    {
        $this->send(
            $userId,
            'payment_received',
            'Payment Received',
            "You have received {$amount} from {$from}.",
            'push',
            ['amount' => $amount, 'from' => $from]
        );
    }

    public function notifyWithdrawalCompleted(int $userId, float $amount): void
    {
        $this->send(
            $userId,
            'withdrawal_completed',
            'Withdrawal Successful',
            "Your withdrawal of {$amount} has been processed.",
            'push',
            ['amount' => $amount]
        );
    }

    public function notifyKycApproved(int $userId): void
    {
        $this->send(
            $userId,
            'kyc_approved',
            'KYC Approved',
            'Your identity verification has been approved.',
            'push'
        );
    }
}