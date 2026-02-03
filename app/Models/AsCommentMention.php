<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsCommentMention extends Model
{
    protected $table = 'as_comment_mentions';

    protected $fillable = [
        'commentId',
        'asCoursesId',
        'mentionType',
        'mentionedUserId',
        'mentionedAuthorName',
        'mentionedAuthorEmail',
        'mentionerUserId',
        'mentionerAuthorName',
        'mentionerType',
        'isRead',
        'isNotified',
        'notifiedAt',
        'readAt',
        'commentPreview',
        'contextType',
        'contextId',
        'delete_status'
    ];

    protected $casts = [
        'isRead' => 'boolean',
        'isNotified' => 'boolean',
        'notifiedAt' => 'datetime',
        'readAt' => 'datetime'
    ];

    /**
     * Scope for active records
     */
    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    /**
     * Scope for unread mentions
     */
    public function scopeUnread($query)
    {
        return $query->where('isRead', false);
    }

    /**
     * Scope for pending notifications
     */
    public function scopePendingNotification($query)
    {
        return $query->where('isNotified', false);
    }

    /**
     * Get the comment this mention belongs to
     */
    public function comment()
    {
        return $this->belongsTo(AsContentComment::class, 'commentId', 'id');
    }

    /**
     * Get the course this mention belongs to
     */
    public function course()
    {
        return $this->belongsTo(AsCourse::class, 'asCoursesId', 'id');
    }

    /**
     * Get mentions for a specific user by email
     */
    public function scopeForUserByEmail($query, $email)
    {
        return $query->where('mentionedAuthorEmail', $email);
    }

    /**
     * Get mentions for a specific user by ID
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('mentionedUserId', $userId);
    }

    /**
     * Mark as read
     */
    public function markAsRead()
    {
        $this->update([
            'isRead' => true,
            'readAt' => now()
        ]);
    }

    /**
     * Mark as notified
     */
    public function markAsNotified()
    {
        $this->update([
            'isNotified' => true,
            'notifiedAt' => now()
        ]);
    }
}
