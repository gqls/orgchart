<?php

// database/migrations/2023_01_01_000005_create_organization_user_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained();
            $table->foreignId('user_id')->constrained();
            $table->foreignId('role_id')->nullable()->constrained();
            $table->boolean('is_admin')->default(false);
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });
    }

    public function down()
    {
        Schema::dropIfExists('organization_user');
    }
};
