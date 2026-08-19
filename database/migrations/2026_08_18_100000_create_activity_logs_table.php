<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('activity_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type');        // quotation.created, quotation.deleted, crop.planted, etc.
            $table->string('subject_type')->nullable(); // App\Models\Quotation, App\Models\FarmerCrop, etc.
            $table->unsignedBigInteger('subject_id')->nullable();
            $table->string('description'); // Human-readable description
            $table->json('metadata')->nullable(); // Extra data: crop_type, amount, etc.
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
            $table->index(['type', 'created_at']);
            $table->index(['subject_type', 'subject_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('activity_logs');
    }
};
