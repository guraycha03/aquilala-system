<?php

namespace App\Services;

use App\Helpers\SeasonHelper;
use App\Models\Crop;
use App\Models\DecisionFormula;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * CropService
 *
 * The crops Aquilala actually knows about, read from the application's own
 * records rather than a list written into a prompt.
 *
 * Two real sources, and they answer different questions:
 *
 *   - the `crops` table is the association's crop directory. It is what
 *     Aquilala is willing to talk about, and it carries a category and a
 *     description for each crop.
 *   - `decision_formulas` is what the association has actually costed. A crop
 *     with formulas for a season is a crop Aquilala can put products, rates
 *     and a cost estimate behind; a crop with none is a name in a directory and
 *     nothing more.
 *
 * Keeping them apart is the point. "What fruit crops does Aquilala support?" is
 * answered from the directory. "What can I plant this October?" is answered from
 * the formulas for the season October falls in, because a suitability claim with
 * no formula behind it is a guess with a confident voice.
 */
class CropService
{
    /**
     * Cached directory rows, keyed by normalised crop name.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $directory = null;

    /**
     * Keys of the rows that are category labels rather than crops, e.g.
     * "Vegetables" or "Root Crops".
     *
     * @var array<string, true>|null
     */
    private ?array $placeholderKeys = null;

    /**
     * The placeholder rows by their own spelling, for diagnostics.
     *
     * @var array<int, string>
     */
    private array $placeholderNames = [];

    /**
     * Cached formula summary, keyed by season.
     *
     * @var array<string, array<string, mixed>>|null
     */
    private ?array $formulas = null;

    /**
     * Every plantable crop in the Aquilala directory, with its category and
     * description.
     *
     * Rows flagged as category placeholders are left out. "Vegetables" and "Root
     * Crops" are grouping labels that happen to sit in the crops table; they are
     * real records that other parts of the application group by, and they are not
     * things a farmer can plant. Listing them as crop choices is the specific
     * mistake the flag exists to prevent, so they never reach a farmer-facing
     * answer, a recommendation, or a seasonal list.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        if ($this->directory !== null) {
            return array_values($this->directory);
        }

        $crops = [];
        $placeholders = [];

        foreach (Crop::orderBy('name')->get() as $crop) {
            $name = trim((string) $crop->name);

            if ($name === '') {
                continue;
            }

            if ((bool) $crop->is_category_placeholder) {
                $placeholders[$this->key($name)] = $name;

                continue;
            }

            $crops[$this->key($name)] = [
                'name'        => $name,
                'category'    => $crop->category !== null ? (string) $crop->category : null,
                'description' => $crop->description !== null ? trim((string) $crop->description) : null,
            ];
        }

        $this->directory = $crops;
        $this->placeholderKeys = array_fill_keys(array_keys($placeholders), true);
        $this->placeholderNames = array_values($placeholders);

        return array_values($crops);
    }

    /**
     * The directory's category labels, e.g. Vegetables, Legumes, Root Crops.
     *
     * @return array<int, string>
     */
    public function placeholders(): array
    {
        $this->all();

        return $this->placeholderNames ?? [];
    }

    /**
     * The crop categories the directory actually uses, e.g. Fruit, Vegetable,
     * Cereal. Used to answer "what fruits can I plant?" without a hardcoded list
     * of fruit names.
     *
     * @return array<int, string>
     */
    public function categories(): array
    {
        return Collection::make($this->all())
            ->pluck('category')
            ->filter()
            ->map(fn ($category) => (string) $category)
            ->unique()
            ->sort()
            ->values()
            ->all();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function inCategory(string $category): array
    {
        $needle = $this->key($category);

        return array_values(array_filter(
            $this->all(),
            fn (array $crop) => $crop['category'] !== null && $this->key((string) $crop['category']) === $needle
        ));
    }

    /**
     * Which category a farmer's word refers to, if the directory uses it.
     *
     * "What fruits can I plant?" and "any vegetables?" both arrive as a category
     * rather than a crop name, and the directory is what makes the difference
     * between a real answer and a guess.
     *
     * @return array<int, string>
     */
    public function categoriesMatching(string $text): array
    {
        $needle = $this->key($text);

        if ($needle === '') {
            return [];
        }

        return array_values(array_filter(
            $this->categories(),
            fn (string $category) => $needle !== '' && str_contains($this->key($category), $needle)
        ));
    }

    /**
     * One crop from the directory, matched on name.
     *
     * @return array<string, mixed>|null
     */
    public function find(?string $name): ?array
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        // Load first. An exact name used to be looked up in an empty cache and
        // fell through to the loose match, so "corn" could be reported as an
        // unknown crop whenever nothing else had warmed the directory.
        $this->all();

        return $this->directory[$this->key($name)] ?? $this->matchLoosely($name);
    }

