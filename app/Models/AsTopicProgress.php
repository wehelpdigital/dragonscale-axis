<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsTopicProgress extends Model
{
    protected $table = 'as_topic_progress';

    protected $fillable = [
        'enrollmentId',
        'topicId',
        'completedAt',
        'deleteStatus'
    ];

    protected $casts = [
        'enrollmentId' => 'integer',
        'topicId' => 'integer',
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

    public function topic()
    {
        return $this->belongsTo(AsTopic::class, 'topicId', 'id');
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

    public function scopeForTopic($query, $topicId)
    {
        return $query->where('topicId', $topicId);
    }
}
