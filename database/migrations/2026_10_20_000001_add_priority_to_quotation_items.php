<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Budget-fitting tier per line, persisted so the farmer-facing views
     * can tell the farmer which lines are essentials when nothing fits a
     * strict budget (and keep the deferral story transparent).
     *
     * essential   — seeds and fertilizer: no crop without them
     * situational — lime/irrigation lines the soil/weather rules added
     * optional    — crop protection: protects yield but can be deferred
     */
    public function up(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->string('priority', 20)
                ->default('essential')
                ->after('is_deferred')
                ->comment('Budget-fitting tier: essential, situational, or optional');
        });
    }

    public function down(): void
    {
        Schema::table('quotation_items', function (Blueprint $table) {
            $table->dropColumn('priority');
        });
    }
};
