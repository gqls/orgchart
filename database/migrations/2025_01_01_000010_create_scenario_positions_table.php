<?php

// database/migrations/2023_01_01_000010_create_scenario_positions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('scenario_positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained();
            $table->foreignId('position_id')->constrained();
            $table->enum('status', ['unchanged', 'new', 'changed', 'removed'])->default('unchanged');
            $table->timestamps();

            $table->unique(['scenario_id', 'position_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('scenario_positions');
    }
};