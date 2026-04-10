<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\FinancialSystem\WithdrawalService;
use Illuminate\Http\Request;

class WithdrawalController extends BaseApiController
{
    public function __construct(protected WithdrawalService $withdrawalService) {}

    /**
     * عرض جميع السحوبات المعلقة
     */
    public function pending()
    {
        $withdrawals = $this->withdrawalService->getPendingWithdrawals();
        return $this->successResponse($withdrawals);
    }

    /**
     * عرض سحوبات وكيل معين
     */
    public function byAgent($agentId, Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $withdrawals = $this->withdrawalService->getWithdrawalsByAgent($agentId, $perPage);
        return $this->successResponse($withdrawals);
    }

    /**
     * إلغاء السحوبات المنتهية تلقائياً
     */
    public function expirePending()
    {
        $count = $this->withdrawalService->expireExpiredPendingWithdrawals();
        return $this->successResponse(['expired_count' => $count], 'Expired withdrawals processed.');
    }
}