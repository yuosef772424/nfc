<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\System\ReportService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ReportController extends BaseApiController
{
    public function __construct(protected ReportService $reportService) {}

    /**
     * ملخص مالي للفترة المحددة.
     */
    public function financialSummary(Request $request)
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : Carbon::now()->endOfDay();

        $summary = $this->reportService->financialSummary($startDate, $endDate);
        return $this->successResponse($summary);
    }

    /**
     * حجم المعاملات اليومي (للرسم البياني).
     */
    public function dailyVolume(Request $request)
    {
        $days = $request->input('days', 30);
        $data = $this->reportService->dailyTransactionVolume((int) $days);
        return $this->successResponse($data);
    }

    /**
     * أداء وكيل معين.
     */
    public function agentPerformance($agentId, Request $request)
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : Carbon::now()->endOfDay();

        try {
            $performance = $this->reportService->agentPerformance($agentId, $startDate, $endDate);
            return $this->successResponse($performance);
        } catch (\InvalidArgumentException $e) {
            return $this->errorResponse($e->getMessage(), 404);
        }
    }

    /**
     * قائمة أفضل الوكلاء.
     */
    public function topAgents(Request $request)
    {
        $limit = $request->input('limit', 10);
        $orderBy = $request->input('order_by', 'commission');
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : Carbon::now()->endOfDay();

        $agents = $this->reportService->topAgents($limit, $orderBy, $startDate, $endDate);
        return $this->successResponse($agents);
    }

    /**
     * إحصائيات النظام العامة.
     */
    public function systemStats()
    {
        $stats = $this->reportService->systemStats();
        return $this->successResponse($stats);
    }

    /**
     * تقرير الأرباح الشهرية.
     */
    public function monthlyProfit(Request $request)
    {
        $months = $request->input('months', 12);
        $profit = $this->reportService->monthlyProfitReport((int) $months);
        return $this->successResponse($profit);
    }

    /**
     * تصدير ملخص مالي إلى CSV.
     */
    public function exportFinancialSummary(Request $request)
    {
        $startDate = $request->has('start_date') ? Carbon::parse($request->start_date) : Carbon::now()->startOfMonth();
        $endDate = $request->has('end_date') ? Carbon::parse($request->end_date) : Carbon::now()->endOfDay();

        $summary = $this->reportService->financialSummary($startDate, $endDate);
        $flattened = collect([
            [
                'Metric' => 'Period Start',
                'Value' => $summary['period']['start'],
            ],
            [
                'Metric' => 'Period End',
                'Value' => $summary['period']['end'],
            ],
            [
                'Metric' => 'Deposits Count',
                'Value' => $summary['deposits']['count'],
            ],
            [
                'Metric' => 'Deposits Total',
                'Value' => $summary['deposits']['total'],
            ],
            // ... يمكن إضافة المزيد
        ]);

        $csv = $this->reportService->exportToCsv($flattened, 'financial_summary.csv');
        return response($csv, 200, [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="financial_summary.csv"',
        ]);
    }
}