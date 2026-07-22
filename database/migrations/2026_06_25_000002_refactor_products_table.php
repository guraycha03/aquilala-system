<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add new columns - non-destructive, preserves existing data
        Schema::table('products', function (Blueprint $table) {
            // Category relationship
            $table->foreignId('category_id')->nullable()->constrained()->nullOnDelete();

            // Brand / manufacturer
            $table->string('brand')->nullable()->after('name');

            // Product type descriptor (e.g., "Premium NPK Compound Fertilizer")
            $table->string('product_type')->nullable()->after('sku');

            // Packaging dimensions
            $table->decimal('package_size', 10, 2)->nullable()->after('description');
            $table->string('package_unit', 50)->nullable()->after('package_size');
            $table->string('packaging_type', 100)->nullable()->after('package_unit');

            // Price unit qualifier (e.g., "Per Sack", "Per Kg", "Per Liter")
            $table->string('price_unit', 100)->nullable()->after('price');

            // Renamed inventory fields
            $table->integer('stock_quantity')->default(0)->after('price_unit');
            $table->string('stock_unit', 50)->nullable()->after('stock_quantity');

            // Product status
            $table->enum('status', ['active', 'inactive', 'out_of_stock', 'discontinued'])->default('active')->after('recommended_season');
        });

        // Backfill: copy existing values to new columns
        DB::statement('UPDATE products SET stock_quantity = stock_level');
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn([
                'category_id',
                'brand',
                'product_type',
                'package_size',
                'package_unit',
                'packaging_type',
                'price_unit',
                'stock_quantity',
                'stock_unit',
                'status',
            ]);
        });
    }
};

