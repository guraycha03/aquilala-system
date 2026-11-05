<?php

namespace App\Services;

use App\Support\ProductFields;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Throwable;

/**
 * Optional AI assist for spreadsheet rows that failed validation.
 *
 * The deterministic normaliser in ProductImportService already handles the
 * mechanical cases (currency symbols, "Out of Stock", unit casing). This step
 * exists for the judgement calls a merchant can get wrong, such as writing
 * "Fertilizer - Urea" in the category column or "Philphos Urea 46-0-0, 50kg
 * bag" as a name.
 *
 * Every suggestion is fed back through the same validation rules, so the AI can
 * only ever narrow a row's problems, never wave them through. With no API key
 * configured, isAvailable() is false and the UI hides the button entirely.
 */
class ProductRowAiFixer
{
    /**
     * Rows are sent in one request, so cap the batch to keep the prompt (and
     * the bill) predictable. The merchant re-runs the action for the rest.
     */
    public const MAX_ROWS_PER_CALL = 40;

    private const MODEL = 'openai/gpt-oss-20b';

    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    public function isAvailable(): bool
    {
        return (string) config('services.groq.key', '') !== '';
    }

    /**
     * Ask the model to correct the given rows.
     *
     * @param  array<int,array{row: int, data: array<string,mixed>, errors: array<string,string>}>  $rows
     * @return array<int,array{row: int, data: array<string,mixed>}> Corrections keyed by the row's "row" value.
     */
    public function suggest(array $rows, array $categoryNames): array
    {
        if (! $this->isAvailable() || $rows === []) {
            return [];
        }

        $payload = [];

        foreach ($rows as $row) {
            $payload[] = [
                'row' => $row['row'],
                'values' => array_map(
                    fn ($value) => is_scalar($value) || $value === null ? $value : (string) $value,
                    array_filter($row['data'], fn ($value) => $value !== null && $value !== '')
                ),
                'errors' => array_values($row['errors']),
            ];
        }

        $instructions = $this->instructions($categoryNames);

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
                'Content-Type' => 'application/json',
            ])
                ->withoutVerifying()
                ->timeout(60)
                ->post(self::ENDPOINT, [
                    'model' => self::MODEL,
                    'messages' => [
                        [
                            'role' => 'system',
                            // temperature 0 keeps the model deterministic: this is
                            // a formatting job, not a creative one.
                            'content' => "You clean up agricultural product spreadsheet rows.\n\n".$instructions,
                        ],
                        [
                            'role' => 'user',
                            'content' => "Fix these rows. Reply with JSON only, no prose, no code fences.\n\n"
                                .json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
                        ],
                    ],
                    'temperature' => 0,
                    'max_tokens' => 4000,
                    'reasoning_effort' => 'low',
                    'response_format' => ['type' => 'json_object'],
                ]);
        } catch (ConnectionException $e) {
            Log::warning('Product import AI assist could not reach the API.', ['error' => $e->getMessage()]);

            return [];
        } catch (Throwable $e) {
            Log::warning('Product import AI assist failed.', ['error' => $e->getMessage()]);

            return [];
        }

        if ($response->failed()) {
            Log::warning('Product import AI assist rejected.', [
                'status' => $response->status(),
                'body' => $response->body(),
            ]);

            return [];
        }

        return $this->parse($response->json('choices.0.message.content', ''));
    }

    private function instructions(array $categoryNames): string
    {
        $seasons = implode(', ', ProductFields::SEASONS);
        $statuses = implode(', ', ProductFields::STATUSES);
        $categories = implode(' | ', $categoryNames);
        $fields = implode(', ', array_keys(ProductFields::COLUMNS));

        return <<<TEXT
Rules:
- Fix only the field named in the error. Never invent data that cannot be derived from the row.
- Never blank a field you are not confident about, and never remove a value that is already correct.
- "name": plain product name only. Never include a size, weight or volume, because that belongs in package_size + package_unit.
- "name": keep the NPK grade in parentheses when it is already part of the name, for example "Urea Fertilizer (46-0-0)". Do not add a grade that is not there.
- "category": must be exactly one of: {$categories}
- "category_id": leave it null when unsure. Only use a number you are confident about.
- "recommended_season": one of {$seasons}
- "status": one of {$statuses}
- "price", "package_size", "fertilizer_rate_min", "fertilizer_rate_max": plain numbers, no currency symbols, no thousands separators.
- "stock_quantity", "reorder_threshold": whole numbers.
- "manufacturing_date", "expiration_date": YYYY-MM-DD.
- "sku": no spaces. Keep the merchant's own code if one exists; otherwise leave it unchanged.
- Only these keys may appear: {$fields}

Reply with this exact shape and nothing else:
{"rows":[{"row":<same row number>,"data":{<only the fields you changed>}}]}
TEXT;
    }

    /**
     * Model output is untrusted, so keep only known fields, only the row numbers
     * we asked about, and only values that survive a type check.
     */
    private function parse(?string $content): array
    {
        if ($content === null || trim($content) === '') {
            return [];
        }

        $content = trim($content);

        // Tolerate a fenced block even though JSON mode was requested.
        if (str_starts_with($content, '```')) {
            $content = (string) preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $content);
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            // Fall back to the first JSON object in the response.
            if (preg_match('/\{.*\}/s', $content, $m)) {
                $decoded = json_decode($m[0], true);
            }
        }

        $rows = $decoded['rows'] ?? null;

        if (! is_array($rows)) {
            return [];
        }

        $numericFields = [
            'price', 'package_size', 'nitrogen_percentage', 'phosphorus_percentage',
            'potassium_percentage', 'fertilizer_rate_min', 'fertilizer_rate_max',
        ];

        $wholeFields = ['stock_quantity', 'reorder_threshold'];
        $dateFields = ['manufacturing_date', 'expiration_date'];

        $corrections = [];

        foreach ($rows as $entry) {
            if (! is_array($entry) || ! isset($entry['row'])) {
                continue;
            }

            $rowNumber = is_numeric($entry['row']) ? (int) $entry['row'] : null;
            $data = $entry['data'] ?? null;

            if ($rowNumber === null || ! is_array($data)) {
                continue;
            }

            $clean = [];

            foreach ($data as $field => $value) {
                if (! array_key_exists($field, ProductFields::COLUMNS)) {
                    continue;
                }

                if ($value === null) {
                    continue;
                }

                if ($value === '') {
                    continue;
                }

                if (in_array($field, $numericFields, true)) {
                    if (! is_numeric($value)) {
                        continue;
                    }

                    $clean[$field] = (float) $value;

                    continue;
                }

                if (in_array($field, $wholeFields, true)) {
                    if (! is_numeric($value)) {
                        continue;
                    }

                    $clean[$field] = (int) round((float) $value);

                    continue;
                }

                if (in_array($field, $dateFields, true)) {
                    $date = $this->normaliseAiDate((string) $value);

                    if ($date === null) {
                        continue;
                    }

                    $clean[$field] = $date;

                    continue;
                }

                if (is_scalar($value)) {
                    $text = trim((string) $value);

                    if ($text !== '') {
                        $clean[$field] = $text;
                    }
                }
            }

            if ($clean !== []) {
                $corrections[$rowNumber] = $clean;
            }
        }

        return $corrections;
    }

    private function normaliseAiDate(string $value): ?string
    {
        $value = trim($value);

        foreach (['Y-m-d', 'm/d/Y', 'd/m/Y', 'M j, Y', 'F j, Y'] as $format) {
            $date = \DateTimeImmutable::createFromFormat($format, $value);

            if ($date !== false) {
                return $date->format('Y-m-d');
            }
        }

        $timestamp = strtotime($value);

        return $timestamp === false ? null : date('Y-m-d', $timestamp);
    }

    /**
     * Exposed for the view so the merchant knows what the AI is allowed to see.
     */
    public function modelName(): string
    {
        return Str::of(self::MODEL)->after('openai/')->toString();
    }
}
