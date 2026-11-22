<?php

namespace App\Services;

use App\Support\HarvestDigestRow;
use Illuminate\Support\Collection;

/**
 * Prototype-shaped analytics for the AI Harvest Report studio.
 *
 * Every figure comes from the rows Aquilala actually stores. Fields the
 * prototype displayed but the system does not keep (expected yield, target,
 * revenue) stay null instead of being invented.
 */
class HarvestReportAnalyticsService
{
    /**
     * @param  Collection<int, \App\Models\HarvestReport>  $reports
     * @return array{years: array<int, array<string, mixed>>, crops: array<int, array<string, mixed>>, months: array<int, int>}
     */
    public function options(Collection $reports): array
    {
        $rows = $reports->map(fn ($report) => HarvestDigestRow::from($report))->values();

        $years = $rows
            ->groupBy(fn (HarvestDigestRow $row) => $row->harvest_date->year)
            ->map(function (Collection $group, int $year) {
                $summary = $this->summarize($group);

                return [
                    'year' => $year,
                    'count' => $summary['reports'],
                    'yield_tons' => $summary['yield_tons'],
                ];
            })
            ->sortByDesc('year')
            ->values()
            ->all();

        $crops = $rows
            ->filter(fn (HarvestDigestRow $row) => $row->crop_type !== null && $row->crop_type !== '')
            ->groupBy('crop_type')
            ->map(function (Collection $group, string $crop) {
                $summary = $this->summarize($group);

                return [
                    'crop' => $crop,
                    'count' => $summary['reports'],
                    'area_ha' => $summary['area_ha'],
                ];
            })
            ->sortBy('crop')
            ->values()
            ->all();

        $months = $rows
            ->pluck('harvest_date')
            ->filter()
            ->map(fn ($date) => (int) $date->month)
            ->unique()
            ->sort()
            ->values()
            ->all();

        return ['years' => $years, 'crops' => $crops, 'months' => $months];
    }

    /**
     * @param  Collection<int, \App\Models\HarvestReport>  $reports
     * @return array<string, mixed>
     */
    public function forReports(Collection $reports): array
    {
        $rows = $reports->map(fn ($report) => HarvestDigestRow::from($report))->values();
        $totals = $this->summarize($rows);

        $byYear = $this->byYear($rows);
        $byCrop = $this->byCrop($rows, (float) ($totals['yield_tons'] ?? 0));
        $wet = $this->seasonSummary($rows, 'wet');
        $dry = $this->seasonSummary($rows, 'dry');

        $totalYield = (float) ($totals['yield_tons'] ?? 0);
        $wetYield = (float) ($wet['total_yield_mt'] ?? 0);
        $wetShare = $totalYield > 0 ? (int) round(($wetYield / $totalYield) * 100) : 0;

        return [
            'total_records' => (int) ($totals['reports'] ?? 0),
            'total_farmers' => (int) ($totals['farmers'] ?? 0),
            'total_area_ha' => (float) ($totals['area_ha'] ?? 0),
            'total_yield_mt' => $totalYield,
            'avg_yield_per_ha' => $totals['yield_per_ha'] ?? null,
            'total_expenses_php' => (float) ($totals['expenses_php'] ?? 0),
            'cost_per_mt' => $totals['expenses_per_ton'] ?? null,
            'total_revenue_php' => null,
            'net_return_php' => null,
            'target_attainment_pct' => null,
            'yoy_yield_delta_pct' => $this->yearOverYear($byYear),
            'first_harvest' => $rows->isNotEmpty() ? $rows->first()->harvest_date->format('M d, Y') : null,
            'last_harvest' => $rows->isNotEmpty() ? $rows->last()->harvest_date->format('M d, Y') : null,
            'wet_summary' => $wet,
            'dry_summary' => $dry,
            'wet_share_pct' => $wetShare,
            'dry_share_pct' => 100 - $wetShare,
            'by_year' => $byYear,
            'by_crop' => $byCrop,
        ];
    }

