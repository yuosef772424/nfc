<?php

namespace App\Contracts\Services;

use App\Models\Dispute;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface DisputeServiceInterface
{
    // ---------------------------------------------------------------
    // Lifecycle
    // ---------------------------------------------------------------

    /**
     * فتح نزاع على معاملة
     *
     * @throws \App\Exceptions\Dispute\TransactionNotDisputeableException  (إذا كانت المعاملة غير مكتملة)
     * @throws \App\Exceptions\Dispute\DisputeAlreadyOpenException         (إذا يوجد نزاع مفتوح على نفس المعاملة)
     */
    public function open(int $transactionId, int $raisedBy, string $reason, ?string $description = null): Dispute;

    /**
     * بدء مراجعة النزاع (Admin)
     *
     * @throws \App\Exceptions\Dispute\DisputeNotFoundException
     */
    public function startReview(int $disputeId, int $reviewerId): bool;

    /**
     * حل النزاع لصالح المستخدم — يُعيد المبلغ
     *
     * @throws \App\Exceptions\Dispute\DisputeAlreadyResolvedException
     */
    public function resolve(int $disputeId, string $resolution, int $resolvedBy): bool;

    /**
     * رفض النزاع
     *
     * @throws \App\Exceptions\Dispute\DisputeAlreadyResolvedException
     */
    public function reject(int $disputeId, string $resolution, int $resolvedBy): bool;

    // ---------------------------------------------------------------
    // Retrieval
    // ---------------------------------------------------------------

    public function getById(int $id): ?Dispute;

    public function getByUser(int $userId, int $perPage = 20): LengthAwarePaginator;

    public function getByTransaction(int $transactionId): \Illuminate\Database\Eloquent\Collection;

    public function getUnresolved(int $perPage = 20): LengthAwarePaginator;

    public function getAll(array $filters = [], int $perPage = 20): LengthAwarePaginator;

    // ---------------------------------------------------------------
    // Stats
    // ---------------------------------------------------------------

    public function getStats(): array; // {open, reviewing, resolved, rejected, total}
}
