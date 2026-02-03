<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;

class AiApiSetting extends BaseModel
{
    protected $table = 'ai_api_settings';

    protected $fillable = [
        'usersId',
        'provider',
        'apiKey',
        'organizationId',
        'defaultModel',
        'maxTokens',
        'temperature',
        'requestsPerMinute',
        'tokensPerMinute',
        'isActive',
        'isDefault',
        'visionEnabled',
        'lastTestedAt',
        'lastTestStatus',
        'lastTestError',
        'delete_status',
    ];

    protected $casts = [
        'maxTokens' => 'integer',
        'temperature' => 'decimal:2',
        'requestsPerMinute' => 'integer',
        'tokensPerMinute' => 'integer',
        'isActive' => 'boolean',
        'isDefault' => 'boolean',
        'visionEnabled' => 'boolean',
        'lastTestedAt' => 'datetime',
    ];

    protected $hidden = [
        'apiKey',
    ];

    protected $appends = [
        'provider_label',
    ];

    // Provider constants
    const PROVIDER_CLAUDE = 'claude';
    const PROVIDER_OPENAI = 'openai';
    const PROVIDER_GEMINI = 'gemini';

    // Test status constants
    const STATUS_PENDING = 'pending';
    const STATUS_SUCCESS = 'success';
    const STATUS_FAILED = 'failed';

    /**
     * Get available providers with labels.
     */
    public static function getProviderLabels(): array
    {
        return [
            self::PROVIDER_CLAUDE => 'Claude (Anthropic)',
            self::PROVIDER_OPENAI => 'GPT (OpenAI)',
            self::PROVIDER_GEMINI => 'Gemini (Google)',
        ];
    }

    /**
     * Get provider icon class.
     */
    public static function getProviderIcons(): array
    {
        return [
            self::PROVIDER_CLAUDE => 'mdi mdi-robot',
            self::PROVIDER_OPENAI => 'mdi mdi-head-snowflake',
            self::PROVIDER_GEMINI => 'mdi mdi-google',
        ];
    }

    /**
     * Get provider color class.
     */
    public static function getProviderColors(): array
    {
        return [
            self::PROVIDER_CLAUDE => '#D97706', // Amber/Orange
            self::PROVIDER_OPENAI => '#10A37F', // OpenAI Green
            self::PROVIDER_GEMINI => '#4285F4', // Google Blue
        ];
    }

    /**
     * Get available models by provider.
     */
    public static function getModelsByProvider(): array
    {
        return [
            self::PROVIDER_CLAUDE => [
                'claude-sonnet-4-20250514' => 'Claude Sonnet 4',
                'claude-opus-4-20250514' => 'Claude Opus 4',
                'claude-3-5-sonnet-20241022' => 'Claude 3.5 Sonnet',
                'claude-3-5-haiku-20241022' => 'Claude 3.5 Haiku',
                'claude-3-opus-20240229' => 'Claude 3 Opus',
            ],
            self::PROVIDER_OPENAI => [
                'gpt-4o' => 'GPT-4o (Recommended)',
                'gpt-4o-mini' => 'GPT-4o Mini (Fast)',
                'gpt-4-turbo' => 'GPT-4 Turbo',
                'o1' => 'o1 (Advanced Reasoning)',
                'o3-mini' => 'o3-mini (Reasoning)',
            ],
            self::PROVIDER_GEMINI => [
                'gemini-2.0-flash' => 'Gemini 2.0 Flash (Google Search)',
                'gemini-2.0-flash-lite' => 'Gemini 2.0 Flash Lite (Fast)',
                'gemini-1.5-pro' => 'Gemini 1.5 Pro (Google Search)',
                'gemini-1.5-flash' => 'Gemini 1.5 Flash (Google Search)',
            ],
        ];
    }

    /**
     * Scope: Active records only (not deleted).
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope: Filter by user.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Scope: Filter by provider.
     */
    public function scopeForProvider($query, $provider)
    {
        return $query->where('provider', $provider);
    }

    /**
     * Scope: Only enabled settings.
     */
    public function scopeEnabled($query)
    {
        return $query->where('isActive', true);
    }

    /**
     * Scope: Get the default provider setting.
     */
    public function scopeDefault($query)
    {
        return $query->where('isDefault', true);
    }

    /**
     * Scope: Only vision-enabled settings.
     */
    public function scopeVisionEnabled($query)
    {
        return $query->where('visionEnabled', true);
    }

    /**
     * Encrypt API key before saving.
     */
    public function setApiKeyAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['apiKey'] = Crypt::encryptString($value);
        } else {
            $this->attributes['apiKey'] = null;
        }
    }

    /**
     * Decrypt API key when retrieving.
     */
    public function getApiKeyAttribute($value)
    {
        if (!empty($value)) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return null;
            }
        }
        return null;
    }

    /**
     * Get masked API key for display.
     */
    public function getMaskedApiKeyAttribute(): string
    {
        $key = $this->apiKey;
        if (empty($key)) {
            return 'Not configured';
        }

        $length = strlen($key);
        if ($length <= 8) {
            return str_repeat('*', $length);
        }

        return substr($key, 0, 4) . str_repeat('*', min($length - 8, 20)) . substr($key, -4);
    }

    /**
     * Check if the setting has a valid API key.
     */
    public function hasApiKey(): bool
    {
        return !empty($this->apiKey);
    }

    /**
     * Get provider label.
     */
    public function getProviderLabelAttribute(): string
    {
        return self::getProviderLabels()[$this->provider] ?? $this->provider;
    }

    /**
     * Get provider icon.
     */
    public function getProviderIconAttribute(): string
    {
        return self::getProviderIcons()[$this->provider] ?? 'mdi mdi-api';
    }

    /**
     * Get provider color.
     */
    public function getProviderColorAttribute(): string
    {
        return self::getProviderColors()[$this->provider] ?? '#6c757d';
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        if (!$this->isActive) {
            return '<span class="badge bg-secondary">Disabled</span>';
        }

        switch ($this->lastTestStatus) {
            case self::STATUS_SUCCESS:
                return '<span class="badge bg-success">Connected</span>';
            case self::STATUS_FAILED:
                return '<span class="badge bg-danger">Failed</span>';
            default:
                return '<span class="badge bg-warning text-dark">Not Tested</span>';
        }
    }

    /**
     * Get available models for this provider.
     */
    public function getAvailableModelsAttribute(): array
    {
        $models = self::getModelsByProvider();
        return $models[$this->provider] ?? [];
    }
}
