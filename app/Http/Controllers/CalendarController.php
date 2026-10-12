<?php

namespace App\Http\Controllers;

use App\Models\AssociationEvent;
use App\Services\ActivityLogger;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * The association's shared calendar — admin creates events (never on past
 * dates), farmers read them. Store accounts have no routes here at all.
 */
class CalendarController extends Controller
{
    public function index(Request $request)
    {
        [$year, $month] = $this->resolveMonth($request);
        $monthStart = Carbon::create($year, $month, 1);

        // The grid spans whole weeks, so it can reach into the previous and
        // next month — fetch that whole window so every visible day is marked.
        $gridStart = $monthStart->copy()->startOfWeek();
        $gridEnd = $monthStart->copy()->endOfMonth()->endOfWeek();

        $events = AssociationEvent::whereBetween('event_date', [
                $gridStart->toDateString(),
                $gridEnd->toDateString(),
            ])
            ->orderBy('event_date')
            ->orderBy('start_time')
            ->get();

        $todayEvents = AssociationEvent::whereDate('event_date', now()->toDateString())
            ->orderBy('start_time')
            ->get();

        return view($this->isAdmin() ? 'admin.calendar.index' : 'farmer.calendar.index', [
            'year'         => $year,
            'month'        => $month,
            'monthLabel'   => $monthStart->format('F Y'),
            'prevUrl'      => $request->fullUrlWithQuery(['month' => $monthStart->copy()->subMonthNoOverflow()->format('Y-m')]),
            'nextUrl'      => $request->fullUrlWithQuery(['month' => $monthStart->copy()->addMonthNoOverflow()->format('Y-m')]),
            'events'       => $events,
            'todayEvents'  => $todayEvents,
        ]);
    }

    public function events(Request $request)
    {
        unset($request);

        return view($this->isAdmin() ? 'admin.calendar.events' : 'farmer.calendar.events', [
            'upcoming' => AssociationEvent::upcoming()->get(),
            'past'     => AssociationEvent::past()->paginate(15)->withQueryString(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules(), $this->messages());
        $validated['created_by'] = Auth::id();

        $event = AssociationEvent::create($validated);

        ActivityLogger::eventCreated($event);

        return back()->with('success', sprintf(
            'Event "%s" added to the calendar for %s.',
            $event->title,
            $event->event_date->format('M d, Y')
        ));
    }

    public function update(Request $request, AssociationEvent $event)
    {
        $originalDate = $event->event_date->toDateString();

        $rules = $this->rules();
        // Keep an already-past event editable (fix a typo, move its time)
        // but never let its date travel further into the past.
        $rules['event_date'] = ['required', 'date', function ($attribute, $value, $fail) use ($originalDate) {
            if ((string) $value !== $originalDate && Carbon::parse($value)->lt(today())) {
                $fail('An event cannot be moved to a past date.');
            }
        }];

        try {
            $validated = $request->validate($rules, $this->messages());
        } catch (\Illuminate\Validation\ValidationException $e) {
            // Re-open the modal in edit mode so the errors are readable.
            return back()->withErrors($e->errors())
                ->withInput()
                ->with('event_edit_id', $event->id);
        }

        $event->update($validated);

        ActivityLogger::eventUpdated($event);

        return back()->with('success', "Event \"{$event->title}\" updated.");
    }

    public function destroy(AssociationEvent $event)
    {
        $title = $event->title;

        ActivityLogger::eventDeleted($event);
        $event->delete();

        return back()->with('success', "Event \"{$title}\" removed from the calendar.");
    }

    // ── Shared bits ──────────────────────────────────────────────

    private function isAdmin(): bool
    {
        return Auth::user()?->role === 'admin';
    }

    private function rules(): array
    {
        return [
            'title'       => 'required|string|max:120',
            'event_type'  => ['required', Rule::in(array_keys(AssociationEvent::TYPES))],
            'event_date'  => 'required|date|after_or_equal:today',
            'start_time'  => 'nullable|date_format:H:i',
            'end_time'    => ['nullable', 'date_format:H:i', $this->endAfterStart()],
            'location'    => 'nullable|string|max:255',
            'description' => 'nullable|string|max:1000',
        ];
    }

    private function messages(): array
    {
        return [
            'event_date.after_or_equal' => 'Events cannot be added on a past date.',
            'event_type.in'            => 'Choose a valid event type.',
        ];
    }

    private function endAfterStart(): \Closure
    {
        return function ($attribute, $value, $fail) {
            $start = request()->input('start_time');

            if ($value && ! $start) {
                $fail('An end time needs a start time.');
            } elseif ($value && $start && $value <= $start) {
                $fail('The end time must be after the start time.');
            }
        };
    }

    /** ?month=YYYY-MM in the URL, else the current month. */
    private function resolveMonth(Request $request): array
    {
        $raw = (string) $request->query('month', '');

        if (preg_match('/^(\d{4})-(0[1-9]|1[0-2])$/', $raw, $m)) {
            return [(int) $m[1], (int) $m[2]];
        }

        return [(int) now()->format('Y'), (int) now()->format('n')];
    }
}
