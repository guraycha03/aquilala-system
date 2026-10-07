<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ProductImage extends Model
{
    protected $fillable = [
        'product_id',
        'image_type',
        'file_path',
        'thumb_path',
        'original_filename',
        'mime_type',
        'file_size',
    ];

    /**
     * Full-size image URL (detail pages, lightboxes).
     */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . $this->file_path);
    }

    /**
     * Thumbnail URL for cards/lists — falls back to the full image for rows
     * stored before thumbnails existed, or uploads we deliberately left alone.
     */
    public function getThumbUrlAttribute(): string
    {
        return asset('storage/' . ($this->thumb_path ?: $this->file_path));
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
