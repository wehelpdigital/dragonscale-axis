<?php

namespace App\Models;

class AsTutorial extends BaseModel
{
    protected $table = 'as_tutorials';

    protected $fillable = [
        'title', 'category', 'youtubeId', 'coverImagePath',
        'description', 'sortOrder', 'isPublished', 'deleteStatus',
    ];

    protected $casts = [
        'isPublished' => 'boolean',
        'sortOrder' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('as_tutorials.deleteStatus', 1);
    }
}
