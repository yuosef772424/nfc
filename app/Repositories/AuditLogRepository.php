<?php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    /**
     * دالة واحدة فقط لتسجيل السجل
     */
    public function create(
        string $action,
        string $entity,
        int $entityId,
        int $userId,
        string $ipAddress,
        ?array $oldData = null,
        ?array $newData = null
    ): AuditLog {
        return AuditLog::create([
            'user_id'    => $userId,
            'action'     => $action,
            'entity'     => $entity,
            'entity_id'  => $entityId,
            'old_data'   => $oldData,    // Model سيكاسته تلقائياً
            'new_data'   => $newData,
            'ip_address' => $ipAddress,
        ]);
    }

    // ========== RETRIEVAL ==========
    public function findById(int $id): ?AuditLog
    {
        return AuditLog::find($id);
    }

    public function getByUserId(int $userId, int $perPage = 20): LengthAwarePaginator
    {
        return AuditLog::where('user_id', $userId)
            ->latest('id')
            ->paginate($perPage);
    }

    public function getByEntity(string $entity, int $entityId, int $perPage = 20): LengthAwarePaginator
    {
        return AuditLog::where('entity', $entity)
            ->where('entity_id', $entityId)
            ->latest('id')
            ->paginate($perPage);
    }

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = AuditLog::query();

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (!empty($filters['entity'])) {
            $query->where('entity', $filters['entity']);
        }

        return $query->latest('id')->paginate($perPage);
    }

    // ========== CLEANUP ==========
    public function deleteOlderThan(\DateTimeInterface $date): int
    {
        return AuditLog::where('created_at', '<', $date)->delete();
    }

    public function deleteByUserId(int $userId): int
    {
        return AuditLog::where('user_id', $userId)->delete();
    }
}