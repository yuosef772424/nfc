<?php

namespace App\Contracts\Services;

use App\Models\NfcDevice;
use App\Models\PhysicalDeviceDetail;
use App\Models\MobileDeviceDetail;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface NfcDeviceServiceInterface
{
    // ---------------------------------------------------------------
    // Registration
    // ---------------------------------------------------------------

    /**
     * تسجيل جهاز مادي (Raspberry Pi / POS)
     * يُنشئ nfc_devices + physical_device_details
     *
     * @throws \App\Exceptions\Device\DeviceAlreadyExistsException
     */
    public function registerPhysicalDevice(int $userId, array $deviceData, array $physicalDetails): NfcDevice;

    /**
     * تسجيل جهاز محمول
     * يُنشئ nfc_devices + mobile_device_details + device_push_token
     *
     * @throws \App\Exceptions\Device\DeviceAlreadyExistsException
     */
    public function registerMobileDevice(int $userId, array $deviceData, array $mobileDetails, ?string $pushToken = null): NfcDevice;

    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function getById(int $id): ?NfcDevice;

    public function getByUserId(int $userId): \Illuminate\Database\Eloquent\Collection;

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    // ---------------------------------------------------------------
    // Status Management
    // ---------------------------------------------------------------

    public function activate(int $deviceId): bool;

    public function deactivate(int $deviceId): bool;

    public function setMaintenance(int $deviceId): bool;

    // ---------------------------------------------------------------
    // Details Update
    // ---------------------------------------------------------------

    public function updatePhysicalDetails(int $deviceId, array $data): PhysicalDeviceDetail;

    public function updateMobileDetails(int $deviceId, array $data): MobileDeviceDetail;

    public function updatePushToken(int $deviceId, string $token, string $platform): bool;

    // ---------------------------------------------------------------
    // Validation
    // ---------------------------------------------------------------

    /**
     * التحقق من أن الجهاز صالح للاستخدام في معاملة
     *
     * @throws \App\Exceptions\Device\DeviceInactiveException
     * @throws \App\Exceptions\Device\DeviceNotFoundException
     */
    public function validateForTransaction(int $deviceId): bool;

    /**
     * التحقق من أن الجهاز المحمول يدعم NFC
     *
     * @throws \App\Exceptions\Device\NfcNotSupportedException
     */
    public function assertNfcSupported(int $deviceId): bool;
}
