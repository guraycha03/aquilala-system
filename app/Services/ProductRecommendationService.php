<?php

namespace App\Services;

use App\Helpers\SeasonHelper;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Rule-based product recommendations for the farmer's Recommended Supplies
 * page.
 *
 * Personal evidence comes only from records the system already holds: planting
 * batches, quotations, harvest reports and the current season from SeasonHelper.
 * Product suitability comes from its own data — product type, category,
 * application guidance and decision formulas — so the page can say what kind of
 * input it is and why it fits, without an LLM.
 */
class ProductRecommendationService
{
    /** @var array<string, array<int, string>> */
    private const KIND_PATTERNS = [
        'seedling' => ['seedling', 'seedlings'],
        'seed' => ['seed', 'seeds', 'planting material'],
        'fertilizer' => ['fertilizer', 'micronutrient', 'soil amendment'],
        'protection' => ['insecticide', 'fungicide', 'herbicide', 'molluscicide', 'pesticide'],
        'irrigation' => ['irrigation'],
        'tool' => ['tool', 'tools'],
        'feed' => ['feed'],
        'postharvest' => ['post-harvest', 'postharvest'],
    ];

    /** @var array<string, string> */
    private const KIND_LABELS = [
        'seedling' => 'seedling',
        'seed' => 'seed',
        'fertilizer' => 'fertilizer',
        'protection' => 'crop-protection product',
        'irrigation' => 'irrigation supply',
        'tool' => 'farm tool',
        'feed' => 'animal feed',
        'postharvest' => 'post-harvest supply',
        'input' => 'farm input',
    ];

    public function __construct(
        private readonly RecommendationExplanationService $explanations,
    ) {}

    /**
     * @return Collection<int, array{
     *     product: Product,
     *     score: int,
     *     reason_codes: array<int, string>,
     *     reason_data: array<string, mixed>,
     *     explanation: string,
     * }>
     */
    public function forFarmer(?User $user): Collection
    {
        $season = Str::lower(SeasonHelper::getCurrentSeason());

        $quotationMap = $this->quotationCropMap($user);
        $plantedMap = $this->cropMapFrom(FarmerCrop::class, $user, 'crop.name', activeOnly: true);
        $harvestMap = $this->cropMapFrom(HarvestReport::class, $user, 'crop_type');
        $ownQuotedProductIds = $this->ownQuotedProductIds($user);

        $products = Product::active()
            ->with([
                'category.parent',
                'mainImage',
                'images',
                'storeOwner',
                'decisionFormulas',
                'cropTypes',
                'applications.crop',
            ])
            ->withCount('quotationItems')
            ->get();

        $recommendations = $products
            ->map(function (Product $product) use ($season, $quotationMap, $plantedMap, $harvestMap, $ownQuotedProductIds): ?array {
                [$reasonCodes, $reasonData] = $this->reasonsFor(
                    $product,
                    $season,
                    $quotationMap,
                    $plantedMap,
                    $harvestMap,
                    $ownQuotedProductIds,
                );

                if (! $this->recommendable($reasonCodes, $reasonData)) {
                    return null;
                }

                return [
                    'product' => $product,
                    'score' => $this->scoreFor($reasonCodes),
                    'reason_codes' => $reasonCodes,
                    'reason_data' => $reasonData,
                    'explanation' => $this->explanations->explain($product, $reasonCodes, $reasonData),
                ];
            })
            ->filter()
            ->values();

        return $recommendations->sort(function (array $left, array $right): int {
            $compare = (int) ($right['score'] ?? 0) <=> (int) ($left['score'] ?? 0);
            if ($compare !== 0) {
                return $compare;
            }

            $compare = (int) ($right['reason_data']['quotation_count'] ?? 0)
                <=> (int) ($left['reason_data']['quotation_count'] ?? 0);
            if ($compare !== 0) {
                return $compare;
            }

            return strcasecmp(
                (string) ($left['product']->name ?? ''),
                (string) ($right['product']->name ?? ''),
            );
        })->values();
    }

