<?php

namespace App\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Aquilala's connection to the model provider.
 *
 * Two providers are configured (Groq and OpenRouter), each with its own key
 * in `.env`. `AI_PROVIDER` picks the primary. The assistant never stops at
 * one dead door: when the primary cannot be reached or rejects the request,
 * the secondary provider is tried once with its own key and model before the
 * call gives up. A provider that is down, rate-limited, or missing a key no
 * longer turns the assistant into an "AI offline" message — it falls through
 * to the deterministic, database-verified answer instead.
 */
class AiLlmClient
{
    private const MODEL = 'openai/gpt-oss-20b';

    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    private const OTHER_PROVIDER = 'openrouter';

    public const DEFAULT_MODEL = 'openai/gpt-oss-20b';

    public function provider(): string
    {
        $provider = trim((string) config('services.ai.provider', 'groq'));

        return $provider !== '' ? $provider : 'groq';
    }

    /**
     * The other provider — the one that is tried when the primary is down.
     */
    public function otherProvider(): string
    {
        return $this->provider() === 'openrouter' ? 'groq' : 'openrouter';
    }

    /**
     * True when at least one configured provider can be called.
     */
    public function available(): bool
    {
        return $this->hasKey('groq') || $this->hasKey('openrouter');
    }

    /**
     * Whether a named provider has a usable key.
     */
    public function hasKey(string $provider): bool
    {
        if ($provider === 'openrouter') {
            return trim((string) config('services.openrouter.key', '')) !== '';
        }

        return trim((string) config('services.groq.key', '')) !== '';
    }

    public function modelName(?string $provider = null): string
    {
        $provider = $provider ?? $this->provider();

        if ($provider === 'openrouter') {
            $model = trim((string) config('services.openrouter.model', self::DEFAULT_MODEL));

            return $model !== '' ? $model : self::DEFAULT_MODEL;
        }

        $model = trim((string) config('services.groq.model', self::MODEL));

        return $model !== '' ? $model : self::MODEL;
    }

    public function defaultModel(): string
    {
        if ($this->provider() === 'openrouter') {
            return $this->modelName($this->provider());
        }

        $harvestOverride = trim((string) config('services.groq.harvest_model', ''));

        if ($harvestOverride !== '') {
            return $harvestOverride;
        }

        return $this->modelName($this->provider());
    }

    /**
     * Call the model and return its raw JSON-ish content, or null.
     *
     * The default budget is generous on purpose: this is a reasoning model,
     * and a tight budget gets spent on reasoning with nothing left for the
     * answer — an empty `content` that used to collapse into the generic
     * fallback the users saw as "I can't answer that". When that happens the
     * call is retried once with double the budget.
     *
     * The primary provider (`AI_PROVIDER`) is tried first. If it cannot be
     * reached, rejects the request, or returns nothing usable, the secondary
     * provider is tried once before giving up.
     *
     * @param  array<int, array{role: string, content: string}>  $messages
     * @return array{raw: string, model: string}|null
     */
    public function completeJson(array $messages, int $maxTokens = 2400, int $timeout = 60, ?string $model = null): ?array
    {
        if (! $this->available() || $messages === []) {
            return null;
        }

        $resolvedModel = is_string($model) && trim($model) !== ''
            ? trim($model)
            : $this->defaultModel();

        $thinkingDelay = max(0, (int) config('services.ai.thinking_delay_ms', 0));

        if ($thinkingDelay > 0) {
            usleep($thinkingDelay * 1000);
        }

        $primary = $this->provider();
        $secondary = $this->otherProvider();

        // Try the primary provider first, then the secondary once. Each
        // provider gets its own key, endpoint and model so a revoked key on
        // one side does not silence the assistant entirely.
        foreach ([$primary, $secondary] as $provider) {
            if (! $this->hasKey($provider)) {
                continue;
            }

            $budget = $maxTokens;

            for ($attempt = 1; $attempt <= 2; $attempt++) {
                $payload = [
                    'model' => $resolvedModel,
                    'messages' => $messages,
                    'temperature' => 0.2,
                    'max_tokens' => $budget,
                ];

                // Only reasoning models accept this. Sending it to a plain
                // chat model is how a provider switch turns into a 400.
                if (str_contains($resolvedModel, 'gpt-oss')) {
                    $payload['reasoning_effort'] = 'low';
                }

                try {
                    $response = $this->post($provider, $payload, $timeout);
                } catch (ConnectionException $e) {
                    Log::warning('Aquilala assistant could not reach the model provider.', [
                        'provider' => $provider,
                        'error' => $e->getMessage(),
                    ]);

                    continue 2;
                } catch (Throwable $e) {
                    Log::warning('Aquilala assistant model call failed.', [
                        'provider' => $provider,
                        'error' => $e->getMessage(),
                    ]);

                    continue 2;
                }

                if ($response->failed()) {
                    Log::warning('Aquilala assistant model call rejected.', [
                        'provider' => $provider,
                        'status' => $response->status(),
                        'model' => $resolvedModel,
                        'quota_exceeded' => $response->status() === 429,
                    ]);

                    break; // provider reachable but refused — try secondary
                }

                $raw = (string) $response->json('choices.0.message.content', '');

                if (trim($raw) !== '') {
                    return ['raw' => $raw, 'model' => $resolvedModel];
                }

                // Empty content: the reasoning budget consumed the whole limit.
                // One retry with more room before giving up on this provider.
                if ($attempt === 1 && $response->json('choices.0.finish_reason') === 'length') {
                    $budget = $maxTokens * 2;
                    continue;
                }

                Log::warning('Aquilala assistant model returned empty content.', [
                    'provider' => $provider,
                    'model' => $resolvedModel,
                    'finish_reason' => $response->json('choices.0.finish_reason'),
                ]);

                break;
            }
        }

        return null;
    }

    /**
     * POST to a named provider with its own key and endpoint.
     */
    private function post(string $provider, array $payload, int $timeout)
    {
        $headers = [
            'Authorization' => 'Bearer ' . $this->key($provider),
            'Content-Type' => 'application/json',
        ];

        if ($provider === 'openrouter') {
            $headers['HTTP-Referer'] = (string) config('app.url', 'https://aquilala.me');
            $headers['X-Title'] = 'Aquilala';

            return Http::withHeaders($headers)
                ->withoutVerifying()
                ->connectTimeout(15)
                ->timeout($timeout)
                ->post((string) config('services.openrouter.url', 'https://openrouter.ai/api/v1/chat/completions'), $payload);
        }

        return Http::withHeaders($headers)
            ->withoutVerifying()
            ->connectTimeout(15)
            ->timeout($timeout)
            ->post(self::ENDPOINT, $payload);
    }

    /**
     * The bearer key for a named provider.
     */
    private function key(string $provider): string
    {
        if ($provider === 'openrouter') {
            return trim((string) config('services.openrouter.key', ''));
        }

        return trim((string) config('services.groq.key', ''));
    }
}
