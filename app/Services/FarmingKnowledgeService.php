<?php

namespace App\Services;

use App\Helpers\SeasonHelper;
use App\Models\Crop;
use App\Models\DecisionFormula;

/**
 * FarmingKnowledgeService
 *
 * The approved knowledge the assistant is allowed to draw on when a farmer
 * asks something that is not a product lookup or a calculation.
 *
 * "Approved" is not a vibe here. Every figure below is already in the
 * application — the Sorsogon crop library in config/crop_library.php and the
 * Bicol climate calendar in SeasonHelper — which means the numbers have an
 * owner who put them there. Anything not in one of those two places is not
 * knowledge this assistant has, and it says so rather than improvising.
 */
class FarmingKnowledgeService
{
    /**
     * Crops with a curated entry in the Sorsogon crop library, keyed by
     * lowercase name. These are the crops the assistant can say something
     * substantive about without a product lookup.
     *
     * @return array<string, array<string, mixed>>
     */
    public function catalog(): array
    {
        $crops = app(CropLibraryService::class)->all();

        return collect($crops)
            ->filter(fn ($crop) => is_array($crop) && ! empty($crop['name']))
            ->keyBy(fn ($crop) => $this->key($crop['name']))
            ->all();
    }

    /**
     * Every crop name the assistant recognises, for intent routing and for
     * answering "what can you help with".
     *
     * @return array<int, string>
     */
    public function cropNames(): array
    {
        $names = array_map(
            fn ($crop) => (string) $crop['name'],
            array_values($this->catalog())
        );

        // The crops table widens the list beyond the curated catalogue.
        $names = array_merge($names, Crop::pluck('name')->map(fn ($name) => (string) $name)->all());

        // So do the crops the association actually has formulas for.
        $names = array_merge(
            $names,
            DecisionFormula::select('crop_type')->distinct()->pluck('crop_type')->map(fn ($c) => (string) $c)->all()
        );

        return array_values(array_unique(array_filter(array_map('trim', $names))));
    }

    /**
     * Look a crop up by name, case- and punctuation-insensitively.
     *
     * @return array<string, mixed>|null
     */
    public function forCrop(?string $name): ?array
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        $catalog = $this->catalog();
        $key = $this->key($name);

