<?php

namespace App\Http\Controllers\Api;

use App\Services\UserManagement\AgentProfileService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AgentProfileController extends BaseApiController
{
    public function __construct(protected AgentProfileService $profileService) {}

    /**
     * عرض ملف الوكيل الخاص بالمستخدم الحالي
     */
    public function show(Request $request)
    {
        $user = $request->get('auth_user');
        $profile = $this->profileService->getProfile($user->id);

        if (!$profile) {
            return $this->errorResponse('Agent profile not found.', 404);
        }

        return $this->successResponse($profile);
    }

    /**
     * إنشاء ملف وكيل (إذا لم يكن موجوداً)
     */
    public function store(Request $request)
    {
        $request->validate([
            'commission_type'  => 'required|string|in:percentage,fixed',
            'commission_value' => 'required|numeric|min:0',
        ]);

        $user = $request->get('auth_user');

        try {
            $profile = $this->profileService->createProfile($user->id, $request->only([
                'commission_type', 'commission_value'
            ]));
            return $this->successResponse($profile, 'Agent profile created.', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تحديث ملف الوكيل
     */
    public function update(Request $request)
    {
        $request->validate([
            'commission_type'  => 'sometimes|string|in:percentage,fixed',
            'commission_value' => 'sometimes|numeric|min:0',
        ]);

        $user = $request->get('auth_user');

        try {
            $updated = $this->profileService->updateProfile($user->id, $request->only([
                'commission_type', 'commission_value'
            ]));
            return $this->successResponse(null, 'Agent profile updated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}