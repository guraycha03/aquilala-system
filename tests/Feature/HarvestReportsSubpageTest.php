<?php

namespace Tests\Feature;

use App\Models\HarvestReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The admin Harvest Performance landing page and its "All Harvest Reports"
 * sub-page.
 *
 * The table was split off the landing page so that page can show headline
 * cards instead of a wall of rows. These tests defend the split: the landing
 * page must no longer render the table, it must offer a way into the sub-page,
 * and the sub-page must carry the table (plus a breadcrumb back) intact.
 */
class HarvestReportsSubpageTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = $this->makeUser('Reports Admin', 'admin');
        $this->farmer = $this->makeUser('Reports Farmer', 'farmer');
    }

    // ── Landing page ──────────────────────────────────────────────

    public function test_the_landing_page_shows_headline_cards_and_no_table(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.index'));

        $response->assertOk()
            ->assertSee('Harvest Performance')
            ->assertSee('Farmers Reporting')
            ->assertSee('Total Yield')
            // The table has moved out.
            ->assertDontSee('adminHarvestsTable')
            ->assertDontSee('id="exportBtn"', false);
    }

    public function test_the_landing_page_offers_a_button_into_the_reports_subpage(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.index'));

        $response->assertOk()
            ->assertSee(route('admin.harvests.reports'), false)
            ->assertSee('All Harvest Reports');
    }

    public function test_the_landing_page_still_links_to_the_ai_subpage(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.harvests.index'));

        $response->assertOk()
            ->assertSee(route('admin.harvests.ai'), false)
            ->assertSee('AI Insights');
    }

    // ── Reports sub-page ──────────────────────────────────────────

    public function test_the_reports_subpage_renders_the_moved_table(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.reports'));

        $response->assertOk()
            ->assertSee('All Harvest Reports')
            ->assertSee('Recorded by farmers across the Aquilala network.')
            ->assertSee('adminHarvestsTable')
            ->assertSee('id="exportBtn"', false)
            ->assertSee('Rice');
    }

    public function test_the_reports_subpage_carries_a_breadcrumb_back_to_harvest_performance(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.harvests.reports'));

        $response->assertOk()
            ->assertSee(route('admin.harvests.index'), false)
            ->assertSee('>Harvest Performance', false);
    }

    public function test_the_reports_subpage_lists_every_farmers_reports(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.reports'));

        $reports = $response->viewData('reports');

        $this->assertCount(4, $reports);
        $this->assertSame(
            $this->farmer->name,
            $reports->first()->farmer->name
        );
    }

    public function test_the_reports_subpage_is_reachable_from_the_sidebar_config(): void
    {
        $items = collect(config('admin-nav.analytics.items'));

        $entry = $items->first(fn (array $item) => ($item['routes'] ?? []) === ['admin.harvests.reports']);

        $this->assertNotNull($entry, 'All Harvest Reports must be listed in the sidebar.');
        $this->assertSame('All Harvest Reports', $entry['label']);
    }

    // ── The toolbar's year and month filters ──────────────────────

    public function test_each_row_exposes_the_year_and_month_the_toolbar_filters_on(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.reports'));

        // The toolbar compares a filter's chosen value against row.dataset[key],
        // so every filter key needs a matching data-* attribute or the row is
        // silently dropped and the table looks empty.
        $response->assertOk()
            ->assertSee('data-year="2025"', false)
            ->assertSee('data-year="2026"', false)
            ->assertSee('data-month="2"', false)
            ->assertSee('data-month="7"', false)
            ->assertSee('data-month="8"', false);

        // …and those attributes must match the option values offered.
        $this->assertContains('2026', array_column($response->viewData('yearOptions'), 0));
        $this->assertContains('7', array_column($response->viewData('monthOptions'), 0));
    }

    public function test_the_farmers_own_harvest_table_exposes_them_too(): void
    {
        $this->seedHarvests();

        $this->actingAs($this->farmer)
            ->get(route('farmer.harvests.index'))
            ->assertOk()
            ->assertSee('data-year="2025"', false)
            ->assertSee('data-month="8"', false);
    }

    public function test_sorting_moves_the_rows_and_not_just_the_page(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.reports'));

        // The toolbar compares and sorts into an array, then must push the rows
        // back into the table in that order. Without the appendChild pass a sort
        // only re-runs pagination, so once a filter leaves fewer rows than fit
        // on one page the sort looks like a dead control.
        $response->assertOk()
            ->assertSee('filtered.forEach(function (r) { container.appendChild(r); })', false)
            ->assertSee('var container = resolveTable();', false);
    }

    // ── Access ────────────────────────────────────────────────────

    public function test_a_farmer_cannot_reach_either_admin_page(): void
    {
        $this->seedHarvests();

        $this->actingAs($this->farmer)->get(route('admin.harvests.index'))->assertForbidden();
        $this->actingAs($this->farmer)->get(route('admin.harvests.reports'))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->seedHarvests();

        $this->get(route('admin.harvests.index'))->assertRedirect();
        $this->get(route('admin.harvests.reports'))->assertRedirect();
    }

    // ── Helpers ───────────────────────────────────────────────────

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name'       => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name'  => explode(' ', $name)[1] ?? 'Test',
            'email'      => strtolower(str_replace(' ', '_', $name)) . '_' . uniqid() . '@example.com',
            'role'       => $role,
            'password'   => 'password123',
        ]);
    }

    private function seedHarvests(): void
    {
        foreach ([
            ['user_id' => $this->farmer->id, 'crop_type' => 'Rice', 'season' => 'dry', 'harvest_date' => '2025-02-10', 'land_area' => 2.0, 'actual_yield_metric_tons' => 8.0, 'actual_total_expenses' => 20000],
            ['user_id' => $this->farmer->id, 'crop_type' => 'Corn', 'season' => 'dry', 'harvest_date' => '2025-03-05', 'land_area' => 1.0, 'actual_yield_metric_tons' => 3.0, 'actual_total_expenses' => 8000],
            ['user_id' => $this->farmer->id, 'crop_type' => 'Rice', 'season' => 'wet', 'harvest_date' => '2026-07-15', 'land_area' => 2.0, 'actual_yield_metric_tons' => 6.0, 'actual_total_expenses' => 22000],
            ['user_id' => $this->farmer->id, 'crop_type' => 'Corn', 'season' => 'wet', 'harvest_date' => '2026-08-20', 'land_area' => 1.0, 'actual_yield_metric_tons' => 3.5, 'actual_total_expenses' => 9000],
        ] as $row) {
            HarvestReport::create($row);
        }
    }
}
