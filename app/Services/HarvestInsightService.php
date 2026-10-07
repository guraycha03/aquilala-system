<?php

namespace App\Services;

use App\AI\AiLlmClient;
use App\AI\Prompts\HarvestQaPrompt;
use App\Helpers\SeasonHelper;
use App\Models\ActivityLog;
use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use App\Models\WeatherLog;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * HarvestInsightService — powers the admin "AI Harvest Insights" chat.
 *
 * Split in two halves, mirroring how the rest of the app treats the model:
 *
 *   1. context() is pure PHP over harvest_reports. It is the only thing that
 *      touches the database, and it produces every number the chat may cite.
 *   2. ask() hands that digest to the LLM for wording and reasoning about the
 *      question the administrator actually typed.
 *
 * If the model is unavailable, empty, or returns something unusable, the
 * caller gets fallback() — the same headings built straight from context().
 * A null model reply is a normal outcome here, never an exception.
 */
class HarvestInsightService
{
    /**
     * The report has five sections of real analysis; the default 1200-token
     * budget of AiLlmClient trims that off mid-sentence.
     */
    private const MAX_TOKENS = 2500;

    /** How many farmers appear in each leaderboard. */
    private const TOP_FARMERS = 8;

    /** Rows of a farmer's own dossier that go to the model, newest first. */
    private const FOCUS_REPORTS = 20;

    private const FOCUS_PLANTINGS = 20;

    private const FOCUS_ACTIVITY = 30;

    /**
     * The only activity types the chat may reason with: planting, growth-stage
     * changes, land, and the harvest record itself. Logins, quotations and
     * store reviews say nothing about a yield.
     */
    private const FARM_ACTIVITY_TYPES = [
        ActivityLog::TYPE_CROP_PLANTED,
        ActivityLog::TYPE_CROP_UPDATED,
        ActivityLog::TYPE_CROP_HARVESTED,
        ActivityLog::TYPE_CROP_DELETED,
        ActivityLog::TYPE_LAND_UPDATED,
        ActivityLog::TYPE_HARVEST_CREATED,
        ActivityLog::TYPE_HARVEST_DELETED,
    ];

    public function __construct(
        private readonly AiLlmClient $llm,
        private readonly CropLibraryService $crops,
    ) {}

    /**
     * Deterministic digest of the harvest_reports table.
     *
     * @return array<string, mixed>
     */
    public function context(): array
    {
        $reports = HarvestReport::with('farmer')->orderBy('harvest_date')->get();

        if ($reports->isEmpty()) {
            return ['coverage' => ['reports' => 0, 'farmers' => 0, 'crops' => []]];
        }

        $coverage = [
            'reports'       => $reports->count(),
            'farmers'       => $reports->pluck('user_id')->unique()->count(),
            'crops'         => $reports->pluck('crop_type')->filter()->unique()->sort()->values()->all(),
            'seasons'       => $reports->pluck('season')->filter()->unique()->sort()->values()->all(),
            'first_harvest' => $this->dateOf($reports->first()->harvest_date),
            'last_harvest'  => $this->dateOf($reports->last()->harvest_date),
        ];

        $periods = $this->periods($reports);
        $weather = $this->weatherEvents($reports, $periods);
        $periods = $this->attachWeather($periods, $weather);

        return [
            'coverage'           => $coverage + [
                // Lets the reader (and the model) see at a glance whether the
                // admin has actually kept a weather log yet.
                'weather_events_logged' => count($weather),
                'weather_events_overlapping' => count(array_filter(
                    $weather,
                    fn (array $event) => $event['overlapping_periods'] !== []
                )),
            ],
            'totals'             => $this->summarize($reports),
            'periods'            => $periods,
            'recent_vs_previous' => $this->recentVsPrevious($periods),
            'by_season'          => $this->bySeason($reports),
            'by_crop'            => $this->byCrop($reports),
            'top_farmers_by_reports' => $this->topFarmers($reports, 'reports'),
            'top_farmers_by_yield'   => $this->topFarmers($reports, 'yield'),
            'varieties'          => $this->varieties($reports),
            'weather_events'     => $weather,
            'climatology_reference' => $this->climatology($coverage['seasons']),
        ];
    }

