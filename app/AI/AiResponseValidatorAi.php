<?php

namespace App\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AiResponseValidatorAi
 *
 * The second AI in the pipeline — AI2. While AiResponseValidator checks the
 * model's output against database facts in PHP, this class asks a second model
 * call to re-evaluate whether the answer actually addresses the user's
 * question, whether it dumps unrelated historical data, and whether the tone
 * acknowledges what the user asked.
 *
 * AI1 builds the answer. AI2 decides if it is fit to show. AI2 is the same
 * model family but is prompted to be stricter: it must judge relevance,
 * proportionality, and conversational awareness, not just factual grounding.
 *
 * A null return (provider down, bad JSON, timeout) means "could not verify",
 * and the caller keeps AI1's answer — the validator never blocks a good
 * answer, it only catches bad ones.
 */
class AiResponseValidatorAi
{
    private const MODEL = 'openai/gpt-oss-20b';

    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    private const OPENROUTER_ENDPOINT = 'https://openrouter.ai/api/v1/chat/completions';

    /**
     * The verdicts AI2 can return.
     */
    public const VERDICT_ACCEPT = 'accept';
    public const VERDICT_REVISE = 'revise';
    public const VERDICT_REJECT = 'reject';

    /**
     * The provider follows AI_PROVIDER, exactly like AI1 (AiLlmClient).
     *
     * AI2 must judge through the same door the answer came from: a
     * Groq-pinned validator under a switched provider calls a revoked key,
     * 403s on every answer, and leaves the quality gate silently off.
     */
    private function provider(): string
    {
        $provider = trim((string) config('services.ai.provider', 'groq'));

        return $provider !== '' ? $provider : 'groq';
    }

    public function available(): bool
    {
        if ($this->provider() === 'openrouter') {
            return trim((string) config('services.openrouter.key', '')) !== '';
        }

        return trim((string) config('services.groq.key', '')) !== '';
    }

    private function endpoint(): string
    {
        if ($this->provider() === 'openrouter') {
            $url = trim((string) config('services.openrouter.url', ''));

            return $url !== '' ? $url : self::OPENROUTER_ENDPOINT;
        }

        return self::ENDPOINT;
    }

    private function model(): string
    {
        if ($this->provider() === 'openrouter') {
            $model = trim((string) config('services.openrouter.model', ''));

            return $model !== '' ? $model : self::MODEL;
        }

        return self::MODEL;
    }

    /**
     * Ask the second model whether AI1's proposed answer is acceptable.
     *
     * @param  array<string, mixed>  $context  The verified context AI1 was shown
     * @return array{verdict: string, reason: string|null, suggested_answer: string|null}|null
     *         null when the provider cannot be reached or the output is unusable
     */
    public function validate(
        string $userMessage,
        string $userRole,
        string $ai1Answer,
        array $context = [],
    ): ?array {
        if (! $this->available()) {
            return null;
        }

        $provider = $this->provider();
        $model = $this->model();

        $headers = ['Content-Type' => 'application/json'];

        if ($provider === 'openrouter') {
            $headers['Authorization'] = 'Bearer ' . trim((string) config('services.openrouter.key', ''));
            $headers['HTTP-Referer'] = (string) config('app.url', 'https://aquilala.me');
            $headers['X-Title'] = 'Aquilala';
        } else {
            $headers['Authorization'] = 'Bearer ' . (string) config('services.groq.key', '');
        }

        $payload = [
            'model'       => $model,
            'messages'    => $this->messages($userMessage, $userRole, $ai1Answer, $context),
            'temperature' => 0.1,
            // Room for the reasoning pass as well as the verdict —
            // a tight budget returns empty content and the verdict
            // is silently dropped.
            'max_tokens'  => 1600,
        ];

        // Only reasoning models accept this. Sending it to a plain chat
        // model is how a provider switch turns into a 400.
        if (str_contains($model, 'gpt-oss')) {
            $payload['reasoning_effort'] = 'low';
        }

        try {
            $response = Http::withHeaders($headers)
                ->withoutVerifying()
                ->connectTimeout(15)
                ->timeout(30)
                ->post($this->endpoint(), $payload);
        } catch (ConnectionException $e) {
            Log::warning('AI2 validator could not reach the model provider.', [
                'error' => $e->getMessage(),
            ]);

            return null;
        } catch (Throwable $e) {
            Log::warning('AI2 validator model call failed.', ['error' => $e->getMessage()]);

            return null;
        }

        if ($response->failed()) {
            Log::warning('AI2 validator model call rejected.', [
                'status' => $response->status(),
                'provider' => $provider,
                'model' => $model,
            ]);

            return null;
        }

        $raw = (string) $response->json('choices.0.message.content', '');

        if (trim($raw) === '') {
            return null;
        }

        return $this->parse($raw);
    }

