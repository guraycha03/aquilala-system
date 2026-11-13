<?php

namespace App\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class AiLlmClient
{
    private const MODEL = 'openai/gpt-oss-20b';

    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    public const DEFAULT_MODEL = 'openai/gpt-oss-20b';

    public function provider(): string
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

    public function modelName(): string
    {
        if ($this->provider() === 'openrouter') {
            $model = trim((string) config('services.openrouter.model', self::DEFAULT_MODEL));

            return $model !== '' ? $model : self::DEFAULT_MODEL;
        }

        $model = trim((string) config('services.groq.model', self::MODEL));

        return $model !== '' ? $model : self::MODEL;
    }

    public function defaultModel(): string
    {
        if ($this->provider() === 'openrouter') {
            return $this->modelName();
        }

        $harvestOverride = trim((string) config('services.groq.harvest_model', ''));

        if ($harvestOverride !== '') {
            return $harvestOverride;
        }

        return $this->modelName();
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

        $provider = $this->provider();
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
                if ($provider === 'openrouter') {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . trim((string) config('services.openrouter.key', '')),
                        'Content-Type' => 'application/json',
                        'HTTP-Referer' => (string) config('app.url', 'https://aquilala.me'),
                        'X-Title' => 'Aquilala',
                    ])->withoutVerifying()->connectTimeout(15)->timeout($timeout)->post(
                        (string) config('services.openrouter.url', 'https://openrouter.ai/api/v1/chat/completions'),
                        $payload
                    );
                } else {
                    $response = Http::withHeaders([
                        'Authorization' => 'Bearer ' . (string) config('services.groq.key', ''),
                        'Content-Type' => 'application/json',
                    ])->withoutVerifying()->connectTimeout(15)->timeout($timeout)->post(
                        self::ENDPOINT,
                        $payload
                    );
                }
            } catch (ConnectionException $e) {
                Log::warning('Aquilala assistant could not reach the model provider.', [
                    'error' => $e->getMessage(),
                ]);

                return null;
            } catch (Throwable $e) {
                Log::warning('Aquilala assistant model call failed.', ['error' => $e->getMessage()]);
                return null;
            }

            if ($response->failed()) {
                Log::warning('Aquilala assistant model call rejected.', [
                    'status' => $response->status(),
                    'provider' => $provider,
                    'model' => $resolvedModel,
                    // A 429 is quota, not a broken answer — worth seeing in
                    // the log as itself, because it explains a burst of
                    // deterministic fallbacks.
                    'quota_exceeded' => $response->status() === 429,
                ]);

                return null;
            }

            $raw = (string) $response->json('choices.0.message.content', '');

            if (trim($raw) !== '') {
                return ['raw' => $raw, 'model' => $resolvedModel];
            }

            // Empty content: the reasoning budget consumed the whole limit.
            // One retry with more room before giving up to the fallback.
            if ($attempt === 1 && $response->json('choices.0.finish_reason') === 'length') {
                $budget = $maxTokens * 2;
                continue;
            }

            Log::warning('Aquilala assistant model returned empty content.', [
                'provider' => $provider,
                'model' => $resolvedModel,
                'finish_reason' => $response->json('choices.0.finish_reason'),
            ]);

            return null;
        }

        return null;
    }
}
