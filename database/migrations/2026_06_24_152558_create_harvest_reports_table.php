<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('harvest_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade'); // Reporting Farmer
            $table->foreignId('quotation_id')->nullable()->constrained()->onDelete('set null'); // Associated estimate
            $table->string('crop_type');
            $table->enum('season', ['wet', 'dry']);
            $table->decimal('land_area', 5, 2);
            $table->decimal('actual_yield_metric_tons', 8, 2); // For tracking performance output changes
            $table->decimal('actual_total_expenses', 12, 2); // For checking deviation variations
            $table->date('harvest_date');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('harvest_reports');
    }
};