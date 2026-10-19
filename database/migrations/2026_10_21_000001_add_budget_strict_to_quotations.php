<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Persist whether the quotation was built with "Fit suggestions to my
 * budget" on. The views need it to tell a plan that sits inside the
 * flexible band (up to twice the stated budget) apart from a plain
 * over-budget plan — a total alone cannot say which it is.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->boolean('budget_strict')
                ->default(false)
                ->after('budget_cap')
                ->comment('Fit mode: ceiling is twice budget_cap; false = budget is only a guide');
        });
    }

    public function down(): void
    {
        Schema::table('quotations', function (Blueprint $table) {
            $table->dropColumn('budget_strict');
        });
    }
};
