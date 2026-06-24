<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // The requesting Farmer
            $table->string('quotation_number')->unique(); // Unique reference ID for offline checkups
            $table->string('crop_type');
            $table->string('season');
            $table->decimal('land_area', 5, 2); // Land size in Hectares
            $table->decimal('estimated_total_cost', 12, 2)->default(0.00);
            $table->timestamp('valid_until'); // Enforces the time-bound expiration logic window
            $table->timestamps();
        });

        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->onDelete('cascade');
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->string('product_name'); // Snapshot parameter to preserve records during price changes
            $table->decimal('price_at_generation', 10, 2);
            $table->decimal('calculated_quantity', 8, 2);
            $table->decimal('subtotal', 12, 2);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
        Schema::dropIfExists('quotations');
    }
};