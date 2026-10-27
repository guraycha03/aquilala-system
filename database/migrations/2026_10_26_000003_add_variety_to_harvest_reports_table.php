<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('harvest_reports', function (Blueprint $table) {
            // The seed/variety label exactly as filed ("NSIC Rc 222 (Tubigan 18)",
            // "Golden Baby F1") — mirrors what farmer_crops already stores, so an
            // imported report carries the same registry-backed label a planted
            // batch would. Nullable: old files simply have no variety column.
            $table->string('variety', 100)
                ->nullable()
                ->after('crop_type')
                ->comment('Seed/variety label as filed, from config/crop_library.php');
            $table->string('variety_key', 60)
                ->nullable()
                ->after('variety')
                ->comment('Stable library key; "general" for Local / General variety');
        });
    }

    public function down(): void
    {
        Schema::table('harvest_reports', function (Blueprint $table) {
            $table->dropColumn(['variety', 'variety_key']);
        });
    }
};
