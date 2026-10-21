<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Quotation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;
use Tests\TestCase;

/**
 * The notification panel behind the header bell.
 *
 * Three things are guarded here:
 *
 *   1. The bell opens a panel, not a page. The rows come from the same
 *      service the notifications page uses, so the two cannot drift apart.
 *   2. A panel is a notification list, not an activity log. It carries
 *      events, information that reaches you from another user, and the
 *      decisions made about your own requests - never a record of what
 *      you did.
 *   3. Reading from the panel is reading: the badge and the rows follow.
 */
class NotificationPanelTest extends TestCase
{
    use RefreshDatabase;

    private User $farmer;

    private User $store;

    private User $admin;

    protected function setUp(): void
    {
        parent::setUp();

        $this->farmer = $this->makeUser('farmer', 'Ana Farmer');
        $this->store = $this->makeUser('store_owner', 'Oakridge Store');
        $this->admin = $this->makeUser('admin', 'Chairperson');
    }

    // ────────────────────────────────────────────────────────────
    //  1. The bell opens a panel
    // ────────────────────────────────────────────────────────────

    public function test_the_bell_ships_a_panel_that_reads_the_same_rows(): void
    {
        $this->actingAs($this->store);

        $panel = $this->getJson(route('store.notifications.index'));

        $panel->assertOk();
        $panel->assertJsonStructure([
            'items',
            'unread',
            'read_all_url',
            'all_url',
        ]);
        $panel->assertJsonPath('unread', 0);
        $panel->assertJsonPath('items', []);
    }

    public function test_the_panel_is_marked_up_in_the_layout_next_to_the_bell(): void
    {
        $this->actingAs($this->store)
            ->get(route('store.dashboard'))
            ->assertOk()
            ->assertSee('id="notifModal"', false)
            ->assertSee('id="notifModalBody"', false)
            ->assertSee('id="notifReadAll"', false);
    }

    public function test_the_panel_shows_events_and_information_from_other_users(): void
    {
        $quotation = $this->makeQuotation($this->farmer);

        $this->actingAs($this->farmer);
        \App\Services\ActivityLogger::quotationCreated($quotation);

        $panel = $this->actingAs($this->store)
            ->getJson(route('store.notifications.index'))
            ->assertOk();

        $this->assertCount(1, $panel->json('items'));
        $this->assertSame(1, $panel->json('unread'));
        $this->assertStringContainsString('includes your products', $panel->json('items.0.text'));
        $this->assertNotNull($panel->json('items.0.read_url'));
    }

    // ────────────────────────────────────────────────────────────
    //  2. A panel is not an activity log
    // ────────────────────────────────────────────────────────────

    public function test_a_farmer_does_not_see_their_own_actions_in_the_panel(): void
    {
        $quotation = $this->makeQuotation($this->farmer);

        // The farmer creates and then deletes their own estimate. The
        // withdrawal takes every trace of it with it - the estimate, the
        // notice, the log lines - so nothing is left pointing at a row
        // that no longer exists.
        $this->actingAs($this->farmer);
        \App\Services\ActivityLogger::quotationCreated($quotation);

        $this->actingAs($this->farmer)
            ->delete(route('farmer.quotations.destroy', $quotation))
            ->assertRedirect(route('farmer.quotations.index'));

        $panel = $this->actingAs($this->farmer)
            ->getJson(route('farmer.notifications.index'))
            ->assertOk();

        $this->assertSame([], $panel->json('items'));
        $this->assertSame(0, $panel->json('unread'));

        // Nothing is left anywhere that points at the estimate.
        $this->assertNull(Quotation::find($quotation->id));
        $this->assertFalse(
            ActivityLog::where('subject_type', Quotation::class)
                ->where('subject_id', $quotation->id)
                ->exists()
        );
    }

    public function test_a_withdrawn_estimate_does_not_reach_the_store_panel(): void
    {
        $quotation = $this->makeQuotation($this->farmer);

        $this->actingAs($this->farmer);
        \App\Services\ActivityLogger::quotationCreated($quotation);

        $this->actingAs($this->farmer)
            ->delete(route('farmer.quotations.destroy', $quotation))
            ->assertRedirect();

        $panel = $this->actingAs($this->store)
            ->getJson(route('store.notifications.index'))
            ->assertOk();

        $this->assertSame([], $panel->json('items'));
        $this->assertSame(0, $panel->json('unread'));

        // And the store's own demand page does not show it either.
        $this->actingAs($this->store)
            ->get(route('store.quotations.index'))
            ->assertOk()
            ->assertDontSee($quotation->quotation_number, false);
    }

