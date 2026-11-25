<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Writes the friendly "why this pick" text from structured reason data.
 *
 * The wording is template-based and deterministic: the same product gets the
 * same explanation for a given day. It speaks to the farmer in a personal,
 * "we/you" voice and draws only on saved farm records, the product's own
 * application data and the current season. No AI service is used, and the
 * wording never refers to an unnamed system making the decision.
 */
class RecommendationExplanationService
{
    /** @var array<string, array<int, string>> */
    private const ADVICE = [
        'seed' => [
            'We’d check the seed rate and maturity against your land area first.',
            'Before you choose it, we’d compare its price and expected yield with your plan.',
            'We’d look at the seed rate and planting window alongside your field size.',
            'It’s worth comparing the maturity and pack size with when you want to plant.',
            'We’d match the seed rate to the area you’re planting this time.',
            'Take a look at the maturity and seed rate against your own field plan.',
        ],
        'seedling' => [
            'We’d line up the transplanting window with the crop you’re preparing.',
            'Check the seedling count and timing against your field before choosing.',
            'We’d compare its maturity and price with the seedlings you planned for.',
            'It’s worth matching the transplanting date to your own schedule.',
            'We’d count the seedlings you need against the pack on offer.',
            'Look at the transplanting window and seedling count together with your plan.',
        ],
        'fertilizer' => [
            'We’d check the nutrient rating and rate per hectare against your land area.',
            'Compare the nutrient balance and application rate with the inputs you already planned.',
            'We’d look at the rate per hectare before you add it to your cost plan.',
            'It’s worth matching the nutrient mix to what your crop needs right now.',
            'We’d compare the bag size and rate with the area you’re treating.',
            'Check the nutrient balance against the stage your crop is in.',
        ],
        'protection' => [
            'We’d check the timing and rate against the problem you’re seeing in the field.',
            'Compare the target pest with what you’re dealing with right now.',
            'We’d look at the application window before adding it to your crop-care plan.',
            'It’s worth matching the rate to the area you need to treat.',
            'We’d compare it with the crop-protection steps you already follow.',
            'Check the target and timing so it fits the issue you have.',
        ],
        'tool' => [
            'We’d compare its size and capacity with the work you need to do.',
            'Check the tool against the ones you already have before buying.',
            'We’d look at the specifications alongside your usual farm tasks.',
            'It’s worth seeing if the capacity matches your field size.',
            'We’d compare the build and price with other tools you’ve considered.',
            'Match the tool’s capacity to the job you have in mind.',
        ],
        'irrigation' => [
            'We’d compare its coverage with the area you need to water.',
            'Check the capacity against your irrigation schedule before choosing.',
            'We’d look at the reach and setup alongside your field layout.',
            'It’s worth matching the coverage to your current water needs.',
            'We’d compare it with the irrigation setup you already use.',
            'See whether the reach fits the part of the farm you’re watering.',
        ],
        'feed' => [
            'We’d compare it with the feed needs of your animals.',
            'Check the ration and pack size against the number of animals you have.',
            'We’d look at it alongside the feeds you already give.',
            'It’s worth matching the ration to the stage your animals are in.',
            'We’d compare the pack size with how much feed you use in a month.',
            'Match the feed type to what your herd or flock needs now.',
        ],
        'postharvest' => [
            'We’d check the capacity against the harvest you’re expecting.',
            'Compare it with the post-harvest steps you already follow.',
            'We’d look at the handling method alongside your storing and sorting plan.',
            'It’s worth matching the capacity to the size of your harvest.',
            'We’d compare it with the tools you use after harvest.',
            'Check whether the handling steps fit the crops you’re bringing in.',
        ],
        'input' => [
            'We’d compare it with the farm inputs you already use.',
            'Check the rate and pack size against your land area before choosing.',
            'We’d look at it alongside the other supplies you’re considering.',
            'It’s worth matching the price and size to your current plan.',
            'We’d check the use notes before you add it to your budget.',
            'Compare it with what you already have on hand.',
        ],
    ];

