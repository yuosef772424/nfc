<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\FinancialSystem\CommissionService;
use App\Services\FinancialSystem\TransactionService;
use Illuminate\Http\Request;

class CommissionController extends BaseApiController
{
    public function __construct(
        protected CommissionService $commissionService,
        protected TransactionService $transactionService
    ) {}

    public function settleAgentCommissions(Request $request, $agentId)
    {
        try {
            $result = $this->commissionService->settlePendingCommissionsForAgent($agentId, $this->transactionService);
            return $this->successResponse($result, 'Commissions settled successfully.');
        } catch (\Exception $e) {
            return $this->errorResponse($e->getMessage(), 422);
        }
    }

    public function getAgentCommissionSummary($agentId)
    {
        return $this->successResponse([
            'pending_total' => $this->commissionService->getTotalPendingForAgent($agentId),
            'paid_total'    => $this->commissionService->getTotalPaidForAgent($agentId),
            'pending_logs'  => $this->commissionService->getPendingCommissionsForAgent($agentId),
        ]);
    }
}