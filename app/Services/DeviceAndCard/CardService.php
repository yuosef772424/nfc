<?php

namespace App\Services\DeviceAndCard;

use App\Contracts\Repositories\CardRepositoryInterface;
use App\Contracts\Repositories\WalletRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use App\Contracts\Repositories\AuditLogRepositoryInterface;
use App\Contracts\Repositories\CacheRepositoryInterface;
use App\Traits\AuditableTrait;
use App\Traits\ConfigurableTrait;
use App\Traits\RateLimiterTrait;
use Illuminate\Validation\ValidationException;

class CardService
{
    use AuditableTrait, ConfigurableTrait, RateLimiterTrait;

    public function __construct(
        protected CardRepositoryInterface $cardRepo,
        protected WalletRepositoryInterface $walletRepo,
        protected AuditLogRepositoryInterface $auditLogRepo,
        protected CacheRepositoryInterface $cacheRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {}

    // ------------------- تنفيذ دوال الـ Traits المجردة -------------------
    protected function getAuditLogRepo(): AuditLogRepositoryInterface { return $this->auditLogRepo; }
    protected function getConfigRepo(): AppConfigRepositoryInterface { return $this->configRepo; }
    protected function getCacheRepo(): CacheRepositoryInterface { return $this->cacheRepo; }

    // ------------------- دوال مساعدة للإعدادات -------------------
    protected function getMaxPinAttempts(): int
    {
        return (int) $this->configRepo->getValue('security', 'pin.max_attempts') ?? 3;
    }

    protected function getPinLockoutSeconds(): int
    {
        return (int) $this->configRepo->getValue('security', 'pin.lockout_seconds') ?? 900;
    }

    // ------------------- إنشاء بطاقة -------------------
    public function createCard(array $data): array
    {
        // التحقق من وجود المحفظة
        $wallet = $this->walletRepo->findById($data['wallet_id']);
        if (!$wallet) {
            throw ValidationException::withMessages(['wallet_id' => 'Wallet not found.']);
        }

        // التحقق من عدم تكرار nfc_uid
        if ($this->cardRepo->existsByNfcUid($data['nfc_uid'])) {
            throw ValidationException::withMessages(['nfc_uid' => 'NFC UID already exists.']);
        }

        // التحقق من عدم تكرار card_number (إذا تم توفيره)
        if (!empty($data['card_number']) && $this->cardRepo->getByCardNumber($data['card_number'])) {
            throw ValidationException::withMessages(['card_number' => 'Card number already exists.']);
        }

        $card = $this->cardRepo->create($data);

        $this->logAudit(
            'card_created',
            'card',
            $card->id,
            $wallet->user_id,
            null,
            $card->toArray()
        );

        return $card->toArray();
    }

    // ------------------- استعلامات -------------------
    public function getCard(int $cardId, array $with = []): ?array
    {
        $card = $this->cardRepo->findById($cardId, $with);
        return $card?->toArray();
    }

    public function getCardByNfcUid(string $nfcUid, array $with = []): ?array
    {
        $card = $this->cardRepo->getByNfcUid($nfcUid, $with);
        return $card?->toArray();
    }

    public function getCardByNumber(string $cardNumber, array $with = []): ?array
    {
        $card = $this->cardRepo->getByCardNumber($cardNumber, $with);
        return $card?->toArray();
    }

    public function getCardsByWallet(int $walletId, array $with = []): array
    {
        return $this->cardRepo->getByWalletId($walletId, $with)->toArray();
    }

    // ------------------- تحديث حالة البطاقة -------------------
    public function updateCardStatus(int $cardId, string $status): bool
    {
        $card = $this->cardRepo->findById($cardId);
        if (!$card) {
            throw ValidationException::withMessages(['card' => 'Card not found.']);
        }

        $allowedStatuses = ['active', 'inactive', 'blocked', 'expired'];
        if (!in_array($status, $allowedStatuses)) {
            throw ValidationException::withMessages(['status' => 'Invalid card status.']);
        }

        $oldStatus = $card->status;
        $updated = $this->cardRepo->updateStatus($cardId, $status);

        if ($updated) {
            $this->logAudit(
                'card_status_updated',
                'card',
                $cardId,
                $card->wallet->user_id ?? null,
                ['status' => $oldStatus],
                ['status' => $status]
            );
        }

        return $updated;
    }

    // ------------------- تعيين PIN -------------------
    public function setPin(int $cardId, string $newPin): bool
    {
        $card = $this->cardRepo->findById($cardId);
        if (!$card) {
            throw ValidationException::withMessages(['card' => 'Card not found.']);
        }

        if (strlen($newPin) < 4 || strlen($newPin) > 8) {
            throw ValidationException::withMessages(['pin' => 'PIN must be between 4 and 8 digits.']);
        }

        $updated = $this->cardRepo->setPin($cardId, $newPin);
        if ($updated) {
            $this->logAudit(
                'card_pin_set',
                'card',
                $cardId,
                $card->wallet->user_id ?? null,
                null,
                null
            );
        }

        return $updated;
    }

    // ------------------- التحقق من PIN مع Rate Limiting -------------------
    public function verifyPin(int $cardId, string $pin): bool
    {
        $card = $this->cardRepo->findById($cardId);
        if (!$card) {
            throw ValidationException::withMessages(['card' => 'Card not found.']);
        }

        if ($card->status !== 'active') {
            throw ValidationException::withMessages(['card' => 'Card is not active.']);
        }

        $attemptKey = "pin_attempts:card:{$cardId}";
        $maxAttempts = $this->getMaxPinAttempts();
        $lockoutSeconds = $this->getPinLockoutSeconds();

        // التحقق من تجاوز الحد الأقصى
        $this->checkRateLimit($attemptKey, $maxAttempts, 'Too many failed PIN attempts. Card has been blocked.');

        $isValid = $this->cardRepo->verifyPin($cardId, $pin);
        if ($isValid) {
            $this->resetAttempts($attemptKey);
            return true;
        }

        // تسجيل محاولة فاشلة
        $this->recordFailedAttempt($attemptKey, $lockoutSeconds);

        // تسجيل الفشل في التدقيق
        $this->logAudit(
            'pin_verification_failed',
            'card',
            $cardId,
            $card->wallet->user_id ?? null,
            null,
            ['attempts' => $this->cacheRepo->get($attemptKey, 0)]
        );

        throw ValidationException::withMessages(['pin' => 'Invalid PIN.']);
    }

    // ------------------- إعادة تعيين محاولات PIN -------------------
    public function resetPinAttempts(int $cardId): void
    {
        $this->resetAttempts("pin_attempts:card:{$cardId}");
    }

    // ------------------- التحقق من الصلاحية -------------------
    public function isExpired(int $cardId): bool
    {
        return $this->cardRepo->isExpired($cardId);
    }

    public function isActive(int $cardId): bool
    {
        return $this->cardRepo->isActive($cardId);
    }

    // ------------------- حذف بطاقة -------------------
    public function deleteCard(int $cardId): bool
    {
        $card = $this->cardRepo->findById($cardId);
        if (!$card) {
            throw ValidationException::withMessages(['card' => 'Card not found.']);
        }

        $deleted = $this->cardRepo->delete($cardId);
        if ($deleted) {
            $this->logAudit(
                'card_deleted',
                'card',
                $cardId,
                $card->wallet->user_id ?? null,
                $card->toArray(),
                null
            );
        }

        return $deleted;
    }
}