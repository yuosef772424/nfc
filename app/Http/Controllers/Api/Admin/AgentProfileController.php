<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\UserManagement\AgentProfileService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AgentProfileController extends BaseApiController
{
    public function __construct(protected AgentProfileService $profileService) {}

    /**
     * عرض جميع ملفات الوكلاء (مع pagination)
     */
    public function index(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $profiles = $this->profileService->getAll($perPage);
        return $this->successResponse($profiles);
    }

    /**
     * عرض الوكلاء النشطين فقط
     */
    public function active()
    {
        $profiles = $this->profileService->getActiveProfiles();
        return $this->successResponse($profiles);
    }

    /**
     * عرض ملف وكيل محدد
     */
    public function show($userId)
    {
        $profile = $this->profileService->getProfile($userId);
        if (!$profile) {
            return $this->errorResponse('Agent profile not found.', 404);
        }
        return $this->successResponse($profile);
    }

    /**
     * تحديث ملف وكيل (يشمل التفعيل/التعطيل وتحديث العمولة)
     */
    public function update($userId, Request $request)
    {
        $request->validate([
            'commission_type'  => 'sometimes|string|in:percentage,fixed',
            'commission_value' => 'sometimes|numeric|min:0',
            'is_active'        => 'sometimes|boolean',
        ]);

        try {
            $updated = $this->profileService->updateProfile($userId, $request->only([
                'commission_type', 'commission_value', 'is_active'
            ]));
            return $this->successResponse(null, 'Agent profile updated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تفعيل ملف وكيل
     */
    public function activate($userId)
    {
        try {
            $this->profileService->setActive($userId, true);
            return $this->successResponse(null, 'Agent profile activated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تعطيل ملف وكيل
     */
    public function deactivate($userId)
    {
        try {
            $this->profileService->setActive($userId, false);
            return $this->successResponse(null, 'Agent profile deactivated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * حذف ملف وكيل
     */
    public function destroy($userId)
    {
        try {
            $this->profileService->deleteProfile($userId);
            return $this->successResponse(null, 'Agent profile deleted.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}