<?php

namespace App\Traits;

use App\Contracts\Repositories\CacheRepositoryInterface;
use Illuminate\Validation\ValidationException;

trait RateLimiterTrait
{
    /**
     * التحقق من عدم تجاوز عدد المحاولات لمفتاح معين.
     * @throws ValidationException
     */
    protected function checkRateLimit(string $key, int $maxAttempts, string $errorMessage = 'Too many attempts. Please try again later.'): void
    {
        $attempts = $this->getCacheRepo()->get($key, 0);
        if ($attempts >= $maxAttempts) {
            throw ValidationException::withMessages(['rate_limit' => $errorMessage]);
        }
    }

    /**
     * تسجيل محاولة فاشلة (زيادة العداد وتعيين صلاحية).
     */
    protected function recordFailedAttempt(string $key, int $lockoutSeconds): void
    {
        $attempts = $this->getCacheRepo()->increment($key);
        if ($attempts === 1) {
            $this->getCacheRepo()->expire($key, $lockoutSeconds);
        }
    }

    /**
     * إعادة تعيين عدد المحاولات عند النجاح.
     */
    protected function resetAttempts(string $key): void
    {
        $this->getCacheRepo()->forget($key);
    }

    /**
     * يجب أن توفر الخدمة التي تستخدم الـ Trait هذه الدالة.
     * @return CacheRepositoryInterface
     */
    abstract protected function getCacheRepo(): CacheRepositoryInterface;
}