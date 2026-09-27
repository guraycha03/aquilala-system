<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductImageCapTest extends TestCase
{
    use RefreshDatabase;

    private function storeOwner(): User
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

    private function category(): Category
    {
        return Category::create([
            'name' => 'Fertilizer',
            'slug' => 'fertilizer-' . uniqid(),
        ]);
    }

    private function payload(User $store, Category $category, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Urea',
            'sku' => 'SKU-' . uniqid(),
            'category' => 'Fertilizer',
            'category_id' => $category->id,
            'price' => 100,
            'stock_quantity' => 10,
            'reorder_threshold' => 2,
            'recommended_season' => 'both',
            'status' => 'active',
        ], $overrides);
    }

    public function test_store_accepts_zero_images(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $res = $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category));

        $res->assertRedirect();
        $this->assertSame(0, Product::where('name', 'Test Urea')->firstOrFail()->images()->count());
    }

    public function test_store_first_image_becomes_main(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => [
                UploadedFile::fake()->create('a.jpg', 20, 'image/jpeg'),
                UploadedFile::fake()->create('b.jpg', 20, 'image/jpeg'),
            ],
        ]))->assertRedirect();

        $product = Product::where('name', 'Test Urea')->firstOrFail();
        $gallery = $product->gallery;

        $this->assertCount(2, $gallery);
        $this->assertSame('main', $gallery->first()->image_type);
        $this->assertSame('gallery', $gallery->last()->image_type);
    }

    public function test_store_rejects_more_than_the_cap(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $images = [];
        for ($i = 0; $i <= Product::MAX_IMAGES; $i++) {
            $images[] = UploadedFile::fake()->create("img{$i}.jpg", 20, 'image/jpeg');
        }

        $res = $this->actingAs($store)
            ->post(route('store.products.store'), $this->payload($store, $category, ['images' => $images]));

        $res->assertSessionHasErrors('images');
        $this->assertSame(0, Product::where('name', 'Test Urea')->count());
    }

    public function test_update_caps_new_uploads_by_remaining_slots(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => array_map(
                fn ($i) => UploadedFile::fake()->create("seed{$i}.jpg", 20, 'image/jpeg'),
                range(1, Product::MAX_IMAGES)
            ),
        ]))->assertRedirect();

        $product = Product::where('name', 'Test Urea')->firstOrFail();
        $this->assertSame(Product::MAX_IMAGES, $product->images()->count());

        // A full product must refuse any further upload, not silently exceed.
        $res = $this->actingAs($store)->put(route('store.products.update', $product), $this->payload($store, $category, [
            'name' => 'Test Urea Renamed',
            'sku' => $product->sku,
            'images' => [UploadedFile::fake()->create('extra.jpg', 20, 'image/jpeg')],
        ]));

        $res->assertSessionHasErrors('images');
        $product->refresh();
        $this->assertSame(Product::MAX_IMAGES, $product->images()->count());
    }

    public function test_update_allows_uploads_into_free_slots(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => array_map(
                fn ($i) => UploadedFile::fake()->create("seed{$i}.jpg", 20, 'image/jpeg'),
                range(1, Product::MAX_IMAGES - 2)
            ),
        ]))->assertRedirect();

        $product = Product::where('name', 'Test Urea')->firstOrFail();
        $this->assertSame(Product::MAX_IMAGES - 2, $product->images()->count());

        $this->actingAs($store)->put(route('store.products.update', $product), $this->payload($store, $category, [
            'sku' => $product->sku,
            'images' => [
                UploadedFile::fake()->create('new1.jpg', 20, 'image/jpeg'),
                UploadedFile::fake()->create('new2.jpg', 20, 'image/jpeg'),
            ],
        ]))->assertSessionHasNoErrors();

        $this->assertSame(Product::MAX_IMAGES, $product->fresh()->images()->count());
    }

    public function test_set_main_and_delete_image_work(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($store, $category, [
            'images' => [
                UploadedFile::fake()->create('a.jpg', 20, 'image/jpeg'),
                UploadedFile::fake()->create('b.jpg', 20, 'image/jpeg'),
            ],
        ]))->assertRedirect();

        $product = Product::where('name', 'Test Urea')->firstOrFail();
        $images = $product->images()->orderBy('id')->get();
        $second = $images[1];

        $this->actingAs($store)
            ->patch(route('store.products.images.main', [$product->id, $second->id]))
            ->assertRedirect();

        $this->assertSame('main', $second->fresh()->image_type);
        $this->assertSame('gallery', $images[0]->fresh()->image_type);

        // Deleting the main image must promote another one.
        $this->actingAs($store)
            ->delete(route('store.products.images.destroy', [$product->id, $second->id]))
            ->assertRedirect();

        $product->refresh();
        $this->assertSame(1, $product->images()->count());
        $this->assertSame('main', $product->images()->first()->image_type);
    }

    public function test_cannot_touch_another_stores_image(): void
    {
        Storage::fake('public');
        $storeA = $this->storeOwner();
        $storeB = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($storeA)->post(route('store.products.store'), $this->payload($storeA, $category, [
            'images' => [UploadedFile::fake()->create('a.jpg', 20, 'image/jpeg'), UploadedFile::fake()->create('b.jpg', 20, 'image/jpeg')],
        ]))->assertRedirect();

        $product = Product::where('name', 'Test Urea')->firstOrFail();
        $image = $product->images()->orderBy('id')->first();

        $this->actingAs($storeB)
            ->delete(route('store.products.images.destroy', [$product->id, $image->id]))
            ->assertForbidden();

        $this->assertSame(2, $product->images()->count());
    }
}
