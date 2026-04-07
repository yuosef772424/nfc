<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('mobile_device_details', function (Blueprint $table) {
            $table->foreignId('device_id')->primary()->constrained('nfc_devices')->onDelete('cascade');
            $table->string('phone_model')->nullable();
            $table->string('phone_os')->nullable();
            $table->string('device_fingerprint')->nullable(); // مشفر
            $table->boolean('nfc_supported')->default(false);
            $table->string('biometric_type')->nullable(); // face | fingerprint | none
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('mobile_device_details');
    }
};