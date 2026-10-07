<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\User;
use App\Services\CostQuotationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Input Formulas page is the store owner's rule matrix: rows carry an
 * application stage and optional min/max safety bounds, and the quotation
 * engine must sum stages and clamp to those bounds.
 */
class FormulaRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $store;

    private Category $category;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = User::create([
            'name'       => 'Rule Matrix Store',
            'first_name' => 'Rule',
            'last_name'  => 'Matrix',
            'email'      => 'rules_' . uniqid() . '@example.com',
            'role'       => 'store_owner',
            'password'   => 'password123',
        ]);

        $this->category = Category::create([
            'name' => 'Fertilizer',
            'slug' => 'fertilizer-' . uniqid(),
        ]);

        $this->product = Product::create([
            'user_id'           => $this->store->id,
            'category_id'       => $this->category->id,
            'category'          => 'Fertilizer',
            'name'              => 'Urea Fertilizer (46-0-0)',
            'sku'               => 'SKU-' . uniqid(),
            'price'             => 100,
            'stock_quantity'    => 10,
            'reorder_threshold' => 2,
            'recommended_season' => 'both',
            'status'            => 'active',
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'crop_type'        => 'Rice',
            'season'           => 'wet',
            'product_id'       => $this->product->id,
            'rate_per_hectare' => 12.5,
            'unit'             => 'Kilograms',
        ], $overrides);
    }

    // ── Model defaults ───────────────────────────────────────────

    public function test_formula_defaults_to_the_standard_stage_without_bounds(): void
    {
        $formula = DecisionFormula::create($this->payload());

        $this->assertSame('standard', $formula->stage);
        $this->assertNull($formula->min_rate_per_hectare);
        $this->assertNull($formula->max_rate_per_hectare);
        $this->assertSame('Standard (single application)', $formula->stageLabel());
    }

    // ── Validation ───────────────────────────────────────────────

    public function test_stage_is_validated_against_the_supported_stages(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.formulas.store'), $this->payload(['stage' => 'rocket_science']))
            ->assertSessionHasErrors('stage');
    }

    public function test_invalid_stage_does_not_persist(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.formulas.store'), $this->payload(['stage' => 'rocket_science']));

        $this->assertSame(0, DecisionFormula::count());
    }

    public function test_min_bound_cannot_exceed_max_bound(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.formulas.store'), $this->payload([
                'min_rate_per_hectare' => 50,
                'max_rate_per_hectare' => 10,
            ]))
            ->assertSessionHasErrors('min_rate_per_hectare');
    }

    public function test_rate_must_fall_inside_the_safety_bounds(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.formulas.store'), $this->payload([
                'rate_per_hectare'     => 5,
                'min_rate_per_hectare' => 10,
            ]))
            ->assertSessionHasErrors('rate_per_hectare');

        $this->actingAs($this->store)
            ->post(route('store.formulas.store'), $this->payload([
                'rate_per_hectare'     => 100,
                'max_rate_per_hectare' => 50,
            ]))
            ->assertSessionHasErrors('rate_per_hectare');

        $this->assertSame(0, DecisionFormula::count());
    }

    // ── Uniqueness now includes the stage ────────────────────────

    public function test_same_stage_duplicate_is_rejected(): void
    {
        DecisionFormula::create($this->payload(['stage' => 'basal']));

        $this->actingAs($this->store)
            ->post(route('store.formulas.store'), $this->payload(['stage' => 'basal']))
            ->assertSessionHasErrors('product_id');
    }

    public function test_a_different_stage_for_the_same_product_is_allowed(): void
    {
        DecisionFormula::create($this->payload(['stage' => 'basal']));

        $this->actingAs($this->store)
            ->post(route('store.formulas.store'), $this->payload(['stage' => 'topdress']))
            ->assertRedirect(route('store.formulas.index'));

        $this->assertSame(2, DecisionFormula::count());
    }

    public function test_a_new_stage_for_an_existing_product_can_be_added(): void
    {
        $formula = DecisionFormula::create($this->payload(['stage' => 'basal']));

        $this->actingAs($this->store)
            ->put(route('store.formulas.update', $formula), $this->payload([
                'stage'            => 'foliar',
                'rate_per_hectare' => 4,
            ]))
            ->assertRedirect(route('store.formulas.index'));

        $this->assertSame('foliar', $formula->fresh()->stage);
    }

    // ── Bounds clamp the effective rate ──────────────────────────

    public function test_effective_rate_is_clamped_to_the_bounds(): void
    {
        $below = DecisionFormula::create($this->payload([
            'stage'                => 'basal',
            'rate_per_hectare'     => 2,
            'min_rate_per_hectare' => 5,
        ]));

        $above = DecisionFormula::create($this->payload([
            'stage'                => 'topdress',
            'rate_per_hectare'     => 90,
            'max_rate_per_hectare' => 40,
        ]));

        $inside = DecisionFormula::create($this->payload([
            'stage'                => 'foliar',
            'rate_per_hectare'     => 8,
            'min_rate_per_hectare' => 5,
            'max_rate_per_hectare' => 12,
        ]));

        $this->assertSame(5.0, $below->effectiveRate());
        $this->assertTrue($below->rateNeedsClamp());

        $this->assertSame(40.0, $above->effectiveRate());
        $this->assertTrue($above->rateNeedsClamp());

        $this->assertSame(8.0, $inside->effectiveRate());
        $this->assertFalse($inside->rateNeedsClamp());
    }

    // ── The engine sums stages per product ───────────────────────

    public function test_engine_sums_stage_rates_for_one_product(): void
    {
        DecisionFormula::create($this->payload([
            'stage'            => 'basal',
            'rate_per_hectare' => 30,
            'unit'             => 'Kilograms',
        ]));

        DecisionFormula::create($this->payload([
            'stage'            => 'topdress',
            'rate_per_hectare' => 20,
            'unit'             => 'Kilograms',
        ]));

        $items = $this->quoteItems(landArea: 2.0);

        $this->assertCount(1, $items);
        // (30 + 20) kg/ha × 2 ha = 100 kg
        $this->assertEqualsWithDelta(100.0, (float) $items[0]['base_quantity'], 0.01);
        $this->assertTrue(
            collect($items[0]['adjustments'])->contains(fn ($note) => str_contains($note, 'Split across stages')),
            'Expected a stage-split note on the line item.'
        );
    }

    public function test_engine_clamps_stage_rates_to_safety_bounds(): void
    {
        DecisionFormula::create($this->payload([
            'stage'                => 'basal',
            'rate_per_hectare'     => 500,
            'max_rate_per_hectare' => 100,
            'unit'                 => 'Kilograms',
        ]));

        $items = $this->quoteItems(landArea: 1.0);

        $this->assertCount(1, $items);
        // The runaway 500 rate is clamped to the 100 ceiling.
        $this->assertEqualsWithDelta(100.0, (float) $items[0]['base_quantity'], 0.01);
        $this->assertContains('Rate adjusted to the configured safety bounds', $items[0]['adjustments']);
    }

    public function test_legacy_formulas_without_bounds_quote_unchanged(): void
    {
        DecisionFormula::create($this->payload([
            'rate_per_hectare' => 12.5,
            'unit'             => 'Kilograms',
        ]));

        $items = $this->quoteItems(landArea: 4.0);

        $this->assertCount(1, $items);
        $this->assertEqualsWithDelta(50.0, (float) $items[0]['base_quantity'], 0.01);
    }

    /**
     * Run the quotation engine for a 1 ha-equivalent quote against this
     * store's catalog and return the generated line items.
     */
    private function quoteItems(float $landArea): array
    {
        $engine = $this->app->make(CostQuotationEngine::class);

        $quotation = $engine->calculate([
            'store_owner_id' => $this->store->id,
            'crop_type'      => 'Rice',
            'season'         => 'wet',
            'weather_context' => 'normal',
            'soil_type'      => 'clay_loam',
            'land_area'      => $landArea,
            'selected_products' => [$this->product->id],
        ], $this->store);

        return $quotation['items'];
    }
}
