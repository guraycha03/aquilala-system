<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The activity history, and what it is allowed to show.
 *
 * Two rules:
 *
 *   1. One look, one voice. Every row wears the same tile and the same
 *      colour, and each row carries the Lucide icon of the action it
 *      describes. A wall of tinted icons says nothing once there are
 *      twenty rows; the wording does the work.
 *   2. A store's history is the store's own business. A farmer
 *      withdrawing an estimate is the farmer's business, and the store
 *      never learns about it.
 */
class ActivityHistoryTest extends TestCase
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

    public function test_every_activity_row_wears_the_same_tile_and_colour(): void
    {
        $store = $this->makeUser('Oakridge Store', 'store_owner');

        ActivityLog::create([
            'user_id' => $store->id,
            'type'    => ActivityLog::TYPE_PRODUCT_CREATED,
            'description' => 'Added product: Urea',
        ]);
        ActivityLog::create([
            'user_id' => $store->id,
            'type'    => ActivityLog::TYPE_PRODUCT_DELETED,
            'description' => 'Removed product: Urea',
        ]);
        ActivityLog::create([
            'user_id' => $store->id,
            'type'    => ActivityLog::TYPE_EVENT_CREATED,
            'description' => 'Added calendar event',
        ]);

        // One tile, one colour, for every row.
        $row = new ActivityLog(['type' => ActivityLog::TYPE_PRODUCT_CREATED]);
        $this->assertSame('#EDF1E9', $row->bg);
        $this->assertSame('#4A7A54', $row->color);

        $page = $this->actingAs($store)->get(route('store.activity.index'))->assertOk();

        // The rows carry the shared tile, and the icon is a Lucide one
        // (the page hands the name to Lucide, which draws the glyph).
        $page->assertSee('background: #EDF1E9', false);
        $page->assertSee('data-lucide="package-plus"', false);
        $page->assertSee('data-lucide="package-x"', false);

        // An event belongs to the members, so its icon is on the farmer's
        // history and not on the store's.
        $farmer = $this->makeUser('Juan Cruz', 'farmer');

        ActivityLog::create([
            'user_id' => $farmer->id,
            'type'    => ActivityLog::TYPE_EVENT_CREATED,
            'description' => 'Upcoming event: "Water" on Oct 21, 2026',
        ]);

        $this->actingAs($farmer)->get(route('farmer.activity.index'))
            ->assertOk()
            ->assertSee('data-lucide="calendar-plus"', false);
    }

    public function test_each_activity_type_has_its_own_icon(): void
    {
        $icons = [
            ActivityLog::TYPE_QUOTATION_CREATED => 'file-plus',
            ActivityLog::TYPE_QUOTATION_DELETED => 'file-x',
            ActivityLog::TYPE_CROP_PLANTED       => 'sprout',
            ActivityLog::TYPE_CROP_CONFIRMED     => 'circle-check',
            ActivityLog::TYPE_PRODUCT_CREATED    => 'package-plus',
            ActivityLog::TYPE_PRODUCT_DELETED    => 'package-x',
            ActivityLog::TYPE_FORMULA_CREATED    => 'calculator',
            ActivityLog::TYPE_EVENT_CREATED      => 'calendar-plus',
            ActivityLog::TYPE_LOGIN              => 'log-in',
            ActivityLog::TYPE_LOGOUT             => 'log-out',
        ];

        foreach ($icons as $type => $icon) {
            $row = new ActivityLog(['type' => $type]);
            $this->assertSame($icon, $row->icon, "The {$type} row has the wrong icon.");
        }
    }

    public function test_a_store_never_sees_that_a_farmer_withdrew_an_estimate(): void
    {
        $store = $this->makeUser('Oakridge Store', 'store_owner');
        $farmer = $this->makeUser('Juan Cruz', 'farmer');

        // The farmer's own record of the withdrawal.
        ActivityLog::create([
            'user_id' => $store->id,
            'actor_id' => $farmer->id,
            'type'    => ActivityLog::TYPE_QUOTATION_DELETED,
            'description' => 'Cost estimate QT-1 that included your products was deleted',
        ]);

        $page = $this->actingAs($store)->get(route('store.activity.index'))->assertOk();

        $page->assertDontSee('was deleted', false);

        // The filter dropdown does not offer it either.
        $page->assertDontSee('quotation.deleted', false);
    }

    public function test_a_store_never_sees_an_association_event(): void
    {
        $store = $this->makeUser('Oakridge Store', 'store_owner');
        $admin = $this->makeUser('Chairperson', 'admin');

        // The event notice a store used to be sent.
        ActivityLog::create([
            'user_id' => $store->id,
            'actor_id' => $admin->id,
            'type'    => ActivityLog::TYPE_EVENT_CREATED,
            'description' => 'Upcoming event: "Water" on Oct 21, 2026 at 11:17 AM',
        ]);

        $page = $this->actingAs($store)->get(route('store.activity.index'))->assertOk();

        // A store owner is not a member of the association: no event, and
        // no offer to filter by one.
        $page->assertDontSee('Upcoming event', false);
        $page->assertDontSee('event.created', false);
    }

    public function test_a_farmer_still_sees_their_own_withdrawal(): void
    {
        $farmer = $this->makeUser('Juan Cruz', 'farmer');

        ActivityLog::create([
            'user_id' => $farmer->id,
            'type'    => ActivityLog::TYPE_QUOTATION_DELETED,
            'description' => 'Deleted cost estimate QT-1 for Rice (wet)',
        ]);

        $this->actingAs($farmer)
            ->get(route('farmer.activity.index'))
            ->assertOk()
            ->assertSee('Deleted cost estimate QT-1', false);
    }
}
