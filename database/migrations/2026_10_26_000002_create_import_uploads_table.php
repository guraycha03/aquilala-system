<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Every file an officer submitted to a bulk importer (members or harvest),
 * kept for good so the original upload stays reachable years later.
 *
 * One row is written when the import is actually confirmed — a preview that
 * is cancelled or left to expire archives nothing. `rows_skipped` counts
 * duplicates that were already on the list; everything not imported and not
 * skipped (invalid rows, unticked OCR records) is total - imported - skipped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('import_uploads', function (Blueprint $table) {
            $table->id();
            // The officer who submitted it. Deleted accounts keep the file.
            $table->foreignId('user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('kind', 20);   // 'members' | 'harvest'
            $table->string('source', 20); // 'file' (CSV/XLSX) | 'ocr' (scanned image)
            $table->string('original_name');
            // Relative path on the private 'local' disk — never web-reachable.
            $table->string('stored_path');
            $table->unsignedBigInteger('size_bytes')->default(0);
            $table->unsignedInteger('rows_total')->default(0);
            $table->unsignedInteger('rows_imported')->default(0);
            $table->unsignedInteger('rows_skipped')->default(0);
            $table->timestamps();

            $table->index(['kind', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('import_uploads');
    }
};
