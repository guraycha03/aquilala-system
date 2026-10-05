<?php

namespace App\Services;

/**
 * Typed accessor for config/crop_library.php — the single source of truth for
 * crops, agronomy and varieties. Every consumer (planting form, Crop
 * Directory, quotation dropdown, yield estimator, AI knowledge) goes through
 * this service so the library stays the only place crop facts are written.
 */
class CropLibraryService
{
    /** Variety prepended to every crop's list when no certified variety is chosen. */
    public const GENERAL_VARIETY = 'Local / General variety';

    /** Stable key for the general variety (stored on farmer_crops.variety_key). */
    public const GENERAL_VARIETY_KEY = 'general';

    /**
     * Every library crop, in file order.
     *
     * @return array<int, array<string, mixed>>
     */
    public function all(): array
    {
        return config('crop_library.crops', []);
    }

    /**
     * @return array<string, mixed>
     */
    public function meta(): array
    {
        return config('crop_library.meta', []);
    }

    /** Human-readable source line ("Sorsogon, Bicol Region ..."). */
    public function source(): string
    {
        return (string) ($this->meta()['source'] ?? 'Sorsogon, Philippines');
    }

    /**
     * Attribution for a crop's numbers: the entry's own `source` override, or
     * the category-level source from meta.sources_by_category. This is what the
     * New Crop Batch hints cite so an estimate never reads as an opinion.
     */
    public function sourceFor(?string $cropName): string
    {
        $entry = $this->forName($cropName);

        return $entry === null ? $this->source() : $this->sourceForEntry($entry);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    public function sourceForEntry(array $entry): string
    {
        if (! empty($entry['source'])) {
            return (string) $entry['source'];
        }

        $category = $this->categorySource($entry);

        return is_array($category) ? (string) ($category['text'] ?? $this->source()) : (string) $category;
    }

    /**
     * Official page to open for a crop's citation (opens in a new tab in the
     * New Crop Batch hints). Falls back to meta.source_url, then the entry's
     * own `source_url`, then the category link.
     */
    public function sourceUrlFor(?string $cropName): string
    {
        $entry = $this->forName($cropName);

        return $entry === null ? $this->defaultSourceUrl() : $this->sourceUrlForEntry($entry);
    }

    /**
     * @param  array<string, mixed>  $entry
     */
    public function sourceUrlForEntry(array $entry): string
    {
        if (! empty($entry['source_url'])) {
            return (string) $entry['source_url'];
        }

        $category = $this->categorySource($entry);
        if (is_array($category) && ! empty($category['url'])) {
            return (string) $category['url'];
        }

        return $this->defaultSourceUrl();
    }

    /** meta.sources_by_category[category] as stored (string or {text, url}). */
    private function categorySource(array $entry): mixed
    {
        $byCategory = (array) ($this->meta()['sources_by_category'] ?? []);
        $category = (string) ($entry['category'] ?? '');

        return $byCategory[$category] ?? $this->source();
    }

    public function defaultSourceUrl(): string
    {
        return (string) ($this->meta()['source_url'] ?? 'https://buplant.da.gov.ph/production-guide/');
    }

    /**
     * Normalise a crop name for matching: lowercase, punctuation and spaces
     * removed, so "Gabi / Taro", "gabi taro" and "TARO" (an alias) all land on
     * the same entry.
     */
    public function key(string $name): string
    {
        return preg_replace('/[^\p{L}\p{N}]+/u', '', mb_strtolower(trim($name)))
            ?? mb_strtolower(trim($name));
    }

    /**
     * Library crops keyed by normalised name (name + aliases both point at the
     * entry's primary key).
     *
     * @return array<string, array<string, mixed>>
     */
    public function keyed(): array
    {
        $byKey = [];
        foreach ($this->all() as $entry) {
            if (empty($entry['name'])) {
                continue;
            }
            $key = $this->key($entry['name']);
            $byKey[$key] = $entry;

            foreach ((array) ($entry['aliases'] ?? []) as $alias) {
                $aliasKey = $this->key((string) $alias);
                if ($aliasKey !== '') {
                    $byKey[$aliasKey] = $entry;
                }
            }
        }

        return $byKey;
    }

    /**
     * Look a crop up by name or alias, case- and punctuation-insensitively.
     *
     * @return array<string, mixed>|null
     */
    public function forName(?string $name): ?array
    {
        if ($name === null || trim($name) === '') {
            return null;
        }

        return $this->keyed()[$this->key($name)] ?? null;
    }

    /**
     * Every crop name in the library, in file order.
     *
     * @return array<int, string>
     */
    public function names(): array
    {
        return array_values(array_filter(array_map(
            fn ($entry) => (string) ($entry['name'] ?? ''),
            $this->all()
        )));
    }

    /**
     * Variety options for a crop. The universal "Local / General variety"
     * default always comes first, so the Add Crop form can preselect it.
     * Unknown crops get only the default.
     *
     * @return array<int, array<string, mixed>>
     */
    public function varietiesFor(?string $cropName): array
    {
        $options = [[
            'key' => self::GENERAL_VARIETY_KEY,
            'name' => self::GENERAL_VARIETY,
            'note' => 'No certified or improved variety — local planting material.',
            'maturity_days' => null,
            'yield_kg_ha' => null,
        ]];

        $entry = $this->forName($cropName);
        if ($entry === null) {
            return $options;
        }

        foreach ((array) ($entry['varieties'] ?? []) as $variety) {
            if (empty($variety['name'])) {
                continue;
            }
            $options[] = [
                'key' => (string) ($variety['key'] ?? $this->key($variety['name'])),
                'name' => (string) $variety['name'],
                'note' => $variety['note'] ?? null,
                'maturity_days' => isset($variety['maturity_days']) ? (int) $variety['maturity_days'] : null,
                'yield_kg_ha' => isset($variety['yield_kg_ha']) ? (float) $variety['yield_kg_ha'] : null,
            ];
        }

        return $options;
    }

    /**
     * Display names allowed as the `variety` field for a crop (for validation).
     *
     * @return array<int, string>
     */
    public function varietyNamesFor(?string $cropName): array
    {
        return array_column($this->varietiesFor($cropName), 'name');
    }

    /**
     * One variety option by its key, or null when the crop/key is unknown.
     *
     * @return array<string, mixed>|null
     */
    public function varietyByKey(?string $cropName, ?string $varietyKey): ?array
    {
        if ($varietyKey === null || $varietyKey === '') {
            return null;
        }

        foreach ($this->varietiesFor($cropName) as $option) {
            if ($option['key'] === $varietyKey) {
                return $option;
            }
        }

        return null;
    }

    /**
     * Crop Directory rows: the old hardcoded catalog shape (name, category,
     * image, featured, planting_method, days_to_harvest, best_season, soil_type,
     * water_needs, description) plus the agronomy and varieties the page now
     * renders.
     *
     * @return array<int, array<string, mixed>>
     */
    public function directoryCrops(): array
    {
        $rows = [];

        foreach ($this->all() as $entry) {
            $varieties = array_column(
                (array) ($entry['varieties'] ?? []),
                'name'
            );

            $searchParts = array_merge(
                [(string) $entry['name']],
                (array) ($entry['aliases'] ?? []),
                [$entry['description'] ?? ''],
                [$entry['scientific_name'] ?? ''],
                $varieties
            );

            $rows[] = [
                'name' => $entry['name'],
                'category' => $entry['group'] ?? 'vegetable',
                'image' => $entry['image'] ?? null,
                'featured' => (bool) ($entry['featured'] ?? false),
                'planting_method' => $entry['growing']['method'] ?? null,
                'days_to_harvest' => $entry['harvest']['days'] ?? ($entry['maturity_days'] ? $entry['maturity_days'].' days' : null),
                'best_season' => $this->seasonLabel((array) ($entry['seasons'] ?? [])),
                'planting_window' => $entry['planting_window'] ?? null,
                'soil_type' => $entry['growing']['soil'] ?? null,
                'water_needs' => $entry['growing']['water'] ?? null,
                'spacing' => $entry['growing']['spacing'] ?? null,
                'description' => $entry['description'] ?? null,
                'scientific_name' => $entry['scientific_name'] ?? null,
                'varieties' => $varieties,
                'search' => mb_strtolower(implode(' ', array_filter($searchParts))),
            ];
        }

        return $rows;
    }

    /**
     * Small agronomy block for the New Crop Batch modal info panel.
     *
     * @return array<string, mixed>|null
     */
    public function infoFor(?string $cropName): ?array
    {
        $entry = $this->forName($cropName);
        if ($entry === null) {
            return null;
        }

        return [
            'name' => $entry['name'],
            'days_to_harvest' => $entry['harvest']['days'] ?? null,
            'harvest_note' => $entry['harvest']['note'] ?? null,
            'maturity_days' => isset($entry['maturity_days']) ? (int) $entry['maturity_days'] : null,
            'planting_window' => $entry['planting_window'] ?? null,
            'spacing' => $entry['growing']['spacing'] ?? null,
            'method' => $entry['growing']['method'] ?? null,
            'soil' => $entry['growing']['soil'] ?? null,
            'water' => $entry['growing']['water'] ?? null,
            'yield_per_ha' => isset($entry['default_yield_per_ha']) ? (float) $entry['default_yield_per_ha'] : null,
            'yield_unit' => $entry['yield_unit'] ?? config('crop_library.defaults.yield_unit', 'kg'),
            'best_season' => $this->seasonLabel((array) ($entry['seasons'] ?? [])),
            'source' => $this->sourceForEntry($entry),
            'source_url' => $this->sourceUrlForEntry($entry),
        ];
    }

    /** "Wet & Dry" / "Wet season (Jun–Jan)" / "Dry season (Feb–May)". */
    public function seasonLabel(array $seasons): string
    {
        $seasons = array_values(array_intersect(['wet', 'dry'], $seasons));

        return match (true) {
            count($seasons) === 2 => 'Wet & Dry',
            $seasons === ['wet'] => 'Wet season (Jun–Jan)',
            $seasons === ['dry'] => 'Dry season (Feb–May)',
            default => 'Year-round',
        };
    }
}
