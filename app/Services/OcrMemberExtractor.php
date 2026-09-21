<?php

namespace App\Services;

/**
 * Turns raw Tesseract output into conservative candidate member rows.
 *
 * Tesseract returns loose text, not database rows. This extractor only
 * fills a field when the surrounding line strongly suggests its meaning
 * (a PH mobile number, an e-mail address, "X years", a single-word place
 * entry). Anything uncertain is left blank and later surfaces in the OCR
 * review preview for the administrator, instead of being guessed.
 */
class OcrMemberExtractor
{
    /** Words that usually appear in scan headers/instructions, never in rows. */
    private const HEADER_WORDS = [
        'name', 'fullname', 'firstname', 'lastname', 'middlename', 'email',
        'address', 'contact', 'phone', 'mobile', 'number', 'no', 'barangay',
        'city', 'municipality', 'province', 'farm', 'land', 'member', 'members',
        'list', 'assoc', 'registration', 'date', 'birth', 'sex', 'role',
        'account', 'status', 'civil', 'record', 'hectares', 'hectare', 'ha',
        'registered', 'association', 'brgy', 'purok', 'sitio', 'of',
    ];

    /**
     * Convert raw OCR text into candidate member rows.
     *
     * @return list<array<string, string>>
     */
    public function extract(string $text): array
    {
        $records = [];
        $current = null;

        foreach ($this->normalizedLines($text) as $line) {
            $fields = $this->classify($line);

            if ($fields['name'] !== '') {
                if ($current !== null) {
                    $records[] = $current;
                }

                $current = [
                    'first_name' => $fields['first_name'],
                    'last_name' => $fields['last_name'],
                ];

                $this->attach($current, $fields);

                continue;
            }

            $hasSignal = $fields['email'] !== '' || $fields['phone'] !== '';

            if ($current === null) {
                if (! $hasSignal) {
                    // Stray line (scan header, label) before any record — ignore.
                    continue;
                }

                $current = [];
            }

            $this->attach($current, $fields);
        }

        if ($current !== null) {
            $records[] = $current;
        }

        return array_values(array_filter($records, fn (array $record) => $record !== []));
    }

    /**
     * Fill the first still-empty field of $record from $fields.
     *
     * @param  array<string, string>  $record
     * @param  array<string, string>  $fields
     */
    private function attach(array &$record, array $fields): void
    {
        foreach (['email', 'phone', 'barangay', 'years_farming'] as $key) {
            if (($record[$key] ?? '') === '' && ($fields[$key] ?? '') !== '') {
                $record[$key] = $fields[$key];
            }
        }
    }

    /**
     * Split text into cleaned OCR lines, dropping scan headers/footers.
     *
     * @return list<string>
     */
    private function normalizedLines(string $text): array
    {
        $text = preg_replace('/^\xEF\xBB\xBF/', '', $text);
        $lines = preg_split('/\r?\n/u', $text) ?: [];

        $out = [];
        foreach ($lines as $line) {
            $line = trim($line);

            if ($line === '') {
                continue;
            }

            // "Page 1 of 5" style scan footers.
            if (preg_match('/\bpage\s*\d+\s*of\b/i', $line)) {
                continue;
            }

            $out[] = $this->stripLeadNoise($line);
        }

        return $out;
    }

    private function stripLeadNoise(string $line): string
    {
        // Remove list markers that open a row ("1. ", "2)", "•", "›"), but keep
        // plain digit runs and decimals (phone numbers, areas) intact.
        return preg_replace(
            '/^\s*(?:(?:\d{1,4}\.(?!\d)|\d{1,4}[)\-:]|[•›▪►●–—])|\d{1,4}\s*[•›▪►●–—])\s*/u',
            ' ',
            $line
        ) ?? $line;
    }

