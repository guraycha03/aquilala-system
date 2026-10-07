<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Soft-delete (archive) support for products.
     *
     * Store owners "delete" a product from their storefront; the row and all
     * of its images/specs stay in the database so the listing can be restored
     * later. Every product query outside the archive screen then excludes
     * trashed rows automatically via the model's SoftDeletes global scope.
     */
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->timestamp('deleted_at')->nullable()->after('status');

            $table->index('deleted_at');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['deleted_at']);
            $table->dropColumn('deleted_at');
        });
    }
};
