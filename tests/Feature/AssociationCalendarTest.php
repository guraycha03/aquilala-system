<?php

namespace Tests\Feature;

use App\Models\AssociationEvent;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssociationCalendarTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $farmer;
    private User $store;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin  = $this->makeUser('Ada Admin', 'admin');
        $this->farmer = $this->makeUser('Ben Farmer', 'farmer');
        $this->store  = $this->makeUser('Sam Store', 'store_owner');
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
            'title' => 'Sample Association Event',
            'event_type' => AssociationEvent::TYPE_MEETING,
            'event_date' => now()->addDays(7)->toDateString(),
            'start_time' => '09:00',
            'end_time' => '11:00',
            'location' => 'Association Office',
            'description' => 'A get-together.',
            'created_by' => $this->admin->id,
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title' => 'Free Corn Seeds Distribution',
            'event_type' => AssociationEvent::TYPE_FREE_INPUT,
            'event_date' => now()->addDays(3)->toDateString(),
            'location' => 'Association Warehouse',
            'description' => 'Hybrid seeds for members.',
        ], $overrides);
    }

    // ── Viewing ──────────────────────────────────────────────────

    public function test_admin_calendar_page_renders_with_events(): void
    {
        $event = $this->makeEvent(['title' => 'General Assembly Seminar']);

        $response = $this->actingAs($this->admin)->get(route('admin.calendar.index'));

        $response->assertOk();
        $response->assertSee('Association Calendar');
        $response->assertSee('General Assembly Seminar');
        $response->assertSee(now()->format('F Y'));
    }

    public function test_farmer_can_view_the_calendar_and_sees_events(): void
    {
        $this->makeEvent(['title' => 'Free Fertilizer Release']);

        $response = $this->actingAs($this->farmer)->get(route('farmer.calendar.index'));

        $response->assertOk();
        $response->assertSee('Association Calendar');
        $response->assertSee('Free Fertilizer Release');
    }

    public function test_store_accounts_are_locked_out_of_both_calendars(): void
    {
        $this->actingAs($this->store)->get(route('admin.calendar.index'))->assertForbidden();
        $this->actingAs($this->store)->get(route('farmer.calendar.index'))->assertForbidden();
        $this->actingAs($this->store)->get(route('admin.calendar.events'))->assertForbidden();
        $this->actingAs($this->store)->get(route('farmer.calendar.events'))->assertForbidden();
    }

    public function test_guests_are_sent_to_login(): void
    {
        $this->get(route('admin.calendar.index'))->assertRedirect(route('login'));
    }

    public function test_the_events_list_splits_upcoming_and_past(): void
    {
        $this->makeEvent(['title' => 'Upcoming Seminar', 'event_date' => now()->addDays(9)->toDateString()]);
        $this->makeEvent(['title' => 'Old Meeting', 'event_date' => now()->subDays(9)->toDateString()]);

        $response = $this->actingAs($this->farmer)->get(route('farmer.calendar.events'));

        $response->assertOk();
        $response->assertSee('Upcoming Events');
        $response->assertSee('Past Events');
        $response->assertSee('Upcoming Seminar');
        $response->assertSee('Old Meeting');
    }

    public function test_todays_events_appear_in_the_today_card(): void
    {
        $this->makeEvent(['title' => 'Distribution Today', 'event_date' => now()->toDateString()]);

        $response = $this->actingAs($this->farmer)->get(route('farmer.calendar.index'));

        $response->assertOk();
        $response->assertSee('Today');
        $response->assertSee('Distribution Today');
    }

    // ── Creating ─────────────────────────────────────────────────

    public function test_admin_can_add_a_future_event(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.calendar.events.store'), $this->payload())
            ->assertSessionHas('success');

        $event = AssociationEvent::sole();
        $this->assertSame('Free Corn Seeds Distribution', $event->title);
        $this->assertSame(AssociationEvent::TYPE_FREE_INPUT, $event->event_type);
        $this->assertSame($this->admin->id, $event->created_by);
        $this->assertNull($event->start_time); // all-day
    }

    public function test_admin_cannot_add_an_event_on_a_past_date(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.calendar.events.store'), $this->payload([
                'event_date' => now()->subDay()->toDateString(),
            ]))
            ->assertSessionHasErrors('event_date');

        $this->assertSame(0, AssociationEvent::count());
    }

    public function test_an_all_day_event_needs_no_times(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.calendar.events.store'), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame(1, AssociationEvent::count());
    }

    public function test_end_time_must_be_after_start_time(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.calendar.events.store'), $this->payload([
                'start_time' => '14:00',
                'end_time' => '13:00',
            ]))
            ->assertSessionHasErrors('end_time');

        $this->assertSame(0, AssociationEvent::count());
    }

    public function test_an_end_time_without_a_start_time_is_rejected(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.calendar.events.store'), $this->payload([
                'end_time' => '16:00',
            ]))
            ->assertSessionHasErrors('end_time');
    }

    public function test_event_type_must_be_one_of_the_known_types(): void
    {
        $this->actingAs($this->admin)
            ->post(route('admin.calendar.events.store'), $this->payload([
                'event_type' => 'party',
            ]))
            ->assertSessionHasErrors('event_type');

        $this->assertSame(0, AssociationEvent::count());
    }

    // ── Editing & deleting ───────────────────────────────────────

    public function test_admin_cannot_move_an_event_into_the_past(): void
    {
        $event = $this->makeEvent();

        $this->actingAs($this->admin)
            ->put(route('admin.calendar.events.update', $event), [
                'title' => $event->title,
                'event_type' => $event->event_type,
                'event_date' => now()->subDays(3)->toDateString(),
            ])
            ->assertSessionHasErrors('event_date');

        $this->assertSame(now()->addDays(7)->toDateString(), $event->fresh()->event_date->toDateString());
    }

    public function test_admin_can_edit_a_past_events_details(): void
    {
        $event = $this->makeEvent([
            'title' => 'Old Title',
            'event_date' => now()->subDays(5)->toDateString(),
        ]);

        $this->actingAs($this->admin)
            ->put(route('admin.calendar.events.update', $event), [
                'title' => 'Corrected Title',
                'event_type' => AssociationEvent::TYPE_MEETING,
                'event_date' => $event->event_date->toDateString(), // unchanged
            ])
            ->assertSessionHas('success');

        $this->assertSame('Corrected Title', $event->fresh()->title);
    }

    public function test_admin_can_delete_an_event(): void
    {
        $event = $this->makeEvent();

        $this->actingAs($this->admin)
            ->delete(route('admin.calendar.events.destroy', $event))
            ->assertSessionHas('success');

        $this->assertSame(0, AssociationEvent::count());
    }

    // ── Farmers are read-only ────────────────────────────────────

    public function test_a_farmer_cannot_add_edit_or_delete_events(): void
    {
        $event = $this->makeEvent();

        $this->actingAs($this->farmer)
            ->post(route('admin.calendar.events.store'), $this->payload())
            ->assertForbidden();

        $this->actingAs($this->farmer)
            ->put(route('admin.calendar.events.update', $event), ['title' => 'X', 'event_type' => 'meeting', 'event_date' => now()->addDay()->toDateString()])
            ->assertForbidden();

        $this->actingAs($this->farmer)
            ->delete(route('admin.calendar.events.destroy', $event))
            ->assertForbidden();

        $this->assertSame(1, AssociationEvent::count()); // untouched
    }

    // ── Month navigation ─────────────────────────────────────────

    public function test_the_calendar_can_move_to_another_month(): void
    {
        $nextMonth = now()->addMonthNoOverflow();

        $response = $this->actingAs($this->admin)
            ->get(route('admin.calendar.index', ['month' => $nextMonth->format('Y-m')]));

        $response->assertOk();
        $response->assertSee($nextMonth->format('F Y'));
    }

    // ── Month events panel (left column) ─────────────────────────

    public function test_the_side_panel_lists_the_displayed_months_events(): void
    {
        // A today-dated event is always inside the current month's panel.
        $this->makeEvent(['title' => 'Monthly Panel Item', 'event_date' => now()->toDateString()]);

        $response = $this->actingAs($this->admin)->get(route('admin.calendar.index'));

        $response->assertOk();
        $response->assertSee('Events &middot; '.now()->format('F Y'), false);
        $response->assertSee('cal-split-events');
        $response->assertSee('cal-split-cal');
        $response->assertSee('Monthly Panel Item');
    }

    public function test_the_side_panel_keeps_its_card_when_the_month_has_no_events(): void
    {
        $response = $this->actingAs($this->admin)
            ->get(route('admin.calendar.index', ['month' => '2025-01']));

        $response->assertOk();
        $response->assertSee('Events &middot; January 2025', false);
        $response->assertSee('cal-split-events');
        $response->assertSee('No events this month.');
        $response->assertSee('Association events for January 2025');
    }

    public function test_farmers_see_the_month_panel_too(): void
    {
        $response = $this->actingAs($this->farmer)->get(route('farmer.calendar.index'));

        $response->assertOk();
        $response->assertSee('Events &middot; '.now()->format('F Y'), false);
        $response->assertSee('cal-split-events');
        $response->assertSee('cal-split-cal');
    }
}
