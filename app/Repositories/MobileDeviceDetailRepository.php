<?php

namespace App\Repositories;

use App\Models\MobileDeviceDetail;
use App\Contracts\Repositories\MobileDeviceDetailRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;

class MobileDeviceDetailRepository implements MobileDeviceDetailRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- دوال مساعدة لقراءة الثوابت من app_config -------------------
    protected function getBiometricTypeConstant(string $typeKey): ?string
    {
        return $this->configRepo->getValue('constant', "biometric_type.{$typeKey}");
    }

    // ------------------- Retrieval -------------------
    public function findByDeviceId(int $deviceId): ?MobileDeviceDetail
    {
        return MobileDeviceDetail::find($deviceId);
    }

    public function getByFingerprint(string $fingerprint): ?MobileDeviceDetail
    {
        return MobileDeviceDetail::where('device_fingerprint', $fingerprint)->first();
    }

    public function hasNfc(int $deviceId): bool
    {
        $detail = $this->findByDeviceId($deviceId);
        return $detail && $detail->nfc_supported === true;
    }

    public function hasBiometric(int $deviceId): bool
    {
        $detail = $this->findByDeviceId($deviceId);
        if (!$detail) return false;
        $noneType = $this->getBiometricTypeConstant('none') ?? 'none';
        return $detail->biometric_type !== $noneType;
    }

    // ------------------- Write -------------------
    public function create(int $deviceId, array $data): MobileDeviceDetail
    {
        $data['device_id'] = $deviceId;
        return MobileDeviceDetail::create($data);
    }

    public function update(int $deviceId, array $data): bool
    {
        $detail = $this->findByDeviceId($deviceId);
        if (!$detail) return false;
        return $detail->update($data);
    }

    public function updateNfcStatus(int $deviceId, bool $nfcEnabled): bool
    {
        return $this->update($deviceId, ['nfc_supported' => $nfcEnabled]);
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
        return MobileDeviceDetail::where('device_id', $deviceId)->exists();
    }
}