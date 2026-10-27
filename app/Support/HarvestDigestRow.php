<?php

namespace App\Support;

use App\Models\HarvestReport;
use Illuminate\Support\Carbon;

/**
 * One harvest report, reduced to the fields the insight digest aggregates.
 *
 * The digest walks the whole harvest history many times — grouped by
 * reporter, season, crop and period — and each of those passes used to pay
 * Eloquent's attribute-access machinery (casts, mutator caches, relation
 * checks) once per cell. Across the association's full history that ran into
 * tens of thousands of accesses and made the admin harvest pages slower than
 * the PWA service worker's navigation timeout, which bounced the click back
 * to the dashboard. Reading each cell exactly once, into plain properties,
 * turns every later pass into plain PHP arithmetic.
 *
 * Property names deliberately match the harvest_reports columns, so the
 * Collection helpers the digest already uses (sum / pluck / groupBy / min /
 * max, all resolved through data_get) keep working unchanged.
 */
final class HarvestDigestRow
{
    public function __construct(
        public readonly int $id,
        public readonly ?int $user_id,
        public readonly ?int $member_id,
        public readonly string $season,
        public readonly ?string $crop_type,
        public readonly float $land_area,
        public readonly float $actual_yield_metric_tons,
        public readonly float $actual_total_expenses,
        public readonly Carbon $harvest_date,
        private readonly string $reporter_key,
        private readonly string $reporter_name,
    ) {}

    /**
     * Read the report once. Numeric cells come from the raw attribute array —
     * the decimal cast's string formatting is never needed here, and skipping
     * it per cell is most of the saving. The reporter identity stays on the
     * model: it needs the eager-loaded farmer/member relations.
     */
    public static function from(HarvestReport $report): self
    {
        $attrs = $report->getAttributes();
        $userId = $attrs['user_id'] ?? null;

        return new self(
            (int) $attrs['id'],
            $userId !== null ? (int) $userId : null,
            isset($attrs['member_id']) ? (int) $attrs['member_id'] : null,
            (string) ($attrs['season'] ?? ''),
            isset($attrs['crop_type']) ? (string) $attrs['crop_type'] : null,
            (float) ($attrs['land_area'] ?? 0),
            (float) ($attrs['actual_yield_metric_tons'] ?? 0),
            (float) ($attrs['actual_total_expenses'] ?? 0),
            $report->harvest_date,
            // Mirrors HarvestReport::reporterKey() over the same raw cells.
            $userId !== null ? 'u'.$userId : 'm'.($attrs['member_id'] ?? 'x'),
            $report->reporterName(),
        );
    }

    public function reporterKey(): string
    {
        return $this->reporter_key;
    }

    public function reporterName(): string
    {
        return $this->reporter_name;
    }

    /**
     * Attribute-style read, for the one caller that groups by a column name
     * chosen at runtime (splitBy).
     */
    public function getAttribute(string $name): mixed
    {
        return $this->{$name} ?? null;
    }
}
