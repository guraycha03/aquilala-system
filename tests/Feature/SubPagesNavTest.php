<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class SubPagesNavTest extends TestCase
{
    use RefreshDatabase;

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'System Admin',
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'subpages_admin_'.uniqid().'@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ]);
    }

    public function test_sub_pages_always_render_as_side_menu_dropdowns(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.users.index'))
            ->assertOk()
            ->assertDontSee('<div class="aqua-tabs"', false)
            ->assertSee('<li class="nav-dropdown open">', false)
            ->assertSee('<button type="button" class="nav-dropdown-btn"', false)
            ->assertSee('<a href="'.route('admin.users.index').'" class="active">', false);
    }

    public function test_the_top_tab_bar_is_gone_from_every_checked_page(): void
    {
        $admin = $this->makeAdmin();

        $pages = [
            route('admin.users.index'),
            route('admin.harvests.index'),
            route('admin.calendar.index'),
            route('admin.settings.interface'),
            route('admin.quotations.index'),
            route('admin.quotations.crops'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($admin)
                ->get($url)
                ->assertOk()
                ->assertDontSee('<div class="aqua-tabs"', false)
                ->assertDontSee('aqua-tabs-spacer', false);
        }
    }

    public function test_reporting_is_folded_into_the_sections_that_own_the_data(): void
    {
        $nav = config('admin-nav');

        $this->assertArrayNotHasKey(
            'analytics',
            $nav,
            'There is no reporting section of its own: every report lives beside the data it describes.'
        );

        // Expense figures stay on Harvest Performance with the other charts;
        // there is no longer a separate cost-report page in the sidebar.
        $harvest = collect($nav['harvest']['items']);
        $this->assertNull(
            $harvest->first(fn (array $item) => in_array('admin.analytics.expenses', $item['routes'] ?? [], true)),
            'Farming Cost Reports must not have its own sidebar entry.'
        );

        // …and crop trends tie quoted farm inputs to crops, so Farm Supplies owns it.
        $operations = collect($nav['operations']['items']);
        $this->assertNotNull(
            $operations->first(fn (array $item) => ($item['routes'] ?? []) === ['admin.quotations.index']),
            'View Crop Trends belongs with Farm Supplies.'
        );
    }

    public function test_ai_insight_is_a_standalone_admin_link_below_dashboard(): void
    {
        $keys = array_keys(config('admin-nav'));

        $this->assertSame(
            'ai_insight',
            $keys[array_search('dashboard', $keys, true) + 1]
        );
        $this->assertTrue(config('admin-nav.ai_insight.standalone'));
        $this->assertNotContains(
            ['label' => 'AI Insights', 'routes' => ['admin.harvests.ai']],
            config('admin-nav.harvest.items')
        );

        $response = $this->actingAs($this->makeAdmin())
            ->get(route('admin.harvests.ai'));

        $response->assertOk()
            ->assertSee('AI Assistant')
            ->assertSee(
                '<a href="'.route('admin.harvests.ai').'" class="active">',
                false
            )
            ->assertDontSee('<li class="nav-dropdown open">', false);
    }

    public function test_the_tabs_style_preference_route_is_removed(): void
    {
        $this->assertFalse(Route::has('settings.tabs-style'));
    }

    public function test_settings_no_longer_offer_a_sub_page_style_picker(): void
    {
        $this->actingAs($this->makeAdmin())
            ->get(route('admin.settings.interface'))
            ->assertOk()
            ->assertDontSee('Tabs on top')
            ->assertDontSee('Sub-page Navigation')
            ->assertSee('Side Menu Appearance');
    }

    public function test_the_sub_pages_style_column_is_dropped(): void
    {
        $this->actingAs($this->makeAdmin())->get(route('admin.users.index'))->assertOk();

        $this->assertFalse(
            \Schema::hasColumn('users', 'sub_pages_style'),
            'users.sub_pages_style should no longer exist.'
        );
    }
}
