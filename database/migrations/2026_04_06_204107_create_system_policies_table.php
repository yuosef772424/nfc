<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('system_policies', function (Blueprint $table) {
            $table->id();
            $table->string('key');
            $table->text('value');
            $table->string('data_type')->default('string'); // string | number | boolean | json
            $table->string('category')->nullable(); // fees | limits | security | system
            $table->string('scope_type')->default('global'); // global | user | agent | merchant
            $table->unsignedBigInteger('scope_id')->nullable(); // null for global
            $table->string('description')->nullable();
            $table->boolean('is_active')->default(true);
            $table->integer('priority')->default(0);
            $table->timestamps();

            $table->index(['key', 'scope_type', 'scope_id']);
            $table->index('is_active');
        });
    }

    public function down()
    {
        Schema::dropIfExists('system_policies');
    }
};