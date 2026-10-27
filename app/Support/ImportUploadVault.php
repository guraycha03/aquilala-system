<?php

namespace App\Support;

use App\Models\ImportUpload;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Throwable;

/**
 * Keeps the actual bytes of every confirmed bulk import.
 *
 * The importers are two-phase: the file arrives with the *preview* request,
 * the rows are committed in a later *confirm* request. PHP hands the upload
 * over as a temp file that dies with the preview request, so the bytes have
 * to be parked somewhere between the two phases — that somewhere is the
 * staging directory on the private disk.
 *
 *   stage()   preview  -> copies the upload into imports/staging/
 *   archive() confirm  -> moves it to imports/{kind}/{Ym}/ and writes the
 *                         ImportUpload row (the permanent record)
 *   discard() cancel   -> deletes the staged copy, archives nothing
 *
 * A preview that is cancelled, or an import session left to expire with the
 * browser open, must not leak files forever, so every stage() also sweeps
 * staging for anything that outlived the 60-minute import session by more
 * than a few hours.
 */
class ImportUploadVault
{
    /** Private disk: never served over the web, only streamed by an admin. */
    public const DISK = 'local';

    private const STAGING_DIR = 'imports/staging';

    /** Staged files older than this are leftovers of an expired session. */
    private const STALE_AFTER_SECONDS = 21600; // 6h — the session itself lives 60 minutes

    /**
     * Park the freshly uploaded file until the import is confirmed.
     * Returns the staged path, or null when the file could not be stored —
     * the import itself must keep working either way, so archiving simply
     * ends up skipped for this upload.
     */
    public static function stage(UploadedFile $file): ?string
    {
        self::sweep();

        $name = date('Ymd_His').'_'.Str::random(6).'_'.self::safeName($file->getClientOriginalName());

        $path = $file->storeAs(self::STAGING_DIR, $name, self::DISK);

        return $path === false || $path === null ? null : $path;
    }

    /**
     * Move a staged file into its permanent home and record it. Returns the
     * ImportUpload, or null when nothing was staged (e.g. an import session
     * created before archiving existed) — the rows are still imported, only
     * the file history is missing.
     *
     * @param  array{total?: int, imported?: int, skipped?: int}  $counts
     */
    public static function archive(
        ?string $stagedPath,
        string $kind,
        string $source,
        string $originalName,
        array $counts = []
    ): ?ImportUpload {
        if ($stagedPath === null || $stagedPath === '') {
            return null;
        }

        $disk = Storage::disk(self::DISK);

        if (! $disk->exists($stagedPath)) {
            return null;
        }

        $target = sprintf(
            'imports/%s/%s/%s',
            $kind,
            date('Ym'),
            date('Ymd_His').'_'.Str::random(6).'_'.self::safeName($originalName)
        );

        if (! $disk->move($stagedPath, $target)) {
            return null;
        }

        return ImportUpload::create([
            'user_id' => auth()->id(),
            'kind' => $kind,
            'source' => $source,
            'original_name' => $originalName,
            'stored_path' => $target,
            'size_bytes' => $disk->exists($target) ? (int) $disk->size($target) : 0,
            'rows_total' => (int) ($counts['total'] ?? 0),
            'rows_imported' => (int) ($counts['imported'] ?? 0),
            'rows_skipped' => (int) ($counts['skipped'] ?? 0),
        ]);
    }

    /** Throw away a staged file — used when the officer cancels the import. */
    public static function discard(?string $stagedPath): void
    {
        if ($stagedPath === null || $stagedPath === '') {
            return;
        }

        $disk = Storage::disk(self::DISK);

        if ($disk->exists($stagedPath)) {
            $disk->delete($stagedPath);
        }
    }

    /** Remove staged files whose import session expired without confirm or cancel. */
    private static function sweep(): void
    {
        try {
            $disk = Storage::disk(self::DISK);
            $cutoff = time() - self::STALE_AFTER_SECONDS;

            foreach ($disk->files(self::STAGING_DIR) as $path) {
                if ($disk->lastModified($path) < $cutoff) {
                    $disk->delete($path);
                }
            }
        } catch (Throwable) {
            // A sweep failure must never block a new preview.
        }
    }

    /**
     * Keep the officer's own filename (it is what they will recognize in the
     * list years later) but strip anything that could escape the directory
     * or confuse the shell, and cap the length so the path stays sane.
     */
    private static function safeName(string $name): string
    {
        $name = basename(str_replace('\\', '/', $name));
        $name = preg_replace('/[^A-Za-z0-9._-]+/', '_', $name) ?? '';
        $name = trim($name, '._-');

        if ($name === '') {
            return 'upload';
        }

        return Str::limit($name, 120, '');
    }
}
