<?php

namespace App\Repositories;

use App\Models\AuditLog;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class AuditLogRepository implements AuditLogRepositoryInterface
{
    /**
     * Create a single audit log entry
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
            'old_data'   => $oldData,
            'new_data'   => $newData,
            'ip_address' => $ipAddress,
        ]);
    }

    // ========== RETRIEVAL ==========
    public function findById(int $id, array $with = []): ?AuditLog
    {
        return AuditLog::with($with)->find($id);
    }

    public function getByUserId(int $userId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return AuditLog::with($with)
            ->where('user_id', $userId)
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function getByEntity(string $entity, int $entityId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return AuditLog::with($with)
            ->where('entity', $entity)
            ->where('entity_id', $entityId)
            ->latest('created_at')
            ->paginate($perPage);
    }

    public function getByAction(string $action, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return AuditLog::with($with)
            ->where('action', $action)
            ->latest('created_at')
            ->paginate($perPage);
    }

    /**
     * Get all audit logs with optional filters and eager loading.
     */
    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = AuditLog::with($with);

        if (!empty($filters['user_id'])) {
            $query->where('user_id', $filters['user_id']);
        }
        if (!empty($filters['action'])) {
            $query->where('action', $filters['action']);
        }
        if (!empty($filters['entity'])) {
            $query->where('entity', $filters['entity']);
        }
        if (!empty($filters['entity_id'])) {
            $query->where('entity_id', $filters['entity_id']);
        }

        return $query->latest('created_at')->paginate($perPage);
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