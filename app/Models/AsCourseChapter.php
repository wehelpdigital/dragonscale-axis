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

    public function topics()
    {
        return $this->hasMany(AsTopic::class, 'chapterId', 'id');
    }
}
