<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fertilizer_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->decimal('nitrogen_percentage', 5, 2)->nullable();
            $table->decimal('phosphorus_percentage', 5, 2)->nullable();
            $table->decimal('potassium_percentage', 5, 2)->nullable();
            $table->string('form_type', 100)->nullable(); // Granular, Prilled, Liquid, Powder
            $table->decimal('application_rate_min', 10, 2)->nullable();
            $table->decimal('application_rate_max', 10, 2)->nullable();
            $table->string('application_rate_unit', 50)->nullable(); // kg/ha, L/ha
            $table->string('application_method', 255)->nullable();
            $table->string('application_timing', 255)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fertilizer_details');
    }
};

