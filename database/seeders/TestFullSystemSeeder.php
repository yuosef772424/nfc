<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class TestFullSystemSeeder extends Seeder
{
    public function run()
    {
        DB::transaction(function () {
            // 1. مستخدم (تاجر)
            $user = \App\Models\User::create([
                'uuid'       => Str::uuid(),
                'name'       => 'تاجر تجريبي',
                'phone'      => '966500000001',
                'email'      => 'merchant@test.com',
                'password'   => Hash::make('123456'),
                'user_type'  => 'merchant',
                'status'     => 'active',
                'is_verified'=> true,
                'metadata'   => ['source' => 'seeder'],
            ]);

            // 2. مستخدم (وكيل)
            $agent = \App\Models\User::create([
                'uuid'       => Str::uuid(),
                'name'       => 'وكيل تجريبي',
                'phone'      => '966500000002',
                'email'      => 'agent@test.com',
                'password'   => Hash::make('123456'),
                'user_type'  => 'agent',
                'status'     => 'active',
                'is_verified'=> true,
                'metadata'   => ['source' => 'seeder'],
            ]);

            // 3. KYC للمستخدم التاجر
            \App\Models\UserKyc::create([
                'user_id'         => $user->id,
                'id_type'         => 'national_id',
                'id_number'       => Hash::make('1234567890'),
                'id_front_image'  => 'encrypted_front_path.jpg',
                'id_back_image'   => 'encrypted_back_path.jpg',
                'id_expiry_date'  => '2030-01-01',
                'date_of_birth'   => '1980-01-01',
                'address'         => 'صنعاء، اليمن',
                'verified_at'     => now(),
            ]);

            // 4. بروفايل الوكيل
            \App\Models\AgentProfile::create([
                'user_id'          => $agent->id,
                'commission_type'  => 'percentage',
                'commission_value' => 2.50,
                'is_active'        => true,
                'metadata'         => ['level' => 'gold'],
            ]);

            // 5. بروفايل التاجر (تم)
            \App\Models\MerchantProfile::create([
                'user_id'       => $user->id,
                'business_name' => 'متجر التجربة',
                'business_type' => 'electronics',
                'is_active'     => true,
            ]);

            // 6. محفظة للتاجر
            $wallet = \App\Models\Wallet::create([
                'user_id'           => $user->id,
                'currency'          => 'YER',
                'status'            => 'active',
                'available_balance' => 1500.00,
                'pending_balance'   => 0.00,
            ]);

            // 7. محفظة للوكيل
            $agentWallet = \App\Models\Wallet::create([
                'user_id'           => $agent->id,
                'currency'          => 'YER',
                'status'            => 'active',
                'available_balance' => 5000.00,
                'pending_balance'   => 0.00,
            ]);

            // 8. جهاز NFC (جوال) للتاجر
            $device = \App\Models\NfcDevice::create([
                'user_id'     => $user->id,
                'device_uuid' => Str::uuid(),
                'device_type' => 'mobile',
                'status'      => 'active',
                'metadata'    => ['model' => 'iPhone 14'],
            ]);

            // 9. جهاز NFC (مادي) للوكيل
            $physicalDevice = \App\Models\NfcDevice::create([
                'user_id'     => $agent->id,
                'device_uuid' => Str::uuid(),
                'device_type' => 'physical',
                'status'      => 'active',
                'metadata'    => ['model' => 'Raspberry Pi 4'],
            ]);

            // 10. تفاصيل الجهاز المحمول
            \App\Models\MobileDeviceDetail::create([
                'device_id'          => $device->id,
                'phone_model'        => 'iPhone 14',
                'phone_os'           => 'iOS 17',
                'device_fingerprint' => Hash::make('fingerprint123'),
                'nfc_supported'      => true,
                'biometric_type'     => 'face',
            ]);

            // 11. تفاصيل الجهاز المادي
            \App\Models\PhysicalDeviceDetail::create([
                'device_id'             => $physicalDevice->id,
                'serial_number'         => 'SN123456789',
                'installation_location' => 'محطة الوكيل الرئيسية',
                'installation_date'     => '2026-01-01',
            ]);

            // 12. بطاقة مرتبطة بمحفظة التاجر
            $card = \App\Models\Card::create([
                'wallet_id'    => $wallet->id,
                'agent_id'     => $agent->id,
                'card_number'  => '1234567890123456',
                'pin_hash'     => Hash::make('1234'),
                'nfc_uid'      => '04:AB:CD:EF:12:34',
                'nfc_key_ref'  => 'kms_key_1',
                'status'       => 'active',
                'expiry_date'  => now()->addYears(3),
            ]);

            // 13. معاملة دفع (من التاجر إلى الوكيل)
            $transaction = \App\Models\WalletTransaction::create([
                'transaction_uuid'    => Str::uuid(),
                'sender_wallet_id'    => $wallet->id,
                'receiver_wallet_id'  => $agentWallet->id,
                'sender_card_id'      => $card->id,
                'sender_device_id'    => $device->id,
                'receiver_device_id'  => $physicalDevice->id,
                'type'                => 'payment',
                'amount'              => 100.00,
                'fee'                 => 2.00,
                'net_amount'          => 98.00,
                'currency'            => 'YER',
                'status'              => 'completed',
                'description'         => 'شراء منتج تجريبي',
                'metadata'            => ['product' => 'laptop'],
            ]);

            // 14. قيد محاسبي (debit من محفظة التاجر)
            $newBalance = $wallet->available_balance - 100.00;
            \App\Models\LedgerEntry::create([
                'transaction_id' => $transaction->id,
                'wallet_id'      => $wallet->id,
                'entry_type'     => 'debit',
                'amount'         => 100.00,
                'balance_after'  => $newBalance,
            ]);
            $wallet->update(['available_balance' => $newBalance]);

            // قيد إضافي (credit لمحفظة الوكيل)
            $agentNewBalance = $agentWallet->available_balance + 98.00;
            \App\Models\LedgerEntry::create([
                'transaction_id' => $transaction->id,
                'wallet_id'      => $agentWallet->id,
                'entry_type'     => 'credit',
                'amount'         => 98.00,
                'balance_after'  => $agentNewBalance,
            ]);
            $agentWallet->update(['available_balance' => $agentNewBalance]);

            // 15. عملية سحب للوكيل
            $withdrawal = \App\Models\Withdrawal::create([
                'wallet_id'         => $agentWallet->id,
                'agent_id'          => $agent->id,
                'requested_amount'  => 200.00,
                'commission_amount' => 5.00,
                'total_amount'      => 205.00,
                'commission_type'   => 'percentage',
                'commission_value'  => 2.50,
                'verification_code' => Hash::make('123456'),
                'expires_at'        => now()->addHours(24),
                'status'            => 'pending',
            ]);

            // 16. سجل عمولة (مرتبط بعملية السحب)
            \App\Models\CommissionLog::create([
                'reference_type' => 'withdrawal',
                'reference_id'   => $withdrawal->id,
                'recipient_type' => 'agent',
                'recipient_id'   => $agent->id,
                'amount'         => 5.00,
                'status'         => 'pending',
            ]);

            // 17. OTP للمستخدم التاجر
            \App\Models\OtpVerification::create([
                'user_id'    => $user->id,
                'purpose'    => 'login',
                'code_hash'  => Hash::make('123456'),
                'expires_at' => now()->addMinutes(5),
            ]);

            // 18. سجل تدقيق (audit log) لعملية إنشاء المستخدم
            \App\Models\AuditLog::create([
                'user_id'    => $user->id,
                'action'     => 'create',
                'entity'     => 'user',
                'entity_id'  => $user->id,
                'old_data'   => null,
                'new_data'   => ['name' => $user->name, 'phone' => $user->phone],
                'ip_address' => '127.0.0.1',
            ]);

            // 19. سياسة نظام (مثال)
            \App\Models\SystemPolicy::create([
                'key'         => 'min_transfer_amount',
                'value'       => '10',
                'data_type'   => 'number',
                'category'    => 'limits',
                'scope_type'  => 'global',
                'scope_id'    => null,
                'description' => 'الحد الأدنى لعملية التحويل',
                'is_active'   => true,
                'priority'    => 1,
            ]);

            // 20. إشعار (تم سابقاً) – نضيف إشعاراً آخر للوكيل
            \App\Models\Notification::create([
                'user_id' => $agent->id,
                'type'    => 'system',
                'title'   => 'تم إضافة عمولة',
                'message' => 'تم إضافة 5 ريال كعمولة لعملية السحب',
                'channel' => 'push',
                'is_read' => false,
                'data'    => ['withdrawal_id' => $withdrawal->id],
            ]);

            // 21. جلسة للوكيل
            \App\Models\Session::create([
                'user_id'     => $agent->id,
                'token_hash'  => Hash::make(Str::random(64)),
                'device_info' => ['ip' => '192.168.1.2', 'agent' => 'Chrome'],
                'location'    => ['city' => 'Aden', 'country' => 'YE'],
                'expires_at'  => now()->addHours(1),
            ]);

            $this->command->info('✅ تم إنشاء بيانات الاختبار لجميع الجداول الـ 18 بنجاح!');
        });
    }
}