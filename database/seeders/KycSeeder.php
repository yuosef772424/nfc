<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\UserKyc;
use Faker\Factory as Faker;
use Carbon\Carbon;

class KycSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $users = User::inRandomOrder()->limit(15)->get(); // نختار 15 مستخدم عشوائي

        foreach ($users as $user) {
            $verifiedAt = $faker->boolean(70) ? Carbon::now()->subDays(rand(1, 90)) : null;
            UserKyc::create([
                'user_id'         => $user->id,
                'id_type'         => $faker->randomElement(['national_id', 'passport']),
                'id_number'       => $faker->numerify('##########'),
                'id_front_image'  => $faker->imageUrl(640, 480, 'id card'),
                'id_back_image'   => $faker->imageUrl(640, 480, 'id card back'),
                'id_expiry_date'  => Carbon::now()->addYears(rand(1, 5)),
                'date_of_birth'   => $faker->dateTimeBetween('-60 years', '-18 years'),
                'address'         => $faker->address,
                'verified_at'     => $verifiedAt,
            ]);

            // تحديث حالة التحقق في جدول المستخدمين إذا تم التحقق
            if ($verifiedAt) {
                $user->update(['is_verified' => true]);
            }
        }
    }
}