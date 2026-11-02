<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_crop_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('crop_type');
            $table->timestamps();

            $table->unique(['product_id', 'crop_type']);
            $table->index('crop_type');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_crop_types');
    }
};
