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
        $reports = $this->loadReports();

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
            // Every farmer, worst yield per hectare last. The leaderboards
            // above only show who did well; this is what answers "who did
            // badly, and why" once farmer_focus zooms into one of them.
            'farmers'           => $this->farmers($reports),
            'varieties'          => $this->varieties($reports),
            'weather_events'     => $weather,
            'climatology_reference' => $this->climatology($coverage['seasons']),
        ];
    }

    private function loadReports(): Collection
    {
        return HarvestReport::with('farmer')->orderBy('harvest_date')->get();
    }

    /**
     * Answer one question from the chat on the AI Harvest Insights subpage.
     *
     * The digest is rebuilt for every turn so an answer can never be based on
     * a stale snapshot. Alongside it the subject of the question is resolved —
     * a farmer named here, a farmer named earlier in the conversation, or the
     * best/worst performer when the question asks for a ranking — and that
     * farmer's own rows are attached as `farmer_focus`. It is what makes a
     * bare "why" come back with that farmer's area, cadence, season mix and
     * planting records instead of a fresh overview.
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

        if (($focus = $this->focusFarmer($context, $message, $history)) !== null) {
            $context['farmer_focus'] = $focus;
        }

        $completion = $this->llm->completeJson([
            ['role' => 'system', 'content' => HarvestQaPrompt::system()],
            ['role' => 'user', 'content' => HarvestQaPrompt::user($context, $message, $history)],
        ], self::MAX_TOKENS);

        $reply = $this->decode($completion['raw'] ?? '', 'reply');

        return [
            'source'  => $reply !== null ? 'ai' : 'fallback',
            'reply'   => $reply ?? $this->fallbackFor($context),
            'context' => $context,
        ];
    }

    /**
     * The fallback that matches the question actually asked: the farmer
     * dossier when one was resolved, the five-section overview otherwise.
     *
     * @param  array<string, mixed>  $context
     */
    private function fallbackFor(array $context): string
    {
        if (isset($context['farmer_focus'])) {
            return $this->focusFallback($context['farmer_focus']);
        }

        return $this->fallback($context);
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
     * One row per farmer with harvest reports, ordered by yield per hectare
     * (best first, no farmer dropped), with their own season and crop splits.
     *
     * Yield per hectare is the ranking key on purpose: total yield mostly
     * measures how much land someone farms, which is exactly the wrong yard
     * stick for "who performed worst".
     *
     * @param  Collection<int, HarvestReport>  $reports
     * @return array<int, array<string, mixed>>
     */
    private function farmers(Collection $reports): array
    {
        return $reports
            ->groupBy('user_id')
            ->map(function (Collection $rows) {
                $summary = Arr::except($this->summarize($rows), ['farmers']);

                return $summary + [
                    'farmer'        => $rows->first()->farmer?->name ?? 'Unknown farmer',
                    'seasons'       => $this->splitBy($rows, 'season'),
                    'crops'         => $this->splitBy($rows, 'crop_type'),
                    'period_labels' => array_column($this->periods($rows), 'label'),
                    'first_harvest' => $this->dateOf($rows->min('harvest_date')),
                    'last_harvest'  => $this->dateOf($rows->max('harvest_date')),
                ];
            })
            ->sortByDesc(fn (array $row) => $row['yield_per_ha'] === null ? -1.0 : (float) $row['yield_per_ha'])
            ->values()
            ->all();
    }

    /**
     * Reports grouped by one column, each group as report count + yield/ha,
     * so a farmer's own wet-vs-dry or rice-vs-corn split can be compared
     * against the association's.
     *
     * @param  Collection<int, HarvestReport>  $rows
     * @return array<string, array{reports: int, yield_per_ha: float|null}>
     */
    private function splitBy(Collection $rows, string $column): array
    {
        return $rows
            ->filter(fn (HarvestReport $row) => trim((string) $row->getAttribute($column)) !== '')
            ->groupBy($column)
            ->map(fn (Collection $group) => [
                'reports'      => $group->count(),
                'yield_per_ha' => $this->summarize($group)['yield_per_ha'],
            ])
            ->toArray();
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
    //  Subject resolution — what makes a bare "why" mean something
    // ────────────────────────────────────────────────────────────────

    /**
     * Build `farmer_focus`: one farmer's own rows plus the facts that could
     * explain their yield, ready to be read as questions rather than verdicts.
     *
     * Returns null when nothing in the question or the conversation names or
     * ranks a farmer — a question about a season or a crop needs no dossier.
     *
     * @param  array<string, mixed>  $context
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array<string, mixed>|null
     */
    private function focusFarmer(array $context, string $message, array $history): ?array
    {
        $farmers = $context['farmers'] ?? [];

        if ($farmers === []) {
            return null;
        }

        $subject = $this->resolveSubject($farmers, $message, $history);

        if ($subject === null) {
            return null;
        }

        $name = $subject['farmer'];
        $row  = collect($farmers)->firstWhere('farmer', $name);

        if ($row === null) {
            return null;
        }

        $reports = $this->loadReports()
            ->filter(fn (HarvestReport $report) => ($report->farmer?->name ?? 'Unknown farmer') === $name)
            ->values();

        if ($reports->isEmpty()) {
            return null;
        }

        $plantings = FarmerCrop::with('crop')
            ->where('user_id', $reports->first()->user_id)
            ->get()
            ->sortByDesc(fn (FarmerCrop $crop) => $crop->planting_date?->getTimestamp() ?? 0)
            ->take(self::FOCUS_PLANTINGS)
            ->values();

        $activity = ActivityLog::whereIn('type', self::FARM_ACTIVITY_TYPES)
            ->where('user_id', $reports->first()->user_id)
            ->orderByDesc('created_at')
            ->limit(self::FOCUS_ACTIVITY)
            ->get();

        $against = $this->againstAssociation($row, $context);

        return [
            'farmer'             => $name,
            'resolved_by'        => $subject['resolved_by'],
            'summary'            => $row,
            'vs_association'     => $against,
            'reports'            => $this->focusReports($reports),
            'plantings'          => $this->focusPlantings($plantings),
            'activity_log'       => $this->focusActivity($activity),
            'candidate_readings' => $this->candidateReadings($row, $against, $plantings, $activity, $context),
        ];
    }

    /**
     * Which farmer is this turn about, walking the question first and the
     * conversation backwards after it — newest text wins, and within a text
     * the farmer named first wins.
     *
     * A farmer name always beats a ranking word, so an answer that says
     * "Juan Cruz has the lowest yield" followed by "why" stays on Juan Cruz
     * instead of re-deriving whoever happens to rank worst today.
     *
     * @param  array<int, array<string, mixed>>  $farmers
     * @param  array<int, array{role: string, content: string}>  $history
     * @return array{farmer: string, resolved_by: string}|null
     */
    private function resolveSubject(array $farmers, string $message, array $history): ?array
    {
        $texts = [$message];

        foreach (array_reverse($history) as $turn) {
            $texts[] = (string) ($turn['content'] ?? '');
        }

        foreach ($texts as $index => $text) {
            if (trim($text) === '') {
                continue;
            }

            if (($named = $this->firstMention($text, $farmers)) !== null) {
                return [
                    'farmer'      => $named,
                    'resolved_by' => $index === 0
                        ? 'named in this question'
                        : 'named earlier in the conversation',
                ];
            }

            if (($ranking = $this->resolveRanking($text)) !== null) {
                $ranked = $this->rankedFarmer($farmers, $ranking[0], $ranking[1]);

                if ($ranked !== null) {
                    return [
                        'farmer'      => $ranked,
                        'resolved_by' => $index === 0
                            ? 'ranking asked for in this question'
                            : 'ranking asked for earlier in the conversation',
                    ];
                }
            }
        }

        return null;
    }

    /**
     * The farmer whose name appears first in $text, matching the full name
     * before falling back to any distinctive part of it.
     *
     * A name part shared by two farmers ("Farmer" in both "Farmer Ana" and
     * "Farmer Ben") identifies nobody, so those parts are dropped — otherwise
     * the word "farmer" in an ordinary question would pick an arbitrary
     * farmer instead of letting the ranking rules run.
     *
     * @param  array<int, array<string, mixed>>  $farmers
     */
    private function firstMention(string $text, array $farmers): ?string
    {
        $partsByFarmer = [];
        $partCounts    = [];

        foreach ($farmers as $farmer) {
            $name = (string) ($farmer['farmer'] ?? '');

            if ($name === '' || $name === 'Unknown farmer') {
                continue;
            }

            foreach (preg_split('/\s+/', $name) as $part) {
                $key = mb_strtolower($part);

                $partsByFarmer[$name][$key] = $part;
                $partCounts[$key] = ($partCounts[$key] ?? 0) + 1;
            }
        }

        $closest   = null;
        $closestAt = PHP_INT_MAX;

        foreach ($partsByFarmer as $name => $parts) {
            $at = null;

            if (preg_match('/\b' . preg_quote($name, '/') . '\b/u', $text, $m, PREG_OFFSET_CAPTURE) === 1) {
                $at = $m[0][1];
            } else {
                foreach ($parts as $key => $part) {
                    // Four characters or more keeps "Ana" from matching
                    // "analysis"; a shared part matches nobody.
                    if (mb_strlen($part) < 4 || $partCounts[$key] > 1) {
                        continue;
                    }

                    if (preg_match('/\b' . preg_quote($part, '/') . '\b/u', $text, $m, PREG_OFFSET_CAPTURE) === 1) {
                        if ($at === null || $m[0][1] < $at) {
                            $at = $m[0][1];
                        }
                    }
                }
            }

            if ($at !== null && $at < $closestAt) {
                $closestAt = $at;
                $closest = $name;
            }
        }

        return $closest;
    }

    /**
     * Ranking words, most specific first: cadence words pin the metric to
     * report count, performance words to yield per hectare.
     *
     * @return array{0: string, 1: string}|null  Column and direction.
     */
    private function resolveRanking(string $text): ?array
    {
        $lower = mb_strtolower($text);

        $rules = [
            ['/\b(fewest|least|fewer|rarely|seldom|infrequent\w*|least often|lowest number)\b/', 'reports', 'asc'],
            ['/\b(most reports|most often|most frequently|frequent\w*|often|highest number|most active)\b/', 'reports', 'desc'],
            ['/\b(worst|lowest|bottom|weakest|poorest|smallest yield|lowest yield|least productive|underperform\w*)\b/', 'yield_per_ha', 'asc'],
            ['/\b(best|highest|top|leading|leader|strongest|highest yield|top yield|most productive|outperform\w*)\b/', 'yield_per_ha', 'desc'],
        ];

        foreach ($rules as [$pattern, $column, $direction]) {
            if (preg_match($pattern, $lower) === 1) {
                return [$column, $direction];
            }
        }

        return null;
    }

    /**
     * @param  array<int, array<string, mixed>>  $farmers
     */
    private function rankedFarmer(array $farmers, string $column, string $direction): ?string
    {
        $sorted = collect($farmers)->sortBy(
            fn (array $row) => (float) ($row[$column] ?? -1),
            SORT_NUMERIC,
            $direction === 'desc'
        );

        return $sorted->first()['farmer'] ?? null;
    }

    /**
     * The farmer against the association, expressed only as numbers the
     * digest already holds — nothing here is an opinion.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>
     */
    private function againstAssociation(array $row, array $context): array
    {
        $totals   = $context['totals'] ?? [];
        $coverage = $context['coverage'] ?? [];
        $farmers  = $context['farmers'] ?? [];

        $reportsPerFarmer = ($coverage['farmers'] ?? 0) > 0
            ? round($coverage['reports'] / $coverage['farmers'], 2)
            : null;

        $areaPerReport = $row['reports'] > 0
            ? round($row['area_ha'] / $row['reports'], 2)
            : null;

        $associationAreaPerReport = ($coverage['reports'] ?? 0) > 0
            ? round(($totals['area_ha'] ?? 0) / $coverage['reports'], 2)
            : null;

        $yieldRank = collect($farmers)->search(fn (array $other) => $other['farmer'] === $row['farmer']);
        $reportOrder = collect($farmers)->sortByDesc('reports')->values();
        $reportRank = $reportOrder->search(fn (array $other) => $other['farmer'] === $row['farmer']);

        return [
            'yield_per_ha' => [
                'farmer'        => $row['yield_per_ha'],
                'association'   => $totals['yield_per_ha'] ?? null,
                'pct_vs'        => $this->pctChange($totals['yield_per_ha'] ?? null, $row['yield_per_ha']),
                'rank_of'       => $yieldRank === false ? null : $yieldRank + 1,
                'farmers_total' => count($farmers),
            ],
            'total_yield_tons' => [
                'farmer'      => $row['yield_tons'],
                'association' => $totals['yield_tons'] ?? null,
            ],
            'area_per_report_ha' => [
                'farmer'      => $areaPerReport,
                'association' => $associationAreaPerReport,
                'pct_vs'      => $this->pctChange($associationAreaPerReport, $areaPerReport),
            ],
            'reports' => [
                'farmer'            => $row['reports'],
                'association_avg'   => $reportsPerFarmer,
                'pct_vs'            => $this->pctChange($reportsPerFarmer, $row['reports']),
                'rank_of'           => $reportRank === false ? null : $reportRank + 1,
            ],
            'expenses_per_ton' => [
                'farmer'      => $row['expenses_per_ton'],
                'association' => $totals['expenses_per_ton'] ?? null,
                'pct_vs'      => $this->pctChange($totals['expenses_per_ton'] ?? null, $row['expenses_per_ton']),
            ],
        ];
    }

    /**
     * @param  Collection<int, HarvestReport>  $reports
     * @return array<int, array<string, mixed>>
     */
    private function focusReports(Collection $reports): array
    {
        return $reports
            ->reverse()
            ->take(self::FOCUS_REPORTS)
            ->map(function (HarvestReport $report) {
                $area  = (float) $report->land_area;
                $yield = (float) $report->actual_yield_metric_tons;

                return [
                    'harvest_date'  => $this->dateOf($report->harvest_date),
                    'crop'          => $report->crop_type,
                    'season'        => $report->season,
                    'area_ha'       => round($area, 2),
                    'yield_tons'    => round($yield, 2),
                    'yield_per_ha'  => $area > 0 ? round($yield / $area, 3) : null,
                    'expenses_php'  => round((float) $report->actual_total_expenses, 2),
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, FarmerCrop>  $plantings
     * @return array<int, array<string, mixed>>
     */
    private function focusPlantings(Collection $plantings): array
    {
        return $plantings
            ->map(function (FarmerCrop $crop) {
                $note = $crop->variety_key
                    ? ($this->crops->varietyByKey($crop->crop?->name, $crop->variety_key)['note'] ?? null)
                    : null;

                return [
                    'crop'              => $crop->crop?->name,
                    'variety'           => $crop->variety ?: null,
                    'variety_note'      => $note,
                    'planting_date'     => $crop->planting_date?->format('M d, Y'),
                    'season'            => $crop->season,
                    'growth_stage'      => $crop->growth_stage,
                    'growth_stage_label' => $crop->growth_stage_label,
                    'best_season'       => $this->crops->infoFor($crop->crop?->name)['best_season'] ?? null,
                    'land_area_ha'      => $crop->land_area !== null ? round((float) $crop->land_area, 2) : null,
                    'actual_yield_tons' => $crop->actual_yield !== null ? round((float) $crop->actual_yield, 2) : null,
                    'harvest_date'      => $crop->actual_harvest_date?->format('M d, Y'),
                    'linked_report'     => $crop->harvest_report_id !== null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, ActivityLog>  $activity
     * @return array<int, array<string, mixed>>
     */
    private function focusActivity(Collection $activity): array
    {
        return $activity
            ->map(fn (ActivityLog $log) => [
                'date'        => $log->created_at->format('M d, Y'),
                'type'        => $log->type,
                'description' => $log->description,
            ])
            ->values()
            ->all();
    }

    /**
     * Facts about this farmer, written by PHP so the model can only choose
     * between readings the records support — never invent one of its own.
     *
     * Every line is a comparison or a count. The model is told to label each
     * it repeats as "worth verifying", because none of them is a cause.
     *
     * @param  array<string, mixed>  $row
     * @param  array<string, mixed>  $against
     * @param  Collection<int, FarmerCrop>  $plantings
     * @param  Collection<int, ActivityLog>  $activity
     * @param  array<string, mixed>  $context
     * @return array<int, string>
     */
    private function candidateReadings(array $row, array $against, Collection $plantings, Collection $activity, array $context): array
    {
        $readings = [];

        $readings[] = sprintf(
            'Yield per hectare **%s MT/ha** against the association\'s **%s MT/ha** — **%s**, ranked **%d of %d** farmers.',
            $row['yield_per_ha'] !== null ? number_format((float) $row['yield_per_ha'], 3) : '—',
            $against['yield_per_ha']['association'] !== null ? number_format((float) $against['yield_per_ha']['association'], 3) : '—',
            $this->pctLabel($against['yield_per_ha']['pct_vs']),
            $against['yield_per_ha']['rank_of'] ?? 0,
            $against['yield_per_ha']['farmers_total']
        );

        $readings[] = sprintf(
            'Harvested **%s ha** over **%d** reports — **%s ha** per report against the association average of **%s ha** (**%s**).',
            number_format((float) $row['area_ha'], 2),
            $row['reports'],
            $against['area_per_report_ha']['farmer'] !== null ? number_format((float) $against['area_per_report_ha']['farmer'], 2) : '—',
            $against['area_per_report_ha']['association'] !== null ? number_format((float) $against['area_per_report_ha']['association'], 2) : '—',
            $this->pctLabel($against['area_per_report_ha']['pct_vs'])
        );

        $readings[] = sprintf(
            '**%d** harvest reports filed against an association average of **%s** per farmer (**%s**, ranked **%d of %d** by report count).',
            $row['reports'],
            $against['reports']['association_avg'] ?? 0,
            $this->pctLabel($against['reports']['pct_vs']),
            $against['reports']['rank_of'] ?? 0,
            count($context['farmers'] ?? [])
        );

        // Season split — is the shortfall concentrated in one season?
        foreach ($row['seasons'] as $season => $split) {
            $association = $context['by_season'][$season] ?? null;

            $readings[] = sprintf(
                '%s: **%d** reports at **%s MT/ha**, against the association\'s **%s** at **%s MT/ha**.',
                ucfirst($season) . ' season',
                $split['reports'],
                $split['yield_per_ha'] !== null ? number_format((float) $split['yield_per_ha'], 3) : '—',
                $association['label'] ?? $season,
                ($association['yield_per_ha'] ?? null) !== null ? number_format((float) $association['yield_per_ha'], 3) : '—'
            );
        }

        // Crop split — is one crop dragging the average down?
        foreach ($row['crops'] as $crop => $split) {
            $association = collect($context['by_crop'] ?? [])->firstWhere('crop', $crop);

            $readings[] = sprintf(
                '%s: **%d** reports at **%s MT/ha**, against the association\'s **%s MT/ha** for this crop.',
                $crop,
                $split['reports'],
                $split['yield_per_ha'] !== null ? number_format((float) $split['yield_per_ha'], 3) : '—',
                ($association['yield_per_ha'] ?? null) !== null ? number_format((float) $association['yield_per_ha'], 3) : '—'
            );
        }

        foreach ($this->plantingReadings($plantings) as $reading) {
            $readings[] = $reading;
        }

        foreach ($this->activityReadings($plantings, $activity, $row) as $reading) {
            $readings[] = $reading;
        }

        return $readings;
    }

    /**
     * What the crop batches say: how often he plants, whether anything is
     * still growing or failed, and whether a batch went in against the crop's
     * own documented season.
     *
     * @param  Collection<int, FarmerCrop>  $plantings
     * @return array<int, string>
     */
    private function plantingReadings(Collection $plantings): array
    {
        if ($plantings->isEmpty()) {
            return ['No crop batches are on record for this farmer, so planting frequency, growth stage and variety cannot be assessed from this data.'];
        }

        $readings = [sprintf(
            '**%d** crop batches on record: %s.',
            $plantings->count(),
            $plantings->countBy('growth_stage')
                ->map(fn (int $count, string $stage) => '**' . $count . '** ' . ($stage !== '' ? $stage : 'unstaged'))
                ->values()
                ->implode(', ')
        )];

        $failed = $plantings->where('growth_stage', 'failed')->count();
        if ($failed > 0) {
            $readings[] = sprintf('**%d** of those batches are recorded as **failed**, so they produced no harvest.', $failed);
        }

        $growing = $plantings
            ->reject(fn (FarmerCrop $crop) => in_array($crop->growth_stage, ['harvested', 'failed'], true))
            ->count();
        if ($growing > 0) {
            $readings[] = sprintf(
                '**%d** batches are still growing and have not reached harvest, so they contribute nothing to the yield recorded so far.',
                $growing
            );
        }

        $mismatched = $plantings->filter(function (FarmerCrop $crop) {
            $best = $this->crops->infoFor($crop->crop?->name)['best_season'] ?? null;

            if ($best === null || $best === '' || in_array($best, ['Wet & Dry', 'Year-round'], true)) {
                return false;
            }

            $suitsWet = mb_strtolower($best) === 'wet';

            return $suitsWet
                ? $crop->season !== 'wet'
                : $crop->season !== 'dry';
        });

        if ($mismatched->isNotEmpty()) {
            $readings[] = sprintf(
                '**%d** batch%s planted in a season the crop directory does **not** list as its best season: %s. Worth checking what was planted when.',
                $mismatched->count(),
                $mismatched->count() === 1 ? '' : 'es',
                $mismatched
                    ->take(4)
                    ->map(fn (FarmerCrop $crop) => sprintf(
                        '**%s** in the **%s** season (directory best season: **%s**)',
                        $crop->crop?->name ?? 'Unknown crop',
                        $crop->season,
                        $this->crops->infoFor($crop->crop?->name)['best_season'] ?? '—'
                    ))
                    ->implode('; ')
            );
        }

        $varietyCounts = $plantings
            ->filter(fn (FarmerCrop $crop) => trim((string) $crop->variety) !== '')
            ->groupBy(fn (FarmerCrop $crop) => $crop->variety);

        if ($varietyCounts->isEmpty()) {
            $readings[] = 'No variety is recorded on any of these batches, so variety cannot be considered as a factor.';
        } else {
            foreach ($varietyCounts->take(3) as $variety => $group) {
                $note = $group->first()->variety_key
                    ? ($this->crops->varietyByKey($group->first()->crop?->name, $group->first()->variety_key)['note'] ?? null)
                    : null;

                $readings[] = sprintf(
                    'Variety **%s** on **%d** batch%s%s.',
                    $variety,
                    $group->count(),
                    $group->count() === 1 ? '' : 'es',
                    $note !== null ? ' — crop library note: **' . $note . '**' : ''
                );
            }
        }

        return $readings;
    }

    /**
     * What the activity log says about cadence — how often planting and
     * harvesting actually happened, and whether anything is missing.
     *
     * @param  Collection<int, FarmerCrop>  $plantings
     * @param  Collection<int, ActivityLog>  $activity
     * @param  array<string, mixed>  $row
     * @return array<int, string>
     */
    private function activityReadings(Collection $plantings, Collection $activity, array $row): array
    {
        if ($activity->isEmpty()) {
            return ['No planting, growth-stage, land or harvest activity is logged for this farmer, so planting cadence cannot be assessed from this data.'];
        }

        $readings = [sprintf(
            '**%d** planting, growth-stage, land or harvest entries in the activity log, newest **%s**, oldest **%s**.',
            $activity->count(),
            $activity->first()->created_at->format('M d, Y'),
            $activity->last()->created_at->format('M d, Y')
        )];

        $planted = $activity
            ->where('type', ActivityLog::TYPE_CROP_PLANTED)
            ->sortBy('created_at')
            ->values();

        if ($planted->isNotEmpty()) {
            $first = $planted->first()->created_at;
            $last  = $planted->last()->created_at;
            $years = max(1, round($first->diffInDays($last) / 365, 1));

            $readings[] = sprintf(
                '**%d** `crop.planted` entries between **%s** and **%s** — roughly **%s** planting events a year.',
                $planted->count(),
                $first->format('M d, Y'),
                $last->format('M d, Y'),
                round($planted->count() / $years, 1)
            );

            $gaps = $planted
                ->slice(1)
                ->map(fn (ActivityLog $log, int $index) => (int) $planted[$index]->created_at->diffInDays($log->created_at));

            if ($gaps->isNotEmpty()) {
                $readings[] = sprintf(
                    'Longest gap between two recorded plantings: **%d days**.',
                    (int) $gaps->max()
                );
            }
        }

        $harvestLogs = $activity->where('type', ActivityLog::TYPE_HARVEST_CREATED)->count();

        if ($harvestLogs !== $row['reports']) {
            $readings[] = sprintf(
                'The activity log holds **%d** `harvest.created` entries while the harvest report table holds **%d** reports for this farmer — worth reconciling.',
                $harvestLogs,
                $row['reports']
            );
        }

        $linked = $plantings->whereNotNull('harvest_report_id')->count();
        if ($plantings->isNotEmpty() && $linked < $plantings->count()) {
            $readings[] = sprintf(
                'Only **%d** of **%d** crop batches are linked to a harvest report, so some plantings never reached a recorded harvest.',
                $linked,
                $plantings->count()
            );
        }

        return $readings;
    }

    /**
     * Offline answer for a question about one farmer: the comparison, then
     * the readings, each marked as something to verify rather than a cause.
     *
     * @param  array<string, mixed>  $focus
     */
    private function focusFallback(array $focus): string
    {
        $against = $focus['vs_association'] ?? [];
        $summary = $focus['summary'] ?? [];

        $lines = [sprintf(
            '**%s** — **%d** reports, **%s ha**, **%s MT** total, **%s MT/ha**, against the association\'s **%s MT/ha** (**%s**).',
            $focus['farmer'],
            $summary['reports'] ?? 0,
            number_format((float) ($summary['area_ha'] ?? 0), 2),
            number_format((float) ($summary['yield_tons'] ?? 0), 2),
            isset($summary['yield_per_ha']) && $summary['yield_per_ha'] !== null
                ? number_format((float) $summary['yield_per_ha'], 3)
                : '—',
            ($against['yield_per_ha']['association'] ?? null) !== null
                ? number_format((float) $against['yield_per_ha']['association'], 3)
                : '—',
            $this->pctLabel($against['yield_per_ha']['pct_vs'] ?? null)
        )];

        $lines[] = '';
        $lines[] = 'Worth verifying from the records:';

        foreach (($focus['candidate_readings'] ?? []) as $reading) {
            $lines[] = '- ' . $reading;
        }

        $lines[] = '';
        $lines[] = 'The model is offline, so these are the recorded facts rather than a reading of them. Nothing above is a stated cause — each one needs checking against the field before it explains anything.';

        return implode("\n", $lines);
    }

    // ────────────────────────────────────────────────────────────────
    //  Model round-trip
    // ────────────────────────────────────────────────────────────────

    /**
     * Pull the answer out of a completion.
     *
     * The model is asked for {"reply": "…"} and usually obeys. When it wraps
     * that in prose or code fences the envelope is still found. When it drops
     * the envelope entirely and answers in plain sentences, those sentences
     * are kept — a real, on-topic answer beats the generic report — and only
     * an empty or envelope-shaped-but-empty response falls through to null so
     * the caller can use fallback().
     */
    private function decode(string $raw, string $field = 'insight'): ?string
    {
        $raw = trim($raw);

        if ($raw === '') {
            return null;
        }

        $raw = preg_replace('/```(?:json|markdown)?/i', '', $raw);
        $raw = trim((string) $raw);

        $envelope = null;

        if (preg_match('/\{.*\}/s', $raw, $match) === 1) {
            $decoded = json_decode($match[0], true);

            if (is_array($decoded)) {
                $envelope = $decoded;
            }
        }

        if ($envelope !== null) {
            $text = $envelope[$field] ?? null;

            // The model named the key something else — take its single answer
            // rather than throwing away a good reply over a label.
            if (! is_string($text) || trim($text) === '') {
                foreach ($envelope as $key => $value) {
                    if (is_string($value) && trim($value) !== '' && ! in_array($key, ['error', 'errors', 'refusal'], true)) {
                        $text = $value;
                        break;
                    }
                }
            }

            if (! is_string($text) || trim($text) === '') {
                return null;
            }
        } else {
            // Plain prose: no envelope to decode, so the whole reply is the
            // answer — unless it is only a stray brace fragment.
            $text = $raw;
        }

        // Belt and braces: the prompt forbids these, but a stray one would
        // render as literal junk on an admin page.
        $text = preg_replace('/\[[ \t\x{00A0}]*SUGGEST:[^\]]*\]/iu', '', $text);

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

    /**
     * "+12.4%" / "-38.2%" / "—" — a percentage change has no direction worth
     * showing when the baseline was missing or zero.
     */
    private function pctLabel(?float $pct): string
    {
        if ($pct === null) {
            return '—';
        }

        return ($pct >= 0 ? '+' : '') . $pct . '%';
    }
}
