<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Quotation Demand Trends — the store owner's demand page.
 *
 * Two rules are guarded here:
 *   1. The page renders for a store owner (it reads the store's own
 *      quotations and nothing else).
 *   2. The week-on-week figure is written in plain words — "2 more than
 *      last week" — never as a percentage against a baseline. A store
 *      owner should not have to do arithmetic to read their own page.
 */
class StoreDemandTrendsTest extends TestCase
{
    use RefreshDatabase;

    private User $store;
    private User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        // The shared sidebar renders the weather widget, which calls out to
        // the weather API on a cold cache. Fake it so the page renders
        // offline and the test never waits on the network.
        Http::fake();

        $this->store = User::create([
            'name'       => 'Green Valley Store',
            'first_name' => 'Green',
            'last_name'  => 'Valley',
            'email'      => 'demand_store_' . uniqid() . '@example.com',
            'role'       => 'store_owner',
            'password'   => 'password123',
        ]);

        $this->farmer = User::create([
            'name'       => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name'  => 'Dela Cruz',
            'email'      => 'demand_farmer_' . uniqid() . '@example.com',
            'role'       => 'farmer',
            'password'   => 'password123',
        ]);
    }

    private function makeQuotation(User $store, User $farmer, string $createdAt, string $productName = 'Premium Urea'): Quotation
    {
        $product = Product::create([
            'user_id'           => $store->id,
            'sku'               => 'SKU-' . strtoupper(substr(md5($productName . $createdAt), 0, 8)),
            'name'              => $productName,
            'category'          => 'Fertilizer',
            'price'             => 100,
            'stock_quantity'    => 10,
            'reorder_threshold' => 2,
            'status'            => 'active',
        ]);

        $quotation = Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-' . strtoupper(substr(md5($createdAt . $productName), 0, 8)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.5,
            'estimated_total_cost' => 300.00,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);

        // created_at is not mass-assignable, so the age of a quotation is
        // stamped straight onto the row — the demand page counts by it.
        Quotation::whereKey($quotation->id)->update([
            'created_at' => $createdAt,
            'updated_at' => $createdAt,
        ]);

        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => $productName,
            'price_at_generation' => 100,
            'calculated_quantity' => 3,
            'subtotal'            => 300,
        ]);

        return $quotation;
    }

    public function test_the_demand_trends_page_renders_for_a_store_owner(): void
    {
        $this->makeQuotation($this->store, $this->farmer, now()->subDay()->toDateTimeString());

        $this->actingAs($this->store)
            ->get(route('store.quotations.index'))
            ->assertOk()
            ->assertSee('Quotation Demand Trends')
            ->assertSee('Your Products in Demand');
    }

    public function test_the_week_figure_is_written_in_plain_words_not_a_percentage(): void
    {
        // Two quotations this week, one the week before → "1 more than last week".
        $this->makeQuotation($this->store, $this->farmer, now()->subDay()->toDateTimeString());
        $this->makeQuotation($this->store, $this->farmer, now()->subDays(3)->toDateTimeString());
        $this->makeQuotation($this->store, $this->farmer, now()->subDays(10)->toDateTimeString());

        $response = $this->actingAs($this->store)
            ->get(route('store.quotations.index'))
            ->assertOk();

        $response->assertSee('1 more than last week');

        // The old jargon — a percentage against a baseline — must not creep back.
        $response->assertDontSee('% more than last week');
        $response->assertDontSee('% less than last week');
    }

    public function test_the_page_shows_the_products_appearing_in_quotations(): void
    {
        $this->makeQuotation($this->store, $this->farmer, now()->subDay()->toDateTimeString(), 'Premium Urea');
        $this->makeQuotation($this->store, $this->farmer, now()->subDays(2)->toDateTimeString(), 'Inbred Rice Seeds');

        $this->actingAs($this->store)
            ->get(route('store.quotations.index'))
            ->assertOk()
            ->assertSee('Premium Urea')
            ->assertSee('Inbred Rice Seeds');
    }

    public function test_a_farmer_cannot_open_the_store_demand_trends_page(): void
    {
        $this->actingAs($this->farmer)
            ->get(route('store.quotations.index'))
            ->assertForbidden();
    }

    // ── The per-quotation view ────────────────────────────────────────────

    public function test_the_quotation_view_renders_for_a_store_owner(): void
    {
        $quotation = $this->makeQuotation($this->store, $this->farmer, now()->subDay()->toDateTimeString());

        $this->actingAs($this->store)
            ->get(route('store.quotations.show', $quotation))
            ->assertOk()
            ->assertSee('Your Products in This Quotation')
            ->assertSee('Full Cost Breakdown')
            ->assertSee('YOUR PRODUCT');
    }

    public function test_the_quotation_view_no_longer_shows_the_read_only_banner(): void
    {
        $quotation = $this->makeQuotation($this->store, $this->farmer, now()->subDay()->toDateTimeString());

        $this->actingAs($this->store)
            ->get(route('store.quotations.show', $quotation))
            ->assertOk()
            ->assertDontSee('Read Only')
            ->assertDontSee('No Action Needed');
    }

    public function test_the_quotation_view_tables_scroll_sideways_on_small_screens(): void
    {
        $quotation = $this->makeQuotation($this->store, $this->farmer, now()->subDay()->toDateTimeString());

        $html = $this->actingAs($this->store)
            ->get(route('store.quotations.show', $quotation))
            ->assertOk()
            ->getContent();

        // Both tables sit inside the shared sideways-scroll wrapper, and each
        // keeps a floor width so a phone scrolls the table instead of
        // crushing every column.
        $this->assertSame(2, substr_count($html, 'class="app-scroll-wrap"'));
        $this->assertSame(2, substr_count($html, 'class="app-scrollbar-thumb"'));
        $this->assertStringContainsString('class="data-table quotation-table"', $html);
        $this->assertStringContainsString('quotation-table--wide', $html);
    }

    public function test_the_your_product_pill_never_breaks_onto_a_second_line(): void
    {
        $quotation = $this->makeQuotation($this->store, $this->farmer, now()->subDay()->toDateTimeString());

        $html = $this->actingAs($this->store)
            ->get(route('store.quotations.show', $quotation))
            ->assertOk()
            ->getContent();

        $this->assertMatchesRegularExpression(
            '/\.your-product-tag\s*\{[^}]*white-space:\s*nowrap;/s',
            $html
        );
    }

    // ── Dark theme ───────────────────────────────────────────────────────

    public function test_the_quotation_view_renders_in_dark_mode(): void
    {
        $quotation = $this->makeQuotation($this->store, $this->farmer, now()->subDay()->toDateTimeString());
        $this->store->update(['theme_mode' => 'dark']);

        $html = $this->actingAs($this->store)
            ->get(route('store.quotations.show', $quotation))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-theme="dark"', $html);
    }

    public function test_the_dark_theme_repaints_every_muted_colour_on_the_page(): void
    {
        // The page's colours live in classes; the dark stylesheet has to
        // know about each of them or the light palette shows through.
        $css = file_get_contents(public_path('css/dark-mode.css'));

        foreach ([
            '.your-product-tag',
            '.your-product-tag--deferred',
            '.quotation-total-row',
            '.quotation-total-row--deferred',
            '.q-title',
            '.q-heading',
            '.q-subtitle',
            '.q-note',
            '.q-product-name',
            '.q-money',
            '.q-total-label',
            '.q-total-label--right',
            '.q-muted',
            '.q-sku',
            '.q-notes',
            '.q-deferred-label',
            '.q-deferred-money',
            '.q-empty',
            '.q-flash',
            '.q-flash-text',
            '.q-status-badge',
        ] as $selector) {
            $this->assertStringContainsString(
                '[data-theme="dark"] ' . $selector,
                $css,
                "The dark theme has no rule for {$selector}."
            );
        }
    }
}
