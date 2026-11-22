<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * Writes the formal explanation section of a saved Actual Harvest Report from
 * the deterministic digest supplied by HarvestInsightService.
 *
 * Report generation deliberately has no model in the loop: every sentence is
 * composed here from the same figures shown on the page, so a saved report can
 * be reopened and downloaded without any external service. AI writing/analysis
 * belongs to a later, separate feature.
 */
class HarvestReportTextService
{
    /**
     * @param  array<string, mixed>  $context
     * @return array{en: string, tl: string, source: string}
     */
    public function compose(array $context): array
    {
        $coverage = $context['coverage'] ?? [];
        $totals = $context['totals'] ?? [];

        $reports = (int) ($totals['reports'] ?? ($coverage['reports'] ?? 0));
        $farmers = (int) ($totals['farmers'] ?? ($coverage['farmers'] ?? 0));
        $yield = $this->number((float) ($totals['yield_tons'] ?? 0));
        $area = $this->number((float) ($totals['area_ha'] ?? 0));
        $yieldPerHa = $this->number((float) ($totals['yield_per_ha'] ?? 0));
        $expenses = 'PHP ' . number_format((float) ($totals['expenses_php'] ?? 0), 2);
        $costPerTon = 'PHP ' . number_format((float) ($totals['expenses_per_ton'] ?? 0), 2);

        $crops = Collection::make($context['by_crop'] ?? [])
            ->take(3)
            ->map(fn (array $crop) => ($crop['crop'] ?? 'Unnamed crop') . ' - ' . $this->number((float) ($crop['yield_tons'] ?? 0)) . ' MT')
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
        $change = is_array($recent) ? ($recent['yield_per_ha_change_pct'] ?? null) : null;
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

        return ['en' => $en, 'tl' => $tl, 'source' => 'template'];
    }

    private function number(float $value): string
    {
        return number_format($value, 2, '.', '');
    }
}
