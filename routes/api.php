<?php

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\UserProfileController;
use App\Http\Controllers\Api\WalletController;
use App\Http\Controllers\Api\TransactionController;
use App\Http\Controllers\Api\WithdrawalController;
use App\Http\Controllers\Api\CardController;
use App\Http\Controllers\Api\MobileDeviceController;
use App\Http\Controllers\Api\PhysicalDeviceController;
use App\Http\Controllers\Api\NotificationController;
use App\Http\Controllers\Api\SessionController;
use App\Http\Controllers\Api\KycController;
use App\Http\Controllers\Api\MerchantProfileController;
use App\Http\Controllers\Api\AgentProfileController;
use App\Http\Controllers\Api\AppConfigController as PublicAppConfigController;

// Admin Controllers
use App\Http\Controllers\Api\Admin\ReportController;
use App\Http\Controllers\Api\Admin\AuditLogController;
use App\Http\Controllers\Api\Admin\CommissionController;
use App\Http\Controllers\Api\Admin\MonthlyProfitController;
use App\Http\Controllers\Api\Admin\WithdrawalController as AdminWithdrawalController;
use App\Http\Controllers\Api\Admin\KycController as AdminKycController;
use App\Http\Controllers\Api\Admin\MerchantProfileController as AdminMerchantProfileController;
use App\Http\Controllers\Api\Admin\AgentProfileController as AdminAgentProfileController;
use App\Http\Controllers\Api\Admin\AppConfigController as AdminAppConfigController;
use App\Http\Controllers\Api\Admin\DashboardController;

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public Routes
|--------------------------------------------------------------------------
*/
Route::post('register', [AuthController::class, 'register']);
Route::post('login', [AuthController::class, 'login']);
Route::post('password/reset-request', [AuthController::class, 'requestPasswordReset']);
Route::post('password/reset', [AuthController::class, 'resetPassword']);

// Public configuration (app settings for mobile)
Route::get('config', [PublicAppConfigController::class, 'publicConfig']);

