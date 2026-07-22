<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_applications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('crop_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('application_rate_min', 10, 2)->nullable();
            $table->decimal('application_rate_max', 10, 2)->nullable();
            $table->string('application_rate_unit', 50)->nullable();
            $table->string('application_method', 255)->nullable();
            $table->string('application_timing', 255)->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_applications');
    }
};