    /**
     * Answer one question from the chat on the AI Harvest Insights subpage.
     *
     * The digest is rebuilt for every turn so an answer can never be based on
     * a stale snapshot, and the caller is handed it back unchanged so the
     * conversation can be replayed on the next question.
     *
     * @param  array<int, array{role: string, content: string}>  $history  Prior turns, oldest first.
     * @return array{source: string, reply: string, context: array<string, mixed>}
     */
    public function ask(string $message, array $history = []): array
    {
        $context = $this->context();

        if (($context['coverage']['reports'] ?? 0) === 0) {
            return [
                'source'  => 'fallback',
                'reply'   => 'No harvest reports have been recorded yet, so there is nothing to analyse.',
                'context' => $context,
            ];
        }

        $completion = $this->llm->completeJson([
            ['role' => 'system', 'content' => HarvestQaPrompt::system()],
            ['role' => 'user', 'content' => HarvestQaPrompt::user($context, $message, $history)],
        ], self::MAX_TOKENS);

        $reply = $this->decode($completion['raw'] ?? '', 'reply');

        return [
            'source'  => $reply !== null ? 'ai' : 'fallback',
            'reply'   => $reply ?? $this->fallback($context),
            'context' => $context,
        ];
    }

    // ────────────────────────────────────────────────────────────────
    //  Context builders
    // ────────────────────────────────────────────────────────────────

    /**
     * One aggregate row per harvest year + season, ordered chronologically.
     *
     * @param  Collection<int, HarvestReport>  $reports
     * @return array<int, array<string, mixed>>
     */
    private function periods(Collection $reports): array
    {
        return $reports
            ->groupBy(fn (HarvestReport $r) => $r->harvest_date->format('Y') . '-' . $r->season)
            ->map(function (Collection $rows, string $key) {
                [$year, $season] = explode('-', $key);
                $first = Carbon::parse($rows->min('harvest_date'));

                return $this->summarize($rows) + [
                    'label'        => $year . ' ' . ucfirst($season) . ' Season',
                    'year'         => (int) $year,
                    'season'       => $season,
                    'first_harvest' => $this->dateOf($rows->min('harvest_date')),
                    'last_harvest'  => $this->dateOf($rows->max('harvest_date')),
                    '_sort'        => $first->timestamp,
                ];
            })
            ->sortBy('_sort')
            ->map(fn (array $period) => Arr::except($period, '_sort'))
            ->values()
            ->all();
    }

    /**
     * Totals for a set of reports: area, yield, yield/ha, expenses, cost/ton.
     *
     * @param  Collection<int, HarvestReport>  $rows
     * @return array<string, mixed>
     */
    private function summarize(Collection $rows): array
    {
        $area    = (float) $rows->sum('land_area');
        $yield   = (float) $rows->sum('actual_yield_metric_tons');
        $spend   = (float) $rows->sum('actual_total_expenses');

        return [
            'reports'          => $rows->count(),
            'farmers'          => $rows->pluck('user_id')->unique()->count(),
            'area_ha'          => round($area, 2),
            'yield_tons'       => round($yield, 2),
            'yield_per_ha'     => $area > 0 ? round($yield / $area, 3) : null,
            'expenses_php'     => round($spend, 2),
            'expenses_per_ha'  => $area > 0 ? round($spend / $area, 2) : null,
            'expenses_per_ton' => $yield > 0 ? round($spend / $yield, 2) : null,
        ];
    }

