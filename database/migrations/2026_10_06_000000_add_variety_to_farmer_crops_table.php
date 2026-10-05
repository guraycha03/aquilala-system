<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('farmer_crops', function (Blueprint $table) {
            $table->string('variety', 100)
                ->nullable()
                ->after('crop_id')
                ->comment('Variety display name chosen in the New Crop Batch form');
            $table->string('variety_key', 60)
                ->nullable()
                ->after('variety')
                ->comment('Stable library key; "general" for Local / General variety');
        });
    }

    public function down(): void
    {
        Schema::table('farmer_crops', function (Blueprint $table) {
            $table->dropColumn(['variety', 'variety_key']);
        });
    }
};