    /**
     * @param  Collection<int, HarvestDigestRow>  $rows
     * @return array<string, mixed>
     */
    private function summarize(Collection $rows): array
    {
        $area = (float) $rows->sum('land_area');
        $yield = (float) $rows->sum('actual_yield_metric_tons');
        $spend = (float) $rows->sum('actual_total_expenses');

        return [
            'reports' => $rows->count(),
            'farmers' => $rows->map(fn (HarvestDigestRow $row) => $row->reporterKey())->unique()->count(),
            'area_ha' => round($area, 2),
            'yield_tons' => round($yield, 2),
            'yield_per_ha' => $area > 0 ? round($yield / $area, 2) : null,
            'expenses_php' => round($spend, 2),
            'expenses_per_ton' => $yield > 0 ? round($spend / $yield, 0) : null,
        ];
    }

    /**
     * @param  Collection<int, HarvestDigestRow>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function byYear(Collection $rows): array
    {
        return $rows
            ->groupBy(fn (HarvestDigestRow $row) => $row->harvest_date->year)
            ->map(function (Collection $group, int $year) {
                $summary = $this->summarize($group);
                $wet = $this->seasonSummary($group, 'wet');
                $dry = $this->seasonSummary($group, 'dry');

                return [
                    'year' => $year,
                    'wet_yield_mt' => $wet['total_yield_mt'],
                    'dry_yield_mt' => $dry['total_yield_mt'],
                    'total_yield_mt' => $summary['yield_tons'],
                    'wet_area_ha' => $wet['total_area_ha'],
                    'dry_area_ha' => $dry['total_area_ha'],
                    'total_area_ha' => $summary['area_ha'],
                    'wet_expenses_php' => $wet['total_expenses_php'],
                    'dry_expenses_php' => $dry['total_expenses_php'],
                    'total_expenses_php' => $summary['expenses_php'],
                    'record_count' => $summary['reports'],
                ];
            })
            ->sortBy('year')
            ->values()
            ->all();
    }

    /**
     * @param  Collection<int, HarvestDigestRow>  $rows
     * @return array<string, mixed>
     */
    private function seasonSummary(Collection $rows, string $season): array
    {
        $subset = $rows->filter(fn (HarvestDigestRow $row) => $row->season === $season);
        $summary = $this->summarize($subset);

        return [
            'season' => $season,
            'record_count' => $summary['reports'],
            'total_area_ha' => $summary['area_ha'],
            'total_yield_mt' => (float) $summary['yield_tons'],
            'yield_per_ha' => $summary['yield_per_ha'],
            'total_expenses_php' => $summary['expenses_php'],
            'cost_per_mt' => $summary['expenses_per_ton'],
            'target_attainment_pct' => null,
        ];
    }

    /**
     * @param  Collection<int, HarvestDigestRow>  $rows
     * @return array<int, array<string, mixed>>
     */
    private function byCrop(Collection $rows, float $totalYield): array
    {
        return $rows
            ->filter(fn (HarvestDigestRow $row) => $row->crop_type !== null && $row->crop_type !== '')
            ->groupBy('crop_type')
            ->map(function (Collection $group, string $crop) use ($totalYield) {
                $summary = $this->summarize($group);

                return [
                    'crop_type' => $crop,
                    'record_count' => $summary['reports'],
                    'total_area_ha' => $summary['area_ha'],
                    'total_yield_mt' => $summary['yield_tons'],
                    'share_pct' => $totalYield > 0
                        ? (int) round(((float) $summary['yield_tons'] / $totalYield) * 100)
                        : 0,
                    'yield_per_ha' => $summary['yield_per_ha'],
                    'cost_per_mt' => $summary['expenses_per_ton'],
                    'target_attainment_pct' => null,
                ];
            })
            ->sortByDesc('total_yield_mt')
            ->values()
            ->all();
    }

    /**
     * @param  array<int, array<string, mixed>>  $byYear
     */
    private function yearOverYear(array $byYear): ?float
    {
        if (count($byYear) < 2) {
            return null;
        }

        $previous = (float) $byYear[count($byYear) - 2]['total_yield_mt'];
        $recent = (float) $byYear[count($byYear) - 1]['total_yield_mt'];

        if ($previous <= 0) {
            return null;
        }

        return round((($recent - $previous) / $previous) * 100, 1);
    }
}
