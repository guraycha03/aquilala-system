<?php

namespace App\Http\Controllers;

use App\Helpers\SettingsHelper;
use App\Models\Crop;
use App\Models\HarvestReport;
use App\Services\ActivityLogger;
use App\Services\HarvestInsightService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;

class HarvestReportController extends Controller
{
    public function __construct(
        private readonly HarvestInsightService $insights,
    ) {}

    /**
     * Farmer-facing list of their own harvest reports.
     * Reads the same harvest_reports table the admin page reads, so a report
     * recorded here is immediately visible to the admin ("synced").
     */
    public function farmerIndex(Request $request)
    {
        $query = HarvestReport::with('farmer')
            ->where('user_id', Auth::id());

        [$reports, $stats, $allReports, $yearOptions, $monthOptions] = $this->applyFiltersAndStats($query, $request);

        return view('farmer.harvests.index', compact('reports', 'stats', 'allReports', 'yearOptions', 'monthOptions'));
    }

    /**
     * Farmer-facing harvest report submission form (dashboard sub-page).
     */
    public function farmerCreate()
    {
        $cropTypes = Crop::orderBy('name')->pluck('name');

        return view('farmer.harvests.create', compact('cropTypes'));
    }

    /**
     * Admin landing page: headline metrics plus the period / comparison /
     * farmer charts, and navigation into the supporting sub-pages. The raw
     * report list lives on `admin.harvests.reports`.
     *
     * The charts read from the same HarvestInsightService digest the AI page
     * sends to the model, so the picture on this page and the figures the
     * analysis quotes can never drift apart.
     */
    public function adminIndex(Request $request)
    {
        [, $stats] = $this->applyFiltersAndStats(HarvestReport::with('farmer'), $request);

        return view('admin.harvests.index', [
            'stats'   => $stats,
            'context' => $this->insights->context(),
        ]);
    }

    /**
     * Admin sub-page holding the full, filterable harvest report table.
     */
    public function reportsIndex(Request $request)
    {
        [$reports, $stats, $allReports, $yearOptions, $monthOptions] = $this->applyFiltersAndStats(
            HarvestReport::with('farmer'),
            $request
        );

        $farmerOptions = $allReports->pluck('farmer.name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('admin.harvests.reports', compact(
            'reports',
            'stats',
            'allReports',
            'farmerOptions',
            'yearOptions',
            'monthOptions'
        ));
    }

    /**
     * AI Harvest Insights subpage.
     *
     * Rendered with the digest already in hand so the page can show exactly
     * which harvest records the analysis will draw from, before anything is
     * sent to the model.
     */
    public function aiIndex()
    {
        $context = $this->insights->context();

        return view('admin.harvests.ai', ['context' => $context]);
    }

    /**
     * Answer one chat question about the harvest digest. Returns JSON so the
     * chat can run its own loading and error states without a page round trip.
     *
     * Always answers with 200 + a `reply`: when the model is offline or
     * unusable, `source` reports `fallback` and the text comes from
     * deterministic PHP over the same records.
     */
    public function aiAsk(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'message'              => ['required', 'string', 'min:1', 'max:500'],
            'messages'             => ['sometimes', 'array', 'max:20'],
            'messages.*.role'      => ['required', 'in:user,assistant'],
            'messages.*.content'   => ['required', 'string', 'max:2000'],
        ]);

        $history = collect($validated['messages'] ?? [])
            ->map(fn (array $turn) => [
                'role'    => $turn['role'],
                'content' => mb_substr($turn['content'], 0, 300),
            ])
            ->reject(fn (array $turn) => trim($turn['content']) === '')
            ->values()
            ->all();

        $result = $this->insights->ask(trim($validated['message']), $history);

        return response()->json([
            'source'  => $result['source'],
            'reply'   => $result['reply'],
            'context' => $result['context'],
        ]);
    }

    /**
     * Shared filter + summary logic for both index pages.
     */
    protected function applyFiltersAndStats($query, Request $request): array
    {
        $search = trim((string) $request->query('q', ''));
        $crop = trim((string) $request->query('crop', ''));
        $season = trim((string) $request->query('season', ''));
        $farmer = trim((string) $request->query('farmer', ''));
        $year = trim((string) $request->query('year', ''));
        $month = trim((string) $request->query('month', ''));

        if ($search !== '') {
            $query->where('crop_type', 'like', "%{$search}%");
        }
        if ($crop !== '') {
            $query->where('crop_type', $crop);
        }
        if (in_array($season, ['wet', 'dry'], true)) {
            $query->where('season', $season);
        }
        if ($farmer !== '') {
            $query->whereHas('farmer', fn ($q) => $q->where('name', $farmer));
        }
        if ($year !== '' && $year !== 'all') {
            $query->whereYear('harvest_date', $year);
        }
        if ($month !== '' && $month !== 'all') {
            $query->whereMonth('harvest_date', $month);
        }

        // Summary figures reflect the user's full dataset (before filters),
        // mirroring how the quotations/activity pages compute their stats.
        $allReports = collect(match (true) {
            Auth::user()->role === 'admin' => HarvestReport::with('farmer')->orderByDesc('harvest_date')->get(),
            default => HarvestReport::with('farmer')->where('user_id', Auth::id())->orderByDesc('harvest_date')->get(),
        });

        $stats = [
            'reports' => $allReports->count(),
            'yield' => SettingsHelper::formatYield((float) $allReports->sum('actual_yield_metric_tons')),
            'yield_atoms' => $allReports->sum('actual_yield_metric_tons'),
            'expenses' => (float) $allReports->sum('actual_total_expenses'),
            'area' => (float) $allReports->sum('land_area'),
            'farmers' => $allReports->pluck('user_id')->unique()->count(),
        ];

        // Get unique years and months for filter dropdowns
        $yearOptions = $allReports->pluck('harvest_date')
            ->filter()
            ->map(fn ($d) => $d->year)
            ->unique()
            ->sortDesc()
            ->values()
            ->map(fn ($y) => [(string) $y, (string) $y])
            ->all();

        $monthOptions = $allReports->pluck('harvest_date')
            ->filter()
            ->map(fn ($d) => $d->month)
            ->unique()
            ->sort()
            ->values()
            ->map(fn ($m) => [(string) $m, Carbon::createFromDate(2000, $m, 1)->format('F')])
            ->all();

        $reports = $query->orderByDesc('harvest_date')->get();

        return [$reports, $stats, $allReports, $yearOptions, $monthOptions];
    }

    /**
     * Record a new harvest report (farmer only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'crop_type' => ['required', 'string', 'max:100'],
            'season' => ['required', 'in:wet,dry'],
            'land_area' => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'actual_yield_metric_tons' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'actual_total_expenses' => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'harvest_date' => ['required', 'date', 'before_or_equal:today'],
        ]);

        $validated['user_id'] = Auth::id();
        $validated['quotation_id'] = null;

        $report = HarvestReport::create($validated);

        ActivityLogger::harvestReportCreated($report);

        return back()->with('success', sprintf(
            'Harvest report recorded for %s — %s recorded on %s.',
            $report->crop_type,
            SettingsHelper::formatYield((float) $report->actual_yield_metric_tons),
            $report->harvest_date->format('M d, Y')
        ));
    }

    /**
     * Remove a harvest report (farmer only, own reports).
     */
    public function destroy(HarvestReport $harvestReport)
    {
        if ($harvestReport->user_id !== Auth::id()) {
            abort(403);
        }

        ActivityLogger::harvestReportDeleted($harvestReport);
        $harvestReport->delete();

        return back()->with('success', 'Harvest report has been removed.');
    }
}
