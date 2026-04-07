<?php

namespace App\Repositories;

use App\Models\PhysicalDeviceDetail;
use App\Contracts\Repositories\PhysicalDeviceDetailRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;

class PhysicalDeviceDetailRepository implements PhysicalDeviceDetailRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- Retrieval -------------------
    public function findByDeviceId(int $deviceId): ?PhysicalDeviceDetail
    {
        return PhysicalDeviceDetail::find($deviceId);
    }

    // ------------------- Write -------------------
    public function create(int $deviceId, array $data): PhysicalDeviceDetail
    {
        $data['device_id'] = $deviceId;
        return PhysicalDeviceDetail::create($data);
    }

    public function update(int $deviceId, array $data): bool
    {
        $detail = $this->findByDeviceId($deviceId);
        if (!$detail) return false;
        return $detail->update($data);
    }

    public function delete(int $deviceId): bool
    {
        $detail = $this->findByDeviceId($deviceId);
        if (!$detail) return false;
        return (bool) $detail->delete();
    }

    // ------------------- Checks -------------------
    public function exists(int $deviceId): bool
    {
        return PhysicalDeviceDetail::where('device_id', $deviceId)->exists();
    }
}