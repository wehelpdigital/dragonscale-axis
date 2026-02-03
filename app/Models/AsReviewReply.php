<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsReviewReply extends Model
{
    protected $table = 'as_review_replies';

    protected $fillable = [
        'reviewId',
        'userId',
        'userName',
        'replyText',
        'deleteStatus'
    ];

    protected $casts = [
        'reviewId' => 'integer',
        'userId' => 'integer',
        'deleteStatus' => 'integer'
    ];

    /**
     * Relationships
     */
    public function review()
    {
        return $this->belongsTo(AsCourseReview::class, 'reviewId', 'id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'userId', 'id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('M j, Y g:i A');
    }

    /**
     * Get time ago
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }

    /**
     * Parse reply text for GIF URLs and convert to img tags
     */
    public function getParsedReplyTextAttribute()
    {
        $text = $this->replyText;

        // Convert GIF URLs to img tags
        $pattern = '/(https?:\/\/[^\s]+\.gif)/i';
        $text = preg_replace($pattern, '<img src="$1" alt="GIF" class="img-fluid rounded" style="max-width: 200px; max-height: 150px;">', $text);

        return $text;
    }
}
