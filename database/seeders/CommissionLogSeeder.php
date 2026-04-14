<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\CommissionLog;
use App\Models\Withdrawal;
use App\Models\WalletTransaction;
use App\Models\User;
use Faker\Factory as Faker;

class CommissionLogSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $agents = User::where('user_type', User::TYPE_AGENT)->get();

        // 1. عمولات الوكلاء (من السحوبات) – لا مشكلة فيها
        $withdrawals = Withdrawal::where('status', 'completed')->get();
        foreach ($withdrawals as $withdrawal) {
            CommissionLog::create([
                'reference_type' => CommissionLog::REF_WITHDRAWAL,
                'reference_id'   => $withdrawal->id,
                'recipient_type' => CommissionLog::RECIPIENT_AGENT,
                'recipient_id'   => $withdrawal->agent_id,
                'amount'         => $withdrawal->commission_amount,
                'status'         => $faker->randomElement(['paid', 'pending']),
                'paid_at'        => $faker->boolean(80) ? now()->subDays(rand(1, 60)) : null,
            ]);
        }

        // 2. عمولات النظام – نحتاج مستخدم حقيقي ليكون recipient
        // نبحث عن المستخدم المسؤول (الإيميل admin@nfc.com) أو أي مستخدم نوعه user
        $systemUser = User::where('email', 'admin@nfc.com')->first()
                      ?? User::where('user_type', User::TYPE_USER)->first();

        if ($systemUser) {
            $transactions = WalletTransaction::where('status', 'completed')
                ->inRandomOrder()
                ->limit(20)
                ->get();

            foreach ($transactions as $transaction) {
                CommissionLog::create([
                    'reference_type' => CommissionLog::REF_TRANSACTION,
                    'reference_id'   => $transaction->id,
                    'recipient_type' => CommissionLog::RECIPIENT_SYSTEM,
                    'recipient_id'   => $systemUser->id,
                    'amount'         => $transaction->fee ?? 0,
                    'status'         => 'paid',
                    'paid_at'        => now()->subDays(rand(1, 30)),
                ]);
            }
        } else {
            // لا يوجد مستخدم، نتجاوز عمولات النظام (اختياري)
            $this->command->warn('No system user found; skipping system commission logs.');
        }
    }
}