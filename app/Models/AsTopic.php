<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsTopic extends Model
{
    protected $table = 'as_courses_topics';

    protected $fillable = [
        'chapterId',
        'topicTitle',
        'topicDescription',
        'topicCoverPhoto',
        'topicContent', // Legacy - kept for backward compatibility
        'topicsOrder',
        'deleteStatus'
    ];

    protected $casts = [
        'deleteStatus' => 'boolean',
        'topicsOrder' => 'integer'
    ];

    public function chapter()
    {
        return $this->belongsTo(AsCourseChapter::class, 'chapterId', 'id');
    }

    /**
     * Get the contents for this topic (new structure)
     */
    public function contents()
    {
        return $this->hasMany(AsTopicContent::class, 'topicId', 'id')
                    ->where('deleteStatus', true)
                    ->orderBy('contentOrder');
    }

    /**
     * Legacy: Get the resources directly attached to topic
     */
    public function resources()
    {
        return $this->hasMany(AsTopicResource::class, 'asTopicsId', 'id');
    }

    /**
     * Scope for active topics
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', true);
    }

    /**
     * Scope for ordered topics
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('topicsOrder');
    }

    /**
     * Get the first letter of topic title
     */
    public function getFirstLetterAttribute()
    {
        return strtoupper(substr($this->topicTitle, 0, 1));
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