    /**
     * Build the two-turn prompt for AI2.
     *
     * @param  array<string, mixed>  $context
     * @return array<int, array{role: string, content: string}>
     */
    private function messages(string $userMessage, string $userRole, string $ai1Answer, array $context): array
    {
        $contextSummary = $this->summarizeContext($context);

        return [
            [
                'role'    => 'system',
                'content' => <<<PROMPT
You are AI2, a strict quality judge for an agricultural assistant called Aquilala.
AI1 has already answered a user's question. Your job is to decide whether AI1's
answer is acceptable to show the user, or whether it should be rejected.

You judge on three things only:

1. RELEVANCE — Does the answer actually respond to the user's question?
   If the user said "good evening" and the answer talks about harvest reports,
   that is a failure: the question was not answered at all.
   If the user asked about one year's harvest and the answer dumps every
   year from 2020 to now, that is a failure: the answer is not proportional.

2. NO UNRELATED DATA DUMPING — The answer must not reproduce a full database
   dump, a full historical table, or a multi-year summary unless the user
   explicitly asked for a full history. One or two key figures is fine; a
   complete chronology is not.

3. CONVERSATIONAL AWARENESS — Greetings, thanks, and identity questions get
   a human-like reply. The user should feel heard.

The user is a {$userRole}. Aquilala is a farmers' association system in
Bulan, Sorsogon, Philippines. The system holds product catalogs, cost
estimates, harvest reports, and farmer activity logs.

The verified context available to AI1 is summarized below. Use it to check
whether AI1's figures match what the system actually holds:

{$contextSummary}

Reply with ONLY a JSON object and nothing else:

{"verdict":"accept|revise|reject","reason":"one short sentence explaining why","suggested_answer":"a corrected short answer if revise or reject, otherwise null"}

Verdict rules:
- "accept" — the answer is relevant, proportional, and conversational. Show it.
- "revise" — the answer is mostly right but has a flaw (wrong tone, a minor
  inaccuracy, or a slightly off topic). Replace it with suggested_answer.
- "reject" — the answer does not answer the question, dumps unrelated data, or
  is about something the system does not cover. Replace it with
  suggested_answer: a short, direct, friendly answer to the user's question,
  written from the verified context. Only when the question is genuinely
  outside the system's scope, write one sentence saying what you can help
  with instead — never a lecture about limitations, and never a bare
  "I cannot answer that".

Never include markdown, code fences, or prose outside the JSON object.
PROMPT
            ],
            [
                'role'    => 'user',
                'content' => <<<PROMPT
USER'S QUESTION:
{$userMessage}

AI1'S PROPOSED ANSWER:
{$ai1Answer}

Decide now. Reply with only the JSON object.
PROMPT
            ],
        ];
    }

