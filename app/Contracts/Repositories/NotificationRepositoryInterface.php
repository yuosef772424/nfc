<?php

namespace App\Contracts\Repositories;

use App\Models\Notification;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NotificationRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function findById(int $id): ?Notification;

    public function getByUserId(int $userId, int $perPage = 20): LengthAwarePaginator;

    public function getUnreadByUserId(int $userId): Collection;   // تعوض scopeUnread

    public function getByType(int $userId, string $type, int $perPage = 20): LengthAwarePaginator; // تعوض scopeOfType

    public function countUnread(int $userId): int;

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------

    public function create(int $userId, string $type, string $title, string $message, string $channel = 'push', ?array $data = null): Notification;

    public function markAsRead(int $id): bool;                    // كانت markAsRead في الموديل

    public function markAllAsRead(int $userId): int;

    public function delete(int $id): bool;

    public function deleteAllByUserId(int $userId): int;
}