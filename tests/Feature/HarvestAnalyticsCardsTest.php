<?php

namespace Tests\Feature;

use App\Models\HarvestReport;
use App\Models\User;
use App\Services\HarvestInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * The analytics cards moved off the AI page onto the Harvest Performance
 * landing page. Periods in scope was later taken off that page too and folded
 * into a "Full view" reachable from the Latest comparison card — first as a
 * dialog, then as its own page (/admin/harvests/history), because a dialog
 * never had the room to read the whole chart. The landing page carries two
 * cards plus the button into that page.
 *
 * Three things are defended:
 *   1. The cards live on /admin/harvests and the AI page renders none of them.
 *   2. The full harvest history is still one click away — as a breadcrumbed
 *      page, deliberately absent from the sidebar.
 *   3. Nothing was lost to the model. The landing page and the AI digest read
 *      from the same HarvestInsightService context, so the figures the charts
 *      draw are exactly the figures the analysis is allowed to quote.
 */
class HarvestAnalyticsCardsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $farmerA;

    private User $farmerB;

    private User $farmerC;

    private User $farmerD;

    protected function setUp(): void
    {
        parent::setUp();

        // The project .env carries a live key; a feature test must never use it.
        Config::set('services.groq.key', null);

        $this->admin = $this->makeUser('Analytics Admin', 'admin');
        $this->farmerA = $this->makeUser('Analytics Ana', 'farmer');
        $this->farmerB = $this->makeUser('Analytics Ben', 'farmer');
        $this->farmerC = $this->makeUser('Analytics Cara', 'farmer');
        $this->farmerD = $this->makeUser('Analytics Dan', 'farmer');
    }

    // ── The charts are on the harvest page ────────────────────────

    public function test_the_landing_page_renders_the_two_cards_that_stay(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.index'));

        $response->assertOk()
            ->assertSee('Latest comparison')
            ->assertSee('Farmers to recognise')
            ->assertSee('Ranked by number of harvest reports filed.')
            // Periods in scope was taken off the landing page…
            ->assertDontSee('Periods in scope')
            // …and moved behind the Latest comparison card's Full view button,
            // which now opens a page of its own instead of a dialog.
            ->assertSee('Full view')
            ->assertSee(route('admin.harvests.history'), false)
            ->assertDontSee('id="hpHistoryModal"', false);
    }

    public function test_dashboard_and_harvest_performance_share_one_identical_chart(): void
    {
        $this->seedHarvests();

        foreach ([route('dashboard.admin'), route('admin.harvests.index')] as $url) {
            $html = $this->actingAs($this->admin)->get($url)->assertOk()->getContent();

            // The dashboard's chart has generous left padding and a bold
            // total-yield figure. Both pages must now get those exact rules
            // from the same partial, not a local copy that drifts.
            $this->assertStringContainsString('.chart-grid { display: flex; gap: 28px; padding: 24px 26px;', $html);
            $this->assertStringContainsString('.yield-big-value { font-size: 28px; font-weight: 800;', $html);
            $this->assertStringContainsString('id="harvestYieldValue"', $html);
            $this->assertSame(1, substr_count($html, 'id="harvestChart"'), $url . ' must render exactly one harvest chart.');
        }
    }

    public function test_the_full_view_button_opens_the_full_history_page(): void
    {
        $this->seedHarvests();

        $landing = $this->actingAs($this->admin)->get(route('admin.harvests.index'));

        $landing->assertOk()
            ->assertSee(route('admin.harvests.history'), false)
            // The dialog is gone — its contents live on the page now. (The
            // layout's own notice modals keep aria-modal; only the harvest
            // dialog's own hooks are asserted gone.)
            ->assertDontSee('data-hp-history-open', false)
            ->assertDontSee('id="hpHistoryModal"', false);

        $page = $this->actingAs($this->admin)->get(route('admin.harvests.history'));

        $page->assertOk()
            // Every period, with its report count and yield per hectare.
            ->assertSee('2025 Dry Season')
            ->assertSee('2026 Wet Season')
            ->assertSee('3 rpt')
            ->assertSee('4 rpt')
            ->assertSee('3.750 MT/ha')
            ->assertSee('4.500 MT/ha');
    }

    public function test_the_full_history_page_carries_a_breadcrumb_back_to_harvest_performance(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.harvests.history'));

        $response->assertOk()
            ->assertSee(route('admin.harvests.index'), false)
            ->assertSee('>Harvest Performance', false);
    }

    public function test_the_full_history_page_is_not_listed_in_the_sidebar(): void
    {
        $listed = collect(config('admin-nav'))
            ->flatMap(fn (array $group) => collect($group['items'] ?? [])->flatMap(fn (array $item) => $item['routes'] ?? []))
            ->merge(collect(config('admin-nav'))->flatMap(fn (array $group) => $group['hidden_routes'] ?? []));

        $this->assertFalse(
            $listed->contains('admin.harvests.history'),
            'The full history page is a view of the landing page, not a sidebar destination.'
        );
    }

    public function test_the_full_history_page_shows_its_own_empty_state(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.harvests.history'));

        $response->assertOk()
            ->assertSee('No harvest reports recorded yet, so there are no periods to group.')
            ->assertDontSee('id="periodsChart"', false);
    }

    public function test_the_landing_page_draws_the_period_and_comparison_cards_as_charts(): void
    {
        $this->seedHarvests();

        $landing = $this->actingAs($this->admin)->get(route('admin.harvests.index'));

        $landing->assertOk()
            // Chart.js is vendored locally (no CDN requests on page load).
            ->assertSee('js/chart.umd.min.js', false)
            ->assertDontSee('cdn.jsdelivr.net', false)
            // The comparison card stays; the periods chart moved to its page.
            ->assertSee('id="latestChart"', false)
            ->assertDontSee('id="periodsChart"', false);

        $page = $this->actingAs($this->admin)->get(route('admin.harvests.history'));

        $page->assertOk()
            ->assertSee('js/chart.umd.min.js', false)
            ->assertDontSee('cdn.jsdelivr.net', false)
            ->assertSee('id="periodsChart"', false);
    }

    public function test_the_farmer_ranking_is_a_medal_list_not_a_chart(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.index'));

        $response->assertOk()
            // No canvas for this card — it lists names instead.
            ->assertDontSee('id="farmersChart"', false)
            // Gold, silver and bronze medals…
            ->assertSee('hp-medal gold', false)
            ->assertSee('hp-medal silver', false)
            // …and an award ribbon for everyone below the top three.
            ->assertSee('hp-medal other', false)
            ->assertSee('Rank 1 — gold', false)
            ->assertSee('Rank 2 — silver', false)
            // With the names and figures beside them.
            ->assertSee('#1 Analytics Ana')
            ->assertSee('#2 Analytics Ben')
            ->assertSee('3 rpt')
            ->assertSee('1 rpt');
    }

    public function test_the_full_history_page_continues_the_landing_pages_bar_chart(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.history'));

        $response->assertOk()
            ->assertSee('id="periodsChart"', false)
            // The same bar chart the landing page's Latest comparison draws —
            // same dataset name, same season colours — so the page reads as
            // that graph continued rather than a second charting style.
            ->assertSee("type: 'bar'", false)
            ->assertSee("label: 'Yield per hectare (MT/ha)'", false)
            ->assertSee('seasonColor(s, 0.72)', false)
            // The axis carries the year alone, each tick coloured by its
            // period's season — short labels that never slant.
            ->assertSee('var periodYears', false)
            ->assertSee('maxRotation: 0', false)
            ->assertDontSee('seasonLine', false)
            ->assertDontSee('spanGaps', false)
            // The per-period figures sit in the shared styled table now, not
            // in the old joined list.
            ->assertSee('class="data-table"', false)
            ->assertDontSee('fh-list', false);

        // The comparison card on the landing page is still a bar chart.
        $this->actingAs($this->admin)
            ->get(route('admin.harvests.index'))
            ->assertOk()
            ->assertSee('id="latestChart"', false)
            ->assertSee("type: 'bar'", false);
    }

    public function test_the_charts_still_show_the_underlying_numbers_as_text(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.index'));

        // Comparison rows.
        $response->assertSee('2026 Wet Season vs 2025 Dry Season')
            ->assertSee('Change in yield per hectare');

        // Farmer rows, ranked: Ana filed 3, Ben filed 2.
        $response->assertSee('#1 Analytics Ana')
            ->assertSee('#2 Analytics Ben')
            ->assertSee('3 rpt')
            ->assertSee('2 rpt');

        // Period rows live on the full history page: both periods, their
        // report counts and their yield per hectare.
        $page = $this->actingAs($this->admin)->get(route('admin.harvests.history'));

        $page->assertSee('2025 Dry Season')
            ->assertSee('2026 Wet Season')
            ->assertSee('3 rpt')
            ->assertSee('4 rpt')
            ->assertSee('3.750 MT/ha')
            ->assertSee('4.500 MT/ha');
    }

    public function test_the_landing_page_shows_empty_states_before_any_report_exists(): void
    {
        $response = $this->actingAs($this->admin)->get(route('admin.harvests.index'));

        $response->assertOk()
            ->assertSee('Only one harvest period exists so far')
            ->assertSee('No farmer records to rank yet.')
            ->assertDontSee('id="periodsChart"', false);
    }

    // ── The cards are gone from the AI page ───────────────────────

    public function test_the_ai_page_no_longer_renders_the_three_cards(): void
    {
        $this->seedHarvests();

        $this->actingAs($this->admin)
            ->get(route('admin.harvests.ai'))
            ->assertOk()
            ->assertDontSee('Periods in scope')
            ->assertDontSee('Latest comparison')
            ->assertDontSee('Farmers to recognise')
            // …but it still has the chat that asks about the same digest.
            ->assertDontSee('Data this chat reads')
            ->assertSee('id="aiChatLog"', false);
    }

    public function test_the_ai_page_drops_the_redundant_coverage_boilerplate(): void
    {
        $this->seedHarvests();

        $this->actingAs($this->admin)
            ->get(route('admin.harvests.ai'))
            ->assertOk()
            // The note banner and the "what else the model reads" footer took a
            // lot of room to restate figures already shown in the data card.
            ->assertDontSee('Sourced only from your harvest reports and your weather log.')
            ->assertDontSee('The model also reads')
            ->assertDontSee('falling inside a recorded harvest range')
            // The standalone chat keeps the context in its conversation rather
            // than exposing a separate raw-data panel.
            ->assertSee('harvest reports');
    }

    // ── Nothing was lost to the model ─────────────────────────────

    public function test_the_landing_page_and_the_ai_page_read_the_same_digest(): void
    {
        $this->seedHarvests();

        $fromLanding = $this->actingAs($this->admin)
            ->get(route('admin.harvests.index'))
            ->viewData('context');

        $fromAi = $this->actingAs($this->admin)
            ->get(route('admin.harvests.ai'))
            ->viewData('context');

        $this->assertSame($fromLanding['periods'], $fromAi['periods']);
        $this->assertSame($fromLanding['recent_vs_previous'], $fromAi['recent_vs_previous']);
        $this->assertSame($fromLanding['top_farmers_by_reports'], $fromAi['top_farmers_by_reports']);
        $this->assertSame($fromLanding['totals'], $fromAi['totals']);
    }

    public function test_the_digest_still_carries_every_analytics_slice_the_llm_needs(): void
    {
        $this->seedHarvests();

        $context = app(HarvestInsightService::class)->context();

        foreach ([
            'coverage',
            'totals',
            'periods',
            'recent_vs_previous',
            'by_season',
            'by_crop',
            'top_farmers_by_reports',
            'top_farmers_by_yield',
            'farmers',
            'varieties',
            'weather_events',
            'climatology_reference',
        ] as $key) {
            $this->assertArrayHasKey($key, $context, "The model is missing the `{$key}` slice.");
        }

        $this->assertCount(2, $context['periods']);
        $this->assertCount(4, $context['top_farmers_by_reports']);
        $this->assertNotEmpty($context['by_season']);
        $this->assertNotEmpty($context['by_crop']);
        $this->assertSame('Analytics Ana', $context['top_farmers_by_reports'][0]['farmer']);

        // Every farmer with a report is in `farmers`, worst yield per
        // hectare last, so "who did badly" is answerable from the digest.
        $this->assertCount(4, $context['farmers']);
        $this->assertArrayHasKey('yield_per_ha', end($context['farmers']));
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

    /**
     * Four farmers over two periods, so every medal tier has a holder:
     *
     *   2025 Dry — 3 reports, 4.0 ha, 15.00 MT (3.750 MT/ha)
     *   2026 Wet — 4 reports, 4.0 ha, 18.00 MT (4.500 MT/ha)
     *
     * Reports filed: Ana 3, Ben 2, Cara 1, Dan 1.
     */
    private function seedHarvests(): void
    {
        foreach ([
            ['user_id' => $this->farmerA->id, 'crop_type' => 'Rice', 'season' => 'dry', 'harvest_date' => '2025-02-10', 'land_area' => 2.0, 'actual_yield_metric_tons' => 8.0, 'actual_total_expenses' => 20000],
            ['user_id' => $this->farmerA->id, 'crop_type' => 'Corn', 'season' => 'dry', 'harvest_date' => '2025-03-05', 'land_area' => 1.0, 'actual_yield_metric_tons' => 3.0, 'actual_total_expenses' => 8000],
            ['user_id' => $this->farmerB->id, 'crop_type' => 'Rice', 'season' => 'dry', 'harvest_date' => '2025-03-20', 'land_area' => 1.0, 'actual_yield_metric_tons' => 4.0, 'actual_total_expenses' => 10000],
            ['user_id' => $this->farmerC->id, 'crop_type' => 'Rice', 'season' => 'wet', 'harvest_date' => '2026-06-10', 'land_area' => 1.0, 'actual_yield_metric_tons' => 4.0, 'actual_total_expenses' => 12000],
            ['user_id' => $this->farmerD->id, 'crop_type' => 'Corn', 'season' => 'wet', 'harvest_date' => '2026-06-25', 'land_area' => 1.0, 'actual_yield_metric_tons' => 4.5, 'actual_total_expenses' => 11000],
            ['user_id' => $this->farmerA->id, 'crop_type' => 'Rice', 'season' => 'wet', 'harvest_date' => '2026-07-15', 'land_area' => 1.5, 'actual_yield_metric_tons' => 6.0, 'actual_total_expenses' => 18000],
            ['user_id' => $this->farmerB->id, 'crop_type' => 'Corn', 'season' => 'wet', 'harvest_date' => '2026-08-20', 'land_area' => 0.5, 'actual_yield_metric_tons' => 3.5, 'actual_total_expenses' => 9000],
        ] as $row) {
            HarvestReport::create($row);
        }
    }
}
