<?php

namespace App\Console\Commands;

use App\Models\AssociationEvent;
use App\Models\User;
use App\Services\PushService;
use Illuminate\Console\Command;

/**
 * The start-soon popup. An event only announced itself when it was
 * created — often days ahead — so this sends the outside-the-app push
 * AGAIN when the event's own start time is close (30 minutes), the
 * moment a member actually needs to move.
 *
 * One shot per event: reminded_at marks the attempt, so an already
 * notified event is never re-sent, even if no device is subscribed or
 * the push service is unavailable. Every-15-minute scheduling
 * guarantees a run inside the 30-minute window.
 */
class RemindUpcomingEvents extends Command
{
    /** Minutes before an event's start when the reminder goes out. */
    private const LEAD_MINUTES = 30;

    protected $signature = 'events:remind';

    protected $description = 'Send the start-soon push for events beginning within the next 30 minutes';

    public function handle(): int
    {
        $from = now();
        $to   = now()->addMinutes(self::LEAD_MINUTES);

        // The SQL only does a coarse date guard (the window may cross
        // midnight, hence two dates); the exact start instant — built from
        // event_date + start_time across two columns — is checked in PHP,
        // where the math stays readable. The tables are tiny.
        $due = AssociationEvent::query()
            ->whereNull('reminded_at')
            ->whereNotNull('start_time')
            ->whereDate('event_date', '>=', $from->toDateString())
            ->whereDate('event_date', '<=', $to->toDateString())
            ->get()
            ->filter(function (AssociationEvent $event) use ($from, $to): bool {
                $start = $event->event_date->copy()
                    ->setTimeFromTimeString($event->start_time->format('H:i:s'));

                return $start->gte($from) && $start->lte($to);
            });

        if ($due->isEmpty()) {
            $this->info('No events due for a start-soon reminder.');

            return self::SUCCESS;
        }

        $farmerIds = User::where('role', 'farmer')->pluck('id');

        foreach ($due as $event) {
            // Marked BEFORE sending: exactly one attempt per event, whatever
            // the push does afterwards (fail-quiet by design).
            AssociationEvent::whereKey($event->id)->update(['reminded_at' => now()]);

            $line = 'Starts at ' . $event->start_time->format('g:i A');
            $body = $event->location ? "{$line} — {$event->location}" : $line;

            // Farmers open their calendar. Store owners are not on the
            // association's calendar and have no calendar screen, so a
            // start-soon reminder is not for them.
            PushService::sendToUsers(
                $farmerIds,
                "Starting soon: {$event->title}",
                $body,
                route('farmer.calendar.index'),
                'event-' . $event->id . '-soon'
            );
        }

        $this->info("Reminded about {$due->count()} event(s).");

        return self::SUCCESS;
    }
}
