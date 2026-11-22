<?php

namespace App\Services;

use App\AI\AiLlmClient;
use App\AI\Prompts\HarvestReportPrompt;
use Illuminate\Support\Collection;

/**
 * Writes the explanatory prose for a saved Actual Harvest Report.
 *
 * The numbers never come from the model. HarvestInsightService computes the
 * digest, this service asks the model only to explain it, and the result is
 * checked against the headline figures before it is accepted. When the model
 * is unavailable or drifts from the data, a deterministic English and Tagalog
 * narrative is written from the same digest instead.
 */
class HarvestReportNarrationService
{
    public function __construct(
        private readonly AiLlmClient $llm,
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
        $en = "### Executive Summary\n\nNo harvest reports matched this report's scope, so there is nothing to analyse.\n\n"
            . "### Recommended Next Steps\n\nConfirm the report filters or add the missing harvest records before generating again.";

        $tl = "### Buod ng Tagapagpaganap\n\nWalang ulat ng ani na tumutugma sa saklaw ng ulat na ito, kaya walang masusuri.\n\n"
            . "### Mga Inirerekomendang Hakbang\n\nTingnan muli ang mga filter o idagdag ang nawawalang tala ng ani bago muling buuin ang ulat.";

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
     * Deterministic report narrative in both languages, written straight from
     * the same digest. Still a real report — just without model wording.
     *
     * @param  array<string, mixed>  $context
     * @return array{en: string, tl: string, source: string, model: ?string}
     */
    private function fallback(array $context): array
    {
        $coverage = $context['coverage'] ?? [];
        $totals = $context['totals'] ?? [];

        $reports = (int) ($totals['reports'] ?? ($coverage['reports'] ?? 0));
        $farmers = (int) ($totals['farmers'] ?? ($coverage['farmers'] ?? 0));
        $yield = $this->number((float) ($totals['yield_tons'] ?? 0));
        $area = $this->number((float) ($totals['area_ha'] ?? 0));
        $yieldPerHa = $this->number((float) ($totals['yield_per_ha'] ?? 0));
        $expenses = '₱' . number_format((float) ($totals['expenses_php'] ?? 0), 2);
        $costPerTon = '₱' . number_format((float) ($totals['expenses_per_ton'] ?? 0), 2);

        $crops = Collection::make($context['by_crop'] ?? [])
            ->take(3)
            ->map(fn (array $crop) => ($crop['crop'] ?? 'Unnamed crop') . ' — ' . $this->number((float) ($crop['yield_tons'] ?? 0)) . ' MT')
            ->implode('; ');
        $crops = $crops !== '' ? $crops : 'No crop breakdown is recorded.';

        $seasons = Collection::make($context['by_season'] ?? [])
            ->map(fn (array $season) => ($season['label'] ?? ucfirst((string) ($season['season'] ?? 'season'))) . ': ' . $this->number((float) ($season['yield_tons'] ?? 0)) . ' MT')
            ->implode('; ');
        $seasons = $seasons !== '' ? $seasons : 'No season breakdown is recorded.';

        $weatherCount = (int) ($coverage['weather_events_logged'] ?? 0);
        $weatherEn = $weatherCount === 0
            ? 'No weather events are logged for this scope, so no weather claim is made.'
            : $weatherCount . ' weather event(s) are logged; these may coincide with the harvest periods but are not treated as causes.';
        $weatherTl = $weatherCount === 0
            ? 'Walang natalang kaganapan sa panahon para sa saklaw na ito, kaya walang pahayag tungkol sa panahon ang ginawa.'
            : $weatherCount . ' kaganapan sa panahon ang natala; maaaring sakto ang mga ito sa mga panahon ng ani ngunit hindi itinuturing na dahilan.';

        $recent = $context['recent_vs_previous'] ?? null;
        $change = is_array($recent) ? $recent['yield_per_ha_change_pct'] ?? null : null;
        $recentEn = $change === null
            ? 'There are not enough periods to compare a latest period with the one before it.'
            : 'Yield per hectare in ' . ($recent['recent_label'] ?? 'the latest period') . ' changed by ' . $this->number((float) $change) . '% against ' . ($recent['previous_label'] ?? 'the previous period') . '.';
        $recentTl = $change === null
            ? 'Hindi sapat ang bilang ng mga panahon upang ihambing ang pinakabago sa nauna.'
            : 'Ang ani bawat ektarya sa ' . ($recent['recent_label'] ?? 'pinakabagong panahon') . ' ay nagbago ng ' . $this->number((float) $change) . '% kumpara sa ' . ($recent['previous_label'] ?? 'nakaraang panahon') . '.';

        $en = "### Executive Summary\n\n"
            . "This report covers {$reports} harvest report(s) from {$farmers} farmer(s), with a total recorded yield of {$yield} MT across {$area} ha.\n\n"
            . "### Performance Highlights\n\n"
            . "Average yield is {$yieldPerHa} MT/ha. Recorded harvest expenses are {$expenses}, or {$costPerTon} per metric ton.\n\n"
            . "### Crop and Season Observations\n\n"
            . "Leading crops by recorded yield: {$crops}. Season totals: {$seasons}. {$recentEn}\n\n"
            . "### Farmer Participation\n\n"
            . "The digest counts {$farmers} distinct reporter(s) across the selected records. The leaderboards in the underlying data show who filed the most reports and who recorded the most yield; those are reporting measures, not a judgement of performance.\n\n"
            . "### Data Quality and Verification\n\n"
            . "{$weatherEn} Verify any thin period or missing field against the original harvest report before using it for a decision.\n\n"
            . "### Recommended Next Steps\n\n"
            . "Confirm the crop and season mix against planting records, keep weather logs tied to harvest periods, and review cost per ton where records are incomplete.";

        $tl = "### Buod ng Tagapagpaganap\n\n"
            . "Saklaw ng ulat na ito ang {$reports} ulat ng ani mula sa {$farmers} magsasaka, na may kabuuang naitalang ani na {$yield} MT sa {$area} ha.\n\n"
            . "### Mga Pangunahing Resulta\n\n"
            . "Ang average na ani ay {$yieldPerHa} MT/ha. Ang naitalang gastos sa pag-aani ay {$expenses}, o {$costPerTon} bawat metrikong tonelada.\n\n"
            . "### Obserbasyon sa Pananim at Panahon\n\n"
            . "Mga nangungunang pananim ayon sa ani: {$crops}. Kabuuan bawat panahon: {$seasons}. {$recentTl}\n\n"
            . "### Pakikilahok ng Magsasaka\n\n"
            . "Binibilang ng datos ang {$farmers} natatanging nag-ulat sa mga napiling tala. Ipinapakita ng leaderboard kung sino ang pinaka-nag-ulat at pinakamaraming ani; sukatan ito ng pag-uulat, hindi paghusga sa pagganap.\n\n"
            . "### Kalidad ng Data at Beripikasyon\n\n"
            . "{$weatherTl} Beripikahin ang anumang kulang na panahon o field sa orihinal na ulat ng ani bago gamitin ito sa desisyon.\n\n"
            . "### Mga Inirerekomendang Hakbang\n\n"
            . "Itugma ang kombinasyon ng pananim at panahon sa tala ng pagtatanim, panatilihin ang tala ng panahon na nakatali sa panahon ng ani, at suriin ang gastos bawat tonelada kung may kulang na tala.";

        return ['en' => $en, 'tl' => $tl, 'source' => 'fallback', 'model' => null];
    }

    private function number(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