        return $catalog[$key] ?? app(CropLibraryService::class)->forName($name);
    }

    /**
     * The approved facts about one crop, shaped for the prompt.
     *
     * @return array<string, mixed>|null
     */
    public function factsFor(?string $name): ?array
    {
        $crop = $this->forCrop($name);

        if ($crop === null) {
            return null;
        }

        return [
            'crop' => $crop['name'],
            'group' => $crop['category'] ?? null,
            'local_name' => $crop['description'] ?? null,
            'typical_yield_per_ha' => isset($crop['default_yield_per_ha'])
                ? $crop['default_yield_per_ha'].' '.($crop['yield_unit'] ?? 'kg')
                : null,
            'maturity_days' => $crop['maturity_days'] ?? null,
            'seasons' => array_map(
                fn ($season) => $season === 'wet' ? 'wet season' : 'dry season',
                (array) ($crop['seasons'] ?? [])
            ),
        ];
    }

    /**
     * The Bicol climate calendar for right now.
     *
     * @return array<string, mixed>
     */
    public function seasonProfile(): array
    {
        $climate = SeasonHelper::getClimateInfo();

        return [
            'season' => $climate['season'],
            'label' => $climate['label'],
            'months' => $climate['months'],
            'sub_season' => $climate['sub_season'],
            'typical_weather' => $climate['typical_weather'],
            'recommendation' => $climate['recommendation'],
        ];
    }

    /**
     * Everything the assistant is willing to say about farming in general.
     * Deliberately short: it is a framing, not an agronomy course.
     *
     * @return array<int, string>
     */
    public function generalGuidance(): array
    {
        return [
            'The '.app(CropLibraryService::class)->source().' crop library is the only crop reference this assistant uses.',
            'Application rates, planting distances and dosages come from Aquilala decision formulas, which are keyed to a crop and a season.',
            'Prices and stock always come from the live catalog, so anything quoted here can change without notice.',
        ];
    }

    /**
     * General planting steps for a crop, by crop group.
     *
     * This is the approved knowledge layer doing the job the specification asks
     * of it: practical, step-shaped guidance for "how do I plant corn?" without a
     * single number in it. Planting distance, seed rate and spray interval are
     * left out on purpose - they are the numbers a farmer would act on, and none
     * of them is recorded anywhere in Aquilala. Each step is something a farmer
     * can do today with what is already in the catalogue.
     *
     * These are general farming practice, not Aquilala rules, and the assistant
     * labels them that way when it uses them.
     *
     * @return array<int, string>
     */
    public function plantingSteps(?string $crop, ?string $group = null): array
    {
        $groupKey = $this->key((string) ($group ?? ''));

        $steps = match (true) {
            str_contains($groupKey, 'cereal') => [
                'Prepare the field so it drains well, and clear it of the previous crop\'s leftovers.',
                'Choose seed that suits the season you are planting in, and buy it from a supplier you can ask about.',
                'Plant at the spacing and depth printed on the seed pack, and keep the row straight so every plant gets the same light.',
                'Keep the soil evenly moist while the crop is young, and pull weeds early before they compete with it.',
                'Watch the leaves for pests and disease, and ask your supplier what the local recommendations are for this variety.',
                'Harvest when the crop reaches the maturity your seed supplier indicates for that variety.',
            ],
            str_contains($groupKey, 'vegetable') => [
                'Prepare a bed or field with well-broken soil, and mix in whatever organic matter you have available.',
                'Decide whether to sow seed directly or start seedlings first; seedlings give you a head start but need more care.',
                'Plant or transplant at the spacing on the seed packet, and firm the soil around each seedling.',
                'Keep the soil evenly moist - vegetables suffer quickly from both drying out and standing water.',
                'Thin or weed early, while the plants are small enough that removing one does not disturb its neighbours.',
                'Harvest leafy vegetables often, and pick fruit vegetables at the size your market wants.',
            ],
            str_contains($groupKey, 'fruit') => [
                'Buy healthy, well-established seedlings or grafted planting material rather than seeds for most fruit crops.',
                'Prepare the planting hole with compost or well-rotted material, and plant at the depth the seedling was raised at.',
                'Space the plants so their mature canopies will not touch, since crowded fruit trees fight for light.',
                'Water regularly while the plants are establishing, then deep-water less often as the roots spread.',
                'Mulch around the base to keep moisture in and weeds out, keeping the mulch off the trunk.',
                'Prune and shape the plant as it grows, and remove dead or diseased branches as you see them.',
            ],
            str_contains($groupKey, 'legume') => [
                'Prepare the soil well and make sure it drains, since legumes dislike standing water.',
                'Treat seed with an inoculant if the seed supplier recommends it - it helps the plant fix its own nitrogen.',
                'Plant at the spacing on the seed packet and avoid working the soil too deeply around the roots.',
                'Do not add heavy nitrogen fertilizer; legumes supply their own, and extra nitrogen gives you leaf instead of pod.',
                'Support climbing varieties with stakes or a trellis before the plants get heavy.',
                'Harvest pods as they fill, and pick regularly so the plant keeps producing.',
            ],
            str_contains($groupKey, 'root') => [
                'Break the soil deep and make it loose, because a hard pan is what stops root crops from forming properly.',
                'Plant seed or seedlings at the depth on the packet, in a bed or ridge you can water evenly.',
                'Thin the young plants to the spacing the seed supplier gives, since crowded roots will not size up.',
                'Keep moisture steady, and mulch to keep the soil soft around the roots.',
                'Lift or harvest before the crop bolts or becomes woody, following the maturity on the packet.',
            ],
            str_contains($groupKey, 'spice') => [
                'Start from healthy cuttings, rhizomes or seedlings rather than seed where the crop is normally propagated that way.',
                'Choose a sheltered spot with good drainage, since most spices rot in wet ground.',
                'Plant at the spacing the supplier recommends and water lightly until the plant is established.',
                'Mulch to keep the root zone moist and to suppress weeds.',
                'Prune dead growth after each harvest, and divide or repot clumps once they fill the space.',
            ],
            str_contains($groupKey, 'nut') => [
                'Plant grafted or proven seedlings, since a nut tree grown from seed can take years to bear.',
                'Give it room: mature nut trees need considerable space, so plant with the spacing the supplier specifies.',
                'Water deeply and regularly while it establishes, and keep a clear ring around the trunk.',
                'Mulch generously but keep it away from the trunk, and weed around the base by hand.',
                'Prune to build a strong frame in the early years, and remove deadwood as the tree matures.',
            ],
            default => [
                'Prepare the soil where you plan to plant, and work in whatever organic matter you have available.',
                'Get healthy planting material - seed, seedlings or cuttings - from a supplier you can ask questions about.',
                'Plant at the spacing and depth the supplier gives you for that variety, rather than guessing.',
                'Water consistently while the plant establishes, and keep the area around it free of weeds.',
                'Watch for pests and disease, and ask locally about what to use on that crop.',
                'Harvest at the maturity the supplier indicates for that variety.',
            ],
        };

        return $steps;
    }

    /**
     * Normalise a crop name for lookup: lowercase, and with the punctuation
     * that separates a Filipino common name from its English one removed, so
     * "sweet potato", "Sweet Potato" and "camote" style variants all match the
     * same catalogue entry.
     */
    private function key(string $name): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(trim($name))) ?? mb_strtolower(trim($name));
    }
}
