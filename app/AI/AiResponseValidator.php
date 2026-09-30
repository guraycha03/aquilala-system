<?php

namespace App\AI;

use App\AI\Prompts\AquilalaAssistantPrompt;

/**
 * AiResponseValidator
 *
 * The last gate between the model and the farmer.
 *
 * The model is asked for JSON, but a request is not a guarantee. Everything
 * that comes back is treated as hostile until checked against the context that
 * was actually retrieved:
 *
 *   - a product id that was not retrieved is discarded, not displayed,
 *   - a price that disagrees with the database is discarded, and the
 *     deterministic answer is used instead,
 *   - a URL in the prose is stripped, because links come from the router,
 *   - an unknown response type falls back to a plain answer,
 *   - unparseable output falls back to Laravel's own wording.
 *
 * A rejected model response is not an error. The assistant has a deterministic
 * answer for every intent, so the worst case is a plainer sentence.
 */
class AiResponseValidator
{
    public const VIOLATION_UNKNOWN_PRODUCT = 'unknown_product_id';

    public const VIOLATION_PRICE_MISMATCH = 'price_mismatch';

    public const VIOLATION_FABRICATED_URL = 'fabricated_url';

    public const VIOLATION_UNKNOWN_TYPE = 'unknown_response_type';

    public const VIOLATION_MALFORMED = 'malformed_response';

    public const VIOLATION_UNVERIFIED_PRODUCT = 'unverified_product_name';

    public const VIOLATION_UNVERIFIED_MONEY = 'unverified_amount';

    /**
     * @param  array<string, mixed>  $decoded   The model's parsed JSON.
     * @param  array<int, array<string, mixed>>  $products  Cards built from the database.
     * @param  array<int, string>  $extraTrustedNames  Approved names from outside
     *         the product cards — crop names, chiefly. These are rows in the
     *         application's own crop directory, so naming one is not a
     *         hallucination, and refusing to let the model say "Mango" would
     *         reject correct prose over a word the system itself supplied.
     * @return array{
     *     ok: bool,
     *     type: string,
     *     answer: string,
     *     product_ids: array<int, int>,
     *     needs_clarification: bool,
     *     clarification_question: string|null,
     *     violations: array<int, string>
     * }
     */
    public function validate(array $decoded, array $products, ?string $deterministicAnswer = null, array $extraTrustedNames = []): array
    {
        $violations = [];

        $trustedIds = [];
        $trustedPrices = [];
        $trustedNames = [];

        foreach ($products as $card) {
            $id = isset($card['id']) ? (int) $card['id'] : null;

            if ($id === null) {
                continue;
            }

            $trustedIds[$id] = true;
            $trustedPrices[(string) $card['display_name']] = (float) ($card['price_value'] ?? 0);
            $trustedPrices[(string) $card['name']] = (float) ($card['price_value'] ?? 0);

            foreach (['display_name', 'name', 'brand', 'category', 'store', 'package'] as $field) {
                $value = (string) ($card[$field] ?? '');

                if (trim($value) !== '') {
                    $trustedNames[] = $value;
                }
            }
        }

        foreach ($extraTrustedNames as $name) {
            $name = trim((string) $name);

            if ($name !== '') {
                $trustedNames[] = $name;
            }
        }

        // ── Response type ──────────────────────────────────────────
        $type = is_string($decoded['type'] ?? null) ? trim($decoded['type']) : '';

        if (! in_array($type, AquilalaAssistantPrompt::responseTypes(), true)) {
            $violations[] = self::VIOLATION_UNKNOWN_TYPE;
            $type = 'answer';
        }

        // ── Product ids ────────────────────────────────────────────
        $requested = $decoded['product_ids'] ?? [];
        $requested = is_array($requested) ? $requested : [];

        $productIds = [];
        $unknown = [];

        foreach ($requested as $id) {
            if (! is_numeric($id)) {
                $unknown[] = (string) $id;

                continue;
            }

            $id = (int) $id;

            if (isset($trustedIds[$id])) {
                $productIds[$id] = true;
            } else {
                $unknown[] = (string) $id;
            }
        }

        if ($unknown !== []) {
            $violations[] = self::VIOLATION_UNKNOWN_PRODUCT;
        }

        // When the model asked for nothing, or asked only for things that do
        // not exist, the retrieved products are still the answer. The catalog
        // lookup already happened; discarding it because the prose was wrong
        // would make the failure worse for no reason.
        if ($productIds === [] && $products !== []) {
            foreach ($trustedIds as $id => $_) {
                $productIds[$id] = true;
            }
        }

        // ── Answer text ────────────────────────────────────────────
        $answer = is_string($decoded['answer'] ?? null) ? trim($decoded['answer']) : '';

        $stripped = $this->stripUrls($answer);

        if ($stripped !== $answer) {
            $violations[] = self::VIOLATION_FABRICATED_URL;
            $answer = $stripped;
        }

        // A brand that is not in the context is a product that does not exist.
        // The model is not supposed to introduce one, and a substituted
        // suggestion reads to a farmer as a recommendation.
        if ($this->mentionsUnverifiedName($answer, $trustedNames)) {
            $violations[] = self::VIOLATION_UNVERIFIED_PRODUCT;
        }

        // Every peso amount in the prose must be one Laravel itself printed. A
        // total, a subtotal or a line price that the database and the rule
        // engine never produced is a calculation the model did on its own.
        if ($this->hasUnverifiedAmount($answer, $deterministicAnswer)) {
            $violations[] = self::VIOLATION_UNVERIFIED_MONEY;
        }

        // A price that contradicts the database is the single most damaging
        // thing a model can say here, because a farmer may act on it. Drop the
        // prose and use Laravel's own wording.
        if ($this->hasPriceConflict($answer, $trustedPrices)) {
            $violations[] = self::VIOLATION_PRICE_MISMATCH;
            $answer = $deterministicAnswer ?? '';
        }

        if ($answer === '' && $deterministicAnswer !== null) {
            $answer = $deterministicAnswer;
        }

        // ── Clarification ──────────────────────────────────────────
        $question = is_string($decoded['clarification_question'] ?? null)
            ? trim($decoded['clarification_question'])
            : '';

        $question = $this->stripUrls($question);

        $needsClarification = (bool) ($decoded['needs_clarification'] ?? false) || $question !== '';

        // ── Nothing usable came back ───────────────────────────────
        if ($answer === '' && $question === '' && $deterministicAnswer === null) {
            $violations[] = self::VIOLATION_MALFORMED;
        }

        return [
            'ok'                     => $violations === [],
            'type'                   => $type,
            'answer'                 => $answer,
            'product_ids'            => array_map('intval', array_keys($productIds)),
            'needs_clarification'    => $needsClarification,
            'clarification_question' => $question === '' ? null : $question,
            'violations'             => array_values(array_unique($violations)),
        ];
    }

