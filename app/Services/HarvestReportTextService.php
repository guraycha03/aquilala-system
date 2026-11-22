<?php

namespace App\Services;

use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Composes the formal narrative of a saved Actual Harvest Report from the
 * deterministic digest supplied by HarvestInsightService.
 *
 * Report generation deliberately has no model in the loop: every statement is
 * derived from the same figures shown on the page, and both language versions
 * are stored with the report. AI writing and analysis belong to a separate,
 * later feature.
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

        $area = $this->number((float) ($totals['area_ha'] ?? 0));
        $yield = $this->number((float) ($totals['yield_tons'] ?? 0));
        $yieldPerHa = $this->number((float) ($totals['yield_per_ha'] ?? 0));
        $expenses = 'PHP ' . $this->number((float) ($totals['expenses_php'] ?? 0));
        $costPerTon = 'PHP ' . $this->number((float) ($totals['expenses_per_ton'] ?? 0));

        $reportCountEn = $this->count($reports, 'harvest report', 'harvest reports');
        $reportCountTl = $this->count($reports, 'ulat ng ani', 'ulat ng ani');
        $farmerCountEn = $this->count($farmers, 'farmer', 'farmers');
        $farmerCountTl = $this->count($farmers, 'magsasaka', 'mga magsasaka');

        $periodEn = $this->period($coverage, false);
        $periodTl = $this->period($coverage, true);

        $cropsEn = $this->cropSentence($context, false);
        $cropsTl = $this->cropSentence($context, true);
        $seasonsEn = $this->seasonSentence($context, false);
        $seasonsTl = $this->seasonSentence($context, true);

        $weatherCount = (int) ($coverage['weather_events_logged'] ?? 0);
        $weatherEn = $this->weatherSentence($weatherCount, false);
        $weatherTl = $this->weatherSentence($weatherCount, true);

        $recent = $context['recent_vs_previous'] ?? null;
        $change = is_array($recent) ? ($recent['yield_per_ha_change_pct'] ?? null) : null;
        $changeEn = $this->changeSentence($recent, $change, false);
        $changeTl = $this->changeSentence($recent, $change, true);

        $en = "### Executive Summary\n\n"
            . "The Aquilala Irrigators Association, Inc. hereby submits this Actual Harvest Report for {$periodEn}. "
            . "The report covers {$reportCountEn} submitted by {$farmerCountEn}, spanning {$area} ha of cultivated area "
            . "and {$yield} MT of recorded production. Total harvest expenditure amounts to {$expenses}, "
            . "equivalent to {$costPerTon} per metric ton.\n\n"
            . "### Production Performance\n\n"
            . "The recorded yield per hectare is {$yieldPerHa} MT. {$changeEn}\n\n"
            . "### Crop and Seasonal Distribution\n\n"
            . "{$cropsEn} {$seasonsEn}\n\n"
            . "### Member Participation\n\n"
            . "The selected scope includes {$farmerCountEn}. Submission counts and recorded output by farmer are set out "
            . "in the accompanying data; these figures measure reporting activity and do not constitute a formal "
            . "performance rating.\n\n"
            . "### Data Verification\n\n"
            . "{$weatherEn} Any period with incomplete entries shall be verified against the original harvest report "
            . "before the figures are used for official purposes.\n\n"
            . "### Recommendations\n\n"
            . "Reconcile the reported crop and seasonal distribution with the association's planting records.\n"
            . "Maintain weather logs in direct reference to each harvest period.\n"
            . "Review the unit production cost of records with incomplete expense entries before official submission.";

        $tl = "### Buod ng Tagapagpaganap\n\n"
            . "Inihaharap ng Aquilala Irrigators Association, Inc. ang Ulat sa Aktwal na Ani para sa {$periodTl}. "
            . "Saklaw ng ulat ang {$reportCountTl} na isinumite ng {$farmerCountTl}, na sumasaklaw sa {$area} ha na "
            . "taniman at {$yield} MT na naitalang produksiyon. Ang kabuuang gastos sa pag-aani ay {$expenses}, "
            . "katumbas ng {$costPerTon} bawat metrikong tonelada.\n\n"
            . "### Pagganap sa Produksiyon\n\n"
            . "Ang naitalang ani bawat ektarya ay {$yieldPerHa} MT. {$changeTl}\n\n"
            . "### Pamamahagi ayon sa Pananim at Panahon\n\n"
            . "{$cropsTl} {$seasonsTl}\n\n"
            . "### Pakikilahok ng mga Magsasaka\n\n"
            . "Kasama sa napiling saklaw ang {$farmerCountTl}. Ang bilang ng isinumiteng talaan at ang naitalang ani "
            . "bawat magsasaka ay nakasaad sa kaakibat na datos; sukatan ng pag-uulat ang mga ito at hindi pormal na "
            . "pagsusuri sa pagganap.\n\n"
            . "### Beripikasyon ng Datos\n\n"
            . "{$weatherTl} Dapat beripikahin sa orihinal na ulat ng ani ang anumang panahong may kulang na tala bago "
            . "gamitin ang mga bilang para sa pormal na layunin.\n\n"
            . "### Mga Rekomendasyon\n\n"
            . "Itugma ang naitalang pamamahagi ng pananim at panahon sa talaan ng pagtatanim ng samahan.\n"
            . "Panatilihin ang mga tala ng panahon kaugnay ng bawat panahon ng ani.\n"
            . "Suriin ang gastos bawat tonelada ng mga talaang may kulang na impormasyon bago isumite para sa pormal na layunin.";

        return ['en' => $en, 'tl' => $tl, 'source' => 'template'];
    }

    /**
     * @param  array<string, mixed>  $coverage
     */
    private function period(array $coverage, bool $tl): string
    {
        $first = $this->date($coverage['first_harvest'] ?? null);
        $last = $this->date($coverage['last_harvest'] ?? null);

        if ($first === null || $last === null) {
            return $tl ? 'napiling saklaw ng ulat' : 'the selected reporting scope';
        }

        $format = $tl ? 'j F Y' : 'F j, Y';

        if ($first->toDateString() === $last->toDateString()) {
            return ($tl ? 'ang petsang ' : 'the reporting date ') . $first->format($format);
        }

        return ($tl ? 'ang panahong mula ' : 'the period from ')
            . $first->format($format) . ($tl ? ' hanggang ' : ' to ') . $last->format($format);
    }

    private function date(mixed $value): ?Carbon
    {
        if ($value instanceof Carbon) {
            return $value;
        }

        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        try {
            return Carbon::parse($value);
        } catch (\Throwable) {
            return null;
        }
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function cropSentence(array $context, bool $tl): string
    {
        $crops = Collection::make($context['by_crop'] ?? [])
            ->take(3)
            ->filter(fn (array $crop) => trim((string) ($crop['crop'] ?? '')) !== '')
            ->map(fn (array $crop) => trim((string) $crop['crop'])
                . ' (' . $this->number((float) ($crop['yield_tons'] ?? 0)) . ' MT)')
            ->values()
            ->all();

        if ($crops === []) {
            return $tl
                ? 'Walang detalyadong talaan ng pananim para sa saklaw na ito.'
                : 'No crop breakdown was recorded for this scope.';
        }

        $list = $this->join($crops, $tl);

        return $tl
            ? 'Ayon sa naitalang produksiyon, nanguna ang ' . $list . '.'
            : 'Crop output was led by ' . $list . '.';
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function seasonSentence(array $context, bool $tl): string
    {
        $seasons = Collection::make($context['by_season'] ?? [])
            ->map(function (array $season): string {
                $label = $season['label'] ?? ucfirst((string) ($season['season'] ?? 'season'));

                return trim((string) $label) . ' — ' . $this->number((float) ($season['yield_tons'] ?? 0)) . ' MT';
            })
            ->filter(fn (string $line) => $line !== '')
            ->values()
            ->all();

        if ($seasons === []) {
            return $tl
                ? 'Walang detalyadong talaan bawat panahon para sa saklaw na ito.'
                : 'No seasonal breakdown was recorded for this scope.';
        }

        $list = implode('; ', $seasons);

        return $tl
            ? 'Ang naitalang produksiyon bawat panahon ay ' . $list . '.'
            : 'Production by season comprised ' . $list . '.';
    }

    /**
     * @param  array<string, mixed>|null  $recent
     */
    private function changeSentence(?array $recent, mixed $change, bool $tl): string
    {
        if (! is_array($recent) || ! is_numeric($change)) {
            return $tl
                ? 'Hindi ipinapresenta ang paghahambing ng ani dahil mas mababa sa dalawa ang maihahambing na panahon sa napiling saklaw.'
                : 'No comparative yield assessment is presented because the selected scope contains fewer than two comparable periods.';
        }

        $current = trim((string) ($recent['recent_label'] ?? ''));
        $previous = trim((string) ($recent['previous_label'] ?? ''));
        $current = $current !== '' ? $current : ($tl ? 'pinakabagong panahon' : 'the latest period');
        $previous = $previous !== '' ? $previous : ($tl ? 'nakaraang panahon' : 'the previous period');

        $percent = $this->number(abs((float) $change));
        $value = (float) $change;

        if ($value > 0) {
            return $tl
                ? 'Ang pinakabagong panahon, ' . $current . ', ay may pagtaas na ' . $percent . '% sa ani bawat ektarya kumpara sa ' . $previous . '.'
                : 'The latest period, ' . $current . ', recorded an increase of ' . $percent . '% in yield per hectare relative to ' . $previous . '.';
        }

        if ($value < 0) {
            return $tl
                ? 'Ang pinakabagong panahon, ' . $current . ', ay may pagbaba na ' . $percent . '% sa ani bawat ektarya kumpara sa ' . $previous . '.'
                : 'The latest period, ' . $current . ', recorded a decrease of ' . $percent . '% in yield per hectare relative to ' . $previous . '.';
        }

        return $tl
            ? 'Ang pinakabagong panahon, ' . $current . ', ay walang pagbabago sa ani bawat ektarya kumpara sa ' . $previous . '.'
            : 'The latest period, ' . $current . ', recorded no change in yield per hectare relative to ' . $previous . '.';
    }

    private function weatherSentence(int $count, bool $tl): string
    {
        if ($count === 0) {
            return $tl
                ? 'Walang natalang kaganapan sa panahon sa loob ng napiling saklaw; kaya, hindi ginawa ang anumang pag-uugnay sa panahon.'
                : 'No weather events were recorded within the selected scope; accordingly, no weather-related attribution is made.';
        }

        $events = $this->count($count, 'weather event', 'weather events');
        $eventsTl = $this->count($count, 'kaganapan sa panahon', 'mga kaganapan sa panahon');

        return $tl
            ? $eventsTl . ' ang natala sa loob ng napiling saklaw. Itinuturing ang mga ito bilang kontekstwal na impormasyon at hindi bilang dahilan ng naitalang ani.'
            : $events . ' were recorded within the selected scope. These entries are treated as contextual information and are not attributed as causes of the recorded yield.';
    }

    /**
     * @param  array<int, string>  $items
     */
    private function join(array $items, bool $tl): string
    {
        if (count($items) === 1) {
            return $items[0];
        }

        $last = array_pop($items);

        return implode(', ', $items) . ($tl ? ' at ' : ' and ') . $last;
    }

    private function count(int $value, string $singular, string $plural): string
    {
        return number_format($value) . ' ' . ($value === 1 ? $singular : $plural);
    }

    private function number(float $value, int $digits = 2): string
    {
        return number_format($value, $digits, '.', ',');
    }
}
