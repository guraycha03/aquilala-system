<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saved AI harvest reports.
     *
     * A report is generated once. Both language versions are stored with it so
     * switching English/Tagalog later is instant and never calls the model
     * again — the report a officer downloads months from now is the one they
     * generated on this date.
     */
    public function up(): void
    {
        Schema::create('generated_harvest_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->json('filters');
            $table->json('context');
            $table->longText('narrative_en');
            $table->longText('narrative_tl');
            $table->string('default_language', 2)->default('en');
            $table->string('source', 20)->default('fallback');
            $table->string('model', 100)->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('generated_harvest_reports');
    }
};
