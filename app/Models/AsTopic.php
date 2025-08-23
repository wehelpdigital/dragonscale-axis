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
        'topicContent',
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

    public function resources()
    {
        return $this->hasMany(AsTopicResource::class, 'asTopicsId');
    }
}
