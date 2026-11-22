<?php

namespace App\Http\Controllers;

use App\Models\GeneratedHarvestReport;
use App\Models\HarvestReport;
use App\Services\HarvestInsightService;
use App\Services\HarvestReportAnalyticsService;
use App\Services\HarvestReportFilterService;
use App\Services\HarvestReportTextService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Symfony\Component\HttpFoundation\Response;

/**
 * Saved, downloadable Actual Harvest Reports with deterministic explanations.
 *
 * A report is generated once and then stored. Viewing, switching language and
 * downloading read the stored text and context; no generation step calls any
 * external service, so the report keeps the date and figures it was made with.
 */
class HarvestReportGenerationController extends Controller
{
    public function __construct(
        private readonly HarvestInsightService $insights,
        private readonly HarvestReportFilterService $filterService,
        private readonly HarvestReportTextService $text,
        private readonly HarvestReportAnalyticsService $analytics,
    ) {}

    public function index(Request $request)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $tab = (string) $request->query('tab', 'generate');
        if (! in_array($tab, ['generate', 'saved', 'records'], true)) {
            $tab = 'generate';
        }

        $filters = $this->filterService->cleanStudio($request->query());
        $selectedReports = $this->filteredReports($filters);
        $previewContext = $this->insights->contextForReports($selectedReports);
        $previewVisual = $this->analytics->forReports($selectedReports);

        $allReports = HarvestReport::with(['farmer', 'member'])
            ->orderByDesc('harvest_date')
            ->get();

        $savedReports = auth()->user()
            ->generatedHarvestReports()
            ->latest()
            ->get();

        $recordSearch = trim((string) $request->query('record_q', ''));
        $recordSort = (string) $request->query('record_sort', 'date');
        if (! in_array($recordSort, ['date', 'yield', 'expenses', 'area', 'crop'], true)) {
            $recordSort = 'date';
        }
        $recordDir = $request->query('record_dir') === 'asc' ? 'asc' : 'desc';

        $viewData = [
            'activeTab' => $tab,
            'filters' => $filters,
            'previewContext' => $previewContext,
            'previewVisual' => $previewVisual,
            'scope' => $this->scopeLabel($filters),
            'seasonFilter' => (string) ($filters['season'] ?? 'all'),
            'savedReports' => $savedReports,
            'options' => $this->analytics->options($allReports),
            'records' => $this->sortRecords($selectedReports, $recordSearch, $recordSort, $recordDir),
            'recordSearch' => $recordSearch,
            'recordSort' => $recordSort,
            'recordDir' => $recordDir,
            'filtersOpen' => $request->query('filters') !== 'closed',
        ];

        // Filter controls ask for just the changing fragments. The full page
        // is still returned for normal visits, so links and forms work without
        // JavaScript; only the in-page enhancement uses this response.
        if ($request->header('X-AQ-Filter-Fragment') === '1') {
            return response()->json([
                'scopeHtml' => view('admin.harvests.generated.partials.scope-studio', $viewData)->render(),
                'previewHtml' => view('admin.harvests.generated.partials.visual-report', $viewData + [
                    'visual' => $previewVisual,
                ])->render(),
                'recordsHtml' => view('admin.harvests.generated.partials.records-table', $viewData)->render(),
                'scopeText' => $viewData['scope'],
                'url' => $request->fullUrl(),
            ]);
        }

        return view('admin.harvests.generated.index', $viewData);
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $validated = $request->validate([
            'title' => ['nullable', 'string', 'max:120'],
            'default_language' => ['required', 'in:en,tl'],
            'filters' => ['sometimes', 'array'],
        ]);

        $filters = $this->filterService->cleanStudio(is_array($validated['filters'] ?? null) ? $validated['filters'] : []);
        $reports = $this->filteredReports($filters);
        $context = $this->insights->contextForReports($reports);

        if ((int) ($context['coverage']['reports'] ?? 0) === 0) {
            return redirect()
                ->route('admin.harvests.reports.generate', $filters)
                ->with('error', 'No harvest reports match those filters, so no report was generated.')
                ->withInput();
        }

        $title = trim((string) ($validated['title'] ?? ''));
        $title = $title !== '' ? $title : $this->defaultTitle($filters);

        $narration = $this->text->compose($context);