    /**
     * @param  array<string, string>  $quotationMap
     * @param  array<string, string>  $plantedMap
     * @param  array<string, string>  $harvestMap
     * @param  array<int, int>  $ownQuotedProductIds
     * @return array{0: array<int, string>, 1: array<string, mixed>}
     */
    private function reasonsFor(
        Product $product,
        string $season,
        array $quotationMap,
        array $plantedMap,
        array $harvestMap,
        array $ownQuotedProductIds,
    ): array {
        $codes = [];
        $data = [];

        $formulas = $product->relationLoaded('decisionFormulas')
            ? $product->getRelation('decisionFormulas')
            : collect();

        $applications = $product->relationLoaded('applications')
            ? $product->getRelation('applications')
            : collect();

        $kind = $this->productKind($product);
        $data['kind'] = $kind;
        $data['kind_label'] = self::KIND_LABELS[$kind] ?? self::KIND_LABELS['input'];
        $data['crop_label'] = $this->cropLabel($product, $kind, $formulas, $applications);

        $formulaCrops = $formulas
            ->pluck('crop_type')
            ->map(fn ($value): string => Str::lower(trim((string) $value)))
            ->filter()
            ->values()
            ->all();

        $formulaSeasons = $formulas
            ->pluck('season')
            ->map(fn ($value): string => Str::lower(trim((string) $value)))
            ->filter()
            ->values()
            ->all();

        $applicationCrops = $applications
            ->pluck('crop.name')
            ->map(fn ($value): string => Str::lower(trim((string) $value)))
            ->filter()
            ->values()
            ->all();

        $linkedCrops = $product->relationLoaded('cropTypes')
            ? $product->getRelation('cropTypes')
                ->pluck('crop_type')
                ->map(fn ($value): string => Str::lower(trim((string) $value)))
                ->filter()
                ->values()
                ->all()
            : [];

        $matchableCrops = array_values(array_unique(array_merge(
            $formulaCrops,
            $linkedCrops,
            $applicationCrops,
        )));

        // One crop source is enough. The ordering reflects how direct the
        // evidence is: what the farmer planted beats what they quoted, which
        // beats a crop they only harvested in the past.
        $source = null;
        $matchedCrop = $this->firstMatch($plantedMap, $matchableCrops);
        if ($matchedCrop !== null) {
            $source = 'planted';
            $codes[] = 'planted_crop_match';
        }

        if ($source === null) {
            $matchedCrop = $this->firstMatch($quotationMap, $matchableCrops);
            if ($matchedCrop !== null) {
                $source = 'quotation';
                $codes[] = 'crop_formula_match';
            }
        }

        if ($source === null) {
            $matchedCrop = $this->firstMatch($harvestMap, $matchableCrops);
            if ($matchedCrop !== null) {
                $source = 'harvest';
                $codes[] = 'harvest_history_match';
            }
        }

        if ($matchedCrop !== null) {
            $data['crop'] = $matchedCrop;
        }
        $data['source'] = $source;

        if (in_array((int) $product->id, $ownQuotedProductIds, true)) {
            $codes[] = 'own_quotation_match';
            $data['source'] ??= 'own_quotation';
        }

        $quotationCount = (int) ($product->quotation_items_count ?? 0);
        $data['quotation_count'] = $quotationCount;

        if ($quotationCount >= 2) {
            $codes[] = 'top_quoted';
            $data['source'] ??= 'quotations';
        } elseif ($quotationCount === 1) {
            $codes[] = 'quotation_support';
        }

        // A season is only a reason when the product has season-specific
        // evidence. `recommended_season = both` is deliberately NOT enough —
        // that flag was making nearly the whole catalog look seasonal.
        $seasonHaystack = Str::lower(implode(' ', array_filter([
            (string) ($product->description ?? ''),
            $applications->pluck('application_timing')->implode(' '),
            $applications->pluck('application_method')->implode(' '),
        ])));

        $formulaSeasonMatch = in_array($season, $formulaSeasons, true);
        $explicitSeasonMatch = $season !== '' && Str::contains($seasonHaystack, $season);
        $recommendedSeasonMatch = Str::lower(trim((string) ($product->recommended_season ?? ''))) === $season;
        $seasonFit = $formulaSeasonMatch
            || $explicitSeasonMatch
            || ($recommendedSeasonMatch && in_array($kind, ['seed', 'seedling'], true));

        if ($seasonFit) {
            $codes[] = 'season_fit';
            $data['season'] = $season;
            $data['season_fit'] = true;
            $data['season_timing'] = $this->seasonTiming($applications, $season, $formulaSeasonMatch);
        }

        $method = trim((string) ($applications->pluck('application_method')->filter()->first() ?? ''));
        $timing = trim((string) ($applications->pluck('application_timing')->filter()->first() ?? ''));
        if ($method !== '' || $timing !== '') {
            $codes[] = 'application_guidance';
            $data['application_method'] = $method;
            $data['application_timing'] = $timing;
        }

        $stage = $formulas
            ->pluck('stage')
            ->map(fn ($value): string => Str::lower(trim((string) $value)))
            ->first(fn (string $value): bool => $value !== '' && $value !== 'standard');

        if ($stage !== null) {
            $codes[] = 'stage_rule';
            $data['formula_stage'] = (string) $stage;
        }

        if ($formulas->isNotEmpty()) {
            $codes[] = 'formula_backed';
        }

        return [$codes, $data];
    }

