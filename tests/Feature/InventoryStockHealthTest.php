<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The Stock Health donut, the metric cards and the status filters must count
 * the same products the same way.
 *
 * They did not. An out-of-stock product has quantity 0, and 0 satisfies
 * `quantity <= reorder_threshold`, so a single empty shelf was counted as BOTH
 * out of stock and low stock, then subtracted twice from healthyCount. A store
 * with one zero-quantity product saw "1 out of stock, 1 low stock, 16 healthy"
 * while its low-stock filter correctly returned nothing — the numbers could
 * not all be true at once.
 *
 * Buckets are therefore decided out-of-stock FIRST, which is how the bar chart
 * beside the donut has always worked (it returns early on quantity <= 0).
 */
class InventoryStockHealthTest extends TestCase
{
    use RefreshDatabase;

    private User $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->store = User::create([
            'name'         => 'Green Valley Store',
            'first_name'   => 'Green',
            'last_name'    => 'Valley',
            'email'        => 'store_' . uniqid() . '@example.com',
            'role'         => 'store_owner',
            'password'     => 'password123',
        ]);
    }

    /**
     * Store 86's real shape: one product at zero, everything else comfortably
     * above its limit. Nothing is genuinely low, so low stock must read 0.
     */
    public function test_an_empty_shelf_is_not_also_counted_as_low_stock(): void
    {
        $category = $this->category();

        $this->product($category, 'Earthworm Vermicast', 0, 10, 'out_of_stock');
        $this->product($category, 'YaraMila Winner', 35, 10, 'active');
        $this->product($category, 'Greenway Urea', 18, 8, 'active');
        $this->product($category, 'Multi-K Potassium', 60, 12, 'active');

        $data = $this->inventoryData();

        $this->assertSame(4, $data['totalItems']);
        $this->assertSame(1, $data['outOfStockCount'], 'The zero-quantity product counts as out of stock, exactly once.');
        $this->assertSame(
            0,
            $data['lowStockCount'],
            'A zero-quantity product is out of stock, not low stock. `0 <= reorder_threshold` used to satisfy the low test as well.'
        );
        $this->assertSame(3, $data['healthyCount'], 'One out, none low — the other three are healthy, not four minus two.');
    }

    /**
     * Guard against over-correcting: a product that is below its limit but
     * still has stock IS genuinely low and must stay counted as such.
     */
    public function test_a_product_below_its_limit_but_above_zero_is_still_low_stock(): void
    {
        $category = $this->category();

        $this->product($category, 'Empty sack', 0, 10, 'out_of_stock');
        $this->product($category, 'Running down', 4, 5, 'active');
        $this->product($category, 'Comfortable', 50, 10, 'active');

        $data = $this->inventoryData();

        $this->assertSame(1, $data['outOfStockCount']);
        $this->assertSame(1, $data['lowStockCount'], 'Positive stock below the reorder level is still low stock.');
        $this->assertSame(1, $data['healthyCount']);
    }

    /**
     * Every product lands in exactly one bucket. Double counting shows up
     * immediately as the three numbers exceeding the catalog total.
     */
    public function test_the_buckets_partition_the_catalog_exactly(): void
    {
        $category = $this->category();

        $this->product($category, 'Out', 0, 10, 'out_of_stock');
        $this->product($category, 'Low', 3, 10, 'active');
        $this->product($category, 'Exactly at limit', 10, 10, 'active');
        $this->product($category, 'Healthy', 99, 10, 'active');
        $this->product($category, 'Flagged out despite stock', 8, 10, 'out_of_stock');

        $data = $this->inventoryData();

        $this->assertSame(5, $data['totalItems']);
        $this->assertSame(
            $data['totalItems'],
            $data['healthyCount'] + $data['lowStockCount'] + $data['outOfStockCount'],
            'The three buckets must sum to the catalog total — otherwise a product is being counted twice.'
        );
    }

    /**
     * The discrepancy that exposed the bug: the filter dropdown and the Stock
     * Health card disagreed about how many products were low. They are now
     * derived from the same rule, so they cannot drift apart.
     */
    public function test_the_donut_and_the_status_filters_agree(): void
    {
        $category = $this->category();

        $this->product($category, 'Out', 0, 10, 'out_of_stock');
        $this->product($category, 'Low', 4, 5, 'active');
        $this->product($category, 'Healthy', 50, 10, 'active');
        $this->product($category, 'Also healthy', 25, 5, 'active');

        $data = $this->inventoryData();

        $map = [
            'low'     => 'lowStockCount',
            'out'     => 'outOfStockCount',
            'healthy' => 'healthyCount',
        ];

        foreach ($map as $filter => $key) {
            $filtered = $this->inventoryData(['status' => $filter]);

            $this->assertSame(
                $data[$key],
                $filtered['products']->count(),
                "The {$filter}-stock filter must return exactly the products the Stock Health card counts as {$key}."
            );
        }
    }

    private function category(): Category
    {
        return Category::create([
            'name' => 'Fertilizer',
            'slug' => 'fertilizer-' . uniqid(),
        ]);
    }

    private function product(Category $category, string $name, int $qty, int $threshold, string $status): Product
    {
        return Product::create([
            'user_id'           => $this->store->id,
            'category_id'       => $category->id,
            'category'          => 'Fertilizer',
            'name'              => $name,
            'brand'             => 'Test Brand',
            'sku'               => 'SKU-' . strtoupper(str_replace(' ', '-', $name)) . '-' . uniqid(),
            'package_size'      => 50,
            'package_unit'      => 'kg',
            'price'             => 100,
            'stock_quantity'    => $qty,
            'reorder_threshold' => $threshold,
            'recommended_season' => 'wet',
            'status'            => $status,
        ]);
    }

    /** The inventory page's view data — where the donut and cards read from. */
    private function inventoryData(array $query = []): array
    {
        $response = $this->actingAs($this->store)->get(route('store.inventory.index', $query));

        $response->assertOk();

        return $response->original->getData();
    }
}
