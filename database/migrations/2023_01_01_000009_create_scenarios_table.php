<?php

// database/migrations/2023_01_01_000009_create_scenarios_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('scenarios', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->string('name');
            $table->text('description')->nullable();
            $table->boolean('is_current')->default(false);
            $table->boolean('is_base')->default(false);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('scenarios');
    }
};