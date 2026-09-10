<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_crops', function (Blueprint $table) {
            $table->foreignId('product_id')
                ->nullable()
                ->after('crop_id')
                ->constrained('products')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('farmer_crops', function (Blueprint $table) {
            $table->dropConstrainedForeignId('product_id');
        });
    }
};