    /**
     * Reduce the full context to a short, readable summary so the validator
     * can fact-check without being overwhelmed by every row.
     *
     * @param  array<string, mixed>  $context
     * @return string
     */
    private function summarizeContext(array $context): string
    {
        $lines = [];

        if (isset($context['coverage'])) {
            $c = $context['coverage'];
            $lines[] = '- coverage: ' . (($c['reports'] ?? 0) . ' reports, '
                . ($c['farmers'] ?? 0) . ' farmers, '
                . 'crops: ' . implode(', ', $c['crops'] ?? [])
                . ', seasons: ' . implode(', ', $c['seasons'] ?? [])
                . ', first harvest: ' . ($c['first_harvest'] ?? 'none')
                . ', last harvest: ' . ($c['last_harvest'] ?? 'none'));
        }

        if (isset($context['totals'])) {
            $t = $context['totals'];
            $lines[] = '- totals: ' . (($t['yield_tons'] ?? 0) . ' MT yield, '
                . ($t['area_ha'] ?? 0) . ' ha, '
                . 'yield/ha: ' . ($t['yield_per_ha'] ?? 'n/a')
                . ', expenses: ₱' . ($t['expenses_php'] ?? 0));
        }

        if (isset($context['by_crop']) && is_array($context['by_crop'])) {
            $crops = array_map(fn (array $c) => $c['crop'] . ' (' . ($c['yield_per_ha'] ?? 'n/a') . ' MT/ha)', $context['by_crop']);
            $lines[] = '- by_crop: ' . implode('; ', $crops);
        }

        if (isset($context['top_farmers_by_yield']) && is_array($context['top_farmers_by_yield'])) {
            $farmers = array_map(fn (array $f) => $f['farmer'] . ' (' . ($f['yield_tons'] ?? 0) . ' MT)', array_slice($context['top_farmers_by_yield'], 0, 3));
            $lines[] = '- top_farmers_by_yield: ' . implode('; ', $farmers);
        }

        if (isset($context['recent_vs_previous'])) {
            $r = $context['recent_vs_previous'];
            $lines[] = '- recent_vs_previous: ' . ($r['recent_label'] ?? '?') . ' vs ' . ($r['previous_label'] ?? '?')
                . ', yield change: ' . ($r['yield_per_ha_change_pct'] ?? 'n/a') . '%';
        }

        if (isset($context['facts'])) {
            $f = $context['facts'];
            if (isset($f['crop']) && is_array($f['crop'])) {
                $cropName = $f['crop']['crop'] ?? $f['crop']['name'] ?? 'unknown';
                $lines[] = '- crop facts: ' . $cropName
                    . ', typical yield: ' . ($f['crop']['typical_yield_per_ha'] ?? 'n/a') . ' per ha';
            }
            if (isset($f['current_season']) && is_array($f['current_season'])) {
                $lines[] = '- current season: ' . ($f['current_season']['label'] ?? 'unknown');
            }
        }

        if (isset($context['products']) && is_array($context['products'])) {
            $lines[] = '- products retrieved: ' . count($context['products']) . ' item(s)';
        }

        if (isset($context['quotation'])) {
            $lines[] = '- quotation: ' . ($context['quotation']['quotation_number'] ?? 'none')
                . ', total: ₱' . ($context['quotation']['total'] ?? 0);
        }

        return $lines !== [] ? implode("\n", $lines) : '(no structured context was retrieved for this question)';
    }

    /**
     * Parse AI2's JSON output, tolerating fenced blocks and surrounding prose.
     *
     * @return array{verdict: string, reason: string|null, suggested_answer: string|null}|null
     */
    private function parse(?string $content): ?array
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
            if (preg_match('/\{.*\}/s', $content, $matches) === 1) {
                $decoded = json_decode($matches[0], true);
            }
        }

        if (! is_array($decoded)) {
            return null;
        }

        $verdict = trim((string) ($decoded['verdict'] ?? ''));

        if (! in_array($verdict, [self::VERDICT_ACCEPT, self::VERDICT_REVISE, self::VERDICT_REJECT], true)) {
            return null;
        }

        return [
            'verdict'            => $verdict,
            'reason'             => isset($decoded['reason']) ? trim((string) $decoded['reason']) : null,
            'suggested_answer'   => isset($decoded['suggested_answer']) ? trim((string) $decoded['suggested_answer']) : null,
        ];
    }
}
