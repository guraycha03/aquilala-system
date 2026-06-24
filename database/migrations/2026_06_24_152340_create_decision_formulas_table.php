<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('decision_formulas', function (Blueprint $table) {
            $table->id();
            $table->string('crop_type'); // e.g., Rice, Corn
            $table->enum('season', ['wet', 'dry']);
            // Links directly to the calculated item
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            // Required quantity needed strictly per Single Hectare (1.00) of agricultural land
            $table->decimal('rate_per_hectare', 8, 2); 
            $table->string('unit'); // e.g., Bags, Kilograms, Liters
            $table->text('notes')->nullable(); // Explains rule logic constraints (e.g., "Flood resistant")
            $table->timestamps();

            $table->unique(['crop_type', 'season', 'product_id'], 'crop_season_product_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('decision_formulas');
    }
};