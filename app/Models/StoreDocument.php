<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One legal paper filed against a store.
 *
 * Documents are added one at a time: the administrator picks a type from the
 * dropdown and uploads that single file. There are no fixed slots to fill and
 * nothing is mandatory up front, so a store can be registered before all of
 * its paperwork has arrived.
 */
class StoreDocument extends Model
{
    /**
     * The dropdown. Kept in PHP rather than a lookup table so the wording can
     * be reviewed alongside the feature, and so a type is never invented by
     * whatever was posted.
     */
    public const TYPES = [
        'Mayor\'s Permit / Business Permit',
        'DTI or SEC Certificate of Registration',
        'BIR Certificate of Registration',
        'Barangay Clearance',
        'Valid Government-issued ID',
        'Proof of Business Address',
        'Sanitary / Health Permit',
        'Fire Safety Inspection Certificate',
        'Other',
    ];

    protected $fillable = [
        'user_id',
        'document_type',
        'original_name',
        'path',
        'mime_type',
        'size_bytes',
        'uploaded_by',
        'notes',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
    ];

    public function store(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function uploader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    public static function isValidType(string $type): bool
    {
        return in_array($type, self::TYPES, true);
    }

    /**
     * Human file size for the paperwork list.
     */
    public function sizeLabel(): string
    {
        if ($this->size_bytes === null) {
            return '';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $size = (float) $this->size_bytes;

        for ($i = 0; $i < count($units) - 1 && $size >= 1024; $i++) {
            $size /= 1024;
        }

        return round($size, $size >= 10 || $i === 0 ? 0 : 1).' '.$units[$i];
    }
}
