<?php

namespace Tests\Feature;

use App\Models\ActivityLog;
use App\Models\HarvestReport;
use App\Models\User;
use App\Models\WeatherLog;
use App\Services\HarvestInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Admin Settings → Weather Log, and its single purpose: giving the harvest
 * analysis a second, date-stamped source so a drop in yield can be explained
 * instead of only measured.
 *
 * The key assertions are about the join — that a logged event lands on the
 * harvest period its dates actually fall inside, and that an event outside
 * every recorded harvest range is reported but never used to explain one.
 */
class WeatherLogTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        // No feature test may reach the real provider.
        Config::set('services.groq.key', null);

        $this->admin = $this->makeUser('Weather Admin', 'admin');
        $this->farmer = $this->makeUser('Weather Farmer', 'farmer');
    }

    // ── Page + access ─────────────────────────────────────────────

    public function test_an_admin_can_open_the_weather_log_settings_page(): void
    {
        $this->seedHarvests();
        $this->seedWeather();

        $response = $this->actingAs($this->admin)->get(route('admin.settings.weather'));

        $response->assertOk()
            ->assertSee('Weather Log')
            ->assertSee('Why this exists')
            ->assertSee('Typhoon Kristine')
            ->assertSee('Aug 10 – 14, 2026');
    }

    public function test_a_farmer_cannot_open_the_weather_log(): void
    {
        $this->actingAs($this->farmer)->get(route('admin.settings.weather'))->assertForbidden();
        $this->actingAs($this->farmer)->post(route('admin.settings.weather.store'))->assertForbidden();
    }

    public function test_every_settings_page_offers_the_weather_log_tab(): void
    {
        $pages = [
            route('admin.settings.index'),
            route('admin.settings.seasons'),
            route('admin.settings.interface'),
            route('admin.audit.index'),
        ];

        foreach ($pages as $url) {
            $this->actingAs($this->admin)
                ->get($url)
                ->assertOk()
                ->assertSee(route('admin.settings.weather'), false)
                ->assertSee('Weather Log');
        }
    }

    public function test_the_weather_log_reachable_from_the_admin_sidebar_config(): void
    {
        $items = collect(config('admin-nav.settings.items'));

        $entry = $items->first(fn (array $item) => ($item['routes'] ?? []) === ['admin.settings.weather']);

        $this->assertNotNull($entry, 'Weather Log must be listed in the sidebar Settings section.');
        $this->assertSame('Weather Log', $entry['label']);
    }

    // ── Creating events ───────────────────────────────────────────

    public function test_an_admin_can_log_a_weather_event(): void
    {
        $response = $this->actingAs($this->admin)->postJson(
            route('admin.settings.weather.store'),
            [
                'title'      => 'Typhoon Kristine',
                'event_type' => 'typhoon',
                'severity'   => 'severe',
                'start_date' => '2025-09-15',
                'end_date'   => '2025-09-18',
                'notes'      => 'Fields in the low-lying barangays stayed flooded for days.',
            ]
        );

        $response->assertOk()->assertJsonPath('event.title', 'Typhoon Kristine');

        $this->assertDatabaseHas('weather_logs', [
            'title'      => 'Typhoon Kristine',
            'event_type' => 'typhoon',
            'severity'   => 'severe',
            'logged_by'  => $this->admin->id,
        ]);
    }

    public function test_a_single_day_event_needs_no_end_date(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.settings.weather.store'), [
            'title'      => 'One-off downpour',
            'event_type' => 'heavy_rain',
            'severity'   => 'moderate',
            'start_date' => '2026-05-02',
            'end_date'   => null,
        ])->assertOk();

        $event = WeatherLog::where('title', 'One-off downpour')->firstOrFail();

        $this->assertNull($event->end_date);
        $this->assertSame('May 02, 2026', $event->dateRangeLabel());
    }

    public function test_an_end_date_before_the_start_date_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.weather.store'), [
                'title'      => 'Backwards range',
                'event_type' => 'flood',
                'severity'   => 'minor',
                'start_date' => '2025-09-18',
                'end_date'   => '2025-09-10',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('end_date');

        $this->assertDatabaseCount('weather_logs', 0);
    }

    public function test_a_future_event_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.weather.store'), [
                'title'      => 'Not yet',
                'event_type' => 'typhoon',
                'severity'   => 'minor',
                'start_date' => now()->addYear()->toDateString(),
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('start_date');
    }

    public function test_an_unknown_event_type_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->postJson(route('admin.settings.weather.store'), [
                'title'      => 'Made up',
                'event_type' => 'alien_invasion',
                'severity'   => 'extreme',
                'start_date' => '2025-01-01',
            ])
            ->assertStatus(422)
            ->assertJsonValidationErrors('event_type');
    }

    public function test_removing_an_event_is_logged_and_persists(): void
    {
        $event = WeatherLog::create([
            'logged_by'  => $this->admin->id,
            'title'      => 'Mistyped date',
            'event_type' => 'flood',
            'severity'   => 'minor',
            'start_date' => '2025-04-01',
        ]);

        $this->actingAs($this->admin)
            ->deleteJson(route('admin.settings.weather.destroy', $event))
            ->assertOk()
            ->assertJson(['ok' => true]);

        $this->assertDatabaseMissing('weather_logs', ['id' => $event->id]);
        $this->assertDatabaseHas('activity_logs', [
            'type'   => ActivityLog::TYPE_WEATHER_DELETED,
            'user_id' => $this->admin->id,
        ]);
    }

    public function test_logging_an_event_writes_an_activity_row(): void
    {
        $this->actingAs($this->admin)->postJson(route('admin.settings.weather.store'), [
            'title'      => 'Typhoon Kristine',
            'event_type' => 'typhoon',
            'severity'   => 'severe',
            'start_date' => '2025-09-15',
            'end_date'   => '2025-09-18',
        ])->assertOk();

        $log = ActivityLog::where('type', ActivityLog::TYPE_WEATHER_CREATED)->first();

        $this->assertNotNull($log);
        $this->assertStringContainsString('Typhoon Kristine', $log->description);
        $this->assertSame('typhoon', $log->metadata['event_type']);
    }

    // ── The join into harvest analysis ────────────────────────────

    public function test_logged_events_land_on_the_harvest_period_their_dates_fall_inside(): void
    {
        $this->seedHarvests();
        $this->seedWeather();

        $context = app(HarvestInsightService::class)->context();

        $this->assertSame(3, $context['coverage']['weather_events_logged']);
        $this->assertSame(2, $context['coverage']['weather_events_overlapping']);

        $byTitle = collect($context['weather_events'])->keyBy('title');

        // The typhoon lands inside 2026 Wet (harvests Jul 15 – Aug 20, 2026).
        $this->assertSame(
            ['2026 Wet Season'],
            $byTitle['Typhoon Kristine']['overlapping_periods']
        );
        $this->assertSame('severe', $byTitle['Typhoon Kristine']['severity']);

        // The drought lands inside 2025 Dry (harvests Feb 10 – Mar 20, 2025).
        $this->assertSame(
            ['2025 Dry Season'],
            $byTitle['Prolonged drought']['overlapping_periods']
        );

        // December 2025 sits between the two windows — reported, attached to nothing.
        $this->assertSame([], $byTitle['Localized flooding']['overlapping_periods']);
    }

    public function test_each_period_carries_the_weather_events_that_fall_inside_it(): void
    {
        $this->seedHarvests();
        $this->seedWeather();

        $context = app(HarvestInsightService::class)->context();

        $periods = collect($context['periods'])->keyBy('label');

        $this->assertSame(
            ['Prolonged drought'],
            array_column($periods['2025 Dry Season']['weather_events'], 'title')
        );
        $this->assertSame(
            ['Typhoon Kristine'],
            array_column($periods['2026 Wet Season']['weather_events'], 'title')
        );
    }

    public function test_an_empty_weather_log_leaves_every_period_without_weather(): void
    {
        $this->seedHarvests();

        $context = app(HarvestInsightService::class)->context();

        $this->assertSame([], $context['weather_events']);
        $this->assertSame(0, $context['coverage']['weather_events_logged']);

        foreach ($context['periods'] as $period) {
            $this->assertSame([], $period['weather_events']);
        }
    }

    // ── What the analysis says about it ───────────────────────────

    public function test_the_fallback_names_the_logged_event_and_stops_short_of_claiming_cause(): void
    {
        $this->seedHarvests();
        $this->seedWeather();

        $service = app(HarvestInsightService::class);
        $insight = $service->fallback($service->context());

        $this->assertStringContainsString('Typhoon Kristine', $insight);
        $this->assertStringContainsString('Aug 10 – 14, 2026', $insight);
        $this->assertStringContainsString('2026 Wet Season', $insight);
        $this->assertStringContainsString('2025 Dry Season', $insight);
        $this->assertStringContainsString('Worth verifying against field records', $insight);

        // The event dated between the windows must not be used as an explanation.
        $this->assertStringNotContainsString('Localized flooding', $insight);
    }

    public function test_the_fallback_asks_for_a_weather_log_when_none_exists(): void
    {
        $this->seedHarvests();

        $service = app(HarvestInsightService::class);
        $insight = $service->fallback($service->context());

        $this->assertStringContainsString('Settings → Weather Log', $insight);
        $this->assertStringContainsString('no weather events are logged', $insight);
    }

    public function test_events_outside_the_harvest_range_are_flagged_not_used(): void
    {
        $this->seedHarvests();

        WeatherLog::create([
            'logged_by'  => $this->admin->id,
            'title'      => 'Prolonged drought',
            'event_type' => 'drought',
            'severity'   => 'moderate',
            'start_date' => '2024-04-01',
            'end_date'   => '2024-04-30',
        ]);

        $service = app(HarvestInsightService::class);
        $context = $service->context();
        $insight = $service->fallback($context);

        $this->assertSame(0, $context['coverage']['weather_events_overlapping']);
        $this->assertStringContainsString('cannot yet be tied to a period', $insight);
        $this->assertStringContainsString('Check their dates', $insight);
    }

    public function test_the_weather_log_reaches_the_model_as_part_of_the_digest(): void
    {
        $this->seedHarvests();
        $this->seedWeather();

        Config::set('services.groq.key', 'test-key');
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode([
                    'reply' => '**Typhoon Kristine** was logged **Aug 10 – 14, 2026** and falls inside **2026 Wet Season**.',
                ])]]],
            ]),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'What weather was logged last year?']);

        $response->assertOk()->assertJson(['source' => 'ai']);
        $this->assertStringContainsString('Typhoon Kristine', $response->json('reply'));

        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];
            $user = collect($messages)->firstWhere('role', 'user');

            return is_array($user)
                && str_contains((string) $user['content'], '"weather_events"')
                && str_contains((string) $user['content'], 'Typhoon Kristine');
        });
    }

    public function test_the_ai_page_carries_no_weather_boilerplate(): void
    {
        $this->seedHarvests();
        $this->seedWeather();

        $this->actingAs($this->admin)
            ->get(route('admin.harvests.ai'))
            ->assertOk()
            // No dedicated card and no banner restating the log counts — the
            // counts live on Settings → Weather Log and in the model's digest.
            ->assertDontSee('<p class="ai-card-title">Weather log</p>', false)
            ->assertDontSee('3 logged')
            ->assertDontSee('2 falling inside a recorded harvest range')
            ->assertDontSee('Sourced only from your harvest reports and your weather log.');
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
     * Two periods, so weather has something to overlap:
     *   2025 Dry — harvests Feb 10 … Mar 20, 2025
     *   2026 Wet — harvests Jul 15 … Aug 20, 2026
     */
    private function seedHarvests(): void
    {
        foreach ([
            ['user_id' => $this->farmer->id, 'crop_type' => 'Rice', 'season' => 'dry', 'harvest_date' => '2025-02-10', 'land_area' => 2.0, 'actual_yield_metric_tons' => 8.0, 'actual_total_expenses' => 20000],
            ['user_id' => $this->farmer->id, 'crop_type' => 'Rice', 'season' => 'dry', 'harvest_date' => '2025-03-20', 'land_area' => 2.0, 'actual_yield_metric_tons' => 7.0, 'actual_total_expenses' => 18000],
            ['user_id' => $this->farmer->id, 'crop_type' => 'Rice', 'season' => 'wet', 'harvest_date' => '2026-07-15', 'land_area' => 2.0, 'actual_yield_metric_tons' => 6.0, 'actual_total_expenses' => 22000],
            ['user_id' => $this->farmer->id, 'crop_type' => 'Rice', 'season' => 'wet', 'harvest_date' => '2026-08-20', 'land_area' => 2.0, 'actual_yield_metric_tons' => 5.5, 'actual_total_expenses' => 21000],
        ] as $row) {
            HarvestReport::create($row);
        }
    }

    /**
     * Two events inside the harvest ranges, one comfortably between them.
     *
     *   2025 Dry window = Feb 10 … Mar 20, 2025  → drought lands inside
     *   2026 Wet window = Jul 15 … Aug 20, 2026  → typhoon lands inside
     *   December 2025 sits between the two windows → attached to nothing
     */
    private function seedWeather(): void
    {
        foreach ([
            ['title' => 'Prolonged drought', 'event_type' => 'drought', 'severity' => 'moderate', 'start_date' => '2025-02-20', 'end_date' => '2025-03-05', 'notes' => 'Dry-seeding conditions, no rain for weeks.'],
            ['title' => 'Typhoon Kristine', 'event_type' => 'typhoon', 'severity' => 'severe', 'start_date' => '2026-08-10', 'end_date' => '2026-08-14', 'notes' => 'Low-lying fields flooded.'],
            ['title' => 'Localized flooding', 'event_type' => 'flood', 'severity' => 'moderate', 'start_date' => '2025-12-05', 'end_date' => '2025-12-07', 'notes' => 'Between harvest periods.'],
        ] as $row) {
            WeatherLog::create($row + ['logged_by' => $this->admin->id]);
        }
    }
}