    /**
     * @param  array<int, string>  $reasonCodes
     * @param  array<string, mixed>  $reasonData
     */
    public function explain(Product $product, array $reasonCodes, array $reasonData = []): string
    {
        $kind = trim((string) ($reasonData['kind'] ?? 'input'));
        $kindLabel = trim((string) ($reasonData['kind_label'] ?? 'farm input'));
        $crop = trim((string) ($reasonData['crop_label'] ?? $reasonData['crop'] ?? ''));
        $crop = $crop !== '' ? $crop : 'this crop';
        $season = Str::lower(trim((string) ($reasonData['season'] ?? 'wet')));

        $leadCode = $this->leadCode($reasonCodes);
        $parts = [];

        $lead = $this->lead($product, $leadCode, $kind, $kindLabel, $crop);
        if ($lead !== '') {
            $parts[] = $lead;
        }

        if (! empty($reasonData['season_fit'])) {
            $parts[] = $this->seasonSentence($product, $kind, $kindLabel, $crop, $season);
        }

        $support = $this->support($product, $reasonCodes, $leadCode, $reasonData, $kindLabel);
        if ($support !== '' && ! in_array($support, $parts, true)) {
            $parts[] = $support;
        }

        if (count($parts) < 3) {
            $advice = $this->advice($product, $kind);
            if ($advice !== '' && ! in_array($advice, $parts, true)) {
                $parts[] = $advice;
            }
        }

        return $parts === []
            ? 'It fits your farm records and the use notes we have on file.'
            : implode(' ', array_slice($parts, 0, 3));
    }

    /**
     * @param  array<int, string>  $reasonCodes
     */
    private function leadCode(array $reasonCodes): string
    {
        foreach ([
            'planted_crop_match',
            'crop_formula_match',
            'harvest_history_match',
            'own_quotation_match',
            'top_quoted',
        ] as $code) {
            if (in_array($code, $reasonCodes, true)) {
                return $code;
            }
        }

        return 'formula_backed';
    }

    private function lead(Product $product, string $code, string $kind, string $kindLabel, string $crop): string
    {
        $values = ['{kind}' => $kindLabel, '{crop}' => $crop];

        $lines = match ($code) {
            'planted_crop_match' => [
                'We picked this {kind} because {crop} is already in your field this season.',
                'You’re growing {crop} right now, so we put this {kind} on your list.',
                'We matched this {kind} to the {crop} you planted this season.',
                'Because you have {crop} in the field, we thought this {kind} would be useful.',
                'Your {crop} planting came up in your records, so we paired it with this {kind}.',
                'We noticed {crop} in your planting log and matched it to this {kind}.',
            ],
            'crop_formula_match' => [
                'We linked this {kind} to the {crop} you already priced in a cost quotation.',
                'You costed {crop} earlier, so we checked whether this {kind} fits that plan.',
                'We saw {crop} in your quotation and matched this {kind} to it.',
                'Because you planned for {crop}, we brought this {kind} into view.',
                'Your {crop} cost plan made us think this {kind} could help.',
                'We tied this {kind} to {crop}, which you included in your input budget.',
            ],
            'harvest_history_match' => [
                'We remembered the {crop} you harvested before and matched this {kind} to it.',
                'You grew {crop} in the past, so we’re showing this {kind} again.',
                'We saw {crop} in your harvest records and paired it with this {kind}.',
                'Your earlier {crop} harvest is why this {kind} came up.',
                'Because you’ve harvested {crop} before, we think this {kind} may fit again.',
                'We picked up {crop} from your harvest history and linked this {kind} to it.',
            ],
            'own_quotation_match' => [
                'We saw this product in your own cost plan.',
                'You already priced this one, so we kept it on your list.',
                'We noticed this product in a quotation you made, so it’s familiar to you.',
                'Your own input budget already includes this product, and we’ve kept it handy.',
                'We found this product in your previous cost plan and brought it back.',
                'Because you quoted this product yourself, we thought you’d want to see it again.',
            ],
            'top_quoted' => [
                'We noticed this {kind} in other farmers’ cost plans.',
                'Other growers have priced this {kind}, so we’ve included it here.',
                'We’ve seen this {kind} show up in farm input budgets nearby.',
                'This {kind} came up in other cost plans we checked.',
                'We thought you might like this {kind} because other farmers use it in their plans.',
                'Other farmers’ quotations made us take a closer look at this {kind}.',
            ],
            default => [
                'We have an application guide for this {kind}.',
                'This {kind} is in our farm-input notes, so we can explain how it’s used.',
                'We’ve got clear use details for this {kind}.',
                'We checked this {kind} against our input guides and it fits farm use.',
                'Our records include an application rule for this {kind}.',
            ],
        };

        return $this->line($product, 'lead|'.$code, $lines, $values);
    }

