<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsContentComment extends Model
{
    protected $table = 'as_content_comments';

    protected $fillable = [
        'asCoursesId',
        'contentId',
        'parentCommentId',
        'authorType',
        'authorId',
        'authorName',
        'authorEmail',
        'authorAvatar',
        'commentText',
        'isAnswered',
        'isApproved',
        'isPinned',
        'likesCount',
        'heartsCount',
        'deleteStatus'
    ];

    protected $casts = [
        'isAnswered' => 'boolean',
        'isApproved' => 'boolean',
        'isPinned' => 'boolean',
        'deleteStatus' => 'boolean'
    ];

    /**
     * Get the course this comment belongs to
     */
    public function course()
    {
        return $this->belongsTo(AsCourse::class, 'asCoursesId', 'id');
    }

    /**
     * Get the content this comment belongs to (if any)
     */
    public function content()
    {
        return $this->belongsTo(AsTopicContent::class, 'contentId', 'id');
    }

    /**
     * Get the parent comment (for nested comments)
     */
    public function parent()
    {
        return $this->belongsTo(AsContentComment::class, 'parentCommentId', 'id');
    }

    /**
     * Get replies to this comment
     */
    public function replies()
    {
        return $this->hasMany(AsContentComment::class, 'parentCommentId', 'id')
                    ->where('deleteStatus', true)
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Get all nested replies recursively
     */
    public function allReplies()
    {
        return $this->hasMany(AsContentComment::class, 'parentCommentId', 'id')
                    ->where('deleteStatus', true)
                    ->with('allReplies')
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Scope for active comments
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', true);
    }

    /**
     * Scope for root comments (no parent)
     */
    public function scopeRootComments($query)
    {
        return $query->whereNull('parentCommentId');
    }

    /**
     * Scope for unanswered comments
     */
    public function scopeUnanswered($query)
    {
        return $query->where('isAnswered', false);
    }

    /**
     * Scope for answered comments
     */
    public function scopeAnswered($query)
    {
        return $query->where('isAnswered', true);
    }

    /**
     * Scope for approved comments
     */
    public function scopeApproved($query)
    {
        return $query->where('isApproved', true);
    }

    /**
     * Check if comment is from admin
     */
    public function getIsAdminReplyAttribute()
    {
        return $this->authorType === 'admin';
    }

    /**
     * Get formatted time ago
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Get the content path for display (Chapter > Topic > Content)
     */
    public function getContentPathAttribute()
    {
        if (!$this->content) {
            return 'General Course Comment';
        }

        $content = $this->content;
        $topic = $content->topic ?? null;
        $chapter = $topic ? $topic->chapter : null;

        $path = [];
        if ($chapter) {
            $path[] = $chapter->chapterTitle;
        }
        if ($topic) {
            $path[] = $topic->topicTitle;
        }
        $path[] = $content->contentTitle;

        return implode(' > ', $path);
    }

    /**
     * Mark this comment as answered
     */
    public function markAsAnswered()
    {
        $this->update(['isAnswered' => true]);
    }

    /**
     * Get avatar URL or generate placeholder
     */
    public function getAvatarUrlAttribute()
    {
        if ($this->authorAvatar) {
            return $this->authorAvatar;
        }

        // Generate a placeholder based on author name
        $initial = strtoupper(substr($this->authorName, 0, 1));
        $colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
        $color = $colors[ord($initial) % count($colors)];

        return "data:image/svg+xml," . rawurlencode("<svg xmlns='http://www.w3.org/2000/svg' width='40' height='40'><rect fill='{$color}' width='40' height='40'/><text x='50%' y='50%' dy='.35em' fill='white' text-anchor='middle' font-family='Arial' font-size='18' font-weight='bold'>{$initial}</text></svg>");
    }
}