    /**
     * Only recommend a product with a real reason. Season alone is allowed just
     * for seeds and seedlings, which are genuinely planting-window products.
     *
     * @param  array<int, string>  $reasonCodes
     * @param  array<string, mixed>  $reasonData
     */
    private function recommendable(array $reasonCodes, array $reasonData): bool
    {
        $personalReasons = array_intersect($reasonCodes, [
            'planted_crop_match',
            'crop_formula_match',
            'harvest_history_match',
            'own_quotation_match',
            'top_quoted',
        ]);

        if ($personalReasons !== []) {
            return true;
        }

        return ! empty($reasonData['season_fit'])
            && in_array($reasonData['kind'] ?? '', ['seed', 'seedling'], true);
    }

    /**
     * @param  array<int, string>  $reasonCodes
     */
    private function scoreFor(array $reasonCodes): int
    {
        $scores = [
            'planted_crop_match' => 6,
            'crop_formula_match' => 5,
            'harvest_history_match' => 4,
            'own_quotation_match' => 4,
            'top_quoted' => 3,
            'season_fit' => 2,
            'quotation_support' => 1,
            'application_guidance' => 1,
            'stage_rule' => 1,
            'formula_backed' => 1,
        ];

        return collect($reasonCodes)->sum(fn (string $code): int => $scores[$code] ?? 0);
    }

    /**
     * Crop names from the farmer's own quotations.
     *
     * @return array<string, string> normalized name => display name
     */
    private function quotationCropMap(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return $this->mapCropNames(
            Quotation::where('user_id', $user->id)->pluck('crop_type')->all(),
        );
    }

    /**
     * @param  class-string  $model
     * @return array<string, string> normalized name => display name
     */
    private function cropMapFrom(string $model, ?User $user, string $column, bool $activeOnly = false): array
    {
        if ($user === null) {
            return [];
        }

        $query = $model::where('user_id', $user->id);

        if ($activeOnly && method_exists($model, 'active')) {
            $query->active();
        }

        $names = $column === 'crop.name'
            ? $query->with('crop')->get()->pluck('crop.name')->all()
            : $query->pluck($column)->all();

        return $this->mapCropNames($names);
    }

    /**
     * @param  array<int, mixed>  $names
     * @return array<string, string> normalized name => display name
     */
    private function mapCropNames(array $names): array
    {
        $map = [];

        foreach ($names as $name) {
            $display = trim((string) $name);
            if ($display !== '') {
                $map[Str::lower($display)] = $display;
            }
        }

        return $map;
    }

