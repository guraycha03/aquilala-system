<?php

namespace App\Http\Controllers;

use App\Models\ActivityLog;
use App\Models\HarvestReport;
use App\Models\ImportUpload;
use App\Services\ActivityLogger;
use App\Support\ImportUploadVault;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Storage;

/**
 * The archive of files officers submitted to the bulk importers.
 *
 * Every confirmed import keeps its original upload — spreadsheets for the
 * members list, the photographed forms read by OCR, and the historical
 * harvest files — so the association can still open the exact file a list
 * was built from years later. The two importers keep separate lists: the
 * Members page shows member files only, the Harvest Reports page shows
 * harvest files only.
 */
class AdminImportUploadController extends Controller
{
    /** Files submitted to the members importer: spreadsheets and OCR scans. */
    public function members()
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        return view('admin.imports.uploads', $this->viewData(
            ImportUpload::KIND_MEMBERS,
            'Import Uploads',
            'Every spreadsheet and scanned form submitted to the members importer is kept here — the exact file the members list was built from.',
            'admin.members.index',
            'Back to Members'
        ));
    }

    /** Files submitted to the harvest importer. */
    public function harvest()
    {
        abort_unless(auth()->user()?->role === 'admin', 403);

        return view('admin.imports.uploads', $this->viewData(
            ImportUpload::KIND_HARVEST,
            'Harvest Import Uploads',
            'Every historical harvest file submitted to the importer is kept here — the exact file the harvest reports were built from.',
            'admin.harvests.reports',
            'Back to Harvest Reports'
        ));
    }

    public function downloadMembers(ImportUpload $importUpload)
    {
        return $this->download($importUpload, ImportUpload::KIND_MEMBERS);
    }

    public function downloadHarvest(ImportUpload $importUpload)
    {
        return $this->download($importUpload, ImportUpload::KIND_HARVEST);
    }

    public function previewMembers(ImportUpload $importUpload)
    {
        return $this->preview($importUpload, ImportUpload::KIND_MEMBERS);
    }

    public function previewHarvest(ImportUpload $importUpload)
    {
        return $this->preview($importUpload, ImportUpload::KIND_HARVEST);
    }

    /**
     * "Remove records" — the file-level half of removing wrong data.
     *
     * Takes every harvest record still linked to this upload, i.e. exactly
     * what the wrong file put on the list, and nothing else. The file itself
     * stays archived here, so the officer can fix the spreadsheet and
     * re-import; planting batches linked to a removed record survive (the
     * foreign key nulls itself) and become reportable again. Members files
     * are rejected outright — member records are not managed from here.
     */
    public function removeHarvestRecords(ImportUpload $importUpload): RedirectResponse
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless($importUpload->kind === ImportUpload::KIND_HARVEST, 404);

        $removed = (int) $importUpload->harvestReports()->withTrashed()->forceDelete();

        if ($removed > 0) {
            ActivityLogger::log(
                ActivityLog::TYPE_HARVEST_DELETED,
                'Removed ' . $removed . ' harvest record' . ($removed > 1 ? 's' : '') . ' imported from ' . $importUpload->original_name,
                null,
                ['count' => $removed, 'upload' => $importUpload->id, 'filename' => $importUpload->original_name]
            );
        }

        return redirect()
            ->route('admin.harvests.uploads')
            ->with(
                'success',
                $removed > 0
                    ? number_format($removed) . ' record' . ($removed > 1 ? 's' : '') . ' removed from Harvest Reports. The file stays in Uploads.'
                    : 'No records from this file are on the list anymore.'
            );
    }

    /**
     * Stream an archived file back. Each section only serves its own kind,
     * so a members URL can never hand out a harvest file (or vice versa),
     * and a file that has been removed from disk 404s instead of lying
     * about being there.
     */
    private function download(ImportUpload $importUpload, string $kind)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless($importUpload->kind === $kind, 404);
        abort_unless($importUpload->isDownloadable(), 404);

        return Storage::disk(ImportUploadVault::DISK)
            ->download($importUpload->stored_path, $importUpload->original_name);
    }

    /**
     * Stream an archived scan back as something the page *renders* — this is
     * where the in-system image viewer points its <img>. Same guards as
     * download(), plus "actually a picture": spreadsheets have no preview,
     * because opening one on this device is a download anyway.
     */
    private function preview(ImportUpload $importUpload, string $kind)
    {
        abort_unless(auth()->user()?->role === 'admin', 403);
        abort_unless($importUpload->kind === $kind, 404);
        abort_unless($importUpload->isDownloadable(), 404);
        abort_unless($importUpload->isImage(), 404);

        return Storage::disk(ImportUploadVault::DISK)
            ->response($importUpload->stored_path, $importUpload->original_name);
    }

    /**
     * Newest first, uploader eager-loaded for the "by" column.
     *
     * @return array<string, mixed>
     */
    private function viewData(string $kind, string $title, string $subtitle, string $backRoute, string $backLabel): array
    {
        return [
            'uploads' => ImportUpload::query()
                ->where('kind', $kind)
                ->with('user')
                // How many of this file's records are still on the list —
                // what a "Remove records" click would actually take. Zero
                // (already removed, or a members file) hides the button.
                ->withCount(['harvestReports as records_present'])
                ->latest()
                ->get(),
            'kind' => $kind,
            'title' => $title,
            'subtitle' => $subtitle,
            'backRoute' => $backRoute,
            'backLabel' => $backLabel,
            'downloadRoute' => $kind === ImportUpload::KIND_MEMBERS
                ? 'admin.members.uploads.download'
                : 'admin.harvests.uploads.download',
            'previewRoute' => $kind === ImportUpload::KIND_MEMBERS
                ? 'admin.members.uploads.preview'
                : 'admin.harvests.uploads.preview',
        ];
    }
}
