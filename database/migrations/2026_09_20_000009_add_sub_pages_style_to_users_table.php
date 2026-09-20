<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Per-user preference for how grouped "sub-pages" are presented:
     *   'top'     – pill tab bar pinned along the top of the page (default)
     *   'sidebar' – sub-page buttons rendered inside the side menu,
     *               directly below the active menu item.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('sub_pages_style', 20)->default('top')->after('sidebar_theme');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('sub_pages_style');
        });
    }
};
