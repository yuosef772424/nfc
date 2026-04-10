<?php

namespace App\Http\Controllers\Api;

use App\Services\DeviceAndCard\MobileDeviceService;
use App\Http\Requests\DeviceAndCardRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MobileDeviceController extends BaseApiController
{
    public function __construct(protected MobileDeviceService $deviceService) {}

    /**
     * تسجيل جهاز موبايل جديد للمستخدم الحالي
     */
    public function store(Request $request)
    {
        $user = $request->get('auth_user');
        $rules = DeviceAndCardRequests::storeMobileDeviceDetail();
        // نضيف device_uuid و device_type لأنها موجودة في NfcDevice
        $rules = array_merge($rules, DeviceAndCardRequests::storeNfcDevice());

        $validated = $request->validate($rules);

        try {
            $result = $this->deviceService->registerMobileDevice($user->id, $validated);
            return $this->successResponse($result, 'Mobile device registered successfully.', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * عرض جميع أجهزة الموبايل الخاصة بالمستخدم
     */
    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $devices = $this->deviceService->getUserMobileDevices($user->id);
        return $this->successResponse($devices);
    }

    /**
     * عرض جهاز محدد
     */
    public function show($id)
    {
        $device = $this->deviceService->getMobileDevice($id);
        if (!$device) {
            return $this->errorResponse('Mobile device not found.', 404);
        }
        return $this->successResponse($device);
    }

    /**
     * تحديث حالة الجهاز
     */
    public function updateStatus(Request $request, $id)
    {
        $rules = DeviceAndCardRequests::updateNfcDevice();
        $validated = $request->validate($rules);

        try {
            $this->deviceService->updateDeviceStatus($id, $validated['status']);
            return $this->successResponse(null, 'Device status updated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تحديث تفاصيل الجهاز (NFC support, biometric, etc.)
     */
    public function updateDetails(Request $request, $id)
    {
        $rules = DeviceAndCardRequests::updateMobileDeviceDetail();
        $validated = $request->validate($rules);

        try {
            $this->deviceService->updateDeviceDetails($id, $validated);
            return $this->successResponse(null, 'Device details updated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * حذف الجهاز
     */
    public function destroy($id)
    {
        try {
            $this->deviceService->deleteMobileDevice($id);
            return $this->successResponse(null, 'Device deleted.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}