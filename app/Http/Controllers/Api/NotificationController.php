<?php

namespace App\Http\Controllers\Api;

use App\Services\System\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class NotificationController extends BaseApiController
{
    public function __construct(protected NotificationService $notificationService) {}

    /**
     * عرض إشعارات المستخدم الحالي (مع pagination)
     */
    public function index(Request $request)
    {
        $user = $request->get('auth_user');
        $perPage = $request->input('per_page', 20);
        $notifications = $this->notificationService->getUserNotifications($user->id, $perPage);
        return $this->paginatedResponse($notifications);
    }

    /**
     * عرض الإشعارات غير المقروءة
     */
    public function unread(Request $request)
    {
        $user = $request->get('auth_user');
        $notifications = $this->notificationService->getUnreadNotifications($user->id);
        return $this->successResponse($notifications);
    }

    /**
     * عدد الإشعارات غير المقروءة
     */
    public function countUnread(Request $request)
    {
        $user = $request->get('auth_user');
        $count = $this->notificationService->countUnread($user->id);
        return $this->successResponse(['unread_count' => $count]);
    }

    /**
     * عرض إشعار محدد (اختياري)
     */
    public function show($id, Request $request)
    {
        $user = $request->get('auth_user');
        // يمكن جلب الإشعار من الـ Repository مباشرة (غير موجود في الخدمة الحالية، نضيفه)
        // للتبسيط نستخدم دالة مساعدة من الـ Repository.
        $notification = app(\App\Contracts\Repositories\NotificationRepositoryInterface::class)->findById($id);
        if (!$notification || $notification->user_id !== $user->id) {
            return $this->errorResponse('Notification not found.', 404);
        }
        return $this->successResponse($notification->toArray());
    }

    /**
     * تعليم إشعار كمقروء
     */
    public function markAsRead($id, Request $request)
    {
        $user = $request->get('auth_user');
        try {
            $this->notificationService->markAsRead($id, $user->id);
            return $this->successResponse(null, 'Notification marked as read.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * تعليم جميع الإشعارات كمقروءة
     */
    public function markAllAsRead(Request $request)
    {
        $user = $request->get('auth_user');
        $count = $this->notificationService->markAllAsRead($user->id);
        return $this->successResponse(['marked_count' => $count], 'All notifications marked as read.');
    }

    /**
     * حذف إشعار
     */
    public function destroy($id, Request $request)
    {
        $user = $request->get('auth_user');
        try {
            $this->notificationService->deleteNotification($id, $user->id);
            return $this->successResponse(null, 'Notification deleted.');
        } catch (ValidationException $e) {
            return $this->errorResponse($e->getMessage(), 422, $e->errors());
        }
    }

    /**
     * حذف جميع الإشعارات
     */
    public function destroyAll(Request $request)
    {
        $user = $request->get('auth_user');
        $count = $this->notificationService->deleteAllForUser($user->id);
        return $this->successResponse(['deleted_count' => $count], 'All notifications deleted.');
    }
}