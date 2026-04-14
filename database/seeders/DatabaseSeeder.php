<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            AppConfigSeeder::class,          // الإعدادات أولاً
            UserSeeder::class,               // المستخدمين (يُنشئ عملاء، وكلاء، تجار)
            WalletSeeder::class,             // المحافظ لكل مستخدم
            AgentProfileSeeder::class,       // ملفات الوكلاء
            MerchantProfileSeeder::class,    // ملفات التجار
            KycSeeder::class,                // طلبات KYC لبعض المستخدمين
            NfcDeviceSeeder::class,          // أجهزة NFC
            CardSeeder::class,               // بطاقات NFC
            TransactionSeeder::class,        // معاملات مالية
            WithdrawalSeeder::class,         // طلبات سحب نقدي
            CommissionLogSeeder::class,      // سجلات العمولات
            // DisputeSeeder::class,            // نزاعات
            NotificationSeeder::class,       // إشعارات
            AuditLogSeeder::class,           // سجلات التدقيق
        ]);
    }
}