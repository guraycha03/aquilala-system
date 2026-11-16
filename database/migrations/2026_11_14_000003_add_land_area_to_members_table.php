<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->decimal('land_area', 8, 2)
                ->nullable()
                ->after('land_ownership')
                ->comment('Total land area recorded by an administrator');
            $table->string('land_area_unit', 10)
                ->nullable()
                ->after('land_area')
                ->comment('ha / sq_m / sq_ft');
        });
    }

    public function down(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->dropColumn(['land_area', 'land_area_unit']);
        });
    }
};
