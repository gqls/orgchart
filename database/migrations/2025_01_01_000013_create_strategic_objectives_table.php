<?php

// database/migrations/2023_01_01_000013_create_strategic_objectives_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('strategic_objectives', function (Blueprint $table) {
            $table->id();
            $table->foreignId('strategy_id')->constrained();
            $table->string('name');
            $table->text('description')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('strategic_objectives');
    }
};
