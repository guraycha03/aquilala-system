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
        $provider = trim((string) env('AI_PROVIDER', 'groq'));

        return $provider !== '' ? $provider : 'groq';
    }

    public function available(): bool
    {
        if ($this->provider() === 'openrouter') {
            return trim((string) env('OPENROUTER_API_KEY', '')) !== '';
        }

        return trim((string) config('services.groq.key', '')) !== '';
    }

    public function modelName(): string
    {
        if ($this->provider() === 'openrouter') {
            return trim((string) env('OPENROUTER_MODEL', 'openai/gpt-oss-20b')) ?: 'openai/gpt-oss-20b';
        }

        return self::MODEL;
    }

    public function defaultModel(): string
    {
        if ($this->provider() === 'openrouter') {
            return $this->modelName();
        }

        $override = trim((string) env('GROQ_MODEL_HARVEST', ''));

        if ($override !== '') {
            return $override;
        }

        $single = trim((string) env('GROQ_MODEL', ''));

        if ($single !== '') {
            return $single;
        }

        return self::MODEL;
    }

    public function completeJson(array $messages, int $maxTokens = 1200, int $timeout = 60, ?string $model = null): ?array
    {
        if (! $this->available() || $messages === []) {
            return null;
        }

        $resolvedModel = is_string($model) && trim($model) !== ''
            ? trim($model)
            : $this->defaultModel();

        $thinkingDelay = max(0, (int) env('AI_THINKING_DELAY_MS', 0));

        if ($thinkingDelay > 0) {
            usleep($thinkingDelay * 1000);
        }

        $provider = $this->provider();

        try {
            if ($provider === 'openrouter') {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . trim((string) env('OPENROUTER_API_KEY', '')),
                    'Content-Type' => 'application/json',
                    'HTTP-Referer' => (string) env('APP_URL', 'https://aquilala.me'),
                    'X-Title' => 'Aquilala',
                ])->withoutVerifying()->connectTimeout(5)->timeout($timeout)->post(
                    'https://openrouter.ai/api/v1/chat/completions',
                    [
                        'model' => $resolvedModel,
                        'messages' => $messages,
                        'temperature' => 0.2,
                        'max_tokens' => $maxTokens,
                    ]
                );
            } else {
                $response = Http::withHeaders([
                    'Authorization' => 'Bearer ' . (string) config('services.groq.key', ''),
                    'Content-Type' => 'application/json',
                ])->withoutVerifying()->connectTimeout(5)->timeout($timeout)->post(
                    self::ENDPOINT,
                    [
                        'model' => $resolvedModel,
                        'messages' => $messages,
                        'temperature' => 0.2,
                        'max_tokens' => $maxTokens,
                    ]
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
            ]);

            return null;
        }

        $raw = (string) $response->json('choices.0.message.content', '');

        if (trim($raw) === '') {
            return null;
        }

        return ['raw' => $raw, 'model' => $resolvedModel];
    }
}
