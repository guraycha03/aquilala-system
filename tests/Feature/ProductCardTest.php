<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
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
            'farmer reviews' => fn () => $this->actingAs($this->farmer)->get(route('farmer.reviews.index')),
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

    public function test_the_card_still_shows_the_essentials(): void
    {
        $html = $this->actingAs($this->farmer)->get(route('farmer.products.browse'))->getContent();

        // Name with its measure, price, category and stock status.
        $this->assertStringContainsString('Urea Fertilizer', $html);
        $this->assertStringContainsString('50 kg', $html);
        $this->assertStringContainsString('1,250.50', $html);
        $this->assertStringContainsString('Fertilizer', $html);
        $this->assertStringContainsString('In Stock', $html);
    }

    public function test_the_card_is_responsive_and_clamped(): void
    {
        $html = $this->actingAs($this->farmer)->get(route('farmer.products.browse'))->getContent();

        // A long name is the main cause of a tall card, so it is clamped.
        $this->assertStringContainsString('-webkit-line-clamp: 2', $html);

        // Two phone breakpoints shrink the image, the name and the price.
        $this->assertStringContainsString('@media (max-width: 640px)', $html);
        $this->assertStringContainsString('@media (max-width: 400px)', $html);

        // The image height is driven by a custom property so the breakpoints
        // can scale it; an inline pixel height would win over the media query.
        $this->assertStringContainsString('--img-h:', $html);
        $this->assertStringNotContainsString('style="height: 190px', $html);
    }
}
