<?php

namespace Tests\Feature;

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
 * CSV/XLSX intake lands on the members list — never on the accounts table.
 *
 * The whole point of the split is that a file can add people without giving
 * anybody a login, so every test here asserts against `members` and, where
 * it matters, that `users` has not grown by a single row.
 */
class AdminMemberImportTest extends TestCase
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

    private function csv(string $rows): UploadedFile
    {
        return UploadedFile::fake()->createWithContent('members.csv', "First Name,Last Name,Email,Phone,Barangay,City,Province\n".$rows);
    }

    private function preview(UploadedFile $file)
    {
        $admin = $this->makeUser('admin');

        return $this->actingAs($admin)->post(route('admin.members.import.preview'), ['file' => $file]);
    }

    public function test_csv_preview_shows_parsed_rows(): void
    {
        $response = $this->preview($this->csv("Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon\nMaria,Santos,maria.santos@example.com,09179876543,Impalutao,Impasugong,Bukidnon"));

        $response->assertOk();

        $import = session('admin.member_import');
        $this->assertNotNull($import);
        $this->assertSame(2, $import['valid']);
        $this->assertSame(0, $import['errors']);
    }

    public function test_csv_confirm_puts_people_on_the_roll_without_creating_accounts(): void
    {
        $this->preview($this->csv('Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon'));

        $token = session('admin.member_import.token');

        $this->post(route('admin.members.import.confirm'), ['token' => $token])
            ->assertRedirect(route('admin.members.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('members', [
            'name' => 'Juan Dela Cruz',
            'first_name' => 'Juan',
            'last_name' => 'Dela Cruz',
            'email' => 'juan.delacruz@example.com',
            'phone' => '+639171234567',
            'barangay' => 'Malaybalay',
            'city' => 'Valencia',
            'province' => 'Bukidnon',
            'status' => Member::STATUS_ACTIVE,
            'user_id' => null,
        ]);

        // The administrator who ran the import is still the only account.
        $this->assertDatabaseCount('users', 1);
        $this->assertSame(0, User::where('email', 'juan.delacruz@example.com')->count());

        $this->assertNull(session('admin.member_import'));
    }

    public function test_xlsx_import_creates_members(): void
    {
        $admin = $this->makeUser('admin');

        $spreadsheet = new Spreadsheet;
        $spreadsheet->getActiveSheet()->fromArray([
            ['First Name', 'Last Name', 'Email', 'Phone', 'Barangay'],
            ['Ana', 'Reyes', 'ana.reyes@example.com', '09271234567', 'Malaybalay'],
        ]);

        $temporary = tempnam(sys_get_temp_dir(), 'aquilala_test_').'.xlsx';
        (new Xlsx($spreadsheet))->save($temporary);
        $spreadsheet->disconnectWorksheets();

        $file = UploadedFile::fake()->createWithContent('members.xlsx', (string) file_get_contents($temporary));
        @unlink($temporary);

        $this->actingAs($admin)
            ->post(route('admin.members.import.preview'), ['file' => $file])
            ->assertOk();

        $import = session('admin.member_import');
        $this->assertSame(1, $import['valid']);

        $this->post(route('admin.members.import.confirm'), ['token' => $import['token']])
            ->assertRedirect();

        $this->assertDatabaseHas('members', [
            'email' => 'ana.reyes@example.com',
            'phone' => '+639271234567',
            'barangay' => 'Malaybalay',
            'user_id' => null,
        ]);
        $this->assertDatabaseCount('users', 1);
    }

    /**
     * Only the formats in config/import-formats.php may reach the parser.
     * Legacy .xls and developer-only .json are refused at the door.
     */
    public function test_unsupported_file_types_are_rejected(): void
    {
        $admin = $this->makeUser('admin');

        $payloads = [
            'members.json' => json_encode(['members' => [
                ['first_name' => 'Ana', 'last_name' => 'Reyes', 'email' => 'ana.reyes@example.com'],
            ]]),
            'members.xls' => "First Name,Last Name,Email\nAna,Reyes,ana.reyes@example.com\n",
        ];

        foreach ($payloads as $filename => $content) {
            $this->actingAs($admin)
                ->post(route('admin.members.import.preview'), [
                    'file' => UploadedFile::fake()->createWithContent($filename, $content),
                ])
                ->assertRedirect();

            $this->assertStringContainsString('Unsupported file type', (string) session('error'), $filename);
            $this->assertNull(session('admin.member_import'), $filename);
        }

        $this->assertDatabaseMissing('members', ['email' => 'ana.reyes@example.com']);
    }

    public function test_within_file_duplicate_email_is_flagged_and_skipped(): void
    {
        $this->preview($this->csv(
            "Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon\n".
            'Juan,Dela Cruz,juan.delacruz@example.com,09179876543,Impalutao,Impasugong,Bukidnon'
        ));

        $import = session('admin.member_import');
        $this->assertSame(1, $import['valid']);
        $this->assertSame(1, $import['errors']);

        $this->post(route('admin.members.import.confirm'), ['token' => $import['token']]);

        $this->assertDatabaseCount('members', 1);
        $this->assertSame(1, Member::where('email', 'juan.delacruz@example.com')->count());
    }

    public function test_existing_member_email_is_skipped(): void
    {
        Member::create([
            'name' => 'Existing Member',
            'first_name' => 'Existing',
            'last_name' => 'Member',
            'email' => 'juan.delacruz@example.com',
        ]);

        $this->preview($this->csv('Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon'));

        $import = session('admin.member_import');
        $this->assertSame(0, $import['valid']);

        $this->post(route('admin.members.import.confirm'), ['token' => $import['token']])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('members', 1);
        $this->assertDatabaseMissing('members', ['name' => 'Juan Dela Cruz']);
    }

    public function test_invalid_rows_are_flagged_and_not_inserted(): void
    {
        $this->preview($this->csv(
            ",Dela Cruz,not-an-email,12345,Malaybalay,Valencia,Bukidnon\n".
            'Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon'
        ));

        $import = session('admin.member_import');
        $this->assertSame(1, $import['valid']);
        $this->assertSame(1, $import['errors']);

        $this->post(route('admin.members.import.confirm'), ['token' => $import['token']]);

        $this->assertDatabaseCount('members', 1);
        $this->assertDatabaseMissing('members', ['phone' => '12345']);
    }

    public function test_non_admin_cannot_access_import(): void
    {
        $farmer = $this->makeUser('farmer', 'Juan Dela Cruz');

        $this->actingAs($farmer)
            ->get(route('admin.members.import'))
            ->assertForbidden();

        $this->actingAs($farmer)
            ->post(route('admin.members.import.preview'), ['file' => 'x'])
            ->assertForbidden();

        $this->actingAs($farmer)
            ->post(route('admin.members.import.revalidate'), ['rows' => []])
            ->assertForbidden();
    }

    public function test_cancel_clears_import_session(): void
    {
        $this->preview($this->csv('Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon'));
        $this->assertNotNull(session('admin.member_import'));

        $this->post(route('admin.members.import.cancel'))
            ->assertRedirect(route('admin.members.index'));

        $this->assertNull(session('admin.member_import'));
    }

    /**
     * The edit affordance belongs on top of every preview, ready or not —
     * a clean table must stay editable just as a failing one is.
     */
    public function test_edit_button_sits_on_top_of_the_preview_table(): void
    {
        $response = $this->preview($this->csv('Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon'));

        $response->assertOk()
            ->assertSee('Edit rows')
            ->assertSee('fileRecheckForm')
            ->assertSee('data-file-cell')
            ->assertSee(route('admin.members.import.revalidate'));
    }

    public function test_revalidate_applies_edits_and_refreshes_the_counts(): void
    {
        $this->preview($this->csv(
            "Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon\n".
            'Maria,Santos,not-an-email,09179876543,Impalutao,Impasugong,Bukidnon'
        ));

        $import = session('admin.member_import');
        $this->assertSame(1, $import['valid']);
        $this->assertSame(1, $import['errors']);

        $this->post(route('admin.members.import.revalidate'), [
            'token' => $import['token'],
            'rows' => [
                0 => ['__row' => 1, 'name' => 'Juan Dela Cruz', 'assoc_member_no' => '', 'email' => 'juan.delacruz@example.com', 'phone' => '09171234567'],
                1 => ['__row' => 2, 'name' => 'Maria Santos', 'assoc_member_no' => '', 'email' => 'maria.santos@example.com', 'phone' => '09179876543'],
            ],
        ])
            ->assertRedirect(route('admin.members.import'))
            ->assertSessionHas('success', 'All 2 row(s) now pass validation.');

        $import = session('admin.member_import');
        $this->assertSame(2, $import['valid']);
        $this->assertSame(0, $import['errors']);
        $this->assertSame('maria.santos@example.com', $import['rows'][1]['data']['email']);

        // Confirm imports exactly what was just reviewed.
        $this->post(route('admin.members.import.confirm'), ['token' => $import['token']])
            ->assertRedirect(route('admin.members.index'));

        $this->assertDatabaseCount('members', 2);
        $this->assertDatabaseHas('members', ['email' => 'maria.santos@example.com']);
    }

    public function test_revalidate_reports_rows_that_still_need_attention(): void
    {
        $this->preview($this->csv('Juan,Dela Cruz,not-an-email,12345,Malaybalay,Valencia,Bukidnon'));

        $import = session('admin.member_import');
        $this->assertSame(0, $import['valid']);

        $this->post(route('admin.members.import.revalidate'), [
            'token' => $import['token'],
            'rows' => [
                0 => ['__row' => 1, 'name' => 'Juan Dela Cruz', 'assoc_member_no' => '', 'email' => 'not-an-email', 'phone' => '12345'],
            ],
        ])
            ->assertRedirect(route('admin.members.import'))
            ->assertSessionHas('success', '1 row(s) still need attention.');

        $import = session('admin.member_import');
        $this->assertSame(0, $import['valid']);
        $this->assertSame(1, $import['errors']);
    }

    /**
     * The table shows four columns; the file carries many more. Editing a
     * cell must never drop the fields the table never rendered.
     */
    public function test_revalidate_keeps_fields_the_table_does_not_show(): void
    {
        $this->preview($this->csv('Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon'));

        $this->post(route('admin.members.import.revalidate'), [
            'token' => session('admin.member_import.token'),
            'rows' => [
                0 => ['__row' => 1, 'name' => 'Juan Dela Cruz Jr', 'assoc_member_no' => 'A-001', 'email' => 'juan.delacruz@example.com', 'phone' => '09171234567'],
            ],
        ])->assertRedirect(route('admin.members.import'));

        $row = session('admin.member_import.rows.0');
        $this->assertSame('Juan Dela Cruz Jr', $row['data']['name']);
        $this->assertSame('A-001', $row['data']['assoc_member_no']);
        $this->assertSame('Malaybalay', $row['data']['barangay']);
        $this->assertSame('Bukidnon', $row['data']['province']);
        $this->assertSame([], $row['issues']);

        $this->post(route('admin.members.import.confirm'), ['token' => session('admin.member_import.token')]);

        $this->assertDatabaseHas('members', [
            'name' => 'Juan Dela Cruz Jr',
            'assoc_member_no' => 'A-001',
            'barangay' => 'Malaybalay',
        ]);
    }

    public function test_revalidate_flags_a_duplicate_created_by_an_edit(): void
    {
        $this->preview($this->csv(
            "Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon\n".
            'Maria,Santos,maria.santos@example.com,09179876543,Impalutao,Impasugong,Bukidnon'
        ));

        $this->assertSame(2, session('admin.member_import.valid'));

        $this->post(route('admin.members.import.revalidate'), [
            'token' => session('admin.member_import.token'),
            'rows' => [
                0 => ['__row' => 1, 'name' => 'Juan Dela Cruz', 'assoc_member_no' => '', 'email' => 'juan.delacruz@example.com', 'phone' => '09171234567'],
                1 => ['__row' => 2, 'name' => 'Maria Santos', 'assoc_member_no' => '', 'email' => 'JUAN.DELACRUZ@example.com', 'phone' => '09179876543'],
            ],
        ])->assertRedirect(route('admin.members.import'));

        $import = session('admin.member_import');
        $this->assertSame(1, $import['valid']);
        $this->assertSame(1, $import['errors']);
        $this->assertStringContainsString('already on the members list', $import['rows'][1]['issues'][0]);
    }

    public function test_revalidate_rejects_a_stale_or_foreign_token(): void
    {
        $this->preview($this->csv('Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon'));
        $import = session('admin.member_import');

        $this->post(route('admin.members.import.revalidate'), [
            'token' => 'not-the-token',
            'rows' => [0 => ['__row' => 1, 'name' => 'Somebody Else']],
        ])
            ->assertRedirect(route('admin.members.import'))
            ->assertSessionHas('error');

        $this->post(route('admin.members.import.revalidate'), ['token' => $import['token']])
            ->assertRedirect(route('admin.members.import'))
            ->assertSessionHas('error', 'There is nothing to re-check.');

        // The staged preview is untouched by either attempt.
        $this->assertSame($import['valid'], session('admin.member_import.valid'));
        $this->assertSame($import['token'], session('admin.member_import.token'));
    }
}
