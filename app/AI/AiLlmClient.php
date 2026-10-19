<?php

namespace App\AI;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * AiLlmClient
 *
 * The only place the assistant talks to a model provider.
 *
 * Isolated here so the rest of the pipeline can be tested without a network,
 * and so a provider outage degrades to Laravel's deterministic answer instead
 * of an error page. A null return is a normal outcome, not an exception: the
 * caller always has its own wording ready.
 */
class AiLlmClient
{
    private const MODEL = 'openai/gpt-oss-20b';

    private const ENDPOINT = 'https://api.groq.com/openai/v1/chat/completions';

    public function available(): bool
    {
        return (string) config('services.groq.key', '') !== '';
    }

    public function modelName(): string
    {
        return self::MODEL;
    }

    /**
     * @param  array<int, array{role: string, content: string}>  $messages
     * @param  int  $maxTokens
     * @param  int  $timeout  Seconds to wait — callers mid-request pass a
     *                        short one so a dead network degrades fast.
     * @return array{raw: string, model: string}|null
     */
    public function completeJson(array $messages, int $maxTokens = 1200, int $timeout = 60): ?array
    {
        if (! $this->available() || $messages === []) {
            return null;
        }

        try {
            $response = Http::withHeaders([
                'Authorization' => 'Bearer ' . config('services.groq.key'),
                'Content-Type'  => 'application/json',
            ])
                ->withoutVerifying()
                // A dead or slow network must degrade in seconds, not
                // minutes: the callers all have their own wording ready.
                ->connectTimeout(5)
                ->timeout($timeout)
                ->post(self::ENDPOINT, [
                    'model' => self::MODEL,
                    'messages' => $messages,
                    // Low temperature: this is a reporting task over given
                    // facts, not a creative one, and a stray temperature makes
                    // the same question answerable two different ways.
                    'temperature'     => 0.2,
                    'max_tokens'      => $maxTokens,
                    'response_format' => ['type' => 'json_object'],
                ]);
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
            ]);

            return null;
        }

        $raw = (string) $response->json('choices.0.message.content', '');

        if (trim($raw) === '') {
            return null;
        }

        return ['raw' => $raw, 'model' => self::MODEL];
    }
}
