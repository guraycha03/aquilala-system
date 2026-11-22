<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Optional LLM explanations live beside the saved report, never inside it.
     *
     * Each language keeps its own cached text, source, model and context hash.
     * Changing the official report snapshot invalidates the cached insight,
     * while an absent or failed Groq call simply keeps the deterministic text.
     */
    public function up(): void
    {
        Schema::table('generated_harvest_reports', function (Blueprint $table) {
            $table->json('ai_insights')->nullable()->after('model');
        });
    }

    public function down(): void
    {
        Schema::table('generated_harvest_reports', function (Blueprint $table) {
            $table->dropColumn('ai_insights');
        });
    }
};
