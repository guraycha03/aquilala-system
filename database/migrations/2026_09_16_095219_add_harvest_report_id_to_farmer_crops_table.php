<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_crops', function (Blueprint $table) {
            $table->foreignId('harvest_report_id')
                ->nullable()
                ->after('product_id')
                ->constrained('harvest_reports')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('farmer_crops', function (Blueprint $table) {
            $table->dropForeign(['harvest_report_id']);
            $table->dropColumn('harvest_report_id');
        });
    }
};