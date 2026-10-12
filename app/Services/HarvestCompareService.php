<?php

namespace App\Services;

use App\Models\FarmerCrop;
use App\Models\HarvestReport;
use Illuminate\Support\Collection;

/**
 * Estimate vs Actual — the capstone comparison.
 *
 * Pairs the yield estimate captured when a farmer planted (farmer_crops.
 * estimated_yield) with the actual yield later filed in the harvest report
 * (harvest_reports.actual_yield_metric_tons). Reports with no linked batch
 * fall back to the Sorsogon crop-library baseline (kg/ha × land area) so old
 * data still gets an honest estimate column.
 *
 * Everything is normalised to metric tons before it reaches the KPI cards,
 * the detail table or the Chart.js payload — estimates arrive in whatever
 * unit the crop library / modal stored (usually kg), reports always in MT.
 */
class HarvestCompareService
{
    public function __construct(
        private readonly CropLibraryService $library,
    ) {}

    /**
     * @param string $scope  'farmer' (own batches/reports only) or 'admin'
     * @param int|null $userId Required when $scope === 'farmer'
     * @param array $filters year, season, crop, farmer (admin), group_by
     */
    public function compare(string $scope, ?int $userId = null, array $filters = []): array
    {
        $year = trim((string) ($filters['year'] ?? ''));
        $season = trim((string) ($filters['season'] ?? ''));
        $crop = trim((string) ($filters['crop'] ?? ''));
        $farmerId = (int) ($filters['farmer'] ?? 0);
        $groupBy = in_array(($filters['group_by'] ?? ''), ['crop', 'period'], true)
            ? $filters['group_by']
            : 'crop';

        // ── Source 1: linked pairs (batch estimate + reported actual) ──
        // Dates/years always come from the report, so the batch only needs
        // its link — some older syncs never stamped actual_harvest_date.
        $linkedQuery = FarmerCrop::query()
            ->with(['crop', 'user', 'harvestReport'])
            ->whereNotNull('harvest_report_id')
            ->when($scope === 'farmer', fn ($q) => $q->where('user_id', $userId));

        $linked = $linkedQuery->get()->filter(fn (FarmerCrop $b) => $b->harvestReport)->values();

        // ── Source 2: reports with no linked batch (baseline estimate) ──
        $looseQuery = HarvestReport::query()
            ->with('farmer')
            ->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('farmer_crops')
                    ->whereColumn('farmer_crops.harvest_report_id', 'harvest_reports.id');
            })
            ->when($scope === 'farmer', fn ($q) => $q->where('user_id', $userId));

        $loose = $looseQuery->get();

        // ── Build rows ────────────────────────────────────────────────
        $rows = [];

        foreach ($linked as $batch) {
            $report = $batch->harvestReport;
            $cropName = $batch->crop?->name ?? $report->crop_type;

            $rows[] = [
                'farmer' => $batch->user?->name ?? '—',
                'farmer_id' => (int) $batch->user_id,
                'crop' => $cropName,
                'batch' => $batch->batch_name,
                'season' => $batch->season ?? $report->season,
                'year' => $report->harvest_date?->year,
                'harvest_date' => $report->harvest_date?->toDateString(),
                'land_area' => (float) $batch->land_area,
                'estimate_mt' => $this->estimateForBatch($batch, $cropName),
                'estimate_source' => $batch->estimated_yield !== null ? 'batch' : 'baseline',
                'actual_mt' => (float) $report->actual_yield_metric_tons,
                'linked' => true,
            ];
        }

        foreach ($loose as $report) {
            $rows[] = [
                'farmer' => $report->farmer?->name ?? '—',
                'farmer_id' => (int) $report->user_id,
                'crop' => $report->crop_type,
                'batch' => null,
                'season' => $report->season,
                'year' => $report->harvest_date?->year,
                'harvest_date' => $report->harvest_date?->toDateString(),
                'land_area' => (float) $report->land_area,
                'estimate_mt' => $this->baselineEstimate($report->crop_type, (float) $report->land_area),
                'estimate_source' => 'baseline',
                'actual_mt' => (float) $report->actual_yield_metric_tons,
                'linked' => false,
            ];
        }

        // ── Filters (applied to rows so KPIs, table and chart agree) ──
        $rows = collect($rows)
            ->filter(function (array $r) use ($year, $season, $crop, $farmerId) {
                if ($year !== '' && $year !== 'all' && (string) $r['year'] !== $year) return false;
                if (in_array($season, ['wet', 'dry'], true) && $r['season'] !== $season) return false;
                if ($crop !== '' && strcasecmp($r['crop'], $crop) !== 0) return false;
                if ($farmerId > 0 && $r['farmer_id'] !== $farmerId) return false;
                return true;
            })
            ->sortBy('harvest_date')
            ->values();

        // ── KPIs ──────────────────────────────────────────────────────
        $totalEstimate = (float) $rows->sum('estimate_mt');
        $totalActual = (float) $rows->sum('actual_mt');
        $variance = $totalActual - $totalEstimate;

        $kpis = [
            'pairs' => $rows->count(),
            'total_estimate_mt' => $totalEstimate,
            'total_actual_mt' => $totalActual,
            'variance_mt' => $variance,
            'accuracy_pct' => $totalEstimate > 0
                ? round(($totalActual / $totalEstimate) * 100, 1)
                : null,
            'over_count' => $rows->filter(fn (array $r) => $r['actual_mt'] > $r['estimate_mt'])->count(),
            'under_count' => $rows->filter(fn (array $r) => $r['actual_mt'] < $r['estimate_mt'])->count(),
            'on_count' => $rows->filter(fn (array $r) => abs($r['actual_mt'] - $r['estimate_mt']) < 0.005)->count(),
            'linked_pairs' => $rows->filter(fn (array $r) => $r['linked'])->count(),
            'baseline_pairs' => $rows->filter(fn (array $r) => ! $r['linked'])->count(),
        ];

        // ── Chart payload ─────────────────────────────────────────────
        $grouped = $rows->groupBy(fn (array $r) => $groupBy === 'crop'
            ? $r['crop']
            : trim(($r['year'] ?? '?').' '.ucfirst($r['season'] ?? '')));

        $chart = [
            'labels' => $grouped->keys()->values()->all(),
            'estimate' => $grouped->map(fn ($g) => round((float) $g->sum('estimate_mt'), 3))->values()->all(),
            'actual' => $grouped->map(fn ($g) => round((float) $g->sum('actual_mt'), 3))->values()->all(),
            'pairs' => $grouped->map(fn ($g) => $g->count())->values()->all(),
        ];

        // ── Filter options ────────────────────────────────────────────
        $allRowsForOptions = $this->unfilteredRows($scope, $userId);

        $options = [
            'years' => $allRowsForOptions->pluck('year')->filter()->unique()->sortDesc()->values()->all(),
            'crops' => $allRowsForOptions->pluck('crop')->filter()->unique()->sort()->values()->all(),
            'farmers' => $scope === 'admin'
                ? $allRowsForOptions->pluck('farmer')->filter()->unique()->sort()->values()->all()
                : [],
        ];

        return [
            'rows' => $rows->all(),
            'kpis' => $kpis,
            'chart' => $chart,
            'options' => $options,
            'group_by' => $groupBy,
            'filters' => [
                'year' => $year,
                'season' => $season,
                'crop' => $crop,
                'farmer' => $filters['farmer'] ?? '',
            ],
        ];
    }

    /**
     * Options for the filter bar without the filter constraints applied
     * (every year/crop/farmer that has data at all).
     */
    protected function unfilteredRows(string $scope, ?int $userId): Collection
    {
        $linked = FarmerCrop::with(['crop', 'user', 'harvestReport'])
            ->whereNotNull('harvest_report_id')
            ->when($scope === 'farmer', fn ($q) => $q->where('user_id', $userId))
            ->get()
            ->filter(fn (FarmerCrop $b) => $b->harvestReport)
            ->map(fn (FarmerCrop $b) => [
                'farmer' => $b->user?->name ?? '—',
                'crop' => $b->crop?->name ?? $b->harvestReport->crop_type,
                'year' => $b->harvestReport->harvest_date?->year,
            ]);

        $loose = HarvestReport::with('farmer')
            ->whereNotExists(function ($sub) {
                $sub->selectRaw('1')
                    ->from('farmer_crops')
                    ->whereColumn('farmer_crops.harvest_report_id', 'harvest_reports.id');
            })
            ->when($scope === 'farmer', fn ($q) => $q->where('user_id', $userId))
            ->get()
            ->map(fn (HarvestReport $r) => [
                'farmer' => $r->farmer?->name ?? '—',
                'crop' => $r->crop_type,
                'year' => $r->harvest_date?->year,
            ]);

        return $linked->concat($loose)->values();
    }

    /**
     * Estimate for a linked batch: its own planting-time estimate when one
     * was recorded, otherwise the crop-library baseline for its land area.
     */
    protected function estimateForBatch(FarmerCrop $batch, string $cropName): float
    {
        if ($batch->estimated_yield !== null) {
            return $this->toMetricTons((float) $batch->estimated_yield, $batch->yield_unit);
        }

        return $this->baselineEstimate($cropName, (float) $batch->land_area);
    }

    /**
     * Baseline: crop-library kg/ha (or its stated unit) × land area.
     * Returns 0 when the crop has no library entry — the chart and KPIs
     * treat that honestly rather than inventing a number.
     */
    public function baselineEstimate(string $cropName, float $landArea): float
    {
        $entry = $this->library->forName($cropName);

        if (! $entry) {
            return 0.0;
        }

        $perHa = (float) ($entry['default_yield_per_ha'] ?? 0);
        $unit = $entry['yield_unit'] ?? config('crop_library.defaults.yield_unit', 'kg');

        if ($perHa <= 0 || $landArea <= 0) {
            return 0.0;
        }

        return $this->toMetricTons($perHa * $landArea, $unit);
    }

    /**
     * Normalise a yield figure to metric tons. Unknown units are assumed to
     * already be MT (that is what reports, quotations and syncHarvestReport
     * all use), which keeps `tons`/`mt`/bare values stable.
     */
    public function toMetricTons(float $value, ?string $unit): float
    {
        $u = strtolower(trim((string) $unit));

        return match (true) {
            in_array($u, ['kg', 'kgs', 'kilogram', 'kilograms'], true) => $value / 1000,
            in_array($u, ['g', 'gram', 'grams'], true) => $value / 1_000_000,
            in_array($u, ['lb', 'lbs', 'pound', 'pounds'], true) => $value * 0.000453592,
            default => $value,
        };
    }
}
