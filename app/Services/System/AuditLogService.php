<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\System\AuditLogService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class AuditLogController extends BaseApiController
{
    public function __construct(protected AuditLogService $auditLogService) {}

    /**
     * عرض سجل التدقيق مع فلترة.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['user_id', 'action', 'entity', 'entity_id']);
        $perPage = $request->input('per_page', 20);

        $logs = $this->auditLogService->getLogs($filters, $perPage, ['user']);
        return $this->paginatedResponse($logs);
    }

    /**
     * عرض سجل التدقيق لمستخدم معين.
     */
    public function byUser($userId, Request $request)
    {
        $perPage = $request->input('per_page', 20);
        $logs = $this->auditLogService->getLogsByUser($userId, $perPage);
        return $this->paginatedResponse($logs);
    }

    /**
     * عرض سجل التدقيق لكيان معين.
     */
    public function byEntity(Request $request)
    {
        $request->validate([
            'entity'    => 'required|string',
            'entity_id' => 'required|integer',
        ]);

        $perPage = $request->input('per_page', 20);
        $logs = $this->auditLogService->getLogsByEntity(
            $request->entity,
            $request->entity_id,
            $perPage
        );
        return $this->paginatedResponse($logs);
    }

    /**
     * إحصائيات الإجراءات الأكثر شيوعاً.
     */
    public function actionStats(Request $request)
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->subDays(30);
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : Carbon::now();
        $limit = $request->input('limit', 10);

        $stats = $this->auditLogService->actionStats($startDate, $endDate, $limit);
        return $this->successResponse($stats);
    }

    /**
     * المستخدمين الأكثر نشاطاً.
     */
    public function topUsers(Request $request)
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->subDays(30);
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : Carbon::now();
        $limit = $request->input('limit', 10);

        $users = $this->auditLogService->topActiveUsers($startDate, $endDate, $limit);
        return $this->successResponse($users);
    }

    /**
     * تصدير السجل إلى CSV.
     */
    public function export(Request $request)
    {
        $filters = $request->only(['user_id', 'action', 'entity', 'entity_id']);
        $csv = $this->auditLogService->exportToCsv($filters);

        $filename = 'audit_logs_' . now()->format('Y-m-d_His') . '.csv';
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ]);
    }
}