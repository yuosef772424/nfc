<?php

namespace App\Contracts\Repositories;

use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;

interface UserRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function findById(int $id): ?User;
    public function findByUuid(string $uuid): ?User;
    public function findByPhone(string $phone): ?User;
    public function findByEmail(string $email): ?User;
    public function findWithRelations(int $id, array $relations): ?User;
    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;
    public function getByType(string $userType, int $perPage = 20): LengthAwarePaginator;
    public function getActiveUsers(int $perPage = 20): LengthAwarePaginator;

    // --- الدوال المنقولة من الموديل (النطاقات والدوال المساعدة) ---
    public function getVerified(): Collection;                   
    public function getAgents(): Collection;                      
    public function getMerchants(): Collection;                   // كانت scopeMerchants
    public function isAgent(int $id): bool;                       // كانت isAgent
    public function isMerchant(int $id): bool;                    // كانت isMerchant
    public function isVerified(int $id): bool;                    // كانت isVerified
    public function isSuspended(int $id): bool;                   // كانت isSuspended

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------

    public function create(array $data): User;
    public function update(int $id, array $data): bool;
    public function updateStatus(int $id, string $status): bool;
    public function markAsVerified(int $id): bool;
    public function delete(int $id): bool;

    // ---------------------------------------------------------------
    // Checks
    // ---------------------------------------------------------------

    public function existsByPhone(string $phone): bool;
    public function existsByEmail(string $email): bool;
    public function countByType(string $userType): int;
}