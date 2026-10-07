<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductArchiveTest extends TestCase
{
    use RefreshDatabase;

    private User $store;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = User::create([
            'name'       => 'Green Valley Store',
            'first_name' => 'Green',
            'last_name'  => 'Valley',
            'email'      => 'store_' . uniqid() . '@example.com',
            'role'       => 'store_owner',
            'password'   => 'password123',
        ]);

        $this->category = Category::create([
            'name' => 'Fertilizer',
            'slug' => 'fertilizer-' . uniqid(),
        ]);
    }

    private function product(array $attributes = [], ?User $owner = null): Product
    {
        return Product::create(array_merge([
            'user_id'           => ($owner ?? $this->store)->id,
            'category_id'       => $this->category->id,
            'category'          => 'Fertilizer',
            'name'              => 'Urea Fertilizer (46-0-0)',
            'sku'               => 'SKU-' . uniqid(),
            'price'             => 100,
            'stock_quantity'    => 10,
            'reorder_threshold' => 2,
            'recommended_season' => 'both',
            'status'            => 'active',
        ], $attributes));
    }

    /**
     * The payload the manual create/edit form submits (only the required
     * rules plus the fields the controller always reads).
     */
    private function payload(Product $product): array
    {
        return [
            'name'               => $product->name,
            'sku'                => $product->sku,
            'category_id'        => $this->category->id,
            'price'              => $product->price,
            'stock_quantity'     => $product->stock_quantity,
            'reorder_threshold'  => $product->reorder_threshold,
            'recommended_season' => 'both',
            'status'             => 'active',
        ];
    }

    public function test_creating_a_product_is_logged_in_the_store_owners_activity_log(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.products.store'), [
                'name'               => 'New Urea Batch',
                'sku'                => 'NEW-' . uniqid(),
                'category_id'        => $this->category->id,
                'price'              => 2150,
                'stock_quantity'     => 120,
                'reorder_threshold'  => 20,
                'recommended_season' => 'both',
                'status'             => 'active',
            ])
            ->assertRedirect(route('store.storefront.index'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->store->id,
            'type'    => ActivityLog::TYPE_PRODUCT_CREATED,
        ]);
    }

    public function test_updating_a_product_logs_the_field_changes(): void
    {
        $product = $this->product(['price' => 100]);

        $payload = $this->payload($product);
        $payload['price'] = 120;

        $this->actingAs($this->store)
            ->put(route('store.products.update', $product), $payload)
            ->assertRedirect(route('store.storefront.index'));

        $log = ActivityLog::where('user_id', $this->store->id)
            ->where('type', ActivityLog::TYPE_PRODUCT_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($log, 'The price change should be logged as a product update.');
        $this->assertStringContainsString('price', $log->description);
        $this->assertStringContainsString('₱120.00', $log->description);
    }

    public function test_a_no_op_resubmit_is_not_logged_as_a_change(): void
    {
        $product = $this->product();

        $this->actingAs($this->store)
            ->put(route('store.products.update', $product), $this->payload($product))
            ->assertRedirect(route('store.storefront.index'));

        $this->assertSame(0, ActivityLog::where('type', ActivityLog::TYPE_PRODUCT_UPDATED)->count());
    }

    public function test_deleting_a_product_archives_it_and_logs_the_archive(): void
    {
        $product = $this->product();

        $this->actingAs($this->store)
            ->delete(route('store.products.destroy', $product))
            ->assertRedirect(route('store.storefront.index'));

        $this->assertSoftDeleted('products', ['id' => $product->id]);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->store->id,
            'type'    => ActivityLog::TYPE_PRODUCT_DELETED,
        ]);
    }

    public function test_an_archived_product_is_hidden_from_the_storefront_and_the_marketplace(): void
    {
        $product = $this->product(['name' => 'Hidden Harvest Batch']);

        $this->actingAs($this->store)
            ->delete(route('store.products.destroy', $product));

        $this->actingAs($this->store)
            ->get(route('store.storefront.index'))
            ->assertDontSee('Hidden Harvest Batch');

        $this->actingAs($this->store)
            ->get(route('store.products.trash'))
            ->assertOk()
            ->assertSee('Hidden Harvest Batch');
    }

    public function test_restoring_an_archived_product_brings_it_back_and_logs_the_restore(): void
    {
        $product = $this->product(['name' => 'Returnable Rice Sacks']);

        $this->actingAs($this->store)
            ->delete(route('store.products.destroy', $product));

        $this->actingAs($this->store)
            ->post(route('store.products.restore', $product))
            ->assertRedirect(route('store.products.trash'));

        $this->assertNull($product->fresh()->deleted_at);

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->store->id,
            'type'    => ActivityLog::TYPE_PRODUCT_RESTORED,
        ]);

        $this->actingAs($this->store)
            ->get(route('store.storefront.index'))
            ->assertSee('Returnable Rice Sacks');
    }

    public function test_force_deleting_an_archived_product_erases_it_and_logs_the_permanent_delete(): void
    {
        $product = $this->product();

        $this->actingAs($this->store)
            ->delete(route('store.products.destroy', $product));

        $this->actingAs($this->store)
            ->delete(route('store.products.force-destroy', $product))
            ->assertRedirect(route('store.products.trash'));

        $this->assertDatabaseMissing('products', ['id' => $product->id]);

        $log = ActivityLog::where('user_id', $this->store->id)
            ->where('type', ActivityLog::TYPE_PRODUCT_DELETED)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertSame('permanent', $log->metadata['action'] ?? null);
    }

    public function test_the_activity_log_page_lists_the_product_crud_entries(): void
    {
        $product = $this->product(['name' => 'Audited Superphosphate']);

        $this->actingAs($this->store)->delete(route('store.products.destroy', $product));

        $this->actingAs($this->store)
            ->get(route('store.activity.index'))
            ->assertOk()
            ->assertSee('Archived product', false)
            ->assertSee('Audited Superphosphate', false);
    }

    public function test_stock_edits_from_the_inventory_page_are_logged(): void
    {
        $product = $this->product();

        $this->actingAs($this->store)
            ->post(route('store.inventory.update', $product), [
                'stock_quantity'    => 55,
                'reorder_threshold' => 4,
            ])
            ->assertRedirect(route('store.inventory.index'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->store->id,
            'type'    => ActivityLog::TYPE_PRODUCT_UPDATED,
        ]);
    }

    public function test_an_archived_sku_still_blocks_a_duplicate_product(): void
    {
        $product = $this->product(['sku' => 'TAKEN-SKU-01']);

        $this->actingAs($this->store)
            ->delete(route('store.products.destroy', $product));

        $response = $this->actingAs($this->store)->post(route('store.products.store'), [
            'name'               => 'Same SKU Copy',
            'sku'                => 'TAKEN-SKU-01',
            'category_id'        => $this->category->id,
            'price'              => 50,
            'stock_quantity'     => 5,
            'reorder_threshold'  => 1,
            'recommended_season' => 'both',
            'status'             => 'active',
        ]);

        $response->assertSessionHasErrors('sku');
        $this->assertDatabaseMissing('products', ['sku' => 'TAKEN-SKU-01', 'name' => 'Same SKU Copy']);
    }

    public function test_a_different_store_owner_cannot_archive_or_restore_a_product(): void
    {
        $other = User::create([
            'name'       => 'Other Store',
            'first_name' => 'Other',
            'last_name'  => 'Store',
            'email'      => 'other_' . uniqid() . '@example.com',
            'role'       => 'store_owner',
            'password'   => 'password123',
        ]);

        $product = $this->product();

        $this->actingAs($other)
            ->delete(route('store.products.destroy', $product))
            ->assertForbidden();

        $this->assertNull($product->fresh()->deleted_at);

        $archived = $this->product();
        $this->actingAs($this->store)->delete(route('store.products.destroy', $archived));

        $this->actingAs($other)
            ->post(route('store.products.restore', $archived))
            ->assertForbidden();

        $this->assertNotNull($archived->fresh()->deleted_at);
    }

    public function test_force_delete_and_restore_resolve_archived_rows_instead_of_404ing(): void
    {
        $product = $this->product();

        $this->actingAs($this->store)->delete(route('store.products.destroy', $product));

        // Route model binding must resolve trashed rows or restore would 404
        // before the controller ever ran.
        $this->actingAs($this->store)
            ->get(route('store.products.trash'))
            ->assertOk();

        $this->actingAs($this->store)
            ->post(route('store.products.restore', $product))
            ->assertRedirect(route('store.products.trash'));
    }

    public function test_the_archive_page_offers_a_button_and_a_picker_of_active_products(): void
    {
        $this->product(['name' => 'Picker Urea Sacks']);

        $this->actingAs($this->store)
            ->get(route('store.products.trash'))
            ->assertOk()
            ->assertSee('Add Product')
            ->assertSee('Picker Urea Sacks');
    }

    public function test_checking_multiple_products_in_the_picker_archives_them_all(): void
    {
        $first = $this->product(['name' => 'First Bulk Item']);
        $second = $this->product(['name' => 'Second Bulk Item']);

        $this->actingAs($this->store)
            ->post(route('store.products.archive-bulk'), [
                'product_ids' => [$first->id, $second->id],
            ])
            ->assertRedirect(route('store.products.trash'));

        $this->assertSoftDeleted('products', ['id' => $first->id]);
        $this->assertSoftDeleted('products', ['id' => $second->id]);

        $this->assertSame(
            2,
            ActivityLog::where('user_id', $this->store->id)
                ->where('type', ActivityLog::TYPE_PRODUCT_DELETED)
                ->where('metadata->action', 'archived')
                ->count()
        );
    }

    public function test_the_bulk_archive_skips_products_the_store_does_not_own(): void
    {
        $other = User::create([
            'name'       => 'Foreign Store',
            'first_name' => 'Foreign',
            'last_name'  => 'Store',
            'email'      => 'foreign_' . uniqid() . '@example.com',
            'role'       => 'store_owner',
            'password'   => 'password123',
        ]);

        $mine = $this->product();
        $foreign = $this->product(['name' => 'Not Mine'], $other);

        $this->actingAs($this->store)
            ->post(route('store.products.archive-bulk'), [
                'product_ids' => [$mine->id, $foreign->id],
            ])
            ->assertRedirect(route('store.products.trash'));

        $this->assertSoftDeleted('products', ['id' => $mine->id]);
        $this->assertNull($foreign->fresh()->deleted_at);
    }

    public function test_the_bulk_archive_rejects_an_empty_selection(): void
    {
        $this->actingAs($this->store)
            ->from(route('store.products.trash'))
            ->post(route('store.products.archive-bulk'), ['product_ids' => []])
            ->assertSessionHasErrors('product_ids');

        $this->actingAs($this->store)
            ->post(route('store.products.archive-bulk'), [])
            ->assertSessionHasErrors('product_ids');
    }
}
