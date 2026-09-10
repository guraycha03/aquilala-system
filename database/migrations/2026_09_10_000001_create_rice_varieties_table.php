<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rice_varieties', function (Blueprint $table) {
            $table->id();
            $table->integer('year_approved')->nullable();
            $table->string('registration_number')->nullable()->index();
            $table->string('variety_name');
            $table->string('variety_type')->nullable(); // e.g., Inbred, Hybrid
            $table->integer('maturity_days')->nullable(); // standardized Days to Harvest
            $table->decimal('target_yield_kg_ha', 12, 2)->nullable(); // standardized target yield
            $table->json('attributes')->nullable(); // unmapped / dataset-specific columns
            $table->string('data_source')->nullable(); // provider or file identifier
            $table->timestamps();

            $table->index('variety_name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rice_varieties');
    }
};