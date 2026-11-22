<?php

namespace App\Services;

use App\AI\AiLlmClient;
use App\AI\Prompts\HarvestReportInsightPrompt;
use App\Models\GeneratedHarvestReport;
use Throwable;

/**
 * On-demand, plain-language guide for a saved harvest report.
 *
 * The official report is already written deterministically by
 * HarvestReportTextService. This service only reads that frozen snapshot and
 * explains it in the viewer. If the model is unavailable or its answer drifts
 * from the saved figures, a report-based explanation is used instead.
 */
class HarvestReportInsightService
{
    public function __construct(private readonly AiLlmClient $llm) {}

    /**
     * @return array{text: string, source: string, source_label: string, model: ?string, cached: bool, language: string}
     */
    public function forReport(GeneratedHarvestReport $report, string $language): array
    {
        $language = $language === 'tl' ? 'tl' : 'en';
        $digest = $this->digest($report);
        $hash = hash('sha256', json_encode($digest, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        $cached = $report->aiInsightFor($language);
        if (
            is_string($cached['text'] ?? null)
            && trim((string) $cached['text']) !== ''
            && ($cached['hash'] ?? null) === $hash
        ) {
            return $this->result(
                trim((string) $cached['text']),
                (string) ($cached['source'] ?? 'fallback'),
                isset($cached['model']) ? (string) $cached['model'] : null,
                true,
                $language
            );
        }

        $text = '';
        $model = null;
        $source = 'fallback';

        try {
            $completion = $this->llm->completeJson(
                [
                    ['role' => 'system', 'content' => HarvestReportInsightPrompt::system()],
                    ['role' => 'user', 'content' => HarvestReportInsightPrompt::user($digest, $language)],
                ],
                1000,
                40,
                $this->model()
            );

            $decoded = $this->decode((string) ($completion['raw'] ?? ''));
            $candidate = is_string($decoded['text'] ?? null) ? trim($decoded['text']) : '';

            if ($this->acceptable($candidate, $digest)) {
                $text = $candidate;
                $model = $completion['model'] ?? null;
                $source = 'ai';
            }
        } catch (Throwable $error) {
            // A provider outage must never break the file viewer.
            $text = '';
        }

        if ($text === '') {
            $text = $this->fallback($digest, $language);
        }

        $this->cache($report, $language, [
            'text' => $text,
            'source' => $source,
            'model' => $model,
            'hash' => $hash,
            'generated_at' => now()->toIso8601String(),
        ]);

        return $this->result($text, $source, $model, false, $language);
    }

    /**
     * @return array<string, mixed>
     */
    private function digest(GeneratedHarvestReport $report): array
    {
        $context = is_array($report->context) ? $report->context : [];
        $visual = is_array($context['visual'] ?? null) ? $context['visual'] : [];
        $totals = is_array($context['totals'] ?? null) ? $context['totals'] : [];
        $filters = is_array($report->filters) ? $report->filters : [];

        // These are the same aggregates the paper itself renders, so every
        // explanation point can be checked against a figure on the report.
        $cropRows = $visual['by_crop'] ?? ($context['by_crop'] ?? []);
        $crops = collect(is_array($cropRows) ? $cropRows : [])
            ->sortByDesc(fn ($row) => (float) ($row['total_yield_mt'] ?? 0))
            ->values();

        $yearRows = $visual['by_year'] ?? [];
        $years = collect(is_array($yearRows) ? $yearRows : [])
            ->sortByDesc(fn ($row) => (float) ($row['total_yield_mt'] ?? 0))
            ->values();

        $selectedYears = $filters['years'] ?? (isset($filters['year']) ? [$filters['year']] : []);
        $selectedYears = array_values(array_filter(array_map('strval', is_array($selectedYears) ? $selectedYears : [])));
        $coverageCrops = is_array($context['coverage']['crops'] ?? null)
            ? count($context['coverage']['crops'])
            : 0;

        $topCrop = $crops->first();
        $totalYield = (float) ($totals['yield_tons'] ?? ($visual['total_yield_mt'] ?? 0));
        $wetYield = (float) (($visual['wet_summary'] ?? [])['total_yield_mt'] ?? 0);
        $dryYield = (float) (($visual['dry_summary'] ?? [])['total_yield_mt'] ?? 0);
        $wetShare = (int) ($visual['wet_share_pct'] ?? ($totalYield > 0 ? round($wetYield / $totalYield * 100) : 0));
        $dryShare = (int) ($visual['dry_share_pct'] ?? ($totalYield > 0 ? 100 - $wetShare : 0));
        $costPerTon = trim((string) ($totals['expenses_per_ton'] ?? ($visual['cost_per_mt'] ?? '')));

        return [
            'title' => $report->displayTitle(),
            'years' => $selectedYears,
            'season' => (string) ($filters['season'] ?? 'all'),
            'filter_crop' => (string) ($filters['crop'] ?? ''),
            'prepared_by' => (string) ($context['prepared_by'] ?? ($report->user?->name ?? '')),
            'reports' => (int) ($totals['reports'] ?? ($visual['total_records'] ?? 0)),
            'farmers' => (int) ($totals['farmers'] ?? ($visual['total_farmers'] ?? 0)),
            'crops' => (int) ($coverageCrops > 0 ? $coverageCrops : $crops->count()),
            'yield_tons' => number_format($totalYield, 2, '.', ''),
            'area_ha' => number_format((float) ($totals['area_ha'] ?? ($visual['total_area_ha'] ?? 0)), 2, '.', ''),
            'yield_per_ha' => number_format((float) ($totals['yield_per_ha'] ?? ($visual['avg_yield_per_ha'] ?? 0)), 2, '.', ''),
            'cost_per_mt' => $costPerTon !== '' ? number_format((float) $costPerTon, 2, '.', '') : '',
            'wet_yield_mt' => number_format($wetYield, 2, '.', ''),
            'dry_yield_mt' => number_format($dryYield, 2, '.', ''),
            'wet_share_pct' => $wetShare,
            'dry_share_pct' => $dryShare,
            'top_crop' => is_array($topCrop) ? (string) ($topCrop['crop_type'] ?? '') : '',
            'top_crop_yield_mt' => is_array($topCrop) ? number_format((float) ($topCrop['total_yield_mt'] ?? 0), 2, '.', '') : '0.00',
            'top_crop_share_pct' => is_array($topCrop) ? (int) ($topCrop['share_pct'] ?? 0) : 0,
            'crop_breakdown' => $crops->take(4)->map(fn ($row) => [
                'crop' => (string) ($row['crop_type'] ?? ''),
                'yield_mt' => number_format((float) ($row['total_yield_mt'] ?? 0), 2, '.', ''),
                'share_pct' => (int) ($row['share_pct'] ?? 0),
                'yield_per_ha' => number_format((float) ($row['yield_per_ha'] ?? 0), 2, '.', ''),
            ])->all(),
            'year_breakdown' => $years->take(4)->map(fn ($row) => [
                'year' => (int) ($row['year'] ?? 0),
                'yield_mt' => number_format((float) ($row['total_yield_mt'] ?? 0), 2, '.', ''),
                'area_ha' => number_format((float) ($row['total_area_ha'] ?? 0), 2, '.', ''),
                'records' => (int) ($row['record_count'] ?? 0),
                'yield_per_ha' => isset($row['total_yield_mt'], $row['total_area_ha'])
                    && (float) $row['total_area_ha'] > 0
                        ? number_format((float) $row['total_yield_mt'] / (float) $row['total_area_ha'], 2, '.', '')
                        : '0.00',
            ])->all(),
            'first_harvest' => (string) ($visual['first_harvest'] ?? ''),
            'last_harvest' => (string) ($visual['last_harvest'] ?? ''),
            'issued_on' => $report->created_at?->format('F j, Y') ?? '',
        ];
    }

    /**
     * @param  array<string, mixed>  $digest
     */
    private function acceptable(string $text, array $digest): bool
    {
        if ($text === '' || str_contains($text, '```') || str_contains($text, 'SUGGEST:')) {
            return false;
        }

        if (str_contains($text, 'tonnes') || str_contains($text, 'ha⁻¹') || str_contains($text, ' t,')) {
            return false;
        }

        $lines = collect(explode("\n", $text))
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->values();

        if ($lines->count() < 5 || $lines->count() > 8) {
            return false;
        }

        foreach ($lines as $line) {
            if (! str_starts_with($line, '-')) {
                return false;
            }
        }

        return substr_count($text, 'MT') >= 3
            && str_contains($text, (string) $digest['yield_tons'])
            && str_contains($text, (string) $digest['reports'])
            && str_contains($text, (string) $digest['top_crop_yield_mt']);
    }

    /**
     * @param  array<string, mixed>  $digest
     */
    private function fallback(array $digest, string $language): string
    {
        if ((int) $digest['reports'] === 0) {
            return $language === 'tl'
                ? "- Saklaw ng ulat: Walang talaan ng ani sa napiling saklaw.\n"
                    . "- Bakit ganito ang resulta: Walang natalang ani na maaaring ipaliwanga hangga't hindi naitala ang bagong talaan.\n"
                    . "- Paalala: Ang naka-save na ulat ang mananatiling opisyal na sanggunian. Suriin muna ang napiling saklaw."
                : "- Report scope: No harvest records fall within the selected scope.\n"
                    . "- Why this result: There are no saved figures to explain until harvest records are encoded.\n"
                    . "- What to remember: The saved report remains the official reference. Review the selected scope first.";
        }

        $lines = $language === 'tl'
            ? $this->fallbackTagalog($digest)
            : $this->fallbackEnglish($digest);

        return implode("\n", $lines);
    }

    /**
     * @param  array<string, mixed>  $digest
     * @return array<int, string>
     */
    private function fallbackEnglish(array $digest): array
    {
        $scope = [];
        if (($digest['years'] ?? []) !== []) {
            $scope[] = 'years ' . implode(', ', (array) $digest['years']);
        }
        if ((string) ($digest['filter_crop'] ?? '') !== '') {
            $scope[] = (string) $digest['filter_crop'] . ' records';
        }
        if ((string) ($digest['season'] ?? 'all') !== 'all') {
            $scope[] = ucfirst((string) $digest['season']) . ' season';
        }
        $scopeText = $scope !== [] ? implode(', ', $scope) : 'all selected records';

        $yearRows = collect(is_array($digest['year_breakdown'] ?? null) ? $digest['year_breakdown'] : [])
            ->sortByDesc(fn ($row) => (float) ($row['yield_mt'] ?? 0))
            ->values();

        if ($yearRows->count() >= 2) {
            $top = $yearRows[0];
            $next = $yearRows[1];
            $gap = number_format(max(0, (float) $top['yield_mt'] - (float) $next['yield_mt']), 2, '.', '');
            $yearLine = "Year with the most harvest: {$top['year']} had {$top['yield_mt']} MT from {$top['records']} records, "
                . "{$gap} MT more than {$next['year']} ({$next['yield_mt']} MT). The records do not state the reason for the difference.";
        } elseif ($yearRows->count() === 1) {
            $top = $yearRows[0];
            $yearLine = "Year covered: {$top['year']} had {$top['yield_mt']} MT from {$top['records']} records. "
                . 'No other year appears in this selection, so a year-to-year comparison is not possible.';
        } else {
            $yearLine = 'Year covered: the saved records do not include a dated year breakdown.';
        }

        $wet = (float) ($digest['wet_yield_mt'] ?? 0);
        $dry = (float) ($digest['dry_yield_mt'] ?? 0);
        if ($wet > 0 && $dry > 0) {
            $winner = $dry >= $wet ? 'Dry season' : 'Wet season';
            $seasonLine = "Season with more harvest: {$winner} contributed the larger recorded output — "
                . "dry season {$digest['dry_yield_mt']} MT ({$digest['dry_share_pct']}%) and wet season "
                . "{$digest['wet_yield_mt']} MT ({$digest['wet_share_pct']}%). The records show the difference but do not give the reason.";
        } elseif ($wet > 0) {
            $seasonLine = "Season with more harvest: all recorded output came from the wet season ({$digest['wet_yield_mt']} MT). "
                . 'No dry-season records appear in this selection.';
        } elseif ($dry > 0) {
            $seasonLine = "Season with more harvest: all recorded output came from the dry season ({$digest['dry_yield_mt']} MT). "
                . 'No wet-season records appear in this selection.';
        } else {
            $seasonLine = 'Season with more harvest: the saved records do not include a season breakdown.';
        }

        $cropRows = collect(is_array($digest['crop_breakdown'] ?? null) ? $digest['crop_breakdown'] : [])
            ->take(3)
            ->values();
        if ($cropRows->isNotEmpty()) {
            $parts = $cropRows->map(fn ($row) => "{$row['crop']} {$row['yield_mt']} MT ({$row['share_pct']}%)")->implode(', ');
            $combined = $cropRows->sum(fn ($row) => (float) $row['share_pct']);
            $cropLine = "Main crops: {$parts}. Together they make up {$combined}% of the recorded output.";
        } else {
            $cropLine = "Main crops: {$digest['top_crop']} contributed {$digest['top_crop_yield_mt']} MT ({$digest['top_crop_share_pct']}%).";
        }

        $cost = (string) ($digest['cost_per_mt'] ?? '');
        $costText = $cost !== '' ? " Spending averaged ₱{$cost} per MT." : '';
        $landLine = "How the land performed: {$digest['area_ha']} ha produced {$digest['yield_tons']} MT, "
            . "or about {$digest['yield_per_ha']} MT per hectare.{$costText}";

        return [
            "- Report scope: {$digest['reports']} records from {$digest['farmers']} farmers, covering {$scopeText}. Total output was {$digest['yield_tons']} MT.",
            "- Why this result: the report uses only the records saved for that scope, so these figures describe that selected group only.",
            $yearLine,
            $seasonLine,
            $cropLine,
            $landLine,
            "- What to remember: this guide is based on the report saved {$digest['issued_on']}. The report paper remains the official record.",
        ];
    }

    /**
     * @param  array<string, mixed>  $digest
     * @return array<int, string>
     */
    private function fallbackTagalog(array $digest): array
    {
        $scope = [];
        if (($digest['years'] ?? []) !== []) {
            $scope[] = 'taong ' . implode(', ', (array) $digest['years']);
        }
        if ((string) ($digest['filter_crop'] ?? '') !== '') {
            $scope[] = 'talaa ng ' . (string) $digest['filter_crop'];
        }
        if ((string) ($digest['season'] ?? 'all') !== 'all') {
            $scope[] = ucfirst((string) $digest['season']) . ' na panahon';
        }
        $scopeText = $scope !== [] ? implode(', ', $scope) : 'lahat ng napiling talaan';

        $yearRows = collect(is_array($digest['year_breakdown'] ?? null) ? $digest['year_breakdown'] : [])
            ->sortByDesc(fn ($row) => (float) ($row['yield_mt'] ?? 0))
            ->values();

        if ($yearRows->count() >= 2) {
            $top = $yearRows[0];
            $next = $yearRows[1];
            $gap = number_format(max(0, (float) $top['yield_mt'] - (float) $next['yield_mt']), 2, '.', '');
            $yearLine = "Taong pinakamaraming ani: ang {$top['year']} ay may {$top['yield_mt']} MT mula sa {$top['records']} talaan, "
                . "{$gap} MT na higit kaysa {$next['year']} ({$next['yield_mt']} MT). Hindi binabanggit ng talaan ang dahilan ng pagkakaiba.";
        } elseif ($yearRows->count() === 1) {
            $top = $yearRows[0];
            $yearLine = "Taong saklaw: ang {$top['year']} ay may {$top['yield_mt']} MT mula sa {$top['records']} talaan. "
                . 'Walang ibang taon sa napiling saklaw kaya hindi ito maihahambing sa ibang taon.';
        } else {
            $yearLine = 'Taong saklaw: walang breakdown ng taon ang naka-save na talaan.';
        }

        $wet = (float) ($digest['wet_yield_mt'] ?? 0);
        $dry = (float) ($digest['dry_yield_mt'] ?? 0);
        if ($wet > 0 && $dry > 0) {
            $winner = $dry >= $wet ? 'tag-araw' : 'tag-ulan';
            $seasonLine = "Panahong mas maraming ani: mas maraming natalang ani sa {$winner} — "
                . "tag-araw {$digest['dry_yield_mt']} MT ({$digest['dry_share_pct']}%) at tag-ulan "
                . "{$digest['wet_yield_mt']} MT ({$digest['wet_share_pct']}%). Ipinapakita ng talaan ang pagkakaiba ngunit hindi ang dahilan.";
        } elseif ($wet > 0) {
            $seasonLine = "Panahong mas maraming ani: lahat ng natalang ani ay sa tag-ulan ({$digest['wet_yield_mt']} MT). "
                . 'Walang talaan ng tag-araw sa napiling saklaw.';
        } elseif ($dry > 0) {
            $seasonLine = "Panahong mas maraming ani: lahat ng natalang ani ay sa tag-araw ({$digest['dry_yield_mt']} MT). "
                . 'Walang talaan ng tag-ulan sa napiling saklaw.';
        } else {
            $seasonLine = 'Panahong mas maraming ani: walang breakdown ng panahon ang naka-save na talaan.';
        }

        $cropRows = collect(is_array($digest['crop_breakdown'] ?? null) ? $digest['crop_breakdown'] : [])
            ->take(3)
            ->values();
        if ($cropRows->isNotEmpty()) {
            $parts = $cropRows->map(fn ($row) => "{$row['crop']} {$row['yield_mt']} MT ({$row['share_pct']}%)")->implode(', ');
            $combined = $cropRows->sum(fn ($row) => (float) $row['share_pct']);
            $cropLine = "Mga pangunahing pananim: {$parts}. Sila ay bumubuo ng {$combined}% ng kabuuang ani.";
        } else {
            $cropLine = "Mga pangunahing pananim: ang {$digest['top_crop']} ay nagbigay ng {$digest['top_crop_yield_mt']} MT ({$digest['top_crop_share_pct']}%).";
        }

        $cost = (string) ($digest['cost_per_mt'] ?? '');
        $costText = $cost !== '' ? " Ang gastos ay humigit-kumulang ₱{$cost} bawat MT." : '';
        $landLine = "Pagganap ng lupa: {$digest['area_ha']} ha ang nagbigay ng {$digest['yield_tons']} MT, "
            . "o humigit-kumulang {$digest['yield_per_ha']} MT bawat ektarya.{$costText}";

        return [
            "- Saklaw ng ulat: {$digest['reports']} talaan mula sa {$digest['farmers']} magsasaka, para sa {$scopeText}. Kabuuang ani: {$digest['yield_tons']} MT.",
            '- Bakit ganito ang resulta: gamit lamang ang mga talaang naka-save para sa saklaw na ito, kaya ang mga figure ay para lamang sa napiling grupo.',
            $yearLine,
            $seasonLine,
            $cropLine,
            $landLine,
            "- Paalala: ang gabay na ito ay batay sa ulat na nai-save noong {$digest['issued_on']}. Ang papel ng ulat ang nananatiling opisyal na talaan.",
        ];
    }

    /**
     * @param  array<string, mixed>|null  $raw
     */
    private function decode(string $raw): ?array
    {
        $raw = trim($raw);
        if ($raw === '') {
            return null;
        }

        $raw = preg_replace('/```(?:json|markdown)?/i', '', $raw);
        $raw = trim((string) $raw);

        if (preg_match('/\{.*\}/s', $raw, $match) !== 1) {
            return null;
        }

        $decoded = json_decode($match[0], true);

        return is_array($decoded) ? $decoded : null;
    }

    private function model(): ?string
    {
        $model = trim((string) config('services.groq.harvest_model', ''));

        return $model !== '' ? $model : null;
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    private function cache(GeneratedHarvestReport $report, string $language, array $entry): void
    {
        $insights = is_array($report->ai_insights) ? $report->ai_insights : [];
        $insights[$language] = $entry;

        $report->update(['ai_insights' => $insights]);
    }

    /**
     * @return array{text: string, source: string, source_label: string, model: ?string, cached: bool, language: string}
     */
    private function result(string $text, string $source, ?string $model, bool $cached, string $language): array
    {
        return [
            'text' => $text,
            'source' => $source,
            'source_label' => $source === 'ai' ? 'AI-assisted explanation' : 'Report-based explanation',
            'model' => $model,
            'cached' => $cached,
            'language' => $language,
        ];
    }
}