    /**
     * Classify a single OCR line into detected fields.
     *
     * @return array<string, string>
     */
    private function classify(string $line): array
    {
        $fields = [
            'name' => '', 'first_name' => '', 'last_name' => '',
            'email' => '', 'phone' => '', 'barangay' => '', 'years_farming' => '',
        ];

        $fields['email'] = $this->extractEmail($line);
        $fields['phone'] = $this->extractPhone($line);

        $rest = $line;
        if ($fields['email'] !== '') {
            $rest = str_replace($fields['email'], ' ', $rest);
        }
        if ($fields['phone'] !== '') {
            $rest = str_replace($fields['phone'], ' ', $rest);
        }
        $rest = trim((string) preg_replace('/\s+/u', ' ', $rest));

        if (preg_match('/(\d{1,3})\s*(?:years?|yrs?)\b/i', $rest, $m)) {
            $fields['years_farming'] = (string) (int) $m[1];
            $rest = trim(str_replace($m[0], ' ', $rest));
        }

        // Farm-area lines ("1.5 hectares", "2 ha") carry no name — ignore them
        // rather than misreading the unit cluster as a person.
        if ($rest !== '' && preg_match('/^\d+(?:\.\d+)?\s*(?:ha\.?|hectares?|sqm|sq\.?\s*m\b|\bft|\bm2)\b/i', $rest)) {
            return $fields;
        }

        if ($rest === '') {
            return $fields;
        }

        // With explicit separators, only the first segment can be a name.
        if (preg_match('/[,|]/', $rest)) {
            $rest = trim(explode(',', preg_replace('/[,|]/', ',', $rest))[0]);
            if ($rest === '') {
                return $fields;
            }
        }

        $tokens = preg_split('/\s+/u', $rest, -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($tokens === []) {
            return $fields;
        }

        // Header-like text ("Name", "EMAIL ADDRESS", "List of Members", …).
        if ($this->areAllHeaderWords($tokens) || $this->isNumericOnly($rest)) {
            return $fields;
        }

        $wordCount = count($tokens);

        if ($wordCount >= 2) {
            $firstName = $this->titleCase(implode(' ', array_slice($tokens, 0, -1)));
            $lastName = $this->titleCase(end($tokens));
            $fields['name'] = trim(implode(' ', [$firstName, $lastName]));
            $fields['first_name'] = $firstName;
            $fields['last_name'] = $lastName;

            return $fields;
        }

        // A single token: treat it as a place (barangay) only when it is
        // capitalised — lower-case single tokens are usually OCR fragments.
        if ($this->isLikelyPlace($tokens[0])) {
            $fields['barangay'] = $this->titleCase($tokens[0]);
        }

        return $fields;
    }

    private function extractEmail(string $line): string
    {
        if (preg_match('/[A-Za-z0-9._%+\-]+@[A-Za-z0-9.\-]+\.[A-Za-z]{2,24}/', $line, $m)) {
            return strtolower(trim($m[0]));
        }

        return '';
    }

    private function extractPhone(string $line): string
    {
        // PH mobile: 0917… / 63917… / +63917… optionally spaced or dashed.
        if (preg_match('/(?<!\d)((?:\+?63|0)?9[0-9][\d\s.\-]{7,12})(?!\d)/', $line, $m)) {
            $digits = preg_replace('/\D/', '', $m[1]);

            if (preg_match('/^(639\d{9}|09\d{9}|9\d{9})$/', $digits)) {
                return trim($m[1]);
            }
        }

        return '';
    }

    private function isLikelyPlace(string $token): bool
    {
        return preg_match('/^[\p{Lu}][\pL\-\'’]{2,30}$/u', $token) === 1
            || preg_match('/^[\p{Lu}][\p{Lu}\-\'’]{2,30}$/u', $token) === 1;
    }

    private function titleCase(string $value): string
    {
        $value = trim((string) preg_replace('/\s+/u', ' ', $value));

        return $value !== '' ? ucwords(strtolower($value)) : '';
    }

    private function isNumericOnly(string $value): bool
    {
        return preg_match('/^[\d\s.,\/\-:]+$/u', $value) === 1;
    }

    /**
     * @param  list<string>  $tokens
     */
    private function areAllHeaderWords(array $tokens): bool
    {
        if ($tokens === []) {
            return false;
        }

        foreach ($tokens as $token) {
            $letters = strtolower((string) preg_replace('/[^a-z]/i', '', $token));
            if ($letters === '' || ! in_array($letters, self::HEADER_WORDS, true)) {
                return false;
            }
        }

        return true;
    }
}