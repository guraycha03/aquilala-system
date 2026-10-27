<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The legal paperwork an administrator holds for an accredited store.
     *
     * Store owners are outsiders, so accreditation rests on documents the
     * association collects itself — one paper at a time, each labelled with
     * the type chosen from the dropdown. Files stay on the private disk:
     * business permits are not public material.
     */
    public function up(): void
    {
        Schema::create('store_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete()
                ->comment('The store owner account this paper belongs to');
            $table->string('document_type', 120)
                ->comment('Type picked from the dropdown, e.g. Mayor\'s Permit');
            $table->string('original_name', 255)
                ->comment('Filename as it was uploaded');
            $table->string('path', 500)
                ->comment('Path on the private disk');
            $table->string('mime_type', 100)->nullable();
            $table->unsignedBigInteger('size_bytes')->nullable();
            $table->foreignId('uploaded_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Administrator who filed the paper');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('store_documents');
    }
};
