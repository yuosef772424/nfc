<?php

namespace App\Contracts\Services;

use App\Models\OtpVerification;

interface OtpServiceInterface
{
    // ---------------------------------------------------------------
    // Generation & Delivery
    // ---------------------------------------------------------------

    /**
     * إنشاء OTP جديد وإرساله
     * يُلغي أي OTP سابق لنفس المستخدم والغرض تلقائياً
     *
     * @param  string $channel  'sms' | 'email'
     * @throws \App\Exceptions\Otp\OtpRateLimitException
     */
    public function send(int $userId, string $purpose, string $channel = 'sms'): OtpVerification;

    /**
     * إعادة إرسال OTP (مع rate limiting)
     *
     * @throws \App\Exceptions\Otp\OtpRateLimitException
     * @throws \App\Exceptions\Otp\TooManyResendAttemptsException
     */
    public function resend(int $userId, string $purpose, string $channel = 'sms'): OtpVerification;

    // ---------------------------------------------------------------
    // Verification
    // ---------------------------------------------------------------

    /**
     * التحقق من صحة الكود
     *
     * @throws \App\Exceptions\Otp\InvalidOtpException
     * @throws \App\Exceptions\Otp\OtpExpiredException
     * @throws \App\Exceptions\Otp\OtpAlreadyUsedException
     */
    public function verify(int $userId, string $purpose, string $code): bool;

    /**
     * التحقق بدون رمي exception — للاستخدام الداخلي
     */
    public function isValid(int $userId, string $purpose, string $code): bool;

    // ---------------------------------------------------------------
    // Maintenance
    // ---------------------------------------------------------------

    /** تنظيف OTP المنتهية والمستخدمة — للـ Scheduled Job */
    public function cleanup(): int;

    /** التحقق من أن المستخدم لم يتجاوز حد الطلبات */
    public function canRequest(int $userId, string $purpose): bool;

    /** كم ثانية يجب الانتظار قبل الطلب التالي */
    public function getRetryAfterSeconds(int $userId, string $purpose): int;
}
