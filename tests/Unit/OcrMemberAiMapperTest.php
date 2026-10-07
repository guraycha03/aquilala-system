<?php

namespace Tests\Unit;

use App\AI\AiLlmClient;
use App\Services\OcrMemberAiMapper;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * The sanitizer half of the OCR AI stage: whatever the model replies with,
 * only bounded, allow-listed, scalar fields may leave this class.
 */
class OcrMemberAiMapperTest extends TestCase
{
    private function mapper(): OcrMemberAiMapper
    {
        return new OcrMemberAiMapper(new AiLlmClient);
    }

    private function replyWith(string $content): void
    {
        config(['services.groq.key' => 'test-key']);

        Http::fake([
            'api.groq.com/*' => Http::response([
                'choices' => [['message' => ['content' => $content]]],
            ]),
        ]);
    }

    public function test_without_an_api_key_nothing_is_sent(): void
    {
        config(['services.groq.key' => '']);
        Http::fake();

        $rows = $this->mapper()->map('Juan Dela Cruz 09171234567', 'list.png', [['first_name' => 'Juan']]);

        $this->assertSame([], $rows);
        Http::assertNothingSent();
    }

    public function test_unknown_fields_and_non_scalar_values_are_dropped(): void
    {
        $this->replyWith(json_encode(['rows' => [
            [
                'first_name' => 'Ana',
                'last_name' => 'Reyes',
                'email' => 'ana@example.com',
                'password' => 'secret',          // not an allowed field
                'barangay' => ['nested' => 'x'],  // not a scalar
                'phone' => 9171234567,            // number is fine, becomes a string
            ],
        ]]));

        $rows = $this->mapper()->map('Ana Reyes ana@example.com', 'list.png', []);

        $this->assertCount(1, $rows);
        $this->assertSame([
            'first_name' => 'Ana',
            'last_name' => 'Reyes',
            'email' => 'ana@example.com',
            'phone' => '9171234567',
        ], $rows[0]);
    }

    public function test_a_reply_with_no_name_and_no_email_is_treated_as_no_answer(): void
    {
        $this->replyWith(json_encode(['rows' => [
            ['barangay' => 'Malaybalay'],
            ['city' => 'Valencia'],
        ]]));

        // [] tells the controller to keep the rule-based rows.
        $this->assertSame([], $this->mapper()->map('Malaybalay Valencia', 'list.png', []));
    }

    public function test_unparseable_reply_returns_no_rows(): void
    {
        $this->replyWith('I could not read the scan.');

        $this->assertSame([], $this->mapper()->map('Juan Dela Cruz', 'list.png', []));
    }

    public function test_empty_text_is_never_sent_to_the_model(): void
    {
        config(['services.groq.key' => 'test-key']);
        Http::fake();

        $this->assertSame([], $this->mapper()->map('   ', 'list.png', []));
        Http::assertNothingSent();
    }
}
