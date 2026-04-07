<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('cards', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->foreignId('agent_id')->nullable()->constrained('users')->onDelete('set null');
            $table->string('card_number')->unique();
            $table->string('pin_hash')->nullable();
            $table->string('nfc_uid')->unique(); // مشفر
            $table->string('nfc_key_ref')->nullable(); // مرجع مفتاح KMS
            $table->string('status')->default('active'); // active | blocked | expired
            $table->date('expiry_date');
            $table->timestamps();

            $table->index('wallet_id');
            $table->index('agent_id');
        });
    }

    public function down()
    {
        Schema::dropIfExists('cards');
    }
};