<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsCourseSettings extends Model
{
    protected $table = 'as_course_settings';

    protected $fillable = [
        'asCoursesId',
        'contentAccessMode',
        'quizBlocksNextChapter',
        'deleteStatus'
    ];

    protected $casts = [
        'asCoursesId' => 'integer',
        'quizBlocksNextChapter' => 'boolean',
        'deleteStatus' => 'integer'
    ];

    /**
     * Content Access Mode Options
     */
    const ACCESS_MODE_OPEN = 'open';
    const ACCESS_MODE_SEQUENTIAL = 'sequential';

    /**
     * Relationships
     */
    public function course()
    {
        return $this->belongsTo(AsCourse::class, 'asCoursesId', 'id');
    }

    /**
     * Scopes
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    public function scopeForCourse($query, $courseId)
    {
        return $query->where('asCoursesId', $courseId);
    }

    /**
     * Get or create settings for a course
     * Returns existing settings or creates default settings if none exist
     */
    public static function getOrCreate($courseId)
    {
        $settings = self::active()->forCourse($courseId)->first();

        if (!$settings) {
            $settings = self::create([
                'asCoursesId' => $courseId,
                'contentAccessMode' => self::ACCESS_MODE_OPEN,
                'quizBlocksNextChapter' => false,
                'deleteStatus' => 1
            ]);
        }

        return $settings;
    }

    /**
     * Check if content access is sequential
     */
    public function isSequentialAccess()
    {
        return $this->contentAccessMode === self::ACCESS_MODE_SEQUENTIAL;
    }

    /**
     * Check if content access is open
     */
    public function isOpenAccess()
    {
        return $this->contentAccessMode === self::ACCESS_MODE_OPEN;
    }

    /**
     * Get human-readable access mode label
     */
    public function getAccessModeLabelAttribute()
    {
        return $this->contentAccessMode === self::ACCESS_MODE_OPEN
            ? 'Open Access'
            : 'Sequential (Linear)';
    }

    /**
     * Get description for the current settings
     */
    public function getSettingsDescriptionAttribute()
    {
        $parts = [];

        if ($this->isOpenAccess()) {
            $parts[] = 'All content accessible';
        } else {
            $parts[] = 'Sequential topic unlock';
        }

        if ($this->quizBlocksNextChapter) {
            $parts[] = 'Quiz required for next chapter';
        }

        return implode(' | ', $parts);
    }
}