    /**
     * Latest period against the one before it — the "recent vs past" question.
     *
     * @param  array<int, array<string, mixed>>  $periods
     * @return array<string, mixed>|null
     */
    private function recentVsPrevious(array $periods): ?array
    {
        if (count($periods) < 2) {
            return null;
        }

        $recent   = $periods[count($periods) - 1];
        $previous = $periods[count($periods) - 2];

        return [
            'recent_label'        => $recent['label'],
            'previous_label'      => $previous['label'],
            'recent_reports'      => $recent['reports'],
            'previous_reports'    => $previous['reports'],
            'recent_yield_per_ha' => $recent['yield_per_ha'],
            'previous_yield_per_ha' => $previous['yield_per_ha'],
            'yield_per_ha_change_pct' => $this->pctChange($previous['yield_per_ha'], $recent['yield_per_ha']),
            'recent_expenses_per_ton' => $recent['expenses_per_ton'],
            'previous_expenses_per_ton' => $previous['expenses_per_ton'],
            'expenses_per_ton_change_pct' => $this->pctChange($previous['expenses_per_ton'], $recent['expenses_per_ton']),
            'yield_tons_change_pct' => $this->pctChange((float) $previous['yield_tons'], (float) $recent['yield_tons']),
        ];
    }

    /**
     * @param  Collection<int, HarvestReport>  $reports
     * @return array<string, mixed>
     */
    private function bySeason(Collection $reports): array
    {
        return $reports
            ->groupBy('season')
            ->map(fn (Collection $rows, string $season) => $this->summarize($rows) + [
                'label' => $season === 'wet' ? 'Wet Season' : 'Dry Season',
            ])
            ->sortBy(fn ($summary, string $season) => $season === 'dry' ? 0 : 1)
            ->toArray();
    }

    /**
     * Per-crop totals plus that crop's own latest-vs-previous movement, so the
     * model can separate "everything fell" from "one crop dragged the average".
     *
     * @param  Collection<int, HarvestReport>  $reports
     * @return array<int, array<string, mixed>>
     */
    private function byCrop(Collection $reports): array
    {
        return $reports
            ->groupBy('crop_type')
            ->map(function (Collection $rows, string $crop) {
                $summary = $this->summarize($rows);
                $series  = $this->periods($rows);

                $recent   = $series[count($series) - 1] ?? null;
                $previous = count($series) >= 2 ? $series[count($series) - 2] : null;

                return $summary + [
                    'crop'                 => $crop,
                    'period_count'         => count($series),
                    'recent_label'         => $recent['label'] ?? null,
                    'recent_yield_per_ha'  => $recent['yield_per_ha'] ?? null,
                    'previous_yield_per_ha' => $previous['yield_per_ha'] ?? null,
                    'yield_per_ha_change_pct' => $previous
                        ? $this->pctChange($previous['yield_per_ha'], $recent['yield_per_ha'])
                        : null,
                ];
            })
            ->sortByDesc('yield_tons')
            ->values()
            ->all();
    }

    /**
     * Two leaderboards: most reports filed, and most yield produced. They are
     * computed separately on purpose — they frequently disagree, and that
     * disagreement is worth showing.
     *
     * @param  Collection<int, HarvestReport>  $reports
     * @return array<int, array<string, mixed>>
     */
    private function topFarmers(Collection $reports, string $ranking): array
    {
        $farmers = $reports
            ->groupBy('user_id')
            ->map(fn (Collection $rows) => Arr::except($this->summarize($rows), ['farmers', 'expenses_per_ha'])
                + ['farmer' => $rows->first()->farmer?->name ?? 'Unknown farmer']);

        $sorted = $ranking === 'yield'
            ? $farmers->sortByDesc('yield_tons')
            : $farmers->sortByDesc('reports');

        return $sorted
            ->take(self::TOP_FARMERS)
            ->values()
            ->all();
    }

