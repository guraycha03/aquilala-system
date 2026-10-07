<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Tests\TestCase;

class AdminUserImportTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);

        // Import confirm now issues a password-setup invitation (link + temp
        // password) per account; fake the mailer so confirm never talks to the
        // real Resend API.
        Mail::fake();
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
        return UploadedFile::fake()->createWithContent('members.csv', "First Name,Last Name,Email,Phone,Barangay,City,Province,Role\n".$rows);
    }

    private function preview(UploadedFile $file)
    {
        $admin = $this->makeUser('admin');

        return $this->actingAs($admin)->post(route('admin.users.import.preview'), ['file' => $file]);
    }

    public function test_csv_preview_shows_parsed_rows(): void
    {
        $response = $this->preview($this->csv("Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon,farmer\nMaria,Santos,maria.santos@example.com,09179876543,Impalutao,Impasugong,Bukidnon,farmer"));

        $response->assertOk();

        $import = session('admin.user_import');
        $this->assertNotNull($import);
        $this->assertSame(2, $import['valid']);
        $this->assertSame(0, $import['errors']);
    }

    public function test_csv_confirm_creates_active_accounts_invited_to_set_password(): void
    {
        $this->preview($this->csv('Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon,farmer'));

        $token = session('admin.user_import.token');

        $this->post(route('admin.users.import.confirm'), ['token' => $token])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'Juan Dela Cruz',
            'email' => 'juan.delacruz@example.com',
            'phone' => '+639171234567',
            'role' => 'farmer',
            'barangay' => 'Malaybalay',
            'city' => 'Valencia',
            'province' => 'Bukidnon',
            'status' => User::STATUS_ACTIVE,
            'must_change_password' => true,
        ]);

        $this->assertNull(session('admin.user_import'));
    }

    public function test_xlsx_import_creates_accounts(): void
    {
        $admin = $this->makeUser('admin');

        $spreadsheet = new Spreadsheet();
        $spreadsheet->getActiveSheet()->fromArray([
            ['First Name', 'Last Name', 'Email', 'Phone', 'Role', 'Store Name'],
            ['Ana', 'Reyes', 'ana.reyes@example.com', '09271234567', 'store_owner', 'Ana Agro Supply'],
        ]);

        $temporary = tempnam(sys_get_temp_dir(), 'aquilala_test_') . '.xlsx';
        (new Xlsx($spreadsheet))->save($temporary);
        $spreadsheet->disconnectWorksheets();

        $file = UploadedFile::fake()->createWithContent('members.xlsx', (string) file_get_contents($temporary));
        @unlink($temporary);

        $this->actingAs($admin)
            ->post(route('admin.users.import.preview'), ['file' => $file])
            ->assertOk();

        $import = session('admin.user_import');
        $this->assertSame(1, $import['valid']);

        $this->post(route('admin.users.import.confirm'), ['token' => $import['token']])
            ->assertRedirect();

        $this->assertDatabaseHas('users', [
            'email' => 'ana.reyes@example.com',
            'role' => 'store_owner',
            'store_name' => 'Ana Agro Supply',
        ]);
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
                ->post(route('admin.users.import.preview'), [
                    'file' => UploadedFile::fake()->createWithContent($filename, $content),
                ])
                ->assertRedirect();

            $this->assertStringContainsString('Unsupported file type', (string) session('error'), $filename);
            $this->assertNull(session('admin.user_import'), $filename);
        }

        $this->assertDatabaseMissing('users', ['email' => 'ana.reyes@example.com']);
    }

    public function test_within_file_duplicate_email_is_flagged_and_skipped(): void
    {
        $this->preview($this->csv(
            "Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon,farmer\n".
            'Juan,Dela Cruz,juan.delacruz@example.com,09179876543,Impalutao,Impasugong,Bukidnon,farmer'
        ));

        $import = session('admin.user_import');
        $this->assertSame(1, $import['valid']);
        $this->assertSame(1, $import['errors']);

        $this->post(route('admin.users.import.confirm'), ['token' => $import['token']]);

        $this->assertDatabaseCount('users', 2);
        $this->assertSame(1, User::where('email', 'juan.delacruz@example.com')->count());
    }

    public function test_existing_account_email_is_skipped(): void
    {
        User::create([
            'name' => 'Existing User',
            'email' => 'juan.delacruz@example.com',
            'role' => 'farmer',
            'password' => 'password123',
        ]);

        $this->preview($this->csv('Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon,farmer'));

        $import = session('admin.user_import');
        $this->assertSame(0, $import['valid']);

        $this->post(route('admin.users.import.confirm'), ['token' => $import['token']])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseMissing('users', ['name' => 'Juan Dela Cruz']);
    }

    public function test_invalid_rows_are_flagged_and_not_inserted(): void
    {
        $response = $this->preview($this->csv(
            ",Dela Cruz,not-an-email,12345,Malaybalay,Valencia,Bukidnon,admin\n".
            'Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon,farmer'
        ));

        $import = session('admin.user_import');
        $this->assertSame(1, $import['valid']);
        $this->assertSame(1, $import['errors']);

        $this->post(route('admin.users.import.confirm'), ['token' => $import['token']]);

        $this->assertDatabaseCount('users', 2);
        $this->assertDatabaseMissing('users', ['phone' => '12345']);
    }

    public function test_non_admin_cannot_access_import(): void
    {
        $farmer = $this->makeUser('farmer', 'Juan Dela Cruz');

        $this->actingAs($farmer)
            ->get(route('admin.users.import'))
            ->assertForbidden();

        $this->actingAs($farmer)
            ->post(route('admin.users.import.preview'), ['file' => 'x'])
            ->assertForbidden();
    }

    public function test_cancel_clears_import_session(): void
    {
        $this->preview($this->csv('Juan,Dela Cruz,juan.delacruz@example.com,09171234567,Malaybalay,Valencia,Bukidnon,farmer'));
        $this->assertNotNull(session('admin.user_import'));

        $this->post(route('admin.users.import.cancel'))
            ->assertRedirect(route('admin.users.index'));

        $this->assertNull(session('admin.user_import'));
    }
}
