<?php

namespace App\Models;

class RecomRecommendation extends BaseModel
{
    protected $table = 'recom_recommendations';

    protected $fillable = [
        'usersId',
        'title',
        'questionnaire_data',
        'ai_response',
        'status',
        'delete_status',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'questionnaire_data' => 'array',
    ];

    // Status constants
    const STATUS_DRAFT = 'draft';
    const STATUS_GENERATED = 'generated';
    const STATUS_PUBLISHED = 'published';

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
     * Scope for records by status.
     */
    public function scopeStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for draft records.
     */
    public function scopeDraft($query)
    {
        return $query->where('status', self::STATUS_DRAFT);
    }

    /**
     * Scope for generated records.
     */
    public function scopeGenerated($query)
    {
        return $query->where('status', self::STATUS_GENERATED);
    }

    /**
     * Scope for published records.
     */
    public function scopePublished($query)
    {
        return $query->where('status', self::STATUS_PUBLISHED);
    }

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that owns the recommendation.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    // ==================== COMPUTED ATTRIBUTES ====================

    /**
     * Get status label.
     */
    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => 'Draft',
            self::STATUS_GENERATED => 'Generated',
            self::STATUS_PUBLISHED => 'Published',
            default => ucfirst($this->status),
        };
    }

    /**
     * Get status badge HTML.
     */
    public function getStatusBadgeAttribute(): string
    {
        return match ($this->status) {
            self::STATUS_DRAFT => '<span class="badge bg-secondary">Draft</span>',
            self::STATUS_GENERATED => '<span class="badge bg-info text-white">Generated</span>',
            self::STATUS_PUBLISHED => '<span class="badge bg-success">Published</span>',
            default => '<span class="badge bg-light text-dark">' . ucfirst($this->status) . '</span>',
        };
    }

    /**
     * Check if recommendation has questionnaire data.
     */
    public function hasQuestionnaireData(): bool
    {
        return !empty($this->questionnaire_data);
    }

    /**
     * Check if recommendation has AI response.
     */
    public function hasAiResponse(): bool
    {
        return !empty($this->ai_response);
    }
}
