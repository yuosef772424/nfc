<?php

namespace App\Services\DeviceAndCard;

use App\Contracts\Repositories\NfcDeviceRepositoryInterface;
use App\Contracts\Repositories\MobileDeviceDetailRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Traits\AuditableTrait;
use App\Traits\ConfigurableTrait;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\DB;

class MobileDeviceService
{
    use AuditableTrait, ConfigurableTrait;

    public function __construct(
        protected NfcDeviceRepositoryInterface $nfcDeviceRepo,
        protected MobileDeviceDetailRepositoryInterface $mobileDetailRepo,
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    // ------------------- تنفيذ دوال الـ Traits -------------------
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    // ------------------- إنشاء جهاز موبايل -------------------
    public function registerMobileDevice(int $userId, array $data): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw ValidationException::withMessages(['user' => 'User not found.']);
        }

        // التحقق من أن نوع الجهاز هو mobile
        $mobileType = $this->getDeviceTypeMobile();
        if (($data['device_type'] ?? '') !== $mobileType) {
            throw ValidationException::withMessages(['device_type' => 'Device type must be mobile.']);
        }

        // التحقق من عدم وجود جهاز بنفس UUID
        if ($this->nfcDeviceRepo->existsByUuid($data['device_uuid'])) {
            throw ValidationException::withMessages(['device_uuid' => 'Device UUID already exists.']);
        }

        // التحقق من عدم وجود بصمة جهاز مكررة
        if ($this->mobileDetailRepo->getByFingerprint($data['device_fingerprint'])) {
            throw ValidationException::withMessages(['device_fingerprint' => 'Device fingerprint already registered.']);
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

            // إنشاء تفاصيل الموبايل
            $detailData = [
                'device_fingerprint' => $data['device_fingerprint'],
                'nfc_supported'      => $data['nfc_supported'],
                'biometric_type'     => $data['biometric_type'],
                'os_version'         => $data['os_version'] ?? null,
                'app_version'        => $data['app_version'] ?? null,
            ];
            $detail = $this->mobileDetailRepo->create($device->id, $detailData);

            $this->logAudit(
                'mobile_device_registered',
                'nfc_device',
                $device->id,
                $userId,
                null,
                array_merge($device->toArray(), $detail->toArray())
            );

            return [
                'device' => $device->toArray(),
                'details' => $detail->toArray(),
            ];
        });
    }

    // ------------------- استعلامات -------------------
    public function getMobileDevice(int $deviceId, array $with = []): ?array
    {
        $device = $this->nfcDeviceRepo->findById($deviceId, $with);
        if (!$device || !$this->nfcDeviceRepo->isMobile($deviceId)) {
            return null;
        }
        return $device->toArray();
    }

    public function getMobileDeviceByUuid(string $uuid, array $with = []): ?array
    {
        $device = $this->nfcDeviceRepo->getByUuid($uuid, $with);
        if (!$device || !$this->nfcDeviceRepo->isMobile($device->id)) {
            return null;
        }
        return $device->toArray();
    }

    public function getUserMobileDevices(int $userId, array $with = []): array
    {
        $devices = $this->nfcDeviceRepo->getByUserId($userId, $with);
        $mobileDevices = $devices->filter(fn($d) => $this->nfcDeviceRepo->isMobile($d->id));
        return $mobileDevices->toArray();
    }

    // ------------------- تحديث حالة الجهاز -------------------
    public function updateDeviceStatus(int $deviceId, string $status): bool
    {
        $device = $this->nfcDeviceRepo->findById($deviceId);
        if (!$device || !$this->nfcDeviceRepo->isMobile($deviceId)) {
            throw ValidationException::withMessages(['device' => 'Mobile device not found.']);
        }

        $allowedStatuses = ['active', 'inactive', 'blocked'];
        if (!in_array($status, $allowedStatuses)) {
            throw ValidationException::withMessages(['status' => 'Invalid status.']);
        }

        $oldStatus = $device->status;
        $updated = $this->nfcDeviceRepo->updateStatus($deviceId, $status);

        if ($updated) {
            $this->logAudit(
                'mobile_device_status_updated',
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
        if (!$device || !$this->nfcDeviceRepo->isMobile($deviceId)) {
            throw ValidationException::withMessages(['device' => 'Mobile device not found.']);
        }

        $allowedFields = ['nfc_supported', 'biometric_type', 'os_version', 'app_version'];
        $updateData = array_intersect_key($data, array_flip($allowedFields));

        if (empty($updateData)) {
            throw ValidationException::withMessages(['update' => 'No valid fields to update.']);
        }

        $oldDetail = $this->mobileDetailRepo->getByDeviceId($deviceId);
        $updated = $this->mobileDetailRepo->update($deviceId, $updateData);

        if ($updated) {
            $this->logAudit(
                'mobile_device_details_updated',
                'mobile_device_detail',
                $deviceId,
                $device->user_id,
                $oldDetail?->toArray(),
                $updateData
            );
        }
        return $updated;
    }

    // ------------------- التحقق من دعم NFC -------------------
    public function hasNfcSupport(int $deviceId): bool
    {
        return $this->mobileDetailRepo->hasNfc($deviceId);
    }

    // ------------------- حذف الجهاز -------------------
    public function deleteMobileDevice(int $deviceId): bool
    {
        $device = $this->nfcDeviceRepo->findById($deviceId);
        if (!$device || !$this->nfcDeviceRepo->isMobile($deviceId)) {
            throw ValidationException::withMessages(['device' => 'Mobile device not found.']);
        }

        return DB::transaction(function () use ($device) {
            // حذف التفاصيل أولاً
            $this->mobileDetailRepo->delete($device->id);
            // حذف الجهاز
            $deleted = $this->nfcDeviceRepo->delete($device->id);

            if ($deleted) {
                $this->logAudit(
                    'mobile_device_deleted',
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
    protected function getDeviceTypeMobile(): string
    {
        return $this->configRepo->getValue('constant', 'device_type.mobile') ?? 'mobile';
    }
}