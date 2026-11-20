<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

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

    /**
     * URL for cards/lists: the thumbnail first, then the full image, then a
     * local placeholder. Cards must stay on the small copy when it exists —
     * that is the whole point of the thumbnail — but must not render broken
     * images when the underlying files are gone.
     */
    public function getCardUrlAttribute(): string
    {
        if ($this->file_path && preg_match('~^https?://~i', $this->file_path)) {
            return $this->file_path;
        }

        try {
            $disk = Storage::disk('public');

            if ($this->thumb_path && $disk->exists($this->thumb_path) && $disk->size($this->thumb_path) > 0) {
                return $this->thumb_url;
            }

            if ($this->file_path && $disk->exists($this->file_path) && $disk->size($this->file_path) > 0) {
                return $this->url;
            }
        } catch (\Throwable) {
            // Fall through to the placeholder.
        }

        return asset('images/product.png');
    }

    /**
     * URL for the detail-page gallery: the full image when it is really on
     * disk, otherwise the thumbnail the cards already serve. When neither
     * file survived an upload or a server migration, a local placeholder is
     * returned so the page never renders broken-image icons with alt text.
     */
    public function getDisplayUrlAttribute(): string
    {
        // Legacy rows may hold an absolute URL instead of a disk path.
        if ($this->file_path && preg_match('~^https?://~i', $this->file_path)) {
            return $this->file_path;
        }

        try {
            $disk = Storage::disk('public');

            if ($this->file_path && $disk->exists($this->file_path) && $disk->size($this->file_path) > 0) {
                return $this->url;
            }

            if ($this->thumb_path && $disk->exists($this->thumb_path) && $disk->size($this->thumb_path) > 0) {
                return $this->thumb_url;
            }
        } catch (\Throwable) {
            // A misconfigured disk must not turn into a broken page.
        }

        return asset('images/product.png');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}
