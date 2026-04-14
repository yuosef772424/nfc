<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Notification;
use Faker\Factory as Faker;

class NotificationSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $users = User::inRandomOrder()->limit(30)->get();

        foreach ($users as $user) {
            for ($i = 0; $i < rand(1, 5); $i++) {
                Notification::create([
                    'user_id'  => $user->id,
                    'type'     => $faker->randomElement(['transaction', 'security', 'system']),
                    'title'    => $faker->sentence(3),
                    'message'  => $faker->paragraph,
                    'channel'  => $faker->randomElement(['push', 'email', 'sms']),
                    'is_read'  => $faker->boolean(70),
                    'data'     => ['action_url' => $faker->url],
                ]);
            }
        }
    }
}