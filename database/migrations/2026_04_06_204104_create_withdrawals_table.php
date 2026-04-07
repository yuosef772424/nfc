<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('withdrawals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('wallet_id')->constrained('wallets')->onDelete('cascade');
            $table->foreignId('agent_id')->constrained('users')->onDelete('cascade');
            
            $table->decimal('requested_amount', 15, 2);
            $table->decimal('commission_amount', 10, 2);
            $table->decimal('total_amount', 15, 2);
            
            $table->string('commission_type')->nullable(); // نسخة وقت السحب
            $table->decimal('commission_value', 10, 2)->nullable();
            
            $table->string('verification_code'); // مشفر
            $table->timestamp('expires_at');
            $table->string('status')->default('pending'); // pending | completed | failed | cancelled
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->index('wallet_id');
            $table->index('agent_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('withdrawals');
    }
};