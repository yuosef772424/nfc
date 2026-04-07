<?php

namespace App\Contracts\Repositories;

use App\Models\MobileDeviceDetail;
use Illuminate\Database\Eloquent\Collection;

interface MobileDeviceDetailRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function findByDeviceId(int $deviceId): ?MobileDeviceDetail;

    public function getByFingerprint(string $fingerprint): ?MobileDeviceDetail;


    public function hasNfc(int $deviceId): bool;

    public function hasBiometric(int $deviceId): bool;

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------

    public function create(int $deviceId, array $data): MobileDeviceDetail;

    public function update(int $deviceId, array $data): bool;

    public function updateNfcStatus(int $deviceId, bool $nfcEnabled): bool;

    public function delete(int $deviceId): bool;

    // ---------------------------------------------------------------
    // Checks
    // ---------------------------------------------------------------

    public function exists(int $deviceId): bool;
}