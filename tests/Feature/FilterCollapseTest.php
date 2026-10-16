<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Global filter-collapse pattern: every page that combines a search bar with
 * filter choices hides those choices behind ONE shared funnel button
 * (.btn-filter + data-fc-toggle) that reveals them via the shared
 * .filter-panel (data-fc-panel), driven by public/js/filter-collapse.js.
 * Activity pages keep their own drawer JS and therefore must NOT carry
 * data-fc-toggle (double-binding).
 */
class FilterCollapseTest extends TestCase
{
    use RefreshDatabase;

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '_', $name)) . '_' . uniqid() . '@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    public function test_the_layout_loads_the_shared_filter_collapse_engine_and_styles(): void
    {
        $farmer = $this->makeUser('Filter Farmer', 'farmer');

        $this->actingAs($farmer)
            ->get(route('dashboard.farmer'))
            ->assertOk()
            ->assertSee('js/filter-collapse.js', false)
            ->assertSee('.btn-filter {', false)
            ->assertSee('.filter-panel {', false)
            ->assertSee('.table-toolbar select {', false);
    }

    public function test_catalog_filter_bars_collapse_behind_the_shared_toggle(): void
    {
        $farmer = $this->makeUser('Catalog Farmer', 'farmer');

        foreach ([
            'farmer.products.browse',
            'farmer.top-quoted.index',
            'farmer.recommendations.index',
        ] as $route) {
            $this->actingAs($farmer)
                ->get(route($route))
                ->assertOk()
                ->assertSee('data-fc-toggle', false)
                ->assertSee('data-fc-panel', false)
                ->assertSee('data-fc-open-when="category,season,stock,min,max,sort"', false);
        }
    }

    public function test_server_filter_pages_open_automatically_for_their_url_params(): void
    {
        $admin = $this->makeUser('Filter Admin', 'admin');
        $farmer = $this->makeUser('Compare Farmer', 'farmer');

        $this->actingAs($admin)
            ->get(route('admin.audit.index'))
            ->assertOk()
            ->assertSee('data-fc-toggle', false)
            ->assertSee('data-fc-open-when="cat,period,user_id"', false);

        $this->actingAs($farmer)
            ->get(route('farmer.harvests.compare'))
            ->assertOk()
            ->assertSee('data-fc-toggle', false)
            ->assertSee('data-fc-open-when="year,season,crop,farmer,group_by"', false);

        $this->actingAs($admin)
            ->get(route('admin.activity.index'))
            ->assertOk()
            ->assertSee('data-fc-toggle', false)
            ->assertSee('data-fc-open-when="user_id,type"', false);
    }

    public function test_storefront_approvals_and_crops_directory_use_the_shared_toggle(): void
    {
        $store = $this->makeUser('Filter Store', 'store_owner');
        $admin = $this->makeUser('Approvals Admin', 'admin');
        $farmer = $this->makeUser('Directory Farmer', 'farmer');

        foreach ([
            [$store, 'store.storefront.index'],
            [$admin, 'admin.approvals.index'],
            [$farmer, 'farmer.crops-directory.index'],
        ] as [$user, $route]) {
            $this->actingAs($user)
                ->get(route($route))
                ->assertOk()
                ->assertSee('data-fc-toggle', false)
                ->assertSee('data-fc-panel', false);
        }
    }

    public function test_activity_pages_share_the_toggle_look_without_double_binding(): void
    {
        $store = $this->makeUser('Activity Store', 'store_owner');
        $farmer = $this->makeUser('Activity Farmer', 'farmer');

        foreach ([
            [$store, 'store.activity.index'],
            [$farmer, 'farmer.activity.index'],
        ] as [$user, $route]) {
            $response = $this->actingAs($user)->get(route($route));

            $response->assertOk()
                ->assertSee('btn-filter', false)
                ->assertSee('id="filterToggleBtn"', false);

            // Their drawer JS owns the click — the shared engine must stay out.
            $response->assertDontSee('data-fc-toggle', false);
        }
    }
}
