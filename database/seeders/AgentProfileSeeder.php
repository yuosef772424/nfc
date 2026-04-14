<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AgentProfile;
use Faker\Factory as Faker;

class AgentProfileSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $agents = User::where('user_type', User::TYPE_AGENT)->get();

        foreach ($agents as $agent) {
            AgentProfile::firstOrCreate(
                ['user_id' => $agent->id],
                [
                    'commission_type'  => $faker->randomElement(['percentage', 'fixed']),
                    'commission_value' => $faker->randomElement([2.5, 5.0, 7.5, 10.0, 1000, 2000]),
                    'is_active'        => $agent->status === User::STATUS_ACTIVE,
                    'metadata'         => ['address' => $faker->address, 'notes' => $faker->sentence],
                ]
            );
        }
    }
}