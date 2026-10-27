<?php

namespace App\Services;

use App\AI\AiLlmClient;
use Illuminate\Support\Facades\Log;

/**
 * Optional second pass over OCR text: lets the model decide which scanned
 * text belongs in which member field, instead of relying on the line-by-line
 * guesses in OcrMemberExtractor.
 *
 * Contract with the caller (AdminMemberImportController::previewOcr):
 *
 *   - it either returns a sanitized, field allow-listed row set, or [].
 *   - [] is returned for every failure mode (no API key, network error,
 *     unusable JSON, zero usable rows, document too large to send), so an AI
 *     outage can never break the import: the rule-based rows are kept.
 *   - returned rows are still unvalidated guesses. The caller always runs
 *     them through normalizeRow() + markDuplicates() exactly like file rows,
 *     so nothing the model invents can reach the database unflagged.
 *
 * Gated on services.groq.key, the same switch as every other AI feature in
 * the app. With no key configured this class never opens a connection.
 */
class OcrMemberAiMapper
{
    /**
     * Rule-based candidates beyond this are not sent to the model; a scan
     * that big is better handled as several smaller scans, and it keeps the
     * request within a sane size.
     */
    public const MAX_ROWS_PER_CALL = 60;

    private const MAX_TEXT_CHARS = 6000;

    private const MAX_FIELD_CHARS = 255;

    private const MAX_REPLY_TOKENS = 4000;

    /**
     * The raw keys normalizeRow() understands — the only keys ever allowed
     * back from the model. Anything else it returns is dropped.
     */
    private const ALLOWED_FIELDS = [
        'first_name', 'middle_initial', 'last_name', 'name', 'email', 'phone',
        'sex', 'birth_date', 'civil_status', 'address', 'barangay', 'city',
        'province', 'farm_location', 'land_ownership', 'store_name',
        'assoc_member_no', 'years_farming', 'role',
    ];

    public function __construct(private AiLlmClient $llm) {}

    public function isAvailable(): bool
    {
        return $this->llm->available();
    }

    /**
     * Match raw OCR text to member fields.
     *
     * @param  array<int, array<string, string>>  $candidates  rule-based rows, used as a hint
     * @return array<int, array<string, string>>
     */
    public function map(string $rawText, string $filename, array $candidates): array
    {
        if (! $this->isAvailable()) {
            return [];
        }

        $rawText = trim($rawText);

        if ($rawText === '') {
            return [];
        }

        if (count($candidates) > self::MAX_ROWS_PER_CALL) {
            Log::info('OCR AI matching skipped: document has too many candidate rows.', [
                'candidates' => count($candidates),
                'limit' => self::MAX_ROWS_PER_CALL,
            ]);

            return [];
        }

        $result = $this->llm->completeJson($this->messages($rawText, $filename, $candidates), self::MAX_REPLY_TOKENS);

        if ($result === null) {
            Log::warning('OCR AI matching got no reply from the model provider; keeping rule-based rows.');

            return [];
        }

        $rows = $this->sanitize($this->parse($result['raw']));

        if ($rows === []) {
            Log::warning('OCR AI matching returned no usable rows; keeping rule-based rows.');

            return [];
        }

        return $rows;
    }

    /**
     * @param  array<int, array<string, string>>  $candidates
     * @return array<int, array{role: string, content: string}>
     */
    private function messages(string $rawText, string $filename, array $candidates): array
    {
        $payload = json_encode([
            'filename' => $filename,
            'raw_ocr_text' => mb_substr($rawText, 0, self::MAX_TEXT_CHARS),
            'rule_based_candidates' => array_values($candidates),
        ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PARTIAL_OUTPUT_ON_ERROR);

        return [
            ['role' => 'system', 'content' => $this->systemPrompt()],
            ['role' => 'user', 'content' => $this->userPrompt(is_string($payload) ? $payload : '{}')],
        ];
    }

    private function systemPrompt(): string
    {
        $fields = implode(', ', self::ALLOWED_FIELDS);

        return <<<'PROMPT'
You turn the OCR text of a scanned Philippine association member list into structured member records.

You are given three things: the uploaded filename, the raw OCR text, and candidate rows produced by a rule-based parser. The rule-based candidates are only a starting hint — verify them, correct misread fields, drop junk, and add members the parser missed.

Rules:
1. Reply with JSON only, no prose and no code fences: {"rows":[{"first_name":"...","last_name":"..."}]}
2. One object per real person listed in the scan.
3. Discard anything that is not a member record: document titles, column headers, page numbers, office contact details, instructions, dates of printing, and the filename. A filename is never a person's name.
4. Use only these field names: {$fields}. Never invent a field name of your own.
5. Split each person's name into first_name and last_name, and also give the full name in "name".
6. email: lowercase, only when it is genuinely printed in the scan.
7. phone: a Philippine mobile number as scanned (09XXXXXXXXX, 11 digits). Leave it out if you are not sure.
8. birth_date: YYYY-MM-DD only when clearly printed. Otherwise leave it out.
9. role: only "farmer" or "store_owner". Leave it out when the scan does not state it.
10. years_farming: digits only.
11. Never invent, guess, autocomplete or reorder values that are not in the scan. If a value is uncertain, leave the field out.
12. Never merge two people into one row, and never split one person into two.
PROMPT;
    }

    private function userPrompt(string $payload): string
    {
        return "Extract the member records. Reply with JSON only, in the form {\"rows\":[...]}.\n\n" . $payload;
    }

    /**
     * Decode the model reply into a rows array, tolerating the usual ways a
     * JSON reply goes wrong. Anything unparseable becomes [].
     */
    private function parse(?string $content): array
    {
        $content = trim((string) $content);

        if ($content === '') {
            return [];
        }

        $content = preg_replace('/^```(?:json)?\s*/i', '', $content) ?? $content;
        $content = preg_replace('/\s*```$/', '', $content) ?? $content;
        $content = trim($content);

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            if (preg_match('/\{.*\}/s', $content, $match)) {
                $decoded = json_decode($match[0], true);
            }

            if (! is_array($decoded)) {
                return [];
            }
        }

        $rows = $decoded['rows'] ?? null;

        if (! is_array($rows)) {
            return [];
        }

        return array_values(array_filter($rows, 'is_array'));
    }

    /**
     * Keep only allow-listed, scalar, bounded values, then drop rows with
     * nothing left in them.
     *
     * @param  array<int, mixed>  $rows
     * @return array<int, array<string, string>>
     */
    private function sanitize(array $rows): array
    {
        $cleaned = [];

        foreach (array_slice($rows, 0, self::MAX_ROWS_PER_CALL) as $row) {
            $record = [];

            foreach (self::ALLOWED_FIELDS as $field) {
                $value = $row[$field] ?? null;

                if (! is_scalar($value)) {
                    continue;
                }

                $value = trim(preg_replace('/[\x00-\x1F\x7F]/u', '', (string) $value) ?? '');

                if ($value === '') {
                    continue;
                }

                $record[$field] = mb_substr($value, 0, self::MAX_FIELD_CHARS);
            }

            if ($record === []) {
                continue;
            }

            $cleaned[] = $record;
        }

        // A set with no name and no e-mail anywhere cannot be reviewed as
        // members — treat it as no answer at all so the caller falls back.
        foreach ($cleaned as $record) {
            if (($record['name'] ?? '') !== '' || ($record['email'] ?? '') !== '') {
                return $cleaned;
            }
        }

        return [];
    }
}
