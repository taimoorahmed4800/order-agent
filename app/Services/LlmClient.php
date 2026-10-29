<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Calls any OpenAI-compatible chat API (Gemini, Groq, OpenRouter, Ollama...).
 */
class LlmClient
{
    /**
     * Send messages (and optionally tools) and return the assistant message
     * EXACTLY as the API sent it. We keep it unchanged because some providers
     * (like Gemini) add hidden fields that must be sent back in the next call.
     *
     * @return array<string, mixed>
     */
    public function completion(array $messages, array $tools = []): array
    {
        $baseUrl = rtrim((string) config('services.ai.base_url'), '/');
        $model   = config('services.ai.model');

        if (! $baseUrl || ! $model) {
            throw new RuntimeException('AI_BASE_URL and AI_MODEL must be set in .env');
        }

        $payload = [
            'model'       => $model,
            'temperature' => 0.2,
            'max_tokens'  => 2000,
            'messages'    => $messages,
        ];

        if ($tools) {
            $payload['tools']       = $tools;
            $payload['tool_choice'] = 'auto';
        }

        $request = Http::timeout(60)->retry(3, 2000, function ($exception) {
            if ($exception instanceof ConnectionException) {
                return true;
            }

            return $exception instanceof RequestException
                && in_array($exception->response->status(), [429, 500, 502, 503, 504], true);
        });

        if ($key = config('services.ai.key')) {
            $request = $request->withToken($key);
        }

        $response = $request->post($baseUrl.'/chat/completions', $payload)->throw();

        $message = $response->json('choices.0.message');

        if (! is_array($message)) {
            throw new RuntimeException('Unexpected response from the chat API.');
        }

        return $message;
    }
}
