<?php

namespace App\Contracts\Repositories;

use App\Models\UserKyc;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface UserKycRepositoryInterface
{
    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function findByUserId(int $userId): ?UserKyc;

    public function getPending(int $perPage = 20): LengthAwarePaginator;   // تعوض scopePending

    public function getVerified(int $perPage = 20): LengthAwarePaginator;  // تعوض scopeVerified

    // ---------------------------------------------------------------
    // Write
    // ---------------------------------------------------------------

    public function createOrUpdate(int $userId, array $data): UserKyc;

    public function markVerified(int $userId): bool;

    public function update(int $userId, array $data): bool;

    public function delete(int $userId): bool;

    // ---------------------------------------------------------------
    // Checks 
    // ---------------------------------------------------------------

    public function isVerified(int $userId): bool;    // كانت isVerified

    public function isExpired(int $userId): bool;     // كانت isExpired

    public function exists(int $userId): bool;
}