    /**
     * Variety detail, reachable only through farmer_crops rows that were linked
     * back to a harvest report. Returns [] when nothing is linked, which the
     * prompt treats as "do not discuss varieties".
     *
     * @param  Collection<int, HarvestReport>  $reports
     * @return array<int, array<string, mixed>>
     */
    private function varieties(Collection $reports): array
    {
        $linked = FarmerCrop::with('crop')
            ->whereIn('harvest_report_id', $reports->pluck('id')->all())
            ->get();

        if ($linked->isEmpty()) {
            return [];
        }

        return $linked
            ->groupBy(fn (FarmerCrop $crop) => trim((string) $crop->variety) !== ''
                ? trim((string) $crop->variety)
                : 'Unspecified variety')
            ->map(function (Collection $rows, string $name) {
                $first  = $rows->first();
                $area   = (float) $rows->sum('land_area');
                $yield  = (float) $rows->sum('actual_yield');
                $note   = $first->variety_key
                    ? ($this->crops->varietyByKey($first->crop?->name, $first->variety_key)['note'] ?? null)
                    : null;

                return [
                    'variety'        => $name,
                    'crop'           => $first->crop?->name,
                    'linked_batches' => $rows->count(),
                    'area_ha'        => round($area, 2),
                    'yield_tons'     => round($yield, 2),
                    'yield_per_ha'   => $area > 0 ? round($yield / $area, 3) : null,
                    'note'           => $note,
                ];
            })
            ->sortByDesc('linked_batches')
            ->values()
            ->all();
    }

    /**
     * Every logged weather event, each tagged with the harvest periods its
     * date range falls inside.
     *
     * This is the bridge that lets the analysis answer "why" instead of only
     * "what". An event with an empty `overlapping_periods` is still reported —
     * it just sits outside the recorded harvest range, and the prompt is told
     * not to use it to explain a period.
     *
     * @param  Collection<int, HarvestReport>  $reports
     * @param  array<int, array<string, mixed>>  $periods
     * @return array<int, array<string, mixed>>
     */
    private function weatherEvents(Collection $reports, array $periods): array
    {
        $events = WeatherLog::orderBy('start_date')->orderBy('id')->get();

        if ($events->isEmpty() || $periods === []) {
            return [];
        }

        return $events
            ->map(function (WeatherLog $event) use ($periods) {
                $overlapping = collect($periods)
                    ->filter(fn (array $period) => $event->overlaps(
                        Carbon::parse($period['first_harvest']),
                        Carbon::parse($period['last_harvest'])
                    ))
                    ->pluck('label')
                    ->values()
                    ->all();

                return [
                    'type'               => $event->event_type,
                    'label'              => $event->typeLabel(),
                    'severity'           => $event->severity,
                    'severity_label'     => $event->severityLabel(),
                    'title'              => $event->title,
                    'dates'              => $event->dateRangeLabel(),
                    'notes'              => $event->notes,
                    'overlapping_periods' => $overlapping,
                ];
            })
            ->all();
    }

    /**
     * Hang each period's weather events off the period itself, so the model
     * sees the two together rather than having to join them mentally.
     *
     * @param  array<int, array<string, mixed>>  $periods
     * @param  array<int, array<string, mixed>>  $events
     * @return array<int, array<string, mixed>>
     */
    private function attachWeather(array $periods, array $events): array
    {
        if ($events === []) {
            return array_map(fn (array $period) => $period + ['weather_events' => []], $periods);
        }

        return array_map(function (array $period) use ($events) {
            return $period + [
                'weather_events' => array_values(array_filter(
                    $events,
                    fn (array $event) => in_array($period['label'], $event['overlapping_periods'], true)
                )),
            ];
        }, $periods);
    }

