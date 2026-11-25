<?php

namespace App\Services;

use App\Models\Product;
use Illuminate\Support\Str;

/**
 * Writes the "Why recommended" text from structured reason data.
 *
 * The wording is template-based and deterministic: the same product gets the
 * same explanation for a given day. It draws only on saved farm records, the
 * product's own application data and the current season. No AI service is used.
 */
class RecommendationExplanationService
{
    /** @var array<string, array<int, string>> */
    private const ADVICE = [
        'seed' => [
            'Compare its seed rate and maturity with your field size before choosing.',
            'It is worth weighing against other seed options for expected yield and price.',
            'Check the seed rate and planting window against your land area.',
        ],
        'seedling' => [
            'Compare the transplanting window and seedling count with your field.',
            'It is worth checking the seedlings’ maturity and price against your plan.',
            'Match the transplanting timing with the crop you are preparing.',
        ],
        'fertilizer' => [
            'Check its nutrient rating and rate per hectare against your land area.',
            'Compare the nutrient balance and application rate with your current input plan.',
            'Its rate per hectare is worth checking before you add it to your cost plan.',
        ],
        'protection' => [
            'Review the recommended timing and rate before adding it to your crop-protection plan.',
            'It is worth checking the target pest and application window first.',
            'Compare its timing and rate with the problem you are seeing in the field.',
        ],
        'tool' => [
            'Compare it with your current tools before your next supply purchase.',
            'It is worth checking the size and capacity against your farm.',
            'Review its specifications against the work you need to do.',
        ],
        'irrigation' => [
            'Compare its coverage with your field before your next supply purchase.',
            'It is worth checking the capacity against your irrigation needs.',
            'Review its reach and setup against the area you need to water.',
        ],
        'feed' => [
            'Review it alongside the feed needs of your animals.',
            'Compare it with the other feed supplies you already use.',
            'It is worth checking the ration and pack size against your herd.',
        ],
        'postharvest' => [
            'Review it alongside your post-harvest handling plan.',
            'It is worth checking the capacity against your expected harvest.',
            'Compare it with the post-harvest steps you already follow.',
        ],
        'input' => [
            'Worth comparing against your current farm input plan.',
            'Check its rate and pack size against your land area before choosing.',
            'Review it alongside the other supplies you are considering.',
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
            ? 'It matches the system’s farm-input rules.'
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
                'This {kind} is matched to {crop}, the crop you have planted this season.',
                'Your active {crop} planting record makes this {kind} a direct fit.',
                'Because you planted {crop}, this {kind} belongs in your current farm plan.',
            ],
            'crop_formula_match' => [
                'This {kind} matches the {crop} you already priced in a cost quotation.',
                'Your {crop} quotation shows a need that this {kind} can cover.',
                'It is tied to {crop}, the crop you included in a previous cost quotation.',
            ],
            'harvest_history_match' => [
                'This {kind} follows on from the {crop} you harvested before.',
                'Your past {crop} harvest makes this {kind} relevant again.',
                'It is connected to {crop} from your harvest history.',
            ],
            'own_quotation_match' => [
                'You already included this product in a cost quotation for your farm.',
                'This is one of the products you previously priced for your inputs.',
                'Your own quotation already lists this product, so the system keeps it in view.',
            ],
            'top_quoted' => [
                'This {kind} appears in farmer cost quotation records.',
                'Other farmers have included this {kind} in their input plans.',
                'Recorded farmer quotations make this {kind} a practical reference point.',
            ],
            default => [
                'This {kind} has an approved rule in the farm-input library.',
                'The system’s rule library recognises this {kind} for farm use.',
                'This {kind} is backed by an approved application rule.',
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
            $lines = [
                '{crop} is a strong {season}-season choice, and this product is listed for that same planting window.',
                'Its registered timing fits the current {season} planting window, when {crop} is usually established.',
                'For {season}-season planting, this {kind} aligns with the {crop} you are preparing for that planting window.',
            ];

            return $this->line($product, 'season|seed', $lines, [
                '{kind}' => $kindLabel,
                '{crop}' => $crop,
                '{season}' => $season,
            ]);
        }

        $lines = [
            'Its application rule is set for the current {season} season.',
            'The system lists this {kind} for {season}-season use, which matches today’s field conditions.',
            'It carries a {season}-season application rule, so it is timely to consider now.',
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
                    'Its guide recommends {method} during {timing}.',
                    'The listed use is {method}, timed for {timing}.',
                ], ['{method}' => $method, '{timing}' => $timing]);
            }

            return $this->line($product, 'support|method', [
                'Its guide recommends {method}.',
                'The listed application method is {method}.',
            ], ['{method}' => $method !== '' ? $method : $timing]);
        }

        if (in_array('stage_rule', $reasonCodes, true) && ! empty($reasonData['formula_stage'])) {
            $stage = str_replace('_', ' ', (string) $reasonData['formula_stage']);

            return $this->line($product, 'support|stage', [
                'A system rule is set for the {stage} stage.',
                'It is registered for the {stage} stage of crop care.',
            ], ['{stage}' => $stage]);
        }

        $count = (int) ($reasonData['quotation_count'] ?? 0);
        if ($leadCode !== 'top_quoted' && $count >= 2) {
            $phrase = $count === 2
                ? 'two recorded farmer quotations'
                : $count.' recorded farmer quotations';

            return $this->line($product, 'support|quotes', [
                'It appears in {count}.',
                'Farmer cost records list it in {count}.',
            ], ['{count}' => $phrase]);
        }

        if ($leadCode !== 'own_quotation_match' && in_array('own_quotation_match', $reasonCodes, true)) {
            return $this->line($product, 'support|own', [
                'You already priced this product for your farm, so it is a familiar option.',
                'Your own quotation makes it easier to compare with your previous budget.',
            ], ['{kind}' => $kindLabel]);
        }

        if (in_array('quotation_support', $reasonCodes, true)) {
            return $this->line($product, 'support|one', [
                'It also appears in one recorded quotation.',
                'One recorded quotation includes this product.',
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
