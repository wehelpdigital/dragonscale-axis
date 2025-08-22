<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsCourse extends Model
{
    protected $table = 'as_courses';

    protected $fillable = [
        'courseName',
        'courseSmallDescription',
        'courseBigDescription',
        'coursePrice',
        'courseImage',
        'isActive',
        'deleteStatus'
    ];

    protected $casts = [
        'isActive' => 'boolean',
        'deleteStatus' => 'boolean',
        'coursePrice' => 'decimal:2'
    ];

    public function chapters()
    {
        return $this->hasMany(AsCourseChapter::class, 'asCoursesId', 'id');
    }

    // Accessor for first letter of course name
    public function getFirstLetterAttribute()
    {
        return strtoupper(substr($this->courseName, 0, 1));
    }

    // Accessor for placeholder color
    public function getPlaceholderColorAttribute()
    {
        $colors = ['bg-primary', 'bg-success', 'bg-warning', 'bg-info', 'bg-danger', 'bg-secondary'];
        return $colors[array_rand($colors)];
    }
}