    /**
     * Normal Bicol climate for whichever seasons appear in the data. This is
     * documented climatology from SeasonHelper — a seasonal characteristic,
     * never a record of a weather event.
     *
     * @param  array<int, string>  $seasons
     * @return array<string, mixed>
     */
    private function climatology(array $seasons): array
    {
        $out = [];

        if (in_array('wet', $seasons, true)) {
            $habagat = SeasonHelper::getClimateInfo(Carbon::create(2025, 7, 1));
            $amihan  = SeasonHelper::getClimateInfo(Carbon::create(2025, 12, 1));

            $out['wet'] = [
                'label'           => 'Wet Season',
                'months'          => SeasonHelper::getSeasonMonths('wet'),
                'habagat_jun_oct' => $habagat['typical_weather'],
                'amihan_nov_jan'  => $amihan['typical_weather'],
                'standard_advice' => $habagat['recommendation'],
            ];
        }

        if (in_array('dry', $seasons, true)) {
            $taginit = SeasonHelper::getClimateInfo(Carbon::create(2025, 3, 1));
            $prep    = SeasonHelper::getClimateInfo(Carbon::create(2025, 5, 1));

            $out['dry'] = [
                'label'           => 'Dry Season',
                'months'          => SeasonHelper::getSeasonMonths('dry'),
                'taginit_feb_apr' => $taginit['typical_weather'],
                'transition_may'  => $prep['typical_weather'],
                'standard_advice' => $taginit['recommendation'],
            ];
        }

        return $out;
    }

    // ────────────────────────────────────────────────────────────────
    //  Model round-trip
    // ────────────────────────────────────────────────────────────────

    /**
     * Pull {"insight": "..."} out of a completion, tolerating the model
     * wrapping its answer in prose or code fences. Returns null when nothing
     * usable came back, so the caller can fall through to fallback().
     */
    private function decode(string $raw, string $field = 'insight'): ?string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        if (preg_match('/\{.*\}/s', $raw, $match) === 1) {
            $raw = $match[0];
        }

        $decoded = json_decode($raw, true);
        $text    = is_array($decoded) ? ($decoded[$field] ?? null) : null;

        if (! is_string($text) || trim($text) === '') {
            return null;
        }

        // Belt and braces: the prompt forbids these, but a stray one would
        // render as literal junk on an admin page.
        $text = preg_replace('/\[[ \t\x{00A0}]*SUGGEST:[^\]]*\]/iu', '', $text);
        $text = preg_replace('/```(?:markdown)?/i', '', $text);

