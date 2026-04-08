<?php

namespace App\Http\Requests\RateLimited;

use App\Contracts\Repositories\CacheRepositoryInterface;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Validation\ValidationException;

abstract class RateLimitedRequest extends FormRequest
{
    protected CacheRepositoryInterface $cacheRepo;

    // يجب على الكلاس الابن تعريف:
    // - unique cache key prefix (مثل 'pin_attempts')
    // - الحد الأقصى للمحاولات
    // - مدة القفل (بالثواني)
    abstract protected function getCacheKeyPrefix(): string;
    abstract protected function getMaxAttempts(): int;
    abstract protected function getLockoutSeconds(): int;

    public function __construct(CacheRepositoryInterface $cacheRepo)
    {
        parent::__construct();
        $this->cacheRepo = $cacheRepo;
    }

    /**
     * بناء مفتاح الكاش الفريد بناءً على معرف المستخدم أو أي معرف فريد.
     */
    protected function getAttemptsKey(): string
    {
        // مثال: pin_attempts:user:123
        return $this->getCacheKeyPrefix() . ':user:' . $this->user()->id;
    }

    /**
     * التحقق من أن عدد المحاولات لم يتجاوز الحد.
     */
    public function passesAttemptsCheck(): bool
    {
        $attempts = $this->cacheRepo->get($this->getAttemptsKey(), 0);
        return $attempts < $this->getMaxAttempts();
    }

    /**
     * إضافة قاعدة تحقق مخصصة.
     */
    public function rules(): array
    {
        return [
            // يمكن إضافة قواعد أخرى هنا
        ];
    }

    /**
     * إضافة التحقق بعد إنشاء الـ Validator.
     */
    public function withValidator(Validator $validator)
    {
        $validator->after(function ($validator) {
            if (!$this->passesAttemptsCheck()) {
                $validator->errors()->add(
                    'attempts',
                    sprintf(
                        'Too many attempts. Please try again after %d seconds.',
                        $this->getLockoutSeconds()
                    )
                );
            }
        });
    }

    /**
     * تسجيل محاولة فاشلة (يتم استدعاؤها من الـ Controller عند فشل العملية).
     */
    public function recordFailedAttempt(): void
    {
        $key = $this->getAttemptsKey();
        $attempts = $this->cacheRepo->get($key, 0) + 1;
        $this->cacheRepo->put($key, $attempts, $this->getLockoutSeconds());
    }

    /**
     * إعادة تعيين المحاولات عند النجاح.
     */
    public function resetAttempts(): void
    {
        $this->cacheRepo->forget($this->getAttemptsKey());
    }
}