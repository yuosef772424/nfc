<?php

namespace App\Contracts\Services;

use App\Models\UserKyc;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface KycServiceInterface
{
    // ---------------------------------------------------------------
    // Submission
    // ---------------------------------------------------------------

    /**
     * رفع بيانات الهوية — يُشفّر البيانات قبل الحفظ
     *
     * @param  array $data  [id_type, id_number, id_front_image, id_back_image, id_expiry_date, date_of_birth, address]
     * @throws \App\Exceptions\Kyc\KycAlreadyVerifiedException
     */
    public function submit(int $userId, array $data): UserKyc;

    /**
     * تحديث بيانات KYC (مسموح فقط إذا لم يتم التحقق بعد أو رُفض)
     *
     * @throws \App\Exceptions\Kyc\KycAlreadyVerifiedException
     */
    public function update(int $userId, array $data): UserKyc;

    // ---------------------------------------------------------------
    // Review (Admin)
    // ---------------------------------------------------------------

    /**
     * الموافقة على KYC وتعيين is_verified = true في جدول users
     *
     * @throws \App\Exceptions\Kyc\KycNotFoundException
     */
    public function approve(int $userId, int $reviewedBy): bool;

    /**
     * رفض KYC مع سبب الرفض
     *
     * @throws \App\Exceptions\Kyc\KycNotFoundException
     */
    public function reject(int $userId, string $reason, int $reviewedBy): bool;

    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function getByUserId(int $userId): ?UserKyc;

    /** قائمة انتظار المراجعة */
    public function getPendingReview(int $perPage = 20): LengthAwarePaginator;

    // ---------------------------------------------------------------
    // Checks
    // ---------------------------------------------------------------

    public function isVerified(int $userId): bool;

    public function isSubmitted(int $userId): bool;

    /**
     * التحقق من أن الهوية لم تنتهِ صلاحيتها
     */
    public function isIdExpired(int $userId): bool;
}
