<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('farmer_crops', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_id')->constrained()->cascadeOnDelete();
            $table->string('batch_name')->nullable();
            $table->decimal('land_area', 8, 2)->default(1);
            $table->string('land_area_unit', 10)->default('ha');
            $table->date('planting_date');
            $table->date('expected_harvest_date')->nullable();
            $table->date('actual_harvest_date')->nullable();
            $table->enum('growth_stage', ['land_prep', 'seeding', 'vegetative', 'reproductive', 'ripening', 'harvested', 'failed'])->default('seeding');
            $table->enum('season', ['wet', 'dry'])->default('wet');
            $table->decimal('estimated_yield', 10, 2)->nullable();
            $table->string('yield_unit', 20)->nullable();
            $table->decimal('actual_yield', 10, 2)->nullable();
            $table->decimal('estimated_cost', 12, 2)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('farmer_crops');
    }
};
