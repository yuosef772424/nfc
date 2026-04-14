<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Card;
use Faker\Factory as Faker;
use Carbon\Carbon;

class CardSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $wallets = Wallet::where('status', Wallet::STATUS_ACTIVE)->inRandomOrder()->limit(30)->get();
        $agents = User::where('user_type', User::TYPE_AGENT)->get();

        foreach ($wallets as $wallet) {
            Card::create([
                'wallet_id'   => $wallet->id,
                'agent_id'    => $agents->random()->id,
                'card_number' => $faker->unique()->numerify('4##############'),
                'pin_hash'    => bcrypt('1234'),
                'nfc_uid'     => $faker->unique()->sha1,
                'nfc_key_ref' => 'kms_ref_' . $faker->randomNumber(5),
                'status'      => $faker->randomElement([Card::STATUS_ACTIVE, Card::STATUS_ACTIVE, Card::STATUS_BLOCKED]),
                'expiry_date' => Carbon::now()->addYears(rand(1, 4)),
            ]);
        }
    }
}