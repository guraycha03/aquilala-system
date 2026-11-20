<?php

namespace App\Services;

use Illuminate\Support\Collection;

/**
 * CropInputAdviceService
 *
 * Answers the "which of these suit my crop?" part of a product recommendation
 * when the association has no decision-formula link between the crop and the
 * shelf the farmer named. Sitaw is the common case: the library knows the crop
 * and the catalogue carries fertilizer, but no formula says which fertilizer
 * belongs to sitaw.
 *
 * The knowledge here is general, not numerical: nutrient behaviour of crop
 * groups, what a seed buyer should look for, and which product labels match
 * that behaviour. It never invents a rate, a price, or a stock figure — those
 * stay in the product cards built by ProductSearchService. It also never
 * decides that a product exists: it can only rank rows that were retrieved
 * from the catalogue.
 */
class CropInputAdviceService
{
    public function __construct(
        private readonly CropLibraryService $library,
    ) {
    }

    /**
     * Catalogue search terms for a shelf name. Several terms are searched for
     * shelves whose products use more than one label ("insecticide",
     * "fungicide" and so on all sit under Crop Protection).
     *
     * @return array<int, string>
     */
    public function searchTerms(string $category): array
    {
        return match ($category) {
            'Fertilizer' => ['fertilizer', 'fertiliser'],
            'Seeds' => ['seed', 'binhi'],
            'Pesticide' => ['pesticide', 'insecticide', 'fungicide', 'herbicide'],
            'Tools & Equipment' => ['sprayer', 'tools', 'kagamitan'],
            'Soil Amendments' => ['lime', 'compost', 'organic fertilizer'],
            default => [],
        };
    }

    /**
     * General guidance for one crop and one shelf, plus the crop directory
     * information the answer may link to.
     *
     * @return array<string, mixed>
     */
    public function guidanceFor(?string $crop, string $category): array
    {
        $entry = $crop === null ? null : $this->library->forName($crop);
        $profile = $this->cropProfile($crop, $entry);

        $guidance = match ($category) {
            'Fertilizer' => $this->fertilizerGuidance($profile),
            'Seeds' => $this->seedGuidance($profile, $entry),
            'Pesticide' => $this->pesticideGuidance($profile),
            'Tools & Equipment' => $this->toolsGuidance($profile),
            'Soil Amendments' => $this->soilGuidance($profile),
            default => [
                'summary' => 'Choose a product labelled for ' . ($crop ?? 'your crop') . ' and follow the directions on its container.',
                'points' => [],
                'preferred' => [],
                'avoid' => [],
            ],
        };

        $guidance['category'] = $category;
        $guidance['crop'] = $entry['name'] ?? $crop;
        $guidance['crop_keywords'] = $profile['keywords'];
        $guidance['summary_tl'] = $this->tagalogSummary($guidance, $profile, $category);
        $guidance['points_tl'] = $this->tagalogPoints($guidance, $profile, $category);
        $guidance['crop_entry'] = $entry === null ? null : [
            'name' => $entry['name'] ?? null,
            'category' => $entry['category'] ?? null,
            'group' => $entry['group'] ?? null,
            'description' => $entry['description'] ?? null,
            'planting_window' => $entry['planting_window'] ?? null,
            'growing' => $entry['growing'] ?? null,
            'aliases' => $entry['aliases'] ?? [],
        ];
        $guidance['crop_image'] = $this->cropImage($entry);

        return $guidance;
    }

