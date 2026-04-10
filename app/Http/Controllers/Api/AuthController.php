<?php

namespace App\Http\Controllers\Api;

use App\Services\Auth\GuestAuthService;
use App\Services\Auth\AuthenticatedUserService;
use App\Http\Requests\Auth\LoginRequest;
use App\Http\Requests\Auth\RegisterRequest;
use App\Http\Requests\Auth\PasswordResetRequest;
use App\Http\Requests\Auth\VerifyEmailRequest;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class AuthController extends BaseApiController
{
    public function __construct(
        protected GuestAuthService $guestAuthService,
        protected AuthenticatedUserService $authenticatedUserService
    ) {}

    /**
     * تسجيل مستخدم جديد
     */
    public function register(RegisterRequest $request)
    {
        try {
            $user = $this->guestAuthService->register($request->validated());
            return $this->successResponse($user, 'Registration successful. Please verify your email.', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Registration failed. Please try again later.', 500);
        }
    }

    /**
     * تسجيل الدخول
     */
    public function login(LoginRequest $request)
    {
        try {
            $result = $this->guestAuthService->login(
                login: $request->input('login'),
                password: $request->input('password'),
                deviceInfo: $request->userAgent(),
                location: ['ip' => $request->ip()]
            );

            return $this->successResponse($result, 'Login successful.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 401, $e->errors());
        } catch (\Exception $e) {
            report($e);
            return $this->errorResponse('Login failed. Please try again.', 500);
        }
    }

    /**
     * تسجيل الخروج
     */
    public function logout(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return $this->errorResponse('Token missing.', 400);
        }

        $tokenHash = hash('sha256', $token);
        try {
            $this->authenticatedUserService->logout($tokenHash);
            return $this->successResponse(null, 'Logged out successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 401, $e->errors());
        }
    }

    /**
     * تجديد التوكن (Refresh Token)
     */
    public function refreshToken(Request $request)
    {
        $token = $request->bearerToken();
        if (!$token) {
            return $this->errorResponse('Token missing.', 400);
        }

        $tokenHash = hash('sha256', $token);
        try {
            $newSession = $this->authenticatedUserService->refreshToken($tokenHash);
            return $this->successResponse($newSession, 'Token refreshed.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 401, $e->errors());
        }
    }

    /**
     * طلب إعادة تعيين كلمة المرور (إرسال رمز OTP)
     */
    public function requestPasswordReset(PasswordResetRequest $request)
    {
        try {
            $code = $this->guestAuthService->requestPasswordReset($request->input('email_or_phone'));
            // في بيئة حقيقية، لا نعيد الكود بل نرسله عبر SMS/Email
            return $this->successResponse(null, 'If the account exists, a reset code has been sent.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * إعادة تعيين كلمة المرور (تأكيد الرمز)
     */
    public function resetPassword(Request $request)
    {
        $request->validate([
            'email_or_phone' => 'required|string',
            'code'           => 'required|string',
            'password'       => 'required|string|min:8|confirmed',
        ]);

        try {
            $this->guestAuthService->resetPassword(
                $request->input('email_or_phone'),
                $request->input('code'),
                $request->input('password')
            );
            return $this->successResponse(null, 'Password reset successful. You can now login.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * إرسال رمز تحقق البريد الإلكتروني
     */
    public function sendEmailVerification(Request $request)
    {
        $user = $request->get('auth_user');
        try {
            $this->guestAuthService->sendEmailVerification($user->id);
            return $this->successResponse(null, 'Verification code sent to your email.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تأكيد البريد الإلكتروني
     */
    public function verifyEmail(VerifyEmailRequest $request)
    {
        $user = $request->get('auth_user');
        try {
            $this->guestAuthService->verifyEmail($user->id, $request->input('code'));
            return $this->successResponse(null, 'Email verified successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * عرض بيانات المستخدم المصادق عليه حالياً مع معلومات الجلسة
     */
    public function me(Request $request)
    {
        $user = $request->get('auth_user');
        $token = $request->bearerToken();
        $tokenHash = hash('sha256', $token);
        $session = $this->authenticatedUserService->getCurrentSession($tokenHash);

        // تحميل العلاقات الأساسية
        $user->load(['wallet', 'agentProfile', 'merchantProfile']);

        return $this->successResponse([
            'user'    => $user,
            'session' => $session,
        ]);
    }
}