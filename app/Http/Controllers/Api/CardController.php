<?php

namespace App\Http\Controllers\Api;

use App\Services\DeviceAndCard\CardService;
use App\Http\Requests\DeviceAndCardRequests;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class CardController extends BaseApiController
{
    public function __construct(protected CardService $cardService) {}

    /**
     * إنشاء بطاقة جديدة
     */
    public function store(Request $request)
    {
        $rules = DeviceAndCardRequests::storeCard();
        $validated = $request->validate($rules);

        try {
            $card = $this->cardService->createCard($validated);
            return $this->successResponse($card, 'Card created successfully.', 201);
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * عرض بطاقة محددة
     */
    public function show($id)
    {
        $card = $this->cardService->getCard($id, ['wallet', 'agent']);
        if (!$card) {
            return $this->errorResponse('Card not found.', 404);
        }
        return $this->successResponse($card);
    }

    /**
     * عرض جميع بطاقات المستخدم الحالي (عبر محفظته)
     */
    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $wallet = $user->wallet;
        if (!$wallet) {
            return $this->errorResponse('Wallet not found.', 404);
        }

        $cards = $this->cardService->getCardsByWallet($wallet->id);
        return $this->successResponse($cards);
    }

    /**
     * تحديث حالة البطاقة
     */
    public function updateStatus(Request $request, $id)
    {
        $rules = DeviceAndCardRequests::updateCard();
        $validated = $request->validate($rules);

        try {
            $this->cardService->updateCardStatus($id, $validated['status']);
            return $this->successResponse(null, 'Card status updated.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تعيين أو تحديث PIN للبطاقة
     */
    public function setPin(Request $request, $id)
    {
        $rules = DeviceAndCardRequests::updateCardPin();
        $validated = $request->validate($rules);

        try {
            $this->cardService->setPin($id, $validated['pin']);
            return $this->successResponse(null, 'PIN set successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * التحقق من PIN البطاقة
     */
    public function verifyPin(Request $request, $id)
    {
        $request->validate(['pin' => 'required|string|min:4|max:8']);

        try {
            $this->cardService->verifyPin($id, $request->input('pin'));
            return $this->successResponse(null, 'PIN verified.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * حذف بطاقة
     */
    public function destroy($id)
    {
        try {
            $this->cardService->deleteCard($id);
            return $this->successResponse(null, 'Card deleted.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }
}