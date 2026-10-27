<?php

namespace Tests\Feature;

use App\Models\ImportUpload;
use App\Models\Member;
use App\Models\User;
use App\Services\OcrService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Every confirmed import keeps the file it was built from.
 *
 * The officers may open the association's books years later, so the archive
 * must hold the exact bytes they uploaded — spreadsheets for the members
 * list, photographed forms read by OCR, and the historical harvest files.
 * These tests pin the lifecycle down: staged at preview, archived only at
 * confirm, discarded at cancel, and each importer keeping its own list.
 */
class AdminImportUploadsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();
        // The import vault parks uploaded files on this disk — fake it so
        // no test ever writes to (or reads from) real storage.
        Storage::fake('local');
    }

    private function makeUser(string $role, string $name = 'System Admin'): User
    {
        return User::create([
            'name' => $name,
            'first_name' => $name,
            'last_name' => $name,
            'email' => strtolower($role).'_'.uniqid().'@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    private function memberCsv(string $rows): UploadedFile
    {
        $header = 'First Name,Last Name,Email,Phone,Barangay,City,Province';

        return UploadedFile::fake()->createWithContent('members.csv', $header."\n".$rows);
    }

    private function harvestCsv(string $rows): UploadedFile
    {
        $header = 'Member Name,Crop,Season,Land Area (ha),Actual Yield (MT),Actual Expenses (PHP),Harvest Date';

        return UploadedFile::fake()->createWithContent('harvest-reports.csv', $header."\n".$rows);
    }

    /** The photographed member list the OCR tests read from. */
    private function scan(): UploadedFile
    {
        $png = file_get_contents(base_path('tests/Fixtures/ocr-member-list.png'));

        return UploadedFile::fake()->createWithContent('member-list.png', $png);
    }

    /**
     * A row already in the archive, without going through an import.
     *
     * @param  'file'|'ocr'  $source
     */
    private function archivedFile(string $kind, string $name, ?User $user = null, string $source = ImportUpload::SOURCE_FILE, ?string $bytes = null): ImportUpload
    {
        $path = 'imports/'.$kind.'/202610/test_'.$name;

        Storage::disk('local')->put($path, $bytes ?? "First Name,Email\nJuan,juan@example.com\n");

        return ImportUpload::create([
            'user_id' => $user?->id,
            'kind' => $kind,
            'source' => $source,
            'original_name' => $name,
            'stored_path' => $path,
            'size_bytes' => 38,
            'rows_total' => 1,
            'rows_imported' => 1,
            'rows_skipped' => 0,
        ]);
    }

    public function test_confirmed_member_file_is_archived_with_counts_and_download(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('admin.members.import.preview'), [
                'file' => $this->memberCsv(
                    "Juan,Dela Cruz,juan.delacruz@example.com,09171234567,San Isidro,Bulan,Sorsogon\n".
                    'Maria,Santos,maria.santos@example.com,09179876543,Bidbidan,Bulan,Sorsogon'
                ),
            ])
            ->assertOk();

        // Preview only parks the upload — nothing is archived until confirm.
        $this->assertSame(0, ImportUpload::count());

        $this->post(route('admin.members.import.confirm'), ['token' => session('admin.member_import.token')])
            ->assertRedirect(route('admin.members.index'))
            ->assertSessionHas('success');

        $upload = ImportUpload::sole();
        $this->assertSame(ImportUpload::KIND_MEMBERS, $upload->kind);
        $this->assertSame(ImportUpload::SOURCE_FILE, $upload->source);
        $this->assertSame('members.csv', $upload->original_name);
        $this->assertSame($admin->id, $upload->user_id);
        $this->assertSame(2, $upload->rows_total);
        $this->assertSame(2, $upload->rows_imported);
        $this->assertSame(0, $upload->rows_skipped);
        $this->assertGreaterThan(0, $upload->size_bytes);
        $this->assertStringStartsWith('imports/members/', $upload->stored_path);

        // The bytes are on disk where the record says, staging is clear.
        $this->assertTrue(Storage::disk('local')->exists($upload->stored_path));
        $this->assertSame([], Storage::disk('local')->files('imports/staging'));

        $this->get(route('admin.members.uploads'))
            ->assertOk()
            ->assertSee('members.csv');

        $download = $this->get(route('admin.members.uploads.download', $upload));
        $download->assertOk();
        $this->assertStringContainsString(
            'members.csv',
            (string) $download->headers->get('content-disposition')
        );
    }

    public function test_counts_reflect_rows_skipped_after_the_preview(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('admin.members.import.preview'), [
                'file' => $this->memberCsv(
                    "Juan,Dela Cruz,juan.a@example.com,09171111111,San Isidro,Bulan,Sorsogon\n".
                    'Maria,Santos,maria.b@example.com,09172222222,Bidbidan,Bulan,Sorsogon'
                ),
            ])
            ->assertOk();

        $this->assertSame(2, session('admin.member_import.valid'));

        // Somebody adds the first member by hand between preview and confirm —
        // the confirm pass re-checks and skips it instead of duplicating.
        Member::create(['name' => 'Juan Dela Cruz', 'email' => 'juan.a@example.com']);

        $this->post(route('admin.members.import.confirm'), ['token' => session('admin.member_import.token')])
            ->assertSessionHas('success');

        $upload = ImportUpload::sole();
        $this->assertSame(2, $upload->rows_total);
        $this->assertSame(1, $upload->rows_imported);
        $this->assertSame(1, $upload->rows_skipped);
        $this->assertDatabaseCount('members', 2);
    }

    public function test_each_importer_keeps_its_own_list_of_files(): void
    {
        $admin = $this->makeUser('admin');

        $memberFile = $this->archivedFile(ImportUpload::KIND_MEMBERS, 'members-2026.csv', $admin);
        $scan = $this->archivedFile(ImportUpload::KIND_MEMBERS, 'member-list.png', $admin, ImportUpload::SOURCE_OCR);
        $harvestFile = $this->archivedFile(ImportUpload::KIND_HARVEST, 'harvest-reports.csv', $admin);

        // Members list: spreadsheets and scanned forms alike, never harvest files.
        $this->actingAs($admin)->get(route('admin.members.uploads'))
            ->assertOk()
            ->assertSee('members-2026.csv')
            ->assertSee('member-list.png')
            ->assertSee('Scanned image')
            ->assertDontSee('harvest-reports.csv')
            ->assertViewHas('uploads', fn ($uploads) => $uploads->pluck('id')->sort()->values()
                == collect([$memberFile->id, $scan->id])->sort()->values());

        // Harvest list: only the historical files.
        $this->get(route('admin.harvests.uploads'))
            ->assertOk()
            ->assertSee('harvest-reports.csv')
            ->assertDontSee('members-2026.csv')
            ->assertViewHas('uploads', fn ($uploads) => $uploads->pluck('id')->all() === [$harvestFile->id]);
    }

    public function test_download_url_refuses_files_from_the_other_importer(): void
    {
        $admin = $this->makeUser('admin');
        $harvestFile = $this->archivedFile(ImportUpload::KIND_HARVEST, 'harvest-reports.csv', $admin);

        // A members URL must never hand out a harvest file (or vice versa).
        $this->actingAs($admin)
            ->get(route('admin.members.uploads.download', $harvestFile))
            ->assertNotFound();

        $memberFile = $this->archivedFile(ImportUpload::KIND_MEMBERS, 'members.csv', $admin);

        $this->get(route('admin.harvests.uploads.download', $memberFile))
            ->assertNotFound();
    }

    public function test_download_404s_when_the_file_itself_is_gone(): void
    {
        $admin = $this->makeUser('admin');
        $upload = $this->archivedFile(ImportUpload::KIND_MEMBERS, 'members.csv', $admin);

        Storage::disk('local')->delete($upload->stored_path);

        $this->actingAs($admin)
            ->get(route('admin.members.uploads.download', $upload))
            ->assertNotFound();

        // The record stays in the list — it just cannot be handed out.
        $this->get(route('admin.members.uploads'))
            ->assertOk()
            ->assertSee('members.csv')
            ->assertSee('File unavailable');
    }

    public function test_cancelled_preview_archives_nothing_and_leaves_no_staged_file(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('admin.members.import.preview'), [
                'file' => $this->memberCsv('Juan,Dela Cruz,juan@example.com,09171234567,San Isidro,Bulan,Sorsogon'),
            ])
            ->assertOk();

        $this->assertCount(1, Storage::disk('local')->files('imports/staging'));

        $this->post(route('admin.members.import.cancel'))
            ->assertRedirect(route('admin.members.index'));

        // Cancelled means archived nothing, staged nothing, kept nothing.
        $this->assertNull(session('admin.member_import'));
        $this->assertSame(0, ImportUpload::count());
        $this->assertSame([], Storage::disk('local')->files('imports/staging'));
        $this->assertDatabaseCount('members', 0);
    }

    public function test_a_second_preview_replaces_the_previous_staged_file(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('admin.members.import.preview'), [
                'file' => UploadedFile::fake()->createWithContent(
                    'first.csv',
                    "First Name,Last Name,Email,Phone,Barangay,City,Province\n".
                    'Juan,Dela Cruz,juan@example.com,09171234567,San Isidro,Bulan,Sorsogon'
                ),
            ])
            ->assertOk();

        $staged = Storage::disk('local')->files('imports/staging');
        $this->assertCount(1, $staged);
        $this->assertStringContainsString('first.csv', $staged[0]);

        $this->post(route('admin.members.import.preview'), [
            'file' => $this->memberCsv('Maria,Santos,maria@example.com,09179876543,Bidbidan,Bulan,Sorsogon'),
        ])->assertOk();

        // The superseded upload is swept away — only the pending one remains.
        $staged = Storage::disk('local')->files('imports/staging');
        $this->assertCount(1, $staged);
        $this->assertStringContainsString('members.csv', $staged[0]);
        $this->assertSame($staged[0], session('admin.member_import.staged_path'));
    }

    public function test_confirmed_harvest_file_is_archived_on_the_harvest_list(): void
    {
        $admin = $this->makeUser('admin');
        Member::create(['name' => 'Juan C. Reyes']);

        $this->actingAs($admin)
            ->post(route('admin.harvests.import.preview'), [
                'file' => $this->harvestCsv('Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15'),
            ])
            ->assertOk();

        $this->post(route('admin.harvests.import.confirm'), ['token' => session('admin.harvest_import.token')])
            ->assertRedirect(route('admin.harvests.reports'))
            ->assertSessionHas('success');

        $upload = ImportUpload::sole();
        $this->assertSame(ImportUpload::KIND_HARVEST, $upload->kind);
        $this->assertSame(ImportUpload::SOURCE_FILE, $upload->source);
        $this->assertSame('harvest-reports.csv', $upload->original_name);
        $this->assertSame(1, $upload->rows_total);
        $this->assertSame(1, $upload->rows_imported);
        $this->assertSame(0, $upload->rows_skipped);
        $this->assertStringStartsWith('imports/harvest/', $upload->stored_path);
        $this->assertTrue(Storage::disk('local')->exists($upload->stored_path));
        $this->assertSame([], Storage::disk('local')->files('imports/staging'));

        $this->get(route('admin.harvests.uploads'))
            ->assertOk()
            ->assertSee('harvest-reports.csv');

        $this->get(route('admin.members.uploads'))
            ->assertOk()
            ->assertDontSee('harvest-reports.csv');
    }

    public function test_confirmed_scan_is_archived_as_a_scanned_image(): void
    {
        if (! app(OcrService::class)->isAvailable()) {
            $this->markTestSkipped('Tesseract OCR (with language data) is not available on this machine.');
        }

        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->post(route('admin.members.import.ocr'), ['file' => $this->scan()])
            ->assertOk();

        $import = session('admin.member_import');
        $this->assertSame('ocr', $import['source'] ?? null);

        $approved = [];
        foreach ($import['rows'] as $row) {
            if (($row['data']['email'] ?? null) === 'juan@example.com' && $row['issues'] === []) {
                $approved[] = (string) $row['_row'];
            }
        }
        $this->assertNotSame([], $approved, 'The scanned row should be importable without issues.');

        $this->post(route('admin.members.import.ocr.confirm'), [
            'token' => $import['token'],
            'approve' => $approved,
        ])->assertRedirect(route('admin.members.index'));

        // The photographed form itself is archived, not just its rows.
        $upload = ImportUpload::sole();
        $this->assertSame(ImportUpload::KIND_MEMBERS, $upload->kind);
        $this->assertSame(ImportUpload::SOURCE_OCR, $upload->source);
        $this->assertSame('member-list.png', $upload->original_name);
        $this->assertStringStartsWith('imports/members/', $upload->stored_path);
        $this->assertSame(count($approved), $upload->rows_imported);
        $this->assertGreaterThanOrEqual($upload->rows_imported, $upload->rows_total);
        $this->assertTrue(Storage::disk('local')->exists($upload->stored_path));
        $this->assertSame([], Storage::disk('local')->files('imports/staging'));
    }

    public function test_the_import_pages_link_to_their_upload_lists(): void
    {
        $admin = $this->makeUser('admin');

        $this->actingAs($admin)->get(route('admin.members.index'))
            ->assertOk()
            ->assertSee('admin/members/uploads');

        $this->get(route('admin.harvests.reports'))
            ->assertOk()
            ->assertSee('admin/harvests/uploads');
    }

    public function test_upload_lists_and_downloads_require_an_administrator(): void
    {
        $upload = $this->archivedFile(ImportUpload::KIND_MEMBERS, 'members.csv');

        // Guests are sent to login before anything else happens.
        $this->get(route('admin.members.uploads'))->assertRedirect();

        $farmer = $this->makeUser('farmer');

        $this->actingAs($farmer)->get(route('admin.members.uploads'))->assertForbidden();
        $this->actingAs($farmer)->get(route('admin.harvests.uploads'))->assertForbidden();
        $this->actingAs($farmer)->get(route('admin.members.uploads.download', $upload))->assertForbidden();
        $this->actingAs($farmer)->get(route('admin.members.uploads.preview', $upload))->assertForbidden();
    }

    public function test_preview_renders_scans_inline_and_only_from_its_own_list(): void
    {
        $admin = $this->makeUser('admin');
        $png = file_get_contents(base_path('tests/Fixtures/ocr-member-list.png'));
        $scan = $this->archivedFile(ImportUpload::KIND_MEMBERS, 'member-list.png', $admin, ImportUpload::SOURCE_OCR, $png);
        $sheet = $this->archivedFile(ImportUpload::KIND_MEMBERS, 'members.xlsx', $admin);
        $harvestScan = $this->archivedFile(ImportUpload::KIND_HARVEST, 'harvest-form.png', $admin, ImportUpload::SOURCE_OCR, $png);

        // A scan streams back rendered (inline) instead of as an attachment —
        // this is what the in-system viewer points its <img> at.
        $ok = $this->actingAs($admin)->get(route('admin.members.uploads.preview', $scan));
        $ok->assertOk();
        $disposition = (string) $ok->headers->get('content-disposition');
        $this->assertStringContainsString('inline', $disposition);
        $this->assertStringContainsString('member-list.png', $disposition);
        $this->assertStringContainsString('image/png', (string) $ok->headers->get('content-type'));

        // A spreadsheet has no preview: opening one on this device is a download.
        $this->get(route('admin.members.uploads.preview', $sheet))->assertNotFound();

        // Each list only serves its own kind, exactly like the downloads.
        $this->get(route('admin.members.uploads.preview', $harvestScan))->assertNotFound();
        $this->get(route('admin.harvests.uploads.preview', $scan))->assertNotFound();
    }

    public function test_file_column_opens_the_file_and_the_separate_back_button_is_gone(): void
    {
        $admin = $this->makeUser('admin');
        $harvestFile = $this->archivedFile(ImportUpload::KIND_HARVEST, 'harvest-reports.csv', $admin);
        $png = file_get_contents(base_path('tests/Fixtures/ocr-member-list.png'));
        $scan = $this->archivedFile(ImportUpload::KIND_MEMBERS, 'member-list.png', $admin, ImportUpload::SOURCE_OCR, $png);
        $sheet = $this->archivedFile(ImportUpload::KIND_MEMBERS, 'members.xlsx', $admin);

        // Harvest list: the file cell is the open affordance — for a sheet
        // that means a download link — and the separate back button is gone;
        // the breadcrumb above still leads back to the reports.
        $this->actingAs($admin)->get(route('admin.harvests.uploads'))
            ->assertOk()
            ->assertDontSee('top-navigation', false)
            ->assertSee('Back to Harvest Reports')
            ->assertSee('class="file-open"', false)
            ->assertSee('href="'.route('admin.harvests.uploads.download', $harvestFile).'"', false)
            ->assertDontSee(' data-up-preview ', false);   // spaced: the link attribute, not the script's selector

        // Members list: a scan's cell opens the in-page viewer instead of
        // downloading, while its spreadsheet still downloads from the cell.
        $this->get(route('admin.members.uploads'))
            ->assertOk()
            ->assertDontSee('top-navigation', false)
            ->assertSee('Back to Members')
            ->assertSee(' data-up-preview ', false)
            ->assertSee(route('admin.members.uploads.preview', $scan), false)
            ->assertSee('href="'.route('admin.members.uploads.download', $sheet).'"', false);
    }
}
