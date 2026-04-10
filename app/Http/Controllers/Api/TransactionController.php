<?php

namespace App\Http\Controllers\Api;

use App\Services\FinancialSystem\TransactionService;
use App\Http\Requests\FinancialRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class TransactionController extends BaseApiController
{
    public function __construct(protected TransactionService $transactionService) {}

    /**
     * عرض معاملات المستخدم الحالي (مع إمكانية الفلترة)
     */
    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $wallet = $user->wallet;

        if (!$wallet) {
            return $this->errorResponse('Wallet not found.', 404);
        }

        $filters = $request->only(['type', 'status']);
        $perPage = $request->input('per_page', 20);

        $transactions = $this->transactionService->getTransactionsByWallet(
            walletId: $wallet->id,
            filters: $filters,
            perPage: $perPage
        );

        return $this->successResponse($transactions);
    }

    /**
     * عرض المعاملات الصادرة من محفظة المستخدم
     */
    public function sent(Request $request)
    {
        $user = $request->get('auth_user');
        $wallet = $user->wallet;

        if (!$wallet) {
            return $this->errorResponse('Wallet not found.', 404);
        }

        $perPage = $request->input('per_page', 20);
        $transactions = $this->transactionService->getSentTransactions($wallet->id, $perPage);

        return $this->successResponse($transactions);
    }

    /**
     * عرض المعاملات الواردة إلى محفظة المستخدم
     */
    public function received(Request $request)
    {
        $user = $request->get('auth_user');
        $wallet = $user->wallet;

        if (!$wallet) {
            return $this->errorResponse('Wallet not found.', 404);
        }

        $perPage = $request->input('per_page', 20);
        $transactions = $this->transactionService->getReceivedTransactions($wallet->id, $perPage);

        return $this->successResponse($transactions);
    }

    /**
     * عرض تفاصيل معاملة محددة بواسطة UUID
     */
    public function show(string $uuid)
    {
        $transaction = $this->transactionService->getTransactionByUuid($uuid);

        if (!$transaction) {
            return $this->errorResponse('Transaction not found.', 404);
        }

        return $this->successResponse($transaction);
    }

    /**
     * إنشاء معاملة جديدة (تحويل بين محفظتين)
     */
    public function store(Request $request)
    {
        $rules = FinancialRequests::storeTransaction();
        $validated = $request->validate($rules);

        try {
            $result = $this->transactionService->createTransaction(
                senderWalletId: $validated['sender_wallet_id'],
                receiverWalletId: $validated['receiver_wallet_id'],
                amount: $validated['amount'],
                type: $validated['type'],
                description: $request->input('description', '')
            );

            return $this->successResponse($result, 'Transaction created successfully.', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تحديث حالة معاملة (للمسؤولين)
     */
    public function updateStatus(Request $request, int $id)
    {
        $rules = FinancialRequests::updateTransaction();
        $validated = $request->validate($rules);

        try {
            $updated = $this->transactionService->updateTransactionStatus(
                transactionId: $id,
                status: $validated['status'],
                failureReason: $validated['failure_reason'] ?? null
            );

            if ($updated) {
                return $this->successResponse(null, 'Transaction status updated.');
            }

            return $this->errorResponse('Failed to update transaction status.', 500);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * استرداد معاملة (Refund) – للمسؤولين
     */
    public function refund(Request $request, int $id)
    {
        $request->validate([
            'reason' => 'nullable|string|max:255',
        ]);

        try {
            $result = $this->transactionService->refundTransaction(
                originalTransactionId: $id,
                reason: $request->input('reason', '')
            );

            return $this->successResponse($result, 'Transaction refunded successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}