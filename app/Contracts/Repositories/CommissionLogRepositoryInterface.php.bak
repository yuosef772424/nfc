<?php

namespace App\Contracts\Repositories;

use App\Models\CommissionLog;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
interface CommissionLogRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------
    public function findById(int $id): ?CommissionLog;
    public function getByRecipient(int $recipientId, string $recipientType, int $perPage = 20): LengthAwarePaginator;
    public function getByReference(string $referenceType, int $referenceId): Collection;
    public function getPendingByAgent(int $agentId): Collection;
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    // --- الدوال المنقولة من الموديل (النطاقات والمساعدة) ---
    public function getPending(): Collection;                          // كانت scopePending
    public function getForAgent(int $agentId): Collection;             // كانت scopeForAgent
    public function getReference(int $logId): ?Model;                  // كانت reference()
    public function markPaid(int $id): bool;                           // موجودة أصلاً لكن نؤكد
    public function markCancelled(int $id): bool;                      // موجودة

    // ---------------------------------------------------------------
    // Aggregates
    // ---------------------------------------------------------------
    public function sumPendingByAgent(int $agentId): float;
    public function sumPaidByAgent(int $agentId): float;

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------
    public function create(array $data): CommissionLog;
    public function settleAllPendingForAgent(int $agentId): int;
}