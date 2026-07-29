<?php

namespace App\Services\AniSenso;

use App\Models\AnisystemUser;
use App\Models\AsAiSetting;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

/**
 * Generates community answers with the configured AniSenso AI, and resolves the
 * "AI Technician" persona user that authors the posted replies.
 *
 * Uses the shared AsAiSetting (provider / model / system prompt / assistant
 * name) and the shared, secret-encrypted key.
 */
class CommunityAiAnswerService
{
    /** Sentinel account that represents the AI Technician in the community. */
    public const PERSONA_EMAIL = 'ai-technician@anisenso.system';

    public function settings(): AsAiSetting
    {
        return AsAiSetting::current();
    }

    public function isUsable(): bool
    {
        return $this->settings()->isUsable();
    }

    /**
     * Find (or create) the AnisystemUser that authors AI replies. Kept in sync
     * with the configured assistant name. Never deletes; only upserts.
     */
    public function personaUser(): AnisystemUser
    {
        $settings = $this->settings();
        $name = trim((string) ($settings->assistantName ?? '')) ?: 'AI Technician';

        $user = AnisystemUser::where('email', self::PERSONA_EMAIL)->first();
        if (! $user) {
            $user = new AnisystemUser();
            $user->email = self::PERSONA_EMAIL;
            $user->password = bcrypt(Str::random(40));
            $user->clientId = 0;
            $user->status = 'active';
            $user->deleteStatus = 1;
        }
        // Keep the display name aligned with the configured persona.
        $user->firstName = $name;
        $user->lastName = '';
        $user->save();

        return $user;
    }

    /**
     * Ask the configured AI a single question and return its answer text.
     * Throws \RuntimeException on any provider/transport failure.
     */
    public function answer(string $question, string $context = ''): string
    {
        $settings = $this->settings();
        if (! $settings->isUsable()) {
            throw new \RuntimeException('The AniSenso AI is not configured (no provider/key).');
        }
        $key = $settings->plainApiKey();
        if (! $key) {
            throw new \RuntimeException('The AI key could not be read on this server.');
        }

        $system = trim((string) $settings->systemPrompt) ?: 'You are a helpful crop-farming technician. Answer community questions clearly and practically.';
        if ($context !== '') {
            $system .= "\n\n" . $context;
        }
        $model = $settings->effectiveModel();
        $maxTokens = (int) ($settings->maxOutputTokens ?: 900);
        $temperature = (float) ($settings->temperature ?: 0.4);

        return match ($settings->provider) {
            'openai' => $this->askOpenAi($key, $model, $system, $question, $maxTokens, $temperature),
            'gemini' => $this->askGemini($key, $model, $system, $question, $maxTokens, $temperature),
            default  => $this->askClaude($key, $model, $system, $question, $maxTokens, $temperature),
        };
    }

    private function askClaude(string $key, string $model, string $system, string $q, int $max, float $temp): string
    {
        $res = Http::withHeaders([
            'x-api-key' => $key,
            'anthropic-version' => '2023-06-01',
            'content-type' => 'application/json',
        ])->timeout(60)->post('https://api.anthropic.com/v1/messages', [
            'model' => $model,
            'max_tokens' => $max,
            'temperature' => $temp,
            'system' => $system,
            'messages' => [['role' => 'user', 'content' => $q]],
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('Claude error: ' . $res->status() . ' ' . $res->body());
        }
        $text = data_get($res->json(), 'content.0.text');
        if (! filled($text)) {
            throw new \RuntimeException('Claude returned no text.');
        }
        return trim($text);
    }

    private function askOpenAi(string $key, string $model, string $system, string $q, int $max, float $temp): string
    {
        $res = Http::withToken($key)->timeout(60)->post('https://api.openai.com/v1/chat/completions', [
            'model' => $model,
            'max_tokens' => $max,
            'temperature' => $temp,
            'messages' => [
                ['role' => 'system', 'content' => $system],
                ['role' => 'user', 'content' => $q],
            ],
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('OpenAI error: ' . $res->status() . ' ' . $res->body());
        }
        $text = data_get($res->json(), 'choices.0.message.content');
        if (! filled($text)) {
            throw new \RuntimeException('OpenAI returned no text.');
        }
        return trim($text);
    }

    private function askGemini(string $key, string $model, string $system, string $q, int $max, float $temp): string
    {
        $url = 'https://generativelanguage.googleapis.com/v1beta/models/' . rawurlencode($model) . ':generateContent?key=' . urlencode($key);
        $res = Http::timeout(60)->post($url, [
            'system_instruction' => ['parts' => [['text' => $system]]],
            'contents' => [['role' => 'user', 'parts' => [['text' => $q]]]],
            'generationConfig' => ['maxOutputTokens' => $max, 'temperature' => $temp],
        ]);

        if (! $res->successful()) {
            throw new \RuntimeException('Gemini error: ' . $res->status() . ' ' . $res->body());
        }
        $text = data_get($res->json(), 'candidates.0.content.parts.0.text');
        if (! filled($text)) {
            throw new \RuntimeException('Gemini returned no text.');
        }
        return trim($text);
    }
}
