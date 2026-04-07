<?php

namespace App\Contracts\Repositories;

use App\Models\PhysicalDeviceDetail;

interface PhysicalDeviceDetailRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function findByDeviceId(int $deviceId): ?PhysicalDeviceDetail;

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------

    public function create(int $deviceId, array $data): PhysicalDeviceDetail;

    public function update(int $deviceId, array $data): bool;

    public function delete(int $deviceId): bool;

    // ---------------------------------------------------------------
    // Checks
    // ---------------------------------------------------------------

    public function exists(int $deviceId): bool;
}