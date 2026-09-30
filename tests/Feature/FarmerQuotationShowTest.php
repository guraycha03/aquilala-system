<?php

namespace Tests\Feature;

use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FarmerQuotationShowTest extends TestCase
{
    use RefreshDatabase;

    private function makeFarmer(): User
    {
        return User::create([
            'name' => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan_' . uniqid() . '@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);
    }

    private function makeStore(): User
    {
        return User::create([
            'name' => 'Green Valley Store',
            'first_name' => 'Green',
            'last_name' => 'Valley',
            'email' => 'store_' . uniqid() . '@example.com',
            'role' => 'store_owner',
            'password' => 'password123',
        ]);
    }

    private function makeProduct(User $store, string $name, string $sku, int $price): Product
    {
        return Product::create([
            'user_id'           => $store->id,
            'sku'               => $sku,
            'name'              => $name,
            'category'          => 'Fertilizer',
            'price'             => $price,
            'stock_quantity'    => 10,
            'reorder_threshold' => 2,
            'status'            => 'active',
        ]);
    }

    private function makeQuotation(User $farmer): Quotation
    {
        $store = $this->makeStore();
        $urea = $this->makeProduct($store, 'Premium Urea', 'SKU-TEST-UREA', 100);
        $seeds = $this->makeProduct($store, 'Inbred Rice Seeds', 'SKU-TEST-SEED', 14);

        $quotation = Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-2026-TEST',
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.5,
            'estimated_total_cost' => 321.00,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
            'notes'                => 'Plan for first season.',
        ]);

        $quotation->items()->create([
            'product_id'          => $urea->id,
            'product_name'        => 'Premium Urea',
            'price_at_generation' => 100,
            'calculated_quantity' => 6,
            'subtotal'            => 300,
        ]);
        $quotation->items()->create([
            'product_id'          => $seeds->id,
            'product_name'        => 'Inbred Rice Seeds',
            'price_at_generation' => 14,
            'calculated_quantity' => 1.5,
            'subtotal'            => 21,
        ]);

        return $quotation;
    }

    public function test_farmer_quotation_show_page_loads_with_summary_and_items(): void
    // Note: the Sep 2026 redesign moved the line-by-line math and the
    // "Quotation Process" timeline off this page. The overview now links to
    // farmer.quotations.breakdown, so the rate/area assertions live there.
    {
        $farmer = $this->makeFarmer();
        $quotation = $this->makeQuotation($farmer);

        $response = $this->actingAs($farmer)
            ->get(route('farmer.quotations.show', $quotation));

        $response
            ->assertOk()
            ->assertSee('QT-2026-TEST')
            ->assertSee('AI Farm Advisor')
            ->assertSee('Your Plan at a Glance')
            ->assertSee('Supply Stores')
            ->assertSee('Full Itemized Breakdown')
            ->assertSee('Estimated Total Cost')
            ->assertSee('Valid until')
            ->assertSee('₱321.00')
            // The overview page links out to the itemized page rather than
            // repeating every line, so it must not render the product names.
            ->assertDontSee('Premium Urea')
            ->assertSee(route('farmer.quotations.breakdown', $quotation));
    }

    public function test_ai_overview_returns_fallback_smart_response_when_no_api_key(): void
    {
        Config::set('services.groq.key', null);

        $farmer = $this->makeFarmer();
        $quotation = $this->makeQuotation($farmer);

        $response = $this->actingAs($farmer)
            ->post(route('farmer.quotations.ai-overview', $quotation), [], [
                'Accept' => 'application/json',
            ]);

        $response
            ->assertOk()
            ->assertJsonPath('source', 'fallback')
            ->assertJsonPath('metrics.crop', 'Rice')
            ->assertJsonPath('metrics.farm_area', 1.5)
            ->assertJsonPath('metrics.total', 321)
            ->assertJsonPath('metrics.item_count', 2)
            ->assertJsonStructure(['overview']);
    }

    public function test_other_farmer_cannot_view_or_query_someone_elses_quotation(): void
    {
        $owner = $this->makeFarmer();
        $intruder = $this->makeFarmer();
        $quotation = $this->makeQuotation($owner);

        $this->actingAs($intruder)
            ->get(route('farmer.quotations.show', $quotation))
            ->assertForbidden();

        $this->actingAs($intruder)
            ->post(route('farmer.quotations.ai-overview', $quotation), [], [
                'Accept' => 'application/json',
            ])
            ->assertForbidden();
    }

    public function test_calculation_uses_decision_formula_rate_per_hectare(): void
    {
        $farmer = $this->makeFarmer();
        $owner = User::create([
            'name' => 'Green Valley Store',
            'first_name' => 'Green',
            'last_name' => 'Valley',
            'email' => 'store_' . uniqid() . '@example.com',
            'role' => 'store_owner',
            'password' => 'password123',
        ]);
        $product = Product::create([
            'user_id' => $owner->id,
            'sku' => 'SKU-TEST-001',
            'name' => 'Premium Urea',
            'category' => 'Fertilizer',
            'price' => 200,
            'stock_quantity' => 10,
            'reorder_threshold' => 2,
            'status' => 'active',
        ]);

        DecisionFormula::create([
            'crop_type'       => 'Rice',
            'season'          => 'wet',
            'product_id'      => $product->id,
            'rate_per_hectare' => 3,
            'unit'            => 'bag',
        ]);

        $quotation = Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-2026-FORM',
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 2.0,
            'estimated_total_cost' => 1200,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);

        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => 'Premium Urea',
            'price_at_generation' => 200,
            'calculated_quantity' => 6,
            'subtotal'            => 1200,
        ]);

        // The rate × area math lives on the dedicated itemized breakdown page;
        // the overview page links to it instead of repeating it.
        // 3 bag/ha × 2.00 ha — the multiplication sign is an HTML entity
        // in the markup, so compare unescaped.
        $this->actingAs($farmer)
            ->get(route('farmer.quotations.breakdown', $quotation))
            ->assertOk()
            ->assertSee('3 bag/ha')
            ->assertSee('&times; 2.00 ha', false);
    }

    public function test_fallback_overview_is_built_from_quotation_items(): void
    {
        Config::set('services.groq.key', null);

        $farmer = $this->makeFarmer();
        $quotation = $this->makeQuotation($farmer);

        $response = $this->actingAs($farmer)
            ->post(route('farmer.quotations.ai-overview', $quotation), [], [
                'Accept' => 'application/json',
            ])
            ->assertOk();

        $overview = $response->json('overview');

        $this->assertStringContainsString('Premium Urea', $overview);
        $this->assertStringContainsString('₱321.00', $overview);
        $this->assertStringContainsString('Rice', $overview);
        $this->assertStringContainsString('AI Takeaway', $overview);
    }
}