<?php

namespace App\Contracts\Repositories;

use App\Models\OtpVerification;

interface OtpVerificationRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function findById(int $id): ?OtpVerification;

    /** جلب آخر OTP صالح لمستخدم وغرض معين (يعوض scopeValid جزئياً) */
    public function findValidByUserAndPurpose(int $userId, string $purpose): ?OtpVerification;

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------

    public function create(int $userId, string $purpose, string $codeHash, \DateTimeInterface $expiresAt): OtpVerification;

    public function markUsed(int $id): bool;

    /** إلغاء كل OTP سابق لنفس المستخدم والغرض قبل إنشاء جديد */
    public function invalidatePreviousByUserAndPurpose(int $userId, string $purpose): int;

    /** تنظيف الـ OTP المنتهية أو المستخدمة */
    public function deleteExpiredAndUsed(): int;

    // ---------------------------------------------------------------
    // Checks & Verification (الدوال المنقولة من الموديل)
    // ---------------------------------------------------------------

    /** التحقق من صلاحية OTP معين (بدون استهلاكه) */
    public function isValid(int $id): bool;

    /** التحقق من الرمز، وفي حالة النجاح يتم تعليم الـ OTP كمستخدم */
    public function verify(int $id, string $code): bool;

    /** هل يوجد OTP صالح لمستخدم وغرض معين */
    public function hasValidOtp(int $userId, string $purpose): bool;
}