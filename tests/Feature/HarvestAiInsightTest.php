<?php

namespace Tests\Feature;

use App\AI\Prompts\HarvestQaPrompt;
use App\Models\ActivityLog;
use App\Models\Crop;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\User;
use App\Services\ActivityLogger;
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

    public function test_the_chat_offers_a_data_driven_why_about_the_weakest_performer(): void
    {
        $this->seedHarvests();

        // The chip is built from the digest, so it always names a real
        // farmer with a real figure and teaches the follow-up by example.
        $this->actingAs($this->admin)
            ->get(route('admin.harvests.ai'))
            ->assertOk()
            ->assertSee('Why does Farmer Ana sit at 3.400 MT/ha?', false);
    }

    public function test_the_harvest_page_does_not_duplicate_the_ai_subpage_button(): void
    {
        $this->seedHarvests();

        $this->actingAs($this->admin)
            ->get(route('admin.harvests.index'))
            ->assertOk()
            ->assertDontSee('title="Explain this harvest data with AI"', false);
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

    public function test_an_empty_model_reply_still_yields_the_php_summary(): void
    {
        $this->seedHarvests();

        Config::set('services.groq.key', 'test-key');
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"reply": "   "}']]],
            ]),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'Summarise everything.']);

        $response->assertOk()->assertJson(['source' => 'fallback']);
        $this->assertStringContainsString('### Performance Snapshot', $response->json('reply'));
        $this->assertStringContainsString('24.50', $response->json('reply'));
    }

    public function test_a_reply_that_drops_the_json_envelope_is_still_an_answer(): void
    {
        $this->seedHarvests();

        Config::set('services.groq.key', 'test-key');
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'The **5** reports came from **2** farmers, **24.50 MT** in total.']]],
            ]),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'Summarise everything.']);

        // A real, on-topic sentence beats being handed the generic report.
        $response->assertOk()->assertJson(['source' => 'ai']);
        $this->assertStringContainsString('**24.50 MT**', $response->json('reply'));
        $this->assertStringNotContainsString('### Performance Snapshot', $response->json('reply'));
    }

    public function test_an_error_shaped_envelope_never_reaches_the_administrator(): void
    {
        $this->seedHarvests();

        Config::set('services.groq.key', 'test-key');
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => '{"error": "rate limited"}']]],
            ]),
        ]);

        $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'Summarise everything.'])
            ->assertOk()
            ->assertJson(['source' => 'fallback']);
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

    // ── Subject resolution: what a follow-up is about ──────────────

    public function test_a_question_about_a_farmer_carries_their_own_dossier(): void
    {
        $this->seedHarvests();
        $this->plantFor($this->farmerA);

        $context = app(HarvestInsightService::class)
            ->ask('Why does Farmer Ana yield so little per hectare?')['context'];

        $focus = $context['farmer_focus'];

        $this->assertSame('Farmer Ana', $focus['farmer']);
        $this->assertSame('named in this question', $focus['resolved_by']);
        $this->assertSame(3, $focus['summary']['reports']);

        // Her own rows, not the association's.
        $this->assertCount(3, $focus['reports']);
        $this->assertSame('Rice', $focus['reports'][2]['crop']);
        $this->assertNotEmpty($focus['plantings']);
        $this->assertNotEmpty($focus['activity_log']);

        // Every reading is a comparison or a count the records support.
        $readings = implode("\n", $focus['candidate_readings']);
        $this->assertStringContainsString('MT/ha', $readings);
        $this->assertStringContainsString('ha** per report', $readings);
        $this->assertStringContainsString('harvest reports filed', $readings);
        $this->assertStringNotContainsString('caused by', $readings);
    }

    public function test_a_bare_why_keeps_the_farmer_the_last_turn_was_about(): void
    {
        $this->seedHarvests();

        $context = app(HarvestInsightService::class)->ask('why', [
            ['role' => 'user', 'content' => 'Which farmer performs the worst in aquilala?'],
            ['role' => 'assistant', 'content' => 'Farmer Ana has the lowest yield per hectare, at 3.400 MT/ha.'],
        ])['context'];

        $this->assertSame('Farmer Ana', $context['farmer_focus']['farmer']);
        $this->assertSame('named earlier in the conversation', $context['farmer_focus']['resolved_by']);
    }

    public function test_a_ranking_question_focusses_the_worst_performer(): void
    {
        $this->seedHarvests();

        $context = app(HarvestInsightService::class)
            ->ask('which farmer performs the worst in aquilala')['context'];

        // Ana files 3 reports over 5.0 ha = 3.4 MT/ha; Ben 2 over 2.0 = 3.75.
        $this->assertSame('Farmer Ana', $context['farmer_focus']['farmer']);
        $this->assertSame('ranking asked for in this question', $context['farmer_focus']['resolved_by']);

        $best = app(HarvestInsightService::class)
            ->ask('which farmer performs best here')['context'];

        $this->assertSame('Farmer Ben', $best['farmer_focus']['farmer']);
    }

    public function test_a_question_with_no_subject_carries_no_farmer_focus(): void
    {
        $this->seedHarvests();

        $context = app(HarvestInsightService::class)
            ->ask('How did the latest season compare with the one before it?')['context'];

        $this->assertArrayNotHasKey('farmer_focus', $context);
    }

    public function test_the_offline_answer_for_a_farmer_is_their_own_numbers(): void
    {
        $this->seedHarvests();
        $this->plantFor($this->farmerA);

        $reply = app(HarvestInsightService::class)
            ->ask('Why is Farmer Ana behind?')['reply'];

        $this->assertStringContainsString('Farmer Ana', $reply);
        $this->assertStringContainsString('Worth verifying from the records', $reply);
        $this->assertStringNotContainsString('### Performance Snapshot', $reply);
        $this->assertStringContainsString('3.400', $reply);
    }

    public function test_the_prompt_forbids_blaming_a_person_for_their_yield(): void
    {
        $system = HarvestQaPrompt::system();

        $this->assertStringContainsString('Never judge a person\'s character', $system);
        $this->assertStringContainsString('candidate_readings', $system);
        $this->assertStringContainsString('Never write "because"', $system);
        $this->assertStringContainsString('resolved_by', $system);
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

    /**
     * Give one farmer the planting side of the picture: crop batches (one
     * linked to a report, one planted against the crop's documented season)
     * plus the activity-log entries the dossier reads.
     */
    private function plantFor(User $farmer): void
    {
        $rice = Crop::create([
            'name' => 'Rice',
            'category' => 'Cereal',
            'description' => 'Test crop row',
        ]);

        $cane = Crop::create([
            'name' => 'Sugarcane',
            'category' => 'Cash Crop',
            'description' => 'Test crop row',
        ]);

        FarmerCrop::create([
            'user_id' => $farmer->id,
            'crop_id' => $rice->id,
            'variety' => 'NSIC Rc 194 (Submarino 1)',
            'variety_key' => 'nsic-rc-194',
            'batch_name' => 'RICE-FIELD1-010125',
            'land_area' => 2.0,
            'planting_date' => '2025-01-01',
            'season' => 'dry',
            'growth_stage' => 'harvested',
            'actual_yield' => 8.0,
            'harvest_report_id' => HarvestReport::where('user_id', $farmer->id)->first()->id,
        ]);

        // Sugarcane is documented as a dry-season crop; this batch went in
        // during the wet season, which is the mismatch the dossier reports.
        FarmerCrop::create([
            'user_id' => $farmer->id,
            'crop_id' => $cane->id,
            'batch_name' => 'SUGARCANE-FIELD1-150726',
            'land_area' => 1.5,
            'planting_date' => '2026-07-15',
            'season' => 'wet',
            'growth_stage' => 'vegetative',
            'harvest_report_id' => null,
        ]);

        foreach ([
            [ActivityLog::TYPE_CROP_PLANTED, 'Planted Rice (RICE-FIELD1-010125) - 2.00 ha'],
            [ActivityLog::TYPE_LAND_UPDATED, 'Land area updated for Rice'],
            [ActivityLog::TYPE_CROP_HARVESTED, 'Harvested Rice (RICE-FIELD1-010125)'],
            [ActivityLog::TYPE_HARVEST_CREATED, 'Recorded harvest of Rice (dry)'],
            [ActivityLog::TYPE_CROP_PLANTED, 'Planted Sugarcane (SUGARCANE-FIELD1-150726) - 1.50 ha'],
        ] as [$type, $description]) {
            ActivityLogger::log($type, $description, null, [], $farmer);
        }
    }

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
