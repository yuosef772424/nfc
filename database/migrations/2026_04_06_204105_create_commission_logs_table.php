<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('commission_logs', function (Blueprint $table) {
            $table->id();
            $table->string('reference_type'); // withdrawal | wallet_transaction
            $table->unsignedBigInteger('reference_id');
            $table->string('recipient_type'); // agent | company | merchant | system
            $table->foreignId('recipient_id')->constrained('users')->onDelete('cascade');
            $table->decimal('amount', 12, 2);
            $table->string('status')->default('pending'); // pending | paid | cancelled
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['reference_type', 'reference_id']);
            $table->index('recipient_id');
            $table->index('status');
        });
    }

    public function down()
    {
        Schema::dropIfExists('commission_logs');
    }
};