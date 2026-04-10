<?php

namespace App\Http\Requests;

use App\Rules\ValidationRules;

class DeviceAndCardRequests
{
    // ==================== Card ====================
    public static function storeCard(): array
    {
        return [
            'wallet_id' => 'required|integer|exists:wallets,id',
            'agent_id' => 'nullable|integer|exists:users,id',
            'card_number' => 'required|string|unique:cards,card_number',
            'nfc_uid' => 'required|string|unique:cards,nfc_uid',
            'status' => ValidationRules::cardStatus(),
            'expiry_date' => 'required|date|after:today',
        ];
    }

    public static function updateCard(): array
    {
        return [
            'status' => ValidationRules::cardStatus(),
            'expiry_date' => 'sometimes|date|after:today',
        ];
    }

    public static function updateCardPin(): array
    {
        return [
            'pin' => 'required|string|min:4|max:8|confirmed',
        ];
    }

    // ==================== NfcDevice ====================
    public static function storeNfcDevice(): array
    {
        return [
            'user_id' => 'required|integer|exists:users,id',
            'device_uuid' => 'required|string|unique:nfc_devices,device_uuid',
            'device_type' => ValidationRules::deviceType(),
            'status' => ValidationRules::deviceStatus(),
        ];
    }

    public static function updateNfcDevice(): array
    {
        return [
            'device_type' => ValidationRules::deviceType(),
            'status' => ValidationRules::deviceStatus(),
        ];
    }

    // ==================== PhysicalDeviceDetail ====================
    public static function storePhysicalDeviceDetail(): array
    {
        return [
            'device_id' => 'required|integer|exists:nfc_devices,id|unique:physical_device_details,device_id',
            'serial_number' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
        ];
    }

    public static function updatePhysicalDeviceDetail(): array
    {
        return [
            'serial_number' => 'nullable|string|max:100',
            'manufacturer' => 'nullable|string|max:100',
            'model' => 'nullable|string|max:100',
        ];
    }

    // ==================== MobileDeviceDetail ====================
  public static function storeMobileDeviceDetail(): array
{
    return [
        'device_id' => 'required|integer|exists:nfc_devices,id|unique:mobile_device_details,device_id',
        'device_fingerprint' => 'required|string|unique:mobile_device_details,device_fingerprint',
        'nfc_supported' => 'required|boolean',
        'biometric_type' => ValidationRules::biometricType(),
        'os_version' => 'nullable|string|max:50',
        'app_version' => 'nullable|string|max:20',
    ];
}
    public static function updateMobileDeviceDetail(): array
    {
        return [
            'nfc_supported' => 'sometimes|boolean',
            'biometric_type' => ValidationRules::biometricType(),
        ];
    }
}