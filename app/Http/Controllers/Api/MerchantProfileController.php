<?php

namespace App\Http\Controllers\Api;

use App\Services\UserManagement\MerchantProfileService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class MerchantProfileController extends BaseApiController
{
    public function __construct(protected MerchantProfileService $profileService) {}

    /**
     * عرض ملف التاجر الخاص بالمستخدم الحالي
     */
    public function show(Request $request)
    {
        $user = $request->get('auth_user');
        $profile = $this->profileService->getProfile($user->id);

        if (!$profile) {
            return $this->errorResponse('Merchant profile not found.', 404);
        }

        return $this->successResponse($profile);
    }

    /**
     * إنشاء ملف تاجر (إذا لم يكن موجوداً)
     */
    public function store(Request $request)
    {
        $request->validate([
            'business_name' => 'required|string|max:255',
            'business_type' => 'required|string|in:retail,wholesale,service,restaurant,other',
        ]);

        $user = $request->get('auth_user');

        try {
            $profile = $this->profileService->createProfile($user->id, $request->only([
                'business_name', 'business_type'
            ]));
            return $this->successResponse($profile, 'Merchant profile created.', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تحديث ملف التاجر
     */
    public function update(Request $request)
    {
        $request->validate([
            'business_name' => 'sometimes|string|max:255',
            'business_type' => 'sometimes|string|in:retail,wholesale,service,restaurant,other',
        ]);

        $user = $request->get('auth_user');

        try {
            $updated = $this->profileService->updateProfile($user->id, $request->only([
                'business_name', 'business_type'
            ]));
            return $this->successResponse(null, 'Merchant profile updated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}