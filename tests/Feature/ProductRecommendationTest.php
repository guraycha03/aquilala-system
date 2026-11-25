<?php

namespace Tests\Feature;

use App\Helpers\SeasonHelper;
use App\Models\Crop;
use App\Models\DecisionFormula;
use App\Models\FarmerCrop;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The farmer's Recommended Supplies page must explain a rule-based
 * recommendation without calling any LLM or external AI service.
 */
class ProductRecommendationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Http::fake();
    }

    private function makeUser(string $role, string $name): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '_', $name)).'_'.uniqid().'@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    public function test_recommendations_show_rule_based_explanations_without_ai(): void
    {
        $farmer = $this->makeUser('farmer', 'Recommendation Farmer');
        $store = $this->makeUser('store_owner', 'Recommendation Store');

        $pechay = Crop::create([
            'name' => 'Pechay',
            'category' => 'Vegetables',
            'is_category_placeholder' => false,
        ]);

        $product = Product::create([
            'user_id' => $store->id,
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Pechay (Bok Choy) Seeds',
            'brand' => 'East-West Seed',
            'product_type' => 'Seeds',
            'category' => 'Seeds',
            'price' => 1200,
            'stock_quantity' => 20,
            'reorder_threshold' => 5,
            'recommended_season' => 'wet',
            'status' => 'active',
        ]);

        DecisionFormula::create([
            'product_id' => $product->id,
            'crop_type' => 'Pechay',
            'season' => 'wet',
            'stage' => 'standard',
            'rate_per_hectare' => 2,
            'unit' => 'pack',
        ]);

        FarmerCrop::create([
            'user_id' => $farmer->id,
            'crop_id' => $pechay->id,
            'variety' => 'Bok Choy',
            'variety_key' => 'bok-choy',
            'batch_name' => 'Test Pechay Batch',
            'land_area' => 2,
            'land_area_unit' => 'ha',
            'planting_date' => now()->subDays(10)->toDateString(),
            'season' => 'wet',
            'growth_stage' => 'vegetative',
            'confirmation_status' => FarmerCrop::CONFIRMATION_CONFIRMED,
            'confirmed_at' => now()->subDays(9),
        ]);

        $response = $this->actingAs($farmer)
            ->get(route('farmer.recommendations.index'))
            ->assertOk();

        $response->assertSee('Why this pick', false);
        $response->assertSee('Pechay', false);
        $response->assertSee('wet', false);
        $response->assertSee('planting window', false);
        $response->assertSee('rec-card', false);
        $response->assertSee('rsrDrawer', false);
        $response->assertSee('rsrPayload', false);
        $response->assertSee('Back to recommendations', false);
        $response->assertSee('Why we picked this for you', false);
        $response->assertDontSee('Why recommended', false);
        $response->assertDontSee('product-card-link', false);
        $response->assertDontSee('Ask the farm assistant', false);
        $response->assertDontSee('Groq', false);
        $response->assertDontSee('OpenRouter', false);

        $html = $response->getContent();
        $this->assertStringContainsString('data-rsr-action="reason"', $html);
        $this->assertStringContainsString('data-rsr-action="info"', $html);
        $this->assertStringNotContainsString('rec-card-actions', $html);
        $this->assertStringNotContainsString('Why recommended:', $html);

        Http::assertNothingSent();
    }

    public function test_a_saved_recommendation_opens_with_a_filled_bookmark(): void
    {
        $farmer = $this->makeUser('farmer', 'Saved Recommendation Farmer');
        $store = $this->makeUser('store_owner', 'Saved Recommendation Store');
        $crop = Crop::create([
            'name' => 'Tomato',
            'category' => 'Vegetables',
            'is_category_placeholder' => false,
        ]);

        $product = Product::create([
            'user_id' => $store->id,
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Tomato Seeds',
            'product_type' => 'Seeds',
            'category' => 'Seeds',
            'price' => 900,
            'stock_quantity' => 12,
            'reorder_threshold' => 5,
            'recommended_season' => 'wet',
            'status' => 'active',
        ]);

        DecisionFormula::create([
            'product_id' => $product->id,
            'crop_type' => 'Tomato',
            'season' => 'wet',
            'rate_per_hectare' => 1,
            'unit' => 'pack',
        ]);

        FarmerCrop::create([
            'user_id' => $farmer->id,
            'crop_id' => $crop->id,
            'variety' => 'Native',
            'variety_key' => 'native',
            'batch_name' => 'Tomato Batch',
            'land_area' => 1,
            'land_area_unit' => 'ha',
            'planting_date' => now()->subDays(5)->toDateString(),
            'season' => 'wet',
            'growth_stage' => 'seeding',
            'confirmation_status' => FarmerCrop::CONFIRMATION_CONFIRMED,
        ]);

        $this->actingAs($farmer)
            ->postJson(route('products.save', $product))
            ->assertOk()
            ->assertJson(['saved' => true]);

        $html = $this->actingAs($farmer)
            ->get(route('farmer.recommendations.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('rec-save is-saved', $html);
        $this->assertStringContainsString('aria-pressed="true"', $html);
        $this->assertStringContainsString('Remove from saved', $html);
    }

    public function test_products_without_a_reason_are_not_recommended(): void
    {
        $farmer = $this->makeUser('farmer', 'Empty Recommendation Farmer');
        $store = $this->makeUser('store_owner', 'Empty Recommendation Store');

        $otherSeason = SeasonHelper::getCurrentSeason() === 'dry' ? 'wet' : 'dry';

        Product::create([
            'user_id' => $store->id,
            'sku' => 'SKU-'.uniqid(),
            'name' => 'Unrelated Item',
            'category' => 'Tools',
            'price' => 500,
            'stock_quantity' => 4,
            'reorder_threshold' => 5,
            'recommended_season' => $otherSeason,
            'status' => 'active',
        ]);

        $response = $this->actingAs($farmer)
            ->get(route('farmer.recommendations.index'))
            ->assertOk();

        $response->assertSee('No recommendations yet', false);
        $response->assertDontSee('Why this pick', false);
    }
}
