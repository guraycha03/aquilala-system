<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 100)->nullable(); // label, technical_data_sheet, safety_data_sheet, certificate, manual
            $table->string('title', 255);
            $table->string('file_path', 500);
            $table->string('original_filename', 255)->nullable();
            $table->string('mime_type', 100)->nullable();
            $table->integer('file_size')->nullable(); // bytes
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_documents');
    }
};

