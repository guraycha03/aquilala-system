<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\DecisionFormula;
use App\Models\Product;
use App\Models\User;
use App\Services\ActivityLogger;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StoreActivityLoggingTest extends TestCase
{
    use RefreshDatabase;

    private User $store;

    private Category $category;

    private Product $product;

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

        $this->product = Product::create([
            'user_id'           => $this->store->id,
            'category_id'       => $this->category->id,
            'category'          => 'Fertilizer',
            'name'              => 'Urea Fertilizer (46-0-0)',
            'sku'               => 'SKU-' . uniqid(),
            'price'             => 100,
            'stock_quantity'    => 10,
            'reorder_threshold' => 2,
            'recommended_season' => 'both',
            'status'            => 'active',
        ]);
    }

    private function formulaPayload(array $overrides = []): array
    {
        return array_merge([
            'crop_type'        => 'Rice',
            'season'           => 'wet',
            'product_id'       => $this->product->id,
            'rate_per_hectare' => 12.5,
            'unit'             => 'Kilograms',
        ], $overrides);
    }

    public function test_formula_creation_is_logged_in_the_store_activity_log(): void
    {
        $this->actingAs($this->store)
            ->post(route('store.formulas.store'), $this->formulaPayload())
            ->assertRedirect(route('store.formulas.index'));

        $this->assertDatabaseHas('activity_logs', [
            'user_id' => $this->store->id,
            'type'    => ActivityLog::TYPE_FORMULA_CREATED,
        ]);
    }

    public function test_formula_update_logs_the_field_that_changed(): void
    {
        $formula = DecisionFormula::create($this->formulaPayload());

        $this->actingAs($this->store)
            ->put(route('store.formulas.update', $formula), $this->formulaPayload([
                'rate_per_hectare' => 20,
            ]))
            ->assertRedirect(route('store.formulas.index'));

        $log = ActivityLog::where('user_id', $this->store->id)
            ->where('type', ActivityLog::TYPE_FORMULA_UPDATED)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('rate', $log->description);
        $this->assertStringContainsString('12.50', $log->description);
        $this->assertStringContainsString('20', $log->description);
    }

    public function test_a_no_op_formula_update_is_not_logged(): void
    {
        $formula = DecisionFormula::create($this->formulaPayload());

        $this->actingAs($this->store)
            ->put(route('store.formulas.update', $formula), $this->formulaPayload());

        $this->assertDatabaseMissing('activity_logs', [
            'user_id' => $this->store->id,
            'type'    => ActivityLog::TYPE_FORMULA_UPDATED,
        ]);
    }

    public function test_formula_deletion_is_logged_in_the_store_activity_log(): void
    {
        $formula = DecisionFormula::create($this->formulaPayload());

        $this->actingAs($this->store)
            ->delete(route('store.formulas.destroy', $formula))
            ->assertRedirect(route('store.formulas.index'));

        $log = ActivityLog::where('user_id', $this->store->id)
            ->where('type', ActivityLog::TYPE_FORMULA_DELETED)
            ->latest('id')
            ->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Rice', $log->description);
        $this->assertStringContainsString($this->product->name, $log->description);
    }

    /**
     * The store history is a work log: sign-in rows stay on the admin audit
     * trail, and the stats row counts product edits instead of logins.
     */
    public function test_the_store_activity_page_hides_sign_ins_and_counts_product_updates(): void
    {
        ActivityLogger::userLoggedIn($this->store, 'email');

        $this->actingAs($this->store)
            ->post(route('store.inventory.update', $this->product), [
                'stock_quantity'    => 55,
                'reorder_threshold' => 4,
            ]);

        $this->actingAs($this->store)
            ->get(route('store.activity.index'))
            ->assertOk()
            ->assertSee('Products Updated')
            ->assertSee('Updated product', false)
            ->assertDontSee('Signed in successfully');
    }
}
