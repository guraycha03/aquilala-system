<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Blade;
use Tests\TestCase;

/**
 * The "save for later" bookmark on the shared product card. There is no
 * saved-products page yet — the button just syncs the per-user state.
 */
class SavedProductsTest extends TestCase
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
            'sku'               => 'SAVE-TEST-SKU',
            'package_size'      => 50,
            'package_unit'      => 'kg',
            'price'             => 1250.50,
            'stock_quantity'    => 10,
            'reorder_threshold' => 2,
            'recommended_season' => 'wet',
            'status'            => 'active',
        ]);
    }

    public function test_a_signed_in_farmer_sees_the_save_button_on_marketplace_cards(): void
    {
        $html = $this->actingAs($this->farmer)->get(route('farmer.products.browse'))->getContent();

        $this->assertStringContainsString('data-lucide="bookmark"', $html);
        $this->assertStringContainsString('class="pc-save', $html);
        $this->assertStringContainsString(
            route('products.save', $this->product->id),
            $html,
            'the save button must POST at the per-product save URL'
        );

        // Icon-only bookmark on the card's own top row: the button must sit
        // above the footer price, and there must be no inline label next to
        // it. Class markers are matched on the rendered markup, not bare
        // tokens: the card's own stylesheet also spells out those selector
        // names.
        $savePos   = strpos($html, 'data-save-url=');
        $pricePos  = strpos($html, 'class="price-tag');
        $this->assertNotFalse($savePos);
        $this->assertNotFalse($pricePos);
        $this->assertLessThan($pricePos, $savePos, 'the save icon sits above the footer price');
        $this->assertStringContainsString('aria-pressed=', $html);
        $this->assertStringNotContainsString('pc-save-text', $html, 'the bookmark is icon-only');
    }

    public function test_a_card_without_a_detail_link_has_no_save_button(): void
    {
        // The store owner's own catalog (manage tab) renders the card without
        // an href — there is no detail page to come back to, so no bookmark.
        // Assert on the rendered markup, not the bare token: the component's
        // own stylesheet/script also contain ".pc-save".
        $html = Blade::render('<x-product-card :product="$product" :actions="true" />', [
            'product' => $this->product,
        ]);

        $this->assertStringNotContainsString('class="pc-save', $html);
        $this->assertStringNotContainsString('data-lucide="bookmark"', $html);
    }

    public function test_the_toggle_saves_and_unsaves_for_the_signed_in_user(): void
    {
        $this->actingAs($this->farmer)
            ->postJson(route('products.save', $this->product))
            ->assertOk()
            ->assertJson(['saved' => true]);

        $this->assertDatabaseHas('saved_products', [
            'user_id'    => $this->farmer->id,
            'product_id' => $this->product->id,
        ]);

        $this->actingAs($this->farmer)
            ->postJson(route('products.save', $this->product))
            ->assertOk()
            ->assertJson(['saved' => false]);

        $this->assertDatabaseMissing('saved_products', [
            'user_id'    => $this->farmer->id,
            'product_id' => $this->product->id,
        ]);
    }

    public function test_saving_is_isolated_per_user(): void
    {
        $this->actingAs($this->farmer)
            ->postJson(route('products.save', $this->product))
            ->assertOk();

        // Farmers can only save active listings, but this is Farmer's save.
        // A different user must not see it as saved.
        $html = $this->actingAs($this->store)->get(route('farmer.products.browse'))->getContent();

        $this->assertStringNotContainsString('aria-pressed="true"', $html, 'the store owner must not inherit the farmer\'s save');
        $this->assertDatabaseCount('saved_products', 1);
        $this->assertDatabaseHas('saved_products', ['user_id' => $this->farmer->id]);
    }

    public function test_a_saved_card_opens_filled(): void
    {
        $this->actingAs($this->farmer)
            ->postJson(route('products.save', $this->product))
            ->assertOk();

        $html = $this->actingAs($this->farmer)->get(route('farmer.products.browse'))->getContent();

        $this->assertStringContainsString('pc-save is-saved', $html);
        $this->assertStringContainsString('aria-pressed="true"', $html);
        $this->assertStringContainsString('Remove from saved', $html);
        $this->assertStringNotContainsString('pc-save-text', $html, 'the bookmark is icon-only even when saved');
    }

    public function test_a_guest_cannot_save_and_is_redirected_to_login(): void
    {
        $this->post(route('products.save', $this->product))
            ->assertRedirect(route('login'));

        $this->assertDatabaseMissing('saved_products', [
            'product_id' => $this->product->id,
        ]);
    }
}