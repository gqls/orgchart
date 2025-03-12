<?php

// database/migrations/2023_01_01_000014_create_metrics_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained();
            $table->string('name');
            $table->string('code')->unique();
            $table->text('description')->nullable();
            $table->string('unit')->nullable();
            $table->string('format')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('metrics');
    }
};