    /**
     * Parse the model's raw content, tolerating a fenced block and an
     * occasional sentence wrapped around the object.
     *
     * @return array<string, mixed>|null
     */
    public function parse(?string $content): ?array
    {
        if ($content === null || trim($content) === '') {
            return null;
        }

        $content = trim($content);

        if (str_starts_with($content, '```')) {
            $content = (string) preg_replace('/^```[a-z]*\s*|\s*```$/i', '', $content);
        }

        $decoded = json_decode($content, true);

        if (! is_array($decoded)) {
            // The model sometimes narrates before the object. Take the object.
            if (preg_match('/\{.*\}/s', $content, $matches) === 1) {
                $decoded = json_decode($matches[0], true);
            }
        }

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Remove anything that looks like a link.
     *
     * Bare domains, http(s) links and site-rooted paths all go. A product
     * reference the farmer can act on is rendered by the application from
     * `route()`; a model-supplied link would be a fabricated destination at
     * best and an attack target at worst.
     */
    private function stripUrls(string $text): string
    {
        if ($text === '') {
            return $text;
        }

        $patterns = [
            '~\bhttps?://\S+~i',
            '~\bwww\.[a-z0-9-]+\.[a-z]{2,}(?:/\S*)?~i',
            // Site-rooted paths, e.g. /farmer/products/12 or /products/12
            '~(?<![\w/])/(?:farmer|store|admin|products)/[\w\-/]*~i',
        ];

        foreach ($patterns as $pattern) {
            $text = (string) preg_replace($pattern, '', $text);
        }

        return trim((string) preg_replace('/[ \t]{2,}/', ' ', $text));
    }

    /**
     * Does the prose name something that is not in the retrieved context?
     *
     * Multi-word capitalised phrases are the unit here, because that is the
     * shape a fabricated product name takes: "Bio-Organic Booster XL". A
     * retrieved product, brand, category, package or store name is fine, as is
     * any single word that appears inside one of them — "urea" is a part of
     * "Premium Urea 46-0-0", and rejecting that would reject the ordinary case.
     *
     * The check is deliberately blunt. A false positive costs the farmer the
     * model's phrasing and leaves Laravel's own wording in place; a false
     * negative puts a product in Aquilala that nobody stocks.
     *
     * @param  array<int, string>  $trustedNames
     */
    private function mentionsUnverifiedName(string $answer, array $trustedNames): bool
    {
        if ($answer === '' || $trustedNames === []) {
            return false;
        }

        $haystack = mb_strtolower(implode(' | ', $trustedNames));

        // Two or more capitalised words, optionally broken by a hyphen or a
        // number: "Bio-Organic Booster XL", "Complete NPK Blend".
        preg_match_all(
            '/\b[\p{Lu}][\p{L}\p{N}]*(?:[\s\-][\p{Lu}\p{N}][\p{L}\p{N}]*)+\b/u',
            $answer,
            $matches
        );

        foreach ($matches[0] as $phrase) {
            if (mb_stripos($haystack, mb_strtolower($phrase)) !== false) {
                continue;
            }

            return true;
        }

        return false;
    }

    /**
     * Does the prose quote a peso amount Laravel did not?
     *
     * The reference is Laravel's own deterministic answer, which is assembled
     * from database prices and the rule engine's output. Any amount in the
     * model's wording that is absent from it was produced somewhere else.
     */
    private function hasUnverifiedAmount(string $answer, ?string $reference): bool
    {
        if ($answer === '' || $reference === null || $reference === '') {
            return false;
        }

        $claimed = $this->moneyAmounts($answer);
        $known = $this->moneyAmounts($reference);

        if ($claimed === []) {
            return false;
        }

        return array_diff($claimed, $known) !== [];
    }

    /**
     * Currency-marked amounts in a piece of prose, normalised to floats.
     *
     * @return array<int, float>
     */
    private function moneyAmounts(string $text): array
    {
        // ₱1,450 / PHP 1450 / PhP 1,450 / peso 1450 / pesos: 1,450
        $money = '(?:₱\s*|(?:PHP|PhP|php)\s*|pesos?\s*:?\s*)(\d{1,3}(?:,\d{3})+(?:\.\d{1,2})?|\d+(?:\.\d{1,2})?)';

        if (preg_match_all('/' . $money . '/u', $text, $matches) === 0) {
            return [];
        }

        return array_map(
            static fn ($match) => round((float) str_replace(',', '', $match), 2),
            $matches[1]
        );
    }

    /**
     * Does the prose state a peso amount for a product that the database
     * disagrees with?
     *
     * Deliberately narrow. Only an amount carrying an explicit currency marker
     * and sitting just after a product name we actually retrieved is treated
     * as a price claim for that product. Everything else in the sentence — a
     * quantity, a hectare count, a line subtotal, the quotation total — is
     * left alone, because those come from the rule engine and checking them
     * here would reject correct answers.
     *
     * @param  array<string, float>  $trustedPrices  Product name => database price.
     */
    private function hasPriceConflict(string $answer, array $trustedPrices): bool
    {
        if ($answer === '' || $trustedPrices === []) {
            return false;
        }

        foreach ($trustedPrices as $name => $price) {
            $name = trim((string) $name);

            if ($name === '' || mb_strlen($name) < 3) {
                continue;
            }

            if (mb_stripos($answer, $name) === false) {
                continue;
            }

            foreach ($this->amountsAfter($answer, $name) as $claimed) {
                // Half a peso of tolerance absorbs rounding in prose without
                // letting a materially different price through.
                if (abs($claimed - $price) > 0.5) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Currency-marked amounts appearing shortly after each mention of a name.
     *
     * @return array<int, float>
     */
    private function amountsAfter(string $answer, string $name): array
    {
        // ₱1,450 / PHP 1450 / PhP 1,450 / peso 1450 / pesos: 1,450
        $money = '(?:₱\s*|(?:PHP|PhP|php)\s*|pesos?\s*:?\s*)(\d{1,3}(?:,\d{3})+(?:\.\d{1,2})?|\d+(?:\.\d{1,2})?)';

        $offset = 0;
        $amounts = [];

        while (($position = mb_stripos($answer, $name, $offset)) !== false) {
            // Sixty characters is enough to cover "is ₱1,450 per sack" and
            // short enough that a price quoted for the *next* product in a list
            // is not attributed to this one.
            $window = mb_substr($answer, $position, mb_strlen($name) + 60);

            if (preg_match_all('/' . $money . '/u', $window, $matches) > 0) {
                foreach ($matches[1] as $match) {
                    $amounts[] = (float) str_replace(',', '', $match);
                }
            }

            $offset = $position + mb_strlen($name);
        }

        return $amounts;
    }
}
