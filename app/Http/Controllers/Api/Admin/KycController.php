<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\UserManagement\KycService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KycController extends BaseApiController
{
    public function __construct(protected KycService $kycService) {}

    /**
     * عرض طلبات KYC المعلقة
     */
    public function pending(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $pending = $this->kycService->getPendingRequests($perPage);
        return $this->successResponse($pending);
    }

    /**
     * عرض طلبات KYC التي تم التحقق منها
     */
    public function verified(Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $verified = $this->kycService->getVerifiedRequests($perPage);
        return $this->successResponse($verified);
    }

    /**
     * عرض تفاصيل KYC لمستخدم محدد
     */
    public function show($userId)
    {
        $status = $this->kycService->getKycStatus($userId);
        if (!$status) {
            return $this->errorResponse('KYC record not found.', 404);
        }
        return $this->successResponse($status);
    }

    /**
     * الموافقة على طلب KYC
     */
    public function approve($userId, Request $request)
    {
        $admin = $request->get('auth_user');
        try {
            $this->kycService->approveKyc($userId, $admin->id);
            return $this->successResponse(null, 'KYC approved successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * رفض طلب KYC
     */
    public function reject($userId, Request $request)
    {
        $request->validate([
            'reason' => 'required|string|max:500',
        ]);

        $admin = $request->get('auth_user');
        try {
            $this->kycService->rejectKyc($userId, $admin->id, $request->input('reason'));
            return $this->successResponse(null, 'KYC rejected.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}