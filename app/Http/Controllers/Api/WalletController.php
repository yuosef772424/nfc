<?php

namespace App\Http\Controllers\Api;

use App\Services\FinancialSystem\WalletService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class WalletController extends BaseApiController
{
    public function __construct(protected WalletService $walletService) {}

    /**
     * عرض محفظة المستخدم الحالي
     */
    public function show(Request $request)
    {
        $user = $request->get('auth_user');
        $wallet = $this->walletService->getUserWallet($user->id, ['user']);

        if (!$wallet) {
            return $this->errorResponse('Wallet not found.', 404);
        }

        return $this->successResponse($wallet);
    }

    /**
     * إيداع مبلغ في المحفظة (لأغراض إدارية أو اختبارية)
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->get('auth_user');
        $wallet = $this->walletService->getUserWallet($user->id);

        if (!$wallet) {
            return $this->errorResponse('Wallet not found.', 404);
        }

        try {
            $result = $this->walletService->deposit(
                walletId: $wallet['id'],
                amount: $request->amount,
                description: $request->description
            );

            return $this->successResponse($result, 'Deposit successful.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * سحب مبلغ من المحفظة
     */
    public function withdraw(Request $request)
    {
        $request->validate([
            'amount'      => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->get('auth_user');
        $wallet = $this->walletService->getUserWallet($user->id);

        if (!$wallet) {
            return $this->errorResponse('Wallet not found.', 404);
        }

        try {
            $result = $this->walletService->withdraw(
                walletId: $wallet['id'],
                amount: $request->amount,
                description: $request->description
            );

            return $this->successResponse($result, 'Withdrawal successful.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تحويل مبلغ إلى محفظة أخرى
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'to_wallet_id' => 'required|integer|exists:wallets,id',
            'amount'       => 'required|numeric|min:0.01',
            'description'  => 'nullable|string|max:255',
        ]);

        $user = $request->get('auth_user');
        $fromWallet = $this->walletService->getUserWallet($user->id);

        if (!$fromWallet) {
            return $this->errorResponse('Sender wallet not found.', 404);
        }

        if ($fromWallet['id'] == $request->to_wallet_id) {
            return $this->errorResponse('Cannot transfer to the same wallet.', 422);
        }

        try {
            $result = $this->walletService->transfer(
                fromWalletId: $fromWallet['id'],
                toWalletId: $request->to_wallet_id,
                amount: $request->amount,
                description: $request->description
            );

            return $this->successResponse($result, 'Transfer successful.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تسوية رصيد معلق (تحويله إلى الرصيد المتاح)
     */
    public function settlePending(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
        ]);

        $user = $request->get('auth_user');
        $wallet = $this->walletService->getUserWallet($user->id);

        if (!$wallet) {
            return $this->errorResponse('Wallet not found.', 404);
        }

        try {
            $success = $this->walletService->settlePending(
                walletId: $wallet['id'],
                amount: $request->amount
            );

            if ($success) {
                return $this->successResponse(null, 'Pending balance settled successfully.');
            }

            return $this->errorResponse('Failed to settle pending balance.', 500);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}