<?php

namespace App\Traits;

use App\Contracts\Repositories\CacheRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Validation\ValidationException;

trait OtpVerificationTrait
{
    /**
     * توليد رمز OTP عشوائي (رقمي بالطول المحدد).
     * @param int $length طول الرمز (افتراضي 6)
     * @return string
     */
    protected function generateOtpCode(int $length = 6): string
    {
        return str_pad((string) random_int(0, 10 ** $length - 1), $length, '0', STR_PAD_LEFT);
    }

    /**
     * تخزين رمز OTP في الكاش مع صلاحية زمنية (بالثواني).
     * @param string $key مفتاح فريد (مثل "otp:user:123")
     * @param string $code الرمز المراد تخزينه
     * @param int $ttlSeconds مدة الصلاحية (افتراضي 300 ثانية = 5 دقائق)
     */
    protected function storeOtpCode(string $key, string $code, int $ttlSeconds = 300): void
    {
        $this->getCacheRepo()->put($key, $code, $ttlSeconds);
    }

    /**
     * التحقق من صحة رمز OTP (مقارنة مع المخزن).
     * @throws ValidationException إذا كان الرمز غير صحيح أو منتهي الصلاحية
     */
    protected function verifyOtpCode(string $key, string $code): bool
    {
        $storedCode = $this->getCacheRepo()->get($key);
        if (!$storedCode || $storedCode !== $code) {
            throw ValidationException::withMessages(['otp' => 'Invalid or expired OTP code.']);
        }
        // بعد التحقق الناجح، نمسح الرمز من الكاش (لا يمكن إعادة استخدامه)
        $this->getCacheRepo()->forget($key);
        return true;
    }

    /**
     * حذف رمز OTP (مثلاً بعد إعادة الإرسال أو الإلغاء).
     */
    protected function clearOtpCode(string $key): void
    {
        $this->getCacheRepo()->forget($key);
    }

    /**
     * التحقق من إمكانية إعادة إرسال OTP (تجنب التكرار السريع).
     * @param string $requestKey مفتاح فريد لتتبع عدد الطلبات (مثل "otp_resend:user:123")
     * @param int $maxRequests الحد الأقصى للطلبات المسموحة خلال المدة
     * @param int $timeWindowSeconds النافذة الزمنية (بالثواني)
     * @throws ValidationException
     */
    protected function canResendOtp(string $requestKey, int $maxRequests = 3, int $timeWindowSeconds = 300): void
    {
        $attempts = $this->getCacheRepo()->get($requestKey, 0);
        if ($attempts >= $maxRequests) {
            throw ValidationException::withMessages(['otp' => 'Too many OTP requests. Please try again later.']);
        }
    }

    /**
     * تسجيل طلب إعادة إرسال OTP (زيادة العداد وتعيين صلاحية).
     */
    protected function recordOtpResendAttempt(string $requestKey, int $timeWindowSeconds = 300): void
    {
        $attempts = $this->getCacheRepo()->increment($requestKey);
        if ($attempts === 1) {
            $this->getCacheRepo()->expire($requestKey, $timeWindowSeconds);
        }
    }

    /**
     * إعادة تعيين عدد طلبات إعادة الإرسال (بعد نجاح التحقق مثلاً).
     */
    protected function resetOtpResendAttempts(string $requestKey): void
    {
        $this->getCacheRepo()->forget($requestKey);
    }

    // ------------------- دوال مجردة (يجب على الخدمة تنفيذها) -------------------

    /**
     * يجب أن توفر الخدمة الـ Cache Repository.
     * @return CacheRepositoryInterface
     */
    abstract protected function getCacheRepo(): CacheRepositoryInterface;

    /**
     * اختيارياً: يمكن جلب الإعدادات من AppConfigRepository.
     * إذا لم تحتج، يمكنك تجاهل هذه الدالة أو استخدام قيم ثابتة.
     */
    abstract protected function getOtpTtlSeconds(): int;
    abstract protected function getOtpMaxResendAttempts(): int;
    abstract protected function getOtpResendWindowSeconds(): int;
}