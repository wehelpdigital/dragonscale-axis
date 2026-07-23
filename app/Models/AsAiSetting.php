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
        'gemini' => 'gemini-2.0-flash',
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
