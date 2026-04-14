<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('sessionss', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('token_hash')->unique();
            $table->json('device_info')->nullable();
            $table->json('location')->nullable();
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('sessionss');
    }
};