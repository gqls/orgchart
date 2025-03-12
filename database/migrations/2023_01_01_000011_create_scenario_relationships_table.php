<?php

// database/migrations/2023_01_01_000011_create_scenario_relationships_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('scenario_relationships', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained();
            $table->foreignId('manager_position_id')->constrained('positions');
            $table->foreignId('direct_report_position_id')->constrained('positions');
            $table->timestamps();

            $table->unique(['scenario_id', 'direct_report_position_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('scenario_relationships');
    }
};