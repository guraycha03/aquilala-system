<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Category;
use App\Models\Crop;
use App\Models\FarmerCrop;
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
 *   farmer      — quotation + planting-decision rows (existing behaviour)
 *   store_owner — rows fanned out for quotations involving their products
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
        $this->actingAs($this->farmer);
        ActivityLogger::quotationCreated($quotation);

        $storeRows = ActivityLog::forUser($this->store->id)->storeNotifications()->get();
        $this->assertCount(1, $storeRows);
        $this->assertNull($storeRows->first()->read_at);
        $this->assertStringContainsString('includes your products', $storeRows->first()->description);

        // The farmer keeps their own row and the admin gets one too.
        $this->assertSame(1, ActivityLog::forUser($this->farmer->id)->farmerNotifications()->count());
        $this->assertSame(1, ActivityLog::forUser($this->admin->id)->adminNotifications()->count());
    }

    public function test_the_store_notifications_page_lists_and_clears_unread_rows(): void
    {
        $quotation = $this->makeQuotation($this->farmer);
        $this->actingAs($this->farmer);
        $this->actingAs($this->farmer);
        ActivityLogger::quotationCreated($quotation);

        $this->actingAs($this->store)
            ->get(route('store.notifications.index'))
            ->assertOk()
            ->assertSee('QT-NOTIF-TEST')
            ->assertSee('<b>1</b> unread', false);

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

    public function test_deleting_a_quotation_notifies_the_involved_store(): void
    {
        $quotation = $this->makeQuotation($this->farmer);

        $this->actingAs($this->farmer)
            ->delete(route('farmer.quotations.destroy', $quotation))
            ->assertRedirect(route('farmer.quotations.index'));

        $row = ActivityLog::forUser($this->store->id)->storeNotifications()->first();
        $this->assertNotNull($row);
        $this->assertSame(ActivityLog::TYPE_QUOTATION_DELETED, $row->type);
    }

    public function test_a_store_owner_cannot_mark_another_stores_notification_read(): void
    {
        $quotation = $this->makeQuotation($this->farmer);
        $this->actingAs($this->farmer);
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

    // ── Farmer regression ────────────────────────────────────────────────

    public function test_the_farmer_bell_still_uses_the_shared_view(): void
    {
        $quotation = $this->makeQuotation($this->farmer);
        $this->actingAs($this->farmer);
        $this->actingAs($this->farmer);
        ActivityLogger::quotationCreated($quotation);

        $this->actingAs($this->farmer)
            ->get(route('farmer.notifications.index'))
            ->assertOk()
            ->assertSee('Generated cost estimate')
            ->assertSee('<b>1</b> unread', false)
            ->assertSee(route('farmer.notifications.read-all'), false);
    }
}
