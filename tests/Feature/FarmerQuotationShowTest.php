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

    private function makeQuotation(User $farmer): Quotation
    {
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
            'product_id'          => null,
            'product_name'        => 'Premium Urea',
            'price_at_generation' => 100,
            'calculated_quantity' => 6,
            'subtotal'            => 300,
        ]);
        $quotation->items()->create([
            'product_id'          => null,
            'product_name'        => 'Inbred Rice Seeds',
            'price_at_generation' => 14,
            'calculated_quantity' => 1.5,
            'subtotal'            => 21,
        ]);

        return $quotation;
    }

    public function test_farmer_quotation_show_page_loads_with_calculation_and_process(): void
    {
        $farmer = $this->makeFarmer();
        $quotation = $this->makeQuotation($farmer);

        $response = $this->actingAs($farmer)
            ->get(route('farmer.quotations.show', $quotation));

        $response
            ->assertOk()
            ->assertSee('Quotation Process')
            ->assertSee('Generated')
            ->assertSee('Shared as Demand Intel')
            ->assertSee('Viewed by Store')
            ->assertSee('Expires')
            ->assertSee('Cost Breakdown')
            ->assertSee('Premium Urea')
            ->assertSee('₱321.00')
            ->assertSee('AI Overview');
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
            ->assertJsonPath('metrics.land_area', 1.5)
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

        $this->actingAs($farmer)
            ->get(route('farmer.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('3 bag/ha')
            ->assertSee('× 2.00 ha');
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