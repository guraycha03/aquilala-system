<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Budget-aware quotation planning.
     *
     * - quotations.budget_cap: the farmer's stated budget for this planting
     *   (nullable = no limit, so every pre-existing quotation stays valid).
     * - quotation_items.is_deferred: items trimmed from the fitted plan to
     *   respect the budget cap. Deferred lines stay recorded on the quotation
     *   so the farmer and store owner both see the full plan vs. what fits
     *   within the budget right now.
     */
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->decimal('budget_cap', 12, 2)
                ->nullable()
                ->after('land_area')
                ->comment('Farmer-stated budget for this planting; null = no limit');
        });

        Schema::table('quotation_items', function (Blueprint $table) {
            $table->boolean('is_deferred')
                ->default(false)
                ->after('override_quantity')
                ->comment('Trimmed to fit the budget cap; excluded from the fitted total');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('is_deferred');
        });

        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('budget_cap');
        });
    }
};
