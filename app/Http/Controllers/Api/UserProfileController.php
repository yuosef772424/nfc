<?php

namespace App\Http\Controllers\Api;

use App\Services\UserManagement\UserProfileService;
use App\Services\Auth\AuthenticatedUserService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class UserProfileController extends BaseApiController
{
    public function __construct(
        protected UserProfileService $profileService,
        protected AuthenticatedUserService $authService
    ) {}

    /**
     * عرض الملف الشخصي للمستخدم الحالي
     */
    public function show(Request $request)
    {
        $user = $request->get('auth_user');
        $profile = $this->profileService->getUserProfile($user->id, ['wallet', 'agentProfile', 'merchantProfile']);
        return $this->successResponse($profile);
    }

    /**
     * تحديث الملف الشخصي (الاسم، الصورة الرمزية، التفضيلات)
     */
    public function update(Request $request)
    {
        $request->validate([
            'name'        => 'sometimes|string|max:255',
            'avatar'      => 'sometimes|image|max:2048',
            'preferences' => 'sometimes|array',
        ]);

        $user = $request->get('auth_user');
        $data = $request->only(['name', 'preferences']);

        // معالجة رفع الصورة الرمزية (اختياري)
        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $data['avatar'] = $path;
        }

        try {
            $this->profileService->updateProfile($user->id, $data);
            return $this->successResponse(null, 'Profile updated successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تغيير كلمة المرور
     */
    public function changePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_password'     => 'required|string|min:8|confirmed',
        ]);

        $user = $request->get('auth_user');
        try {
            $this->authService->changePassword(
                $user->id,
                $request->input('current_password'),
                $request->input('new_password')
            );
            return $this->successResponse(null, 'Password changed successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * بدء عملية تغيير البريد الإلكتروني (إرسال رمز تحقق)
     */
    public function initiateEmailChange(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_email'        => 'required|email',
        ]);

        $user = $request->get('auth_user');
        try {
            $this->profileService->initiateEmailChange(
                $user->id,
                $request->input('current_password'),
                $request->input('new_email')
            );
            return $this->successResponse(null, 'Verification code sent to new email.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تأكيد تغيير البريد الإلكتروني
     */
    public function confirmEmailChange(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->get('auth_user');
        try {
            $this->profileService->confirmEmailChange($user->id, $request->input('code'));
            return $this->successResponse(null, 'Email changed successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * بدء عملية تغيير رقم الهاتف (إرسال رمز تحقق)
     */
    public function initiatePhoneChange(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string',
            'new_phone'        => 'required|string',
        ]);

        $user = $request->get('auth_user');
        try {
            $this->profileService->initiatePhoneChange(
                $user->id,
                $request->input('current_password'),
                $request->input('new_phone')
            );
            return $this->successResponse(null, 'Verification code sent to new phone.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تأكيد تغيير رقم الهاتف
     */
    public function confirmPhoneChange(Request $request)
    {
        $request->validate(['code' => 'required|string']);

        $user = $request->get('auth_user');
        try {
            $this->profileService->confirmPhoneChange($user->id, $request->input('code'));
            return $this->successResponse(null, 'Phone changed successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تعطيل الحساب (إخفاء مؤقت)
     */
    public function deactivateAccount(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = $request->get('auth_user');
        try {
            $this->profileService->deactivateAccount($user->id, $request->input('password'));
            return $this->successResponse(null, 'Account deactivated successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * إعادة تنشيط الحساب (يحتاج صلاحيات خاصة، يمكن أن تكون للمسؤول فقط)
     */
    public function reactivateAccount(Request $request)
    {
        $user = $request->get('auth_user');
        try {
            $this->profileService->reactivateAccount($user->id);
            return $this->successResponse(null, 'Account reactivated successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * حذف الحساب نهائياً
     */
    public function deleteAccount(Request $request)
    {
        $request->validate(['password' => 'required|string']);

        $user = $request->get('auth_user');
        try {
            $this->profileService->deleteAccount($user->id, $request->input('password'));
            return $this->successResponse(null, 'Account deleted successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}