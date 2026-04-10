<?php

namespace App\Services\DeviceAndCard;

use App\Contracts\Repositories\NfcDeviceRepositoryInterface;
use App\Contracts\Repositories\PhysicalDeviceDetailRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Traits\AuditableTrait;
use App\Traits\ConfigurableTrait;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class PhysicalDeviceService
{
    use AuditableTrait, ConfigurableTrait;

    public function __construct(
        protected NfcDeviceRepositoryInterface $nfcDeviceRepo,
        protected PhysicalDeviceDetailRepositoryInterface $physicalDetailRepo,
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    // ------------------- تنفيذ دوال الـ Traits -------------------
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    // ------------------- إنشاء جهاز فيزيائي -------------------
    public function registerPhysicalDevice(int $userId, array $data): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw ValidationException::withMessages(['user' => 'User not found.']);
        }

        // التحقق من أن نوع الجهاز هو physical
        $physicalType = $this->getDeviceTypePhysical();
        if (($data['device_type'] ?? '') !== $physicalType) {
            throw ValidationException::withMessages(['device_type' => 'Device type must be physical.']);
        }

        // التحقق من عدم وجود جهاز بنفس UUID
        if ($this->nfcDeviceRepo->existsByUuid($data['device_uuid'])) {
            throw ValidationException::withMessages(['device_uuid' => 'Device UUID already exists.']);
        }

        return DB::transaction(function () use ($userId, $data) {
            // إنشاء سجل الجهاز الأساسي
            $deviceData = [
                'user_id'     => $userId,
                'device_uuid' => $data['device_uuid'],
                'device_type' => $data['device_type'],
                'status'      => $data['status'] ?? 'active',
            ];
            $device = $this->nfcDeviceRepo->create($userId, $deviceData);

            // إنشاء تفاصيل الجهاز الفيزيائي
            $detailData = [
                'serial_number' => $data['serial_number'] ?? null,
                'manufacturer'  => $data['manufacturer'] ?? null,
                'model'         => $data['model'] ?? null,
                'firmware_version' => $data['firmware_version'] ?? null,
                'hardware_version'  => $data['hardware_version'] ?? null,
                'battery_level'     => $data['battery_level'] ?? null,
            ];
            $detail = $this->physicalDetailRepo->create($device->id, $detailData);

            $this->logAudit(
                'physical_device_registered',
                'nfc_device',
                $device->id,
                $userId,
                null,
                array_merge($device->toArray(), $detail->toArray())
            );

            return [
                'device'  => $device->toArray(),
                'details' => $detail->toArray(),
            ];
        });
    }

    // ------------------- استعلامات -------------------
    public function getPhysicalDevice(int $deviceId, array $with = []): ?array
    {
        $device = $this->nfcDeviceRepo->findById($deviceId, $with);
        if (!$device || !$this->nfcDeviceRepo->isPhysical($deviceId)) {
            return null;
        }
        return $device->toArray();
    }

    public function getPhysicalDeviceByUuid(string $uuid, array $with = []): ?array
    {
        $device = $this->nfcDeviceRepo->getByUuid($uuid, $with);
        if (!$device || !$this->nfcDeviceRepo->isPhysical($device->id)) {
            return null;
        }
        return $device->toArray();
    }

    public function getUserPhysicalDevices(int $userId, array $with = []): array
    {
        $devices = $this->nfcDeviceRepo->getByUserId($userId, $with);
        $physicalDevices = $devices->filter(fn($d) => $this->nfcDeviceRepo->isPhysical($d->id));
        return $physicalDevices->toArray();
    }

    // ------------------- تحديث حالة الجهاز -------------------
    public function updateDeviceStatus(int $deviceId, string $status): bool
    {
        $device = $this->nfcDeviceRepo->findById($deviceId);
        if (!$device || !$this->nfcDeviceRepo->isPhysical($deviceId)) {
            throw ValidationException::withMessages(['device' => 'Physical device not found.']);
        }

        $allowedStatuses = ['active', 'inactive', 'blocked'];
        if (!in_array($status, $allowedStatuses)) {
            throw ValidationException::withMessages(['status' => 'Invalid status.']);
        }

        $oldStatus = $device->status;
        $updated = $this->nfcDeviceRepo->updateStatus($deviceId, $status);

        if ($updated) {
            $this->logAudit(
                'physical_device_status_updated',
                'nfc_device',
                $deviceId,
                $device->user_id,
                ['status' => $oldStatus],
                ['status' => $status]
            );
        }
        return $updated;
    }

    // ------------------- تحديث تفاصيل الجهاز -------------------
    public function updateDeviceDetails(int $deviceId, array $data): bool
    {
        $device = $this->nfcDeviceRepo->findById($deviceId);
        if (!$device || !$this->nfcDeviceRepo->isPhysical($deviceId)) {
            throw ValidationException::withMessages(['device' => 'Physical device not found.']);
        }

        $allowedFields = [
            'serial_number',
            'manufacturer',
            'model',
            'firmware_version',
            'hardware_version',
            'battery_level',
        ];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            throw ValidationException::withMessages(['update' => 'No valid fields to update.']);
        }

        $oldDetail = $this->physicalDetailRepo->getByDeviceId($deviceId);
        $updated = $this->physicalDetailRepo->update($deviceId, $updateData);

        if ($updated) {
            $this->logAudit(
                'physical_device_details_updated',
                'physical_device_detail',
                $deviceId,
                $device->user_id,
                $oldDetail?->toArray(),
                $updateData
            );
        }
        return $updated;
    }

    // ------------------- حذف الجهاز -------------------
    public function deletePhysicalDevice(int $deviceId): bool
    {
        $device = $this->nfcDeviceRepo->findById($deviceId);
        if (!$device || !$this->nfcDeviceRepo->isPhysical($deviceId)) {
            throw ValidationException::withMessages(['device' => 'Physical device not found.']);
        }

        return DB::transaction(function () use ($device) {
            // حذف التفاصيل أولاً
            $this->physicalDetailRepo->delete($device->id);
            // حذف الجهاز
            $deleted = $this->nfcDeviceRepo->delete($device->id);

            if ($deleted) {
                $this->logAudit(
                    'physical_device_deleted',
                    'nfc_device',
                    $device->id,
                    $device->user_id,
                    $device->toArray(),
                    null
                );
            }
            return $deleted;
        });
    }

    // ------------------- دوال مساعدة -------------------
    protected function getDeviceTypePhysical(): string
    {
        return $this->configRepo->getValue('constant', 'device_type.physical') ?? 'physical';
    }
}