<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsTopicContent extends Model
{
    protected $table = 'as_topic_contents';

    protected $fillable = [
        'topicId',
        'contentTitle',
        'contentBody',
        'youtubeUrl',
        'contentPhotos',
        'takeaways',
        'contentOrder',
        'deleteStatus'
    ];

    protected $casts = [
        'deleteStatus' => 'boolean',
        'contentOrder' => 'integer',
        'contentPhotos' => 'array'
    ];

    /**
     * Get the topic that owns this content
     */
    public function topic()
    {
        return $this->belongsTo(AsTopic::class, 'topicId', 'id');
    }

    /**
     * Get the resources (downloadables) for this content
     */
    public function resources()
    {
        return $this->hasMany(AsContentResource::class, 'contentId', 'id')
                    ->where('deleteStatus', true)
                    ->orderBy('resourceOrder');
    }

    /**
     * Scope for active contents
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', true);
    }

    /**
     * Scope for ordered contents
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('contentOrder');
    }

    /**
     * Extract YouTube video ID from URL
     */
    public function getYoutubeIdAttribute()
    {
        if (!$this->youtubeUrl) {
            return null;
        }

        $pattern = '/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/i';
        preg_match($pattern, $this->youtubeUrl, $matches);

        return $matches[1] ?? null;
    }

    /**
     * Get YouTube embed URL
     */
    public function getYoutubeEmbedUrlAttribute()
    {
        $videoId = $this->youtube_id;
        return $videoId ? "https://www.youtube.com/embed/{$videoId}" : null;
    }
}
