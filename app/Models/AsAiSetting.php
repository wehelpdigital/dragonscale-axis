<?php

namespace App\Models;

use App\Support\AiKeyCipher;
use Illuminate\Database\Eloquent\Model;

/**
 * AniSystem's AI configuration (shared `anisystem_ai_settings` table).
 *
 * The API key is encrypted with AniSystem's APP_KEY, so it is written here but
 * never displayed — the admin sees only whether a key is set.
 */
class AsAiSetting extends Model
{
    protected $table = 'anisystem_ai_settings';

    public const PROVIDERS = [
        'claude' => 'Claude (Anthropic)',
        'openai' => 'GPT (OpenAI)',
        'gemini' => 'Gemini (Google)',
    ];

    public const DEFAULT_MODELS = [
        'claude' => 'claude-sonnet-5',
        'openai' => 'gpt-4o',
        // Google retired gemini-2.0-flash (their 404 names this successor).
        'gemini' => 'gemini-3.6-flash',
    ];

    protected $fillable = [
        'provider', 'apiKey', 'model', 'systemPrompt', 'assistantName', 'avatarPath',
        'creditsPerInputK', 'creditsPerOutputK', 'creditsPerImage', 'freeCreditsOnSignup',
        'maxOutputTokens', 'temperature', 'isEnabled', 'deleteStatus',
    ];

    protected $casts = [
        'creditsPerInputK' => 'decimal:2',
        'creditsPerOutputK' => 'decimal:2',
        'creditsPerImage' => 'decimal:2',
        'freeCreditsOnSignup' => 'integer',
        'maxOutputTokens' => 'integer',
        'temperature' => 'decimal:2',
        'isEnabled' => 'boolean',
        'deleteStatus' => 'integer',
    ];

    protected $hidden = ['apiKey'];

    public static function current(): self
    {
        return static::query()->orderBy('id')->first() ?? new static();
    }

    public function hasKey(): bool
    {
        return filled($this->attributes['apiKey'] ?? null);
    }

    public function isUsable(): bool
    {
        return (bool) $this->isEnabled && $this->hasKey();
    }

    public function effectiveModel(): string
    {
        return $this->model ?: (self::DEFAULT_MODELS[$this->provider] ?? self::DEFAULT_MODELS['claude']);
    }

    /** Decrypt the shared key for outbound LLM calls (null if unset/undecryptable). */
    public function plainApiKey(): ?string
    {
        $enc = $this->attributes['apiKey'] ?? null;
        if (! filled($enc)) {
            return null;
        }
        try {
            return AiKeyCipher::decrypt($enc);
        } catch (\Throwable $e) {
            return null;
        }
    }

    /**
     * Encrypt under the shared secret so AniSystem can read it back.
     * An empty value leaves the stored key untouched.
     */
    public function storeApiKey(string $plain): void
    {
        if ($plain === '') {
            return;
        }

        $this->attributes['apiKey'] = AiKeyCipher::encrypt($plain);
    }

    public function clearApiKey(): void
    {
        $this->attributes['apiKey'] = null;
    }
}
