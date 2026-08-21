<?php

namespace App\Modules\OutreachEngine\Services;

use App\Modules\OutreachEngine\Models\OutreachSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * One provider-agnostic door to Claude / OpenAI / Gemini for the whole module.
 *
 * The wire formats mirror App\Services\AniSenso\CommunityAiAnswerService, which is
 * already proven against these three APIs in this codebase - only the settings
 * source and the failure contract differ.
 *
 * Failure contract: this service NEVER throws. Every caller runs inside cron (email
 * discovery, template rephrasing) where a provider outage must degrade the feature,
 * not abort the run - so complete() returns '' and completeJson() returns [], and
 * the caller falls back to its non-AI path.
 */
class LlmService
{
    /** Model used when the user left llmModel blank, per provider. */
    const DEFAULT_MODELS = [
        OutreachSetting::PROVIDER_CLAUDE => 'claude-sonnet-4-20250514',
        OutreachSetting::PROVIDER_OPENAI => 'gpt-4o-mini',
        OutreachSetting::PROVIDER_GEMINI => 'gemini-2.0-flash',
    ];

    /** Seconds to wait on a provider. Generous: these are cron calls, not page loads. */
    const TIMEOUT_SECONDS = 60;

    /** Low temperature for completeJson() - JSON answers want determinism, not flair. */
    const JSON_TEMPERATURE = 0.2;

    protected OutreachSetting $settings;

    public function __construct(OutreachSetting $settings)
    {
        $this->settings = $settings;
    }

    /**
     * Do we have both a provider and a key?
     */
    public function isConfigured(): bool
    {
        return $this->settings->hasLlm();
    }

    /**
     * The provider to call. An unknown value in the column falls back to the same
     * default the migration uses, so a hand-edited row can never reach a match arm
     * that does not exist.
     */
    public function provider(): string
    {
        $provider = (string) $this->settings->llmProvider;

        return array_key_exists($provider, self::DEFAULT_MODELS)
            ? $provider
            : OutreachSetting::PROVIDER_GEMINI;
    }

    /**
     * The model id to send, honouring the user's override.
     */
    public function model(): string
    {
        $model = trim((string) $this->settings->llmModel);

        return $model !== '' ? $model : self::DEFAULT_MODELS[$this->provider()];
    }

    /**
     * Free-text completion. Returns '' on any failure - never throws.
     */
    public function complete(string $system, string $user, int $maxTokens = 600, float $temperature = 0.7): string
    {
        if (!$this->isConfigured()) {
            Log::warning('[OutreachEngine] LLM call skipped: no provider or key configured.', [
                'usersId' => $this->settings->usersId,
            ]);
            return '';
        }

        // The accessor decrypts; a rotated APP_KEY makes it null rather than throwing.
        $key = (string) $this->settings->llmApiKey;
        if ($key === '') {
            Log::warning('[OutreachEngine] LLM call skipped: the stored key could not be read on this server.', [
                'usersId' => $this->settings->usersId,
            ]);
            return '';
        }

        $provider = $this->provider();
        $model = $this->model();
        $maxTokens = max(1, $maxTokens);

        try {
            $text = match ($provider) {
                OutreachSetting::PROVIDER_OPENAI => $this->askOpenAi($key, $model, $system, $user, $maxTokens, $temperature),
                OutreachSetting::PROVIDER_CLAUDE => $this->askClaude($key, $model, $system, $user, $maxTokens, $temperature),
                default => $this->askGemini($key, $model, $system, $user, $maxTokens, $temperature),
            };
        } catch (\Throwable $e) {
            Log::error('[OutreachEngine] LLM completion failed: ' . $e->getMessage(), [
                'usersId' => $this->settings->usersId,
                'provider' => $provider,
                'model' => $model,
            ]);
            return '';
        }

        return trim($text);
    }

