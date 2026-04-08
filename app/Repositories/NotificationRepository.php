<?php

namespace App\Repositories;

use App\Models\Notification;
use App\Contracts\Repositories\NotificationRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NotificationRepository implements NotificationRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- Helpers -------------------
    protected function getNotificationTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "notification_type.{$typeKey}");
    }

    protected function getNotificationChannelConstant(string $channelKey): ?string
    {
        return $this->configRepo->getValue('constant', "notification_channel.{$channelKey}");
    }

    // ------------------- Retrieval -------------------
    public function findById(int $id, array $with = []): ?Notification
    {
        return Notification::with($with)->find($id);
    }

    public function getByUserId(int $userId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return Notification::with($with)->where('user_id', $userId)->paginate($perPage);
    }

    public function getUnreadByUserId(int $userId, array $with = []): Collection
    {
        return Notification::with($with)
            ->where('user_id', $userId)
            ->where('is_read', false)
            ->get();
    }

    public function getByType(int $userId, string $type, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return Notification::with($with)
            ->where('user_id', $userId)
            ->where('type', $type)
            ->paginate($perPage);
    }

    public function countUnread(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->count();
    }

    // ------------------- Write -------------------
    public function create(int $userId, string $type, string $title, string $message, string $channel = 'push', ?array $data = null): Notification
    {
        $defaultChannel = $this->getNotificationChannelConstant('push') ?? 'push';
        $finalChannel = $channel ?: $defaultChannel;

        return Notification::create([
            'user_id'  => $userId,
            'type'     => $type,
            'title'    => $title,
            'message'  => $message,
            'channel'  => $finalChannel,
            'is_read'  => false,
            'data'     => $data,
        ]);
    }

    public function markAsRead(int $id): bool
    {
        $notification = $this->findById($id);
        if (!$notification) {
            return false;
        }
        return $notification->update(['is_read' => true]);
    }

    public function markAllAsRead(int $userId): int
    {
        return Notification::where('user_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
    }

    public function delete(int $id): bool
    {
        $notification = $this->findById($id);
        if (!$notification) {
            return false;
        }
        return (bool) $notification->delete();
    }

    public function deleteAllByUserId(int $userId): int
    {
        return Notification::where('user_id', $userId)->delete();
    }
}