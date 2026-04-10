<?php

namespace App\Services\System;

use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Traits\AuditableTrait;
use Illuminate\Validation\ValidationException;

class AppConfigService
{
    use AuditableTrait;

    public function __construct(
        protected AppConfigRepositoryInterface $configRepo,
        protected AuditLogRepositoryInterface $auditLogRepo
    ) {}

    protected function getAuditLogRepo(): AuditLogRepositoryInterface
    {
        return $this->auditLogRepo;
    }

    /**
     * الحصول على قيمة إعداد محددة.
     */
    public function get(string $group, string $key, array $metaFilters = []): mixed
    {
        return $this->configRepo->getValue($group, $key, $metaFilters);
    }

    /**
     * الحصول على مجموعة كاملة من الإعدادات.
     */
    public function getGroup(string $group, array $metaFilters = []): array
    {
        return $this->configRepo->getGroup($group, $metaFilters)->toArray();
    }

    /**
     * الحصول على جميع الإعدادات مجمعة.
     */
    public function getAllGrouped(): array
    {
        return $this->configRepo->getAllGrouped();
    }

    /**
     * إنشاء أو تحديث إعداد.
     */
    public function set(string $group, string $key, mixed $value, array $meta = [], ?string $label = null): array
    {
        // التحقق من صحة المفتاح والمجموعة
        if (empty(trim($group)) || empty(trim($key))) {
            throw ValidationException::withMessages(['config' => 'Group and key are required.']);
        }

        // الحصول على القيمة القديمة (إن وجدت) لتسجيل التدقيق
        $oldValue = $this->configRepo->getValue($group, $key, $meta);

        $config = $this->configRepo->set($group, $key, $value, $meta);

        // تحديث التصنيف إذا تم توفيره
        if ($label !== null) {
            $config->update(['label' => $label]);
        }

        $this->logAudit(
            action: 'config_updated',
            entity: 'app_config',
            entityId: $config->id,
            userId: auth()->id(),
            oldData: ['value' => $oldValue],
            newData: ['group' => $group, 'key' => $key, 'value' => $value]
        );

        return $config->toArray();
    }

    /**
     * حذف إعداد (إخفاء بدلاً من الحذف الفعلي).
     */
    public function deactivate(string $group, string $key): bool
    {
        $config = \App\Models\AppConfig::where('group', $group)->where('key', $key)->first();
        if (!$config) {
            throw ValidationException::withMessages(['config' => 'Configuration not found.']);
        }

        $updated = $config->update(['is_active' => false]);
        if ($updated) {
            $this->configRepo->clearCache();
            $this->logAudit(
                action: 'config_deactivated',
                entity: 'app_config',
                entityId: $config->id,
                userId: auth()->id()
            );
        }
        return $updated;
    }

    /**
     * إعادة تفعيل إعداد.
     */
    public function activate(string $group, string $key): bool
    {
        $config = \App\Models\AppConfig::where('group', $group)->where('key', $key)->first();
        if (!$config) {
            throw ValidationException::withMessages(['config' => 'Configuration not found.']);
        }

        $updated = $config->update(['is_active' => true]);
        if ($updated) {
            $this->configRepo->clearCache();
            $this->logAudit(
                action: 'config_activated',
                entity: 'app_config',
                entityId: $config->id,
                userId: auth()->id()
            );
        }
        return $updated;
    }

    /**
     * مسح الكاش يدوياً.
     */
    public function clearCache(): void
    {
        $this->configRepo->clearCache();
        $this->logAudit(
            action: 'config_cache_cleared',
            entity: 'app_config',
            entityId: 0,
            userId: auth()->id()
        );
    }
}