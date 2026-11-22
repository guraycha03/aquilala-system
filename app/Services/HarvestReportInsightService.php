<?php

namespace App\Services;

use App\AI\AiLlmClient;
use App\AI\Prompts\HarvestReportInsightPrompt;
use App\Models\GeneratedHarvestReport;
use Throwable;

/**
 * Supplementary, on-demand explanation for a saved harvest report.
 *
 * The official report is already written deterministically by
 * HarvestReportTextService. This service only reads that frozen snapshot and
 * explains it in the viewer. If the model is unavailable or its answer drifts
 * from the saved figures, the deterministic explanation is used instead.
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
                700,
                35,
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

        // The analytics snapshot is the one the paper itself renders, so it is
        // the safest source for the top crop the officer can actually see.
        $cropRows = $visual['by_crop'] ?? ($context['by_crop'] ?? []);
        $crops = collect(is_array($cropRows) ? $cropRows : []);
        $topCrop = $crops->sortByDesc(fn ($row) => (float) ($row['total_yield_mt'] ?? 0))->first();
        $coverageCrops = is_array($context['coverage']['crops'] ?? null)
            ? count($context['coverage']['crops'])
            : 0;

        $years = $filters['years'] ?? (isset($filters['year']) ? [$filters['year']] : []);
        $years = array_values(array_filter(array_map('strval', is_array($years) ? $years : [])));

        return [
            'title' => $report->displayTitle(),
            'years' => $years,
            'season' => (string) ($filters['season'] ?? 'all'),
            'prepared_by' => (string) ($context['prepared_by'] ?? ($report->user?->name ?? '')),
            'reports' => (int) ($totals['reports'] ?? ($visual['total_records'] ?? 0)),
            'farmers' => (int) ($totals['farmers'] ?? ($visual['total_farmers'] ?? 0)),
            'crops' => (int) ($coverageCrops > 0 ? $coverageCrops : $crops->count()),
            'yield_tons' => number_format((float) ($totals['yield_tons'] ?? ($visual['total_yield_mt'] ?? 0)), 2, '.', ''),
            'area_ha' => number_format((float) ($totals['area_ha'] ?? ($visual['total_area_ha'] ?? 0)), 2, '.', ''),
            'yield_per_ha' => number_format((float) ($totals['yield_per_ha'] ?? ($visual['avg_yield_per_ha'] ?? 0)), 3, '.', ''),
            'expenses_php' => number_format((float) ($totals['expenses_php'] ?? ($visual['total_expenses_php'] ?? 0)), 2, '.', ''),
            'wet_share_pct' => (int) ($visual['wet_share_pct'] ?? 0),
            'dry_share_pct' => (int) ($visual['dry_share_pct'] ?? 0),
            'top_crop' => is_array($topCrop) ? (string) ($topCrop['crop_type'] ?? '') : '',
            'top_crop_yield_mt' => is_array($topCrop) ? number_format((float) ($topCrop['total_yield_mt'] ?? 0), 2, '.', '') : '0.00',
            'top_crop_share_pct' => is_array($topCrop) ? (int) ($topCrop['share_pct'] ?? 0) : 0,
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

        $lines = collect(explode("\n", $text))
            ->map(fn (string $line) => trim($line))
            ->filter(fn (string $line) => $line !== '')
            ->values();

        if ($lines->count() < 3) {
            return false;
        }

        foreach ($lines as $line) {
            if (! str_starts_with($line, '-')) {
                return false;
            }
        }

        return str_contains($text, (string) $digest['yield_tons'])
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
                ? "- Ipinapakita ng ulat: Walang talaan ng ani sa napiling saklaw.\n"
                    . "- Kahalagahan ng mga numero: Walang figure na maaaring suriin hangga't walang natalang ani.\n"
                    . "- Paalala sa datos: Ang opisyal na ulat ang mananatiling sanggunian; suriin muna ang napiling saklaw."
                : "- What the report shows: No harvest records fall within the selected scope.\n"
                    . "- Why the figures matter: There are no saved figures to interpret until harvest records are encoded.\n"
                    . "- Data note: The official report remains the reference; review the selected scope first.";
        }

        $scope = $digest['years'] !== []
            ? implode(', ', $digest['years'])
            : ($digest['season'] === 'all' ? 'the saved scope' : ucfirst((string) $digest['season']) . ' season');

        if ($language === 'tl') {
            return "- Ipinapakita ng ulat: Saklaw ng naka-save na ulat ang {$digest['reports']} talaan ng ani mula sa {$digest['farmers']} magsasaka para sa {$scope}. Ang kabuuang ani ay {$digest['yield_tons']} MT mula sa {$digest['area_ha']} ha.\n"
                . "- Kahalagahan ng mga numero: Ang {$digest['top_crop']} ang pinakamalaking bahagi ng ani na {$digest['top_crop_yield_mt']} MT ({$digest['top_crop_share_pct']}%). Ang tag-ulan ay {$digest['wet_share_pct']}% at ang tag-araw ay {$digest['dry_share_pct']}% ng kabuuang ani.\n"
                . "- Paalala sa datos: Mula ito sa snapshot ng ulat na inisyu noong {$digest['issued_on']}. Ang opisyal na figure ay nananatili sa naka-save na ulat.";
        }

        return "- What the report shows: This saved report covers {$digest['reports']} harvest records from {$digest['farmers']} farmers for {$scope}. Total recorded output is {$digest['yield_tons']} MT from {$digest['area_ha']} ha.\n"
            . "- Why the figures matter: {$digest['top_crop']} is the largest recorded crop contribution at {$digest['top_crop_yield_mt']} MT ({$digest['top_crop_share_pct']}%). Wet-season records account for {$digest['wet_share_pct']}% and dry-season records for {$digest['dry_share_pct']}% of total output.\n"
            . "- Data note: These figures come from the report snapshot issued {$digest['issued_on']}. The official figures remain in the saved report.";
    }

    /**
     * @return array<string, mixed>|null
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
            'source_label' => $source === 'ai' ? 'Groq-enhanced explanation' : 'Deterministic explanation',
            'model' => $model,
            'cached' => $cached,
            'language' => $language,
        ];
    }
}
