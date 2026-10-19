<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

class ProductCardTest extends TestCase
{
    use RefreshDatabase;

    private User $store;

    private User $farmer;

    private Product $product;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = User::create([
            'name' => 'Green Valley Store',
            'first_name' => 'Green',
            'last_name' => 'Valley',
            'email' => 'store_' . uniqid() . '@example.com',
            'role' => 'store_owner',
            'password' => 'password123',
        ]);

        $this->farmer = User::create([
            'name' => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'farmer_' . uniqid() . '@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);

        $category = Category::create([
            'name' => 'Fertilizer',
            'slug' => 'fertilizer-' . uniqid(),
        ]);

        $this->product = Product::create([
            'user_id'           => $this->store->id,
            'category_id'       => $category->id,
            'category'          => 'Fertilizer',
            'name'              => 'Urea Fertilizer',
            'brand'             => 'Philphos',
            'sku'               => 'UNIQUE-SKU-9001',
            'package_size'      => 50,
            'package_unit'      => 'kg',
            'price'             => 1250.50,
            'stock_quantity'    => 10,
            'reorder_threshold' => 2,
            'recommended_season' => 'wet',
            'status'            => 'active',
        ]);
    }

    /** The store catalog and the farmer marketplace both render the card. */
    public function test_no_catalog_surface_shows_the_sku(): void
    {
        $surfaces = [
            'store catalog' => fn () => $this->actingAs($this->store)->get(route('store.storefront.index')),
            'farmer browse'  => fn () => $this->actingAs($this->farmer)->get(route('farmer.products.browse')),
            'farmer top-quoted' => fn () => $this->actingAs($this->farmer)->get(route('farmer.top-quoted.index')),
            'owner dashboard' => fn () => $this->actingAs($this->store)->get(route('store.dashboard')),
        ];

        foreach ($surfaces as $label => $visit) {
            $response = $visit();
            $response->assertOk();

            $html = $response->getContent();

            $this->assertStringNotContainsString(
                'UNIQUE-SKU-9001',
                $html,
                "the SKU must not appear on the {$label} card"
            );
        }
    }

    public function test_the_sku_is_still_available_on_the_product_detail_page(): void
    {
        $this->actingAs($this->farmer)
            ->get(route('farmer.products.show', $this->product))
            ->assertOk()
            ->assertSee('UNIQUE-SKU-9001');
    }

    /** The farmer preview tab lives behind role:farmer, so its cards must link
     *  to the store-prefixed route or the store owner hits a 403. */
    public function test_the_preview_tab_links_to_detail_pages_a_store_owner_can_open(): void
    {
        $html = $this->actingAs($this->store)->get(route('store.storefront.index'))->assertOk()->getContent();

        $this->assertStringContainsString(route('store.products.show', $this->product->id), $html);

        $this->actingAs($this->store)
            ->get(route('store.products.show', $this->product))
            ->assertOk()
            ->assertSee('Urea Fertilizer');

        $this->actingAs($this->store)
            ->get(route('farmer.products.show', $this->product))
            ->assertForbidden();
    }

    public function test_the_card_still_shows_the_essentials(): void
    {
        $html = $this->actingAs($this->farmer)->get(route('farmer.products.browse'))->getContent();

        // Name with its measure, price, category and the season icon.
        $this->assertStringContainsString('Urea Fertilizer', $html);
        $this->assertStringContainsString('50 kg', $html);
        $this->assertStringContainsString('1,250.50', $html);
        $this->assertStringContainsString('Fertilizer', $html);
        $this->assertStringContainsString('pc-season', $html);
    }

    public function test_the_card_never_shows_stock_or_sku(): void
    {
        // Stock and remaining quantity are stock-control data. They belong on
        // the product detail page, so give the card a product where leaking
        // them would be obvious.
        //
        // The brand is deliberately NOT hidden any more: it is the small light
        // eyebrow above the product name (TradeWinds, AgriVet…), added when the
        // card was restructured into brand / name / divider / unit caption /
        // price. It identifies the maker, which is catalog copy a shopper reads
        // while comparing — unlike stock and SKU, which describe one shop's
        // shelf. Pinned below so a future change cannot quietly remove it.
        $this->product->update([
            'brand'          => 'Philphos',
            'stock_quantity' => 1,
        ]);

        // Assert on the component itself rather than a whole page: the store
        // dashboard legitimately has its own "Low Stock" panel, which is not
        // part of the card and would produce a false failure here.
        $html = Blade::render('<x-product-card :product="$product" />', [
            'product' => $this->product->fresh(),
        ]);

        $this->assertStringContainsString('Philphos', $html, 'the brand eyebrow must appear above the product name');
        $this->assertStringNotContainsString('In Stock', $html, 'the stock badge must not appear on the card');
        $this->assertStringNotContainsString('Low Stock', $html, 'the stock badge must not appear on the card');
        // Target the rendered copy, not the bare word "left": the component's
        // own stylesheet positions the category chip with `left: 10px`, so a
        // naive substring check always matched the CSS and never the markup.
        $this->assertStringNotContainsString('left in stock', $html, 'remaining stock must not appear on the card');
        $this->assertStringNotContainsString('stock-badge', $html, 'the stock badge markup must be gone');
        $this->assertStringNotContainsString('stock_unit', $html, 'the stock unit must not appear on the card');
        $this->assertStringNotContainsString('Urea-001', $html, 'the SKU must not appear on the card');
    }

    public function test_no_catalog_page_still_renders_the_old_card_chrome(): void
    {
        $this->product->update(['brand' => 'Philphos', 'stock_quantity' => 1]);

        $surfaces = [
            'store catalog'   => fn () => $this->actingAs($this->store)->get(route('store.storefront.index')),
            'farmer browse'   => fn () => $this->actingAs($this->farmer)->get(route('farmer.products.browse')),
            'farmer top-quoted' => fn () => $this->actingAs($this->farmer)->get(route('farmer.top-quoted.index')),
            'owner dashboard' => fn () => $this->actingAs($this->store)->get(route('store.dashboard')),
        ];

        foreach ($surfaces as $label => $visit) {
            $html = $visit()->assertOk()->getContent();

            $this->assertStringNotContainsString('Urea-001', $html, "the SKU must not appear on the {$label} card");
            $this->assertStringNotContainsString('stock-badge', $html, "the stock badge must be gone from the {$label} card");
            $this->assertStringNotContainsString('stock-status', $html, "the stock line must be gone from the {$label} card");
        }
    }

    public function test_the_card_uses_official_lucide_icons_instead_of_inline_svg(): void
    {
        $card = function (string $season, bool $actions = true) {
            return Blade::render('<x-product-card :product="$product" :actions="$actions" />', [
                'product' => tap(clone $this->product)->forceFill(['recommended_season' => $season]),
                'actions' => $actions,
            ]);
        };

        // Every icon on the card goes through <i data-lucide="..."> so the
        // official open-source glyphs render, not hand-rolled polygons.
        // Wet season is a raindrop (was an umbrella: it filled in at 14px
        // and read as weather-generic rather than as the season itself).
        $html = $card('wet');
        $this->assertStringContainsString('data-lucide="pencil"', $html);
        $this->assertStringContainsString('data-lucide="trash-2"', $html);
        $this->assertStringContainsString('data-lucide="droplet"', $html);
        $this->assertStringNotContainsString('data-lucide="umbrella"', $html, 'the umbrella glyph was retired for wet season');
        $this->assertStringContainsString('data-lucide="image"', $html);
        $this->assertStringContainsString('data-lucide="sun"', $card('dry'));
        $this->assertStringContainsString('data-lucide="refresh-cw"', $card('all'));

        // And no hand-drawn <svg> icons are left on the card.
        $this->assertStringNotContainsString('<svg', $html);
    }

    public function test_the_card_is_responsive_and_clamped(): void
    {
        $html = $this->actingAs($this->farmer)->get(route('farmer.products.browse'))->getContent();

        // A long name is the main cause of a tall card, so it is clamped.
        $this->assertStringContainsString('-webkit-line-clamp: 2', $html);

        // The clamp must reserve its own two lines (2.7em = 2 x 1.35 line-height)
        // even for a short name, otherwise the row below drifts down on
        // cards with long names and up on cards with short ones.
        $this->assertStringContainsString('min-height: 2.7em', $html);

        // Two phone breakpoints shrink the image, the name and the price.
        $this->assertStringContainsString('@media (max-width: 640px)', $html);
        $this->assertStringContainsString('@media (max-width: 400px)', $html);

        // The image height is driven by a custom property so the breakpoints
        // can scale it; an inline pixel height would win over the media query.
        $this->assertStringContainsString('--img-h:', $html);
        $this->assertStringNotContainsString('style="height: 190px', $html);
    }
}
