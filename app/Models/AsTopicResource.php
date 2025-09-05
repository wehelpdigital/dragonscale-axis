<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsTopicResource extends Model
{
    protected $table = 'as_courses_topics_resources';

    protected $fillable = [
        'asTopicsId',
        'fileName',
        'fileUrl',
        'deleteStatus'
    ];

    protected $casts = [
        'deleteStatus' => 'boolean'
    ];

    public function topic()
    {
        return $this->belongsTo(AsTopic::class, 'asTopicsId');
    }
}





