<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * `stock_level` predates `stock_quantity` and stopped being written when
     * the inventory fields were renamed, so every existing row kept its default
     * of 0 while the real quantity lived in `stock_quantity`. Product::booted()
     * now mirrors the value on save; this backfills the rows that predate it.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('products', 'stock_level')
            || ! Schema::hasColumn('products', 'stock_quantity')) {
            return;
        }

        DB::table('products')
            ->whereColumn('stock_level', '!=', 'stock_quantity')
            ->update([
                'stock_level' => DB::raw('stock_quantity'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        // No reversal: the legacy column is a mirror of stock_quantity, so
        // restoring stale zeros would re-introduce the mismatch this fixes.
    }
};
