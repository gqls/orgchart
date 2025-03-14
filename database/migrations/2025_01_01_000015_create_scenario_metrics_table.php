<?php

// database/migrations/2023_01_01_000015_create_scenario_metrics_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('scenario_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('scenario_id')->constrained();
            $table->foreignId('metric_id')->constrained();
            $table->decimal('value', 15, 2);
            $table->decimal('goal', 15, 2)->nullable();
            $table->decimal('benchmark', 15, 2)->nullable();
            $table->timestamps();

            $table->unique(['scenario_id', 'metric_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('scenario_metrics');
    }
};
