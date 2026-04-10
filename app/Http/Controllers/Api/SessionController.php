<?php

namespace App\Http\Controllers\Api;

use App\Services\Auth\SessionService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class SessionController extends BaseApiController
{
    public function __construct(protected SessionService $sessionService) {}

    /**
     * عرض جميع جلسات المستخدم النشطة.
     */
    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $sessions = $this->sessionService->getUserActiveSessions($user->id);
        return $this->successResponse($sessions);
    }

    /**
     * عرض جميع جلسات المستخدم (بما فيها المنتهية).
     */
    public function all(Request $request)
    {
        $user = $request->get('auth_user');
        $sessions = $this->sessionService->getAllUserSessions($user->id);
        return $this->successResponse($sessions);
    }

    /**
     * إلغاء جلسة محددة.
     */
    public function revoke($sessionId, Request $request)
    {
        $user = $request->get('auth_user');
        try {
            $this->sessionService->revokeSession($sessionId, $user->id);
            return $this->successResponse(null, 'Session revoked successfully.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * إلغاء جميع الجلسات الأخرى (تسجيل خروج من الأجهزة الأخرى).
     */
    public function revokeOthers(Request $request)
    {
        $user = $request->get('auth_user');
        $token = $request->bearerToken();
        $tokenHash = hash('sha256', $token);

        try {
            $count = $this->sessionService->revokeOtherSessions($user->id, $tokenHash);
            return $this->successResponse(['revoked_count' => $count], 'Other sessions revoked.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * إلغاء جميع الجلسات (تسجيل خروج كامل من كل الأجهزة).
     */
    public function revokeAll(Request $request)
    {
        $user = $request->get('auth_user');
        $count = $this->sessionService->revokeAllSessions($user->id);
        return $this->successResponse(['revoked_count' => $count], 'All sessions revoked.');
    }
}