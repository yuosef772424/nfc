<?php

namespace App\Http\Controllers\Api;

use App\Services\DeviceAndCard\PhysicalDeviceService;
use App\Http\Requests\DeviceAndCardRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class PhysicalDeviceController extends BaseApiController
{
    public function __construct(protected PhysicalDeviceService $deviceService) {}

    /**
     * تسجيل جهاز فيزيائي جديد للمستخدم الحالي
     */
    public function store(Request $request)
    {
        $user = $request->get('auth_user');
        $rules = DeviceAndCardRequests::storePhysicalDeviceDetail();
        $rules = array_merge($rules, DeviceAndCardRequests::storeNfcDevice());

        $validated = $request->validate($rules);

        try {
            $result = $this->deviceService->registerPhysicalDevice($user->id, $validated);
            return $this->successResponse($result, 'Physical device registered successfully.', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * عرض جميع الأجهزة الفيزيائية الخاصة بالمستخدم
     */
    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $devices = $this->deviceService->getUserPhysicalDevices($user->id);
        return $this->successResponse($devices);
    }

    /**
     * عرض جهاز محدد
     */
    public function show($id)
    {
        $device = $this->deviceService->getPhysicalDevice($id);
        if (!$device) {
            return $this->errorResponse('Physical device not found.', 404);
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
     * تحديث تفاصيل الجهاز الفيزيائي
     */
    public function updateDetails(Request $request, $id)
    {
        $rules = DeviceAndCardRequests::updatePhysicalDeviceDetail();
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
            $this->deviceService->deletePhysicalDevice($id);
            return $this->successResponse(null, 'Device deleted.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}