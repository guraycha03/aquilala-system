<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The per-user "sub-pages" navigation preference (sidebar menu vs pill
     * tabs on top) has been removed from the product. Sub-pages always render
     * inside the side menu now, so the column is no longer needed.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sub_pages_style');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sub_pages_style', 20)->default('sidebar')->after('sidebar_theme');
        });
    }
};
