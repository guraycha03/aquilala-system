<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Turns a flat rate row into a real rule: the same product can now appear
 * once per application stage (basal, topdress, …) for a crop + season, and
 * every rate carries optional min/max guardrails the engines clamp to.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('decision_formulas', function (Blueprint $table) {
            $table->string('stage', 30)->default('standard')->after('season');
            $table->decimal('min_rate_per_hectare', 8, 2)->nullable()->after('rate_per_hectare');
            $table->decimal('max_rate_per_hectare', 8, 2)->nullable()->after('min_rate_per_hectare');

            // The old key locked one row per product; stages need the key to
            // include the stage column.
            $table->dropUnique('crop_season_product_unique');
            $table->unique(
                ['crop_type', 'season', 'product_id', 'stage'],
                'crop_season_product_stage_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('decision_formulas', function (Blueprint $table) {
            $table->dropUnique('crop_season_product_stage_unique');
            $table->unique(
                ['crop_type', 'season', 'product_id'],
                'crop_season_product_unique'
            );

            $table->dropColumn(['stage', 'min_rate_per_hectare', 'max_rate_per_hectare']);
        });
    }
};
