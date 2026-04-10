<?php

namespace App\Http\Controllers\Api;

use App\Services\UserManagement\KycService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class KycController extends BaseApiController
{
    public function __construct(protected KycService $kycService) {}

    /**
     * عرض حالة KYC للمستخدم الحالي.
     */
    public function status(Request $request)
    {
        $user = $request->get('auth_user');
        $status = $this->kycService->getKycStatus($user->id);
        return $this->successResponse($status);
    }

    /**
     * تقديم طلب KYC جديد.
     */
    public function submit(Request $request)
    {
        $request->validate([
            'id_type'        => 'required|string|in:passport,national_id,driving_license',
            'id_number'      => 'required|string|max:50',
            'id_expiry_date' => 'required|date|after:today',
        ]);

        $user = $request->get('auth_user');

        try {
            $kyc = $this->kycService->submitKyc($user->id, $request->only([
                'id_type', 'id_number', 'id_expiry_date'
            ]));
            return $this->successResponse($kyc, 'KYC submitted successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}