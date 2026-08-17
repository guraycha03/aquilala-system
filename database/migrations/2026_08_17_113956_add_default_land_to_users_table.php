<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->decimal('default_land_area', 8, 2)->nullable()->after('province');
            $table->string('default_land_area_unit', 10)->nullable()->after('default_land_area');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['default_land_area', 'default_land_area_unit']);
        });
    }
};
