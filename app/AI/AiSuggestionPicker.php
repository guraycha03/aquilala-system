<?php

namespace App\AI;

use Illuminate\Support\Facades\Cache;
use Throwable;

/**
 * AiSuggestionPicker
 *
 * When the model provider is reachable, asks it to choose the best single
 * product for each job (role) from an auto-include candidate list — the
 * best seed, the best fertilizer, one weed control — instead of quoting
 * every product configured for the crop.
 *
 * The quotation never depends on it: a missing key, a timeout, a rejected
 * call, or a nonsense reply all return null and the engine falls back to
 * its deterministic best-per-role ranking. Null is an expected outcome,
 * not an error — this class must never throw.
 */
class AiSuggestionPicker
{
    /** Ids only — keep the reply tiny and the wait short; the farmer is mid-form. */
    private const MAX_TOKENS = 512;

    private const TIMEOUT_SECONDS = 15;

    private const CACHE_MINUTES = 15;

    public function __construct(private readonly AiLlmClient $llm) {}

    /**
     * @param  array<int, array{id: int, name: string, category: string, role: string, price: float, stock: int, times_quoted: int}>  $candidates
     * @return array<int, int>|null  Validated product ids (at most one per role), or null to fall back.
     */
    public function pick(array $candidates, string $cropType, string $season): ?array
    {
        try {
            if (! $this->llm->available() || count($candidates) < 2) {
                return null;
            }

            // Same crop, season and candidates usually mean the same answer;
            // cache the valid reply so repeat quotations do not pay the call.
            $cacheKey = 'ai_input_picks:' . md5(json_encode([
                strtolower($cropType),
                $season,
                array_column($candidates, 'id'),
            ]));

            $cached = Cache::get($cacheKey);
            if (is_array($cached) && $cached !== []) {
                return $cached;
            }

            $completion = $this->llm->completeJson(
                $this->messages($candidates, $cropType, $season),
                self::MAX_TOKENS,
                self::TIMEOUT_SECONDS
            );

            if ($completion === null) {
                return null;
            }

            $picks = $this->validate(json_decode($completion['raw'], true), $candidates);

            if ($picks !== null) {
                Cache::put($cacheKey, $picks, now()->addMinutes(self::CACHE_MINUTES));
            }

            return $picks;
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array<int, array{role: string, content: string}>
     */
    private function messages(array $candidates, string $cropType, string $season): array
    {
        $system = <<<'PROMPT'
You choose farm inputs for an auto-generated cost quotation.

From the candidate list, pick the SINGLE best product for each job (role) for this crop and season — the best seed, the best fertilizer, one weed control, one fungicide, and so on. A quotation must present the strongest candidate per job, never several products competing for the same job.

Rules:
- Choose ONLY product ids that appear in the candidates. Never invent ids, names, or numbers.
- Never pick two products that share the same role.
- Prefer in-stock products. Among equals, prefer the one farmers quote most, then the cheaper one.
- Skip a job entirely when nothing in it fits this crop.
- Reply with JSON only, in exactly this shape: {"product_ids":[1,5]}
- No prose, no markdown, no explanation.
PROMPT;

        return [
            ['role' => 'system', 'content' => $system],
            ['role' => 'user', 'content' => json_encode([
                'crop' => $cropType,
                'season' => $season,
                'candidates' => $candidates,
            ], JSON_UNESCAPED_UNICODE)],
        ];
    }

    /**
     * The reply only counts if every id is a real candidate and no job is
     * filled twice. Anything else is distrusted whole — the engine then
     * uses its deterministic ranking rather than half of a guess.
     *
     * @param  mixed  $payload  Decoded reply (may be garbage).
     * @return array<int, int>|null
     */
    private function validate(mixed $payload, array $candidates): ?array
    {
        if (! is_array($payload) || ! is_array($payload['product_ids'] ?? null) || $payload['product_ids'] === []) {
            return null;
        }

        $byId = array_column($candidates, null, 'id');
        $filledRoles = [];
        $picks = [];

        foreach ($payload['product_ids'] as $id) {
            if (! is_int($id)) {
                return null;
            }
            if (! isset($byId[$id])) {
                return null;
            }

            $role = $byId[$id]['role'];
            if (isset($filledRoles[$role])) {
                continue; // one product per job — keep the first it chose
            }
            $filledRoles[$role] = true;
            $picks[] = $id;
        }

        return $picks === [] ? null : $picks;
    }
}
