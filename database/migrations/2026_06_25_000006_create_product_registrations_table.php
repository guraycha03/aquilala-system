<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_registrations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('registration_status', 50)->nullable();
            $table->string('registration_authority', 255)->nullable();
            $table->string('registration_number', 255)->nullable();
            $table->string('lot_number', 255)->nullable();
            $table->string('batch_number', 255)->nullable();
            $table->date('manufacturing_date')->nullable();
            $table->date('expiration_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_registrations');
    }
};

