<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('otp_verifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->onDelete('cascade');
            $table->string('purpose'); // registration | withdrawal | pin_change | login
            $table->string('code_hash'); // مشفر
            $table->boolean('is_used')->default(false);
            $table->timestamp('expires_at');
            $table->timestamps();

            $table->index('user_id');
            $table->index('code_hash');
            $table->index('expires_at');
        });
    }

    public function down()
    {
        Schema::dropIfExists('otp_verifications');
    }
};