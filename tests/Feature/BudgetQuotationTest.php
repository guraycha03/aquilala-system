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
 * The quotation form now carries the farmer's field conditions (soil
 * type, season risk) and an optional budget cap. The engine must apply
 * the paper's adjustment rules for those inputs and, when a budget is
 * stated, fit the plan inside it by deferring optional lines — essentials
 * first, and never silently: every deferral stays recorded on the
 * quotation.
 */
class BudgetQuotationTest extends TestCase
{
    use RefreshDatabase;

    private User $store;

    private User $farmer;

    private Product $fertilizer;

    private Product $fungicide;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = User::create([
            'name'       => 'Budget Store',
            'first_name' => 'Budget',
            'last_name'  => 'Store',
            'email'      => 'budget_' . uniqid() . '@example.com',
            'role'       => 'store_owner',
            'password'   => 'password123',
        ]);

        $this->farmer = User::create([
            'name'       => 'Budget Farmer',
            'first_name' => 'Budget',
            'last_name'  => 'Farmer',
            'email'      => 'budgetfarmer_' . uniqid() . '@example.com',
            'role'       => 'farmer',
            'password'   => 'password123',
        ]);

        $fertilizerCat = Category::create([
            'name' => 'Fertilizer',
            'slug' => 'fertilizer-' . uniqid(),
        ]);
        $protectionCat = Category::create([
            'name' => 'Crop Protection',
            'slug' => 'crop-protection-' . uniqid(),
        ]);

        $this->fertilizer = Product::create([
            'user_id'           => $this->store->id,
            'category_id'       => $fertilizerCat->id,
            'category'          => 'Fertilizer',
            'name'              => 'Urea Fertilizer (46-0-0)',
            'sku'               => 'SKU-' . uniqid(),
            'price'             => 100,
            'stock_quantity'    => 10,
            'reorder_threshold' => 2,
            'recommended_season' => 'both',
            'status'            => 'active',
        ]);

        $this->fungicide = Product::create([
            'user_id'           => $this->store->id,
            'category_id'       => $protectionCat->id,
            'category'          => 'Crop Protection',
            'name'              => 'Shield Fungicide 500',
            'sku'               => 'SKU-' . uniqid(),
            'price'             => 200,
            'stock_quantity'    => 10,
            'reorder_threshold' => 2,
            'recommended_season' => 'both',
            'status'            => 'active',
        ]);

