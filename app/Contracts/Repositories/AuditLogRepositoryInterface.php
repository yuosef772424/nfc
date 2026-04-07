<?php

namespace App\Contracts\Repositories;

use App\Models\AuditLog;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface AuditLogRepositoryInterface
{
    // Retrieval
    public function findById(int $id): ?AuditLog;
    public function getByUserId(int $userId, int $perPage = 20): LengthAwarePaginator;
    public function getByEntity(string $entity, int $entityId, int $perPage = 20): LengthAwarePaginator;
    public function getByAction(string $action, int $perPage = 20): LengthAwarePaginator;
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    // Write
    public function log(
        string $action,
        string $entity,
        int $entityId,
        ?array $oldData = null,
        ?array $newData = null,
        ?int $userId = null,
        ?string $ipAddress = null
    ): AuditLog;

    /** حذف السجلات القديمة */
    public function deleteOlderThan(\DateTimeInterface $date): int;


}