<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OcrService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Tests\TestCase;

/**
 * The manual-edit step on the OCR member review table: the Edit button, the
 * cells it opens, the row an administrator can add by hand, and the re-check
 * that puts the edited values through the same validator a scan gets.
 *
 * The session is seeded directly so these run without Tesseract; the last
 * test renders the real preview and skips when OCR is unavailable.
 */
class AdminMemberImportOcrEditTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();
        // The import vault parks uploaded files on this disk — fake it so
        // no test ever writes to (or reads from) real storage.
        Storage::fake('local');
    }

    private function makeAdmin(): User
    {
        return User::create([
            'name' => 'System Admin',
            'first_name' => 'System',
            'last_name' => 'Admin',
            'email' => 'admin_'.uniqid().'@example.com',
            'role' => 'admin',
            'password' => 'password123',
        ]);
    }

    /** The one administrator the test signs in as. */
    private function admin(): User
    {
        return $this->admin ??= $this->makeAdmin();
    }

    /**
     * One staged OCR row. `_row` is assigned by stage(), `_raw` defaults to
     * the normalized copy the way a scan stages it.
     *
     * @return array<string, mixed>
     */
    private function row(array $data, array $issues = [], ?array $raw = null): array
    {
        return [
            'data' => $data,
            'issues' => $issues,
            '_raw' => $raw ?? $data,
        ];
    }

    /** @param  list<array<string, mixed>>  $rows */
    private function stage(array $rows, array $extra = []): string
    {
        $token = Str::random(32);

        foreach ($rows as $index => &$row) {
            $row['_row'] = $index + 1;
            $row['_source'] = 'ocr';
        }
        unset($row);

        $valid = count(array_filter($rows, fn (array $row) => $row['issues'] === []));

        $this->withSession([
            'admin.member_import' => $extra + [
                'token' => $token,
                'filename' => 'member-list.png',
                'source' => 'ocr',
                'ai_assisted' => false,
                'ocr_text' => 'RAW OCR TEXT',
                'rows' => $rows,
                'valid' => $valid,
                'errors' => count($rows) - $valid,
                'approved' => [],
                'expires' => now()->addMinutes(60)->timestamp,
            ],
        ]);

        return $token;
    }

    /** @return array<string, mixed> */
    private function staged(): array
    {
        $import = session('admin.member_import');
        $this->assertIsArray($import, 'The OCR review session should still be staged.');

        return $import;
    }

    public function test_revalidate_revalidates_an_edited_cell_and_reports_the_result(): void
    {
        $token = $this->stage([
            $this->row(
                ['name' => 'Juan Dela Cruz', 'email' => 'not-an-email'],
                ['Email address is invalid.']
            ),
        ]);

        $this->actingAs($this->admin())->post(route('admin.members.import.ocr.revalidate'), [
            'token' => $token,
            'approve' => ['1'],
            'rows' => [[
                '__row' => '1',
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@example.com',
                'phone' => '',
                'assoc_member_no' => '',
            ]],
        ])->assertSessionHas('success');

        $import = $this->staged();

        $this->assertSame([], $import['rows'][0]['issues'], 'The fixed e-mail must clear the flag.');
        $this->assertSame('juan@example.com', $import['rows'][0]['data']['email']);
        $this->assertSame(1, $import['valid']);
        $this->assertSame(0, $import['errors']);
        // The tick survives the re-check.
        $this->assertSame(['1'], $import['approved']);

        // back() lands on the review page, which renders the new state.
        $this->actingAs($this->admin())->get(route('admin.members.import'))
            ->assertOk()
            ->assertSee('All 1 row(s) now pass validation.')
            ->assertSee('juan@example.com');
    }

    public function test_editing_one_cell_keeps_the_fields_the_table_does_not_show(): void
    {
        $token = $this->stage([
            $this->row(
                ['name' => 'Ana Reyes', 'email' => 'ana@example.com'],
                ['Phone number is not a valid PH mobile number (e.g. 09171234567).'],
                // The scan also read a barangay and a broken phone number;
                // neither has a column in the table.
                ['name' => 'Ana Reyes', 'email' => 'ana@example.com', 'phone' => '12345',
                    'barangay' => 'Malaybalay']
            ),
        ]);

        $this->actingAs($this->admin())->post(route('admin.members.import.ocr.revalidate'), [
            'token' => $token,
            'rows' => [[
                '__row' => '1',
                'name' => 'Ana Reyes',
                'email' => 'ana@example.com',
                'phone' => '09171234567',
                'assoc_member_no' => '',
            ]],
        ])->assertSessionHas('success');

        $row = $this->staged()['rows'][0];

        $this->assertSame([], $row['issues']);
        $this->assertSame('+639171234567', $row['data']['phone'], 'Stored in the normalised PH form.');
        $this->assertSame('Malaybalay', $row['data']['barangay'], 'A field outside the table must survive the edit.');
        $this->assertSame('Malaybalay', $row['_raw']['barangay']);
    }

    public function test_only_the_columns_the_table_edits_are_accepted(): void
    {
        $token = $this->stage([
            $this->row(['name' => 'Juan Dela Cruz', 'email' => 'juan@example.com']),
        ]);

        $this->actingAs($this->admin())->post(route('admin.members.import.ocr.revalidate'), [
            'token' => $token,
            'rows' => [[
                '__row' => '1',
                'name' => 'Juan Dela Cruz',
                'email' => 'juan@example.com',
                'phone' => '',
                'assoc_member_no' => 'MEM-01',
                // Posted, but not a column this table is allowed to change —
                // and members hold no role or credentials at all.
                'role' => 'store_owner',
                'password' => 'secret',
                'status' => 'active',
                'is_admin' => '1',
                '_source' => 'file',
            ]],
        ]);

        $data = $this->staged()['rows'][0]['data'];

        $this->assertSame('MEM-01', $data['assoc_member_no']);
        $this->assertArrayNotHasKey('role', $data, 'Members are the list, not accounts — they carry no role.');
        $this->assertArrayNotHasKey('password', $data);
        $this->assertArrayNotHasKey('status', $data);
        $this->assertArrayNotHasKey('is_admin', $data);
        $this->assertSame('ocr', $this->staged()['rows'][0]['_source'], 'A row can never stop being an OCR row.');
    }

    public function test_a_row_added_manually_is_numbered_and_keeps_its_tick(): void
    {
        $token = $this->stage([
            $this->row(['name' => 'Juan Dela Cruz', 'email' => 'juan@example.com']),
        ]);

        $this->actingAs($this->admin())->post(route('admin.members.import.ocr.revalidate'), [
            'token' => $token,
            'approve' => ['2'],
            'rows' => [
                [
                    '__row' => '1',
                    'name' => 'Juan Dela Cruz',
                    'email' => 'juan@example.com',
                    'phone' => '',
                    'assoc_member_no' => '',
                ],
                [
                    // Added in the table: no row number yet.
                    '__row' => '',
                    'name' => 'Maria Santos',
                    'email' => 'maria@example.com',
                    'phone' => '',
                    'assoc_member_no' => 'MEM-02',
                ],
            ],
        ])->assertSessionHas('success');

        $import = $this->staged();

        $this->assertCount(2, $import['rows']);
        $this->assertSame(2, $import['rows'][1]['_row'], 'The added row takes the next free number.');
        $this->assertSame('maria@example.com', $import['rows'][1]['data']['email']);
        $this->assertSame('ocr', $import['rows'][1]['_source']);
        $this->assertSame([], $import['rows'][1]['issues']);
        // The tick was posted with the provisional number and still matches.
        $this->assertSame(['2'], $import['approved']);
        $this->assertSame(2, $import['valid']);

        $this->actingAs($this->admin())->post(route('admin.members.import.ocr.confirm'), [
            'token' => $token,
            'approve' => ['2'],
        ])->assertRedirect(route('admin.members.index'));

        $this->assertDatabaseHas('members', ['email' => 'maria@example.com', 'user_id' => null]);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_confirm_imports_edited_values_without_a_separate_recheck(): void
    {
        $token = $this->stage([
            $this->row(['name' => 'Juan Dela Cruz', 'email' => 'juan@example.com']),
        ]);

        $this->actingAs($this->admin())->post(route('admin.members.import.ocr.confirm'), [
            'token' => $token,
            'approve' => ['1'],
            // The table posts its cells with the ticks, so importing straight
            // after an edit must import what was typed.
            'rows' => [[
                '__row' => '1',
                'name' => 'Juan Dela Cruz',
                'email' => 'juan.dela.cruz@example.com',
                'phone' => '',
                'assoc_member_no' => '',
            ]],
        ])->assertRedirect(route('admin.members.index'));

        $this->assertDatabaseHas('members', ['email' => 'juan.dela.cruz@example.com']);
        $this->assertDatabaseMissing('members', ['email' => 'juan@example.com']);
        $this->assertDatabaseCount('users', 1);
    }

    public function test_revalidate_flags_a_duplicate_created_by_an_edit(): void
    {
        $token = $this->stage([
            $this->row(['name' => 'Juan Dela Cruz', 'email' => 'juan@example.com']),
            $this->row(['name' => 'Ana Reyes', 'email' => 'ana@example.com']),
        ]);

        $this->actingAs($this->admin())->post(route('admin.members.import.ocr.revalidate'), [
            'token' => $token,
            'rows' => [
                ['__row' => '1', 'name' => 'Juan Dela Cruz', 'email' => 'juan@example.com',
                    'phone' => '', 'assoc_member_no' => ''],
                ['__row' => '2', 'name' => 'Ana Reyes', 'email' => 'juan@example.com',
                    'phone' => '', 'assoc_member_no' => ''],
            ],
        ])->assertSessionHas('success');

        $import = $this->staged();

        $this->assertSame([], $import['rows'][0]['issues'], 'The first occurrence of an address is fine.');
        $this->assertContains('This email is already on the members list.', $import['rows'][1]['issues']);
        $this->assertSame(1, $import['valid']);
        $this->assertSame(1, $import['errors']);
    }

    public function test_revalidate_rejects_a_stale_or_foreign_token(): void
    {
        $token = $this->stage([
            $this->row(['name' => 'Juan Dela Cruz', 'email' => 'juan@example.com']),
        ]);

        $this->actingAs($this->admin())->post(route('admin.members.import.ocr.revalidate'), [
            'token' => 'not-the-staged-token',
            'rows' => [[
                '__row' => '1', 'name' => 'Someone Else', 'email' => 'someone@example.com',
                'phone' => '', 'assoc_member_no' => '',
            ]],
        ])->assertRedirect(route('admin.members.import'))->assertSessionHas('error');

        $this->assertSame('juan@example.com', $this->staged()['rows'][0]['data']['email']);
        $this->assertSame($token, $this->staged()['token']);
    }

    public function test_the_review_table_shows_an_edit_button_and_editable_cells(): void
    {
        if (! app(OcrService::class)->isAvailable()) {
            $this->markTestSkipped('Tesseract OCR (with language data) is not available on this machine.');
        }

        $png = file_get_contents(base_path('tests/Fixtures/ocr-member-list.png'));
        $file = UploadedFile::fake()->createWithContent('member-list.png', $png);

        $this->actingAs($this->admin())->post(route('admin.members.import.ocr'), ['file' => $file])
            ->assertOk()
            ->assertSee('Edit rows')
            ->assertSee('id="ocrEditBtn"', false)
            ->assertSee('id="ocrSaveBtn"', false)
            ->assertSee('name="rows[0][email]"', false)
            ->assertSee(route('admin.members.import.ocr.revalidate'), false);
    }
}
