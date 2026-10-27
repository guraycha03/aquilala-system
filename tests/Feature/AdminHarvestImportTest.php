<?php

namespace Tests\Feature;

use App\Models\Crop;
use App\Models\HarvestReport;
use App\Models\Member;
use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

/**
 * Harvest files land on the members list, not the accounts table.
 *
 * The officers load years of paper records before anybody has a login, so
 * every test here asserts that imported reports carry `member_id` with
 * `user_id` null, that names off the members list fail into review instead
 * of importing, and that `users` never grows beyond the admin running it.
 */
class AdminHarvestImportTest extends TestCase
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
            'email' => strtolower($role).'_'.uniqid().'@example.com',
            'role' => $role,
            'password' => 'password123',
        ]);
    }

    /** A member as the members importer files them: composed name stored as-is. */
    private function makeMember(string $name): Member
    {
        return Member::create(['name' => $name]);
    }

    private function csv(string $rows): UploadedFile
    {
        $header = 'Member Name,Crop,Season,Land Area (ha),Actual Yield (MT),Actual Expenses (PHP),Harvest Date';

        return UploadedFile::fake()->createWithContent('harvest-reports.csv', $header."\n".$rows);
    }

    /** Same file with the optional Seed Variety column the registry validates. */
    private function varietyCsv(string $rows): UploadedFile
    {
        $header = 'Member Name,Crop,Seed Variety,Season,Land Area (ha),Actual Yield (MT),Actual Expenses (PHP),Harvest Date';

        return UploadedFile::fake()->createWithContent('harvest-reports.csv', $header."\n".$rows);
    }

    private function preview(UploadedFile $file)
    {
        $admin = $this->makeUser('admin');

        return $this->actingAs($admin)->post(route('admin.harvests.import.preview'), ['file' => $file]);
    }

    private function confirm()
    {
        return $this->post(route('admin.harvests.import.confirm'), [
            'token' => session('admin.harvest_import.token'),
        ]);
    }

    public function test_csv_preview_counts_ready_rows_and_failed_names(): void
    {
        $this->makeMember('Juan C. Reyes');

        $response = $this->preview($this->csv(
            "Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15\n".
            'Juan C. Reyse,Rice,Wet,0.80,3.10,25000,2024-11-20'
        ));

        $response->assertOk();

        $import = session('admin.harvest_import');
        $this->assertNotNull($import);
        $this->assertSame(1, $import['valid']);
        $this->assertSame(1, $import['errors']);

        $failed = collect($import['rows'])->first(fn (array $row) => $row['issues'] !== []);
        $this->assertNotNull($failed);
        $this->assertStringContainsString('Did you mean "Juan C. Reyes"?', implode(' ', $failed['issues']));
        // The typed name stays visible so the officer can fix the file.
        $this->assertSame('Juan C. Reyse', $failed['data']['member']);

        // Nothing has been written by previewing.
        $this->assertDatabaseCount('harvest_reports', 0);
    }

    public function test_confirm_files_reports_against_members_without_accounts(): void
    {
        $member = $this->makeMember('Juan C. Reyes');

        $this->preview($this->csv("Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15"));

        $this->confirm()
            ->assertRedirect(route('admin.harvests.reports'))
            ->assertSessionHas('success');

        $report = HarvestReport::sole();
        $this->assertSame($member->id, $report->member_id);
        $this->assertNull($report->user_id);
        $this->assertSame('Rice', $report->crop_type);
        $this->assertSame('wet', $report->season);
        $this->assertSame(1.2, (float) $report->land_area);
        $this->assertSame(4.74, (float) $report->actual_yield_metric_tons);
        $this->assertSame('2024-11-15', $report->harvest_date->toDateString());

        // The administrator who ran the import is still the only account.
        $this->assertDatabaseCount('users', 1);
        $this->assertNull(session('admin.harvest_import'));

        // One summary entry in the audit trail.
        $this->assertDatabaseHas('activity_logs', ['type' => 'harvest.imported']);
    }

    public function test_unknown_member_is_never_imported(): void
    {
        $this->makeMember('Juan C. Reyes');

        $this->preview($this->csv(
            "Totally Unknown,Rice,Wet,1.20,4.74,38400,2024-11-15\n".
            "Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15"
        ));

        $this->assertSame(1, session('admin.harvest_import.valid'));
        $this->assertSame(1, session('admin.harvest_import.errors'));

        $this->confirm();

        $this->assertDatabaseCount('harvest_reports', 1);
        $this->assertNull(HarvestReport::sole()->user_id);
    }

    public function test_bad_season_future_date_and_unknown_crop_fail_the_row(): void
    {
        $this->makeMember('Juan C. Reyes');
        // With a crop directory present, crops are validated against it.
        Crop::create(['name' => 'Rice']);

        $nextYear = date('Y-m-d', strtotime('+1 year'));

        $this->preview($this->csv(
            "Juan C. Reyes,Rainy Season,Wet,1.20,4.74,38400,2024-11-15\n".
            "Juan C. Reyes,Rice,Rainy Season,1.20,4.74,38400,2024-11-15\n".
            "Juan C. Reyes,Rice,Wet,1.20,4.74,38400,{$nextYear}\n".
            'Juan C. Reyes,Beets,Wet,1.20,4.74,38400,2024-11-15'
        ));

        $import = session('admin.harvest_import');
        $this->assertSame(0, $import['valid']);
        $this->assertSame(4, $import['errors']);

        $issues = implode(' ', array_merge(...array_column($import['rows'], 'issues')));
        $this->assertStringContainsString('Unknown crop "Beets"', $issues);
        $this->assertStringContainsString('Season must be Wet or Dry', $issues);
        $this->assertStringContainsString('Harvest date cannot be in the future', $issues);

        $this->confirm();
        $this->assertDatabaseCount('harvest_reports', 0);
    }

    public function test_duplicate_member_crop_date_in_file_is_flagged(): void
    {
        $this->makeMember('Juan C. Reyes');

        $this->preview($this->csv(
            "Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15\n".
            "Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15"
        ));

        $import = session('admin.harvest_import');
        $this->assertSame(1, $import['valid']);
        $this->assertSame(1, $import['errors']);
        $this->assertStringContainsString('appears earlier in the file', implode(' ', $import['rows'][1]['issues']));

        $this->confirm();
        $this->assertDatabaseCount('harvest_reports', 1);
    }

    public function test_row_already_on_the_list_is_flagged(): void
    {
        $member = $this->makeMember('Juan C. Reyes');

        HarvestReport::create([
            'user_id' => null,
            'member_id' => $member->id,
            'crop_type' => 'Rice',
            'season' => 'wet',
            'land_area' => 1.20,
            'actual_yield_metric_tons' => 4.74,
            'actual_total_expenses' => 38400,
            'harvest_date' => '2024-11-15',
        ]);

        $this->preview($this->csv('Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15'));

        $import = session('admin.harvest_import');
        $this->assertSame(0, $import['valid']);
        $this->assertStringContainsString(
            'already has that crop reported on that date',
            implode(' ', $import['rows'][0]['issues'])
        );

        $this->assertDatabaseCount('harvest_reports', 1);
    }

    public function test_xlsx_upload_imports_reports(): void
    {
        $member = $this->makeMember('Juan C. Reyes');
        $admin = $this->makeUser('admin');

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['Member Name', 'Crop', 'Season', 'Land Area (ha)', 'Actual Yield (MT)', 'Actual Expenses (PHP)', 'Harvest Date'],
            ['juan c. reyes', 'Rice', 'Dry', '1.10', '5.02', '37400', '2025-04-18'],
        ]);

        $temporary = tempnam(sys_get_temp_dir(), 'aquilala_test_').'.xlsx';
        (new Xlsx($spreadsheet))->save($temporary);
        $spreadsheet->disconnectWorksheets();

        $file = UploadedFile::fake()->createWithContent('harvest-reports.xlsx', (string) file_get_contents($temporary));
        @unlink($temporary);

        $this->actingAs($admin)
            ->post(route('admin.harvests.import.preview'), ['file' => $file])
            ->assertOk();

        $import = session('admin.harvest_import');
        $this->assertSame(1, $import['valid']);

        $this->confirm()->assertRedirect(route('admin.harvests.reports'));

        $report = HarvestReport::sole();
        $this->assertSame($member->id, $report->member_id);
        $this->assertNull($report->user_id);
        $this->assertSame('dry', $report->season);
        $this->assertSame('2025-04-18', $report->harvest_date->toDateString());
        // No Seed Variety column in the file → nothing invented for it.
        $this->assertNull($report->variety);
        $this->assertNull($report->variety_key);
    }

    /**
     * The Seed Variety column carries the registry's own labels: whatever
     * spelling the file typed, the row stores the crop library's spelling
     * and its stable key — the same pair a planted batch stores.
     */
    public function test_seed_variety_column_imports_with_its_library_key(): void
    {
        $this->makeMember('Juan C. Reyes');

        $this->preview($this->varietyCsv(
            'Juan C. Reyes,Rice,nsic rc 222 (tubigan 18),Wet,1.20,4.74,38400,2024-11-15'
        ))->assertOk();

        $import = session('admin.harvest_import');
        $this->assertSame(1, $import['valid'], implode(' ', $import['rows'][0]['issues'] ?? []));
        $this->assertSame('NSIC Rc 222 (Tubigan 18)', $import['rows'][0]['data']['variety']);
        $this->assertSame('nsic-rc-222', $import['rows'][0]['data']['variety_key']);

        $this->confirm()->assertRedirect(route('admin.harvests.reports'));

        $report = HarvestReport::sole();
        $this->assertSame('NSIC Rc 222 (Tubigan 18)', $report->variety);
        $this->assertSame('nsic-rc-222', $report->variety_key);
    }

    /**
     * A seed label that is not in the registry fails into review with a
     * suggestion instead of quietly inventing a variety.
     */
    public function test_unknown_seed_variety_fails_review_with_a_suggestion(): void
    {
        $this->makeMember('Juan C. Reyes');

        $this->preview($this->varietyCsv(
            'Juan C. Reyes,Rice,NSIC Rc 2222,Wet,1.20,4.74,38400,2024-11-15'
        ))->assertOk();

        $import = session('admin.harvest_import');
        $this->assertSame(0, $import['valid']);

        $issues = implode(' ', $import['rows'][0]['issues']);
        $this->assertStringContainsString('Unknown variety "NSIC Rc 2222" for Rice', $issues);
        $this->assertStringContainsString('Did you mean "NSIC Rc 222 (Tubigan 18)"?', $issues);
        // The typed label stays on the row so the edit table shows it.
        $this->assertSame('NSIC Rc 2222', $import['rows'][0]['data']['variety']);
        $this->assertNull($import['rows'][0]['data']['variety_key']);

        $this->assertDatabaseCount('harvest_reports', 0);
    }

    public function test_the_edit_table_can_fix_a_seed_variety(): void
    {
        $this->makeMember('Juan C. Reyes');

        $this->preview($this->varietyCsv(
            'Juan C. Reyes,Rice,NSIC Rc 9999,Wet,1.20,4.74,38400,2024-11-15'
        ));

        $this->assertSame(0, session('admin.harvest_import.valid'));

        $this->post(route('admin.harvests.import.revalidate'), [
            'token' => session('admin.harvest_import.token'),
            'rows' => [
                0 => [
                    '__row' => 1,
                    'member' => 'Juan C. Reyes',
                    'crop' => 'Rice',
                    'variety' => 'NSIC Rc 18 (Ala)',
                    'season' => 'Wet',
                    'land_area' => '1.20',
                    'yield' => '4.74',
                    'expenses' => '38400',
                    'harvest_date' => '2024-11-15',
                ],
            ],
        ])
            ->assertRedirect(route('admin.harvests.import'))
            ->assertSessionHas('success', 'All 1 row(s) now pass validation.');

        $this->confirm()->assertRedirect(route('admin.harvests.reports'));

        $report = HarvestReport::sole();
        $this->assertSame('NSIC Rc 18 (Ala)', $report->variety);
        $this->assertSame('nsic-rc-18', $report->variety_key);
    }

    public function test_unsupported_file_types_are_rejected(): void
    {
        $admin = $this->makeUser('admin');

        foreach (['harvest-reports.json' => '{"rows":[]}', 'harvest-reports.xls' => "Member Name\nJuan\n"] as $filename => $content) {
            $this->actingAs($admin)
                ->post(route('admin.harvests.import.preview'), [
                    'file' => UploadedFile::fake()->createWithContent($filename, $content),
                ])
                ->assertRedirect();

            $this->assertStringContainsString('Unsupported file type', (string) session('error'), $filename);
            $this->assertNull(session('admin.harvest_import'), $filename);
        }

        $this->assertDatabaseCount('harvest_reports', 0);
    }

    public function test_non_admin_cannot_access_import(): void
    {
        $farmer = $this->makeUser('farmer', 'Juan C. Reyes');

        $this->actingAs($farmer)
            ->get(route('admin.harvests.import'))
            ->assertForbidden();

        $this->actingAs($farmer)
            ->post(route('admin.harvests.import.preview'), ['file' => 'x'])
            ->assertForbidden();

        $this->actingAs($farmer)
            ->post(route('admin.harvests.import.confirm'), ['token' => 'x'])
            ->assertForbidden();

        $this->actingAs($farmer)
            ->post(route('admin.harvests.import.revalidate'), ['rows' => []])
            ->assertForbidden();
    }

    public function test_cancel_clears_import_session(): void
    {
        $this->makeMember('Juan C. Reyes');
        $this->preview($this->csv('Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15'));
        $this->assertNotNull(session('admin.harvest_import'));

        $this->post(route('admin.harvests.import.cancel'))
            ->assertRedirect(route('admin.harvests.import'));

        $this->assertNull(session('admin.harvest_import'));
        $this->assertDatabaseCount('harvest_reports', 0);
    }

    public function test_template_downloads_cover_both_formats(): void
    {
        $admin = $this->makeUser('admin');

        $csv = $this->actingAs($admin)->get(route('admin.harvests.import.template', 'csv'));
        $csv->assertOk();
        $csv->assertDownload('aquilala-harvest-import-template.csv');
        $template = (string) $csv->streamedContent();
        $this->assertStringContainsString('Member Name', $template);
        $this->assertStringContainsString('Seed Variety', $template);

        $xlsx = $this->actingAs($admin)->get(route('admin.harvests.import.template', 'xlsx'));
        $xlsx->assertOk();
        $xlsx->assertDownload('aquilala-harvest-import-template.xlsx');

        $this->actingAs($admin)
            ->get(route('admin.harvests.import.template', 'json'))
            ->assertNotFound();
    }

    public function test_reports_page_renders_member_filed_reports(): void
    {
        $this->makeMember('Juan C. Reyes');
        $this->preview($this->csv('Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15'));
        $this->confirm();

        $admin = $this->makeUser('admin');

        $this->actingAs($admin)
            ->get(route('admin.harvests.reports'))
            ->assertOk()
            ->assertSee('Juan C. Reyes');
    }

    /**
     * The edit affordance belongs on top of every preview, ready or not —
     * a clean table must stay editable just as a failing one is.
     */
    public function test_edit_button_sits_on_top_of_the_preview_table(): void
    {
        $this->makeMember('Juan C. Reyes');

        $response = $this->preview($this->csv('Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15'));

        $response->assertOk()
            ->assertSee('Edit rows')
            ->assertSee('hvRecheckForm')
            ->assertSee('data-hv-cell')
            ->assertSee(route('admin.harvests.import.revalidate'));
    }

    public function test_revalidate_fixes_an_unmatched_name_and_imports_the_row(): void
    {
        $member = $this->makeMember('Juan C. Reyes');

        $this->preview($this->csv('Juan C. Reyse,Rice,Wet,1.20,4.74,38400,2024-11-15'));

        $this->assertSame(0, session('admin.harvest_import.valid'));
        $this->assertSame(1, session('admin.harvest_import.errors'));

        $this->post(route('admin.harvests.import.revalidate'), [
            'token' => session('admin.harvest_import.token'),
            'rows' => [
                0 => [
                    '__row' => 1,
                    'member' => 'Juan C. Reyes',
                    'crop' => 'Rice',
                    'season' => 'Wet',
                    'land_area' => '1.20',
                    'yield' => '4.74',
                    'expenses' => '38400',
                    'harvest_date' => '2024-11-15',
                ],
            ],
        ])
            ->assertRedirect(route('admin.harvests.import'))
            ->assertSessionHas('success', 'All 1 row(s) now pass validation.');

        $row = session('admin.harvest_import.rows.0');
        $this->assertSame([], $row['issues']);
        $this->assertSame($member->id, $row['data']['member_id']);

        $this->confirm()->assertRedirect(route('admin.harvests.reports'));

        $report = HarvestReport::sole();
        $this->assertSame($member->id, $report->member_id);
        $this->assertNull($report->user_id);
    }

    public function test_revalidate_edits_flow_through_to_the_import(): void
    {
        $this->makeMember('Juan C. Reyes');

        $this->preview($this->csv('Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15'));

        $this->post(route('admin.harvests.import.revalidate'), [
            'token' => session('admin.harvest_import.token'),
            'rows' => [
                0 => [
                    '__row' => 1,
                    'member' => 'Juan C. Reyes',
                    'crop' => 'Rice',
                    'season' => 'Dry',
                    'land_area' => '2.00',
                    'yield' => '9.25',
                    'expenses' => '41000',
                    'harvest_date' => '2024-12-01',
                ],
            ],
        ])->assertRedirect(route('admin.harvests.import'));

        $this->confirm();

        $report = HarvestReport::sole();
        $this->assertSame('dry', $report->season);
        $this->assertSame(2.0, (float) $report->land_area);
        $this->assertSame(9.25, (float) $report->actual_yield_metric_tons);
        $this->assertSame(41000.0, (float) $report->actual_total_expenses);
        $this->assertSame('2024-12-01', $report->harvest_date->toDateString());
    }

    public function test_revalidate_keeps_unposted_rows_and_reports_what_still_fails(): void
    {
        $this->makeMember('Juan C. Reyes');

        $this->preview($this->csv(
            "Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15\n".
            'Totally Unknown,Rice,Wet,0.80,3.10,25000,2024-11-20'
        ));

        // Only the first row comes back from the table; the second keeps
        // exactly what the upload staged.
        $this->post(route('admin.harvests.import.revalidate'), [
            'token' => session('admin.harvest_import.token'),
            'rows' => [
                0 => [
                    '__row' => 1,
                    'member' => 'Juan C. Reyes',
                    'crop' => 'Rice',
                    'season' => 'Wet',
                    'land_area' => '1.20',
                    'yield' => '4.74',
                    'expenses' => '38400',
                    'harvest_date' => '2024-11-15',
                ],
            ],
        ])
            ->assertRedirect(route('admin.harvests.import'))
            ->assertSessionHas('success', '1 row(s) still need attention.');

        $import = session('admin.harvest_import');
        $this->assertSame(1, $import['valid']);
        $this->assertSame(1, $import['errors']);
        $this->assertSame('Totally Unknown', $import['rows'][1]['data']['member']);
        $this->assertSame(2, $import['rows'][1]['_row']);
        $this->assertStringContainsString('No member named "Totally Unknown"', implode(' ', $import['rows'][1]['issues']));
    }

    public function test_revalidate_rejects_a_stale_or_foreign_token(): void
    {
        $this->makeMember('Juan C. Reyes');
        $this->preview($this->csv('Juan C. Reyes,Rice,Wet,1.20,4.74,38400,2024-11-15'));
        $import = session('admin.harvest_import');

        $this->post(route('admin.harvests.import.revalidate'), [
            'token' => 'not-the-token',
            'rows' => [0 => ['__row' => 1, 'member' => 'Somebody Else']],
        ])
            ->assertRedirect(route('admin.harvests.import'))
            ->assertSessionHas('error');

        $this->post(route('admin.harvests.import.revalidate'), ['token' => $import['token']])
            ->assertRedirect(route('admin.harvests.import'))
            ->assertSessionHas('error', 'There is nothing to re-check.');

        // The staged preview is untouched by either attempt.
        $this->assertSame($import['valid'], session('admin.harvest_import.valid'));
        $this->assertSame($import['token'], session('admin.harvest_import.token'));
    }
}
