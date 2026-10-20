<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Services\CostQuotationEngine;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * Auto-include used to quote EVERY product with a formula for the crop —
 * 20+ lines, several competing for the same job (five fertilizer families,
 * three insecticides). The quotation now suggests the best product per
 * job: chosen by the model when the provider is reachable, by a
 * deterministic stock → farmer-demand → price ranking when it is not.
 *
 * The provider is never load-bearing: no key, timeout, rejected call, or
 * nonsense reply must all land on the deterministic path without an error,
 * and products the farmer picked by hand are never curated at all.
 */
class AiSuggestionCurationTest extends TestCase
{
    use RefreshDatabase;

    private User $store;

    private User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = User::create([
            'name'       => 'Curation Store',
            'first_name' => 'Curation',
            'last_name'  => 'Store',
            'email'      => 'curationstore_' . uniqid() . '@example.com',
            'role'       => 'store_owner',
            'password'   => 'password123',
        ]);

        $this->farmer = User::create([
            'name'       => 'Curation Farmer',
            'first_name' => 'Curation',
            'last_name'  => 'Farmer',
            'email'      => 'curationfarmer_' . uniqid() . '@example.com',
            'role'       => 'farmer',
            'password'   => 'password123',
        ]);
    }

    /**
     * An active product with a Rice/wet formula, so the auto-include path
     * (no products selected) picks it up as a candidate.
     */
    private function makeFormulaProduct(string $categoryName, string $name, float $price, int $stock = 10): Product
    {
        $category = Category::firstWhere('name', $categoryName)
            ?? Category::create([
                'name' => $categoryName,
                'slug' => Str::slug($categoryName) . '-' . uniqid(),
            ]);

        $product = Product::create([
            'user_id'            => $this->store->id,
            'category_id'        => $category->id,
            'category'           => $categoryName,
            'name'               => $name,
            'sku'                => 'SKU-' . uniqid(),
            'price'              => $price,
            'stock_quantity'     => $stock,
            'reorder_threshold'  => 1,
            'recommended_season' => 'both',
            'status'             => 'active',
        ]);

        DecisionFormula::create([
            'crop_type'        => 'Rice',
            'season'           => 'wet',
            'product_id'       => $product->id,
            'rate_per_hectare' => 1,
            'unit'             => 'piece',
        ]);

        return $product;
    }

    /**
     * Prior quotation rows so a product has real farmer demand behind it.
     */
    private function quoteProductBefore(Product $product, int $times): void
    {
        $quotation = Quotation::create([
            'user_id'              => $this->farmer->id,
            'quotation_number'     => 'QT-PRIOR-' . strtoupper(Str::random(6)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.0,
            'estimated_total_cost' => (float) $product->price * $times,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);

        for ($i = 0; $i < $times; $i++) {
            $quotation->items()->create([
                'product_id'          => $product->id,
                'product_name'        => $product->name,
                'price_at_generation' => $product->price,
                'calculated_quantity' => 1,
                'subtotal'            => $product->price,
            ]);
        }
    }

    /**
     * @return array{items: array, deferred_items: array, summary: array}
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
            'selected_products' => [], // nothing chosen → auto-include
        ], $overrides), $this->store);
    }

    private function ids(array $result): array
    {
        return array_column($result['items'], 'product_id');
    }

    // ── Deterministic curation (provider unavailable) ────────────

    public function test_auto_include_suggests_one_best_product_per_job(): void
    {
        // phpunit forces GROQ_API_KEY empty — this is the no-AI path.
        $this->assertEmpty(config('services.groq.key'));

        $popularSeed = $this->makeFormulaProduct('Rice Seeds', 'Popular Inbred Seed', 900);
        $cheapSeed   = $this->makeFormulaProduct('Rice Seeds', 'Budget Seed', 400);
        $legacyFert  = $this->makeFormulaProduct('Fertilizer', 'Legacy Urea (out of stock)', 100, 0);
        $bestFert    = $this->makeFormulaProduct('Complete Fertilizer', 'Complete 14-14-14', 800);
        $protection  = $this->makeFormulaProduct('Crop Protection', 'Shield Fungicide', 300);

        $this->quoteProductBefore($popularSeed, 2);

        $result = $this->quote();

        // Five candidates, three jobs, three suggestions — and the display
        // order runs crop basics first.
        $this->assertSame(
            [$popularSeed->id, $bestFert->id, $protection->id],
            $this->ids($result)
        );
        // Popularity beat the cheaper seed…
        $this->assertNotContains($cheapSeed->id, $this->ids($result));
        // …and being in stock beat the cheaper, much-quoted out-of-stock fertilizer.
        $this->assertNotContains($legacyFert->id, $this->ids($result));
    }

    public function test_fertilizer_families_share_one_slot(): void
    {
        $this->makeFormulaProduct('Rice Seeds', 'Rice Seed', 500);
        $this->makeFormulaProduct('Fertilizer', 'Urea 46-0-0', 430);
        $this->makeFormulaProduct('Complete Fertilizer', 'Complete 14-14-14', 1200);
        $this->makeFormulaProduct('Organic Fertilizer', 'Organic Boost', 900);

        $result = $this->quote();

        $this->assertCount(2, $result['items']);
        // Equal stock and demand → the cheapest fertilizer wins the slot.
        $this->assertContains('Urea 46-0-0', array_column($result['items'], 'product_name'));
    }

    public function test_manually_selected_products_are_never_curated(): void
    {
        $fertA = $this->makeFormulaProduct('Fertilizer', 'Urea 46-0-0', 430);
        $fertB = $this->makeFormulaProduct('Complete Fertilizer', 'Complete 14-14-14', 1200);

        $result = $this->quote(['selected_products' => [$fertA->id, $fertB->id]]);

        // Both share one job — but the farmer asked for them by hand.
        $this->assertCount(2, $result['items']);
        $this->assertEqualsCanonicalizing(
            [$fertA->id, $fertB->id],
            $this->ids($result)
        );
    }

    public function test_a_single_candidate_passes_through_untouched(): void
    {
        $only = $this->makeFormulaProduct('Fertilizer', 'Solo Urea', 430);

        $result = $this->quote();

        $this->assertSame([$only->id], $this->ids($result));
    }

    // ── Provider available: its picks are used (and constrained) ─

    public function test_ai_picks_are_used_when_the_provider_answers(): void
    {
        $seedA = $this->makeFormulaProduct('Rice Seeds', 'AI Chosen Seed', 900);
        $this->makeFormulaProduct('Rice Seeds', 'Cheaper Seed', 400); // deterministic winner
        $fert  = $this->makeFormulaProduct('Fertilizer', 'Urea 46-0-0', 430);

        Config::set('services.groq.key', 'test-key');
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['product_ids' => [$seedA->id, $fert->id]])]],
                ],
            ], 200),
        ]);

        $result = $this->quote();

        Http::assertSent(fn ($request) => str_contains($request->url(), 'groq.com'));

        // The provider's seed is the expensive one the deterministic rule
        // would have skipped — proof the pick came from the reply.
        $this->assertSame([$seedA->id, $fert->id], $this->ids($result));
    }

    public function test_ai_reply_cannot_fill_one_job_twice(): void
    {
        $seedA = $this->makeFormulaProduct('Rice Seeds', 'First Seed', 500);
        $seedB = $this->makeFormulaProduct('Rice Seeds', 'Second Seed', 600);
        $fert  = $this->makeFormulaProduct('Fertilizer', 'Urea 46-0-0', 430);

        Config::set('services.groq.key', 'test-key');
        Http::fake([
            '*' => Http::response([
                'choices' => [
                    ['message' => ['content' => json_encode(['product_ids' => [$seedB->id, $seedA->id, $fert->id]])]],
                ],
            ], 200),
        ]);

        $result = $this->quote();

        // First seed kept, second seed for the same job dropped.
        $this->assertSame([$seedB->id, $fert->id], $this->ids($result));
    }

    // ── Provider unavailable: deterministic, never an error ──────

    public function test_provider_failures_fall_back_to_deterministic_picks(): void
    {
        $this->makeFormulaProduct('Rice Seeds', 'Popular Seed', 900);
        $this->makeFormulaProduct('Rice Seeds', 'Cheaper Seed', 400); // deterministic winner
        $fert = $this->makeFormulaProduct('Fertilizer', 'Urea 46-0-0', 430);

        Config::set('services.groq.key', 'test-key');

        $scenarios = [
            'connection drop'    => fn () => Http::fake(fn () => throw new ConnectionException('cURL error 28: timed out')),
            'server 500'         => fn () => Http::fake(['*' => Http::response('boom', 500)]),
            'garbage reply'      => fn () => Http::fake(['*' => Http::response([
                'choices' => [['message' => ['content' => 'not json at all']]],
            ], 200)]),
            'unknown product id' => fn () => Http::fake(['*' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['product_ids' => [999999]])]]],
            ], 200)]),
            'empty reply'        => fn () => Http::fake(['*' => Http::response([
                'choices' => [['message' => ['content' => '']]],
            ], 200)]),
        ];

        foreach ($scenarios as $label => $fake) {
            $fake();

            $result = $this->quote();

            // Same answer every time: the cheaper in-stock seed and the
            // fertilizer — regardless of what the provider did or did not do.
            $this->assertCount(2, $result['items'], $label);
            $this->assertSame('Cheaper Seed', $result['items'][0]['product_name'], $label);
            $this->assertSame($fert->id, $result['items'][1]['product_id'], $label);
        }
    }

    // ── The overview pages degrade the same way ──────────────────

    public function test_farmer_overview_falls_back_when_the_provider_is_unreachable(): void
    {
        Config::set('services.groq.key', 'test-key');
        Http::fake(fn () => throw new ConnectionException('network unreachable'));

        $farmer = $this->farmer;
        $product = $this->makeFormulaProduct('Fertilizer', 'Premium Urea', 200);

        $quotation = Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-OVER-' . strtoupper(Str::random(6)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.5,
            'estimated_total_cost' => 200,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);
        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => $product->name,
            'price_at_generation' => 200,
            'calculated_quantity' => 1,
            'subtotal'            => 200,
        ]);

        $response = $this->actingAs($farmer)
            ->post(route('farmer.quotations.ai-overview', $quotation), [], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk()->assertJsonPath('source', 'fallback');
        $this->assertStringContainsString('AI Takeaway', $response->json('overview'));
        $this->assertStringContainsString('Premium Urea', $response->json('overview'));
    }

    public function test_store_demand_report_falls_back_without_the_provider(): void
    {
        Config::set('services.groq.key', null);

        $product = $this->makeFormulaProduct('Fertilizer', 'Premium Urea', 200);

        $quotation = Quotation::create([
            'user_id'              => $this->farmer->id,
            'quotation_number'     => 'QT-STORE-' . strtoupper(Str::random(6)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.0,
            'estimated_total_cost' => 200,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);
        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => $product->name,
            'price_at_generation' => 200,
            'calculated_quantity' => 2,
            'subtotal'            => 400,
        ]);

        $response = $this->actingAs($this->store)
            ->post(route('store.quotations.ai-overview'), [], [
                'Accept' => 'application/json',
            ]);

        // A real report, not an error the page would have to explain.
        $response->assertOk()->assertJsonPath('source', 'fallback');

        $overview = $response->json('overview');
        $this->assertStringContainsString('What Farmers Asked For', $overview);
        $this->assertStringContainsString('Premium Urea', $overview);
        $this->assertStringContainsString('[SUGGEST:', $overview);
        $this->assertStringContainsString('Next Step', $overview);
    }

    public function test_store_demand_report_falls_back_when_the_call_fails(): void
    {
        Config::set('services.groq.key', 'test-key');
        Http::fake(['*' => Http::response('gateway timeout', 502)]);

        $product = $this->makeFormulaProduct('Fertilizer', 'Premium Urea', 200);

        $quotation = Quotation::create([
            'user_id'              => $this->farmer->id,
            'quotation_number'     => 'QT-SFAIL-' . strtoupper(Str::random(6)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.0,
            'estimated_total_cost' => 200,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);
        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => $product->name,
            'price_at_generation' => 200,
            'calculated_quantity' => 2,
            'subtotal'            => 400,
        ]);

        $response = $this->actingAs($this->store)
            ->post(route('store.quotations.ai-overview'), [], [
                'Accept' => 'application/json',
            ]);

        $response->assertOk()->assertJsonPath('source', 'fallback');
        $this->assertStringContainsString('Premium Urea', $response->json('overview'));
    }
}
