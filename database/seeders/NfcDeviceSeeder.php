<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\NfcDevice;
use App\Models\MobileDeviceDetail;
use App\Models\PhysicalDeviceDetail;
use Faker\Factory as Faker;

class NfcDeviceSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $users = User::inRandomOrder()->limit(20)->get();

        foreach ($users as $user) {
            $deviceType = $faker->randomElement([NfcDevice::TYPE_MOBILE, NfcDevice::TYPE_PHYSICAL]);
            $device = NfcDevice::create([
                'user_id'     => $user->id,
                'device_uuid' => $faker->uuid,
                'device_type' => $deviceType,
                'status'      => $faker->randomElement([NfcDevice::STATUS_ACTIVE, NfcDevice::STATUS_ACTIVE, NfcDevice::STATUS_INACTIVE]),
                'metadata'    => ['brand' => $faker->randomElement(['Samsung', 'Xiaomi', 'Huawei', 'Verifone', 'Ingenico'])],
            ]);

            if ($deviceType === NfcDevice::TYPE_MOBILE) {
                MobileDeviceDetail::create([
                    'device_id'          => $device->id,
                    'phone_model'        => $faker->randomElement(['Galaxy S21', 'Redmi Note 10', 'iPhone 12', 'P40 Pro']),
                    'phone_os'           => $faker->randomElement(['Android 13', 'iOS 16', 'Android 12']),
                    'device_fingerprint' => $faker->sha256,
                    'nfc_supported'      => true,
                    'biometric_type'     => $faker->randomElement(['fingerprint', 'face', 'none']),
                ]);
            } else {
                PhysicalDeviceDetail::create([
                    'device_id'             => $device->id,
                    'serial_number'         => $faker->unique()->numerify('SN-#####'),
                    'installation_location' => $faker->address,
                    'installation_date'     => $faker->dateTimeThisYear(),
                ]);
            }
        }
    }
}