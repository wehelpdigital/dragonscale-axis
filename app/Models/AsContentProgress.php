<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsContentProgress extends Model
{
    protected $table = 'as_content_progress';

    protected $fillable = [
        'enrollmentId',
        'contentId',
        'completedAt',
        'deleteStatus'
    ];

    protected $casts = [
        'enrollmentId' => 'integer',
        'contentId' => 'integer',
        'deleteStatus' => 'integer',
        'completedAt' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function enrollment()
    {
        return $this->belongsTo(AsCourseEnrollment::class, 'enrollmentId', 'id');
    }

    public function content()
    {
        return $this->belongsTo(AsTopicContent::class, 'contentId', 'id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public function scopeForEnrollment($query, $enrollmentId)
    {
        return $query->where('enrollmentId', $enrollmentId);
    }

    public function scopeForContent($query, $contentId)
    {
        return $query->where('contentId', $contentId);
    }
}