    /**
     * Rank retrieved products by how well their labels match the guidance.
     * Nothing is added: a catalogue row that was not retrieved cannot appear.
     *
     * @param  Collection<int, \App\Models\Product>  $products
     * @param  array<string, mixed>  $guidance
     * @return Collection<int, \App\Models\Product>
     */
    public function rank(Collection $products, array $guidance): Collection
    {
        $preferred = (array) ($guidance['preferred'] ?? []);
        $avoid = (array) ($guidance['avoid'] ?? []);
        $keywords = array_map('mb_strtolower', (array) ($guidance['crop_keywords'] ?? []));

        $scored = $products->map(function ($product) use ($preferred, $avoid, $keywords) {
            $haystack = mb_strtolower(implode(' ', array_filter([
                $product->name,
                $product->brand,
                $product->product_type,
                $product->description,
                $product->category_display_name,
                $product->top_category_group,
            ])));

            $score = 0;

            foreach ($preferred as $trait) {
                foreach ($this->traitPatterns((string) $trait) as $pattern) {
                    if (preg_match($pattern, $haystack) === 1) {
                        $score += 12;
                        break;
                    }
                }
            }

            foreach ($avoid as $trait) {
                foreach ($this->traitPatterns((string) $trait) as $pattern) {
                    if (preg_match($pattern, $haystack) === 1) {
                        $score -= 10;
                        break;
                    }
                }
            }

            // A product named for the crop is the strongest signal a catalogue
            // row can carry — "Sitaw (String Beans) Seeds" for sitaw seed.
            $cropMatch = false;

            foreach ($keywords as $keyword) {
                if ($keyword !== '' && str_contains($haystack, $keyword)) {
                    $score += 20;
                    $cropMatch = true;
                    break;
                }
            }

            return ['product' => $product, 'score' => $score, 'crop_match' => $cropMatch];
        });

        // Stable sort keeps catalogue order for equal scores, so an equally
        // valid set of products does not reshuffle on every request.
        $ranked = $scored->sortByDesc('score')->values();

        // When the crop is named on any product, those are the answer to a
        // crop-specific question. Rice seed is not a useful extra card on an
        // ampalaya seed question. Fertilizer rarely carries a crop name, so
        // the general nutrient ranking stands in that case.
        if ($keywords !== []) {
            $cropMatches = $ranked->filter(static fn (array $row): bool => $row['crop_match'] === true);

            if ($cropMatches->isNotEmpty()) {
                return $cropMatches->map(fn (array $row) => $row['product'])->values();
            }
        }

        return $ranked->map(fn (array $row) => $row['product']);
    }

    /**
     * @param  array<string, mixed>  $guidance
     * @param  array<string, mixed>  $profile
     */
    private function tagalogSummary(array $guidance, array $profile, string $category): string
    {
        $name = (string) ($profile['name'] ?? $this->nameFromGuidance($guidance));

        if ($category === 'Fertilizer') {
            if ($profile['legume']) {
                return $name . ' ay legume — nakakakuha ito ng sariling nitrogen, kaya ang bagay ay balanced na complete fertilizer sa pagtatanim, kasama ang phosphorus at potash para sa ugat at pods. Iwasan ang sobrang straight nitrogen.';
            }
            if ($profile['cereal']) {
                return $name . ' ay madaming kumakain ng nitrogen: complete NPK sa pagtatanim, tapos nitrogen topdress habang aktibong lumalaki ang tanim.';
            }
            if ($profile['root']) {
                return $name . ' ay itinatanim para sa ugat/tuber, kaya mas mahalaga ang phosphorus at potash kaysa sobrang nitrogen.';
            }
            if ($profile['fruit']) {
                return $name . ' ay nangangailangan ng balanced na nutrisyon at dagdag na potassium pag nagbubunga, kasama ang maraming organic matter.';
            }
            if ($profile['leafy']) {
                return $name . ' ay itinatanim para sa dahon, kaya ang nitrogen ang pangunahing kailangan — mas mainam mula sa complete fertilizer at kaunting organic na topdress.';
            }

            return 'Ang balanced na complete fertilizer ang ligtas na simulang abono' . ($name !== '' ? ' para sa ' . $name : '') . ', na may magaang na nitrogen topdress pagkatapos mag-establish.';
        }

        if ($category === 'Seeds') {
            return 'Para sa ' . ($name !== '' ? $name : 'inyong tanim') . ', pumili ng variety na bagay sa inyong panahon at bumili ng malinis na binhi sa kilalang tindahan — dapat nakasaad sa packet ang pangalan ng tanim at variety.';
        }

        if ($category === 'Pesticide') {
            return 'Itugma ang produkto sa aktwal na peste at sa crop label — ang "crop protection" ay isang kategorya, hindi iisang gamot.';
        }

        if ($category === 'Tools & Equipment') {
            return 'Pumili ng kagamitan ayon sa trabaho — sprayer para sa pag-spray, pump para sa tubig — at tiyakin na bagay ito sa laki ng inyong tanim.';
        }

        if ($category === 'Soil Amendments') {
            return 'Ang soil amendment ay pinipili mula sa soil test: lime para sa acidic na lupa, compost at organic matter para sa istruktura at paghawak ng nutrients.';
        }

        return (string) ($guidance['summary'] ?? '');
    }

