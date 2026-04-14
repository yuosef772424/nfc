<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Faker\Factory as Faker;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create('ar_SA');

        // 1. مستخدم مسؤول – نستخدم updateOrCreate لتجنب التكرار
        User::updateOrCreate(
            ['email' => 'admin@nfc.com'],
            [
                'uuid'        => $faker->uuid,
                'name'        => 'مدير النظام',
                'phone'       => '770000001',
                'password'    => Hash::make('password'),
                'user_type'   => User::TYPE_USER,
                'status'      => User::STATUS_ACTIVE,
                'is_verified' => true,
            ]
        );

        // 2. عملاء عاديون – نستخدم firstOrCreate لتجنب التكرار بناءً على البريد
        for ($i = 1; $i <= 20; $i++) {
            $email = $faker->unique()->safeEmail;
            User::firstOrCreate(
                ['email' => $email],
                [
                    'uuid'        => $faker->uuid,
                    'name'        => $faker->name,
                    'phone'       => '77' . $faker->numerify('#######'),
                    'password'    => Hash::make('password'),
                    'user_type'   => User::TYPE_USER,
                    'status'      => $faker->randomElement([User::STATUS_ACTIVE, User::STATUS_ACTIVE, User::STATUS_ACTIVE, User::STATUS_INACTIVE]),
                    'is_verified' => $faker->boolean(80),
                ]
            );
        }

        // 3. وكلاء
        for ($i = 1; $i <= 10; $i++) {
            $email = $faker->unique()->safeEmail;
            User::firstOrCreate(
                ['email' => $email],
                [
                    'uuid'        => $faker->uuid,
                    'name'        => $faker->name,
                    'phone'       => '78' . $faker->numerify('#######'),
                    'password'    => Hash::make('password'),
                    'user_type'   => User::TYPE_AGENT,
                    'status'      => $faker->randomElement([User::STATUS_ACTIVE, User::STATUS_ACTIVE, User::STATUS_ACTIVE, User::STATUS_SUSPENDED]),
                    'is_verified' => $faker->boolean(90),
                ]
            );
        }

        // 4. تجار
        for ($i = 1; $i <= 10; $i++) {
            $email = $faker->unique()->companyEmail;
            User::firstOrCreate(
                ['email' => $email],
                [
                    'uuid'        => $faker->uuid,
                    'name'        => $faker->company,
                    'phone'       => '79' . $faker->numerify('#######'),
                    'password'    => Hash::make('password'),
                    'user_type'   => User::TYPE_MERCHANT,
                    'status'      => $faker->randomElement([User::STATUS_ACTIVE, User::STATUS_ACTIVE, User::STATUS_INACTIVE]),
                    'is_verified' => $faker->boolean(85),
                ]
            );
        }
    }
}