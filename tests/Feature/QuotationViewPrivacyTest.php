<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Looking at a price is not an activity.
 *
 * A store opening a farmer's estimate to read what it costs used to be
 * written to the activity history, and the farmer's own page counted how
 * many stores had looked. Checking what an input costs is not a deal and
 * not news, and a farmer who feels watched stops pricing.
 */
class QuotationViewPrivacyTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '.', $name)).'_'.uniqid().'@example.test',
            'role' => $role,
            'status' => User::STATUS_ACTIVE,
            'password' => 'password123',
        ]);
    }

    private function makeQuotation(User $store, User $farmer): Quotation
    {
        $product = Product::create([
            'user_id'           => $store->id,
            'sku'               => 'SKU-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'name'              => 'Premium Urea 46-0-0',
            'category'          => 'Fertilizers',
            'price'             => 1450.00,
            'stock_quantity'    => 40,
            'reorder_threshold' => 5,
            'status'            => 'active',
        ]);

        $quotation = Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.0,
            'estimated_total_cost' => 4350.00,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
        ]);

        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => $product->name,
            'price_at_generation' => 1450.00,
            'calculated_quantity' => 3,
            'subtotal'            => 4350.00,
        ]);

        return $quotation;
    }

    public function test_a_store_looking_at_an_estimate_records_no_activity(): void
    {
        $store = $this->makeUser('Oakridge Store', 'store_owner');
        $farmer = $this->makeUser('Juan Cruz', 'farmer');
        $quotation = $this->makeQuotation($store, $farmer);

        $this->actingAs($store)
            ->get(route('store.quotations.show', $quotation))
            ->assertOk();

        $this->assertSame(
            0,
            ActivityLog::where('type', ActivityLog::TYPE_QUOTATION_VIEWED)->count()
        );

        // The estimate's own state still changes - that is the workflow,
        // not a record of who was watching.
        $this->assertSame(Quotation::STATUS_VIEWED, $quotation->fresh()->status);
    }

    public function test_a_store_looking_at_the_same_estimate_twice_records_nothing_extra(): void
    {
        $store = $this->makeUser('Oakridge Store', 'store_owner');
        $farmer = $this->makeUser('Juan Cruz', 'farmer');
        $quotation = $this->makeQuotation($store, $farmer);

        foreach (range(1, 5) as $ignored) {
            $this->actingAs($store)->get(route('store.quotations.show', $quotation))->assertOk();
        }

        $this->assertSame(
            0,
            ActivityLog::where('type', ActivityLog::TYPE_QUOTATION_VIEWED)->count()
        );
    }

    public function test_the_farmer_page_does_not_count_stores_that_looked(): void
    {
        $store = $this->makeUser('Oakridge Store', 'store_owner');
        $farmer = $this->makeUser('Juan Cruz', 'farmer');
        $this->makeQuotation($store, $farmer);

        $page = $this->actingAs($farmer)->get(route('farmer.quotations.index'))->assertOk();

        // No card counting who looked, and no wording that names the
        // watcher.
        $page->assertDontSee('Seen by Stores', false);
        $page->assertDontSee('Viewed by Store', false);
    }

    public function test_the_viewed_status_stays_neutral(): void
    {
        // "Viewed" only ever meant "a store opened this once". The farmer
        // sees two states: the estimate is live, or it has expired.
        $this->assertSame('Active', (new Quotation(['status' => Quotation::STATUS_COMPUTED]))->status_label);
        $this->assertSame('Active', (new Quotation(['status' => Quotation::STATUS_VIEWED]))->status_label);
        $this->assertSame('Expired', (new Quotation(['status' => Quotation::STATUS_EXPIRED]))->status_label);
    }
}
