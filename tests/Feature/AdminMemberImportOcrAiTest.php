<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\OcrService;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The AI field-matching stage on the OCR member import.
 *
 * Every test still goes through real Tesseract (same skip guard as
 * AdminMemberImportOcrTest) because the stage sits between raw OCR text and
 * the shared normalizeRow() pipeline.
 */
class AdminMemberImportOcrAiTest extends TestCase
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

    private function previewScan(): void
    {
        $this->actingAs($this->makeAdmin())->post(route('admin.members.import.ocr'), [
            'file' => $this->scan(),
        ])->assertOk();
    }

    /**
     * Fake every outbound call (the app also refreshes weather in the
     * background) but answer only the model provider with $content.
     */
    private function replyWith(string $content): void
    {
        config(['services.groq.key' => 'test-key']);

        Http::fake(function ($request) use ($content) {
            if (str_contains($request->url(), 'api.groq.com')) {
                return Http::response([
                    'choices' => [['message' => ['content' => $content]]],
                ]);
            }

            return Http::response('{}');
        });
    }

    /** @return array<int, Request> */
    private function groqRequests(): array
    {
        return collect(Http::recorded())
            ->filter(fn ($pair) => str_contains($pair[0]->url(), 'api.groq.com'))
            ->map(fn ($pair) => $pair[0])
            ->values()
            ->all();
    }

    public function test_ai_matched_rows_are_staged_and_the_filename_is_only_context(): void
    {
        $this->replyWith(json_encode(['rows' => [
            ['first_name' => 'Ana', 'last_name' => 'Reyes', 'name' => 'Ana Reyes',
                'email' => 'ana.reyes@example.com', 'phone' => '09271234567', 'barangay' => 'Malaybalay'],
            ['first_name' => 'Juan', 'last_name' => 'Dela Cruz', 'name' => 'Juan Dela Cruz',
                'email' => 'juan@example.com'],
        ]]));

        $this->previewScan();

        $requests = $this->groqRequests();
        $this->assertCount(1, $requests);
        // The filename travels as context so the model knows it is not a member.
        $this->assertStringContainsString('member-list.png', $requests[0]->body());

        $import = session('admin.member_import');
        $this->assertTrue($import['ai_assisted'] ?? false);

        $emails = array_column(array_column($import['rows'], 'data'), 'email');
        $this->assertContains('ana.reyes@example.com', $emails);
        $this->assertContains('juan@example.com', $emails);

        foreach ($import['rows'] as $row) {
            $this->assertStringNotContainsStringIgnoringCase('member-list', (string) ($row['data']['name'] ?? ''));
        }
    }

    public function test_unusable_ai_reply_falls_back_to_rule_based_rows(): void
    {
        $this->replyWith('Sorry, I cannot extract that document.');

        $this->previewScan();

        $import = session('admin.member_import');
        $this->assertNotNull($import);
        $this->assertFalse($import['ai_assisted'] ?? false);

        // The rule-based parser still produced the row the plain OCR test expects.
        $emails = array_column(array_column($import['rows'], 'data'), 'email');
        $this->assertContains('juan@example.com', $emails);
    }

    public function test_ai_rows_are_still_validated_by_the_shared_validator(): void
    {
        $this->replyWith(json_encode(['rows' => [
            ['first_name' => 'Broken', 'last_name' => 'Row', 'name' => 'Broken Row', 'email' => 'not-an-email'],
        ]]));

        $this->previewScan();

        $import = session('admin.member_import');
        $this->assertTrue($import['ai_assisted'] ?? false);

        // An invalid address never reaches `data` — normalizeRow() flags the
        // row instead of storing it — so look at what the model actually sent.
        $flagged = array_values(array_filter(
            $import['rows'],
            fn (array $row) => (($row['_raw']['email'] ?? null) === 'not-an-email')
                || (($row['data']['email'] ?? null) === 'not-an-email')
        ));
        $this->assertCount(1, $flagged);
        $this->assertNotSame([], $flagged[0]['issues'], 'An AI-supplied invalid e-mail must be flagged, not importable.');
        $this->assertArrayNotHasKey('email', $flagged[0]['data'], 'The bad address must not be staged for insert.');
    }

    public function test_no_api_key_means_no_request_and_rule_based_rows(): void
    {
        config(['services.groq.key' => '']);
        Http::fake(fn () => Http::response('{}'));

        $this->previewScan();

        $this->assertSame([], $this->groqRequests(), 'No model call may happen without an API key.');

        $import = session('admin.member_import');
        $this->assertFalse($import['ai_assisted'] ?? false);

        $emails = array_column(array_column($import['rows'], 'data'), 'email');
        $this->assertContains('juan@example.com', $emails);
    }
}
