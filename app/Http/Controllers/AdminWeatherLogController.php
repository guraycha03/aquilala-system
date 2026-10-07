<?php

namespace App\Http\Controllers;

use App\Models\HarvestReport;
use App\Models\WeatherLog;
use App\Services\ActivityLogger;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\View\View;

/**
 * Admin Settings → Weather Log.
 *
 * The settings tabs are closures for simple preference pages, but this one is a
 * real log with create and delete actions, so it gets a controller — the same
 * shape AdminAuditController already uses for the Audit Log tab.
 *
 * Everything here writes rows the harvest analysis reads; no live weather feed
 * is involved, deliberately.
 */
class AdminWeatherLogController extends Controller
{
    /**
     * The log itself, newest first, plus the counts the header shows.
     */
    public function index(Request $request): View
    {
        $events = WeatherLog::with('logger')
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get();

        // How many logged events actually sit inside the harvest data's span.
        // Those are the ones the AI analysis can line up against a period;
        // the rest are still kept, just not yet correlatable.
        $harvestSpan = HarvestReport::selectRaw('MIN(harvest_date) as first, MAX(harvest_date) as last')
            ->first();

        $inSpan = $harvestSpan?->first
            ? $events->filter(fn (WeatherLog $event) => $event->overlaps(
                Carbon::parse($harvestSpan->first),
                Carbon::parse($harvestSpan->last)
            ))->count()
            : 0;

        $stats = [
            'total'   => $events->count(),
            'severe'  => $events->whereIn('severity', ['severe', 'extreme'])->count(),
            'in_span' => $inSpan,
            'types'   => $events->countBy(fn (WeatherLog $event) => $event->event_type),
        ];

        return view('admin.settings.weather', [
            'events' => $events,
            'stats'  => $stats,
        ]);
    }

    /**
     * Add one event. Returns JSON so the page can add the row without a round
     * trip and keep the admin's scroll position.
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'event_type' => ['required', 'string', 'in:' . implode(',', array_keys(WeatherLog::TYPES))],
            'severity'   => ['required', 'string', 'in:' . implode(',', array_keys(WeatherLog::SEVERITIES))],
            'title'      => ['required', 'string', 'max:120'],
            'start_date' => ['required', 'date', 'before_or_equal:today'],
            'end_date'   => ['nullable', 'date', 'after_or_equal:start_date'],
            'notes'      => ['nullable', 'string', 'max:1000'],
        ]);

        $data['logged_by'] = $request->user()->id;

        $event = WeatherLog::create($data);

        ActivityLogger::weatherLogCreated($event);

        return response()->json([
            'ok'    => true,
            'event' => $this->payload($event),
            'flash' => "{$event->typeLabel()} logged for {$event->dateRangeLabel()}.",
        ]);
    }

    /**
     * Remove one event. Kept as an explicit action rather than a bulk purge so
     * a mis-logged date can be corrected without touching anything else.
     */
    public function destroy(Request $request, WeatherLog $weatherLog): RedirectResponse|JsonResponse
    {
        ActivityLogger::weatherLogDeleted($weatherLog);
        $weatherLog->delete();

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'flash' => 'Weather event removed from the log.']);
        }

        return back()->with('success', 'Weather event removed from the log.');
    }

    /**
     * Shape a row for the view's JSON responses.
     *
     * @return array<string, mixed>
     */
    private function payload(WeatherLog $event): array
    {
        return [
            'id'           => $event->id,
            'event_type'   => $event->event_type,
            'type_label'   => $event->typeLabel(),
            'severity'     => $event->severity,
            'severity_label' => $event->severityLabel(),
            'title'        => $event->title,
            'start_date'   => $event->start_date->format('Y-m-d'),
            'end_date'     => $event->end_date?->format('Y-m-d'),
            'date_label'   => $event->dateRangeLabel(),
            'notes'        => $event->notes,
            'logger'       => $event->logger?->name,
            'delete_url'   => route('admin.settings.weather.destroy', $event),
        ];
    }
}
