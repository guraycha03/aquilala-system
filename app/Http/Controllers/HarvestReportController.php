<?php

namespace App\Http\Controllers;

use App\Models\Crop;
use App\Models\HarvestReport;
use App\Services\ActivityLogger;
use App\Helpers\SettingsHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HarvestReportController extends Controller
{
    /**
     * Farmer-facing list of their own harvest reports.
     * Reads the same harvest_reports table the admin page reads, so a report
     * recorded here is immediately visible to the admin ("synced").
     */
    public function farmerIndex(Request $request)
    {
        $query = HarvestReport::with('farmer')
            ->where('user_id', Auth::id());

        [$reports, $stats, $allReports] = $this->applyFiltersAndStats($query, $request);

        $cropTypes = Crop::orderBy('name')->pluck('name');

        return view('farmer.harvests.index', compact('reports', 'stats', 'allReports', 'cropTypes'));
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
     * Admin-facing list of every farmer's harvest reports (read-only).
     */
    public function adminIndex(Request $request)
    {
        $query = HarvestReport::with('farmer');

        [$reports, $stats, $allReports] = $this->applyFiltersAndStats($query, $request);

        $farmerOptions = $allReports->pluck('farmer.name')
            ->filter()
            ->unique()
            ->sort()
            ->values();

        return view('admin.harvests.index', compact('reports', 'stats', 'allReports', 'farmerOptions'));
    }

    /**
     * Shared filter + summary logic for both index pages.
     */
    protected function applyFiltersAndStats($query, Request $request): array
    {
        $search = trim((string) $request->query('q', ''));
        $crop   = trim((string) $request->query('crop', ''));
        $season = trim((string) $request->query('season', ''));
        $farmer = trim((string) $request->query('farmer', ''));

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

        // Summary figures reflect the user's full dataset (before filters),
        // mirroring how the quotations/activity pages compute their stats.
        $allReports = collect(match (true) {
            Auth::user()->role === 'admin' => HarvestReport::with('farmer')->orderByDesc('harvest_date')->get(),
            default => HarvestReport::with('farmer')->where('user_id', Auth::id())->orderByDesc('harvest_date')->get(),
        });

        $stats = [
            'reports' => $allReports->count(),
            'yield'   => SettingsHelper::formatYield((float) $allReports->sum('actual_yield_metric_tons')),
            'yield_atoms' => $allReports->sum('actual_yield_metric_tons'),
            'expenses' => (float) $allReports->sum('actual_total_expenses'),
            'area' => (float) $allReports->sum('land_area'),
            'farmers' => $allReports->pluck('user_id')->unique()->count(),
        ];

        $reports = $query->orderByDesc('harvest_date')->get();

        return [$reports, $stats, $allReports];
    }

    /**
     * Record a new harvest report (farmer only).
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'crop_type'                => ['required', 'string', 'max:100'],
            'season'                   => ['required', 'in:wet,dry'],
            'land_area'                => ['required', 'numeric', 'min:0.01', 'max:999.99'],
            'actual_yield_metric_tons' => ['required', 'numeric', 'min:0', 'max:999999.99'],
            'actual_total_expenses'    => ['required', 'numeric', 'min:0', 'max:999999999.99'],
            'harvest_date'             => ['required', 'date', 'before_or_equal:today'],
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