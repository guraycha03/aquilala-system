<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Distinguish a real crop from a category label that happens to be sitting in
 * the crop table.
 *
 * The crops table carries rows such as "Vegetables", "Legumes", "Root Crops" and
 * "Other". They are grouping labels, not plantable crops, and the decision
 * formula matrix points at "Vegetables" and "Fruits" as crop types - which is
 * useful for storing inputs against a group, and misleading the moment a
 * farmer is shown "Vegetables" as something to plant.
 *
 * The records are legitimate: other parts of the application group by category,
 * and the formulas attached to them are real rows that other reports read. So
 * nothing is deleted here. The records gain an explicit flag, and the assistant
 * leaves them out of the lists a farmer reads as crop choices.
 *
 * A row is flagged when its name is its own category, or a plural of its own
 * category. That is the same test the data already passes - "Vegetables" is
 * categorised as "Vegetable", "Root Crops" as "Root Crop" - so the flag records
 * a fact that was already visible rather than a new opinion about the data.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('crops', 'is_category_placeholder')) {
            Schema::table('crops', function (Blueprint $table) {
                $table->boolean('is_category_placeholder')
                    ->default(false)
                    ->after('category')
                    ->comment('True when this row is a category label rather than a plantable crop.');
            });
        }

        DB::table('crops')->orderBy('id')->each(function ($crop) {
            $name = mb_strtolower(trim((string) $crop->name));
            $category = mb_strtolower(trim((string) $crop->category));

            if ($name === '' || $category === '') {
                return;
            }

            $isPlaceholder = $name === $category
                || $name === $category . 's'
                || $name === rtrim($category, 's') . 's'
                || $name === rtrim($category, 's');

            if ($isPlaceholder && ! $crop->is_category_placeholder) {
                DB::table('crops')->where('id', $crop->id)->update([
                    'is_category_placeholder' => true,
                ]);
            }
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('crops', 'is_category_placeholder')) {
            Schema::table('crops', function (Blueprint $table) {
                $table->dropColumn('is_category_placeholder');
            });
        }
    }
};