    /**
     * Completion parsed as JSON. Returns [] on any failure - never throws.
     * Markdown fences and any prose the model wrapped the object in are stripped first.
     */
    public function completeJson(string $system, string $user, int $maxTokens = 600): array
    {
        $raw = $this->complete($system, $user, $maxTokens, self::JSON_TEMPERATURE);

        if (trim($raw) === '') {
            return [];
        }

        $decoded = json_decode($this->stripJsonFences($raw), true);

        if (!is_array($decoded)) {
            Log::warning('[OutreachEngine] LLM returned unparseable JSON.', [
                'usersId' => $this->settings->usersId,
                'provider' => $this->provider(),
                // Truncated: a runaway answer must not bloat the log file.
                'raw' => mb_substr($raw, 0, 500),
            ]);
            return [];
        }

        return $decoded;
    }

    /**
     * Pull the JSON out of whatever the model actually said.
     *
     * Handles ```json fences, bare ``` fences, and the very common
     * "Sure! Here is the JSON: {...}" preamble by keeping the outermost
     * brace/bracket pair, whichever opens first.
     */
    protected function stripJsonFences(string $raw): string
    {
        $text = trim($raw);

        if (preg_match('/```(?:json)?\s*(.+?)\s*```/is', $text, $matches)) {
            $text = trim($matches[1]);
        } else {
            // An unterminated fence - the model hit the token cap mid-answer.
            $text = trim((string) preg_replace('/^```(?:json)?\s*/i', '', $text));
            $text = trim((string) preg_replace('/\s*```$/', '', $text));
        }

        $candidates = [];

        $objectStart = strpos($text, '{');
        $objectEnd = strrpos($text, '}');
        if ($objectStart !== false && $objectEnd !== false && $objectEnd > $objectStart) {
            $candidates[$objectStart] = substr($text, $objectStart, $objectEnd - $objectStart + 1);
        }

        $arrayStart = strpos($text, '[');
        $arrayEnd = strrpos($text, ']');
        if ($arrayStart !== false && $arrayEnd !== false && $arrayEnd > $arrayStart) {
            $candidates[$arrayStart] = substr($text, $arrayStart, $arrayEnd - $arrayStart + 1);
        }

        if (!empty($candidates)) {
            ksort($candidates);
            return trim((string) reset($candidates));
        }

        return $text;
    }

    /**
     * Anthropic Messages API.
     */
    protected function askClaude(string $key, string $model, string $system, string $user, int $max, float $temp): string
    {
        $res = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(self::TIMEOUT_SECONDS)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => $max,
            'temperature' => $temp,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $user]],
        ]);

        if (!$res->successful()) {
            throw new \RuntimeException('Claude error: ' . $res->status() . ' ' . mb_substr($res->body(), 0, 500));
        }

        return (string) data_get($res->json(), 'content.0.text', '');
    }

    /**
     * OpenAI Chat Completions API.
     */
    protected function askOpenAi(string $key, string $model, string $system, string $user, int $max, float $temp): string
    {
        $res = Http::withToken($key)->timeout(self::TIMEOUT_SECONDS)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'max_tokens' => $max,
            'temperature' => $temp,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $user],
            ],
        ]);

        if (!$res->successful()) {
            throw new \RuntimeException('OpenAI error: ' . $res->status() . ' ' . mb_substr($res->body(), 0, 500));
        }

        return (string) data_get($res->json(), 'choices.0.message.content', '');
    }

    /**
     * Google Gemini generateContent API. The key rides in the query string.
     */
    protected function askGemini(string $key, string $model, string $system, string $user, int $max, float $temp): string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/'
            . rawurlencode($model) . ':generateContent?key=' . urlencode($key);

        $res = Http::timeout(self::TIMEOUT_SECONDS)->post($url, [
            'system_instruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $user]]]],
            'generationConfig' => ['maxOutputTokens' => $max, 'temperature' => $temp],
        ]);

        if (!$res->successful()) {
            throw new \RuntimeException('Gemini error: ' . $res->status() . ' ' . mb_substr($res->body(), 0, 500));
        }

        return (string) data_get($res->json(), 'candidates.0.content.parts.0.text', '');
    }
}
