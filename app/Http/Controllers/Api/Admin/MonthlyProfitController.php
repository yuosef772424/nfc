<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Services\FinancialSystem\MonthlyProfitService;
use Illuminate\Http\Request;
use Carbon\Carbon;

class MonthlyProfitController extends BaseApiController
{
    public function __construct(protected MonthlyProfitService $profitService) {}

    /**
     * عرض أرباح آخر 12 شهراً
     */
    public function index()
    {
        $profits = $this->profitService->getLastTwelveMonthsProfit();
        return $this->successResponse($profits);
    }

    /**
     * حساب إجمالي أرباح شهر محدد
     */
    public function show(string $yearMonth)
    {
        try {
            $carbon = Carbon::createFromFormat('Y-m', $yearMonth);
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid date format. Use YYYY-MM.', 422);
        }

        $totalProfit = $this->profitService->getTotalProfitForMonth($yearMonth);
        return $this->successResponse([
            'year_month'   => $yearMonth,
            'total_profit' => $totalProfit,
        ]);
    }

    /**
     * توزيع أرباح شهر محدد
     */
    public function distribute(string $yearMonth)
    {
        try {
            $carbon = Carbon::createFromFormat('Y-m', $yearMonth);
        } catch (\Exception $e) {
            return $this->errorResponse('Invalid date format. Use YYYY-MM.', 422);
        }

        $result = $this->profitService->distributeMonthlyProfit($yearMonth);
        return $this->successResponse($result, $result['message'] ?? 'Distribution completed.');
    }

    /**
     * توزيع أرباح الشهر السابق (اختصار)
     */
    public function distributePrevious()
    {
        $result = $this->profitService->distributePreviousMonthProfit();
        return $this->successResponse($result, $result['message'] ?? 'Previous month distribution completed.');
    }

    /**
     * ملخص الأرباح (حالي + سابق + توزيعات)
     */
    public function summary()
    {
        $currentMonth = Carbon::now()->format('Y-m');
        $previousMonth = Carbon::now()->subMonth()->format('Y-m');

        $currentProfit = $this->profitService->getTotalProfitForMonth($currentMonth);
        $previousProfit = $this->profitService->getTotalProfitForMonth($previousMonth);

        // يمكنك إضافة منطق لمعرفة حالة التوزيع لكل شهر
        $lastTwelve = $this->profitService->getLastTwelveMonthsProfit();

        return $this->successResponse([
            'current_month' => [
                'year_month'   => $currentMonth,
                'total_profit' => $currentProfit,
            ],
            'previous_month' => [
                'year_month'   => $previousMonth,
                'total_profit' => $previousProfit,
            ],
            'last_twelve_months' => $lastTwelve,
        ]);
    }
}