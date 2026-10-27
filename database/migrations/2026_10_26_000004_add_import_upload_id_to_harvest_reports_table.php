<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Which uploaded file each harvest record came from.
 *
 * Bulk-imported rows carry the id of the ImportUpload that produced them, so
 * an officer who was handed the wrong spreadsheet can remove exactly that
 * file's records in one action from the Uploads list instead of hunting row
 * by row. The link is nullable: manual reports and rows imported before this
 * column existed simply have none.
 *
 * The backfill only links pre-existing rows when the archive is
 * unambiguous — exactly one harvest upload exists and its imported count
 * equals the number of records on the list, i.e. that file is what the whole
 * table is made of. Anything less certain is left null rather than guessed.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvest_reports', function (Blueprint $table) {
            $table->foreignId('import_upload_id')
                ->nullable()
                ->after('quotation_id')
                ->constrained('import_uploads')
                ->nullOnDelete();
        });

        $harvestUploads = DB::table('import_uploads')
            ->where('kind', 'harvest')
            ->orderBy('id')
            ->get();

        if ($harvestUploads->count() === 1
            && (int) $harvestUploads->first()->rows_imported === DB::table('harvest_reports')->count()) {
            DB::table('harvest_reports')
                ->whereNull('import_upload_id')
                ->update(['import_upload_id' => $harvestUploads->first()->id]);
        }
    }

    public function down(): void
    {
        Schema::table('harvest_reports', function (Blueprint $table) {
            $table->dropForeign(['import_upload_id']);
            $table->dropColumn('import_upload_id');
        });
    }
};
