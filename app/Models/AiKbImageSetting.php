<?php

namespace App\Models;

use Illuminate\Support\Facades\Crypt;

class AiKbImageSetting extends BaseModel
{
    protected $table = 'ai_kb_image_settings';

    protected $fillable = [
        'usersId',
        'apiKey',
        'indexName',
        'indexHost',
        'email',
        'delete_status',
    ];

    /**
     * Encrypt the API key when setting it.
     */
    public function setApiKeyAttribute($value)
    {
        if ($value) {
            $this->attributes['apiKey'] = Crypt::encryptString($value);
        } else {
            $this->attributes['apiKey'] = null;
        }
    }

    /**
     * Decrypt the API key when getting it.
     */
    public function getApiKeyAttribute($value)
    {
        if ($value) {
            try {
                return Crypt::decryptString($value);
            } catch (\Exception $e) {
                return $value;
            }
        }
        return null;
    }

    /**
     * Get masked API key for display.
     */
    public function getMaskedApiKeyAttribute()
    {
        $key = $this->apiKey;
        if ($key && strlen($key) > 12) {
            return substr($key, 0, 8) . '...' . substr($key, -4);
        }
        return $key ? '********' : null;
    }

    /**
     * Scope for active records.
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope for user's records.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Get the user that owns the settings.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get or create settings for a user.
     */
    public static function getOrCreateForUser($userId)
    {
        $settings = static::active()->forUser($userId)->first();

        if (!$settings) {
            $settings = static::create([
                'usersId' => $userId,
                'delete_status' => 'active',
            ]);
        }

        return $settings;
    }

    /**
     * Get or create GLOBAL settings (not user-specific).
     */
    public static function getOrCreate()
    {
        $settings = static::active()->first();

        if (!$settings) {
            $settings = static::create([
                'usersId' => null,
                'delete_status' => 'active',
            ]);
        }

        return $settings;
    }

    /**
     * Check if settings are configured.
     */
    public function isConfigured(): bool
    {
        return !empty($this->apiKey) && !empty($this->indexName);
    }
}
