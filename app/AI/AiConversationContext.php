<?php

namespace App\AI;

use App\Models\User;

/**
 * The short-term conversation state the assistant is allowed to rely on.
 *
 * Deliberately not a transcript. The whole conversation is re-sent on every
 * turn today, which is both expensive and the reason the model keeps
 * re-deriving facts it was already told. Four scalars are enough to resolve
 * "it", "that product" and "the fertilizer you mentioned" without any of that.
 *
 * Serialised to and from the browser so a farmer can reload the page, or open
 * a saved conversation tomorrow, and "how much is it?" still means the same
 * product.
 */
final class AiConversationContext
{
    public function __construct(
        public readonly ?string $lastIntent = null,
        public readonly ?string $lastCrop = null,
        /** @var array<int, int> */
        public readonly array $lastProductIds = [],
        public readonly ?string $lastSeason = null,
        public readonly ?float $lastArea = null,
    ) {
    }

    /**
     * @param  array<string, mixed>|null  $data
     */
    public static function fromArray(?array $data): self
    {
        if ($data === null) {
            return new self();
        }

        $ids = $data['last_product_ids'] ?? [];

        return new self(
            lastIntent: self::stringOrNull($data['last_intent'] ?? null),
            lastCrop: self::stringOrNull($data['last_crop'] ?? null),
            // Only ints survive. Anything else in this field came from the
            // client and is not worth a database round trip.
            lastProductIds: is_array($ids)
                ? array_values(array_unique(array_filter(array_map(
                    static fn ($id) => is_numeric($id) ? (int) $id : null,
                    $ids
                ))))
                : [],
            lastSeason: in_array($data['last_season'] ?? null, ['wet', 'dry'], true) ? $data['last_season'] : null,
            lastArea: isset($data['last_area']) && is_numeric($data['last_area']) ? (float) $data['last_area'] : null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'last_intent'      => $this->lastIntent,
            'last_crop'        => $this->lastCrop,
            'last_product_ids' => $this->lastProductIds,
            'last_season'      => $this->lastSeason,
            'last_area'        => $this->lastArea,
        ];
    }

    public function withResolved(
        ?string $intent = null,
        ?string $crop = null,
        ?array $productIds = null,
        ?string $season = null,
        ?float $area = null,
    ): self {
        return new self(
            lastIntent: $intent ?? $this->lastIntent,
            lastCrop: $crop ?? $this->lastCrop,
            lastProductIds: $productIds ?? $this->lastProductIds,
            lastSeason: $season ?? $this->lastSeason,
            lastArea: $area ?? $this->lastArea,
        );
    }

    /**
     * Ids from the previous turn, minus anything the caller has already
     * resolved this turn.
     *
     * @param  array<int, int>  $exclude
     * @return array<int, int>
     */
    public function productIdsExcept(array $exclude = []): array
    {
        $exclude = array_map('intval', $exclude);

        return array_values(array_diff($this->lastProductIds, $exclude));
    }

    /**
     * The farmer's own crop batches are real, recorded information about their
     * farm, so they may legitimately supply a crop the message did not name.
     * This is the one place a slot may be inferred rather than asked for.
     *
     * @return array<int, array<string, mixed>>
     */
    public static function activeBatchesFor(?User $user): array
    {
        if ($user === null || $user->role !== 'farmer') {
            return [];
        }

        return $user->farmerCrops()
            ->with('crop')
            ->whereNotIn('growth_stage', ['harvested', 'failed'])
            ->orderByDesc('planting_date')
            ->limit(5)
            ->get()
            ->map(fn ($batch) => [
                'crop'          => $batch->crop?->name,
                'season'        => $batch->season,
                'land_area'     => $batch->land_area === null ? null : (float) $batch->land_area,
                'area_unit'     => $batch->land_area_unit,
                'growth_stage'  => $batch->growth_stage,
                'planting_date' => $batch->planting_date?->toDateString(),
            ])
            ->filter(fn ($batch) => $batch['crop'] !== null)
            ->values()
            ->all();
    }

    private static function stringOrNull(mixed $value): ?string
    {
        if (! is_string($value)) {
            return null;
        }

        $value = trim($value);

        return $value === '' ? null : $value;
    }
}
