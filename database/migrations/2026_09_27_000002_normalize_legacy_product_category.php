<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('products') || ! Schema::hasTable('categories')) {
            return;
        }

        $categories = DB::table('categories')
            ->select('id', 'name', 'parent_id')
            ->get()
            ->keyBy('id');

        $updated = 0;

        DB::table('products')
            ->select('id', 'category_id', 'category')
            ->whereNotNull('category_id')
            ->orderBy('id')
            ->each(function ($product) use ($categories, &$updated) {
                $category = $categories->get($product->category_id);

                if (! $category) {
                    return;
                }

                $parent = $category->parent_id ? $categories->get($category->parent_id) : null;

                // The legacy column always holds the TOP-LEVEL group name so it
                // can fall back as a label when the relation is not loaded.
                $expected = $parent?->name ?? $category->name;

                if ($product->category !== $expected) {
                    DB::table('products')->where('id', $product->id)->update(['category' => $expected]);
                    $updated++;
                }
            });
    }

    public function down(): void
    {
        // Deliberately not reverted: the previous values were internally
        // inconsistent (some stored the leaf name, some a shortened alias
        // such as "Seeds" for "Seeds and Planting Materials").
    }
};
