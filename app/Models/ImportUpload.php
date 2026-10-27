<?php

namespace App\Models;

use App\Support\ImportUploadVault;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Storage;

/**
 * One file submitted to a bulk importer, archived when the import was
 * confirmed.
 *
 * The importers are two-phase — the file arrives with the preview, the rows
 * are committed in a later request — so this row is only written at confirm
 * time, together with the move out of staging. It exists so the association
 * can still open the exact file it imported from long after the import
 * session is gone.
 */
class ImportUpload extends Model
{
    /** Which importer accepted the file. Drives which list it appears on. */
    public const KIND_MEMBERS = 'members';

    public const KIND_HARVEST = 'harvest';

    /** Structured spreadsheet vs. a photographed form read by OCR. */
    public const SOURCE_FILE = 'file';

    public const SOURCE_OCR = 'ocr';

    protected $fillable = [
        'user_id',
        'kind',
        'source',
        'original_name',
        'stored_path',
        'size_bytes',
        'rows_total',
        'rows_imported',
        'rows_skipped',
    ];

    protected $casts = [
        'size_bytes' => 'integer',
        'rows_total' => 'integer',
        'rows_imported' => 'integer',
        'rows_skipped' => 'integer',
    ];

    /** The officer who submitted the file. */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    /**
     * Harvest records this file imported and that are still on the list —
     * what the "Remove records" action on a wrong upload actually takes.
     * Always empty for members uploads: they link to members, not reports.
     */
    public function harvestReports(): HasMany
    {
        return $this->hasMany(HarvestReport::class, 'import_upload_id');
    }

    /**
     * False when the file itself is gone (removed from disk by hand) — the
     * list still shows the row, just without a download.
     */
    public function isDownloadable(): bool
    {
        return $this->stored_path !== ''
            && Storage::disk(ImportUploadVault::DISK)->exists($this->stored_path);
    }

    /**
     * Scanned forms are pictures, so they can be rendered inside the page's
     * viewer; everything else is a spreadsheet, and opening one means handing
     * the file to the device's own spreadsheet app (a download).
     */
    public function isImage(): bool
    {
        $ext = strtolower(pathinfo((string) $this->stored_path, PATHINFO_EXTENSION));

        return in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp', 'bmp'], true);
    }

    public function sourceLabel(): string
    {
        return $this->source === self::SOURCE_OCR ? 'Scanned image' : 'Spreadsheet';
    }

    /** Human file size for the uploads list. */
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

    /** "48 of 50 rows imported · 1 duplicate skipped" for the list. */
    public function outcomeLabel(): string
    {
        $parts = [number_format($this->rows_imported).' of '.number_format($this->rows_total).' rows imported'];

        if ($this->rows_skipped > 0) {
            $parts[] = number_format($this->rows_skipped).' duplicate'.($this->rows_skipped > 1 ? 's' : '').' skipped';
        }

        return implode(' · ', $parts);
    }
}
