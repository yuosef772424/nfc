<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('physical_device_details', function (Blueprint $table) {
            $table->foreignId('device_id')->primary()->constrained('nfc_devices')->onDelete('cascade');
            $table->string('serial_number')->unique();
            $table->string('installation_location')->nullable();
            $table->date('installation_date')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('physical_device_details');
    }
};