        return trim((string) $text);
    }

    // ────────────────────────────────────────────────────────────────
    //  Deterministic fallback
    // ────────────────────────────────────────────────────────────────

    /**
     * Same five headings, written straight from context(). Shown when the
     * model is offline or returned nothing usable — still 100% real data.
     *
     * @param  array<string, mixed>  $context
     */
    public function fallback(array $context): string
    {
        $coverage = $context['coverage'] ?? [];

        if ((int) ($coverage['reports'] ?? 0) === 0) {
            return "### Performance Snapshot\n\n"
                . "- No harvest reports have been recorded yet, so there is nothing to analyse.";
        }

        $totals  = $context['totals'] ?? [];
        $changes = $context['recent_vs_previous'] ?? null;
        $lines   = [];

        // ── Performance Snapshot ──
        $lines[] = '### Performance Snapshot';
        $lines[] = sprintf(
            '- **%d** harvest reports from **%d** farmers covering **%s**, recorded **%s** to **%s**.',
            $coverage['reports'],
            $coverage['farmers'],
            implode(', ', $coverage['crops'] ?? ['—']) ?: '—',
            $coverage['first_harvest'] ?? '—',
            $coverage['last_harvest'] ?? '—'
        );
        $lines[] = '- **' . number_format((float) $totals['yield_tons'], 2) . ' MT** total yield from **'
            . number_format((float) $totals['area_ha'], 2) . ' ha** — **'
            . ($totals['yield_per_ha'] !== null ? number_format((float) $totals['yield_per_ha'], 3) . ' MT/ha**' : 'not calculable (no area recorded)')
            . '.';
        $lines[] = '- **₱' . number_format((float) $totals['expenses_php'], 0) . '** recorded expenses — **₱'
            . ($totals['expenses_per_ton'] !== null ? number_format((float) $totals['expenses_per_ton'], 0) : '—')
            . '** per tonne of yield.';

        // ── Recent vs. Past ──
        $lines[] = '';
        $lines[] = '### Recent vs. Past';

        if ($changes === null) {
            $lines[] = '- Only **one** harvest period is recorded, so there is nothing to compare against yet.';
        } elseif ($changes['yield_per_ha_change_pct'] === null) {
            $lines[] = sprintf(
                '- **%s** against **%s**, but yield per hectare is not comparable — one of them has no harvested area recorded.',
                $changes['recent_label'],
                $changes['previous_label']
            );
        } else {
            $lines[] = sprintf(
                '- **%s** against **%s**: **%s** vs **%s** MT/ha (**%s%s**).',
                $changes['recent_label'],
                $changes['previous_label'],
                $changes['recent_yield_per_ha'] !== null ? number_format((float) $changes['recent_yield_per_ha'], 3) : '—',
                $changes['previous_yield_per_ha'] !== null ? number_format((float) $changes['previous_yield_per_ha'], 3) : '—',
                $changes['yield_per_ha_change_pct'] >= 0 ? '+' : '',
                $changes['yield_per_ha_change_pct'] . '%'
            );
            $lines[] = sprintf(
                '- Period volume: **%d** reports against **%d** reports, total yield **%s**.',
                $changes['recent_reports'],
                $changes['previous_reports'],
                $changes['yield_tons_change_pct'] === null
                    ? 'unchanged in percentage terms (previous period recorded zero yield)'
                    : sprintf('%s%s', $changes['yield_tons_change_pct'] >= 0 ? '+' : '', $changes['yield_tons_change_pct'] . '%')
            );
        }

        // ── What The Data Suggests ──
        $lines[] = '';
        $lines[] = '### What The Data Suggests';

        foreach ($this->seasonContrasts($context) as $contrast) {
            $lines[] = $contrast;
        }

        foreach (array_slice($context['by_crop'] ?? [], 0, 3) as $crop) {
            if ($crop['yield_per_ha_change_pct'] === null) {
                continue;
            }
            $lines[] = sprintf(
                '- **%s**: **%s** MT/ha in **%s**, against **%s** MT/ha previously (**%s%s**). Worth verifying: what changed for this crop between those two periods.',
                $crop['crop'],
                $crop['recent_yield_per_ha'] !== null ? number_format((float) $crop['recent_yield_per_ha'], 3) : '—',
                $crop['recent_label'] ?? 'the recent period',
                $crop['previous_yield_per_ha'] !== null ? number_format((float) $crop['previous_yield_per_ha'], 3) : '—',
                $crop['yield_per_ha_change_pct'] >= 0 ? '+' : '',
                $crop['yield_per_ha_change_pct'] . '%'
            );
        }

        foreach ($this->weatherLines($context) as $line) {
            $lines[] = $line;
        }

        // ── Farmers To Recognise ──
        $lines[] = '';
        $lines[] = '### Farmers To Recognise';

        $byReports = array_slice($context['top_farmers_by_reports'] ?? [], 0, 3);
        if ($byReports === []) {
            $lines[] = '- No farmer records to rank yet.';
        } else {
            foreach ($byReports as $rank => $farmer) {
                $lines[] = sprintf(
                    '- #%d **%s** — **%d** reports, **%s** MT total yield.',
                    $rank + 1,
                    $farmer['farmer'],
                    $farmer['reports'],
                    number_format((float) $farmer['yield_tons'], 2)
                );
            }
        }

        // ── Recommended Follow-Up ──
        $lines[] = '';
        $lines[] = '### Recommended Follow-Up';

        if (($context['coverage']['weather_events_logged'] ?? 0) === 0) {
            $lines[] = '- Log typhoons, floods and droughts under Settings → Weather Log — without dated events, no yield movement can be attributed with confidence.';
        } elseif (($context['coverage']['weather_events_overlapping'] ?? 0) === 0) {
            $lines[] = '- The logged weather events all fall outside your recorded harvest dates. Check their dates, or add the ones you are missing, so periods can be correlated.';
        } else {
            $lines[] = '- Confirm the logged weather events above against field records before treating any of them as the reason a period moved.';
        }

        if (empty($context['varieties'])) {
            $lines[] = '- Link crop batches to their harvest reports so variety notes (including flood-tolerant stock) become analysable.';
        } else {
            $lines[] = '- Review the linked variety notes above against the crops that moved most between periods.';
        }
        $lines[] = '- Ask the leading farmers above what they did differently, and record the answer against their next harvest.';

        return implode("\n", $lines);
    }

    /**
     * Weather bullets for the deterministic fallback — the same evidence the
     * prompt hands to the model, written out by PHP instead.
     *
     * Logged events are stated as logged facts with their dates. Cause is
     * never asserted; each line is explicitly framed as something to verify.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    private function weatherLines(array $context): array
    {
        $events = $context['weather_events'] ?? [];

        if ($events === []) {
            return ['- Worth verifying: no weather events are logged against your harvest dates, so no cause here can be confirmed from this data alone.'];
        }

        $overlapping = array_values(array_filter(
            $events,
            fn (array $event) => $event['overlapping_periods'] !== []
        ));

        if ($overlapping === []) {
            return [sprintf(
                '- **%d** weather event%s logged, but none of them fall inside a recorded harvest range, so they cannot yet be tied to a period.',
                count($events),
                count($events) === 1 ? '' : 's'
            )];
        }

        $lines = [];
        foreach (array_slice($overlapping, 0, 4) as $event) {
            $lines[] = sprintf(
                '- **%s** (%s, %s) was logged **%s** and falls inside **%s**%s. Worth verifying against field records before treating it as the reason that period moved.',
                $event['title'],
                $event['label'],
                $event['severity_label'],
                $event['dates'],
                implode(' and ', $event['overlapping_periods']),
                $event['notes'] !== null ? ' — ' . $event['notes'] : ''
            );
        }

        $beyond = count($overlapping) - min(4, count($overlapping));
        if ($beyond > 0) {
            $lines[] = sprintf('- And **%d** more logged event%s inside recorded harvest ranges.', $beyond, $beyond === 1 ? '' : 's');
        }

        return $lines;
    }

    /**
     * Wet-vs-dry contrast lines, computed once and reused by the fallback.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    private function seasonContrasts(array $context): array
    {
        $seasons = $context['by_season'] ?? [];

        if (count($seasons) < 2) {
            $only = array_values($seasons)[0] ?? null;

            return $only
                ? [sprintf('- Only **%s** harvests are recorded, so wet-vs-dry cannot be compared.', $only['label'])]
                : [];
        }

        $wet   = $seasons['wet'] ?? null;
        $dry   = $seasons['dry'] ?? null;

        if ($wet === null || $dry === null || $wet['yield_per_ha'] === null || $dry['yield_per_ha'] === null) {
            return ['- Not enough area recorded in both seasons to compare yield per hectare.'];
        }

        $gap = $this->pctChange((float) $dry['yield_per_ha'], (float) $wet['yield_per_ha']);

        return [sprintf(
            '- **Wet Season** (**%s** MT/ha over **%d** reports) vs **Dry Season** (**%s** MT/ha over **%d** reports) — **%s%s**. Worth verifying: season, crop mix, and variety notes behind each side of that gap.',
            number_format((float) $wet['yield_per_ha'], 3),
            $wet['reports'],
            number_format((float) $dry['yield_per_ha'], 3),
            $dry['reports'],
            $gap >= 0 ? '+' : '',
            $gap . '%'
        )];
    }

    // ────────────────────────────────────────────────────────────────
    //  Small helpers
    // ────────────────────────────────────────────────────────────────

    /**
     * Percentage change from $from to $to, rounded to one decimal. Null when
     * the baseline is missing or zero — 0 is not a meaningful denominator.
     */
    private function pctChange(mixed $from, mixed $to): ?float
    {
        if ($from === null || $to === null || (float) $from == 0.0) {
            return null;
        }

        return round(((float) $to - (float) $from) / (float) $from * 100, 1);
    }

    private function dateOf(mixed $date): ?string
    {
        if ($date === null) {
            return null;
        }

        return Carbon::parse($date)->format('M d, Y');
    }
}
