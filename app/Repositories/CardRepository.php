<?php

namespace App\Repositories;

use App\Models\Card;
use App\Contracts\Repositories\CardRepositoryInterface;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Hash;

class CardRepository implements CardRepositoryInterface
{
    // ========== RETRIEVAL ==========
    public function findById(int $id, array $with = []): ?Card
    {
        return Card::with($with)->find($id);
    }

    public function getByNfcUid(string $nfcUid, array $with = []): ?Card
    {
        return Card::with($with)->where('nfc_uid', $nfcUid)->first();
    }

    public function getByCardNumber(string $cardNumber, array $with = []): ?Card
    {
        return Card::with($with)->where('card_number', $cardNumber)->first();
    }

    public function getByWalletId(int $walletId, array $with = []): Collection
    {
        return Card::with($with)->where('wallet_id', $walletId)->get();
    }

    public function getByAgentId(int $agentId, int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        return Card::with($with)
            ->where('agent_id', $agentId)
            ->latest('id')
            ->paginate($perPage);
    }

    /**
     * Get all cards with optional status filter and eager loading.
     */
    public function getAll(array $filters = [], int $perPage = 20, array $with = []): LengthAwarePaginator
    {
        $query = Card::with($with);

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }
        if (!empty($filters['wallet_id'])) {
            $query->where('wallet_id', $filters['wallet_id']);
        }

        return $query->latest('id')->paginate($perPage);
    }

    public function getActive(array $with = []): Collection
    {
        return Card::with($with)->where('status', 'active')->get();
    }

    public function getExpired(array $with = []): Collection
    {
        return Card::with($with)->where('status', 'expired')->get();
    }

    public function isActive(int $id): bool
    {
        return optional($this->findById($id))->status === 'active';
    }

    public function isExpired(int $id): bool
    {
        $card = $this->findById($id);
        if (!$card || !$card->expiry_date) {
            return false;
        }
        return $card->expiry_date->isPast();
    }

    // ========== PIN OPERATIONS ==========
    public function verifyPin(int $id, string $pin): bool
    {
        $card = $this->findById($id);
        return $card && Hash::check($pin, $card->pin_hash);
    }

    public function setPin(int $id, string $pin): bool
    {
        $card = $this->findById($id);
        return $card ? $card->update(['pin_hash' => Hash::make($pin)]) : false;
    }

    // ========== WRITE OPERATIONS ==========
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

    // ========== CHECKS ==========
    public function existsByNfcUid(string $nfcUid): bool
    {
        return Card::where('nfc_uid', $nfcUid)->exists();
    }
}