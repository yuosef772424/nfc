<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Withdrawal;
use Faker\Factory as Faker;
use Carbon\Carbon;

class WithdrawalSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $wallets = Wallet::where('status', Wallet::STATUS_ACTIVE)->inRandomOrder()->limit(30)->get();
        $agents = User::where('user_type', User::TYPE_AGENT)->get();

        foreach ($wallets as $wallet) {
            $requested = $faker->randomFloat(2, 100, 2000);
            $commission = round($requested * 0.03, 2);
            $total = $requested + $commission;
            $status = $faker->randomElement(['pending', 'completed', 'cancelled']);
            $completedAt = $status === 'completed' ? Carbon::now()->subDays(rand(1, 30)) : null;

            Withdrawal::create([
                'wallet_id'          => $wallet->id,
                'agent_id'           => $agents->random()->id,
                'requested_amount'   => $requested,
                'commission_amount'  => $commission,
                'total_amount'       => $total,
                'commission_type'    => $faker->randomElement(['percentage', 'fixed']),
                'commission_value'   => $commission,
                'verification_code'  => bcrypt('123456'),
                'expires_at'         => Carbon::now()->addMinutes(30),
                'status'             => $status,
                'completed_at'       => $completedAt,
            ]);
        }
    }
}