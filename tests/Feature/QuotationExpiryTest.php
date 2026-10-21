<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Services\StoreDemandService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What an expired estimate means, everywhere it shows up.
 *
 * An estimate is a snapshot: the price the farmer was given and planned
 * around. It does not change, and it does not quietly become a price list.
 * When its window closes it says so, it leaves the live figures, and it
 * waits in the farmer's archive.
 */
class QuotationExpiryTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;

    private User $store;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->farmer = $this->makeUser('Juan Cruz', 'farmer');
        $this->store = $this->makeUser('Oakridge Store', 'store_owner');

        $this->product = Product::create([
            'user_id'           => $this->store->id,
            'sku'               => 'SKU-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'name'              => 'Premium Urea 46-0-0',
            'category'          => 'Fertilizers',
            'price'             => 1450.00,
            'stock_quantity'    => 40,
            'reorder_threshold' => 5,
            'status'            => 'active',
        ]);
    }

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'User',
            'email' => strtolower(str_replace(' ', '.', $name)) . '.' . uniqid() . '@example.test',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
            'password' => 'password123',
        ]);
    }

    private function makeQuotation(int $validInDays, float $price = 1450.00): Quotation
    {
        $quotation = Quotation::create([
            'user_id'              => $this->farmer->id,
            'quotation_number'     => 'QT-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.0,
            'estimated_total_cost' => $price * 3,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays($validInDays),
        ]);

        $quotation->items()->create([
            'product_id'          => $this->product->id,
            'product_name'        => $this->product->name,
            'price_at_generation' => $price,
            'calculated_quantity' => 3,
            'subtotal'            => $price * 3,
        ]);

        return $quotation;
    }

    // ────────────────────────────────────────────────────────────
    //  A store opening an expired estimate is told
    // ────────────────────────────────────────────────────────────

    public function test_a_store_sees_a_notice_on_an_expired_estimate(): void
    {
        $expired = $this->makeQuotation(-3);

        $html = $this->actingAs($this->store)
            ->get(route('store.quotations.show', $expired))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('This estimate expired on', $html);
        $this->assertStringContainsString('may have moved', $html);
    }

    public function test_a_live_estimate_carries_no_expiry_notice(): void
    {
        $live = $this->makeQuotation(5);

        $html = $this->actingAs($this->store)
            ->get(route('store.quotations.show', $live))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('This estimate expired on', $html);
    }

    // ────────────────────────────────────────────────────────────
    //  The live figures leave expired estimates out
    // ────────────────────────────────────────────────────────────

    public function test_expired_estimates_leave_the_live_demand_figures(): void
    {
        $this->makeQuotation(-5);
        $this->makeQuotation(5);

        $summary = app(StoreDemandService::class)->summary($this->store->id);

        $this->assertSame(1, $summary['live_quotations'], 'Only the live estimate counts as live demand.');
        $this->assertSame(1, $summary['expired_quotations']);
        $this->assertSame(2, $summary['total_quotations'], 'The history keeps both.');
        $this->assertSame(1, $summary['this_week']);
    }

    // ────────────────────────────────────────────────────────────
    //  A quoted cost is fixed
    // ────────────────────────────────────────────────────────────

    public function test_a_price_change_is_reported_and_the_quoted_cost_stands(): void
    {
        $quotation = $this->makeQuotation(5, 1450.00);

        // The store re-prices the product.
        $this->product->update(['price' => 1600.00]);

        $changes = $quotation->fresh()->priceChanges();

        $this->assertCount(1, $changes);
        $this->assertSame(1450.00, $changes[0]['was']);
        $this->assertSame(1600.00, $changes[0]['now']);

        // The estimate itself did not move.
        $quotation->refresh();
        $this->assertSame(4350.00, (float) $quotation->estimated_total_cost);
        $this->assertSame(1450.00, (float) $quotation->items->first()->price_at_generation);

        // And the store is told, at the foot of the estimate.
        $html = $this->actingAs($this->store)
            ->get(route('store.quotations.show', $quotation))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('A price has changed since this estimate', $html);
        $this->assertStringContainsString('1,450.00', $html);
        $this->assertStringContainsString('1,600.00', $html);
    }

    public function test_an_unchanged_price_raises_no_notice(): void
    {
        $quotation = $this->makeQuotation(5, 1450.00);

        $this->assertSame([], $quotation->fresh()->priceChanges());

        $html = $this->actingAs($this->store)
            ->get(route('store.quotations.show', $quotation))
            ->assertOk()
            ->getContent();

        $this->assertStringNotContainsString('A price has changed since this estimate', $html);
    }

    // ────────────────────────────────────────────────────────────
    //  The farmer's archive
    // ────────────────────────────────────────────────────────────

    public function test_an_expired_estimate_leaves_the_working_page_for_the_archive(): void
    {
        $this->makeQuotation(-4);
        $this->makeQuotation(6);

        $index = $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Archived estimates', $index);
        // Only the live one is on the working page.
        $this->assertSame(1, substr_count($index, 'data-search="qt-'));

        $archive = $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.archive'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('Archived estimates', $archive);
        $this->assertSame(1, substr_count($archive, 'class="archive-ref"'));
    }

    public function test_the_archive_is_empty_when_nothing_has_expired(): void
    {
        $this->makeQuotation(6);

        $this->actingAs($this->farmer)
            ->get(route('farmer.quotations.archive'))
            ->assertOk()
            ->assertSee('Nothing archived yet', false);
    }

    // ────────────────────────────────────────────────────────────
    //  The window
    // ────────────────────────────────────────────────────────────

    public function test_a_new_estimate_holds_for_the_window_the_farmer_chose(): void
    {
        $this->actingAs($this->farmer);

        $response = $this->postJson(route('farmer.quotations.store'), [
            'crop_type' => 'Rice',
            'season' => 'wet',
            'land_area' => 1,
            'valid_days' => 3,
        ]);

        $response->assertRedirect();

        $quotation = Quotation::where('user_id', $this->farmer->id)->latest()->first();

        $this->assertNotNull($quotation);
        $this->assertTrue($quotation->valid_until->isAfter(now()->addDays(2)));
        $this->assertTrue($quotation->valid_until->isBefore(now()->addDays(4)));
    }
}