    /**
     * Directory crops whose name or description matches what the farmer typed.
     *
     * @return array<int, array<string, mixed>>
     */
    public function search(string $text, int $limit = 25): array
    {
        $needle = $this->key($text);

        if ($needle === '') {
            return [];
        }

        $matches = array_values(array_filter($this->all(), function (array $crop) use ($needle) {
            if (str_contains($this->key((string) $crop['name']), $needle)) {
                return true;
            }

            $category = $crop['category'] !== null ? $this->key((string) $crop['category']) : '';

            if ($category !== '' && str_contains($category, $needle)) {
                return true;
            }

            $description = $crop['description'] !== null ? $this->key((string) $crop['description']) : '';

            return $description !== '' && str_contains($description, $needle);
        }));

        return array_slice($matches, 0, $limit);
    }

    /**
     * The crop types the association has decision formulas for, with the seasons
     * each one is costed in.
     *
     * @param  string|null  $season  'wet', 'dry', or null for every season
     * @return array<int, array<string, mixed>>
     */
    public function formulaCrops(?string $season = null): array
    {
        $rows = $this->formulaIndex();

        $crops = [];

        foreach ($rows as $cropType => $record) {
            $seasons = $record['seasons'];

            if ($season !== null && ! in_array($season, $seasons, true)) {
                continue;
            }

            if ($record['is_category_placeholder']) {
                continue;
            }

            $crops[] = [
                'name'        => $cropType,
                'seasons'     => $seasons,
                'has_formula' => $record['formula_count'] > 0,
                'category'    => $record['category'],
                'description' => $record['description'],
            ];
        }

        // Alphabetical, never by formula count. A crop with 39 formulas is not
        // better than one with 24; it is only better covered, and ordering a
        // farmer's list by that turns a metadata field into a ranking the
        // application has no basis for.
        usort($crops, static fn (array $a, array $b) => strcmp((string) $a['name'], (string) $b['name']));

        return $crops;
    }

    /**
     * The crops Aquilala can answer for in a given season: the ones with
     * formulas in that season, described from the directory where the two
     * overlap.
     *
     * @return array<int, array<string, mixed>>
     */
    public function forSeason(string $season, int $limit = 12): array
    {
        return array_slice($this->formulaCrops($this->normalizeSeason($season)), 0, $limit);
    }

    /**
     * Which farming season a calendar month falls in, from the application's own
     * Bicol climate calendar.
     */
    public function seasonForMonth(int $month): string
    {
        return SeasonHelper::getCurrentSeason(Carbon::create(Carbon::now()->year, $month, 1));
    }

    /**
     * The season a named month refers to, or null when the word is not a month.
     */
    public function seasonForMonthName(string $text): ?array
    {
        $months = [
            'january' => 1, 'jan' => 1,
            'february' => 2, 'feb' => 2,
            'march' => 3, 'mar' => 3,
            'april' => 4, 'apr' => 4,
            'may' => 5,
            'june' => 6, 'jun' => 6,
            'july' => 7, 'jul' => 7,
            'august' => 8, 'aug' => 8,
            'september' => 9, 'sep' => 9, 'sept' => 9,
            'october' => 10, 'oct' => 10,
            'november' => 11, 'nov' => 11,
            'december' => 12, 'dec' => 12,
        ];

        $needle = $this->key($text);

        if (! isset($months[$needle])) {
            return null;
        }

        $month = $months[$needle];

        return [
            'month'      => $month,
            'month_name' => ucfirst($needle),
            'season'     => $this->seasonForMonth($month),
        ];
    }

    /**
     * The month a farmer means when they say "this month" or name no month at
     * all, so a seasonal answer can always be dated.
     *
     * @return array{month: int, month_name: string, season: string}
     */
    public function currentMonth(): array
    {
        $now = Carbon::now();

        return [
            'month'      => $now->month,
            'month_name' => $now->format('F'),
            'season'     => SeasonHelper::getCurrentSeason($now),
        ];
    }

    public function normalizeSeason(?string $season): ?string
    {
        if ($season === null) {
            return null;
        }

        $season = mb_strtolower(trim($season));

        return in_array($season, ['wet', 'dry'], true) ? $season : null;
    }