/*
|--------------------------------------------------------------------------
| Protected Routes (Require Valid Token)
|--------------------------------------------------------------------------
*/
Route::middleware('auth.mobile')->group(function () {

    // ------------------- Current User -------------------
    Route::get('me', [AuthController::class, 'me']);

    // ------------------- Auth / Session -------------------
    Route::post('logout', [AuthController::class, 'logout']);
    Route::post('token/refresh', [AuthController::class, 'refreshToken']);
    Route::post('email/verification/send', [AuthController::class, 'sendEmailVerification']);
    Route::post('email/verify', [AuthController::class, 'verifyEmail']);

    // ------------------- User Profile -------------------
    Route::get('profile', [UserProfileController::class, 'show']);
    Route::put('profile', [UserProfileController::class, 'update']);
    Route::post('profile/password', [UserProfileController::class, 'changePassword']);
    Route::post('profile/email/change', [UserProfileController::class, 'initiateEmailChange']);
    Route::post('profile/email/confirm', [UserProfileController::class, 'confirmEmailChange']);
    Route::post('profile/phone/change', [UserProfileController::class, 'initiatePhoneChange']);
    Route::post('profile/phone/confirm', [UserProfileController::class, 'confirmPhoneChange']);
    Route::post('profile/deactivate', [UserProfileController::class, 'deactivateAccount']);
    Route::post('profile/delete', [UserProfileController::class, 'deleteAccount']);

    // ------------------- Wallet -------------------
    Route::get('wallet', [WalletController::class, 'show']);
    Route::post('wallet/deposit', [WalletController::class, 'deposit']);
    Route::post('wallet/withdraw', [WalletController::class, 'withdraw']);
    Route::post('wallet/transfer', [WalletController::class, 'transfer']);
    Route::post('wallet/settle-pending', [WalletController::class, 'settlePending']);

    // ------------------- Withdrawals -------------------
    Route::get('withdrawals', [WithdrawalController::class, 'index']);
    Route::post('withdrawals/request', [WithdrawalController::class, 'request']);
    Route::post('withdrawals/confirm', [WithdrawalController::class, 'confirm']);
    Route::post('withdrawals/{id}/cancel', [WithdrawalController::class, 'cancel']);
    Route::get('withdrawals/{id}', [WithdrawalController::class, 'show']);

    // ------------------- Transactions -------------------
    Route::get('transactions', [TransactionController::class, 'index']);
    Route::get('transactions/{uuid}', [TransactionController::class, 'show']);

    // ------------------- Cards -------------------
    Route::apiResource('cards', CardController::class)->except(['index', 'update']);
    Route::put('cards/{id}/status', [CardController::class, 'updateStatus']);
    Route::post('cards/{id}/pin', [CardController::class, 'setPin']);
    Route::post('cards/{id}/verify-pin', [CardController::class, 'verifyPin']);

    // ------------------- Mobile Devices -------------------
    Route::apiResource('mobile-devices', MobileDeviceController::class)->except(['update']);
    Route::put('mobile-devices/{id}/status', [MobileDeviceController::class, 'updateStatus']);
    Route::put('mobile-devices/{id}/details', [MobileDeviceController::class, 'updateDetails']);

    // ------------------- Physical Devices -------------------
    Route::apiResource('physical-devices', PhysicalDeviceController::class)->except(['update']);
    Route::put('physical-devices/{id}/status', [PhysicalDeviceController::class, 'updateStatus']);
    Route::put('physical-devices/{id}/details', [PhysicalDeviceController::class, 'updateDetails']);

    // ------------------- KYC (User) -------------------
    Route::get('kyc/status', [KycController::class, 'status']);
    Route::post('kyc/submit', [KycController::class, 'submit']);

    // ------------------- Merchant Profile (User) -------------------
    Route::get('merchant/profile', [MerchantProfileController::class, 'show']);
    Route::post('merchant/profile', [MerchantProfileController::class, 'store']);
    Route::put('merchant/profile', [MerchantProfileController::class, 'update']);

    // ------------------- Agent Profile (User) -------------------
    Route::get('agent/profile', [AgentProfileController::class, 'show']);
    Route::post('agent/profile', [AgentProfileController::class, 'store']);
    Route::put('agent/profile', [AgentProfileController::class, 'update']);

    // ------------------- Notifications -------------------
    Route::prefix('notifications')->group(function () {
        Route::get('/', [NotificationController::class, 'index']);
        Route::get('/unread', [NotificationController::class, 'unread']);
        Route::get('/unread/count', [NotificationController::class, 'countUnread']);
        Route::post('/read-all', [NotificationController::class, 'markAllAsRead']);
        Route::delete('/all', [NotificationController::class, 'destroyAll']);
        Route::get('/{id}', [NotificationController::class, 'show']);
        Route::post('/{id}/read', [NotificationController::class, 'markAsRead']);
        Route::delete('/{id}', [NotificationController::class, 'destroy']);
    });

    // ------------------- Sessions -------------------
    Route::prefix('sessions')->group(function () {
        Route::get('/', [SessionController::class, 'index']);
        Route::get('/all', [SessionController::class, 'all']);
        Route::delete('/{sessionId}', [SessionController::class, 'revoke']);
        Route::post('/revoke-others', [SessionController::class, 'revokeOthers']);
        Route::post('/revoke-all', [SessionController::class, 'revokeAll']);
    });

    /*
    |--------------------------------------------------------------------------
    | Admin Routes (Require 'admin' ability / role)
    |--------------------------------------------------------------------------
    */
    Route::middleware('can:admin')->prefix('admin')->group(function () {

        // Dashboard
        Route::get('dashboard', [DashboardController::class, 'index']);

        // Reports
        Route::prefix('reports')->group(function () {
            Route::get('financial-summary', [ReportController::class, 'financialSummary']);
            Route::get('daily-volume', [ReportController::class, 'dailyVolume']);
            Route::get('agent/{agentId}/performance', [ReportController::class, 'agentPerformance']);
            Route::get('top-agents', [ReportController::class, 'topAgents']);
            Route::get('system-stats', [ReportController::class, 'systemStats']);
            Route::get('monthly-profit', [ReportController::class, 'monthlyProfit']);
            Route::get('export/financial-summary', [ReportController::class, 'exportFinancialSummary']);
        });

        // Audit Logs
        Route::prefix('audit-logs')->group(function () {
            Route::get('/', [AuditLogController::class, 'index']);
            Route::get('/user/{userId}', [AuditLogController::class, 'byUser']);
            Route::get('/entity', [AuditLogController::class, 'byEntity']);
            Route::get('/stats/actions', [AuditLogController::class, 'actionStats']);
            Route::get('/stats/top-users', [AuditLogController::class, 'topUsers']);
            Route::get('/export', [AuditLogController::class, 'export']);
        });

        // Commission Management
        Route::prefix('commissions')->group(function () {
            Route::post('agents/{agentId}/settle', [CommissionController::class, 'settleAgentCommissions']);
            Route::get('agents/{agentId}/summary', [CommissionController::class, 'getAgentCommissionSummary']);
        });

        // Monthly Profit
        Route::prefix('monthly-profit')->group(function () {
            Route::get('/', [MonthlyProfitController::class, 'index']);
            Route::get('/summary', [MonthlyProfitController::class, 'summary']);
            Route::get('/{yearMonth}', [MonthlyProfitController::class, 'show']);
            Route::post('/{yearMonth}/distribute', [MonthlyProfitController::class, 'distribute']);
            Route::post('/distribute-previous', [MonthlyProfitController::class, 'distributePrevious']);
        });

        // Withdrawals (Admin)
        Route::prefix('withdrawals')->group(function () {
            Route::get('/pending', [AdminWithdrawalController::class, 'pending']);
            Route::get('/agent/{agentId}', [AdminWithdrawalController::class, 'byAgent']);
            Route::post('/expire-pending', [AdminWithdrawalController::class, 'expirePending']);
        });

        // KYC (Admin)
        Route::prefix('kyc')->group(function () {
            Route::get('pending', [AdminKycController::class, 'pending']);
            Route::get('verified', [AdminKycController::class, 'verified']);
            Route::get('{userId}', [AdminKycController::class, 'show']);
            Route::post('{userId}/approve', [AdminKycController::class, 'approve']);
            Route::post('{userId}/reject', [AdminKycController::class, 'reject']);
        });

        // Merchant Profiles (Admin)
        Route::prefix('merchants')->group(function () {
            Route::get('/', [AdminMerchantProfileController::class, 'index']);
            Route::get('/active', [AdminMerchantProfileController::class, 'active']);
            Route::get('/{userId}', [AdminMerchantProfileController::class, 'show']);
            Route::put('/{userId}', [AdminMerchantProfileController::class, 'update']);
            Route::post('/{userId}/activate', [AdminMerchantProfileController::class, 'activate']);
            Route::post('/{userId}/deactivate', [AdminMerchantProfileController::class, 'deactivate']);
            Route::delete('/{userId}', [AdminMerchantProfileController::class, 'destroy']);
        });

        // Agent Profiles (Admin)
        Route::prefix('agents')->group(function () {
            Route::get('/', [AdminAgentProfileController::class, 'index']);
            Route::get('/active', [AdminAgentProfileController::class, 'active']);
            Route::get('/{userId}', [AdminAgentProfileController::class, 'show']);
            Route::put('/{userId}', [AdminAgentProfileController::class, 'update']);
            Route::post('/{userId}/activate', [AdminAgentProfileController::class, 'activate']);
            Route::post('/{userId}/deactivate', [AdminAgentProfileController::class, 'deactivate']);
            Route::delete('/{userId}', [AdminAgentProfileController::class, 'destroy']);
        });

        // App Configuration (Admin)
        Route::prefix('config')->group(function () {
            Route::get('/', [AdminAppConfigController::class, 'index']);
            Route::get('/group/{group}', [AdminAppConfigController::class, 'group']);
            Route::get('/value', [AdminAppConfigController::class, 'show']);
            Route::post('/', [AdminAppConfigController::class, 'store']);
            Route::post('/deactivate', [AdminAppConfigController::class, 'deactivate']);
            Route::post('/activate', [AdminAppConfigController::class, 'activate']);
            Route::post('/clear-cache', [AdminAppConfigController::class, 'clearCache']);
        });

    });

});