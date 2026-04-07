<?php

namespace App\Contracts\Services;

use App\Models\User;
use App\Models\Session;

interface AuthServiceInterface
{
    // ---------------------------------------------------------------
    // Registration
    // ---------------------------------------------------------------

    /**
     * تسجيل مستخدم جديد (user | agent | merchant)
     * يُنشئ: user + wallet + agent/merchant profile إذا انطبق
     *
     * @return array{user: User, token: string}
     */
    public function register(array $data, string $userType = 'user'): array;

    // ---------------------------------------------------------------
    // Login / Logout
    // ---------------------------------------------------------------

    /**
     * تسجيل الدخول بالهاتف وكلمة المرور
     *
     * @return array{user: User, token: string, session: Session}
     * @throws \App\Exceptions\Auth\InvalidCredentialsException
     * @throws \App\Exceptions\Auth\AccountSuspendedException
     */
    public function login(string $phone, string $password, array $deviceInfo = [], ?array $location = null): array;

    /** تسجيل خروج الجلسة الحالية */
    public function logout(string $tokenHash): bool;

    /** تسجيل خروج كل الجلسات لمستخدم معين */
    public function logoutAllSessions(int $userId): int;

    // ---------------------------------------------------------------
    // Token
    // ---------------------------------------------------------------

    /** التحقق من صحة التوكن وإرجاع المستخدم */
    public function validateToken(string $tokenHash): ?User;

    /** تجديد التوكن (refresh) */
    public function refreshToken(string $oldTokenHash): ?array; // {token, session}

    // ---------------------------------------------------------------
    // Password
    // ---------------------------------------------------------------

    /**
     * طلب إعادة تعيين كلمة المرور — يُرسل OTP
     *
     * @throws \App\Exceptions\Auth\UserNotFoundException
     */
    public function requestPasswordReset(string $phone): bool;

    /**
     * إعادة تعيين كلمة المرور بعد التحقق من OTP
     *
     * @throws \App\Exceptions\Auth\InvalidOtpException
     */
    public function resetPassword(string $phone, string $otpCode, string $newPassword): bool;

    public function changePassword(int $userId, string $currentPassword, string $newPassword): bool;

    // ---------------------------------------------------------------
    // Phone Verification
    // ---------------------------------------------------------------

    public function sendPhoneVerificationOtp(int $userId): bool;

    /**
     * @throws \App\Exceptions\Auth\InvalidOtpException
     */
    public function verifyPhone(int $userId, string $otpCode): bool;
}
