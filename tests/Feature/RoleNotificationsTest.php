<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\Product;
use App\Models\Quotation;
use App\Models\User;
use App\Services\ActivityLogger;
use App\Services\CropLibraryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The header bell renders for every role, each pointing at its own
 * dedicated notifications page (shared view) with an unread badge fed by
 * that role's ActivityLog scope:
 *
 *   farmer      — important notices only, i.e. activity incoming FROM
 *                 other users: the admin's decision on their planting
 *                 request (confirmed/rejected) and association events.
 *                 Their own actions (planting batches, harvest reports,
 *                 quotations) are history — Settings → Activity.
 *   store_owner — news arriving FROM other people: anonymous notices for
 *                 quotations involving their products (no farmer name, no
 *                 actor, ever) plus the association's calendar events
 *   admin       — rows fanned out for quotations, planting requests and
 *                 harvest reports across the association
 */
class RoleNotificationsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $farmer;
    private User $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser('System Admin', 'admin');
        $this->farmer = $this->makeUser('Ana Farmer', 'farmer');
        $this->store = $this->makeUser('Green Valley Store', 'store_owner');
    }

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '_', $name)).'_'.uniqid().'@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    private function makeProduct(): Product
    {
        $category = Category::create([
            'name' => 'Fertilizers',
            'slug' => 'fertilizers-'.uniqid(),
            'description' => 'Fertilizer inputs',
        ]);

        return Product::create([
            'user_id'           => $this->store->id,
            'category_id'       => $category->id,
            'category'          => $category->name,
            'sku'               => 'SKU-NOTIF-'.uniqid(),
            'name'              => 'Premium Urea',
            'price'             => 100,
            'price_unit'        => 'sack',
            'stock_quantity'    => 20,
            'stock_unit'        => 'sacks',
            'reorder_threshold' => 5,
            'status'            => 'active',
        ]);
    }

    private function makeQuotation(User $farmer): Quotation
    {
        $product = $this->makeProduct();

        $quotation = Quotation::create([
            'user_id'              => $farmer->id,
            'quotation_number'     => 'QT-NOTIF-TEST',
            'crop_type'            => 'Rice',
            'season'               => 'wet',
            'land_area'            => 1.5,
            'estimated_total_cost' => 300.00,
            'status'               => Quotation::STATUS_COMPUTED,
            'valid_until'          => now()->addDays(14),
            'notes'                => null,
        ]);

        $quotation->items()->create([
            'product_id'          => $product->id,
            'product_name'        => $product->name,
            'price_at_generation' => 100,
            'calculated_quantity' => 3,
            'subtotal'            => 300,
        ]);

        return $quotation;
    }

    // ── The bell ─────────────────────────────────────────────────────────

    public function test_every_role_sees_the_bell_on_the_header(): void
    {
        $cases = [
            [$this->farmer, route('farmer.activity.index'), route('farmer.notifications.index')],
            [$this->store, route('store.activity.index'), route('store.notifications.index')],
            [$this->admin, route('admin.activity.index'), route('admin.notifications.index')],
        ];

        foreach ($cases as [$user, $page, $bellTarget]) {
            $this->actingAs($user)
                ->get($page)
                ->assertOk()
                ->assertSee('id="desktopNotifBtn"', false)
                ->assertSee($bellTarget, false);
        }
    }

    public function test_guests_do_not_get_a_bell(): void
    {
        $this->get(route('login'))
            ->assertOk()
            ->assertDontSee('id="desktopNotifBtn"', false);
    }

    // ── Store owner ──────────────────────────────────────────────────────

    public function test_a_quotation_fans_out_a_notification_to_the_involved_store(): void
    {
        $quotation = $this->makeQuotation($this->farmer);

        // Drive the shortcut the controller calls after a successful create.
        $this->actingAs($this->farmer);
        ActivityLogger::quotationCreated($quotation);

        $storeRows = ActivityLog::forUser($this->store->id)->storeNotifications()->get();
        $this->assertCount(1, $storeRows);
        $this->assertNull($storeRows->first()->read_at);
        $this->assertStringContainsString('includes your products', $storeRows->first()->description);

        // Strictly anonymous: no actor is recorded and the farmer's name
        // never appears in the store's copy.
        $this->assertNull($storeRows->first()->actor_id);
        $this->assertStringNotContainsString('Ana Farmer', $storeRows->first()->description);

        // The farmer's own quotation row is history — it no longer surfaces
        // in their notifications. The admin still gets a named row.
        $this->assertSame(0, ActivityLog::forUser($this->farmer->id)->farmerNotifications()->count());
        $this->assertSame(1, ActivityLog::forUser($this->farmer->id)->where('type', ActivityLog::TYPE_QUOTATION_CREATED)->count());
        $this->assertSame(1, ActivityLog::forUser($this->admin->id)->adminNotifications()->count());
    }

    public function test_the_store_notifications_page_lists_and_clears_unread_rows(): void
    {
        $quotation = $this->makeQuotation($this->farmer);
        $this->actingAs($this->farmer);
        ActivityLogger::quotationCreated($quotation);

        $this->actingAs($this->store)
            ->get(route('store.notifications.index'))
            ->assertOk()
            ->assertSee('QT-NOTIF-TEST')
            ->assertSee('<b>1</b> unread', false)
            // Strictly anonymous: the generating farmer is never on the page.
            ->assertDontSee('Ana Farmer');

        // Badge on the dashboard header while unread.
        $this->actingAs($this->store)
            ->get(route('store.dashboard'))
            ->assertOk()
            ->assertSee('id="desktopNotifBadge"', false);

        $this->actingAs($this->store)
            ->post(route('store.notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, ActivityLog::forUser($this->store->id)->storeNotifications()->unread()->count());

        $this->actingAs($this->store)
            ->get(route('store.dashboard'))
            ->assertOk()
            ->assertDontSee('id="desktopNotifBadge"', false);
    }

    public function test_deleting_a_quotation_removes_every_trace_of_it(): void
    {
        $quotation = $this->makeQuotation($this->farmer);

        $this->actingAs($this->farmer);
        ActivityLogger::quotationCreated($quotation);

        $this->actingAs($this->farmer)
            ->delete(route('farmer.quotations.destroy', $quotation))
            ->assertRedirect(route('farmer.quotations.index'));

        // A withdrawn estimate leaves nothing behind: not a notice for the
        // store, not a line in anyone's history, and not the estimate.
        $this->assertSame(0, ActivityLog::forUser($this->store->id)->storeNotifications()->count());
        $this->assertSame(0, ActivityLog::forUser($this->farmer->id)->count());
        $this->assertSame(0, ActivityLog::forUser($this->admin->id)->count());
        $this->assertNull(Quotation::find($quotation->id));
    }

    public function test_a_store_owner_cannot_mark_another_stores_notification_read(): void
    {
        $quotation = $this->makeQuotation($this->farmer);
        $this->actingAs($this->farmer);
        ActivityLogger::quotationCreated($quotation);

        $otherStore = $this->makeUser('Other Store', 'store_owner');
        $foreignRow = ActivityLog::forUser($this->store->id)->storeNotifications()->sole();

        $this->actingAs($otherStore)
            ->post(route('store.notifications.read', $foreignRow))
            ->assertStatus(403);
    }

    // ── Admin ────────────────────────────────────────────────────────────

    public function test_a_new_planting_request_notifies_every_admin(): void
    {
        $rice = Crop::create(['name' => 'Rice', 'category' => 'Cereal', 'description' => 'Test rice']);
        $otherAdmin = $this->makeUser('Second Admin', 'admin');

        $this->actingAs($this->farmer)->post(route('farmer.crops.store'), [
            'crop_id' => $rice->id,
            'variety' => CropLibraryService::GENERAL_VARIETY,
            'land_area' => 1.0,
            'land_area_unit' => 'ha',
            'planting_date' => now()->subDays(2)->format('Y-m-d'),
            'season' => 'wet',
        ])->assertRedirect(route('farmer.crops.index'));

        foreach ([$this->admin, $otherAdmin] as $admin) {
            $row = ActivityLog::forUser($admin->id)->adminNotifications()->sole();
            $this->assertSame(ActivityLog::TYPE_CROP_PLANTED, $row->type);
            $this->assertStringContainsString('New planting request from', $row->description);
            $this->assertNull($row->read_at);
        }

        $this->actingAs($this->admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('New planting request from')
            ->assertSee('<b>1</b> unread', false);

        $this->actingAs($this->admin)
            ->post(route('admin.notifications.read-all'))
            ->assertRedirect();

        $this->assertSame(0, ActivityLog::forUser($this->admin->id)->adminNotifications()->unread()->count());
        // One admin's badge clearing must not touch the other's rows.
        $this->assertSame(1, ActivityLog::forUser($otherAdmin->id)->adminNotifications()->unread()->count());
    }

    public function test_admin_notifications_render_for_an_empty_inbox(): void
    {
        $this->actingAs($this->admin)
            ->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee('No Notifications Yet');
    }

    // ── Farmer scope ────────────────────────────────────────────────────

    public function test_the_farmer_bell_uses_the_shared_view_for_important_notices(): void
    {
        $rice = Crop::create(['name' => 'Rice', 'category' => 'Cereal', 'description' => 'Test rice']);

        $this->actingAs($this->farmer)->post(route('farmer.crops.store'), [
            'crop_id' => $rice->id,
            'variety' => CropLibraryService::GENERAL_VARIETY,
            'land_area' => 1.0,
            'land_area_unit' => 'ha',
            'planting_date' => now()->subDays(2)->format('Y-m-d'),
            'season' => 'wet',
        ])->assertRedirect(route('farmer.crops.index'));

        $farmerCrop = \App\Models\FarmerCrop::sole();

        // The request echo is the farmer's own action — it stays out of the
        // bell (it lives in Activity history). The bell shows the decision.
        $this->actingAs($this->farmer)
            ->get(route('farmer.notifications.index'))
            ->assertOk()
            ->assertDontSee('Planted Rice')
            ->assertSee('No Notifications Yet');

        $this->actingAs($this->admin)
            ->post(route('admin.plantings.confirm', $farmerCrop))
            ->assertRedirect();

        $this->actingAs($this->farmer)
            ->get(route('farmer.notifications.index'))
            ->assertOk()
            ->assertSee('Confirmed planting of Rice')
            ->assertSee('<b>1</b> unread', false)
            ->assertSee(route('farmer.notifications.read-all'), false);
    }

    public function test_a_farmers_own_planted_rows_stay_out_of_their_notifications(): void
    {
        // Same rule as quotations: submitting a planting request is the
        // farmer's own action — history under Settings, not a bell notice.
        $rice = Crop::create(['name' => 'Rice', 'category' => 'Cereal', 'description' => 'Test rice']);

        $this->actingAs($this->farmer)->post(route('farmer.crops.store'), [
            'crop_id' => $rice->id,
            'variety' => CropLibraryService::GENERAL_VARIETY,
            'land_area' => 1.0,
            'land_area_unit' => 'ha',
            'planting_date' => now()->subDays(2)->format('Y-m-d'),
            'season' => 'wet',
        ])->assertRedirect(route('farmer.crops.index'));

        $this->assertSame(0, ActivityLog::forUser($this->farmer->id)->farmerNotifications()->count());
        // The row still exists for their activity history.
        $this->assertSame(1, ActivityLog::forUser($this->farmer->id)->where('type', ActivityLog::TYPE_CROP_PLANTED)->count());
    }

    public function test_a_farmers_own_quotation_rows_stay_out_of_their_notifications(): void
    {
        // Important notices only: generating a quotation is the farmer's own
        // action — it belongs to Activity history under Settings, not the bell.
        $quotation = $this->makeQuotation($this->farmer);
        $this->actingAs($this->farmer);
        ActivityLogger::quotationCreated($quotation);

        $this->actingAs($this->farmer)
            ->get(route('farmer.notifications.index'))
            ->assertOk()
            ->assertSee('No Notifications Yet');

        // The row still exists for their activity history.
        $this->assertSame(1, ActivityLog::forUser($this->farmer->id)->where('type', ActivityLog::TYPE_QUOTATION_CREATED)->count());
    }

    public function test_a_farmers_own_harvest_rows_stay_out_of_their_notifications(): void
    {
        // Notifications carry activity incoming from OTHER users. Filing a
        // harvest report is the farmer's own action, and no admin ever acts
        // on reports (admin harvest pages are read-only), so no outcome can
        // come back to them — the row is history under Settings. The admin
        // copy ("New harvest report from …") is the notice that matters.
        $report = HarvestReport::create([
            'user_id' => $this->farmer->id,
            'crop_type' => 'Rice',
            'season' => 'wet',
            'land_area' => 1.0,
            'actual_yield_metric_tons' => 4.2,
            'actual_total_expenses' => 20000,
            'harvest_date' => now()->subDays(2)->toDateString(),
        ]);

        $this->actingAs($this->farmer);
        ActivityLogger::harvestReportCreated($report);

        $this->actingAs($this->farmer)
            ->get(route('farmer.notifications.index'))
            ->assertOk()
            ->assertDontSee('Recorded harvest of')
            ->assertSee('No Notifications Yet');

        // The row still exists for their activity history.
        $this->assertSame(1, ActivityLog::forUser($this->farmer->id)->where('type', ActivityLog::TYPE_HARVEST_CREATED)->count());

        // The admin side of the same event stays a notice: it is a farmer's
        // submission arriving at the admin — data from another user.
        $adminRow = ActivityLog::forUser($this->admin->id)->adminNotifications()
            ->ofType(ActivityLog::TYPE_HARVEST_CREATED)->sole();
        $this->assertStringContainsString('New harvest report from', $adminRow->description);
    }

    public function test_association_events_reach_every_farmers_bell(): void
    {
        $otherFarmer = $this->makeUser('Ben Farmer', 'farmer');

        $this->actingAs($this->admin)->post(route('admin.calendar.events.store'), [
            'title' => 'Free Corn Seeds Distribution',
            'event_type' => 'free_input',
            'event_date' => now()->addDays(3)->toDateString(),
            'location' => 'Association Warehouse',
            'description' => 'Hybrid seeds for members.',
        ])->assertSessionHas('success');

        foreach ([$this->farmer, $otherFarmer] as $farmer) {
            $row = ActivityLog::forUser($farmer->id)->farmerNotifications()
                ->ofType(ActivityLog::TYPE_EVENT_CREATED)->sole();
            $this->assertStringContainsString('Upcoming event', $row->description);
            $this->assertStringContainsString('Free Corn Seeds Distribution', $row->description);
            $this->assertNull($row->read_at);
        }

        // Store owners are not association members. The association's
        // calendar is not theirs, so no event notice reaches their bell.
        $this->assertSame(
            0,
            ActivityLog::forUser($this->store->id)->storeNotifications()
                ->ofType(ActivityLog::TYPE_EVENT_CREATED)->count()
        );
    }

    public function test_event_notices_reflect_the_events_live_status_not_stale_upcoming(): void
    {
        // Three events covering every state: finished yesterday, an all-day
        // event happening today, and one still days ahead. The logger bakes
        // the event's status INTO the row and push at creation time ("Past
        // event" / "Happening now" / "Upcoming event"), and the pages rewrite
        // the prefix again whenever they render — so a notice can never keep
        // calling its event upcoming once the date has passed.
        $past = \App\Models\AssociationEvent::create([
            'title'      => 'Seed Distribution Wrap-up',
            'event_type' => 'free_input',
            'event_date' => now()->subDay()->toDateString(),
            'location'   => 'Association Warehouse',
            'created_by' => $this->admin->id,
        ]);
        $today = \App\Models\AssociationEvent::create([
            'title'      => 'Water Scheduling Today',
            'event_type' => 'water_schedule',
            'event_date' => now()->toDateString(),
            'location'   => 'Canal Gate 2',
            'created_by' => $this->admin->id,
        ]);
        $future = \App\Models\AssociationEvent::create([
            'title'      => 'General Assembly Planning',
            'event_type' => 'meeting',
            'event_date' => now()->addDays(3)->toDateString(),
            'location'   => 'Association Office',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin);
        ActivityLogger::eventCreated($past);
        ActivityLogger::eventCreated($today);
        ActivityLogger::eventCreated($future);

        $rowFor = fn ($event) => ActivityLog::forUser($this->farmer->id)
            ->ofType(ActivityLog::TYPE_EVENT_CREATED)
            ->where('subject_id', $event->id)
            ->sole();

        $pastRow   = $rowFor($past);
        $todayRow  = $rowFor($today);
        $futureRow = $rowFor($future);

        // Each row knows the event's state without ever storing it.
        $this->assertSame('done', $pastRow->event_state);
        $this->assertSame('Done', $pastRow->event_state_label);
        $this->assertSame('ongoing', $todayRow->event_state);
        $this->assertSame('Happening now', $todayRow->event_state_label);
        $this->assertSame('upcoming', $futureRow->event_state);
        $this->assertSame('Upcoming', $futureRow->event_state_label);

        // The stored row already leads with the event's status at creation;
        // the rendered text is corrected once more so it can never go stale.
        $this->assertStringStartsWith('Past event:', $pastRow->description);
        $this->assertStringStartsWith('Happening now:', $todayRow->description);
        $this->assertStringStartsWith('Upcoming event:', $futureRow->description);
        $this->assertStringNotContainsString('Upcoming event', $pastRow->display_description);
        $this->assertStringStartsWith('Past event:', $pastRow->display_description);
        $this->assertStringStartsWith('Happening now:', $todayRow->display_description);
        $this->assertSame($futureRow->description, $futureRow->display_description);

        // A legacy row written before this fix — "Upcoming event" stored for
        // an event that has since finished — is still corrected at render.
        $pastRow->description = 'Upcoming event: "' . $past->title . '" on ' . $past->event_date->format('M d, Y');
        $this->assertStringStartsWith('Past event:', $pastRow->display_description);

        // The notification page shows a live status chip on every notice.
        $this->actingAs($this->farmer)
            ->get(route('farmer.notifications.index'))
            ->assertOk()
            ->assertSee('nf-state--done', false)
            ->assertSee('nf-state--ongoing', false)
            ->assertSee('nf-state--upcoming', false);

        // The farmer's activity history carries the same corrected text.
        $this->actingAs($this->farmer)
            ->get(route('farmer.activity.index'))
            ->assertOk()
            ->assertSee('Past event:', false);

        // A deleted event leaves its notice behind — the row must still
        // recognise the state from the metadata it stored itself.
        $past->delete();
        $this->assertSame('done', ActivityLog::find($pastRow->id)->event_state);
    }

    public function test_editing_an_event_updates_the_farmers_in_app_rows(): void
    {
        $event = \App\Models\AssociationEvent::create([
            'title' => 'General Assembly',
            'event_type' => 'meeting',
            'event_date' => now()->addDays(7)->toDateString(),
            'location' => 'Association Office',
            'created_by' => $this->admin->id,
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.calendar.events.update', $event), [
                'title' => 'General Assembly',
                'event_type' => 'meeting',
                'event_date' => now()->addDays(10)->toDateString(),
                'location' => 'Association Office',
            ])
            ->assertSessionHas('success');

        $row = ActivityLog::forUser($this->farmer->id)->farmerNotifications()
            ->ofType(ActivityLog::TYPE_EVENT_UPDATED)->sole();
        $this->assertStringContainsString('Updated event', $row->description);
    }
}
