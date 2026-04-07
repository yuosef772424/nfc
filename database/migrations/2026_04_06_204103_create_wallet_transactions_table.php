<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('wallet_transactions', function (Blueprint $table) {
            $table->id();
            $table->uuid('transaction_uuid')->unique();
            
            $table->foreignId('sender_wallet_id')->nullable()->constrained('wallets')->onDelete('set null');
            $table->foreignId('receiver_wallet_id')->nullable()->constrained('wallets')->onDelete('set null');
            
            $table->foreignId('sender_card_id')->nullable()->constrained('cards')->onDelete('set null');
            $table->foreignId('sender_device_id')->nullable()->constrained('nfc_devices')->onDelete('set null');
            $table->foreignId('receiver_device_id')->nullable()->constrained('nfc_devices')->onDelete('set null');
            
            $table->string('type'); // payment | transfer | deposit | withdrawal | refund
            $table->decimal('amount', 15, 2);
            $table->decimal('fee', 10, 2)->default(0.00);
            $table->decimal('net_amount', 15, 2);
            $table->string('currency')->default('YER');
            
            $table->string('status')->default('pending'); // pending | completed | failed | cancelled
            $table->text('failure_reason')->nullable();
            $table->string('failure_code')->nullable();
            
            $table->text('description')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamps();

            $table->index('sender_wallet_id');
            $table->index('receiver_wallet_id');
            $table->index('status');
            $table->index('created_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('wallet_transactions');
    }
};