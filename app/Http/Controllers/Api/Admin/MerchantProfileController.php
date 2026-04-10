<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\UserManagement\MerchantProfileService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MerchantProfileController extends BaseApiController
{
    public function __construct(protected MerchantProfileService $profileService) {}

    /**
     * عرض جميع ملفات التجار (مع pagination)
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $profiles = $this->profileService->getAll($perPage);
        return $this->successResponse($profiles);
    }

    /**
     * عرض التجار النشطين فقط
     */
    public function active()
    {
        $profiles = $this->profileService->getActiveProfiles();
        return $this->successResponse($profiles);
    }

    /**
     * عرض ملف تاجر محدد
     */
    public function show($userId)
    {
        $profile = $this->profileService->getProfile($userId);
        if (!$profile) {
            return $this->errorResponse('Merchant profile not found.', 404);
        }
        return $this->successResponse($profile);
    }

    /**
     * تحديث ملف تاجر (يشمل التفعيل/التعطيل)
     */
    public function update($userId, Request $request)
    {
        $request->validate([
            'business_name' => 'sometimes|string|max:255',
            'business_type' => 'sometimes|string|in:retail,wholesale,service,restaurant,other',
            'is_active'     => 'sometimes|boolean',
        ]);

        try {
            $updated = $this->profileService->updateProfile($userId, $request->only([
                'business_name', 'business_type', 'is_active'
            ]));
            return $this->successResponse(null, 'Merchant profile updated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تفعيل ملف تاجر
     */
    public function activate($userId)
    {
        try {
            $this->profileService->setActive($userId, true);
            return $this->successResponse(null, 'Merchant profile activated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تعطيل ملف تاجر
     */
    public function deactivate($userId)
    {
        try {
            $this->profileService->setActive($userId, false);
            return $this->successResponse(null, 'Merchant profile deactivated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * حذف ملف تاجر
     */
    public function destroy($userId)
    {
        try {
            $this->profileService->deleteProfile($userId);
            return $this->successResponse(null, 'Merchant profile deleted.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}