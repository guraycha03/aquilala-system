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

    // ── Fit mode: the 2× flexible ceiling ─────────────────────────

    public function test_fit_mode_allows_the_plan_up_to_double_the_budget(): void
    {
        // Confirmed with the farmer: a ₱300 budget may build a plan worth
        // up to ₱600 — the essential line (₱400) now fits even though it
        // crosses the stated budget, and the optional line (another ₱400)
        // is trimmed because the pair would reach ₱800 > ₱600.
        $result = $this->quote(['budget_cap' => 300, 'budget_strict' => true]);

        $this->assertTrue($result['summary']['budget_in_flex_band']);
        $this->assertEqualsWithDelta(600.0, (float) $result['summary']['budget_flex_ceiling'], 0.01);
        $this->assertSame('trimmed', $result['summary']['budget_status']);
        $this->assertCount(1, $result['items']);
        $this->assertSame($this->fertilizer->id, $result['items'][0]['product_id']);
        $this->assertCount(1, $result['deferred_items']);
        $this->assertNull($result['summary']['budget_left']);
    }

    public function test_fit_mode_stays_unreachable_when_essentials_pass_double_the_budget(): void
    {
        // ₱150 budget → ₱300 ceiling; essentials start at ₱400: even the
        // flexibility cannot start the plan.
        $result = $this->quote(['budget_cap' => 150, 'budget_strict' => true]);

        $this->assertSame('unreachable', $result['summary']['budget_status']);
        $this->assertEmpty($result['items']);
        $this->assertCount(2, $result['deferred_items']);
        // ₱400 essentials vs the ₱300 ceiling → ₱100 short.
        $this->assertEqualsWithDelta(400.0, (float) $result['summary']['essential_minimum'], 0.01);
        $this->assertEqualsWithDelta(300.0, (float) $result['summary']['budget_flex_ceiling'], 0.01);
        $this->assertEqualsWithDelta(100.0, (float) $result['summary']['shortfall'], 0.01);
        $this->assertNull($result['summary']['budget_left']);
    }

    public function test_fit_mode_does_not_apply_when_the_toggle_is_off(): void
    {
        // Toggle off → ceiling is the stated budget itself, no allowance.
        $result = $this->quote(['budget_cap' => 500]);

        $this->assertEqualsWithDelta(500.0, (float) $result['summary']['budget_flex_ceiling'], 0.01);
        $this->assertFalse($result['summary']['budget_in_flex_band']);
        $this->assertSame('trimmed', $result['summary']['budget_status']);
        $this->assertCount(1, $result['items']);
    }

    public function test_fit_band_renders_as_flexible_not_short(): void
    {
        $engine = $this->app->make(CostQuotationEngine::class);

        $quotation = $engine->calculateAndSave([
            'store_owner_id'    => $this->store->id,
            'crop_type'         => 'Rice',
            'season'            => 'wet',
            'weather_context'   => 'normal',
            'soil_type'         => 'clay_loam',
            'land_area'         => 1.0,
            'budget_cap'        => 300,
            'budget_strict'     => true,
            'selected_products' => [$this->fertilizer->id, $this->fungicide->id],
        ], $this->farmer);

        // The persisted flag tells the view this is the allowance band,
        // not a plain over-budget plan.
        $this->assertTrue((bool) $quotation->budget_strict);

        $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Within your flexible limit')
            ->assertDontSee('Short by');

        $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.breakdown', $quotation))
            ->assertOk()
            ->assertSee('within your flexible limit')
            ->assertSee('up to ₱600.00');
    }

    // ── Strict mode: "fit suggestions to my budget" ──────────────

    public function test_strict_mode_builds_the_included_set_inside_the_ceiling(): void
    {
        $result = $this->quote(['budget_cap' => 500, 'budget_strict' => true]);

        // The full ₱800 plan now fits: fit mode's ceiling is twice the
        // ₱500 budget (₱1,000), so strict mode keeps everything the old
        // 1× cap would have trimmed — the ceiling is the rule.
        $this->assertSame('fitted', $result['summary']['budget_status']);
        $this->assertTrue($result['summary']['budget_strict']);
        $this->assertCount(2, $result['items']);
        $this->assertEmpty($result['deferred_items']);
        $this->assertTrue($result['summary']['budget_in_flex_band']);
        $this->assertEqualsWithDelta(800.0, (float) $result['summary']['grand_total'], 0.01);
        $this->assertEqualsWithDelta(1000.0, (float) $result['summary']['budget_flex_ceiling'], 0.01);
        $this->assertNull($result['summary']['budget_left']);
    }

    public function test_strict_mode_limits_auto_included_products_by_the_budget(): void
    {
        // The reported bug: no products selected → the engine auto-included
        // the whole recommended plan (₱26k) on a ₱1,000 budget. Strict mode
        // must cap the auto-included set at what the ceiling can buy —
        // twice the stated budget.
        $tight = $this->quote(['selected_products' => [], 'budget_cap' => 300, 'budget_strict' => true]);

        // ₱600 ceiling keeps the ₱400 essential line, trims the ₱400
        // optional line that would reach ₱800.
        $this->assertSame('trimmed', $tight['summary']['budget_status']);
        $this->assertCount(1, $tight['items']);
        $this->assertSame($this->fertilizer->id, $tight['items'][0]['product_id']);
        $this->assertCount(1, $tight['deferred_items']);

        // A budget whose doubled ceiling still sits below every line
        // auto-includes nothing at all.
        $tiny = $this->quote(['selected_products' => [], 'budget_cap' => 100, 'budget_strict' => true]);

        $this->assertSame('unreachable', $tiny['summary']['budget_status']);
        $this->assertEmpty($tiny['items']);
        $this->assertCount(2, $tiny['deferred_items']);
    }

    public function test_without_strict_the_budget_stays_additional_information(): void
    {
        // Toggle off → current behavior: essentials always survive even
        // below their cost, with the shortfall reported.
        $result = $this->quote(['budget_cap' => 300]);

        $this->assertSame('over_budget', $result['summary']['budget_status']);
        $this->assertCount(1, $result['items']);
        $this->assertFalse($result['summary']['budget_strict']);
    }

    public function test_priority_tier_is_persisted_on_every_line(): void
    {
        $engine = $this->app->make(CostQuotationEngine::class);

        $quotation = $engine->calculateAndSave([
            'store_owner_id'    => $this->store->id,
            'crop_type'         => 'Rice',
            'season'            => 'wet',
            'weather_context'   => 'normal',
            'soil_type'         => 'clay_loam',
            'land_area'         => 1.0,
            'selected_products' => [$this->fertilizer->id, $this->fungicide->id],
        ], $this->farmer);

        $this->assertSame('essential', $quotation->items->firstWhere('product_id', $this->fertilizer->id)->priority);
        $this->assertSame('optional', $quotation->items->firstWhere('product_id', $this->fungicide->id)->priority);
    }

    public function test_budget_strict_reaches_the_quotation_from_the_form(): void
    {
        $response = $this->actingAs($this->farmer)->post(route('farmer.quotations.store'), [
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.0,
            'soil_type'            => 'clay_loam',
            'weather_context'      => 'normal',
            'budget_cap'           => 300,
            'budget_strict'        => '1',
            'selected_product_ids' => json_encode([$this->fertilizer->id, $this->fungicide->id]),
        ]);

        $response->assertSessionHasNoErrors();

        $quotation = \App\Models\Quotation::where('user_id', $this->farmer->id)
            ->latest('id')
            ->first();

        $this->assertNotNull($quotation);
        $this->assertEqualsWithDelta(300.0, (float) $quotation->budget_cap, 0.01);
        $this->assertTrue((bool) $quotation->budget_strict);
        // ₱600 ceiling: the essential line survives, the optional line is
        // trimmed — and the fitted total reflects what actually stays.
        $this->assertCount(2, $quotation->items);
        $this->assertSame(1, $quotation->items->where('is_deferred', true)->count());
        $this->assertEqualsWithDelta(400.0, (float) $quotation->estimated_total_cost, 0.01);
    }

    // ── The product filter data the form needs ───────────────────

    public function test_products_endpoint_returns_categories_for_the_filter(): void
    {
        $response = $this->actingAs($this->farmer)->get(
            route('farmer.quotations.products', ['crop_type' => 'Rice', 'season' => 'wet'])
        );

        $response->assertOk();
        $categories = collect($response->json())->pluck('category')->filter()->all();

        $this->assertContains('Fertilizer', $categories);
        $this->assertContains('Crop Protection', $categories);
    }

    public function test_unreachable_budget_pages_render_the_nothing_fits_state(): void
    {
        $engine = $this->app->make(CostQuotationEngine::class);

        $quotation = $engine->calculateAndSave([
            'store_owner_id'    => $this->store->id,
            'crop_type'         => 'Rice',
            'season'            => 'wet',
            'weather_context'   => 'normal',
            'soil_type'         => 'clay_loam',
            'land_area'         => 1.0,
            'budget_cap'        => 100,
            'budget_strict'     => true,
            'selected_products' => [$this->fertilizer->id, $this->fungicide->id],
        ], $this->farmer);

        $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Nothing fits your')
            ->assertSee('plan needs');

        $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.breakdown', $quotation))
            ->assertOk()
            ->assertSee('nothing fits');

        $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.request'))
            ->assertOk()
            ->assertSee('Fit suggestions to my budget')
            ->assertSee('product-category', false)
            ->assertSee('Weather risk this planting');
    }
}
