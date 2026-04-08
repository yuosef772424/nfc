<?php

namespace App\Repositories;

use App\Models\NfcDevice;
use App\Models\PhysicalDeviceDetail;
use App\Models\MobileDeviceDetail;
use App\Contracts\Repositories\NfcDeviceRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class NfcDeviceRepository implements NfcDeviceRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- Helpers -------------------
    protected function getDeviceTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "device_type.{$typeKey}");
    }

    protected function getDeviceStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "device_status.{$statusKey}");
    }

    // ------------------- Retrieval -------------------
    public function getByUuid(string $uuid, array $with = []): ?NfcDevice
    {
        return NfcDevice::with($with)->where('device_uuid', $uuid)->first();
    }

    public function findById(int $id, array $with = []): ?NfcDevice
    {
        return NfcDevice::with($with)->find($id);
    }

    public function getByUserId(int $userId, array $with = []): Collection
    {
        return NfcDevice::with($with)->where('user_id', $userId)->get();
    }

    public function getByType(string $deviceType, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return NfcDevice::with($with)->where('device_type', $deviceType)->paginate($perPage);
    }

    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = NfcDevice::with($with);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['device_type'])) {
            $query->where('device_type', $filters['device_type']);
        }

        return $query->paginate($perPage);
    }

    public function getActive(array $with = []): Collection
    {
        $activeStatus = $this->getDeviceStatusConstant('active') ?? 'active';
        return NfcDevice::with($with)->where('status', $activeStatus)->get();
    }

    public function getPhysical(array $with = []): Collection
    {
        $physicalType = $this->getDeviceTypeConstant('physical') ?? 'physical';
        return NfcDevice::with($with)->where('device_type', $physicalType)->get();
    }

    public function getMobile(array $with = []): Collection
    {
        $mobileType = $this->getDeviceTypeConstant('mobile') ?? 'mobile';
        return NfcDevice::with($with)->where('device_type', $mobileType)->get();
    }

    public function isPhysical(int $id): bool
    {
        $device = $this->findById($id);
        if (!$device) {
            return false;
        }
        $physicalType = $this->getDeviceTypeConstant('physical') ?? 'physical';
        return $device->device_type === $physicalType;
    }

    public function isMobile(int $id): bool
    {
        $device = $this->findById($id);
        if (!$device) {
            return false;
        }
        $mobileType = $this->getDeviceTypeConstant('mobile') ?? 'mobile';
        return $device->device_type === $mobileType;
    }

    public function getDetails(int $id): ?\Illuminate\Database\Eloquent\Model
    {
        $device = $this->findById($id);
        if (!$device) {
            return null;
        }

        $physicalType = $this->getDeviceTypeConstant('physical') ?? 'physical';
        if ($device->device_type === $physicalType) {
            return PhysicalDeviceDetail::where('device_id', $id)->first();
        }

        return MobileDeviceDetail::where('device_id', $id)->first();
    }

    // ------------------- Write -------------------
    public function create(int $userId, array $data): NfcDevice
    {
        $data['user_id'] = $userId;
        return NfcDevice::create($data);
    }

    public function updateStatus(int $id, string $status): bool
    {
        return $this->update($id, ['status' => $status]);
    }

    public function update(int $id, array $data): bool
    {
        $device = $this->findById($id);
        if (!$device) {
            return false;
        }
        return $device->update($data);
    }

    public function delete(int $id): bool
    {
        $device = $this->findById($id);
        if (!$device) {
            return false;
        }
        return (bool) $device->delete();
    }

    // ------------------- Checks -------------------
    public function isActive(int $id): bool
    {
        $device = $this->findById($id);
        if (!$device) {
            return false;
        }
        $activeStatus = $this->getDeviceStatusConstant('active') ?? 'active';
        return $device->status === $activeStatus;
    }

    public function existsByUuid(string $uuid): bool
    {
        return NfcDevice::where('device_uuid', $uuid)->exists();
    }
}