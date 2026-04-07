<?php

namespace App\Contracts\Repositories;

use App\Models\Card;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface CardRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------
    public function findById(int $id): ?Card;
    public function findByNfcUid(string $nfcUid): ?Card;
    public function findByCardNumber(string $cardNumber): ?Card;
    public function getByWalletId(int $walletId): Collection;
    public function getByAgentId(int $agentId, int $perPage = 20): LengthAwarePaginator;
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    // --- الدوال المنقولة من الموديل (بدل scopes) ---
    public function getActive(): Collection;                 // كانت scopeActive
    public function getExpired(): Collection;               // كانت scopeExpired

    // --- دوال التحقق المنقولة ---
    public function isActive(int $id): bool;                // كانت isActive في الموديل
    public function isExpired(int $id): bool;               // كانت isExpired في الموديل
    public function verifyPin(int $id, string $pin): bool;  // كانت verifyPin في الموديل
    public function setPin(int $id, string $pin): void;      // كانت setPin في الموديل

    // ---------------------------------------------------------------
    // Write (الموجودة أصلاً)
    // ---------------------------------------------------------------
    public function create(array $data): Card;
    public function updateStatus(int $id, string $status): bool;
    public function updatePin(int $id, string $pinHash): bool;
    public function incrementPinAttempts(int $id): int;

    public function delete(int $id): bool;

    // ---------------------------------------------------------------
    // Checks (باقي الدوال)
    // ---------------------------------------------------------------

    public function existsByNfcUid(string $nfcUid): bool;
}