<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductMeasureTest extends TestCase
{
    use RefreshDatabase;

    private function product(array $attributes = []): Product
    {
        $store = User::create([
            'name' => 'Green Valley Store',
            'first_name' => 'Green',
            'last_name' => 'Valley',
            'email' => 'store_' . uniqid() . '@example.com',
            'role' => 'store_owner',
            'password' => 'password123',
        ]);

        $category = Category::create([
            'name' => 'Fertilizer',
            'slug' => 'fertilizer-' . uniqid(),
        ]);

        return Product::create(array_merge([
            'user_id'           => $store->id,
            'category_id'       => $category->id,
            'category'          => 'Fertilizer',
            'name'              => 'Urea Fertilizer (46-0-0)',
            'sku'               => 'SKU-' . uniqid(),
            'price'             => 100,
            'stock_quantity'    => 10,
            'reorder_threshold' => 2,
            'status'            => 'active',
        ], $attributes));
    }

    public function test_plain_name_stays_measure_free(): void
    {
        $product = $this->product(['package_size' => 50, 'package_unit' => 'kg']);

        $this->assertSame('Urea Fertilizer (46-0-0)', $product->name);
        $this->assertSame('50 kg', $product->size_label);
        $this->assertSame('Urea Fertilizer (46-0-0) — 50 kg', $product->display_name);
    }

    public function test_display_name_falls_back_to_plain_name_without_a_measure(): void
    {
        $product = $this->product(['package_size' => null, 'package_unit' => null]);

        $this->assertNull($product->size_label);
        $this->assertSame('Urea Fertilizer (46-0-0)', $product->display_name);
    }

    public function test_a_unit_without_a_number_does_not_render_a_measure(): void
    {
        $product = $this->product(['package_size' => null, 'package_unit' => 'kg']);

        $this->assertNull($product->size_label);
        $this->assertSame('Urea Fertilizer (46-0-0)', $product->display_name);
    }

    public function test_units_are_normalised(): void
    {
        $this->assertSame('1 L', $this->product(['package_size' => 1, 'package_unit' => 'l'])->size_label);
        $this->assertSame('1 L', $this->product(['package_size' => 1, 'package_unit' => 'L'])->size_label);
        $this->assertSame('500 mL', $this->product(['package_size' => 500, 'package_unit' => 'ml'])->size_label);
        $this->assertSame('2 kg', $this->product(['package_size' => 2, 'package_unit' => 'KG'])->size_label);
        $this->assertSame('1 piece', $this->product(['package_size' => 1, 'package_unit' => 'PIECE'])->size_label);
        $this->assertSame('3 rolls', $this->product(['package_size' => 3, 'package_unit' => 'rolls'])->size_label);
    }

    public function test_fractional_sizes_keep_their_value(): void
    {
        $this->assertSame('1.5 L', $this->product(['package_size' => 1.5, 'package_unit' => 'L'])->size_label);
        $this->assertSame('2 kg', $this->product(['package_size' => 2.0, 'package_unit' => 'kg'])->size_label);
    }

    public function test_display_name_never_repeats_the_measure(): void
    {
        // A name that still carries its measure must not gain a second one.
        $product = $this->product([
            'name'          => 'Urea Fertilizer (46-0-0) — 50 kg',
            'package_size'  => 50,
            'package_unit'  => 'kg',
        ]);

        $this->assertSame(1, substr_count($product->display_name, '50 kg'));
    }

    public function test_gallery_puts_the_main_image_first(): void
    {
        $product = $this->product(['package_size' => 50, 'package_unit' => 'kg']);

        $second = $product->images()->create([
            'file_path' => 'products/b.jpg',
            'image_type' => 'gallery',
        ]);
        $main = $product->images()->create([
            'file_path' => 'products/a.jpg',
            'image_type' => 'main',
        ]);
        $third = $product->images()->create([
            'file_path' => 'products/c.jpg',
            'image_type' => 'gallery',
        ]);

        $this->assertSame(
            [$main->id, $second->id, $third->id],
            $product->gallery->pluck('id')->all()
        );
    }

    public function test_gallery_is_empty_for_a_product_without_images(): void
    {
        $this->assertTrue($this->product()->gallery->isEmpty());
    }

    public function test_size_label_is_searchable_through_display_name(): void
    {
        $product = $this->product(['package_size' => 50, 'package_unit' => 'kg']);

        $this->assertStringContainsString('50 kg', $product->display_name);
    }
}