    /**
     * Formula coverage, keyed by crop type, built once per request.
     *
     * @return array<string, array<string, mixed>>
     */
    private function formulaIndex(): array
    {
        if ($this->formulas !== null) {
            return $this->formulas;
        }

        $seasonsByCrop = [];
        $countsByCrop = [];

        DecisionFormula::select('crop_type', 'season')
            ->get()
            ->each(function (DecisionFormula $formula) use (&$seasonsByCrop, &$countsByCrop) {
                $cropType = trim((string) $formula->crop_type);

                if ($cropType === '') {
                    return;
                }

                $seasonsByCrop[$cropType][$formula->season] = true;
                $countsByCrop[$cropType] = ($countsByCrop[$cropType] ?? 0) + 1;
            });

        $index = [];

        foreach ($seasonsByCrop as $cropType => $seasons) {
            $directory = $this->directoryEntryForFormulaCrop((string) $cropType);

            $index[$cropType] = [
                'seasons'       => array_keys($seasons),
                'formula_count' => $countsByCrop[$cropType] ?? 0,
                'category'      => $directory['category'] ?? null,
                'description'   => $directory['description'] ?? null,
                // Kept for filtering, never shown. See isPlaceholderCropType().
                'is_category_placeholder' => $this->isPlaceholderCropType((string) $cropType, $directory !== []),
            ];
        }

        return $this->formulas = $index;
    }

    /**
     * Whether a formula crop type is really a category label.
     *
     * The formula matrix stores inputs against groups as well as crops:
     * "Fruits" has 13 formulas and "Vegetables" has 35, neither of which is a
     * plant. Both are excluded from farmer-facing crop lists.
     *
     * A crop type that matches a real directory row is a real crop, whatever it
     * is called - so the category test only applies to types the directory does
     * not know, which is what keeps a genuine crop from being dropped for
     * sharing a name with its own category.
     *
     * @param  bool  $isInDirectory  whether a non-placeholder row backs this type
     */
    private function isPlaceholderCropType(string $cropType, bool $isInDirectory): bool
    {
        if ($isInDirectory) {
            return false;
        }

        $this->all();

        $key = $this->key($cropType);

        if (isset($this->placeholderKeys[$key])) {
            return true;
        }

        foreach ($this->categories() as $category) {
            $categoryKey = $this->key($category);

            if ($key === $categoryKey
                || $key === $categoryKey . 's'
                || $key === rtrim($categoryKey, 's') . 's'
                || $key === rtrim($categoryKey, 's')) {
                return true;
            }
        }

        return false;
    }

    /**
     * The directory row behind a formula crop type, if there is one.
     *
     * The two are not keyed the same way: the formula matrix says "Ampalaya
     * (bitter Gourd)" and the directory says "Ampalaya". Containment in either
     * direction is the match, longest name first so "Sweet Corn" is not read as
     * "Corn".
     *
     * @return array<string, mixed>
     */
    private function directoryEntryForFormulaCrop(string $cropType): array
    {
        $needle = $this->key($cropType);

        $best = [];
        $bestLength = 0;

        foreach ($this->all() as $crop) {
            $name = $this->key((string) $crop['name']);

            if ($name === '') {
                continue;
            }

            $matches = str_contains($needle, $name) || str_contains($name, $needle);

            if ($matches && mb_strlen($name) > $bestLength) {
                $best = $crop;
                $bestLength = mb_strlen($name);
            }
        }

        return $best;
    }

    /**
     * A directory crop for a name that is not an exact key match, e.g. "manggo"
     * or a crop type with a parenthetical suffix.
     *
     * @return array<string, mixed>|null
     */
    private function matchLoosely(string $name): ?array
    {
        $needle = $this->key($name);

        if (mb_strlen($needle) < 3) {
            return null;
        }

        foreach ($this->all() as $crop) {
            $cropKey = $this->key((string) $crop['name']);

            if ($cropKey !== '' && (str_contains($cropKey, $needle) || str_contains($needle, $cropKey))) {
                return $crop;
            }
        }

        return null;
    }

    /**
     * Normalise a crop or category name for matching: lowercase, and stripped of
     * the punctuation and parenthetical alternates that separate a Filipino
     * common name from its English one.
     */
    private function key(string $name): string
    {
        $name = preg_replace('/\(.*?\)/', ' ', $name) ?? $name;
        $name = preg_replace('/[^\p{L}\p{N}]+/u', ' ', mb_strtolower(trim($name))) ?? mb_strtolower(trim($name));

        return trim($name);
    }
}
