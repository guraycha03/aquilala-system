<?php

namespace Tests\Feature;

use App\AI\AiResponseValidatorAi;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * AI2 — the second model that re-evaluates AI1's answer before it reaches
 * the user.
 *
 * AI2 judges relevance, proportionality, and conversational awareness:
 *   - A greeting gets a greeting back, not a harvest report.
 *   - A question about one year does not get a 2020–2026 database dump.
 *   - An unrelated topic gets a clear "I cannot answer that" explanation.
 *
 * The provider is faked in every test so results are deterministic.
 */
class AiTwoValidatorTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Config::set('services.groq.key', 'test-key');
    }

    // ── AI2 accepts a good, on-topic answer ──────────────────────────

    public function test_ai2_accepts_an_on_topic_greeting_reply(): void
    {
        $this->fakeAi2([
            'verdict' => 'accept',
            'reason' => 'The answer reciprocates the greeting and stays on topic.',
            'suggested_answer' => null,
        ]);

        $result = app(AiResponseValidatorAi::class)->validate(
            'good evening',
            'farmer',
            'Good evening! I am here and ready to help you with your farm today.',
        );

        $this->assertNotNull($result);
        $this->assertSame('accept', $result['verdict']);
    }

    public function test_ai2_accepts_a_proportional_harvest_answer(): void
    {
        $this->fakeAi2([
            'verdict' => 'accept',
            'reason' => 'The answer gives the specific year asked for without dumping all years.',
            'suggested_answer' => null,
        ]);

        $result = app(AiResponseValidatorAi::class)->validate(
            'which year had the least harvest performance?',
            'admin',
            'The 2025 Dry Season had the lowest yield per hectare at 3.167 MT/ha.',
            ['coverage' => ['reports' => 5, 'first_harvest' => 'Feb 10, 2025', 'last_harvest' => 'Aug 20, 2026']]
        );

        $this->assertNotNull($result);
        $this->assertSame('accept', $result['verdict']);
    }

    // ── AI2 rejects answers that do not address the question ─────────

    public function test_ai2_rejects_a_report_dump_when_user_greeted(): void
    {
        $this->fakeAi2([
            'verdict' => 'reject',
            'reason' => 'The user said good evening but the answer talks about harvest reports instead of greeting back.',
            'suggested_answer' => 'Good evening! I am here and ready to help you review Aquilala’s harvest records or talk through a farming question.',
        ]);

        $result = app(AiResponseValidatorAi::class)->validate(
            'good evening',
            'admin',
            'Here is the full harvest summary: 2020 Dry Season had 5 reports, 2020 Wet Season had 3 reports, 2021 Dry Season had 8 reports... [continues for all years]',
            ['coverage' => ['reports' => 50, 'first_harvest' => 'Jan 1, 2020', 'last_harvest' => 'Dec 31, 2026']]
        );

        $this->assertNotNull($result);
        $this->assertSame('reject', $result['verdict']);
        $this->assertStringContainsString('Good evening', $result['suggested_answer']);
    }

    public function test_ai2_rejects_a_full_database_dump_for_a_single_year_question(): void
    {
        $this->fakeAi2([
            'verdict' => 'reject',
            'reason' => 'The user asked about one year but the answer dumps every year from 2020 to 2026.',
            'suggested_answer' => 'The 2025 Dry Season had the lowest yield per hectare at 3.167 MT/ha.',
        ]);

        $result = app(AiResponseValidatorAi::class)->validate(
            'which year had the least harvest performance?',
            'admin',
            '2020 Dry Season: 2.5 MT/ha. 2020 Wet Season: 3.0 MT/ha. 2021 Dry Season: 2.8 MT/ha. 2021 Wet Season: 3.2 MT/ha. 2022 Dry Season: ... [all years dumped]',
            ['coverage' => ['reports' => 30, 'first_harvest' => 'Feb 10, 2020', 'last_harvest' => 'Aug 20, 2026']]
        );

        $this->assertNotNull($result);
        $this->assertSame('reject', $result['verdict']);
        $this->assertStringNotContainsString('2020', $result['suggested_answer']);
        $this->assertStringContainsString('3.167', $result['suggested_answer']);
    }

    public function test_ai2_revises_an_off_topic_answer_to_a_scoped_refusal(): void
    {
        $this->fakeAi2([
            'verdict' => 'revise',
            'reason' => 'The question is about the weather forecast, which the system does not cover.',
            'suggested_answer' => 'I cannot answer that from Aquilala’s harvest records. I cover harvest yields, seasons, crops, farmers, costs, and the activity log. Ask me about a harvest or a season comparison instead.',
        ]);

        $result = app(AiResponseValidatorAi::class)->validate(
            'will it rain tomorrow in Manila?',
            'farmer',
            'The weather in Manila tomorrow will be sunny with a high of 32 degrees.',
        );

        $this->assertNotNull($result);
        $this->assertSame('revise', $result['verdict']);
        $this->assertStringContainsString('I cannot answer that', $result['suggested_answer']);
    }

    // ── AI2 returns null when the provider is unavailable ────────────

    public function test_ai2_returns_null_when_no_key_is_configured(): void
    {
        Config::set('services.groq.key', null);

        $result = app(AiResponseValidatorAi::class)->validate(
            'good evening',
            'farmer',
            'Good evening!',
        );

        $this->assertNull($result);
    }

    public function test_ai2_returns_null_on_provider_error(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response(['error' => 'rate limited'], 429),
        ]);

        $result = app(AiResponseValidatorAi::class)->validate(
            'good evening',
            'farmer',
            'Good evening!',
        );

        $this->assertNull($result);
    }

    public function test_ai2_returns_null_on_malformed_json(): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => 'not json at all']]],
            ]),
        ]);

        $result = app(AiResponseValidatorAi::class)->validate(
            'good evening',
            'farmer',
            'Good evening!',
        );

        $this->assertNull($result);
    }

    // ── Role-based context is included in the validation ─────────────

    public function test_ai2_receives_the_user_role(): void
    {
        $this->fakeAi2([
            'verdict' => 'accept',
            'reason' => 'OK',
            'suggested_answer' => null,
        ]);

        app(AiResponseValidatorAi::class)->validate(
            'what products do I need for rice?',
            'store_owner',
            'For rice, Aquilala lists urea and rice seeds.',
        );

        Http::assertSent(function ($request) {
            $data = $request->data() ?? [];
            $messages = $data['messages'] ?? [];
            $system = $messages[0]['content'] ?? '';

            return is_string($system) && str_contains($system, 'store_owner');
        });
    }

    // ── Helpers ──────────────────────────────────────────────────────

    /**
     * @param  array<string, mixed>  $payload
     */
    private function fakeAi2(array $payload): void
    {
        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [[
                    'message' => ['content' => json_encode($payload)],
                ]],
            ]),
        ]);
    }
}
