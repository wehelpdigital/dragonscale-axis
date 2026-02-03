<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsCourseChapter extends Model
{
    protected $table = 'as_courses_chapters';

    protected $fillable = [
        'asCoursesId',
        'chapterTitle',
        'chapterDescription',
        'chapterCoverPhoto',
        'chapterOrder',
        'deleteStatus'
    ];

    protected $casts = [
        'deleteStatus' => 'boolean',
        'chapterOrder' => 'integer'
    ];

    public function course()
    {
        return $this->belongsTo(AsCourse::class, 'asCoursesId', 'id');
    }

    /**
     * Get active topics ordered by topicsOrder
     */
    public function topics()
    {
        return $this->hasMany(AsTopic::class, 'chapterId', 'id')
                    ->where('deleteStatus', true)
                    ->orderBy('topicsOrder');
    }

    /**
     * Get all topics including deleted
     */
    public function allTopics()
    {
        return $this->hasMany(AsTopic::class, 'chapterId', 'id');
    }

    /**
     * Scope for active chapters
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', true);
    }

    /**
     * Scope for ordered chapters
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('chapterOrder');
    }

    /**
     * Get the first letter of chapter title
     */
    public function getFirstLetterAttribute()
    {
        return strtoupper(substr($this->chapterTitle, 0, 1));
    }

    /**
     * Get placeholder color
     */
    public function getPlaceholderColorAttribute()
    {
        $colors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-info', 'bg-danger', 'bg-secondary'];
        return $colors[$this->id % count($colors)];
    }
}
