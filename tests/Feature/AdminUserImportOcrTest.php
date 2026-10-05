<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OcrService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AdminUserImportOcrTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutMiddleware(ValidateCsrfToken::class);
        Mail::fake();

        if (! app(OcrService::class)->isAvailable()) {
            $this->markTestSkipped('Tesseract OCR (with language data) is not available on this machine.');
        }
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

    private function scan(): UploadedFile
    {
        $png = file_get_contents(base_path('tests/Fixtures/ocr-member-list.png'));

        return UploadedFile::fake()->createWithContent('member-list.png', $png);
    }

    public function test_ocr_preview_reads_the_scan_and_offers_rows_for_review(): void
    {
        $admin = $this->makeAdmin();

        $response = $this->actingAs($admin)->post(route('admin.users.import.ocr'), [
            'file' => $this->scan(),
        ]);

        $response->assertOk();

        $import = session('admin.user_import');
        $this->assertNotNull($import);
        $this->assertSame('ocr', $import['source'] ?? null);
        $this->assertNotSame('', trim($import['ocr_text'] ?? ''));

        $emails = array_column(array_column($import['rows'], 'data'), 'email');
        $this->assertContains('juan@example.com', $emails);
    }

    public function test_ocr_confirm_creates_accounts_only_for_approved_rows(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post(route('admin.users.import.ocr'), ['file' => $this->scan()])
            ->assertOk();

        $import = session('admin.user_import');
        $this->assertNotNull($import);

        $approved = [];
        foreach ($import['rows'] as $row) {
            if (($row['data']['email'] ?? null) === 'juan@example.com' && $row['issues'] === []) {
                $approved[] = (string) $row['_row'];
            }
        }
        $this->assertNotSame([], $approved, 'The scanned row should be importable without issues.');

        $this->post(route('admin.users.import.ocr.confirm'), [
            'token' => $import['token'],
            'approve' => $approved,
        ])
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'email' => 'juan@example.com',
            'role' => 'farmer',
        ]);
    }

    public function test_ocr_confirm_requires_explicit_row_approval(): void
    {
        $admin = $this->makeAdmin();
        $this->actingAs($admin)->post(route('admin.users.import.ocr'), ['file' => $this->scan()])
            ->assertOk();

        $import = session('admin.user_import');

        $this->post(route('admin.users.import.ocr.confirm'), [
            'token' => $import['token'],
            'approve' => [],
        ])->assertSessionHas('error');

        $this->assertDatabaseCount('users', 1);
    }

    public function test_ocr_preview_rejects_non_image_files(): void
    {
        $admin = $this->makeAdmin();
        $fake = UploadedFile::fake()->createWithContent('members.csv', "First Name,Email\nJuan,juan@example.com\n");

        $this->actingAs($admin)->post(route('admin.users.import.ocr'), ['file' => $fake])
            ->assertSessionHas('error');
    }
}