        // 4 kg/ha fertilizer = ₱400/ha · 2 bottles/ha fungicide = ₱400/ha.
        DecisionFormula::create([
            'crop_type'        => 'Rice',
            'season'           => 'wet',
            'product_id'       => $this->fertilizer->id,
            'rate_per_hectare' => 4,
            'unit'             => 'Kilograms',
        ]);
        DecisionFormula::create([
            'crop_type'        => 'Rice',
            'season'           => 'wet',
            'product_id'       => $this->fungicide->id,
            'rate_per_hectare' => 2,
            'unit'             => 'Bottles',
        ]);
    }

    /**
     * Run the engine for a 1 ha quote with both products selected.
     */
    private function quote(array $overrides = []): array
    {
        $engine = $this->app->make(CostQuotationEngine::class);

        return $engine->calculate(array_merge([
            'store_owner_id'    => $this->store->id,
            'crop_type'         => 'Rice',
            'season'            => 'wet',
            'weather_context'   => 'normal',
            'soil_type'         => 'clay_loam',
            'land_area'         => 1.0,
            'selected_products' => [$this->fertilizer->id, $this->fungicide->id],
        ], $overrides), $this->store);
    }

    // ── Field-condition adjustments (paper rules) ────────────────

    public function test_sandy_soil_adds_fifteen_percent_fertilizer(): void
    {
        $result = $this->quote(['soil_type' => 'sandy']);

        $fertilizer = collect($result['items'])->firstWhere('product_id', $this->fertilizer->id);

        $this->assertEqualsWithDelta(4.6, (float) $fertilizer['adjusted_quantity'], 0.01);
        $this->assertContains('Sandy soil adjustment: +15% (nutrient leaching)', $fertilizer['adjustments']);
        // ₱460 fertilizer + ₱400 fungicide
        $this->assertEqualsWithDelta(860.0, (float) $result['summary']['subtotal'], 0.01);
    }

    public function test_typhoon_risk_adds_fungicide_allocation_and_contingency(): void
    {
        $result = $this->quote(['weather_context' => 'typhoon']);

        $protection = collect($result['items'])->firstWhere('product_id', $this->fungicide->id);

        $this->assertContains('Typhoon season: +20% fungicide allocation (fungal risk)', $protection['adjustments']);
        // 2.4 bottles of a discrete unit rounds up to 3 whole bottles.
        $this->assertEqualsWithDelta(3.0, (float) $protection['adjusted_quantity'], 0.01);
        // ₱400 + ₱600 = ₱1,000 subtotal, +8% typhoon contingency.
        $this->assertEqualsWithDelta(1000.0, (float) $result['summary']['subtotal'], 0.01);
        $this->assertEqualsWithDelta(80.0, (float) $result['summary']['weather_contingency'], 0.01);
        $this->assertEqualsWithDelta(1080.0, (float) $result['summary']['grand_total'], 0.01);
    }

    // ── Budget fitting ───────────────────────────────────────────

    public function test_budget_defers_optional_crop_protection_to_fit(): void
    {
        $result = $this->quote(['budget_cap' => 500]);

        $this->assertSame('trimmed', $result['summary']['budget_status']);
        $this->assertEqualsWithDelta(500.0, (float) $result['summary']['budget_cap'], 0.01);
        $this->assertSame(1, $result['summary']['deferred_count']);
        $this->assertEqualsWithDelta(400.0, (float) $result['summary']['deferred_total'], 0.01);
        $this->assertEqualsWithDelta(800.0, (float) $result['summary']['full_plan_subtotal'], 0.01);
        $this->assertEqualsWithDelta(400.0, (float) $result['summary']['grand_total'], 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $result['summary']['budget_left'], 0.01);

        // The fertilizer stays, the deferral is announced on the deferred line.
        $this->assertCount(1, $result['items']);
        $this->assertSame($this->fertilizer->id, $result['items'][0]['product_id']);
        $this->assertSame($this->fungicide->id, $result['deferred_items'][0]['product_id']);
        $this->assertContains(
            'Deferred to fit your budget — add it when your budget allows',
            $result['deferred_items'][0]['adjustments']
        );
    }

    public function test_essentials_survive_a_budget_below_the_essential_cost(): void
    {
        $result = $this->quote(['budget_cap' => 100]);

        $this->assertSame('over_budget', $result['summary']['budget_status']);
        $this->assertEqualsWithDelta(300.0, (float) $result['summary']['shortfall'], 0.01); // 400 − 100
        $this->assertNull($result['summary']['budget_left']);
        $this->assertCount(1, $result['items']);
        $this->assertSame($this->fertilizer->id, $result['items'][0]['product_id']);
        $this->assertSame(1, $result['summary']['deferred_count']);
    }

    public function test_a_generous_budget_keeps_the_whole_plan(): void
    {
        $result = $this->quote(['budget_cap' => 900]);

        $this->assertSame('fitted', $result['summary']['budget_status']);
        $this->assertSame(0, $result['summary']['deferred_count']);
        $this->assertCount(2, $result['items']);
        $this->assertEmpty($result['deferred_items']);
        $this->assertEqualsWithDelta(100.0, (float) $result['summary']['budget_left'], 0.01);
    }

    public function test_without_a_budget_the_full_plan_is_unchanged(): void
    {
        $result = $this->quote();

        $this->assertSame('none', $result['summary']['budget_status']);
        $this->assertNull($result['summary']['budget_cap']);
        $this->assertSame(0, $result['summary']['deferred_count']);
        $this->assertCount(2, $result['items']);
        $this->assertEqualsWithDelta(800.0, (float) $result['summary']['grand_total'], 0.01);
    }

    // ── Persistence ──────────────────────────────────────────────

    public function test_fitted_and_deferred_lines_are_persisted_on_the_quotation(): void
    {
        $engine = $this->app->make(CostQuotationEngine::class);

        $quotation = $engine->calculateAndSave([
            'store_owner_id'    => $this->store->id,
            'crop_type'         => 'Rice',
            'season'            => 'wet',
            'weather_context'   => 'normal',
            'soil_type'         => 'clay_loam',
            'land_area'         => 1.0,
            'budget_cap'        => 500,
            'selected_products' => [$this->fertilizer->id, $this->fungicide->id],
        ], $this->farmer);

        $this->assertNotNull($quotation);
        $this->assertEqualsWithDelta(500.0, (float) $quotation->budget_cap, 0.01);
        $this->assertSame('clay_loam', $quotation->soil_type);
        $this->assertSame('normal', $quotation->weather_context);
        $this->assertEqualsWithDelta(400.0, (float) $quotation->estimated_total_cost, 0.01);
        $this->assertCount(2, $quotation->items);

        $fertilizerItem = $quotation->items->firstWhere('product_id', $this->fertilizer->id);
        $fungicideItem  = $quotation->items->firstWhere('product_id', $this->fungicide->id);

        $this->assertFalse((bool) $fertilizerItem->is_deferred);
        $this->assertTrue((bool) $fungicideItem->is_deferred);
    }

    // ── Farmer flow: form → controller → quotation ───────────────

    public function test_field_conditions_and_budget_reach_the_quotation_from_the_form(): void
    {
        $response = $this->actingAs($this->farmer)->post(route('farmer.quotations.store'), [
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.5,
            'soil_type'            => 'sandy',
            'weather_context'      => 'typhoon',
            'budget_cap'           => 900,
            'selected_product_ids' => json_encode([$this->fertilizer->id, $this->fungicide->id]),
        ]);

        $response->assertSessionHasNoErrors();

        $quotation = \App\Models\Quotation::where('user_id', $this->farmer->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($quotation);
        $this->assertSame('sandy', $quotation->soil_type);
        $this->assertSame('typhoon', $quotation->weather_context);
        $this->assertEqualsWithDelta(900.0, (float) $quotation->budget_cap, 0.01);
    }

    public function test_field_conditions_and_budget_are_validated(): void
    {
        $base = [
            'crop_type'       => 'Rice',
            'season'          => 'wet',
            'land_area'       => 1.0,
            'soil_type'       => 'clay_loam',
            'weather_context' => 'normal',
        ];

        $this->actingAs($this->farmer)
            ->post(route('farmer.quotations.store'), array_merge($base, ['soil_type' => 'volcano']))
            ->assertSessionHasErrors('soil_type');

        $this->actingAs($this->farmer)
            ->post(route('farmer.quotations.store'), array_merge($base, ['weather_context' => 'hurricane']))
            ->assertSessionHasErrors('weather_context');

        $this->actingAs($this->farmer)
            ->post(route('farmer.quotations.store'), array_merge($base, ['budget_cap' => 'not-a-number']))
            ->assertSessionHasErrors('budget_cap');

        $this->actingAs($this->farmer)
            ->post(route('farmer.quotations.store'), array_merge($base, ['budget_cap' => 0]))
            ->assertSessionHasErrors('budget_cap');
    }

    // ── The pages render with the new inputs ─────────────────────

    public function test_request_form_and_quotation_pages_render_the_budget_feature(): void
    {
        $engine = $this->app->make(CostQuotationEngine::class);

        $quotation = $engine->calculateAndSave([
            'store_owner_id'    => $this->store->id,
            'crop_type'         => 'Rice',
            'season'            => 'wet',
            'weather_context'   => 'normal',
            'soil_type'         => 'clay_loam',
            'land_area'         => 1.0,
            'budget_cap'        => 500,
            'selected_products' => [$this->fertilizer->id, $this->fungicide->id],
        ], $this->farmer);

        $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.request'))
            ->assertOk()
            ->assertSee('What are your field conditions?')
            ->assertSee('budget_cap', false);

        $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Fitted to your')
            ->assertSee('Clay loam soil');

        $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.breakdown', $quotation))
            ->assertOk()
            ->assertSee('Deferred to fit budget');

        $this->actingAs($this->store)
            ->get(route('store.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('DEFERRED');
    }
}
