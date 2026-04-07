<?php

namespace App\Repositories;

use App\Models\Card;
use App\Contracts\Repositories\CardRepositoryInterface;
use App\Contracts\Repositories\AppConfigRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class CardRepository implements CardRepositoryInterface
{
    protected AppConfigRepositoryInterface $configRepo;

    public function __construct(AppConfigRepositoryInterface $configRepo)
    {
        $this->configRepo = $configRepo;
    }

    // ------------------- دوال مساعدة لقراءة الثوابت من app_config -------------------
    protected function getCardStatusConstant(string $statusKey): ?string
    {
        return $this->configRepo->getValue('constant', "card_status.{$statusKey}");
    }

    // ------------------- Retrieval -------------------
    public function findById(int $id): ?Card
    {
        return Card::find($id);
    }

    public function findByNfcUid(string $nfcUid): ?Card
    {
        return Card::where('nfc_uid', $nfcUid)->first();
    }

    public function findByCardNumber(string $cardNumber): ?Card
    {
        return Card::where('card_number', $cardNumber)->first();
    }

    public function getByWalletId(int $walletId): Collection
    {
        return Card::where('wallet_id', $walletId)->get();
    }

    public function getByAgentId(int $agentId, int $perPage = 20): LengthAwarePaginator
    {
        return Card::where('agent_id', $agentId)->paginate($perPage);
    }

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator
    {
        $query = Card::query();
        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        return $query->paginate($perPage);
    }

    public function getActive(): Collection
    {
        $activeStatus = $this->getCardStatusConstant('active') ?? 'active';
        return Card::where('status', $activeStatus)->get();
    }

    public function getExpired(): Collection
    {
        $expiredStatus = $this->getCardStatusConstant('expired') ?? 'expired';
        return Card::where('status', $expiredStatus)->get();
    }

    public function isActive(int $id): bool
    {
        $card = $this->findById($id);
        if (!$card) return false;
        $activeStatus = $this->getCardStatusConstant('active') ?? 'active';
        return $card->status === $activeStatus;
    }

    public function isExpired(int $id): bool
    {
        $card = $this->findById($id);
        return $card && optional($card->expiry_date)->isPast() ?? false;
    }

    // ------------------- PIN Operations (بدون تتبع محاولات أو قفل) -------------------
    public function verifyPin(int $id, string $pin): bool
    {
        $card = $this->findById($id);
        return $card && Hash::check($pin, $card->pin_hash);
    }

    
public function setPin(int $id, string $pin): bool
{
    $card = $this->findById($id);
    if (!$card) {
        return false;
    }

    return $card->update(['pin_hash' => Hash::make($pin)]);
}

    public function updatePin(int $id, string $pinHash): bool
    {
        $card = $this->findById($id);
        return $card ? $card->update(['pin_hash' => $pinHash]) : false;
    }

    // ------------------- Write -------------------
    public function create(array $data): Card
    {
        return Card::create($data);
    }

    public function updateStatus(int $id, string $status): bool
    {
        $card = $this->findById($id);
        return $card ? $card->update(['status' => $status]) : false;
    }

    public function delete(int $id): bool
    {
        $card = $this->findById($id);
        return $card ? (bool) $card->delete() : false;
    }

    // ------------------- Checks -------------------
    public function existsByNfcUid(string $nfcUid): bool
    {
        return Card::where('nfc_uid', $nfcUid)->exists();
    }
}