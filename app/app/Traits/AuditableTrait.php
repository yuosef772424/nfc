<?php

namespace App\Traits;

use App\Contracts\Repositories\AuditLogRepositoryInterface;

trait AuditableTrait
{
    /**
     * تسجيل حدث في AuditLog.
     */
    protected function logAudit(
        string $action,
        string $entity,
        int $entityId,
        ?int $userId = null,
        ?array $oldData = null,
        ?array $newData = null
    ): void {
        $this->getAuditLogRepo()->create(
            action: $action,
            entity: $entity,
            entityId: $entityId,
            userId: $userId ?? auth()->id(),
            ipAddress: request()->ip(),
            oldData: $oldData,
            newData: $newData
        );
    }

    /**
     * يجب أن توفر الخدمة التي تستخدم الـ Trait هذه الدالة.
     * @return AuditLogRepositoryInterface
     */
    abstract protected function getAuditLogRepo(): AuditLogRepositoryInterface;
}