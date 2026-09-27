<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Moves the measure out of `products.name` and into the `package_size` /
 * `package_unit` columns that already back the `display_name` accessor.
 *
 * Names look like "Urea Fertilizer (46-0-0) — 50 kg". We only strip a
 * trailing measure when it agrees with what is already stored, so a name
 * that carries information the size columns do not (e.g. a 14 in blade on a
 * product stocked as "1 piece") is left untouched rather than silently
 * losing data. Rows with no size on record get it filled from the name.
 */
return new class extends Migration
{
    /**
     * Matches a trailing measure after a dash-like separator:
     *   "— 50 kg", "- 500 mL", "— per piece", "— 14 in"
     */
    private const MEASURE_PATTERN = '/\s*[\x{2014}\x{2013}\x{2012}-]\s*(?:(\d+(?:[.,]\d+)?)\s*([a-zA-Z]+)\.?|per\s+([a-zA-Z]+)\.?)\s*$/u';

    public function up(): void
    {
        DB::table('products')
            ->select('id', 'name', 'package_size', 'package_unit')
            ->orderBy('id')
            ->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    $this->splitMeasure($product);
                }
            });
    }

    public function down(): void
    {
        // Re-append the stored measure so names read the way they did before.
        DB::table('products')
            ->select('id', 'name', 'package_size', 'package_unit')
            ->orderBy('id')
            ->chunkById(200, function ($products) {
                foreach ($products as $product) {
                    $measure = $this->formatMeasure($product->package_size, $product->package_unit);

                    if ($measure === null || trim((string) $product->name) === '') {
                        continue;
                    }

                    $name = trim((string) $product->name);

                    if (str_contains($name, '— ' . $measure)) {
                        continue;
                    }

                    DB::table('products')
                        ->where('id', $product->id)
                        ->update(['name' => $name . ' — ' . $measure]);
                }
            });
    }

    private function splitMeasure(object $product): void
    {
        $name = (string) $product->name;

        if (!preg_match(self::MEASURE_PATTERN, $name, $matches)) {
            return;
        }

        // "per piece" means one piece; "50 kg" is an explicit quantity.
        $isPer = ($matches[3] ?? '') !== '';
        $size = $isPer ? 1.0 : (float) str_replace(',', '.', $matches[1]);
        $unit = trim($isPer ? $matches[3] : ($matches[2] ?? ''));

        if ($unit === '') {
            return;
        }

        $cleanName = trim(preg_replace(self::MEASURE_PATTERN, '', $name));

        if ($cleanName === '') {
            return;
        }

        $hasStoredSize = $product->package_size !== null
            || ($product->package_unit !== null && trim((string) $product->package_unit) !== '');

        // Nothing stored yet: adopt the measure parsed out of the name.
        if (!$hasStoredSize) {
            DB::table('products')->where('id', $product->id)->update([
                'name'          => $cleanName,
                'package_size'  => $size,
                'package_unit'  => $unit,
            ]);

            return;
        }

        // Stored size agrees with the trailing measure: safe to drop it.
        $sizeAgrees = $product->package_size !== null
            && abs((float) $product->package_size - $size) < 0.0001;
        $unitAgrees = strcasecmp(trim((string) $product->package_unit), $unit) === 0;

        if ($sizeAgrees && $unitAgrees) {
            DB::table('products')->where('id', $product->id)->update(['name' => $cleanName]);
        }
    }

    private function formatMeasure($size, $unit): ?string
    {
        if ($size === null) {
            return null;
        }

        $number = rtrim(rtrim(number_format((float) $size, 2, '.', ''), '0'), '.');
        $unit = trim((string) $unit);

        return $unit === '' ? $number : $number . ' ' . $unit;
    }
};
