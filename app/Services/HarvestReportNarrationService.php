<?php

namespace App\Services;

use App\AI\AiLlmClient;
use App\AI\Prompts\HarvestReportPrompt;

/**
 * Reserved for a later, separate AI writing feature.
 *
 * Report generation does not use this class: HarvestReportTextService writes
 * the formal narrative deterministically. If AI writing is enabled later, the
 * numbers will still come from the digest, and wording that drifts from those
 * figures will fall back to the same formal template.
 */
class HarvestReportNarrationService
{
    public function __construct(
        private readonly AiLlmClient $llm,
        private readonly HarvestReportTextService $text,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     * @param  array<string, string>  $filters
     * @return array{en: string, tl: string, source: string, model: ?string}
     */
    public function generate(array $context, string $title, array $filters): array
    {
        if ((int) ($context['coverage']['reports'] ?? 0) === 0) {
            return $this->emptyReport();
        }

        $completion = $this->llm->completeJson(
            [
                ['role' => 'system', 'content' => HarvestReportPrompt::system()],
                ['role' => 'user', 'content' => HarvestReportPrompt::user($context, $title, $filters)],
            ],
            5000,
            90,
            ($harvestModel = trim((string) env('GROQ_MODEL_HARVEST', ''))) !== ''
                ? $harvestModel
                : null
        );

        $decoded = $this->decode($completion['raw'] ?? '');
        $en = is_string($decoded['en'] ?? null) ? trim($decoded['en']) : '';
        $tl = is_string($decoded['tl'] ?? null) ? trim($decoded['tl']) : '';

        if ($this->acceptable($en, $context) && $this->acceptable($tl, $context)) {
            return [
                'en' => $en,
                'tl' => $tl,
                'source' => 'ai',
                'model' => $completion['model'] ?? null,
            ];
        }

        return $this->fallback($context);
    }

    /**
     * @return array{en: string, tl: string, source: string, model: ?string}
     */
    private function emptyReport(): array
    {
        $en = "### Executive Summary\n\nNo harvest records fall within the selected scope; accordingly, no yield analysis is presented.\n\n"
            . "### Recommendations\n\nReview the selected filters or encode the missing harvest records before generating the report.";

        $tl = "### Buod ng Tagapagpaganap\n\nWalang talaan ng ani na sakto sa napiling saklaw; kaya, hindi ipinapresenta ang pagsusuri sa ani.\n\n"
            . "### Mga Rekomendasyon\n\nSuriin ang mga napiling filter o i-encode ang mga kulang na talaan ng ani bago buuin ang ulat.";

        return ['en' => $en, 'tl' => $tl, 'source' => 'fallback', 'model' => null];
    }

    /**
     * @param  array<string, mixed>  $context
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

    /**
     * A narrative is only accepted when it actually contains the two headline
     * figures the reader will check first. This is a cheap guard against a
     * fluent but ungrounded answer; the prompt does the rest of the work.
     *
     * @param  array<string, mixed>  $context
     */
    private function acceptable(string $text, array $context): bool
    {
        if ($text === '' || str_contains($text, 'SUGGEST:') || str_contains($text, '```')) {
            return false;
        }

        $totals = $context['totals'] ?? [];
        $reports = (int) ($totals['reports'] ?? ($context['coverage']['reports'] ?? 0));
        $yield = number_format((float) ($totals['yield_tons'] ?? 0), 2, '.', '');

        $yieldVariants = [$yield, rtrim(rtrim($yield, '0'), '.')];
        $hasYield = false;

        foreach ($yieldVariants as $variant) {
            if ($variant !== '' && str_contains($text, $variant)) {
                $hasYield = true;
                break;
            }
        }

        return $hasYield && str_contains($text, (string) $reports);
    }

    /**
     * When the model is unavailable or its wording drifts from the figures,
     * the same formal association narrative used by the generator is written
     * from the digest. No external service is involved.
     *
     * @param  array<string, mixed>  $context
     * @return array{en: string, tl: string, source: string, model: ?string}
     */
    private function fallback(array $context): array
    {
        $narrative = $this->text->compose($context);

        return [
            'en' => $narrative['en'],
            'tl' => $narrative['tl'],
            'source' => 'fallback',
            'model' => null,
        ];
    }
}
