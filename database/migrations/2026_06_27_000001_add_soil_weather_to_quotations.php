<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Add soil_type and weather_context fields to the quotations table
     * to support the CostQuotationEngine's dynamic multiplier logic.
     */
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->string('soil_type')
                ->default('clay_loam')
                ->after('season')
                ->comment('clay_loam, sandy, or peat');

            $table->string('weather_context')
                ->default('normal')
                ->after('soil_type')
                ->comment('normal, low_rain, typhoon, or el_nino');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn(['soil_type', 'weather_context']);
        });
    }
};

