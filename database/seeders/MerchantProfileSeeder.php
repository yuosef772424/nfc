<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\MerchantProfile;
use Faker\Factory as Faker;

class MerchantProfileSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $merchants = User::where('user_type', User::TYPE_MERCHANT)->get();

        foreach ($merchants as $merchant) {
            MerchantProfile::firstOrCreate(
                ['user_id' => $merchant->id],
                [
                    'business_name'            => $merchant->name,
                    'business_type'            => $faker->randomElement(['retail', 'wholesale', 'service', 'restaurant', 'other']),
                    'commercial_registration'  => $faker->optional()->numerify('CR-#####'),
                    'license_number'           => $faker->optional()->numerify('LIC-#####'),
                    'is_active'                => $merchant->status === User::STATUS_ACTIVE,
                ]
            );
        }
    }
}