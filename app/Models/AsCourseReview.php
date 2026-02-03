<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsCourseReview extends Model
{
    protected $table = 'as_course_reviews';

    protected $fillable = [
        'asCoursesId',
        'enrollmentId',
        'rating',
        'reviewTitle',
        'reviewText',
        'isApproved',
        'isFeatured',
        'deleteStatus'
    ];

    protected $casts = [
        'asCoursesId' => 'integer',
        'enrollmentId' => 'integer',
        'rating' => 'integer',
        'isApproved' => 'boolean',
        'isFeatured' => 'boolean',
        'deleteStatus' => 'integer'
    ];

    /**
     * Relationships
     */
    public function course()
    {
        return $this->belongsTo(AsCourse::class, 'asCoursesId', 'id');
    }

    public function enrollment()
    {
        return $this->belongsTo(AsCourseEnrollment::class, 'enrollmentId', 'id');
    }

    public function replies()
    {
        return $this->hasMany(AsReviewReply::class, 'reviewId', 'id')
                    ->where('deleteStatus', 1)
                    ->orderBy('created_at', 'asc');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public function scopeApproved($query)
    {
        return $query->where('isApproved', true);
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('asCoursesId', $courseId);
    }

    public function scopeByRating($query, $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Get star display (returns array of filled/empty stars)
     */
    public function getStarsHtmlAttribute()
    {
        $html = '';
        for ($i = 1; $i <= 5; $i++) {
            if ($i <= $this->rating) {
                $html .= '<i class="bx bxs-star text-warning"></i>';
            } else {
                $html .= '<i class="bx bx-star text-muted"></i>';
            }
        }
        return $html;
    }

    /**
     * Get formatted date
     */
    public function getFormattedDateAttribute()
    {
        return $this->created_at->format('M j, Y');
    }

    /**
     * Get time ago
     */
    public function getTimeAgoAttribute()
    {
        return $this->created_at->diffForHumans();
    }
}
