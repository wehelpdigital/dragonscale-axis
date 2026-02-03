<?php

namespace App\Models;

class AiChatMessage extends BaseModel
{
    protected $table = 'ai_chat_messages';

    protected $fillable = [
        'sessionId',
        'role',
        'content',
        'images',
        'metadata',
        'processingTime',
        'delete_status',
    ];

    protected $casts = [
        'images' => 'array',
        'metadata' => 'array',
        'processingTime' => 'decimal:3',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Role constants.
     */
    const ROLE_USER = 'user';
    const ROLE_ASSISTANT = 'assistant';
    const ROLE_SYSTEM = 'system';
    const ROLE_THINKING = 'thinking';

    // ==================== SCOPES ====================

    /**
     * Scope for active records.
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope for user messages only.
     */
    public function scopeUserMessages($query)
    {
        return $query->where('role', self::ROLE_USER);
    }

    /**
     * Scope for assistant messages only.
     */
    public function scopeAssistantMessages($query)
    {
        return $query->where('role', self::ROLE_ASSISTANT);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the session this message belongs to.
     */
    public function session()
    {
        return $this->belongsTo(AiChatSession::class, 'sessionId');
    }

    // ==================== COMPUTED ATTRIBUTES ====================

    /**
     * Check if message has images.
     */
    public function getHasImagesAttribute()
    {
        return !empty($this->images) && count($this->images) > 0;
    }

    /**
     * Get image count.
     */
    public function getImageCountAttribute()
    {
        return $this->images ? count($this->images) : 0;
    }

    /**
     * Get image URLs for display.
     */
    public function getImageUrlsAttribute()
    {
        if (!$this->images) return [];

        return array_map(function ($path) {
            return asset('storage/' . $path);
        }, $this->images);
    }

    /**
     * Get role badge HTML.
     */
    public function getRoleBadgeAttribute()
    {
        return match($this->role) {
            self::ROLE_USER => '<span class="badge bg-primary">User</span>',
            self::ROLE_ASSISTANT => '<span class="badge bg-success">Assistant</span>',
            self::ROLE_SYSTEM => '<span class="badge bg-secondary">System</span>',
            self::ROLE_THINKING => '<span class="badge bg-warning text-dark">Thinking</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->role) . '</span>',
        };
    }

    /**
     * Get formatted time.
     */
    public function getFormattedTimeAttribute()
    {
        return $this->created_at->format('g:i A');
    }

    /**
     * Get formatted date.
     */
    public function getFormattedDateAttribute()
    {
        if ($this->created_at->isToday()) {
            return 'Today';
        }
        if ($this->created_at->isYesterday()) {
            return 'Yesterday';
        }
        return $this->created_at->format('M d, Y');
    }

    /**
     * Get processing time formatted.
     */
    public function getProcessingTimeFormattedAttribute()
    {
        if (!$this->processingTime) return null;

        if ($this->processingTime < 1) {
            return round($this->processingTime * 1000) . 'ms';
        }
        return round($this->processingTime, 2) . 's';
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if this is a user message.
     */
    public function isUser()
    {
        return $this->role === self::ROLE_USER;
    }

    /**
     * Check if this is an assistant message.
     */
    public function isAssistant()
    {
        return $this->role === self::ROLE_ASSISTANT;
    }

    /**
     * Check if this is a thinking message.
     */
    public function isThinking()
    {
        return $this->role === self::ROLE_THINKING;
    }

    /**
     * Get content preview for lists.
     */
    public function getContentPreview($length = 100)
    {
        return \Str::limit(strip_tags($this->content), $length);
    }
}
