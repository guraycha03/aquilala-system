<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductSpecFieldsTest extends TestCase
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

    private function category(string $name = 'Fertilizer', ?int $parentId = null): Category
    {
        return Category::create([
            'name'      => $name,
            'slug'      => str($name)->slug() . '-' . uniqid(),
            'parent_id' => $parentId,
        ]);
    }

    private function groupWithChild(string $group, string $child): Category
    {
        $parent = $this->category($group);

        return $this->category($child, $parent->id);
    }

    private function payload(Category $category, array $overrides = []): array
    {
        return array_merge([
            'name' => 'Test Product',
            'sku' => 'SKU-' . uniqid(),
            'category' => $category->name,
            'category_id' => $category->id,
            'price' => 100,
            'stock_quantity' => 10,
            'reorder_threshold' => 2,
            'recommended_season' => 'both',
            'status' => 'active',
        ], $overrides);
    }

    public function test_legacy_category_column_is_populated_on_create(): void
    {
        $store = $this->storeOwner();
        $category = $this->category('Nitrogen Fertilizer');

        $this->actingAs($store)
            ->post(route('store.products.store'), $this->payload($category))
            ->assertRedirect();

        $product = Product::where('name', 'Test Product')->firstOrFail();

        // The NOT NULL legacy column must be filled or strict mode rejects the
        // insert outright.
        $this->assertSame('Nitrogen Fertilizer', $product->getRawOriginal('category'));
        $this->assertSame($category->id, $product->category_id);
    }

    public function test_legacy_category_column_stores_the_top_level_group_name(): void
    {
        // The legacy column has always held the GROUP name across the seeded
        // catalog ("Fertilizer", "Seeds", "Crop Protection") while
        // category_id points at the specific sub-category. Writing the leaf
        // name instead would make new rows inconsistent with existing data.
        $store = $this->storeOwner();
        $leaf = $this->groupWithChild('Fertilizer', 'Nitrogen Fertilizer');

        $this->actingAs($store)
            ->post(route('store.products.store'), $this->payload($leaf))
            ->assertRedirect();

        $product = Product::where('name', 'Test Product')->firstOrFail();

        $this->assertSame('Fertilizer', $product->getRawOriginal('category'));
        $this->assertSame('Nitrogen Fertilizer', $product->load('category')->category_display_name);
    }

    public function test_legacy_category_column_uses_own_name_for_a_top_level_category(): void
    {
        // Top-level categories have no parent, so they are their own group.
        $store = $this->storeOwner();
        $top = $this->category('Farm Tools');

        $this->actingAs($store)
            ->post(route('store.products.store'), $this->payload($top))
            ->assertRedirect();

        $product = Product::where('name', 'Test Product')->firstOrFail();

        $this->assertSame('Farm Tools', $product->getRawOriginal('category'));
    }

    public function test_legacy_category_column_follows_a_category_change(): void
    {
        $store = $this->storeOwner();
        $fertilizer = $this->groupWithChild('Fertilizer', 'Nitrogen Fertilizer');
        $pesticide = $this->groupWithChild('Crop Protection', 'Insecticide');

        $this->actingAs($store)
            ->post(route('store.products.store'), $this->payload($fertilizer))
            ->assertRedirect();

        $product = Product::where('name', 'Test Product')->firstOrFail();

        $this->actingAs($store)->put(
            route('store.products.update', $product),
            $this->payload($pesticide, ['sku' => $product->sku, 'name' => 'Test Product'])
        )->assertSessionHasNoErrors();

        $product->refresh();
        $this->assertSame('Crop Protection', $product->getRawOriginal('category'));
        $this->assertSame('Insecticide', $product->load('category')->category_display_name);
    }

    public function test_fertilizer_analysis_is_saved(): void
    {
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'nitrogen_percentage'   => 46,
            'phosphorus_percentage' => 0,
            'potassium_percentage'  => 0,
            'form_type'             => 'Prilled',
            'fertilizer_rate_min'   => 25,
            'fertilizer_rate_max'   => 50,
            'fertilizer_rate_unit'  => 'kg/ha',
            'fertilizer_method'     => 'Broadcasting at planting',
            'fertilizer_timing'     => 'Basal',
        ]))->assertSessionHasNoErrors();

        $product = Product::where('name', 'Test Product')->firstOrFail();
        $fert = $product->fertilizerDetail;

        $this->assertNotNull($fert);
        $this->assertSame('46.00', $fert->nitrogen_percentage);
        $this->assertSame('Prilled', $fert->form_type);
        $this->assertSame('kg/ha', $fert->application_rate_unit);
    }

    public function test_a_zero_only_npk_grade_still_saves(): void
    {
        // Superphosphate 0-20-0 has no nitrogen: the old !empty() guard treated
        // "0" as missing and silently dropped the whole detail row.
        $store = $this->storeOwner();
        $category = $this->category('Complete Fertilizer');

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'name'                   => 'Superphosphate 0-20-0',
            'nitrogen_percentage'   => 0,
            'phosphorus_percentage' => 20,
            'potassium_percentage'  => 0,
        ]))->assertSessionHasNoErrors();

        $product = Product::where('name', 'Superphosphate 0-20-0')->firstOrFail();
        $this->assertNotNull($product->fertilizerDetail, 'a 0-20-0 grade must keep its detail row');
        $this->assertSame('0.00', $product->fertilizerDetail->nitrogen_percentage);
    }

    public function test_application_rate_only_entry_is_saved(): void
    {
        // No NPK at all, just guidance: previously discarded by the guard.
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'form_type'           => 'Liquid',
            'fertilizer_rate_min' => 2,
        ]))->assertSessionHasNoErrors();

        $product = Product::where('name', 'Test Product')->firstOrFail();
        $this->assertNotNull($product->fertilizerDetail);
        $this->assertSame('Liquid', $product->fertilizerDetail->form_type);
    }

    public function test_registration_is_saved(): void
    {
        $store = $this->storeOwner();
        $category = $this->category('Insecticide');

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'registration_status'    => 'Registered',
            'registration_authority' => 'Fertilizer and Pesticide Authority',
            'registration_number'    => '1234-ABC-5678',
            'lot_number'             => 'LOT-2026-014',
            'batch_number'           => 'BATCH-0142',
            'manufacturing_date'     => '2026-01-15',
            'expiration_date'        => '2028-01-15',
        ]))->assertSessionHasNoErrors();

        $product = Product::where('name', 'Test Product')->firstOrFail();
        $reg = $product->registration;

        $this->assertNotNull($reg);
        $this->assertSame('1234-ABC-5678', $reg->registration_number);
        $this->assertSame('LOT-2026-014', $reg->lot_number);
    }

    public function test_registration_number_only_entry_is_saved(): void
    {
        // Only the number filled in: previously discarded.
        $store = $this->storeOwner();
        $category = $this->category('Insecticide');

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'registration_number' => '9999-XYZ-1111',
        ]))->assertSessionHasNoErrors();

        $product = Product::where('name', 'Test Product')->firstOrFail();
        $this->assertNotNull($product->registration);
        $this->assertSame('9999-XYZ-1111', $product->registration->registration_number);
    }

    public function test_blank_spec_blocks_create_no_rows(): void
    {
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)
            ->post(route('store.products.store'), $this->payload($category))
            ->assertSessionHasNoErrors();

        $product = Product::where('name', 'Test Product')->firstOrFail();

        $this->assertNull($product->fertilizerDetail);
        $this->assertNull($product->registration);
    }

    public function test_category_display_name_resolves_through_the_relation(): void
    {
        // The legacy `category` column shadows the `category()` relation, so
        // the accessor must prefer the relation when it is loaded.
        $store = $this->storeOwner();
        $category = $this->category('Nitrogen Fertilizer');

        $this->actingAs($store)
            ->post(route('store.products.store'), $this->payload($category))
            ->assertRedirect();

        $product = Product::with('category')->where('name', 'Test Product')->firstOrFail();

        $this->assertSame('Nitrogen Fertilizer', $product->category_display_name);
    }

    public function test_stock_level_legacy_column_mirrors_stock_quantity(): void
    {
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'stock_quantity' => 37,
        ]))->assertRedirect();

        $product = Product::where('name', 'Test Product')->firstOrFail();
        $this->assertSame(37, (int) $product->stock_level);

        $this->actingAs($store)->put(
            route('store.products.update', $product),
            $this->payload($category, ['sku' => $product->sku, 'stock_quantity' => 5])
        )->assertSessionHasNoErrors();

        $this->assertSame(5, (int) $product->fresh()->stock_level);
    }

    public function test_blanking_the_fertilizer_block_clears_the_saved_row(): void
    {
        // Previously updateOrCreate was skipped entirely when every field was
        // blank, so clearing the form left stale NPK data on the product.
        $store = $this->storeOwner();
        $category = $this->category();

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'nitrogen_percentage'   => 46,
            'phosphorus_percentage' => 0,
            'potassium_percentage'  => 0,
            'form_type'             => 'Prilled',
            'fertilizer_rate_min'   => 25,
        ]))->assertSessionHasNoErrors();

        $product = Product::where('name', 'Test Product')->firstOrFail();
        $this->assertNotNull($product->fertilizerDetail);

        $this->actingAs($store)->put(
            route('store.products.update', $product),
            $this->payload($category, [
                'sku'                    => $product->sku,
                'nitrogen_percentage'    => '',
                'phosphorus_percentage'  => '',
                'potassium_percentage'   => '',
                'form_type'              => '',
                'fertilizer_rate_min'    => '',
                'fertilizer_rate_max'    => '',
                'fertilizer_rate_unit'   => '',
                'fertilizer_method'      => '',
                'fertilizer_timing'      => '',
            ])
        )->assertSessionHasNoErrors();

        $this->assertNull($product->fresh()->fertilizerDetail);
    }

    public function test_blanking_the_registration_block_clears_the_saved_row(): void
    {
        $store = $this->storeOwner();
        $category = $this->category('Insecticide');

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'registration_status' => 'Registered',
            'registration_number' => '1234-ABC-5678',
            'lot_number'          => 'LOT-2026-014',
        ]))->assertSessionHasNoErrors();

        $product = Product::where('name', 'Test Product')->firstOrFail();
        $this->assertNotNull($product->registration);

        $this->actingAs($store)->put(
            route('store.products.update', $product),
            $this->payload($category, [
                'sku'                    => $product->sku,
                'registration_status'    => '',
                'registration_authority' => '',
                'registration_number'    => '',
                'lot_number'             => '',
                'batch_number'           => '',
                'manufacturing_date'     => '',
                'expiration_date'        => '',
            ])
        )->assertSessionHasNoErrors();

        $this->assertNull($product->fresh()->registration);
    }

    public function test_clearing_one_field_keeps_the_others(): void
    {
        $store = $this->storeOwner();
        $category = $this->category('Insecticide');

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'registration_status' => 'Registered',
            'registration_number' => '1234-ABC-5678',
            'lot_number'          => 'LOT-2026-014',
        ]))->assertSessionHasNoErrors();

        $product = Product::where('name', 'Test Product')->firstOrFail();

        $this->actingAs($store)->put(
            route('store.products.update', $product),
            $this->payload($category, [
                'sku'                    => $product->sku,
                'registration_status'    => 'Registered',
                'registration_number'    => '1234-ABC-5678',
                'lot_number'             => '',
            ])
        )->assertSessionHasNoErrors();

        $reg = $product->fresh()->registration;
        $this->assertNotNull($reg);
        $this->assertNull($reg->lot_number, 'the blanked field must be nulled');
        $this->assertSame('1234-ABC-5678', $reg->registration_number);
    }

    public function test_quotation_product_payload_exposes_size_and_category(): void
    {
        $store = $this->storeOwner();
        $farmer = User::create([
            'name' => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'farmer_' . uniqid() . '@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);

        $category = $this->category('Nitrogen Fertilizer');

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'name'          => 'Urea Fertilizer (46-0-0)',
            'package_size'  => 50,
            'package_unit'  => 'kg',
        ]))->assertRedirect();

        $product = Product::where('name', 'Urea Fertilizer (46-0-0)')->firstOrFail();

        DecisionFormula::create([
            'product_id'   => $product->id,
            'crop_type'    => 'Rice',
            'season'       => 'wet',
            'rate_per_hectare' => 100,
            'unit'         => 'kg',
        ]);

        $res = $this->actingAs($farmer)->getJson(route('farmer.quotations.products', [
            'crop_type' => 'Rice',
            'season'    => 'wet',
        ]));

        $res->assertOk();
        $row = $res->json('0');

        $this->assertSame('Urea Fertilizer (46-0-0) — 50 kg', $row['name']);
        $this->assertSame('Nitrogen Fertilizer', $row['category']);
    }

    public function test_the_create_page_renders_without_any_spec_blocks(): void
    {
        // The create form passes no fertilizer/registration rows at all, so the
        // shared component has to cope with both props being null.
        $this->actingAs($this->storeOwner())
            ->get(route('store.products.create'))
            ->assertOk()
            ->assertSee('name="nitrogen_percentage"', escape: false)
            ->assertSee('name="registration_number"', escape: false)
            ->assertSee('name="manufacturing_date" value=""', escape: false)
            ->assertSee('name="expiration_date" value=""', escape: false);
    }

    public function test_the_edit_page_renders_blank_dates_for_a_product_without_registration(): void
    {
        $store = $this->storeOwner();
        $category = $this->category('Herbicide');

        $this->actingAs($store)->post(
            route('store.products.store'),
            $this->payload($category)
        )->assertSessionHasNoErrors();

        $product = Product::where('name', 'Test Product')->firstOrFail();

        $this->actingAs($store)
            ->get(route('store.products.edit', $product))
            ->assertOk()
            ->assertSee('name="manufacturing_date" value=""', escape: false);
    }

    public function test_the_edit_page_prefills_the_saved_registration_dates(): void
    {
        $store = $this->storeOwner();
        $category = $this->category('Herbicide');

        $this->actingAs($store)->post(route('store.products.store'), $this->payload($category, [
            'registration_status'  => 'Registered',
            'manufacturing_date'   => '2026-01-15',
            'expiration_date'      => '2028-01-15',
        ]))->assertSessionHasNoErrors();

        $product = Product::where('name', 'Test Product')->firstOrFail();

        $this->actingAs($store)
            ->get(route('store.products.edit', $product))
            ->assertOk()
            ->assertSee('name="manufacturing_date" value="2026-01-15"', escape: false)
            ->assertSee('name="expiration_date" value="2028-01-15"', escape: false);
    }
}
