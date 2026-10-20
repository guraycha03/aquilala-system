<?php

namespace Tests\Feature;

use App\Models\AssociationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;
use Tests\TestCase;

/**
 * events:remind — the start-soon popup for scheduled events. Marks the
 * event (reminded_at) exactly once; delivery itself is the same
 * fire-and-forget VAPID sender the creation notice uses (a no-op in
 * tests, where phpunit forces the VAPID keys empty).
 */
class EventReminderTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $farmer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin  = $this->makeUser('Ada Admin', 'admin');
        $this->farmer = $this->makeUser('Ben Farmer', 'farmer');
    }

    private function makeUser(string $name, string $role): User
    {
        return User::create([
            'name' => $name,
            'first_name' => explode(' ', $name)[0],
            'last_name' => explode(' ', $name)[1] ?? 'Test',
            'email' => strtolower(str_replace(' ', '_', $name)).'_'.uniqid().'@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    private function makeEvent(array $overrides = []): AssociationEvent
    {
        return AssociationEvent::create(array_merge([
            'title' => 'General Assembly Planning',
            'event_type' => AssociationEvent::TYPE_MEETING,
            'event_date' => now()->toDateString(),
            'start_time' => now()->addMinutes(20)->format('H:i'),
            'end_time' => now()->addMinutes(90)->format('H:i'),
            'location' => 'Association Office',
            'description' => 'Quarterly planning.',
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    private function remind(): void
    {
        $this->artisan('events:remind')->assertExitCode(0);
    }

    // ── Selection ────────────────────────────────────────────────

    public function test_event_starting_within_thirty_minutes_is_reminded(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));
        $event = $this->makeEvent(['start_time' => '10:20']);

        $this->remind();

        $this->assertNotNull($event->fresh()->reminded_at);
    }

    public function test_event_more_than_thirty_minutes_away_is_left_for_later(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));
        $event = $this->makeEvent(['start_time' => '13:00']);

        $this->remind();

        $this->assertNull($event->fresh()->reminded_at);
    }

    public function test_event_already_started_is_not_reminded(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));
        $event = $this->makeEvent(['start_time' => '08:00']);

        $this->remind();

        $this->assertNull($event->fresh()->reminded_at);
    }

    public function test_event_without_a_start_time_is_skipped(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));
        $event = $this->makeEvent(['start_time' => null, 'end_time' => null]);

        $this->remind();

        $this->assertNull($event->fresh()->reminded_at);
    }

    public function test_window_crosses_midnight(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 23:45:00'));
        $event = $this->makeEvent([
            'event_date' => '2026-10-25',
            'start_time' => '00:10',
        ]);

        $this->remind();

        $this->assertNotNull($event->fresh()->reminded_at);
    }

    // ── One shot per event ──────────────────────────────────────

    public function test_a_reminded_event_is_never_reminded_again(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));
        $event = $this->makeEvent(['start_time' => '10:15']);
        $event->forceFill(['reminded_at' => '2026-10-24 09:47:00'])->save();

        $this->remind();

        $this->assertEquals(
            '2026-10-24 09:47:00',
            $event->fresh()->reminded_at->format('Y-m-d H:i:s')
        );
    }

    public function test_second_run_makes_no_further_changes(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));
        $event = $this->makeEvent(['start_time' => '10:25']);

        $this->remind();
        $first = $event->fresh()->reminded_at;
        $this->assertNotNull($first);

        $this->travelTo(now()->addMinutes(15));
        $this->remind();

        $this->assertEquals(
            $first->format('Y-m-d H:i:s'),
            $event->fresh()->reminded_at->format('Y-m-d H:i:s')
        );
    }

    // ── Rescheduling ─────────────────────────────────────────────

    public function test_moving_a_reminded_event_arms_a_fresh_reminder(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));
        $this->actingAs($this->admin);
        $event = $this->makeEvent(['start_time' => '10:20']);

        $this->remind();
        $this->assertNotNull($event->fresh()->reminded_at);

        // The admin moves it to the afternoon — the same flow the
        // controller uses: update, then the lifecycle hook.
        $event->update(['start_time' => '14:00']);
        \App\Services\ActivityLogger::eventUpdated($event);

        $this->assertNull($event->fresh()->reminded_at);

        // Now the new start is inside the window: it gets its popup.
        $this->travelTo(Carbon::parse('2026-10-24 13:40:00'));
        $this->remind();

        $this->assertNotNull($event->fresh()->reminded_at);
    }

    public function test_editing_without_moving_the_time_keeps_the_fired_reminder(): void
    {
        $this->travelTo(Carbon::parse('2026-10-24 10:00:00'));
        $this->actingAs($this->admin);
        $event = $this->makeEvent(['start_time' => '10:20']);

        $this->remind();
        $fired = $event->fresh()->reminded_at->format('Y-m-d H:i:s');
        $this->assertNotNull($event->fresh()->reminded_at);

        // A title fix must not re-arm anything.
        $event->update(['title' => 'General Assembly Planning (renamed)']);
        \App\Services\ActivityLogger::eventUpdated($event);

        $this->assertEquals(
            $fired,
            $event->fresh()->reminded_at->format('Y-m-d H:i:s')
        );
    }

    // ── Scheduling ──────────────────────────────────────────────

    public function test_scheduler_runs_the_reminder_command(): void
    {
        Artisan::call('schedule:list');

        $this->assertStringContainsString('events:remind', Artisan::output());
    }
}
