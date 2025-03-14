<?php

// database/migrations/2023_01_01_000007_create_positions_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('positions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained();
            $table->foreignId('department_id')->nullable()->constrained();
            $table->integer('level')->nullable();
            $table->string('title');
            $table->string('employee_id')->nullable();
            $table->string('grade')->nullable();
            $table->string('function')->nullable();
            $table->string('sub_function')->nullable();
            $table->string('region')->nullable();
            $table->string('country')->nullable();
            $table->string('office')->nullable();
            $table->integer('tenure')->nullable();
            $table->decimal('fully_loaded_cost', 12, 2)->nullable();
            $table->string('cost_center')->nullable();
            $table->string('contract_type')->nullable();
            $table->string('manager_id')->nullable();
            $table->string('name')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down()
    {
        Schema::dropIfExists('positions');
    }
};