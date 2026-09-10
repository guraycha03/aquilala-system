<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class RiceVariety extends Model
{
    protected $fillable = [
        'year_approved',
        'registration_number',
        'variety_name',
        'variety_type',
        'maturity_days',
        'target_yield_kg_ha',
        'attributes',
        'data_source',
    ];

    protected $casts = [
        'year_approved' => 'integer',
        'maturity_days' => 'integer',
        'target_yield_kg_ha' => 'decimal:2',
        'attributes' => 'array',
    ];

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        return $query->when($term, function (Builder $q) use ($term) {
            $q->where('variety_name', 'like', "%{$term}%")
                ->orWhere('registration_number', 'like', "%{$term}%");
        });
    }

    public function scopeOfType(Builder $query, ?string $type): Builder
    {
        return $query->when($type, fn (Builder $q) => $q->where('variety_type', $type));
    }

    /**
     * Read a single extra/metadata property from the JSON attributes column.
     * Dot notation is supported, e.g. $variety->attribute('Reaction to Pest.Green Leafhopper').
     */
    public function attribute(string $key, mixed $default = null): mixed
    {
        return data_get($this->getAttribute('attributes'), $key, $default);
    }

    /**
     * Set (or overwrite) one extra property inside the JSON attributes column.
     */
    public function setAttributeData(string $key, mixed $value): self
    {
        $extra = (array) $this->getAttribute('attributes');
        data_set($extra, $key, $value);
        $this->setAttribute('attributes', $extra);

        return $this;
    }

    /** Remove a single extra property from the JSON attributes column. */
    public function forgetAttributeData(string $key): self
    {
        $extra = (array) $this->getAttribute('attributes');
        data_forget($extra, $key);
        $this->setAttribute('attributes', $extra);

        return $this;
    }

    /** Return the list of top-level keys stored in the JSON attributes column. */
    public function attributeKeys(): array
    {
        return array_keys((array) $this->getAttribute('attributes'));
    }

    /** Convenience accessor for pest reaction ratings (usually like array of pest => rating). */
    public function pestReactions(): array
    {
        return (array) ($this->attribute('Reaction to Pest') ?? []);
    }

    /** Convenience accessor for disease reaction ratings. */
    public function diseaseReactions(): array
    {
        return (array) ($this->attribute('Reaction to Diseases') ?? []);
    }
}