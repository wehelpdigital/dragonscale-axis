<?php

namespace App\Models;

use Illuminate\Support\Facades\Storage;

class AiChatAvatarSetting extends BaseModel
{
    protected $table = 'ai_chat_avatar_settings';

    protected $fillable = [
        'usersId',
        'avatarPath',
        'avatarFilename',
        'displayName',
        'useCustomAvatar',
        'delete_status',
    ];

    protected $casts = [
        'useCustomAvatar' => 'boolean',
    ];

    /**
     * Default avatar path (relative to public)
     */
    const DEFAULT_AVATAR = 'images/ai-avatar-default.svg';

    /**
     * Storage directory for avatars
     */
    const AVATAR_STORAGE_PATH = 'ai-avatars';

    /**
     * Scope for active records
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope for user
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Get or create avatar settings for a user
     */
    public static function getOrCreateForUser($userId): self
    {
        $setting = self::active()
            ->forUser($userId)
            ->first();

        if (!$setting) {
            $setting = self::create([
                'usersId' => $userId,
                'displayName' => 'AI Technician',
                'useCustomAvatar' => false,
                'delete_status' => 'active',
            ]);
        }

        return $setting;
    }

    /**
     * Get or create GLOBAL avatar settings (not user-specific)
     */
    public static function getOrCreate(): self
    {
        $setting = self::active()->first();

        if (!$setting) {
            $setting = self::create([
                'usersId' => null,
                'displayName' => 'AI Technician',
                'useCustomAvatar' => false,
                'delete_status' => 'active',
            ]);
        }

        return $setting;
    }

    /**
     * Get the avatar URL
     */
    public function getAvatarUrlAttribute(): string
    {
        if ($this->useCustomAvatar && $this->avatarPath) {
            // Check if file exists in storage
            if (Storage::disk('public')->exists($this->avatarPath)) {
                return asset('storage/' . $this->avatarPath);
            }
        }

        // Return default avatar
        return asset(self::DEFAULT_AVATAR);
    }

    /**
     * Check if using custom avatar
     */
    public function hasCustomAvatar(): bool
    {
        return $this->useCustomAvatar && $this->avatarPath && Storage::disk('public')->exists($this->avatarPath);
    }

    /**
     * Delete old avatar file when replacing
     */
    public function deleteOldAvatar(): void
    {
        if ($this->avatarPath && Storage::disk('public')->exists($this->avatarPath)) {
            Storage::disk('public')->delete($this->avatarPath);
        }
    }

    /**
     * Get the full storage path for avatars
     */
    public static function getStoragePath($userId): string
    {
        return self::AVATAR_STORAGE_PATH . '/' . $userId;
    }
}
