<?php

namespace App\Http\Controllers\Api\Admin;

use App\Http\Controllers\Api\BaseApiController;
use App\Contracts\Repositories\UserRepositoryInterface;
use App\Contracts\Repositories\WalletRepositoryInterface;
use App\Contracts\Repositories\TransactionRepositoryInterface;
use App\Contracts\Repositories\WithdrawalRepositoryInterface;
use App\Services\UserManagement\KycService;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class DashboardController extends BaseApiController
{
    public function __construct(
        protected UserRepositoryInterface $userRepo,
        protected WalletRepositoryInterface $walletRepo,
        protected TransactionRepositoryInterface $transactionRepo,
        protected WithdrawalRepositoryInterface $withdrawalRepo,
        protected KycService $kycService,
    ) {}

    /**
     * عرض ملخص لوحة التحكم مع إحصائيات سريعة.
     */
    public function index()
    {
        $today = Carbon::today();
        $startOfMonth = Carbon::now()->startOfMonth();
        $endOfMonth = Carbon::now()->endOfMonth();

        // إحصائيات المستخدمين
        $totalUsers = $this->userRepo->getAll([], 1)->total();
        $totalAgents = $this->userRepo->countByType('agent');
        $totalMerchants = $this->userRepo->countByType('merchant');
        $newUsersToday = $this->userRepo->getAll(['created_at_from' => $today], 1)->total();

        // إحصائيات KYC
        $pendingKyc = count($this->kycService->getPendingRequests(1)); // نمرر صفحة صغيرة لنجلب الكل بسرعة
        $verifiedKyc = count($this->kycService->getVerifiedRequests(1));

        // إحصائيات المحافظ
        $wallets = $this->walletRepo->getAll([], 10000);
        $totalBalance = $wallets->sum('available_balance');
        $totalPendingBalance = $wallets->sum('pending_balance');

        // إحصائيات المعاملات (شهرية)
        $transactionsThisMonth = $this->transactionRepo->getAll([
            'created_at_from' => $startOfMonth,
            'created_at_to'   => $endOfMonth,
        ], 10000);
        $transactionVolume = $transactionsThisMonth->sum('amount');
        $transactionCount = $transactionsThisMonth->count();

        // إحصائيات السحوبات
        $pendingWithdrawals = $this->withdrawalRepo->getPending();
        $pendingWithdrawalsCount = $pendingWithdrawals->count();
        $pendingWithdrawalsAmount = $pendingWithdrawals->sum('total_amount');

        $completedWithdrawalsThisMonth = $this->withdrawalRepo->getCompleted()
            ->whereBetween('completed_at', [$startOfMonth, $endOfMonth]);
        $withdrawalVolumeThisMonth = $completedWithdrawalsThisMonth->sum('total_amount');

        return $this->successResponse([
            'users' => [
                'total'        => $totalUsers,
                'agents'       => $totalAgents,
                'merchants'    => $totalMerchants,
                'new_today'    => $newUsersToday,
            ],
            'kyc' => [
                'pending'  => $pendingKyc,
                'verified' => $verifiedKyc,
            ],
            'wallets' => [
                'total_balance'         => round($totalBalance, 2),
                'total_pending_balance' => round($totalPendingBalance, 2),
            ],
            'transactions' => [
                'this_month_count'  => $transactionCount,
                'this_month_volume' => round($transactionVolume, 2),
            ],
            'withdrawals' => [
                'pending_count'     => $pendingWithdrawalsCount,
                'pending_amount'    => round($pendingWithdrawalsAmount, 2),
                'this_month_volume' => round($withdrawalVolumeThisMonth, 2),
            ],
        ]);
    }
}