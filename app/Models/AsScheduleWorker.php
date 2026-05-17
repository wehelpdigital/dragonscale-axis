<?php

namespace App\Models;

class AsScheduleWorker extends BaseModel
{
    protected $table = 'as_schedule_workers';

    protected $fillable = [
        'croppingScheduleId',
        'workerName',
        'costPerHalfDay',
        'priority',
        'notes',
        'deleteStatus',
    ];

    protected $casts = [
        'costPerHalfDay' => 'decimal:2',
        'priority' => 'integer',
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

    public function offDates()
    {
        return $this->hasMany(AsScheduleWorkerOffDate::class, 'workerId');
    }

    public function offDays()
    {
        return $this->hasMany(AsScheduleWorkerOffDay::class, 'workerId');
    }

    public function activities()
    {
        return $this->belongsToMany(
            AsScheduleActivity::class,
            'as_schedule_activity_workers',
            'workerId',
            'activityId'
        );
    }

    public function isAvailableOn(\Carbon\Carbon $date): bool
    {
        $dow = (int) $date->dayOfWeek; // 0=Sunday
        if ($this->offDays()->where('dayOfWeek', $dow)->exists()) {
            return false;
        }
        if ($this->offDates()->where('offDate', $date->format('Y-m-d'))->exists()) {
            return false;
        }
        return true;
    }
}
