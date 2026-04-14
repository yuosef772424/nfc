<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Wallet;
use App\Models\WalletTransaction;
use App\Models\LedgerEntry;
use App\Models\Card;
use App\Models\NfcDevice;
use Faker\Factory as Faker;
use Carbon\Carbon;

class TransactionSeeder extends Seeder
{
    public function run(): void
    {
        $faker = Faker::create();
        $wallets = Wallet::all();

        for ($i = 0; $i < 100; $i++) {
            $senderWallet = $wallets->random();
            $receiverWallet = $wallets->where('id', '!=', $senderWallet->id)->random();

            $amount = $faker->randomFloat(2, 10, 5000);
            $fee = round($amount * 0.01, 2);
            $net = $amount - $fee;

            $transaction = WalletTransaction::create([
                'transaction_uuid'    => $faker->uuid,
                'sender_wallet_id'    => $senderWallet->id,
                'receiver_wallet_id'  => $receiverWallet->id,
                'sender_card_id'      => Card::inRandomOrder()->first()?->id,
                'sender_device_id'    => NfcDevice::inRandomOrder()->first()?->id,
                'receiver_device_id'  => NfcDevice::inRandomOrder()->first()?->id,
                'type'                => $faker->randomElement(['transfer', 'payment']),
                'amount'              => $amount,
                'fee'                 => $fee,
                'net_amount'          => $net,
                'currency'            => 'YER',
                'status'              => $faker->randomElement(['completed', 'completed', 'completed', 'pending', 'failed']),
                'description'         => $faker->sentence,
                'metadata'            => ['ip' => $faker->ipv4],
            ]);

            // تحديث أرصدة المحافظ (تقريبي للبيانات الوهمية)
            if ($transaction->status === 'completed') {
                $senderWallet->decrement('available_balance', $amount);
                $receiverWallet->increment('available_balance', $net);

                // قيود الدفتر
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'wallet_id'      => $senderWallet->id,
                    'entry_type'     => LedgerEntry::TYPE_DEBIT,
                    'amount'         => $amount,
                    'balance_after'  => $senderWallet->available_balance,
                    'created_at'     => now(),
                ]);
                LedgerEntry::create([
                    'transaction_id' => $transaction->id,
                    'wallet_id'      => $receiverWallet->id,
                    'entry_type'     => LedgerEntry::TYPE_CREDIT,
                    'amount'         => $net,
                    'balance_after'  => $receiverWallet->available_balance,
                    'created_at'     => now(),
                ]);
            }
        }
    }
}