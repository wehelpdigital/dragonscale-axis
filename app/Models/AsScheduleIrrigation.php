<?php

namespace App\Models;

class AsScheduleIrrigation extends BaseModel
{
    protected $table = 'as_schedule_irrigations';

    /**
     * Canonical irrigation task-type catalog.
     *
     * Each entry pairs a slug (DB value) with a label, color, and icon so
     * controllers / views / the calendar bands all pull the same visual
     * vocabulary. Edit here and every consumer updates automatically.
     */
    public const TASK_TYPES = [
        'irrigate' => 'Irrigate',
        'maintain' => 'Maintain Water Level',
        'overflow' => 'Overflow / Flush',
        'drain'    => 'Drain / Stop Irrigate',
    ];

    public const TASK_TYPE_COLORS = [
        'irrigate' => '#1976d2', // active water-in blue
        'maintain' => '#0097a7', // steady teal
        'overflow' => '#f4a82a', // alert amber for flush/excess
        'drain'    => '#6b7280', // off-state slate
    ];

    public const TASK_TYPE_ICONS = [
        'irrigate' => '💧',  // single drop = active fill
        'maintain' => '≈',   // wavy = steady water surface
        'overflow' => '🌊',  // big wave = overflow/flush
        'drain'    => '▾',   // small down triangle = drain off
    ];

    /**
     * Resolve the visual meta (label / color / icon) for a task-type slug,
     * falling back to 'irrigate' for null or unknown values so callers
     * never have to defensively check.
     */
    public static function taskTypeMeta(?string $slug): array
    {
        $key = (is_string($slug) && isset(self::TASK_TYPES[$slug])) ? $slug : 'irrigate';
        return [
            'slug'  => $key,
            'label' => self::TASK_TYPES[$key],
            'color' => self::TASK_TYPE_COLORS[$key],
            'icon'  => self::TASK_TYPE_ICONS[$key],
        ];
    }

    protected $fillable = [
        'croppingScheduleId',
        'irrigationTitle',
        'description',
        'startDay',
        'endDay',
        'taskType',
        'assignedWorkerId',
        'timeRequired',
        'deleteStatus',
    ];

    protected $casts = [
        'startDay' => 'integer',
        'endDay' => 'integer',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function assignedWorker()
    {
        return $this->belongsTo(AsScheduleWorker::class, 'assignedWorkerId');
    }
}
