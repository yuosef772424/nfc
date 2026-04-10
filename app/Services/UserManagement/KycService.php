<?php

namespace App\Services\UserManagement;

use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\CacheRepositoryInterface;
use App\Contracts\Repositories\UserKycRepositoryInterface;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Traits\ConfigurableTrait;
use App\Traits\RateLimiterTrait;
use App\Traits\AuditableTrait;
use Illuminate\Validation\ValidationException;
use Carbon\Carbon;

class KycService
{
    use ConfigurableTrait, RateLimiterTrait, AuditableTrait;

    public function __construct(
        protected UserKycRepositoryInterface $kycRepo,
        protected UserRepositoryInterface $userRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected CacheRepositoryInterface $cacheRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    protected function getCacheRepo(): CacheRepositoryInterface { return $this->cacheRepo; }
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }

    protected function getMaxKycSubmissionAttempts(): int {
        return (int) $this->configRepo->getValue('security', 'kyc.submission.max_attempts') ?? 3;
    }
    protected function getKycSubmissionLockoutSeconds(): int {
        return (int) $this->configRepo->getValue('security', 'kyc.submission.lockout_seconds') ?? 86400;
    }

    // ------------------- رفع طلب KYC -------------------
    public function submitKyc(int $userId, array $data, ?array $documents = null): array
    {
        $user = $this->userRepo->findById($userId);
        if (!$user) {
            throw ValidationException::withMessages(['user' => 'User not found.']);
        }

        $attemptKey = "kyc_submission_attempts:user:" . $userId;
        $this->checkRateLimit($attemptKey, $this->getMaxKycSubmissionAttempts(), 'Too many failed KYC submissions.');

        $requiredFields = ['id_type', 'id_number', 'id_expiry_date'];
        foreach ($requiredFields as $field) {
            if (empty($data[$field])) {
                throw ValidationException::withMessages([$field => "The $field field is required."]);
            }
        }

        $expiry = Carbon::parse($data['id_expiry_date']);
        if ($expiry->isPast()) {
            throw ValidationException::withMessages(['id_expiry_date' => 'ID expiry date must be in the future.']);
        }

        // رفع المستندات (صور الهوية)
        if ($documents) {
            $documentPaths = [];
            foreach ($documents as $key => $file) {
                $path = $file->store('kyc_documents', 'public');
                $documentPaths[$key] = $path;
            }
            $data['documents'] = $documentPaths;
        }

        $data['verified_at'] = null;
        $kyc = $this->kycRepo->createOrUpdate($userId, $data);

        $this->resetAttempts($attemptKey);
        $this->logAudit('kyc_submitted', 'user_kyc', $userId, $userId, null, $kyc->toArray());

        return $kyc->toArray();
    }

    // ------------------- موافقة المسؤول -------------------
    public function approveKyc(int $userId, int $adminId): bool
    {
        $kyc = $this->kycRepo->getByUserId($userId);
        if (!$kyc) {
            throw ValidationException::withMessages(['kyc' => 'No KYC record found.']);
        }
        if ($kyc->verified_at !== null) {
            throw ValidationException::withMessages(['kyc' => 'KYC already verified.']);
        }

        $updated = $this->kycRepo->markVerified($userId);
        if ($updated) {
            $this->logAudit('kyc_approved', 'user_kyc', $userId, $adminId, null, ['verified_at' => now()]);
        }
        return $updated;
    }

    // ------------------- رفض المسؤول -------------------
    public function rejectKyc(int $userId, int $adminId, string $reason): bool
    {
        $kyc = $this->kycRepo->getByUserId($userId);
        if (!$kyc) {
            throw ValidationException::withMessages(['kyc' => 'No KYC record found.']);
        }

        $updated = $this->kycRepo->update($userId, [
            'verified_at' => null,
            'rejection_reason' => $reason,
        ]);

        $attemptKey = "kyc_submission_attempts:user:" . $userId;
        $this->recordFailedAttempt($attemptKey, $this->getKycSubmissionLockoutSeconds());

        if ($updated) {
            $this->logAudit('kyc_rejected', 'user_kyc', $userId, $adminId, null, ['rejection_reason' => $reason]);
        }
        return $updated;
    }

    // ------------------- استعلامات -------------------
    public function getKycStatus(int $userId): ?array
    {
        $kyc = $this->kycRepo->getByUserId($userId);
        if (!$kyc) return null;

        return [
            'id_type'         => $kyc->id_type,
            'id_number'       => $kyc->id_number,
            'id_expiry_date'  => $kyc->id_expiry_date?->toDateString(),
            'is_verified'     => $kyc->verified_at !== null,
            'verified_at'     => $kyc->verified_at?->toDateTimeString(),
            'is_expired'      => $this->isExpired($userId),
            'rejection_reason'=> $kyc->rejection_reason ?? null,
            'documents'       => $kyc->documents ?? [],
        ];
    }

    public function isExpired(int $userId): bool { return $this->kycRepo->isExpired($userId); }
    public function isVerified(int $userId): bool { return $this->kycRepo->isVerified($userId); }

    public function canResubmit(int $userId): bool {
        $attemptKey = "kyc_submission_attempts:user:" . $userId;
        $attempts = $this->cacheRepo->get($attemptKey, 0);
        return $attempts < $this->getMaxKycSubmissionAttempts();
    }

    public function getPendingRequests(int $perPage = 20): array {
        return $this->kycRepo->getPending($perPage)->toArray();
    }

    public function getVerifiedRequests(int $perPage = 20): array {
        return $this->kycRepo->getVerified($perPage)->toArray();
    }

    public function getAllPending(): \Illuminate\Pagination\LengthAwarePaginator {
        return $this->kycRepo->getPending(20);
    }
}