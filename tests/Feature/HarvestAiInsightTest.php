<?php

namespace Tests\Feature;

use App\AI\Prompts\HarvestQaPrompt;
use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\User;
use App\Services\HarvestInsightService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Admin AI Harvest Insights subpage — the chat.
 *
 * Two things are being defended here:
 *   1. Every answer may only be built from harvest_reports — no other table,
 *      no invented figures, and no claim that a weather event happened.
 *   2. A model that is offline, absent, or rambling must still leave the
 *      admin with a readable, accurate answer rather than an error page.
 *
 * The Groq key is nulled in setUp so no test can reach the real provider;
 * the model path is exercised through Http::fake.
 */
class HarvestAiInsightTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $farmerA;

    private User $farmerB;

    protected function setUp(): void
    {
        parent::setUp();

        // The project .env carries a live key; a feature test must never use it.
        Config::set('services.groq.key', null);

        $this->admin = $this->makeUser('Insights Admin', 'admin', 'admin');
        $this->farmerA = $this->makeUser('Farmer Ana', 'farmer', 'farmer');
        $this->farmerB = $this->makeUser('Farmer Ben', 'farmer', 'farmer');
    }

    // ── Page + navigation ──────────────────────────────────────────

    public function test_an_admin_can_open_the_ai_insights_subpage(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)->get(route('admin.harvests.ai'));

        $response->assertOk()->assertSee('AI Harvest Insights');

        // The chat container, its suggested-question buttons and its input.
        $response->assertSee('id="aiChatLog"', false)
            ->assertSee('id="aiChatForm"', false)
            ->assertSee('id="aiChatInput"', false)
            ->assertSee('How did the latest season compare with the one before it?', false)
            ->assertSee('Which farmers should I recognise?', false)
            ->assertSee('What should I verify next?', false);

        // Nothing generates a report on the administrator's behalf.
        $response->assertDontSee('Generate insights', false)
            ->assertDontSee('id="aiNarrative"', false);

        $context = $response->viewData('context');

        $this->assertSame(5, $context['coverage']['reports']);
        $this->assertSame(2, $context['coverage']['farmers']);
    }

    public function test_the_harvest_page_links_to_the_ai_subpage(): void
    {
        $this->seedHarvests();

        $this->actingAs($this->admin)
            ->get(route('admin.harvests.index'))
            ->assertOk()
            ->assertSee(route('admin.harvests.ai'), false)
            ->assertSee('AI Insights');
    }

    public function test_a_farmer_cannot_reach_the_admin_ai_subpage(): void
    {
        $this->seedHarvests();

        $this->actingAs($this->farmerA)->get(route('admin.harvests.ai'))->assertForbidden();
        $this->actingAs($this->farmerA)->post(route('admin.harvests.ai.ask'), ['message' => 'hi'])->assertForbidden();
    }

    // ── Deterministic context ──────────────────────────────────────

    public function test_the_context_is_built_from_real_harvest_records(): void
    {
        $this->seedHarvests();

        $context = app(HarvestInsightService::class)->context();

        $this->assertSame(5, $context['coverage']['reports']);
        $this->assertSame(2, $context['coverage']['farmers']);
        $this->assertSame(['Corn', 'Rice'], $context['coverage']['crops']);
        $this->assertSame(['dry', 'wet'], $context['coverage']['seasons']);
        $this->assertSame('Feb 10, 2025', $context['coverage']['first_harvest']);
        $this->assertSame('Aug 20, 2026', $context['coverage']['last_harvest']);

        $totals = $context['totals'];
        $this->assertSame(24.5, $totals['yield_tons']);
        $this->assertSame(7.0, $totals['area_ha']);
        $this->assertSame(3.5, $totals['yield_per_ha']);
        $this->assertSame(69000.0, $totals['expenses_php']);
        $this->assertSame(2816.33, $totals['expenses_per_ton']);
    }

    public function test_periods_are_ordered_chronologically_and_compared(): void
    {
        $this->seedHarvests();

        $context = app(HarvestInsightService::class)->context();

        $this->assertSame(
            ['2025 Dry Season', '2026 Wet Season'],
            array_column($context['periods'], 'label')
        );
        $this->assertSame(3.75, $context['periods'][0]['yield_per_ha']);
        $this->assertSame(3.167, $context['periods'][1]['yield_per_ha']);

        $recent = $context['recent_vs_previous'];
        $this->assertSame('2026 Wet Season', $recent['recent_label']);
        $this->assertSame('2025 Dry Season', $recent['previous_label']);
        $this->assertSame(-15.5, $recent['yield_per_ha_change_pct']);
        $this->assertSame(-36.7, $recent['yield_tons_change_pct']);
    }

    public function test_both_leaderboards_and_the_season_split_are_present(): void
    {
        $this->seedHarvests();

        $context = app(HarvestInsightService::class)->context();

        $byReports = $context['top_farmers_by_reports'];
        $byYield = $context['top_farmers_by_yield'];

        $this->assertSame('Farmer Ana', $byReports[0]['farmer']);
        $this->assertSame(3, $byReports[0]['reports']);
        $this->assertSame(2, $byReports[1]['reports']);

        $this->assertSame('Farmer Ana', $byYield[0]['farmer']);
        $this->assertSame(17.0, $byYield[0]['yield_tons']);
        $this->assertSame(7.5, $byYield[1]['yield_tons']);

        $this->assertSame(3.75, $context['by_season']['dry']['yield_per_ha']);
        $this->assertSame(3.167, $context['by_season']['wet']['yield_per_ha']);

        $this->assertSame(['Rice', 'Corn'], array_column($context['by_crop'], 'crop'));
        $this->assertSame(-25.0, $context['by_crop'][0]['yield_per_ha_change_pct']);
        $this->assertSame(16.7, $context['by_crop'][1]['yield_per_ha_change_pct']);
    }

    public function test_a_linked_variety_carries_its_library_note(): void
    {
        $this->seedHarvests();

        $rice = Crop::create([
            'name' => 'Rice',
            'category' => 'Cereal',
            'description' => 'Test crop row',
        ]);

        $report = HarvestReport::first();

        FarmerCrop::create([
            'user_id' => $this->farmerA->id,
            'crop_id' => $rice->id,
            'variety' => 'NSIC Rc 194 (Submarino 1)',
            'variety_key' => 'nsic-rc-194',
            'land_area' => 2.0,
            'planting_date' => '2025-01-01',
            'season' => 'wet',
            'growth_stage' => 'harvested',
            'actual_yield' => 6.0,
            'harvest_report_id' => $report->id,
        ]);

        $context = app(HarvestInsightService::class)->context();

        $this->assertCount(1, $context['varieties']);
        $this->assertSame('NSIC Rc 194 (Submarino 1)', $context['varieties'][0]['variety']);
        $this->assertStringContainsString('Flood-tolerant', $context['varieties'][0]['note']);
    }

    public function test_unlinked_varieties_produce_no_variety_section(): void
    {
        $this->seedHarvests();

        $context = app(HarvestInsightService::class)->context();

        $this->assertSame([], $context['varieties']);
    }

    // ── Chat turn: model unavailable ───────────────────────────────

    public function test_ask_falls_back_to_php_when_no_key_is_configured(): void
    {
        $this->seedHarvests();

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'How did the latest season do?']);

        $response->assertOk()->assertJson(['source' => 'fallback']);

        $reply = $response->json('reply');

        foreach ([
            '### Performance Snapshot',
            '### Recent vs. Past',
            '### What The Data Suggests',
            '### Farmers To Recognise',
            '### Recommended Follow-Up',
        ] as $heading) {
            $this->assertStringContainsString($heading, $reply);
        }

        // Figures must come from the records, not from thin air.
        $this->assertStringContainsString('24.50', $reply);
        $this->assertStringContainsString('-15.5%', $reply);
        $this->assertStringContainsString('Farmer Ana', $reply);
        $this->assertStringContainsString('Wet Season', $reply);

        // Nothing was sent to a provider.
        Http::assertNothingSent();
    }

    public function test_ask_answers_without_erroring_on_an_empty_table(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'How did we do?']);

        $response->assertOk()->assertJson(['source' => 'fallback']);
        $this->assertStringContainsString(
            'No harvest reports have been recorded yet',
            $response->json('reply')
        );
    }

    // ── Chat turn: model available ─────────────────────────────────

    public function test_ask_uses_the_model_when_it_returns_a_reply(): void
    {
        $this->seedHarvests();

        Config::set('services.groq.key', 'test-key');
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'reply' => 'The **5** reports came from **2** farmers.',
                        ]),
                    ],
                ]],
            ]),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'How many reports do we have?']);

        $response->assertOk()->assertJson(['source' => 'ai']);
        $this->assertStringContainsString('**5** reports', $response->json('reply'));

        // The model must have been handed the harvest digest, not a blank slate.
        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];
            $user = collect($messages)->firstWhere('role', 'user');

            return is_array($user)
                && str_contains((string) $user['content'], 'HARVEST REPORT CONTEXT')
                && str_contains((string) $user['content'], '"reports": 5')
                && str_contains((string) $user['content'], "ADMINISTRATOR'S QUESTION:");
        });
    }

    public function test_the_chat_sends_earlier_turns_back_to_the_model(): void
    {
        $this->seedHarvests();

        Config::set('services.groq.key', 'test-key');
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => json_encode(['reply' => 'Same as before.'])]]],
            ]),
        ]);

        $this->actingAs($this->admin)->postJson(route('admin.harvests.ai.ask'), [
            'message'  => 'And the wet season?',
            'messages' => [
                ['role' => 'user', 'content' => 'Which season yielded more per hectare?'],
                ['role' => 'assistant', 'content' => 'The dry season did, at **3.75 MT/ha**.'],
            ],
        ])->assertOk();

        Http::assertSent(function ($request) {
            $messages = $request->data()['messages'] ?? [];
            $user = collect($messages)->firstWhere('role', 'user');

            return is_array($user)
                && str_contains((string) $user['content'], 'CONVERSATION SO FAR')
                && str_contains((string) $user['content'], 'Which season yielded more per hectare?')
                && str_contains((string) $user['content'], 'The dry season did')
                && str_contains((string) $user['content'], 'And the wet season?');
        });
    }

    public function test_an_unusable_model_reply_still_yields_the_php_summary(): void
    {
        $this->seedHarvests();

        Config::set('services.groq.key', 'test-key');
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'sorry, here are some numbers I made up']]],
            ]),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'Summarise everything.']);

        $response->assertOk()->assertJson(['source' => 'fallback']);
        $this->assertStringContainsString('### Performance Snapshot', $response->json('reply'));
        $this->assertStringContainsString('24.50', $response->json('reply'));
    }

    public function test_a_provider_failure_does_not_become_an_error_page(): void
    {
        $this->seedHarvests();

        Config::set('services.groq.key', 'test-key');
        Http::fake(['api.groq.com/*' => Http::response('boom', 500)]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'Anything?'])
            ->assertOk()
            ->assertJson(['source' => 'fallback']);
    }

    public function test_an_empty_question_is_rejected_before_any_model_call(): void
    {
        $this->seedHarvests();

        $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => '   '])
            ->assertStatus(422)
            ->assertJsonValidationErrors(['message']);

        Http::assertNothingSent();
    }

    // ── Guard rails in the prompt itself ───────────────────────────

    public function test_the_prompt_forbids_inventing_weather_events_and_numbers(): void
    {
        $system = HarvestQaPrompt::system();

        // Numbers must still come only from the digest.
        $this->assertStringContainsString('NEVER invent, estimate, guess, or extrapolate a number', $system);

        // Weather may be cited — but only from the admin's own log, and a
        // logged date is not permission to claim damage.
        $this->assertStringContainsString('Weather events may be cited ONLY from `weather_events`', $system);
        $this->assertStringContainsString('NEVER name, invent or imply a typhoon', $system);
        $this->assertStringContainsString('NEVER claim a weather event caused a yield change', $system);
        $this->assertStringContainsString('overlapping_periods', $system);

        // The chat must answer from the digest's own slices, in one voice.
        $this->assertStringContainsString('`recent_vs_previous`', $system);
        $this->assertStringContainsString('`periods`', $system);
        $this->assertStringContainsString('`top_farmers_by_reports`', $system);
        $this->assertStringContainsString('`top_farmers_by_yield`', $system);
        $this->assertStringContainsString('Do not use section headings', $system);

        // Only real JSON context may be quoted back as user input.
        $user = HarvestQaPrompt::user(['coverage' => ['reports' => 5]], 'How many reports?');
        $this->assertStringContainsString('"reports": 5', $user);
        $this->assertStringContainsString('HARVEST REPORT CONTEXT', $user);
        $this->assertStringContainsString("ADMINISTRATOR'S QUESTION:", $user);
        $this->assertStringContainsString('How many reports?', $user);
    }

    // ── Helpers ────────────────────────────────────────────────────

    private function makeUser(string $name, string $firstName, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => $firstName,
            'last_name' => 'Test',
            'email' => strtolower(str_replace(' ', '_', $name)) . '_' . uniqid() . '@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    /**
     * Five reports over two year+season periods, chosen so every aggregate
     * asserted above has a hand-checkable value:
     *
     *   2025 Dry — 4.0 ha, 15.00 MT  (Ana 8.0 + Ana 3.0 + Ben 4.0)
     *   2026 Wet — 3.0 ha,  9.50 MT  (Ana 6.0 + Ben 3.5)
     */
    private function seedHarvests(): void
    {
        foreach ([
            ['user_id' => $this->farmerA->id, 'crop_type' => 'Rice', 'season' => 'dry', 'harvest_date' => '2025-02-10', 'land_area' => 2.0, 'actual_yield_metric_tons' => 8.0, 'actual_total_expenses' => 20000],
            ['user_id' => $this->farmerA->id, 'crop_type' => 'Corn', 'season' => 'dry', 'harvest_date' => '2025-03-05', 'land_area' => 1.0, 'actual_yield_metric_tons' => 3.0, 'actual_total_expenses' => 8000],
            ['user_id' => $this->farmerB->id, 'crop_type' => 'Rice', 'season' => 'dry', 'harvest_date' => '2025-03-20', 'land_area' => 1.0, 'actual_yield_metric_tons' => 4.0, 'actual_total_expenses' => 10000],
            ['user_id' => $this->farmerA->id, 'crop_type' => 'Rice', 'season' => 'wet', 'harvest_date' => '2026-07-15', 'land_area' => 2.0, 'actual_yield_metric_tons' => 6.0, 'actual_total_expenses' => 22000],
            ['user_id' => $this->farmerB->id, 'crop_type' => 'Corn', 'season' => 'wet', 'harvest_date' => '2026-08-20', 'land_area' => 1.0, 'actual_yield_metric_tons' => 3.5, 'actual_total_expenses' => 9000],
        ] as $row) {
            HarvestReport::create($row);
        }
    }
}
