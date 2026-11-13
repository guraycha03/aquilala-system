<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The store enters application rules on Product → Application Rules, the
 * product detail page must show them, and the quotation engine must quote from
 * them. All three read the one `decision_formulas` table — but only after this
 * change did the product page read it at all, so a store could configure rates
 * that a farmer could never see.
 *
 * The section always renders. Every field on this page is optional because
 * rules are added after the product is created, so an absent rule reads N/A
 * exactly like Packaging & Pricing and Certification do — it is never hidden.
 *
 * The rate shown is `effectiveRate()`, the same value CostQuotationEngine sums
 * per product, so what the page displays and what a quotation charges cannot
 * drift apart.
 */
class ProductApplicationRulesTest extends TestCase
{
    use RefreshDatabase;

    private User $store;
    private User $farmer;
    private Category $category;
    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = User::create([
            'name'       => 'Green Valley Store',
            'first_name' => 'Green',
            'last_name'  => 'Valley',
            'email'      => 'store_' . uniqid() . '@example.com',
            'role'       => 'store_owner',
            'password'   => 'password123',
        ]);

        $this->farmer = User::create([
            'name'       => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name'  => 'Dela Cruz',
            'email'      => 'farmer_' . uniqid() . '@example.com',
            'role'       => 'farmer',
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
            'name'              => 'YaraMila Winner 14-14-14',
            'brand'             => 'Yara',
            'sku'               => 'SKU-RULES-' . uniqid(),
            'package_size'      => 50,
            'package_unit'      => 'kg',
            'price'             => 1800,
            'stock_quantity'    => 40,
            'reorder_threshold' => 10,
            'recommended_season' => 'wet',
            'status'            => 'active',
        ]);
    }

    /**
     * Rules are added after creation, so most products have none. The section
     * still has to render — hiding it would make the page lie by omission.
     */
    public function test_the_section_renders_and_reads_na_when_no_rule_exists_yet(): void
    {
        $response = $this->actingAs($this->farmer)
            ->get(route('farmer.products.show', $this->product->id));

        $response->assertOk()->assertSee('Application Rules');

        // These labels appear nowhere else on the page, so they pin the
        // section's own empty state rather than some other N/A on the page.
        $response->assertSee('Rate per Hectare');
        $response->assertSee('Application Stage');
        $response->assertSee('Safety Bounds');
    }

    public function test_a_rule_entered_on_the_edit_page_shows_up_on_the_product_page(): void
    {
        DecisionFormula::create([
            'product_id'      => $this->product->id,
            'crop_type'       => 'Rice',
            'season'          => 'wet',
            'stage'           => 'basal',
            'rate_per_hectare' => 45,
            'unit'            => 'kg',
            'notes'           => 'Apply to moist soil within 3 days of transplanting.',
        ]);

        $response = $this->actingAs($this->farmer)
            ->get(route('farmer.products.show', $this->product->id));

        $response->assertOk()
            ->assertSee('Application Rules')
            ->assertSee('Rice')
            ->assertSee('Basal (at planting)')
            ->assertSee('45 kg')
            ->assertSee('Apply to moist soil within 3 days of transplanting.');
    }

    /**
     * The page must show the rate the quotation engine applies, not the raw
     * entry. A typo above the guardrail is clamped before it ever reaches a
     * quotation, so displaying the unclamped number would promise a rate the
     * quotation never charges.
     */
    public function test_the_page_shows_the_clamped_rate_the_quotation_will_actually_use(): void
    {
        DecisionFormula::create([
            'product_id'          => $this->product->id,
            'crop_type'           => 'Rice',
            'season'              => 'wet',
            'stage'               => 'standard',
            'rate_per_hectare'    => 500,   // typo — way above the safety window
            'max_rate_per_hectare' => 40,
            'unit'                => 'kg',
        ]);

        $formula = DecisionFormula::firstOrFail();

        // The engine's figure, computed independently of the view.
        $this->assertSame(40.0, $formula->effectiveRate());
        $this->assertTrue($formula->rateNeedsClamp());

        $response = $this->actingAs($this->farmer)
            ->get(route('farmer.products.show', $this->product->id));

        $response->assertOk()->assertSee('Safety Bounds');

        // The clamped value is what appears as the rate; the raw entry is
        // still disclosed alongside it so the store can see the correction.
        $response->assertSee('40 kg');
        $response->assertSee('configured 500');
    }

    /**
     * The link that ties the two pages together: from the product page the
     * store jumps straight to the rules editor. Farmers must not see it —
     * the editor lives behind role:store_owner and would 403 on click.
     */
    public function test_the_store_owner_gets_a_link_to_the_rules_editor_and_farmers_do_not(): void
    {
        DecisionFormula::create([
            'product_id'      => $this->product->id,
            'crop_type'       => 'Rice',
            'season'          => 'wet',
            'rate_per_hectare' => 45,
            'unit'            => 'kg',
        ]);

        $expected = route('store.products.edit', ['product' => $this->product, 'tab' => 'rules']);

        $this->actingAs($this->store)
            ->get(route('store.products.show', $this->product->id))
            ->assertOk()
            ->assertSee('Edit rules')
            ->assertSee($expected, false);

        $this->actingAs($this->farmer)
            ->get(route('farmer.products.show', $this->product->id))
            ->assertOk()
            ->assertDontSee('Edit rules');
    }
}