        // The ids let the saved record still show the exact rows it covered,
        // while the narrative and context remain the snapshot made today.
        $storedFilters = $filters + ['_report_ids' => $reports->pluck('id')->all()];
        $context['visual'] = $this->analytics->forReports($reports);

        $saved = auth()->user()->generatedHarvestReports()->create([
            'title' => $title,
            'filters' => $storedFilters,
            'context' => $context,
            'narrative_en' => $narration['en'],
            'narrative_tl' => $narration['tl'],
            'default_language' => $validated['default_language'],
            'source' => 'template',
            'model' => null,
        ]);

        return redirect()
            ->route('admin.harvests.reports.generated.show', $saved)
            ->with('success', 'Harvest report generated and saved.');
    }

    public function show(GeneratedHarvestReport $generatedHarvestReport, Request $request)
    {
        $this->authorizeReport($generatedHarvestReport);

        $context = $generatedHarvestReport->context ?? [];
        $ids = $generatedHarvestReport->filters['_report_ids'] ?? [];
        $reports = HarvestReport::with(['farmer', 'member'])
            ->whereIn('id', is_array($ids) ? $ids : [])
            ->orderByDesc('harvest_date')
            ->get();

        $language = $request->query('lang') === 'tl' ? 'tl' : $generatedHarvestReport->default_language;

        return view('admin.harvests.generated.show', [
            'report' => $generatedHarvestReport,
            'context' => $context,
            'reports' => $reports,
            'scope' => $this->scopeLabel($generatedHarvestReport->filters ?? []),
            'visual' => $context['visual'] ?? $this->analytics->forReports($reports),
            'language' => $language,
            'downloadBaseName' => $generatedHarvestReport->downloadBaseName(),
            'savedReports' => auth()->user()->generatedHarvestReports()->latest()->get(),
            'narratives' => [
                'en' => $this->narrativeSections($generatedHarvestReport->narrative_en),
                'tl' => $this->narrativeSections($generatedHarvestReport->narrative_tl),
            ],
        ]);
    }

    public function language(Request $request, GeneratedHarvestReport $generatedHarvestReport): \Illuminate\Http\JsonResponse
    {
        $this->authorizeReport($generatedHarvestReport);

        $validated = $request->validate([
            'language' => ['required', 'in:en,tl'],
        ]);

        $generatedHarvestReport->update(['default_language' => $validated['language']]);

        return response()->json(['language' => $validated['language']]);
    }

    public function download(GeneratedHarvestReport $generatedHarvestReport, Request $request): Response
    {
        $this->authorizeReport($generatedHarvestReport);

        $requested = $request->query('lang');
        $language = in_array($requested, ['en', 'tl'], true)
            ? $requested
            : ($generatedHarvestReport->default_language ?: 'en');
        $html = view('admin.harvests.generated.download', [
            'report' => $generatedHarvestReport,
            'context' => $generatedHarvestReport->context ?? [],
            'visual' => $generatedHarvestReport->context['visual'] ?? [],
            'narratives' => [
                'en' => $this->narrativeSections($generatedHarvestReport->narrative_en),
                'tl' => $this->narrativeSections($generatedHarvestReport->narrative_tl),
            ],
            'language' => $language,
            'scope' => $this->scopeLabel($generatedHarvestReport->filters ?? []),
        ])->render();

        $filename = $generatedHarvestReport->downloadFilename($language);

        return response($html, 200)
            ->header('Content-Type', 'text/html; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="' . $filename . '"');
    }

    public function destroy(GeneratedHarvestReport $generatedHarvestReport): RedirectResponse
    {
        $this->authorizeReport($generatedHarvestReport);
        $generatedHarvestReport->delete();

        return redirect()
            ->route('admin.harvests.reports.generate', ['tab' => 'saved'])
            ->with('success', 'Saved report removed.');
    }

    public function exportCsv(Request $request): Response
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        $filters = $this->filterService->cleanStudio($request->query());
        $reports = $this->filteredReports($filters);

        $handle = fopen('php://temp', 'w+');
        fputcsv($handle, [
            'ID', 'Farmer', 'Crop', 'Variety', 'Season', 'Year', 'Area (ha)',
            'Yield (MT)', 'Expenses (PHP)', 'Harvest Date',
        ]);

        foreach ($reports as $report) {
            $attributes = $report->getAttributes();
            fputcsv($handle, [
                $report->id,
                $report->reporterName(),
                $report->crop_type,
                $report->variety,
                $report->season,
                $report->harvest_date?->format('Y') ?? '',
                number_format((float) ($attributes['land_area'] ?? 0), 2, '.', ''),
                number_format((float) ($attributes['actual_yield_metric_tons'] ?? 0), 2, '.', ''),
                number_format((float) ($attributes['actual_total_expenses'] ?? 0), 2, '.', ''),
                $report->harvest_date?->format('Y-m-d') ?? '',
            ]);
        }

        rewind($handle);
        $content = (string) stream_get_contents($handle);
        fclose($handle);

        return response($content, 200)
            ->header('Content-Type', 'text/csv; charset=utf-8')
            ->header('Content-Disposition', 'attachment; filename="aquilala-harvest-scope-'
                . now()->format('Y-m-d') . '.csv"');
    }

    private function authorizeReport(GeneratedHarvestReport $report): void
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless((int) $report->user_id === (int) auth()->id(), 403);
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function filteredReports(array $filters): Collection
    {
        $query = HarvestReport::with(['farmer', 'member']);
        $this->filterService->apply($query, $filters);

        return $query->orderBy('harvest_date')->get();
    }

    /**
     * @param  Collection<int, HarvestReport>  $records
     */
    private function sortRecords(Collection $records, string $search, string $sort, string $dir): Collection
    {
        $needle = mb_strtolower($search);

        if ($needle !== '') {
            $records = $records->filter(function (HarvestReport $report) use ($needle) {
                $haystack = mb_strtolower(
                    $report->reporterName() . ' '
                    . (string) $report->crop_type . ' '
                    . (string) $report->variety . ' '
                    . (string) $report->season . ' '
                    . ($report->harvest_date?->format('Y') ?? '')
                );

                return str_contains($haystack, $needle);
            });
        }

        $key = fn (HarvestReport $report): mixed => match ($sort) {
            'yield' => (float) ($report->getAttributes()['actual_yield_metric_tons'] ?? 0),
            'expenses' => (float) ($report->getAttributes()['actual_total_expenses'] ?? 0),
            'area' => (float) ($report->getAttributes()['land_area'] ?? 0),
            'crop' => mb_strtolower((string) $report->crop_type),
            default => $report->harvest_date?->timestamp ?? 0,
        };

        return $dir === 'asc'
            ? $records->sortBy($key)->values()
            : $records->sortByDesc($key)->values();
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function defaultTitle(array $filters): string
    {
        $scope = $this->scopeLabel($filters);

        return $scope === 'All recorded harvest reports'
            ? 'Actual Harvest Report — ' . now()->format('F Y')
            : 'Actual Harvest Report — ' . $scope;
    }

    /**
     * @param  array<string, mixed>  $filters
     */
    private function scopeLabel(array $filters): string
    {
        $labels = [
            'years' => 'Years',
            'q' => 'Search',
            'crop' => 'Crop',
            'crops' => 'Crops',
            'season' => 'Season',
            'farmer' => 'Farmer',
            'year' => 'Year',
            'month' => 'Month',
        ];

        $parts = [];

        foreach ($labels as $key => $label) {
            $value = $filters[$key] ?? null;

            if (is_array($value)) {
                $values = array_values(array_filter(array_map('strval', $value), fn (string $item) => $item !== ''));
                if ($values !== []) {
                    $parts[] = $label . ': ' . implode(', ', $values);
                }

                continue;
            }

            if (is_string($value) && $value !== '' && $value !== 'all' && $value !== 'both') {
                $parts[] = $label . ': ' . ($key === 'season' ? ucfirst($value) : $value);
            }
        }

        return $parts === [] ? 'All recorded harvest reports' : implode(', ', $parts);
    }

    /**
     * @return array<string, array<int, string>>
     */
    private function narrativeSections(?string $narrative): array
    {
        $sections = [];
        $current = 'Overview';

        foreach (preg_split('/\R/u', trim((string) $narrative)) ?: [] as $line) {
            $line = trim($line);
            if ($line === '') {
                continue;
            }

            if (str_starts_with($line, '### ')) {
                $current = substr($line, 4);
                $sections[$current] = [];

                continue;
            }

            $sections[$current][] = $line;
        }

        return $sections;
    }
}
