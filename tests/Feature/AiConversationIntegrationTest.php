<?php

namespace Tests\Feature;

use App\AI\AiIntent;
use App\Models\ActivityLog;
use App\Models\HarvestReport;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Full conversation integration: random questions from real users,
 * different roles (admin, farmer), greeting reciprocation, unrelated
 * topics, and the no-database-dump guarantee.
 *
 * The Groq provider is faked so every result is deterministic and fast.
 */
class AiConversationIntegrationTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;

    private User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        // No real provider in integration tests.
        Config::set('services.groq.key', null);

        $this->admin = User::create([
            'name' => 'Insights Admin',
            'first_name' => 'Insights',
            'last_name' => 'Admin',
            'email' => 'admin_' . uniqid() . '@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ]);

        $this->farmer = User::create([
            'name' => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'farmer_' . uniqid() . '@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);
    }

    // ── Admin: greeting reciprocation ────────────────────────────────

    /**
     * @param  array<int, array{0: string, 1: string}>  $cases
     */
    public static function greetingCases(): array
    {
        return [
            'good morning' => ['Good morning', 'Good morning!'],
            'good evening' => ['Good evening', 'Good evening!'],
            'good afternoon' => ['Good afternoon', 'Good afternoon!'],
            'hello' => ['Hello', 'Hello.'],
            'hi' => ['Hi', 'Hello.'],
        ];
    }

    #[\PHPUnit\Framework\Attributes\DataProvider('greetingCases')]
    public function test_admin_greeting_is_reciprocated_same_time_of_day(string $message, string $expectedStart): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => $message]);

        $response->assertOk()->assertJson(['source' => 'conversation']);
        $this->assertStringStartsWith($expectedStart, $response->json('reply'));
        // No harvest data dump for a greeting.
        $this->assertStringNotContainsString('2020', $response->json('reply'));
        $this->assertStringNotContainsString('Performance Snapshot', $response->json('reply'));
        Http::assertNothingSent();
    }

    // ── Admin: unrelated question gets a scoped refusal ──────────────

    public function test_admin_unrelated_question_gets_a_clear_why_not(): void
    {
        // Seed harvest reports so the empty-table short-circuit does not fire.
        HarvestReport::create([
            'user_id' => $this->farmer->id,
            'crop_type' => 'Rice',
            'season' => 'dry',
            'harvest_date' => '2025-02-10',
            'land_area' => 2.0,
            'actual_yield_metric_tons' => 8.0,
            'actual_total_expenses' => 20000,
        ]);

        // A long enough unrelated question to bypass the clarification short-circuit.
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'who won the national election last year?']);

        $response->assertOk();
        $reply = $response->json('reply');

        // A clear reason is given, not a database dump.
        $this->assertStringContainsString('cannot answer that', $reply);
        $this->assertStringContainsString('harvest records', $reply);
        $this->assertStringNotContainsString('election', $reply);
        $this->assertStringNotContainsString('2020', $reply);
        Http::assertNothingSent();
    }

    public function test_admin_unrelated_weather_question_gets_a_scoped_refusal(): void
    {
        HarvestReport::create([
            'user_id' => $this->farmer->id,
            'crop_type' => 'Rice',
            'season' => 'dry',
            'harvest_date' => '2025-02-10',
            'land_area' => 2.0,
            'actual_yield_metric_tons' => 8.0,
            'actual_total_expenses' => 20000,
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'what is the weather forecast in tokyo tomorrow?']);

        $response->assertOk();
        $reply = $response->json('reply');

        $this->assertStringContainsString('cannot answer that', $reply);
        $this->assertStringNotContainsString('32 degrees', $reply);
        $this->assertStringNotContainsString('tokyo', $reply);
        Http::assertNothingSent();
    }

    // ── Admin: farmer activity today ─────────────────────────────────

    public function test_admin_sees_new_farmer_activity_today(): void
    {
        ActivityLog::create([
            'user_id' => $this->farmer->id,
            'actor_id' => $this->farmer->id,
            'type' => ActivityLog::TYPE_CROP_PLANTED,
            'description' => 'Planted Rice (FIELD-001) - 2.00 ha',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        ActivityLog::create([
            'user_id' => $this->farmer->id,
            'actor_id' => $this->farmer->id,
            'type' => ActivityLog::TYPE_HARVEST_CREATED,
            'description' => 'Recorded harvest of Corn (wet)',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        // Old activity must not appear — it is outside today's window.
        ActivityLog::create([
            'user_id' => $this->farmer->id,
            'actor_id' => $this->farmer->id,
            'type' => ActivityLog::TYPE_CROP_UPDATED,
            'description' => 'Old crop update',
            'created_at' => Carbon::now()->subDays(2),
            'updated_at' => Carbon::now()->subDays(2),
        ]);

        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'any new activity today by farmers?']);

        $response->assertOk()->assertJson(['source' => 'data']);
        $reply = $response->json('reply');

        // The farmer's own name appears (Juan Dela Cruz), and today's activities are listed.
        $this->assertStringContainsString('Juan Dela Cruz', $reply);
        $this->assertStringContainsString('activities', $reply);
        $this->assertStringNotContainsString('Old crop update', $reply);
        // No full historical dump.
        $this->assertStringNotContainsString('2020', $reply);
        Http::assertNothingSent();
    }

    // ── Admin: harvest question without dumping all years ────────────

    public function test_admin_single_year_harvest_question_does_not_dump_all_years(): void
    {
        HarvestReport::create([
            'user_id' => $this->farmer->id,
            'crop_type' => 'Rice',
            'season' => 'dry',
            'harvest_date' => '2025-02-10',
            'land_area' => 2.0,
            'actual_yield_metric_tons' => 8.0,
            'actual_total_expenses' => 20000,
        ]);
        HarvestReport::create([
            'user_id' => $this->farmer->id,
            'crop_type' => 'Corn',
            'season' => 'wet',
            'harvest_date' => '2026-08-20',
            'land_area' => 1.0,
            'actual_yield_metric_tons' => 3.5,
            'actual_total_expenses' => 9000,
        ]);

        // With no model configured, the fallback gives a farmer-focused summary
        // rather than dumping every year. The key guarantee: no old-year dump.
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'which year had the least harvest performance?']);

        $response->assertOk();
        $reply = $response->json('reply');

        // No dumping of years not in the data.
        $this->assertStringNotContainsString('2020', $reply);
        $this->assertStringNotContainsString('2021', $reply);
        $this->assertStringNotContainsString('2022', $reply);
        $this->assertStringNotContainsString('2023', $reply);
        $this->assertStringNotContainsString('2024', $reply);
        Http::assertNothingSent();
    }

    // ── Farmer AI: greeting through the farmer chat ──────────────────

    public function test_farmer_chat_greeting_gets_a_greeting(): void
    {
        $response = $this->actingAs($this->farmer)
            ->postJson(route('farmer.ai-chat.send'), [
                'messages' => [['role' => 'user', 'content' => 'good evening']],
            ]);

        $response->assertOk();
        $this->assertSame(AiIntent::GREETING, $response->json('intent'));
        $reply = $response->json('reply');
        $this->assertStringContainsString('How can I help', $reply);
        $this->assertStringNotContainsString('2020', $reply);
        Http::assertNothingSent();
    }

    // ── Farmer AI: unrelated question is scoped ──────────────────────

    public function test_farmer_unrelated_question_is_scoped(): void
    {
        $response = $this->actingAs($this->farmer)
            ->postJson(route('farmer.ai-chat.send'), [
                'messages' => [['role' => 'user', 'content' => 'who won the election?']],
            ]);

        $response->assertOk();
        $this->assertSame(AiIntent::UNSUPPORTED, $response->json('intent'));
        $reply = $response->json('reply');
        $this->assertStringContainsString('Aquilala', $reply);
        $this->assertStringNotContainsString('election', $reply);
        Http::assertNothingSent();
    }

    // ── Role-based data: admin sees all, farmer sees own ─────────────

    public function test_admin_can_access_admin_ai_but_farmer_cannot(): void
    {
        // Admin can reach the subpage.
        $this->actingAs($this->admin)
            ->get(route('admin.harvests.ai'))
            ->assertOk();

        // Farmer is forbidden.
        $this->actingAs($this->farmer)
            ->get(route('admin.harvests.ai'))
            ->assertForbidden();
    }

    // ── No provider: deterministic fallback still works ──────────────

    public function test_conversation_works_with_no_model_provider(): void
    {
        $response = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'good morning']);

        $response->assertOk();
        $this->assertStringStartsWith('Good morning', $response->json('reply'));
        Http::assertNothingSent();
    }

    // ── Follow-up context is preserved ───────────────────────────────

    public function test_admin_bare_why_keeps_previous_farmer_subject(): void
    {
        HarvestReport::create([
            'user_id' => $this->farmer->id,
            'crop_type' => 'Rice',
            'season' => 'wet',
            'harvest_date' => '2026-07-15',
            'land_area' => 2.0,
            'actual_yield_metric_tons' => 6.0,
            'actual_total_expenses' => 22000,
        ]);

        $first = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), ['message' => 'which farmer performs worst?']);
        $first->assertOk();

        $second = $this->actingAs($this->admin)
            ->postJson(route('admin.harvests.ai.ask'), [
                'message' => 'why',
                'messages' => [
                    ['role' => 'user', 'content' => 'which farmer performs worst?'],
                    ['role' => 'assistant', 'content' => $first->json('reply')],
                ],
            ]);
        $second->assertOk();

        $context = $second->json('context');
        $this->assertArrayHasKey('farmer_focus', $context);
    }
}