    /**
     * @param  array<string, mixed>  $guidance
     * @param  array<string, mixed>  $profile
     * @return array<int, string>
     */
    private function tagalogPoints(array $guidance, array $profile, string $category): array
    {
        if ($category === 'Fertilizer') {
            if ($profile['legume']) {
                return [
                    'Magsimula sa balanced complete NPK (halimbawa 14-14-14) na hinaluan sa planting hill.',
                    'Ang phosphorus at potassium ay tumutulong sa ugat at pagbuo ng pods; magaang na topdress lang pagkatapos mag-establish.',
                    'Ang sobrang urea o straight nitrogen ay nagpapalapad ng sanga at dahon sa halip na bunga.',
                ];
            }
            if ($profile['cereal']) {
                return [
                    'Lagyan ng complete fertilizer sa pagtatanim.',
                    'Mag-topdress ng urea o ibang nitrogen source habang aktibong lumalaki ang tanim.',
                    'Hatiin ang nitrogen sa ilang beses para bawasan ang nasasayang at maging pantay ang paglaki.',
                ];
            }
            if ($profile['root']) {
                return [
                    'Gumamit ng complete fertilizer na may sapat na potash sa pagtatanim.',
                    'Katamtaman lang ang nitrogen — ang sobra ay napupunta sa dahon, hindi sa ugat.',
                    'Ang organic matter ay nagpapalambot at nagpapahinga ng lupa para sa ugat.',
                ];
            }
            if ($profile['fruit']) {
                return [
                    'Gumamit ng complete NPK bilang base dressing.',
                    'Mahalaga ang potassium simula sa pagbubunga hanggang sa paglaki ng bunga.',
                    'Ang compost o organic fertilizer ay nagpapabuti ng lupa sa paligid ng tanim.',
                ];
            }
            if ($profile['leafy']) {
                return [
                    'Ang complete fertilizer ang magbibigay ng base nutrients.',
                    'Ang magaang na nitrogen side-dress ay nagpapanatili ng pagtubo ng dahon.',
                    'Ang organic fertilizer ay tumutulong magpreno ng tubig at nutrients sa ibabaw ng lupa.',
                ];
            }

            return [
                'Haluuan ng complete NPK ang lupa sa pagtatanim.',
                'Mag-topdress nang magaang habang lumalaki, tapos bawasan pag nagbubunga na.',
                'Ang organic matter ay nagpapabuti ng paghawak ng tubig at nutrients.',
            ];
        }

        if ($category === 'Seeds') {
            $points = [];
            $varieties = collect((array) ($profile['varieties'] ?? []))->pluck('name')->filter()->take(3)->values()->all();
            if ($varieties !== []) {
                $points[] = 'Hanapin ang ' . implode(', ', $varieties) . ' o ibang variety na bagay sa inyong lugar.';
            }
            $window = (string) ($profile['planting_window'] ?? '');
            if ($window !== '') {
                $points[] = 'Planting window mula sa crop directory: ' . $window . '.';
            }
            $method = (string) ($profile['method'] ?? '');
            if ($method !== '') {
                $points[] = 'Pagtanim: ' . $method . '.';
            }
            $points[] = 'Bumili ng certified o malinis na binhi at tingnan ang packet kung tama ang tanim at variety.';

            return $points;
        }

        if ($category === 'Pesticide') {
            return [
                'Kilalanin muna ang peste o sakit bago pumili ng insecticide, fungicide o herbicide.',
                'Gumamit ng produktong may label para sa tanim na ito at sundin ang rate at pre-harvest interval.',
                'Palitan ang mode of action kung maaari para hindi maging resistant ang peste.',
            ];
        }

        if ($category === 'Tools & Equipment') {
            return [
                'Itugma ang laki ng kagamitan sa inyong tinatanim na lugar.',
                'Tingnan kung may available na piyesa at serbisyo sa inyong lugar.',
                'Gamitin ang tamang nozzle o setting para sa produktong ilalapat.',
            ];
        }

        if ($category === 'Soil Amendments') {
            return [
                'Magpa-soil test muna para malaman kung anong kulang ang lupa.',
                'Ang compost at organic matter ay nakakatulong sa karamihang lupa ng gulay.',
                'Ang lime ay kapaki-pakinabang lang kapag acidic ang lupa — sundin ang resulta ng test sa dami.',
            ];
        }

        return [];
    }

