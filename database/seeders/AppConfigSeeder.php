<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\AppConfig;

class AppConfigSeeder extends Seeder
{
    public function run(): void
    {
        // ========== إعدادات الأمان ==========
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'login.max_attempts'],
            ['value' => '5', 'data_type' => 'number', 'label' => 'Max Login Attempts']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'login.lockout_seconds'],
            ['value' => '300', 'data_type' => 'number', 'label' => 'Login Lockout (seconds)']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'registration.max_attempts'],
            ['value' => '3', 'data_type' => 'number', 'label' => 'Max Registration Attempts']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'registration.lockout_seconds'],
            ['value' => '600', 'data_type' => 'number', 'label' => 'Registration Lockout (seconds)']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'reset_password.max_attempts'],
            ['value' => '3', 'data_type' => 'number', 'label' => 'Max Reset Password Attempts']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'reset_password.lockout_seconds'],
            ['value' => '900', 'data_type' => 'number', 'label' => 'Reset Password Lockout (seconds)']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'verification.max_attempts'],
            ['value' => '5', 'data_type' => 'number', 'label' => 'Max Verification Attempts']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'verification.lockout_seconds'],
            ['value' => '3600', 'data_type' => 'number', 'label' => 'Verification Lockout (seconds)']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'pin.max_attempts'],
            ['value' => '3', 'data_type' => 'number', 'label' => 'PIN Max Attempts']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'pin.lockout_seconds'],
            ['value' => '900', 'data_type' => 'number', 'label' => 'PIN Lockout (seconds)']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'password_change.max_attempts'],
            ['value' => '3', 'data_type' => 'number', 'label' => 'Max Password Change Attempts']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'password_change.lockout_seconds'],
            ['value' => '900', 'data_type' => 'number', 'label' => 'Password Change Lockout (seconds)']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'token_refresh.max_attempts'],
            ['value' => '5', 'data_type' => 'number', 'label' => 'Max Token Refresh Attempts']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'token_refresh.lockout_seconds'],
            ['value' => '300', 'data_type' => 'number', 'label' => 'Token Refresh Lockout (seconds)']
        );

        // إعدادات KYC
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'kyc.submission.max_attempts'],
            ['value' => '3', 'data_type' => 'number', 'label' => 'KYC Max Submission Attempts']
        );
        AppConfig::updateOrCreate(
            ['group' => 'security', 'key' => 'kyc.submission.lockout_seconds'],
            ['value' => '86400', 'data_type' => 'number', 'label' => 'KYC Lockout (seconds)']
        );

        // ========== إعدادات السياسات العامة ==========
        AppConfig::updateOrCreate(
            ['group' => 'policy', 'key' => 'session.expiry_minutes'],
            ['value' => '120', 'data_type' => 'number', 'label' => 'Session Expiry (minutes)']
        );

        // ========== إعدادات الرسوم والعمولات ==========
        AppConfig::updateOrCreate(
            ['group' => 'fee', 'key' => 'withdrawal_commission_type'],
            ['value' => 'percentage', 'data_type' => 'string', 'label' => 'Withdrawal Commission Type']
        );
        AppConfig::updateOrCreate(
            ['group' => 'fee', 'key' => 'withdrawal_commission_value'],
            ['value' => '3', 'data_type' => 'number', 'label' => 'Withdrawal Commission Value']
        );

        // ========== ثوابت أنواع المستخدمين ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'user_type.customer'],
            ['value' => 'user', 'data_type' => 'string', 'label' => 'User Type: Customer']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'user_type.agent'],
            ['value' => 'agent', 'data_type' => 'string', 'label' => 'User Type: Agent']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'user_type.merchant'],
            ['value' => 'merchant', 'data_type' => 'string', 'label' => 'User Type: Merchant']
        );

        // ========== ثوابت حالات المستخدمين ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'user_status.active'],
            ['value' => 'active', 'data_type' => 'string', 'label' => 'User Status: Active']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'user_status.inactive'],
            ['value' => 'inactive', 'data_type' => 'string', 'label' => 'User Status: Inactive']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'user_status.suspended'],
            ['value' => 'suspended', 'data_type' => 'string', 'label' => 'User Status: Suspended']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'user_status.deleted'],
            ['value' => 'deleted', 'data_type' => 'string', 'label' => 'User Status: Deleted']
        );

        // ========== ثوابت أنواع الأجهزة ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'device_type.mobile'],
            ['value' => 'mobile', 'data_type' => 'string', 'label' => 'Device Type: Mobile']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'device_type.physical'],
            ['value' => 'physical', 'data_type' => 'string', 'label' => 'Device Type: Physical']
        );

        // ========== ثوابت حالات الأجهزة ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'device_status.active'],
            ['value' => 'active', 'data_type' => 'string', 'label' => 'Device Status: Active']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'device_status.inactive'],
            ['value' => 'inactive', 'data_type' => 'string', 'label' => 'Device Status: Inactive']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'device_status.maintenance'],
            ['value' => 'maintenance', 'data_type' => 'string', 'label' => 'Device Status: Maintenance']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'device_status.blocked'],
            ['value' => 'blocked', 'data_type' => 'string', 'label' => 'Device Status: Blocked']
        );

        // ========== ثوابت أنواع القياسات الحيوية ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'biometric_type.face'],
            ['value' => 'face', 'data_type' => 'string', 'label' => 'Biometric Type: Face']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'biometric_type.fingerprint'],
            ['value' => 'fingerprint', 'data_type' => 'string', 'label' => 'Biometric Type: Fingerprint']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'biometric_type.none'],
            ['value' => 'none', 'data_type' => 'string', 'label' => 'Biometric Type: None']
        );

        // ========== ثوابت حالات المحفظة ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'wallet_status.active'],
            ['value' => 'active', 'data_type' => 'string', 'label' => 'Wallet Status: Active']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'wallet_status.inactive'],
            ['value' => 'inactive', 'data_type' => 'string', 'label' => 'Wallet Status: Inactive']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'wallet_status.frozen'],
            ['value' => 'frozen', 'data_type' => 'string', 'label' => 'Wallet Status: Frozen']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'wallet_status.closed'],
            ['value' => 'closed', 'data_type' => 'string', 'label' => 'Wallet Status: Closed']
        );

        // ========== ثوابت أنواع المعاملات ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'transaction_type.payment'],
            ['value' => 'payment', 'data_type' => 'string', 'label' => 'Transaction Type: Payment']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'transaction_type.transfer'],
            ['value' => 'transfer', 'data_type' => 'string', 'label' => 'Transaction Type: Transfer']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'transaction_type.deposit'],
            ['value' => 'deposit', 'data_type' => 'string', 'label' => 'Transaction Type: Deposit']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'transaction_type.withdrawal'],
            ['value' => 'withdrawal', 'data_type' => 'string', 'label' => 'Transaction Type: Withdrawal']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'transaction_type.refund'],
            ['value' => 'refund', 'data_type' => 'string', 'label' => 'Transaction Type: Refund']
        );

        // ========== ثوابت حالات المعاملات ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'transaction_status.pending'],
            ['value' => 'pending', 'data_type' => 'string', 'label' => 'Transaction Status: Pending']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'transaction_status.completed'],
            ['value' => 'completed', 'data_type' => 'string', 'label' => 'Transaction Status: Completed']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'transaction_status.failed'],
            ['value' => 'failed', 'data_type' => 'string', 'label' => 'Transaction Status: Failed']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'transaction_status.cancelled'],
            ['value' => 'cancelled', 'data_type' => 'string', 'label' => 'Transaction Status: Cancelled']
        );

        // ========== ثوابت أنواع القيود المحاسبية ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'ledger_entry_type.debit'],
            ['value' => 'debit', 'data_type' => 'string', 'label' => 'Ledger Entry Type: Debit']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'ledger_entry_type.credit'],
            ['value' => 'credit', 'data_type' => 'string', 'label' => 'Ledger Entry Type: Credit']
        );

        // ========== ثوابت حالات السحب ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'withdrawal_status.pending'],
            ['value' => 'pending', 'data_type' => 'string', 'label' => 'Withdrawal Status: Pending']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'withdrawal_status.completed'],
            ['value' => 'completed', 'data_type' => 'string', 'label' => 'Withdrawal Status: Completed']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'withdrawal_status.failed'],
            ['value' => 'failed', 'data_type' => 'string', 'label' => 'Withdrawal Status: Failed']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'withdrawal_status.cancelled'],
            ['value' => 'cancelled', 'data_type' => 'string', 'label' => 'Withdrawal Status: Cancelled']
        );

        // ========== ثوابت حالات العمولات ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'commission_status.pending'],
            ['value' => 'pending', 'data_type' => 'string', 'label' => 'Commission Status: Pending']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'commission_status.paid'],
            ['value' => 'paid', 'data_type' => 'string', 'label' => 'Commission Status: Paid']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'commission_status.cancelled'],
            ['value' => 'cancelled', 'data_type' => 'string', 'label' => 'Commission Status: Cancelled']
        );

        // ========== ثوابت أنواع المستلمين ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'recipient_type.agent'],
            ['value' => 'agent', 'data_type' => 'string', 'label' => 'Recipient Type: Agent']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'recipient_type.merchant'],
            ['value' => 'merchant', 'data_type' => 'string', 'label' => 'Recipient Type: Merchant']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'recipient_type.system'],
            ['value' => 'system', 'data_type' => 'string', 'label' => 'Recipient Type: System']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'recipient_type.company'],
            ['value' => 'company', 'data_type' => 'string', 'label' => 'Recipient Type: Company']
        );

        // ========== ثوابت أنواع الإشعارات ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'notification_type.transaction'],
            ['value' => 'transaction', 'data_type' => 'string', 'label' => 'Notification Type: Transaction']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'notification_type.security'],
            ['value' => 'security', 'data_type' => 'string', 'label' => 'Notification Type: Security']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'notification_type.system'],
            ['value' => 'system', 'data_type' => 'string', 'label' => 'Notification Type: System']
        );

        // ========== ثوابت قنوات الإشعارات ==========
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'notification_channel.push'],
            ['value' => 'push', 'data_type' => 'string', 'label' => 'Notification Channel: Push']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'notification_channel.sms'],
            ['value' => 'sms', 'data_type' => 'string', 'label' => 'Notification Channel: SMS']
        );
        AppConfig::updateOrCreate(
            ['group' => 'constant', 'key' => 'notification_channel.email'],
            ['value' => 'email', 'data_type' => 'string', 'label' => 'Notification Channel: Email']
        );
    }
}