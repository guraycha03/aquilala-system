<?php

namespace App\Rules;

use App\Models\User;
use App\Services\CostQuotationEngine;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/**
 * CropRecommendationEngine
 *
 * The authority on "how much of what, for this crop". Every quantity, rate and
 * peso figure the assistant shows comes from here.
 *
 * It deliberately holds no arithmetic of its own: the rate math, the soil and
 * weather adjustments, the whole-unit rounding and the contingency fees all
 * belong to CostQuotationEngine, which already backs the farmer's quotation
 * flow. Reimplementing them here would create a second, quietly divergent set
 * of numbers — the exact failure the AI layer is meant to remove.
 *
 * What this class adds is the part the LLM must never do: refuse to produce a
 * number at all when the farmer's inputs are incomplete.
 */
class CropRecommendationEngine
{
    public const STATUS_OK = 'ok';

    public const STATUS_MISSING_INPUT = 'missing_input';

    public const STATUS_NO_RULES = 'no_rules';

    /**
     * Soil and weather are refinements, not requirements. When the farmer has
     * not said, the engine assumes the neutral case and *says so* in the
     * payload, so the answer can disclose the assumption rather than bury it.
     */
    public const DEFAULT_SOIL = 'clay_loam';

    public const DEFAULT_WEATHER = 'normal';

    public function __construct(private readonly CostQuotationEngine $quotations)
    {
    }

    /**
     * @param  array{crop?: string|null, season?: string|null, area?: float|null, soil?: string|null, weather?: string|null}  $input
     * @return array{
     *     status: string,
     *     missing: array<int, string>,
     *     assumptions: array<int, string>,
     *     crop: string|null,
     *     season: string|null,
     *     area: float|null,
     *     items: array<int, array<string, mixed>>,
     *     summary: array<string, mixed>|null,
     *     formula_ids: array<int, int>
     * }
     */
    public function evaluate(array $input, ?User $user = null): array
    {
        // The user asking the question, recorded for provenance only.
        $askedBy = $user;
        $crop   = $this->clean($input['crop'] ?? null);
        $season = in_array($input['season'] ?? null, ['wet', 'dry'], true) ? $input['season'] : null;
        $area   = isset($input['area']) && is_numeric($input['area']) ? (float) $input['area'] : null;
        $soil   = in_array($input['soil'] ?? null, ['clay_loam', 'sandy', 'peat'], true) ? $input['soil'] : self::DEFAULT_SOIL;
        $weather = in_array($input['weather'] ?? null, ['normal', 'low_rain', 'typhoon', 'el_nino'], true)
            ? $input['weather']
            : self::DEFAULT_WEATHER;

        $missing = [];

        // The crop and the area are the two inputs the math cannot run without.
        // Season only narrows which formula row applies; without it the
        // assistant can still name the crop's products, just not their rates.
        if ($crop === null || $crop === '') {
            $missing[] = 'crop';
        }

        if ($area === null || $area <= 0) {
            $missing[] = 'area';
        }

        $assumptions = [];

        if ($crop !== null && $crop !== '' && $season === null) {
            $missing[] = 'season';
        }

        if ($soil === self::DEFAULT_SOIL && empty($input['soil'])) {
            $assumptions[] = 'Soil type was not given, so clay loam was used.';
        }

        if ($weather === self::DEFAULT_WEATHER && empty($input['weather'])) {
            $assumptions[] = 'Weather context was not given, so normal conditions were used.';
        }

        $empty = [
            'items'        => [],
            'summary'      => null,
            'formula_ids'  => [],
        ];

        if ($missing !== []) {
            return array_merge($empty, [
                'status'      => self::STATUS_MISSING_INPUT,
                'missing'     => $missing,
                'assumptions' => $assumptions,
                'crop'        => $crop,
                'season'      => $season,
                'area'        => $area,
            ]);
        }

        try {
            $computed = $this->quotations->calculate([
                'crop_type'        => $crop,
                'season'           => $season,
                'soil_type'        => $soil,
                'weather_context'  => $weather,
                'land_area'        => $area,
            ], $this->storeContext($askedBy));
        } catch (ValidationException) {
            return array_merge($empty, [
                'status'      => self::STATUS_MISSING_INPUT,
                'missing'     => ['area'],
                'assumptions' => $assumptions,
                'crop'        => $crop,
                'season'      => $season,
                'area'        => $area,
            ]);
        }

        $items = $this->presentItems($computed['items'], $area);

        if ($items === []) {
            return array_merge($empty, [
                'status'      => self::STATUS_NO_RULES,
                'missing'     => [],
                'assumptions' => $assumptions,
                'crop'        => $crop,
                'season'      => $season,
                'area'        => $area,
            ]);
        }

        return [
            'status'      => self::STATUS_OK,
            'missing'     => [],
            'assumptions' => $assumptions,
            'crop'        => $crop,
            'season'      => $season,
            'area'        => $area,
            'items'       => $items,
            'summary'     => $computed['summary'],
            'formula_ids' => array_map('intval', array_column($computed['items'], 'product_id')),
        ];
    }