    private function nameFromGuidance(array $guidance): string
    {
        return (string) ($guidance['crop'] ?? '');
    }

    /**
     * Resolve the crop family from the library entry and its own name. Kept as
     * a small profile so every shelf answers the same crop the same way.
     *
     * @param  array<string, mixed>|null  $entry
     * @return array<string, mixed>
     */
    private function cropProfile(?string $crop, ?array $entry): array
    {
        $name = mb_strtolower((string) ($entry['name'] ?? $crop ?? ''));
        $group = mb_strtolower((string) ($entry['group'] ?? ''));
        $category = mb_strtolower((string) ($entry['category'] ?? ''));

        $legume = preg_match('/(bean|sitaw|mung|peanut|soy|legume)/u', $name . ' ' . $group) === 1;
        $cereal = preg_match('/(rice|corn|wheat|maize|cereal)/u', $name . ' ' . $group) === 1;
        $root = preg_match('/(cassava|potato|carrot|ginger|taro|gabi|yam|root)/u', $name . ' ' . $group) === 1;
        $leafy = preg_match('/(pechay|lettuce|cabbage|kangkong|malunggay|leafy)/u', $name . ' ' . $group) === 1;
        $fruit = preg_match('/(banana|mango|papaya|pineapple|watermelon|calamansi|citrus|coconut|fruit|pili|jackfruit|avocado)/u', $name . ' ' . $group) === 1;

        return [
            'name' => (string) ($entry['name'] ?? $crop ?? ''),
            'group' => $group !== '' ? $group : $category,
            'legume' => $legume,
            'cereal' => $cereal,
            'root' => $root,
            'leafy' => $leafy,
            'fruit' => $fruit,
            'varieties' => $entry['varieties'] ?? [],
            'planting_window' => $entry['planting_window'] ?? '',
            'method' => $entry['growing']['method'] ?? '',
            'keywords' => array_values(array_unique(array_filter(array_map(
                'mb_strtolower',
                array_merge(
                    (array) ($entry['product_keywords'] ?? []),
                    (array) ($entry['aliases'] ?? [])
                )
            )))),
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function fertilizerGuidance(array $profile): array
    {
        $name = (string) ($profile['name'] ?? '');

        if ($profile['legume']) {
            return [
                'summary' => $name . ' is a legume: it fixes much of its own nitrogen, so the useful fertilizer is a balanced complete one at planting with phosphorus and potash for roots and pods — not a heavy straight-nitrogen programme.',
                'points' => [
                    'Start with a balanced complete NPK (for example 14-14-14) worked into the planting hill.',
                    'Phosphorus and potassium support root growth and pod filling; a light topdress is enough once plants are established.',
                    'Too much urea or other straight nitrogen makes vines and leaves instead of pods.',
                ],
                'preferred' => ['balanced_npk', 'phosphorus', 'potassium', 'organic'],
                'avoid' => ['heavy_nitrogen'],
            ];
        }

        if ($profile['cereal']) {
            return [
                'summary' => $name . ' is a heavy nitrogen feeder. A complete NPK at planting followed by a nitrogen topdress during active growth is the usual approach.',
                'points' => [
                    'Apply a balanced complete fertilizer at planting.',
                    'Side-dress with urea or another nitrogen source when the crop is actively tillering or vegetating.',
                    'Splitting the nitrogen reduces losses and gives a steadier crop.',
                ],
                'preferred' => ['balanced_npk', 'nitrogen'],
                'avoid' => [],
            ];
        }

        if ($profile['root']) {
            return [
                'summary' => $name . ' is grown for its stored root or tuber, so phosphorus and potassium matter more than a large nitrogen dose.',
                'points' => [
                    'Use a complete fertilizer with adequate potash at planting.',
                    'Keep nitrogen modest — excess goes to leaves rather than the harvested root.',
                    'Organic matter improves the loose soil root crops need.',
                ],
                'preferred' => ['potassium', 'phosphorus', 'balanced_npk', 'organic'],
                'avoid' => ['heavy_nitrogen'],
            ];
        }

        if ($profile['fruit']) {
            return [
                'summary' => $name . ' needs balanced nutrition with extra potassium for flowering and fruit sizing, supported by plenty of organic matter.',
                'points' => [
                    'Use a complete NPK as the base dressing.',
                    'Potassium is especially important once the crop starts flowering and fruiting.',
                    'Compost or organic fertilizer improves the soil around fruiting crops.',
                ],
                'preferred' => ['potassium', 'balanced_npk', 'organic'],
                'avoid' => ['heavy_nitrogen'],
            ];
        }

        if ($profile['leafy']) {
            return [
                'summary' => $name . ' is grown for its leaves, so nitrogen drives the yield — preferably from a complete fertilizer plus a light organic topdress.',
                'points' => [
                    'A balanced complete fertilizer gives the base nutrients.',
                    'A light nitrogen side-dress keeps leaf production going through successive cuts.',
                    'Organic fertilizer helps hold moisture and nutrients in the topsoil.',
                ],
                'preferred' => ['nitrogen', 'balanced_npk', 'organic'],
                'avoid' => [],
            ];
        }

        return [
            'summary' => $name !== '' ? 'A balanced complete fertilizer is the safe starting point for ' . $name . ', with a light nitrogen side-dress once it is established.' : 'A balanced complete fertilizer is the safe starting point for most vegetables.',
            'points' => [
                'Work a complete NPK into the soil at planting.',
                'Side-dress lightly as the crop establishes, then ease off once fruiting begins.',
                'Organic matter improves moisture holding and nutrient availability.',
            ],
            'preferred' => ['balanced_npk', 'potassium', 'organic'],
            'avoid' => ['heavy_nitrogen'],
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @param  array<string, mixed>|null  $entry
     * @return array<string, mixed>
     */
    private function seedGuidance(array $profile, ?array $entry): array
    {
        $name = (string) ($entry['name'] ?? $profile['name'] ?? '');
        $varieties = collect((array) ($entry['varieties'] ?? []))
            ->pluck('name')
            ->filter()
            ->take(3)
            ->values()
            ->all();
        $window = (string) ($entry['planting_window'] ?? '');
        $method = (string) ($entry['growing']['method'] ?? '');

        $points = [];

        if ($varieties !== []) {
            $points[] = 'Look for ' . implode(', ', $varieties) . ' or another locally adapted variety when choosing seed.';
        }
        if ($window !== '') {
            $points[] = 'Planting window from the crop directory: ' . $window . '.';
        }
        if ($method !== '') {
            $points[] = 'Establishment: ' . $method . '.';
        }
        $points[] = 'Buy certified or clean seed from a known store, and check the packet for the crop name and variety.';

        return [
            'summary' => $name !== ''
                ? 'For ' . $name . ', choose a variety suited to your season and buy clean seed from a known store — the seed packet should name the crop and variety.'
                : 'Choose certified or clean seed of a variety suited to your season, and check that the packet names the crop.',
            'points' => $points,
            'preferred' => $profile['keywords'],
            'avoid' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function pesticideGuidance(array $profile): array
    {
        $name = (string) ($profile['name'] ?? '');

        return [
            'summary' => 'Match the product to the actual pest and the crop label — "crop protection" is a shelf, not a single treatment.',
            'points' => [
                'Identify the pest or disease before choosing an insecticide, fungicide or herbicide.',
                'Use a product labelled for ' . ($name !== '' ? $name : 'your crop') . ' and follow the label rate and pre-harvest interval.',
                'Rotate modes of action where possible so pests do not build resistance.',
            ],
            'preferred' => $profile['keywords'],
            'avoid' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function toolsGuidance(array $profile): array
    {
        return [
            'summary' => 'Choose equipment by the job — a sprayer for foliar work, a pump for water — and check it suits the area you manage.',
            'points' => [
                'Match capacity to your planted area rather than buying the largest unit.',
                'Check that replacement parts and service are available locally.',
                'Use the correct nozzle or setting for the product being applied.',
            ],
            'preferred' => [],
            'avoid' => [],
        ];
    }

    /**
     * @param  array<string, mixed>  $profile
     * @return array<string, mixed>
     */
    private function soilGuidance(array $profile): array
    {
        return [
            'summary' => 'Soil amendments are selected from a soil test: lime corrects acidity, compost and organic matter improve structure and nutrient holding.',
            'points' => [
                'Test the soil first so the amendment answers a real deficiency.',
                'Compost and organic matter help most vegetable soils, especially those worked repeatedly.',
                'Lime is only useful when the soil is actually acidic — follow a test recommendation for the rate.',
            ],
            'preferred' => ['organic', 'potassium'],
            'avoid' => [],
        ];
    }

    /**
     * Label patterns for a nutrient trait. Kept private and small: this is a
     * reading aid for ranks, not a rate calculator.
     *
     * @return array<int, string>
     */
    private function traitPatterns(string $trait): array
    {
        return match ($trait) {
            'balanced_npk' => ['/\bcomplete\b/u', '/\bnpk\b/u', '/\b14\s*[-\/]\s*14\s*[-\/]\s*14\b/u', '/\b16\s*[-\/]\s*16\s*[-\/]\s*16\b/u'],
            'nitrogen', 'heavy_nitrogen' => ['/\burea\b/u', '/\bammonium\b/u', '/\bnitrogen\b/u', '/\b46\s*[-\/]\s*0\s*[-\/]\s*0\b/u', '/\b21\s*[-\/]\s*0\s*[-\/]\s*0\b/u'],
            'phosphorus' => ['/\bphosphat\b/u', '/\bsuperphosphate\b/u', '/\b0\s*[-\/]\s*(?:20|30|45)\b/u'],
            'potassium' => ['/\bpotash\b/u', '/\bpotassium\b/u', '/\bmuriate\b/u', '/\b0\s*[-\/]\s*0\s*[-\/]\s*(?:50|60)\b/u'],
            'organic' => ['/\borganic\b/u', '/\bcompost\b/u', '/\bhumus\b/u', '/\bvermicompost\b/u'],
            'micronutrient' => ['/\bmicro\b/u', '/\bzinc\b/u', '/\bboron\b/u'],
            default => [],
        };
    }

    /**
     * The directory's JPG files are named after crop names. Only return a URL
     * when the file really exists; a broken thumbnail is worse than none.
     *
     * @param  array<string, mixed>|null  $entry
     */
    private function cropImage(?array $entry): ?string
    {
        if ($entry === null) {
            return null;
        }

        $words = array_merge([(string) ($entry['name'] ?? '')], (array) ($entry['aliases'] ?? []));
        $candidates = [];

        foreach ($words as $word) {
            $slug = preg_replace('/[^\p{L}\p{N}]+/u', '-', mb_strtolower(trim((string) $word))) ?? '';
            $slug = trim($slug, '-');

            if ($slug !== '') {
                $candidates[] = $slug;
            }
        }

        foreach (array_unique($candidates) as $candidate) {
            $path = public_path('images/crops/' . $candidate . '.jpg');

            if (is_file($path)) {
                return asset('images/crops/' . $candidate . '.jpg');
            }
        }

        return null;
    }
}
