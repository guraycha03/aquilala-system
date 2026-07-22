<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_images', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('image_type', 50)->default('main'); // main, packaging, label, specification
            $table->string('file_path', 500);
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->integer('file_size')->nullable(); // bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_images');
    }
};

