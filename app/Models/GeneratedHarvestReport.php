<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GeneratedHarvestReport extends Model
{
    protected $fillable = [
        'user_id',
        'title',
        'filters',
        'context',
        'narrative_en',
        'narrative_tl',
        'default_language',
        'source',
        'model',
        'ai_insights',
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'context' => 'array',
            'ai_insights' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function narrativeFor(string $language): string
    {
        return $language === 'tl' ? $this->narrative_tl : $this->narrative_en;
    }

    /**
     * @return array<string, mixed>
     */
    public function aiInsightFor(string $language): array
    {
        $insights = is_array($this->ai_insights) ? $this->ai_insights : [];

        return is_array($insights[$language] ?? null) ? $insights[$language] : [];
    }

    /**
     * Short display title without the legacy "Actual" prefix or scope suffix.
     * Old saved records can still carry both, so every page normalises here.
     */
    public function displayTitle(): string
    {
        $title = preg_replace('/^Actual\s+/i', '', trim((string) $this->title));
        $title = preg_replace('/\s+(?:—|-)\s+.*$/u', '', $title);

        return trim((string) $title) !== '' ? trim((string) $title) : 'Harvest Report';
    }

    public function downloadBaseName(): string
    {
        $slug = preg_replace('/[^a-z0-9]+/', '-', strtolower($this->title)) ?: 'harvest-report';

        return trim($slug, '-') . '-' . $this->created_at->format('Ymd');
    }

    public function downloadFilename(string $language): string
    {
        return $this->downloadBaseName() . '-' . $language . '.html';
    }
}
