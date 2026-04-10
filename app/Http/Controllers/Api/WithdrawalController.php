<?php

namespace App\Http\Controllers\Api;

use App\Services\FinancialSystem\WithdrawalService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WithdrawalController extends BaseApiController
{
    public function __construct(protected WithdrawalService $withdrawalService) {}

    /**
     * عرض سحوبات المستخدم الحالي
     */
    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $wallet = $user->wallet;
        if (!$wallet) {
            return $this->errorResponse('Wallet not found.', 404);
        }

        $perPage = $request->input('per_page', 20);
        $withdrawals = $this->withdrawalService->getWithdrawalsByWallet($wallet->id, $perPage);
        return $this->successResponse($withdrawals);
    }

    /**
     * عرض تفاصيل سحب محدد
     */
    public function show($id, Request $request)
    {
        $user = $request->get('auth_user');
        $withdrawal = $this->withdrawalService->getWithdrawal($id);

        if (!$withdrawal || $withdrawal['wallet_id'] !== $user->wallet?->id) {
            return $this->errorResponse('Withdrawal not found.', 404);
        }

        return $this->successResponse($withdrawal);
    }

    /**
     * طلب سحب جديد
     */
    public function request(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = $request->get('auth_user');
        $wallet = $user->wallet;
        if (!$wallet) {
            return $this->errorResponse('Wallet not found.', 404);
        }

        try {
            $result = $this->withdrawalService->requestWithdrawal(
                walletId: $wallet->id,
                agentId: $user->id,
                requestedAmount: $request->amount
            );
            // ملاحظة: في الإنتاج يجب عدم إعادة verification_code، بل إرساله عبر SMS/Email
            return $this->successResponse($result, 'Withdrawal requested. Use the verification code to confirm.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تأكيد السحب باستخدام رمز التحقق
     */
    public function confirm(Request $request)
    {
        $request->validate([
            'withdrawal_id' => 'required|integer',
            'code'          => 'required|string',
        ]);

        try {
            $result = $this->withdrawalService->confirmWithdrawal(
                withdrawalId: $request->withdrawal_id,
                code: $request->code
            );
            return $this->successResponse($result, 'Withdrawal confirmed and processed.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * إلغاء طلب سحب (قبل التأكيد)
     */
    public function cancel($id, Request $request)
    {
        $user = $request->get('auth_user');
        $withdrawal = $this->withdrawalService->getWithdrawal($id);

        if (!$withdrawal || $withdrawal['agent_id'] !== $user->id) {
            return $this->errorResponse('Withdrawal not found or access denied.', 404);
        }

        try {
            $this->withdrawalService->cancelWithdrawal($id, 'user_cancelled');
            return $this->successResponse(null, 'Withdrawal cancelled.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}