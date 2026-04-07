<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('app_config', function (Blueprint $table) {
            $table->id();
            $table->string('group');          // policy, constant, setting, feature
            $table->string('key');            // المفتاح داخل المجموعة
            $table->text('value')->nullable();
            $table->string('data_type')->default('string'); // string, number, boolean, json
            $table->string('label')->nullable(); // وصف readable
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->json('meta')->nullable();  // بيانات إضافية (scope, priority, category)
            $table->timestamps();

            $table->unique(['group', 'key']);
            $table->index('group');
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('app_config');
    }
};