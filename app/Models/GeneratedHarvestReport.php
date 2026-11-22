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
    ];

    protected function casts(): array
    {
        return [
            'filters' => 'array',
            'context' => 'array',
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
