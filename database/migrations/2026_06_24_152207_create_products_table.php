<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            // Tracks which Store Owner or Cooperative Officer manages this supply profile
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('sku')->unique();
            $table->string('name');
            $table->string('category'); // e.g., Fertilizer, Seed, Pesticide
            $table->decimal('price', 10, 2);
            $table->integer('stock_level')->default(0);
            $table->integer('reorder_threshold')->default(10); // Visual indicator flag value
            $table->enum('recommended_season', ['wet', 'dry', 'both'])->default('both');
            $table->text('description')->nullable();
            $table->timestamps();

            // Indexes for optimizing dashboard analytical load times
            $table->index(['category', 'stock_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};