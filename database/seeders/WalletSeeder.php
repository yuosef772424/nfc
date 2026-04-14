<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Wallet;
use Faker\Factory as Faker;

class WalletSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $users = User::all();

        foreach ($users as $user) {
            // التأكد من عدم وجود محفظة سابقة
            if (!Wallet::where('user_id', $user->id)->exists()) {
                $balance = $faker->randomFloat(2, 100, 10000);
                Wallet::create([
                    'user_id'           => $user->id,
                    'currency'          => 'YER',
                    'status'            => Wallet::STATUS_ACTIVE,
                    'available_balance' => $balance,
                    'pending_balance'   => $faker->randomFloat(2, 0, $balance * 0.2),
                ]);
            }
        }
    }
}