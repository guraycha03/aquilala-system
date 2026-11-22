<?php

namespace App\Services;

use App\Models\Member;
use Illuminate\Support\Collection;

/**
 * The filter contract shared by Harvest Reports and the AI report builder.
 *
 * Keeping it here means "generate a report from these filters" covers exactly
 * the rows the officer was looking at on the table page; the two pages cannot
 * drift into different definitions of "wet season" or "this crop".
 */
class HarvestReportFilterService
{
    /** @var array<int, string> */
    public const KEYS = ['q', 'crop', 'season', 'farmer', 'year', 'month'];

    /**
     * Apply the supported report filters to a HarvestReport query.
     *
     * @param  mixed  $query  Eloquent builder for HarvestReport
     * @param  array<string, mixed>  $filters
     */
    public function apply($query, array $filters): void
    {
        $search = trim((string) ($filters['q'] ?? ''));
        $crop = trim((string) ($filters['crop'] ?? ''));
        $season = trim((string) ($filters['season'] ?? ''));
        $farmer = trim((string) ($filters['farmer'] ?? ''));
        $year = trim((string) ($filters['year'] ?? ''));
        $month = trim((string) ($filters['month'] ?? ''));

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
            $memberIds = $this->memberIdsNamed($farmer);

            $query->where(function ($q) use ($farmer, $memberIds) {
                $q->whereHas('farmer', fn ($fq) => $fq->where('name', $farmer));

                if ($memberIds->isNotEmpty()) {
                    $q->orWhereIn('member_id', $memberIds);
                }
            });
        }
        if ($year !== '' && $year !== 'all' && ctype_digit($year)) {
            $query->whereYear('harvest_date', (int) $year);
        }
        if ($month !== '' && $month !== 'all' && ctype_digit($month)) {
            $query->whereMonth('harvest_date', (int) $month);
        }

        // The studio uses multi-select year/crop tiles. An empty selection
        // means "all", so it deliberately adds no constraint.
        $years = $filters['years'] ?? [];
        if (is_array($years) && $years !== []) {
            // SQLite/MySQL both accept a YEAR() comparison through whereYear
            // only one at a time, so build an OR group for the selected years.
            $query->where(function ($q) use ($years) {
                foreach ($years as $selectedYear) {
                    if (ctype_digit((string) $selectedYear)) {
                        $q->orWhereYear('harvest_date', (int) $selectedYear);
                    }
                }
            });
        }

        $crops = $filters['crops'] ?? [];
        if (is_array($crops) && $crops !== []) {
            $query->whereIn('crop_type', array_values(array_filter(
                array_map('strval', $crops),
                fn (string $value) => $value !== ''
            )));
        }
    }

    /**
     * Clean the studio's multi-select filter contract while still accepting the
     * single-value filters carried from Harvest Reports.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, mixed>
     */
    public function cleanStudio(array $input): array
    {
        $filters = $this->clean($input);

        if (array_key_exists('years', $input)) {
            $years = is_array($input['years']) ? $input['years'] : [];
            $years = array_values(array_filter(
                array_map('strval', $years),
                fn (string $year) => ctype_digit($year)
            ));

            unset($filters['year']);
            if ($years !== []) {
                $filters['years'] = $years;
            }
        }

        if (array_key_exists('crops', $input)) {
            $crops = is_array($input['crops']) ? $input['crops'] : [];
            $crops = array_values(array_filter(
                array_map('trim', array_map('strval', $crops)),
                fn (string $crop) => $crop !== '' && $crop !== 'all'
            ));

            unset($filters['crop']);
            if ($crops !== []) {
                $filters['crops'] = $crops;
            }
        }

        // The prototype's "Both Seasons" is the same as leaving season on All.
        if (($filters['season'] ?? '') === 'both') {
            unset($filters['season']);
        }

        return $filters;
    }

    /**
     * Keep only the recognised filter keys, with empty values removed. This is
     * what gets carried from the table page and stored with a saved report.
     *
     * @param  array<string, mixed>  $input
     * @return array<string, string>
     */
    public function clean(array $input): array
    {
        $clean = [];

        foreach (self::KEYS as $key) {
            $value = trim((string) ($input[$key] ?? ''));

            if ($value !== '' && $value !== 'all') {
                $clean[$key] = $value;
            }
        }

        return $clean;
    }

    /**
     * @return Collection<int, int>
     */
    private function memberIdsNamed(string $name): Collection
    {
        $needle = self::normalizeReporterName($name);

        if ($needle === '') {
            return collect();
        }

        return Member::query()
            ->get(['id', 'name', 'first_name', 'middle_initial', 'last_name'])
            ->filter(fn (Member $member) => self::normalizeReporterName($member->fullName()) === $needle)
            ->pluck('id')
            ->values();
    }

    public static function normalizeReporterName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/u', ' ', str_replace('.', '', trim($name))));
    }
}
