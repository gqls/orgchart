<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->json('permissions')->nullable()->after('is_admin');
        });
    }

    public function down()
    {
        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropColumn('permissions');
        });
    }
};