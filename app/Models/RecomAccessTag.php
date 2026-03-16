<?php

namespace App\Models;

class RecomAccessTag extends BaseModel
{
    protected $table = 'recom_access_tags';

    protected $fillable = [
        'usersId',
        'tagName',
        'expirationLength',
        'description',
        'delete_status',
    ];

    protected $casts = [
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
        'expirationLength' => 'integer',
    ];

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

    // ==================== RELATIONSHIPS ====================

    /**
     * Get the user that owns the tag.
     */
    public function user()
    {
        return $this->belongsTo(User::class, 'usersId');
    }

    // ==================== COMPUTED ATTRIBUTES ====================

    /**
     * Get human-readable expiration length.
     */
    public function getExpirationLengthHumanAttribute()
    {
        $days = $this->expirationLength;

        if ($days >= 365) {
            $years = floor($days / 365);
            return $years . ' ' . ($years == 1 ? 'year' : 'years');
        } elseif ($days >= 30) {
            $months = floor($days / 30);
            return $months . ' ' . ($months == 1 ? 'month' : 'months');
        } elseif ($days >= 7) {
            $weeks = floor($days / 7);
            return $weeks . ' ' . ($weeks == 1 ? 'week' : 'weeks');
        } else {
            return $days . ' ' . ($days == 1 ? 'day' : 'days');
        }
    }
}
