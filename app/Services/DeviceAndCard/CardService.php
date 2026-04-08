<?php

namespace App\Services\DeviceAndCard;

use App\Repositories\CardRepository;
use App\Repositories\WalletRepository;
use App\Repositories\AuditLogRepository;
use App\Repositories\CacheRepository;
use App\Repositories\AppConfigRepositoryInterface;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class CardService
{
    protected int $maxPinAttempts;
    protected int $pinLockoutSeconds;

    public function __construct(
        protected CardRepository $cardRepo,
        protected WalletRepository $walletRepo,
        protected AuditLogRepository $auditLogRepo,
        protected CacheRepository $cacheRepo,
        protected AppConfigRepositoryInterface $configRepo,
    ) {
        $this->maxPinAttempts = (int) $this->configRepo->getValue('security', 'pin.max_attempts') ?? 3;
        $this->pinLockoutSeconds = (int) $this->configRepo->getValue('security', 'pin.lockout_seconds') ?? 900;
    }

    /**
     * إنشاء بطاقة جديدة (ربط بمحفظة ووكيل اختياري)
     */
    public function createCard(array $data): array
    {
        // التحقق من وجود المحفظة
        $wallet = $this->walletRepo->findById($data['wallet_id']);
        if (!$wallet) {
            throw ValidationException::withMessages(['wallet_id' => 'Wallet not found.']);
        }

        // التحقق من عدم تكرار nfc_uid أو card_number
        if ($this->cardRepo->existsByNfcUid($data['nfc_uid'])) {
            throw ValidationException::withMessages(['nfc_uid' => 'NFC UID already exists.']);
        }

        $card = $this->cardRepo->create($data);

        $this->auditLogRepo->create(
            action: 'card_created',
            entity: 'card',
            entityId: $card->id,
            userId: $wallet->user_id,
            ipAddress: request()->ip(),
            oldData: null,
            newData: $card->toArray()
        );

        return $card->toArray();
    }

    /**
     * الحصول على بطاقة بواسطة ID
     */
    public function getCard(int $cardId, array $with = []): ?array
    {
        $card = $this->cardRepo->findById($cardId, $with);
        return $card?->toArray();
    }

    /**
     * الحصول على بطاقة بواسطة NFC UID
     */
    public function getCardByNfcUid(string $nfcUid, array $with = []): ?array
    {
        $card = $this->cardRepo->getByNfcUid($nfcUid, $with);
        return $card?->toArray();
    }

    /**
     * الحصول على بطاقة بواسطة رقم البطاقة
     */
    public function getCardByNumber(string $cardNumber, array $with = []): ?array
    {
        $card = $this->cardRepo->getByCardNumber($cardNumber, $with);
        return $card?->toArray();
    }

    /**
     * جلب بطاقات المحفظة
     */
    public function getCardsByWallet(int $walletId, array $with = []): array
    {
        return $this->cardRepo->getByWalletId($walletId, $with)->toArray();
    }

    /**
     * تحديث حالة البطاقة (active, inactive, blocked, expired)
     */
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
            $this->auditLogRepo->create(
                action: 'card_status_updated',
                entity: 'card',
                entityId: $cardId,
                userId: $card->wallet->user_id ?? null,
                ipAddress: request()->ip(),
                oldData: ['status' => $oldStatus],
                newData: ['status' => $status]
            );
        }
        return $updated;
    }

    /**
     * تعيين PIN للبطاقة (أو تحديثه)
     */
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
            $this->auditLogRepo->create(
                action: 'card_pin_set',
                entity: 'card',
                entityId: $cardId,
                userId: $card->wallet->user_id ?? null,
                ipAddress: request()->ip(),
                oldData: null,
                newData: null
            );
        }
        return $updated;
    }

    /**
     * التحقق من PIN مع تتبع المحاولات الفاشلة وقفل مؤقت
     */
    public function verifyPin(int $cardId, string $pin): bool
    {
        $card = $this->cardRepo->findById($cardId);
        if (!$card) {
            throw ValidationException::withMessages(['card' => 'Card not found.']);
        }

        if ($card->status !== 'active') {
            throw ValidationException::withMessages(['card' => 'Card is not active.']);
        }

        $attemptsKey = "pin_attempts:card:{$cardId}";
        $attempts = $this->cacheRepo->get($attemptsKey, 0);

        if ($attempts >= $this->maxPinAttempts) {
            // قفل البطاقة مؤقتاً
            $this->updateCardStatus($cardId, 'blocked');
            throw ValidationException::withMessages(['pin' => 'Too many failed attempts. Card has been blocked.']);
        }

        $isValid = $this->cardRepo->verifyPin($cardId, $pin);
        if ($isValid) {
            // إعادة تعيين المحاولات عند النجاح
            $this->cacheRepo->forget($attemptsKey);
            return true;
        }

        // تسجيل محاولة فاشلة
        $newAttempts = $attempts + 1;
        $this->cacheRepo->put($attemptsKey, $newAttempts, $this->pinLockoutSeconds);

        // تسجيل الحدث في AuditLog (اختياري)
        $this->auditLogRepo->create(
            action: 'pin_verification_failed',
            entity: 'card',
            entityId: $cardId,
            userId: $card->wallet->user_id ?? null,
            ipAddress: request()->ip(),
            oldData: null,
            newData: ['attempts' => $newAttempts]
        );

        throw ValidationException::withMessages(['pin' => 'Invalid PIN.']);
    }

    /**
     * إعادة تعيين محاولات PIN (مثلاً بعد فتح القفل يدوياً)
     */
    public function resetPinAttempts(int $cardId): void
    {
        $this->cacheRepo->forget("pin_attempts:card:{$cardId}");
    }

    /**
     * التحقق من صلاحية البطاقة (تاريخ الانتهاء)
     */
    public function isExpired(int $cardId): bool
    {
        return $this->cardRepo->isExpired($cardId);
    }

    /**
     * التحقق من أن البطاقة نشطة
     */
    public function isActive(int $cardId): bool
    {
        return $this->cardRepo->isActive($cardId);
    }

    /**
     * حذف بطاقة (نادراً ما تستخدم، يفضل تعطيلها)
     */
    public function deleteCard(int $cardId): bool
    {
        $card = $this->cardRepo->findById($cardId);
        if (!$card) {
            throw ValidationException::withMessages(['card' => 'Card not found.']);
        }

        $deleted = $this->cardRepo->delete($cardId);
        if ($deleted) {
            $this->auditLogRepo->create(
                action: 'card_deleted',
                entity: 'card',
                entityId: $cardId,
                userId: $card->wallet->user_id ?? null,
                ipAddress: request()->ip(),
                oldData: $card->toArray(),
                newData: null
            );
        }
        return $deleted;
    }
}