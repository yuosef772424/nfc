<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\AuditLog;
use Faker\Factory as Faker;

class AuditLogSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $users = User::all();

        for ($i = 0; $i < 200; $i++) {
            $user = $users->random();
            AuditLog::create([
                'user_id'    => $user->id,
                'action'     => $faker->randomElement(['create', 'update', 'delete', 'login', 'logout']),
                'entity'     => $faker->randomElement(['user', 'wallet', 'card', 'transaction', 'withdrawal']),
                'entity_id'  => rand(1, 100),
                'old_data'   => $faker->boolean(30) ? ['old' => $faker->word] : null,
                'new_data'   => ['new' => $faker->word],
                'ip_address' => $faker->ipv4,
                'created_at' => $faker->dateTimeThisYear(),
            ]);
        }
    }
}