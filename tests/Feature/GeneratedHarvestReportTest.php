<?php

namespace Tests\Feature;

use App\AI\AiLlmClient;
use App\AI\Prompts\HarvestReportPrompt;
use App\Models\GeneratedHarvestReport;
use App\Models\HarvestReport;
use App\Models\User;
use App\Services\HarvestReportNarrationService;
use App\Services\HarvestReportTextService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

/**
 * Saved Actual Harvest Reports with deterministic explanations.
 *
 * The contract being defended: numbers are computed by PHP and frozen with
 * the report, plain templates word them without any external service, both
 * languages are stored at once, and later viewing/downloading stays offline.
 */
class GeneratedHarvestReportTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $otherAdmin;

    private User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        // The developer .env has real keys; tests must never call a provider.
        Config::set('services.groq.key', null);
        Config::set('services.openrouter.key', null);

        $this->admin = $this->makeUser('Report Admin', 'admin');
        $this->otherAdmin = $this->makeUser('Other Admin', 'admin');
        $this->farmer = $this->makeUser('Report Farmer', 'farmer');
    }

    public function test_all_harvest_reports_has_one_generator_link_carrying_current_filters(): void
    {
        $this->seedHarvests();

        $html = $this->actingAs($this->admin)
            ->get(route('admin.harvests.reports', ['crop' => 'Rice', 'season' => 'wet']))
            ->assertOk()
            ->getContent();

        $this->assertSame(1, substr_count($html, 'class="btn-ai-report"'));
        $this->assertStringContainsString(
            e(route('admin.harvests.reports.generate') . '?crop=Rice&season=wet'),
            $html
        );
    }

    public function test_nav_lists_the_generator_under_report_and_request(): void
    {
        $item = collect(config('admin-nav.harvest.items'))
            ->first(fn (array $item) => $item['label'] === 'Generate report');

        $this->assertNotNull($item, 'Generate report is missing from the Report and Request dropdown.');
        $this->assertContains('admin.harvests.reports.generate', $item['routes']);
    }

    public function test_generator_is_admin_only_and_shows_an_empty_saved_list(): void
    {
        $this->actingAs($this->farmer)
            ->get(route('admin.harvests.reports.generate'))
            ->assertForbidden();

        $this->actingAs($this->otherAdmin)
            ->get(route('admin.harvests.reports.generate'))
            ->assertOk()
            ->assertSee('Report Scope', false)
            ->assertSee('Generate report');

        $this->actingAs($this->otherAdmin)
            ->get(route('admin.harvests.reports.generate', ['tab' => 'saved']))
            ->assertOk()
            ->assertSee('No Saved AI Harvest Reports Yet');
    }

    public function test_generate_stays_focused_on_reporting_with_multiyear_filters(): void
    {
        $this->seedHarvests();

        $html = $this->actingAs($this->admin)
            ->get(route('admin.harvests.reports.generate', [
                'years' => ['2025', '2026'],
                'season' => 'wet',
            ]))
            ->assertOk()
            ->getContent();

        foreach ([
            'AI Harvest Report',
            'Report Scope',
            'Generate report',
        ] as $fragment) {
            $this->assertStringContainsString($fragment, $html);
        }

        // The charts were moved to the Harvest Performance page; the report
        // page now holds only the narrative and its supporting metrics.
        foreach ([
            'Year-by-Year Seasonal Performance',
            'Crop Output &amp; Unit Cost',
            'Wet vs. Dry Season',
        ] as $fragment) {
            $this->assertStringNotContainsString($fragment, $html);
        }

        $this->assertStringContainsString('2 records', $html);
        $this->assertStringNotContainsString('Leave a filter', $html);

        // Years are plain chips, and filter failures never force a reload.
        $this->assertStringContainsString('ahr-year-chip', $html);
        $this->assertStringNotContainsString('ahr-year-tile', $html);
        $this->assertStringNotContainsString('ahr-year-meta', $html);
        $this->assertStringNotContainsString('window.location.href = url;', $html);
        $this->assertStringContainsString('Could not update the report', $html);
        $this->assertStringContainsString('incoming.id = id', $html);

        // The generate tab is deliberately lean: title, tabs, then Report Scope.
        $titleAt = strpos($html, '>AI Harvest Report</h1>');
        $topbarAt = strpos($html, 'class="ahr-topbar');
        $scopeAt = strpos($html, 'Report Scope');

        $this->assertNotFalse($titleAt);
        $this->assertNotFalse($topbarAt);
        $this->assertNotFalse($scopeAt);
        $this->assertLessThan($topbarAt, $titleAt);
        $this->assertLessThan($scopeAt, $topbarAt);
        $this->assertStringNotContainsString('Performance at a glance', $html);
        $this->assertStringNotContainsString('id="ahr-preview"', $html);

        // The old studio wording and underlined text controls are gone.
        $this->assertStringNotContainsString('AI report studio', $html);
        $this->assertStringNotContainsString('Ready to write your report', $html);
        $this->assertStringContainsString(
            '.ahr-link-btn { border: 0; background: none; padding: 0; cursor: pointer; font: inherit; font-size: 11.5px; font-weight: 600; color: var(--ahr-primary); text-decoration: none; }',
            $html
        );
        $this->assertStringContainsString('data-filter-link', $html);
        $this->assertStringContainsString('ahr-spinner', $html);
        $this->assertStringContainsString('id="ahr-generating-overlay"', $html);
        $this->assertStringContainsString('Preparing your report', $html);
        $this->assertStringContainsString("matches('#ahr-generate-form')", $html);
        $this->assertStringNotContainsString('id="ahr-kpi-year"', $html);
        $this->assertStringNotContainsString('Print / Export PDF', $html);
    }

    public function test_filter_requests_return_only_the_changing_fragments(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.harvests.reports.generate', ['season' => 'dry']), [
                'X-AQ-Filter-Fragment' => '1',
            ])
            ->assertOk()
            ->assertJsonStructure(['scopeHtml', 'previewHtml', 'recordsHtml', 'scopeText', 'url']);

        $data = $response->json();

        $this->assertStringContainsString('Report Scope', $data['scopeHtml']);
        $this->assertStringContainsString('Total Harvest Volume', $data['previewHtml']);
        $this->assertStringContainsString('Dry', $data['scopeText']);
        $this->assertStringNotContainsString('<html', $data['scopeHtml']);
        $this->assertStringNotContainsString('Ready to write your report', $data['previewHtml']);
    }

    public function test_harvest_performance_page_carries_the_charts_moved_off_the_ai_report(): void
    {
        $this->seedHarvests();

        $this->actingAs($this->admin)
            ->get(route('admin.harvests.index'))
            ->assertOk()
            ->assertSee('Year-by-Year Seasonal Performance')
            ->assertSee('Crop Output & Unit Cost', false)
            ->assertDontSee('Wet vs. Dry Season')
            // The chart fills the card instead of letterboxing a small chart
            // inside a fixed-height scroll box.
            ->assertSee('viewBox="0 0 1100 280"', false)
            ->assertSee('width:100%; height:auto; min-width:960px', false)
            ->assertDontSee('height:200px; overflow-x:auto', false)
            // All three metric buttons now switch real chart panels.
            ->assertSee('data-metric-panel="yield"', false)
            ->assertSee('data-metric-panel="rate" hidden', false)
            ->assertSee('data-metric-panel="expenses" hidden', false)
            ->assertSee('window.__aqMetricSwitchBound', false)
            // Expense axis labels are short and left-aligned so the peso sign
            // never runs off the left edge of the chart.
            ->assertSee('text-anchor="start"', false)
            ->assertSee('₱27k', false)
            ->assertSee('₱29k', false)
            // Wet season reads as a muted, matte blue rather than green or a
            // bright sky tone.
            ->assertSee('fill="#A9C9DF"', false)
            ->assertSee('background:#A9C9DF', false)
            ->assertSee('color:#55799B', false)
            ->assertSee('fill="#E3C9AF"', false)
            ->assertSee('background:#E3C9AF', false)
            ->assertSee('color:#8F6B4B', false)
            ->assertDontSee('fill="#1d8a3e"', false)
            ->assertDontSee('fill="#38BDF8"', false)
            ->assertDontSee('fill="#b45309"', false)
            ->assertDontSee('fill="#C08A5E"', false)
            ->assertDontSee('fill="#D2A47C"', false)
            ->assertDontSee('fill="#7BA7C7"', false)
            ->assertDontSee('fill="#D8B08A"', false)
            ->assertDontSee('rgba(180, 83, 9, ', false)
            ->assertDontSee('rgba(123, 167, 199, ', false)
            ->assertDontSee('rgba(216, 176, 138, ', false)
            ->assertDontSee('opacity=".9"', false)
            ->assertDontSee('seasonColor(s, 0.72)', false);
    }

    public function test_expenses_chart_uses_two_decimal_abbreviations(): void
    {
        $this->seedHarvests();
        HarvestReport::create([
            'user_id' => $this->farmer->id,
            'crop_type' => 'Rice',
            'season' => 'wet',
            'harvest_date' => '2027-06-10',
            'land_area' => 1,
            'actual_yield_metric_tons' => 1,
            'actual_total_expenses' => 1720000,
        ]);

        $html = $this->actingAs($this->admin)
            ->get(route('admin.harvests.index'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('₱1.72M', $html);
        $this->assertStringContainsString('text-anchor="start"', $html);
        $this->assertStringNotContainsString('₱1,720,000', $html);
    }

    public function test_scoped_csv_export_and_saved_report_deletion(): void
    {
        $this->seedHarvests();

        $csv = $this->actingAs($this->admin)
            ->get(route('admin.harvests.reports.generate.export', [
                'years' => ['2026'],
                'season' => 'wet',
            ]))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/csv; charset=utf-8');

        $this->assertStringContainsString('Rice', $csv->getContent());
        $this->assertStringContainsString('Corn', $csv->getContent());
        $this->assertStringNotContainsString('2025-03-05', $csv->getContent());

        $saved = $this->generateSavedReport();

        $this->actingAs($this->otherAdmin)
            ->delete(route('admin.harvests.reports.generated.destroy', $saved))
            ->assertForbidden();

        $this->actingAs($this->admin)
            ->delete(route('admin.harvests.reports.generated.destroy', $saved))
            ->assertRedirectContains('tab=saved');

        $this->assertSame(0, GeneratedHarvestReport::count());
    }

    public function test_generating_a_report_freezes_the_scoped_figures_and_both_languages(): void
    {
        $this->seedHarvests();

        $this->actingAs($this->admin)
            ->post(route('admin.harvests.reports.generate.store'), [
                'title' => 'Wet Rice 2026',
                'default_language' => 'tl',
                'filters' => [
                    'crop' => 'Rice',
                    'season' => 'wet',
                ],
            ])
            ->assertRedirectContains('/admin/harvests/reports/generated/');

        $saved = GeneratedHarvestReport::firstOrFail();

        $this->assertSame($this->admin->id, $saved->user_id);
        $this->assertSame('Wet Rice 2026', $saved->title);
        $this->assertSame('tl', $saved->default_language);
        $this->assertSame(['crop' => 'Rice', 'season' => 'wet'], collect($saved->filters)->except('_report_ids')->all());
        $this->assertCount(1, $saved->filters['_report_ids']);
        $this->assertSame(1, $saved->context['totals']['reports']);
        $this->assertEquals(6.0, $saved->context['totals']['yield_tons']);

        foreach ([$saved->narrative_en, $saved->narrative_tl] as $narrative) {
            $this->assertStringContainsString('6.00', $narrative);
            $this->assertStringContainsString('1', $narrative);
            $this->assertStringNotContainsString('offline', strtolower($narrative));
            $this->assertStringNotContainsString('unavailable', strtolower($narrative));
        }

        $this->assertStringContainsString('Executive Summary', $saved->narrative_en);
        $this->assertStringContainsString('Buod ng Tagapagpaganap', $saved->narrative_tl);
        $this->assertSame('template', $saved->source);
        $this->assertNull($saved->model);
    }

    public function test_generation_never_calls_the_llm(): void
    {
        $this->seedHarvests();

        $this->mock(AiLlmClient::class, function ($mock) {
            $mock->shouldNotReceive('completeJson');
        });

        $this->actingAs($this->admin)
            ->post(route('admin.harvests.reports.generate.store'), [
                'title' => 'Offline report',
                'default_language' => 'en',
                'filters' => ['season' => 'wet'],
            ])
            ->assertRedirectContains('/admin/harvests/reports/generated/');

        $saved = GeneratedHarvestReport::firstOrFail();

        $this->assertSame('template', $saved->source);
        $this->assertNull($saved->model);
    }

    public function test_report_prompt_accepts_array_filter_values(): void
    {
        $text = HarvestReportPrompt::user([], 'Scope', [
            'years' => ['2025', '2026'],
            'season' => 'wet',
        ]);

        $this->assertStringContainsString('Years: 2025, 2026', $text);
        $this->assertStringContainsString('Season: wet', $text);
    }

    public function test_empty_selection_does_not_create_a_saved_report(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.harvests.reports.generate.store'), [
                'title' => 'Nothing',
                'default_language' => 'en',
                'filters' => ['crop' => 'Mango'],
            ])
            ->assertRedirect(route('admin.harvests.reports.generate', ['crop' => 'Mango']))
            ->assertSessionHas('error');

        $this->assertSame(0, GeneratedHarvestReport::count());
    }

    public function test_show_and_download_use_the_saved_copy_without_calling_the_model(): void
    {
        $this->seedHarvests();

        $saved = $this->generateSavedReport();

        // Showing and downloading read the frozen text. If either tried to
        // recompose it, this mock would fail the test.
        $this->mock(HarvestReportTextService::class, function ($mock) {
            $mock->shouldNotReceive('compose');
        });

        $show = $this->actingAs($this->admin)
            ->get(route('admin.harvests.reports.generated.show', $saved))
            ->assertOk();

        $show->assertSee($saved->title)
            ->assertSee('data-lang-panel="en"', false)
            ->assertSee('data-lang-panel="tl"', false)
            ->assertSee('data-lang-btn="tl"', false)
            ->assertSee('Download report')
            ->assertDontSee('Print / Export PDF')
            ->assertDontSee('window.print()', false)
            ->assertDontSee('Download EN')
            ->assertDontSee('Download TL')
            ->assertDontSee('AI Harvest Report · Saved copy')
            ->assertDontSee('AI offline', false);

        $download = $this->actingAs($this->admin)
            ->get(route('admin.harvests.reports.generated.download', [$saved, 'lang' => 'tl']))
            ->assertOk()
            ->assertHeader('Content-Type', 'text/html; charset=utf-8');

        $downloadHtml = $download->getContent();

        $this->assertStringContainsString('lang="tl"', $downloadHtml);
        $this->assertStringContainsString('Buod ng Tagapagpaganap', $downloadHtml);
        $this->assertStringContainsString('-tl.html', (string) $download->headers->get('Content-Disposition'));

        // The exported file is a formal report, not an AI process log.
        // Strip the embedded logo bytes so base64 noise cannot skew the check.
        $visibleDownload = preg_replace('/src="data:image\/png;base64,[^"]+"/', 'src="[logo]"', $downloadHtml);

        $this->assertStringNotContainsString('AI', $visibleDownload);
        $this->assertStringNotContainsString('AI-refined', $visibleDownload);
        $this->assertStringNotContainsString('model', strtolower($visibleDownload));
        $this->assertTrue(
            str_contains($downloadHtml, 'Harvest Performance Report') || str_contains($downloadHtml, 'Ulat sa Pagganap ng Ani'),
            'The exported report is missing its report-type heading.'
        );
        $this->assertTrue(
            str_contains($downloadHtml, 'Mga pangunahing bilang') || str_contains($downloadHtml, 'Key figures'),
            'The exported report is missing its key figures section.'
        );
        $this->assertStringContainsString('Lahat ng bilang ay mula', $downloadHtml);
    }

    public function test_report_language_choice_is_saved_and_used_for_download(): void
    {
        $this->seedHarvests();
        $saved = $this->generateSavedReport();

        $this->actingAs($this->admin)
            ->post(route('admin.harvests.reports.generated.language', $saved), ['language' => 'tl'])
            ->assertOk()
            ->assertJson(['language' => 'tl']);

        $this->assertSame('tl', $saved->fresh()->default_language);

        $showHtml = $this->actingAs($this->admin)
            ->get(route('admin.harvests.reports.generated.show', $saved))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('data-language="tl"', $showHtml);
        $this->assertStringContainsString('data-lang-panel="en" hidden', $showHtml);
        $this->assertStringNotContainsString('data-lang-panel="tl" hidden', $showHtml);
        $this->assertStringContainsString('Download report', $showHtml);
        $this->assertStringContainsString('id="ahr-download-confirm"', $showHtml);
        $this->assertStringContainsString('data-download-base-name=', $showHtml);
        $this->assertStringContainsString('-tl.html', $showHtml);

        $downloadHtml = $this->actingAs($this->admin)
            ->get(route('admin.harvests.reports.generated.download', $saved))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('lang="tl"', $downloadHtml);
        $this->assertStringContainsString('Ulat sa Pagganap ng Ani', $downloadHtml);
        $this->assertStringContainsString('Buod ng Tagapagpaganap', $downloadHtml);
    }

    public function test_an_admin_cannot_read_another_admins_saved_report(): void
    {
        $this->seedHarvests();
        $saved = $this->generateSavedReport();

        $this->actingAs($this->otherAdmin)
            ->get(route('admin.harvests.reports.generated.show', $saved))
            ->assertForbidden();

        $this->actingAs($this->otherAdmin)
            ->get(route('admin.harvests.reports.generated.download', [$saved, 'lang' => 'en']))
            ->assertForbidden();
    }

    public function test_grounded_model_wording_is_saved_with_its_model_name(): void
    {
        $raw = json_encode([
            'en' => "### Executive Summary\nThis selection has 2 reports and 12.50 MT recorded.\n",
            'tl' => "### Buod ng Tagapagpaganap\nMay 2 ulat at 12.50 MT na naitala sa napiling tala.\n",
        ], JSON_UNESCAPED_UNICODE);

        $this->mock(AiLlmClient::class, function ($mock) use ($raw) {
            $mock->shouldReceive('completeJson')->once()->andReturn([
                'raw' => $raw,
                'model' => 'test-model',
            ]);
        });

        $result = app(HarvestReportNarrationService::class)->generate(
            $this->narrationContext(),
            'Grounded report',
            []
        );

        $this->assertSame('ai', $result['source']);
        $this->assertSame('test-model', $result['model']);
        $this->assertStringContainsString('12.50 MT', $result['en']);
        $this->assertStringContainsString('12.50 MT', $result['tl']);
    }

    public function test_ungrounded_model_wording_falls_back_to_the_deterministic_narrative(): void
    {
        $raw = json_encode([
            'en' => "### Executive Summary\nThis selection has 99 reports and 999.00 MT recorded.\n",
            'tl' => "### Buod ng Tagapagpaganap\nMay 99 ulat at 999.00 MT na naitala.\n",
        ], JSON_UNESCAPED_UNICODE);

        $this->mock(AiLlmClient::class, function ($mock) use ($raw) {
            $mock->shouldReceive('completeJson')->once()->andReturn([
                'raw' => $raw,
                'model' => 'drifting-model',
            ]);
        });

        $result = app(HarvestReportNarrationService::class)->generate(
            $this->narrationContext(),
            'Drifting report',
            []
        );

        $this->assertSame('fallback', $result['source']);
        $this->assertNull($result['model']);
        $this->assertStringContainsString('12.50', $result['en']);
        $this->assertStringContainsString('12.50', $result['tl']);
        $this->assertStringNotContainsString('999.00', $result['en']);
        $this->assertStringNotContainsString('999.00', $result['tl']);
    }

    // ── Helpers ───────────────────────────────────────────────────

    /**
     * @return array<string, mixed>
     */
    private function narrationContext(): array
    {
        return [
            'coverage' => [
                'reports' => 2,
                'farmers' => 2,
                'crops' => ['Rice'],
                'seasons' => ['wet'],
            ],
            'totals' => [
                'reports' => 2,
                'farmers' => 2,
                'area_ha' => 3.0,
                'yield_tons' => 12.5,
                'yield_per_ha' => 4.167,
                'expenses_php' => 25000.0,
                'expenses_per_ton' => 2000.0,
            ],
            'by_crop' => [],
            'by_season' => [],
            'weather_events' => [],
            'recent_vs_previous' => null,
        ];
    }

    private function generateSavedReport(): GeneratedHarvestReport
    {
        $this->actingAs($this->admin)
            ->post(route('admin.harvests.reports.generate.store'), [
                'title' => 'Saved Wet Report',
                'default_language' => 'en',
                'filters' => ['season' => 'wet'],
            ])
            ->assertRedirect();

        return GeneratedHarvestReport::firstOrFail();
    }

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

    private function seedHarvests(): void
    {
        foreach ([
            ['user_id' => $this->farmer->id, 'crop_type' => 'Rice', 'season' => 'wet', 'harvest_date' => '2026-06-10', 'land_area' => 1.5, 'actual_yield_metric_tons' => 6.0, 'actual_total_expenses' => 18000],
            ['user_id' => $this->farmer->id, 'crop_type' => 'Corn', 'season' => 'wet', 'harvest_date' => '2026-07-20', 'land_area' => 1.0, 'actual_yield_metric_tons' => 3.5, 'actual_total_expenses' => 9000],
            ['user_id' => $this->farmer->id, 'crop_type' => 'Rice', 'season' => 'dry', 'harvest_date' => '2025-03-05', 'land_area' => 1.0, 'actual_yield_metric_tons' => 4.0, 'actual_total_expenses' => 10000],
        ] as $row) {
            HarvestReport::create($row);
        }
    }
}
