<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class AsCourseEnrollment extends Model
{
    protected $table = 'as_course_enrollments';

    protected $fillable = [
        'accessClientId',
        'asCoursesId',
        'enrollmentDate',
        'expirationDate',
        'isActive',
        'deleteStatus'
    ];

    protected $casts = [
        'accessClientId' => 'integer',
        'asCoursesId' => 'integer',
        'isActive' => 'boolean',
        'deleteStatus' => 'integer',
        'enrollmentDate' => 'datetime',
        'expirationDate' => 'datetime'
    ];

    /**
     * Relationships
     */
    public function course()
    {
        return $this->belongsTo(AsCourse::class, 'asCoursesId', 'id');
    }

    /**
     * Topic-based progress (new)
     */
    public function topicProgress()
    {
        return $this->hasMany(AsTopicProgress::class, 'enrollmentId', 'id')
                    ->where('deleteStatus', 1);
    }

    /**
     * Content-based progress (legacy)
     */
    public function progress()
    {
        return $this->hasMany(AsContentProgress::class, 'enrollmentId', 'id')
                    ->where('deleteStatus', 1);
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1)->where('isActive', 1);
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('asCoursesId', $courseId);
    }

    public function scopeForClient($query, $clientId)
    {
        return $query->where('accessClientId', $clientId);
    }

    /**
     * Check if enrollment is expired
     */
    public function getIsExpiredAttribute()
    {
        if (!$this->expirationDate) return false;
        return Carbon::now()->greaterThan($this->expirationDate);
    }

    /**
     * Get days remaining until expiration (whole number)
     */
    public function getDaysRemainingAttribute()
    {
        if (!$this->expirationDate) return null; // Lifetime
        $now = Carbon::now();
        if ($now->greaterThan($this->expirationDate)) return 0;
        return (int) floor($now->diffInDays($this->expirationDate, false));
    }

    /**
     * Get formatted expiration string
     * Format: "January 2, 2026 (33 days remaining)" or "Lifetime Access"
     */
    public function getFormattedExpirationAttribute()
    {
        if (!$this->expirationDate) return 'Lifetime Access';
        $formatted = $this->expirationDate->format('F j, Y');
        $days = $this->days_remaining;
        if ($days === 0) return "{$formatted} (Expired)";
        if ($days === 1) return "{$formatted} (1 day remaining)";
        return "{$formatted} ({$days} days remaining)";
    }

    /**
     * Calculate progress percentage based on completed topics
     */
    public function getProgressPercentage()
    {
        $totalTopics = $this->getTotalTopics();
        if ($totalTopics === 0) return 0;

        $completedTopics = $this->topicProgress()->count();
        return round(($completedTopics / $totalTopics) * 100);
    }

    /**
     * Get total topics count in the course
     */
    public function getTotalTopics()
    {
        return AsTopic::where('deleteStatus', true)
            ->whereHas('chapter', function($q) {
                $q->where('asCoursesId', $this->asCoursesId)
                  ->where('deleteStatus', true);
            })->count();
    }

    /**
     * Get completed topics count
     */
    public function getCompletedTopics()
    {
        return $this->topicProgress()->count();
    }

    /**
     * Legacy: Get total contents count in the course
     */
    public function getTotalContents()
    {
        return AsTopicContent::whereHas('topic', function($q) {
            $q->where('deleteStatus', true)
              ->whereHas('chapter', function($cq) {
                  $cq->where('asCoursesId', $this->asCoursesId)
                     ->where('deleteStatus', true);
              });
        })->where('deleteStatus', true)->count();
    }

    /**
     * Legacy: Get completed contents count
     */
    public function getCompletedContents()
    {
        return $this->progress()->count();
    }
}
