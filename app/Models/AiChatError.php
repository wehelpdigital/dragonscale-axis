<?php

namespace App\Models;

class AiChatError extends BaseModel
{
    protected $table = 'ai_chat_errors';

    protected $fillable = [
        'usersId',
        'sessionId',
        'errorDate',
        'chatThread',
        'flowLogs',
        'errorDescription',
        'status',
        'delete_status',
    ];

    protected $casts = [
        'errorDate' => 'datetime',
        'chatThread' => 'array',
        'flowLogs' => 'array',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Status constants.
     */
    const STATUS_PENDING = 'pending';
    const STATUS_FIXED = 'fixed';

    // ==================== SCOPES ====================

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
     * Scope for pending errors.
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope for fixed errors.
     */
    public function scopeFixed($query)
    {
        return $query->where('status', self::STATUS_FIXED);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that owns this error.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    /**
     * Get the related chat session.
     */
    public function session()
    {
        return $this->belongsTo(AiChatSession::class, 'sessionId');
    }

    // ==================== COMPUTED ATTRIBUTES ====================

    /**
     * Get formatted error date (January 25, 2022 5:00pm).
     */
    public function getFormattedDateAttribute()
    {
        return $this->errorDate->format('F j, Y g:ia');
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute()
    {
        return match($this->status) {
            self::STATUS_PENDING => '<span class="badge bg-warning text-dark">Pending</span>',
            self::STATUS_FIXED => '<span class="badge bg-success">Fixed</span>',
            default => '<span class="badge bg-secondary">' . ucfirst($this->status) . '</span>',
        };
    }

    /**
     * Get chat thread preview.
     */
    public function getChatPreviewAttribute()
    {
        if (!$this->chatThread || empty($this->chatThread)) {
            return 'No messages';
        }

        $thread = is_array($this->chatThread) ? $this->chatThread : json_decode($this->chatThread, true);

        if (empty($thread)) {
            return 'No messages';
        }

        // Get first user message for preview
        foreach ($thread as $msg) {
            if (isset($msg['role']) && $msg['role'] === 'user') {
                $content = $msg['content'] ?? '';
                return \Str::limit(strip_tags($content), 80);
            }
        }

        // Fallback to first message
        $firstMsg = $thread[0] ?? null;
        if ($firstMsg && isset($firstMsg['content'])) {
            return \Str::limit(strip_tags($firstMsg['content']), 80);
        }

        return 'Chat thread saved';
    }

    /**
     * Get message count from thread.
     */
    public function getMessageCountAttribute()
    {
        if (!$this->chatThread) {
            return 0;
        }

        $thread = is_array($this->chatThread) ? $this->chatThread : json_decode($this->chatThread, true);
        return count($thread ?? []);
    }

    /**
     * Check if has flow logs.
     */
    public function getHasFlowLogsAttribute()
    {
        return !empty($this->flowLogs);
    }

    // ==================== HELPER METHODS ====================

    /**
     * Check if error is pending.
     */
    public function isPending()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Check if error is fixed.
     */
    public function isFixed()
    {
        return $this->status === self::STATUS_FIXED;
    }

    /**
     * Mark error as fixed.
     */
    public function markAsFixed()
    {
        $this->update(['status' => self::STATUS_FIXED]);
    }

    /**
     * Mark error as pending.
     */
    public function markAsPending()
    {
        $this->update(['status' => self::STATUS_PENDING]);
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Pending',
            self::STATUS_FIXED => 'Fixed',
        ];
    }
}