    public function test_a_quotation_notice_stays_for_three_days_and_then_leaves(): void
    {
        $quotation = $this->makeQuotation($this->farmer);

        $this->actingAs($this->farmer);
        \App\Services\ActivityLogger::quotationCreated($quotation);

        // Fresh: the notice is in the list.
        $this->assertCount(1, ActivityLog::forUser($this->store->id)->storeNotifications()->get());

        // Two days old: still there.
        ActivityLog::forUser($this->store->id)->storeNotifications()
            ->update(['created_at' => now()->subDays(2)]);
        $this->assertCount(1, ActivityLog::forUser($this->store->id)->storeNotifications()->get());

        // Four days old: gone from the list, and from the badge.
        ActivityLog::forUser($this->store->id)->storeNotifications()
            ->update(['created_at' => now()->subDays(4)]);
        $this->assertCount(0, ActivityLog::forUser($this->store->id)->storeNotifications()->get());

        $panel = $this->actingAs($this->store)
            ->getJson(route('store.notifications.index'))
            ->assertOk();

        $this->assertSame([], $panel->json('items'));
        $this->assertSame(0, $panel->json('unread'));

        // The estimate itself is untouched - the full history lives on the
        // demand page, which is where the store reads it.
        $this->assertNotNull(Quotation::find($quotation->id));
    }

    public function test_a_sign_in_is_never_a_notification(): void
    {        $this->actingAs($this->farmer);
        \App\Services\ActivityLogger::userLoggedIn($this->farmer, 'email');

        foreach (['farmer', 'store', 'admin'] as $prefix) {
            $panel = $this->actingAs($this->{$this->roleFor($prefix)})
                ->getJson(route($prefix . '.notifications.index'))
                ->assertOk();

            $this->assertSame([], $panel->json('items'), "A sign-in reached the {$prefix} panel.");
        }
    }

    // ────────────────────────────────────────────────────────────
    //  3. Reading from the panel is reading
    // ────────────────────────────────────────────────────────────

    public function test_marking_one_read_from_the_panel_returns_the_new_count(): void
    {
        $quotation = $this->makeQuotation($this->farmer);

        $this->actingAs($this->farmer);
        \App\Services\ActivityLogger::quotationCreated($quotation);

        $row = ActivityLog::forUser($this->store->id)->storeNotifications()->sole();

        $response = $this->actingAs($this->store)
            ->postJson(route('store.notifications.read', $row));

        $response->assertOk();
        $response->assertJson(['ok' => true, 'unread' => 0]);
        $this->assertNotNull($row->fresh()->read_at);
    }

    public function test_marking_all_read_from_the_panel_clears_the_badge_count(): void
    {
        $quotation = $this->makeQuotation($this->farmer);

        $this->actingAs($this->farmer);
        \App\Services\ActivityLogger::quotationCreated($quotation);

        $response = $this->actingAs($this->store)
            ->postJson(route('store.notifications.read-all'));

        $response->assertOk();
        $response->assertJson(['ok' => true, 'unread' => 0]);
        $this->assertSame(0, ActivityLog::forUser($this->store->id)->storeNotifications()->unread()->count());
    }

    public function test_one_member_cannot_mark_another_members_notification_read(): void
    {
        $quotation = $this->makeQuotation($this->farmer);

        $this->actingAs($this->farmer);
        \App\Services\ActivityLogger::quotationCreated($quotation);

        $row = ActivityLog::forUser($this->store->id)->storeNotifications()->sole();

        $this->actingAs($this->admin)
            ->postJson(route('store.notifications.read', $row))
            ->assertStatus(403);
    }

    // ────────────────────────────────────────────────────────────
    //  Helpers
    // ────────────────────────────────────────────────────────────

    private function roleFor(string $prefix): string
    {
        return match ($prefix) {
            'farmer' => 'farmer',
            'store' => 'store',
            default => 'admin',
        };
    }

    private function makeUser(string $role, string $name): User
    {
        return User::create([
            'name'       => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name'  => explode(' ', $name)[1] ?? 'User',
            'email'      => str($role . '.' . uniqid() . '@example.test')->lower()->value(),
            'role'       => $role,
            'password'   => 'password123',
        ]);
    }

    private function makeQuotation(User $farmer)
    {
        $store = $this->store;

        $product = \App\Models\Product::create([
            'user_id'           => $store->id,
            'sku'               => 'SKU-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'name'              => 'Premium Urea 46-0-0',
            'category'          => 'Fertilizers',
            'price'             => 1450.00,
            'stock_quantity'    => 40,
            'reorder_threshold' => 5,
            'status'            => 'active',
        ]);

        $quotation = \App\Models\Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-' . strtoupper(substr(md5(uniqid('', true)), 0, 8)),
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.0,
            'estimated_total_cost' => 300.00,
            'status'               => \App\Models\Quotation::STATUS_COMPUTED,
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
}