    /**
     * @param  array<string, string>  $map
     * @param  array<int, string>  $candidates
     */
    private function firstMatch(array $map, array $candidates): ?string
    {
        foreach ($candidates as $candidate) {
            $key = Str::lower(trim((string) $candidate));
            if ($key !== '' && isset($map[$key])) {
                return $map[$key];
            }
        }

        return null;
    }

    /**
     * @return array<int, int>
     */
    private function ownQuotedProductIds(?User $user): array
    {
        if ($user === null) {
            return [];
        }

        return QuotationItem::whereHas('quotation', fn ($query) => $query->where('user_id', $user->id))
            ->pluck('product_id')
            ->map(fn ($id): int => (int) $id)
            ->unique()
            ->values()
            ->all();
    }

    private function productKind(Product $product): string
    {
        $haystack = Str::lower(implode(' ', array_filter([
            (string) ($product->product_type ?? ''),
            (string) ($product->category_display_name ?? ''),
            (string) ($product->name ?? ''),
        ])));

        foreach (self::KIND_PATTERNS as $kind => $needles) {
            foreach ($needles as $needle) {
                if (Str::contains($haystack, $needle)) {
                    return $kind;
                }
            }
        }

        return 'input';
    }

    /**
     * A short, human crop/variety label for the explanation. Seed product names
     * carry the most useful detail ("Tomato Seeds", "Pechay (Bok Choy) Seeds"),
     * so they are preferred over the generic "Vegetables" application crop.
     *
     * @param  Collection<int, \App\Models\DecisionFormula>  $formulas
     * @param  Collection<int, \App\Models\ProductApplication>  $applications
     */
    private function cropLabel(Product $product, string $kind, Collection $formulas, Collection $applications): string
    {
        if (in_array($kind, ['seed', 'seedling'], true)) {
            $fromName = $this->cropLabelFromName($product);
            if ($fromName !== '') {
                return $fromName;
            }
        }

        foreach ($applications as $application) {
            $name = trim((string) ($application->crop?->name ?? ''));
            if ($this->specificCropName($name)) {
                return $name;
            }
        }

        if ($product->relationLoaded('cropTypes')) {
            foreach ($product->getRelation('cropTypes') as $cropType) {
                $name = trim((string) ($cropType->crop_type ?? ''));
                if ($this->specificCropName($name)) {
                    return $name;
                }
            }
        }

        foreach ($formulas as $formula) {
            $name = trim((string) ($formula->crop_type ?? ''));
            if ($this->specificCropName($name)) {
                return $name;
            }
        }

        $fallback = trim((string) ($product->category_display_name ?? ''));

        return $fallback !== '' ? $fallback : 'this crop';
    }

    private function cropLabelFromName(Product $product): string
    {
        $name = trim((string) ($product->name ?? ''));
        $brand = trim((string) ($product->brand ?? ''));

        if ($brand !== '' && Str::startsWith(Str::lower($name), Str::lower($brand))) {
            $name = ltrim(substr($name, strlen($brand)));
        }

        $name = (string) preg_replace('/\b(f1|hybrid|certified|improved)\b/i', ' ', $name);
        $name = (string) preg_replace('/\b(seeds?|seedlings?|planting\s+materials?)\b/i', ' ', $name);
        $name = trim((string) preg_replace('/\s+/', ' ', $name), " \t\n\r\0\x0B-,");

        return $name;
    }

    private function specificCropName(string $name): bool
    {
        $normalized = Str::lower($name);

        return $normalized !== ''
            && ! in_array($normalized, ['vegetables', 'crops', 'general', 'other'], true);
    }

    /**
     * @param  Collection<int, \App\Models\ProductApplication>  $applications
     */
    private function seasonTiming(Collection $applications, string $season, bool $formulaSeasonMatch): string
    {
        foreach ($applications as $application) {
            $timing = trim((string) ($application->application_timing ?? ''));
            if ($timing !== '' && Str::contains(Str::lower($timing), $season)) {
                return $timing;
            }
        }

        return $formulaSeasonMatch ? 'approved application rule' : 'listed season';
    }
}