    /**
     * The engine's line items as the assistant is allowed to repeat them.
     *
     * Product names and prices are re-read from the database rather than
     * trusted from the engine's snapshot, so a product renamed or repriced
     * between the calculation and the response still shows current values.
     *
     * @param  array<int, array<string, mixed>>  $items
     * @return array<int, array<string, mixed>>
     */
    private function presentItems(array $items, float $area): array
    {
        $ids = array_values(array_unique(array_filter(array_map(
            static fn ($item) => isset($item['product_id']) ? (int) $item['product_id'] : null,
            $items
        ))));

        $products = \App\Models\Product::active()
            ->whereIn('id', $ids)
            ->get()
            ->keyBy('id');

        $presented = [];

        foreach ($items as $item) {
            $product = $products->get((int) $item['product_id']);

            // A line item whose product has since gone inactive is not shown.
            if ($product === null) {
                continue;
            }

            $presented[] = [
                'product_id'        => (int) $product->id,
                'product_name'      => $product->display_name,
                'category'          => $product->category_display_name,
                'price'             => (float) $product->price,
                'quantity'          => (float) $item['adjusted_quantity'],
                'unit'              => $item['unit'],
                'rate_per_hectare'  => $this->rateFor($item, $area),
                'subtotal'          => (float) $item['subtotal'],
                'adjustments'       => array_values($item['adjustments'] ?? []),
                'in_stock'          => $product->status === 'active' && (int) $product->stock_quantity > 0,
                'stock_status'      => $product->stock_status,
            ];
        }

        return $presented;
    }

    /**
     * The per-hectare rate behind a line item, recovered by dividing the base
     * quantity the engine computed by the area it was computed for. Reported
     * only when that division is meaningful, so a zero-area line never shows a
     * fabricated rate of zero.
     *
     * @param  array<string, mixed>  $item
     */
    private function rateFor(array $item, float $area): ?float
    {
        if ($area <= 0) {
            return null;
        }

        $base = (float) ($item['base_quantity'] ?? 0);

        return $base > 0 ? round($base / $area, 2) : null;
    }

    /**
     * The crops the association actually has approved formulas for.
     *
     * @return Collection<int, string>
     */
    public function supportedCrops(): Collection
    {
        return \App\Models\DecisionFormula::select('crop_type')
            ->distinct()
            ->orderBy('crop_type')
            ->pluck('crop_type')
            ->map(fn ($crop) => trim((string) $crop))
            ->filter()
            ->values();
    }

    private function clean(?string $value): ?string
    {
        $value = $value === null ? '' : trim($value);

        return $value === '' ? null : $value;
    }

    /**
     * The user handed to CostQuotationEngine, which expects the store owner
     * running the computation.
     *
     * Passing the farmer straight through would hand the engine a
     * non-merchant; null lets the engine fall back to the authenticated user,
     * which is the same person in every real request.
     */
    private function storeContext(?User $askedBy): ?User
    {
        return $askedBy?->role === 'store_owner' ? $askedBy : null;
    }
}
