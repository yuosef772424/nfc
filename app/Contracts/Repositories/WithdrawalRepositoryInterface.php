<?php

namespace App\Contracts\Repositories;

use App\Models\Withdrawal;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface WithdrawalRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function findById(int $id): ?Withdrawal;
    public function getByWalletId(int $walletId, int $perPage = 20): LengthAwarePaginator;
    public function getByAgentId(int $agentId, int $perPage = 20): LengthAwarePaginator;
    public function getByStatus(string $status, int $perPage = 20): LengthAwarePaginator;
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;
    public function getPendingExpired(): Collection;  // كانت scopeExpired

    // --- الدوال المنقولة من الموديل (النطاقات والدوال المساعدة) ---
    public function getPending(): Collection;                      // كانت scopePending
    public function getCompleted(): Collection;                    // كانت scopeCompleted
    public function isExpired(int $id): bool;                      // كانت isExpired
    public function isPending(int $id): bool;                      // كانت isPending
    public function verifyCode(int $id, string $code): bool;       // كانت verifyCode
    public function markCompleted(int $id): bool;                  // كانت markCompleted (موجودة في الواجهة، نؤكد)

    // ---------------------------------------------------------------
    // Aggregates
    // ---------------------------------------------------------------

    public function sumByAgent(int $agentId, string $status = 'completed'): float;
    public function countByAgent(int $agentId, string $status): int;

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------

    public function create(array $data): Withdrawal;
    public function updateStatus(int $id, string $status): bool;
    public function markFailed(int $id): bool;
    public function markCancelled(int $id): bool;
    public function expireOldPending(): int;
}