    private function seasonSentence(
        Product $product,
        string $kind,
        string $kindLabel,
        string $crop,
        string $season,
    ): string {
        $specificCrop = $crop !== 'this crop';

        if ($specificCrop && in_array($kind, ['seed', 'seedling'], true)) {
            // Keep "planting window" in every variant so the farmer always sees
            // the timing reason in plain language.
            $lines = [
                'We checked the {season} planting window, and it lines up with the {crop} you’re getting ready.',
                'Your {crop} fits this {season} planting window, so we matched this {kind} to that timing.',
                'We saw the {season} planting window and thought of the {crop} you’re preparing.',
                'For {season} planting, this {kind} sits right in the {crop} planting window.',
                'We compared the {season} planting window with your {crop} plan, and this {kind} fits.',
                'This {kind} is listed for the {season} planting window, which suits {crop}.',
            ];

            return $this->line($product, 'season|seed', $lines, [
                '{kind}' => $kindLabel,
                '{crop}' => $crop,
                '{season}' => $season,
            ]);
        }

        $lines = [
            'We listed this {kind} for {season} use, which fits the season you’re in now.',
            'It’s {season} season, and we’ve got this {kind} noted for that time.',
            'We matched this {kind} to the current {season} season.',
            'This {kind} has {season}-season use notes, so we thought it was timely.',
            'Because it’s {season} now, we brought this {kind} forward.',
            'We checked the season and found this {kind} is used in {season} conditions.',
        ];

        return $this->line($product, 'season|general', $lines, [
            '{kind}' => $kindLabel,
            '{season}' => $season,
        ]);
    }

    /**
     * @param  array<int, string>  $reasonCodes
     * @param  array<string, mixed>  $reasonData
     */
    private function support(
        Product $product,
        array $reasonCodes,
        string $leadCode,
        array $reasonData,
        string $kindLabel,
    ): string {
        $method = trim((string) ($reasonData['application_method'] ?? ''));
        $timing = trim((string) ($reasonData['application_timing'] ?? ''));

        if (in_array('application_guidance', $reasonCodes, true) && ($method !== '' || $timing !== '')) {
            if ($method !== '' && $timing !== '') {
                return $this->line($product, 'support|method-timing', [
                    'We found a guide that says {method} around {timing}.',
                    'Our notes show {method}, best timed for {timing}.',
                    'We spotted a use note for {method} at {timing}.',
                    'The guide we have lists {method}, with {timing} as the timing.',
                    'We’d use {method} when {timing} comes around.',
                ], ['{method}' => $method, '{timing}' => $timing]);
            }

            return $this->line($product, 'support|method', [
                'We found a note that says {method}.',
                'Our guide lists {method} as the way to use it.',
                'We’ve got {method} in the use notes for this product.',
                'We checked the guide and saw {method}.',
                'The use note we have is {method}.',
            ], ['{method}' => $method !== '' ? $method : $timing]);
        }

        if (in_array('stage_rule', $reasonCodes, true) && ! empty($reasonData['formula_stage'])) {
            $stage = str_replace('_', ' ', (string) $reasonData['formula_stage']);

            return $this->line($product, 'support|stage', [
                'We have a guide for the {stage} stage.',
                'This one is noted for the {stage} stage, so we matched it to that step.',
                'We saw the {stage} stage in its use notes.',
                'Our guide points to the {stage} stage for this product.',
                'We noted it fits the {stage} stage of crop care.',
            ], ['{stage}' => $stage]);
        }

        $count = (int) ($reasonData['quotation_count'] ?? 0);
        if ($leadCode !== 'top_quoted' && $count >= 2) {
            $phrase = $count === 2
                ? 'two farm cost plans'
                : $count.' farm cost plans';

            return $this->line($product, 'support|quotes', [
                'We also saw it in {count}.',
                'It turned up in {count} as well.',
                'We found it in {count}, too.',
                'Other plans we checked include it in {count}.',
                'We noticed it in {count} besides your records.',
            ], ['{count}' => $phrase]);
        }

        if ($leadCode !== 'own_quotation_match' && in_array('own_quotation_match', $reasonCodes, true)) {
            return $this->line($product, 'support|own', [
                'You already priced this one for your farm, so we kept it close.',
                'We saw it in your budget, which makes it easier to compare with your plan.',
                'Your earlier price for this product helped us put it here.',
                'We remembered the price you set for this one.',
                'Because you’ve costed this product before, we kept it in view for you.',
            ], ['{kind}' => $kindLabel]);
        }

        if (in_array('quotation_support', $reasonCodes, true)) {
            return $this->line($product, 'support|one', [
                'We also spotted it in one farm cost plan.',
                'One other cost plan includes it, too.',
                'We found it in a single farm budget we checked.',
                'It showed up once in the cost plans we looked at.',
                'We saw it in one other grower’s plan.',
            ], []);
        }

        return '';
    }

    private function advice(Product $product, string $kind): string
    {
        $lines = self::ADVICE[$kind] ?? self::ADVICE['input'];

        return $this->line($product, 'advice|'.$kind, $lines, []);
    }

    /**
     * @param  array<int, string>  $lines
     * @param  array<string, string>  $values
     */
    private function line(Product $product, string $key, array $lines, array $values): string
    {
        if ($lines === []) {
            return '';
        }

        $index = abs(crc32((string) $product->id.'|'.$key.'|'.now()->toDateString())) % count($lines);
        $template = $lines[$index];

        return str_replace(
            array_keys($values),
            array_values($values),
            $template,
        );
    }
}
