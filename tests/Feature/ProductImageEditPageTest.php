<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The edit page used to nest the "Set main" / "Remove" forms inside the
 * product form. Browsers drop the inner <form> tag, so "Remove" submitted the
 * product form — and because that form carried a _method=DELETE field, Laravel
 * ran products.destroy and the whole product disappeared.
 */
class ProductImageEditPageTest extends TestCase
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

    private function payload(Category $category, array $overrides = []): array
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

    private function productWithImages(User $store, Category $category, int $images = 2): Product
    {
        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'images' => array_map(
                fn ($i) => UploadedFile::fake()->create("img{$i}.jpg", 20, 'image/jpeg'),
                range(1, $images)
            ),
        ]))->assertRedirect();

        return Product::where('name', 'Test Urea')->firstOrFail();
    }

    /** Depth of the deepest form-in-form sequence in a rendered page. */
    private function maxFormDepth(string $html): int
    {
        preg_match_all('#</?form\b[^>]*>#i', $html, $matches);

        $depth = 0;
        $max = 0;

        foreach ($matches[0] as $token) {
            $depth += str_starts_with(strtolower($token), '</') ? -1 : 1;
            $max = max($max, $depth);
        }

        return $max;
    }

    /** Byte offset of the </form> that closes the form opened at $open. */
    private function closingOffsetOf(string $html, int $open): int
    {
        $depth = 0;
        preg_match_all('#</?form\b[^>]*>#i', $html, $matches, PREG_OFFSET_CAPTURE);

        foreach ($matches[0] as [$token, $offset]) {
            if ($offset < $open) {
                continue;
            }

            $depth += str_starts_with(strtolower($token), '</') ? -1 : 1;

            if ($depth === 0) {
                return $offset;
            }
        }

        $this->fail('The product form was never closed in the rendered page.');
    }

    public function test_the_edit_page_never_nests_one_form_inside_another(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();
        $product = $this->productWithImages($store, $category);

        $html = $this->actingAs($store)
            ->get(route('store.products.edit', $product))
            ->assertOk()
            ->getContent();

        $this->assertSame(
            1,
            $this->maxFormDepth($html),
            'The edit page must render sibling forms, never one form inside another.'
        );
    }

    public function test_the_image_actions_sit_outside_the_product_form(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();
        $product = $this->productWithImages($store, $category);
        $image = $product->images()->orderBy('id')->first();

        $html = $this->actingAs($store)->get(route('store.products.edit', $product))->getContent();

        $productForm = strpos($html, '<form method="POST" action="' . route('store.products.update', $product) . '"');
        $removeForm = strpos($html, route('store.products.images.destroy', [$product, $image]));

        $this->assertNotFalse($productForm, 'The product form must be on the page.');
        $this->assertNotFalse($removeForm, 'The remove-image form must be on the page.');

        $productFormEnd = $this->closingOffsetOf($html, $productForm);

        $this->assertTrue(
            $removeForm < $productForm || $removeForm > $productFormEnd,
            'The remove-image form must be a sibling of the product form, not a child of it.'
        );

        // It still has to work on its own: confirm hook, DELETE method, CSRF.
        $this->assertStringContainsString('data-aqui-confirm="Remove this image from the product?"', $html);
        $this->assertStringContainsString('value="DELETE"', $html);
    }

    public function test_stripping_every_image_leaves_the_product_healthy(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();
        $product = $this->productWithImages($store, $category);

        foreach ($product->images()->orderBy('id')->get() as $image) {
            $this->actingAs($store)
                ->delete(route('store.products.images.destroy', [$product, $image]))
                ->assertRedirect();

            $this->assertDatabaseHas('products', ['id' => $product->id]);
        }

        $product->refresh();
        $this->assertSame(0, $product->images()->count());
        $this->assertSame('Test Urea', $product->name);

        // The page must still render and the product must still be editable
        // with an empty gallery.
        $html = $this->actingAs($store)
            ->get(route('store.products.edit', $product))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, $this->maxFormDepth($html));

        $this->actingAs($store)->put(route('store.products.update', $product), $this->payload($category, [
            'sku' => $product->sku,
            'name' => 'Test Urea Renamed',
        ]))->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('Test Urea Renamed', $product->name);
        $this->assertSame(0, $product->images()->count());
    }

    public function test_the_edit_page_renders_for_a_product_without_images(): void
    {
        Storage::fake('public');
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category))
            ->assertRedirect();

        $product = Product::where('name', 'Test Urea')->firstOrFail();

        $html = $this->actingAs($store)
            ->get(route('store.products.edit', $product))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, $this->maxFormDepth($html));
        $this->assertStringContainsString('Add Photos', $html);
        $this->assertStringNotContainsString('Add More Photos', $html);
        $this->assertStringNotContainsString('Add More Images', $html);
        $this->assertStringContainsString('form-section-static', $html);
        $this->assertStringNotContainsString('data-aqui-confirm="Remove this image from the product?"', $html);
    }
}
