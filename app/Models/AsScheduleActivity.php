<?php

namespace App\Models;

class AsScheduleActivity extends BaseModel
{
    protected $table = 'as_schedule_activities';

    protected $fillable = [
        'croppingScheduleId',
        'activityTitle',
        'targetDate',
        'targetEndDate',
        'priority',
        'isDayZero',
        'isDraft',
        'description',
        'timeRequired',
        'sequenceOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'targetDate' => 'date:Y-m-d',
        'targetEndDate' => 'date:Y-m-d',
        'isDayZero' => 'boolean',
        'isDraft' => 'boolean',
        'sequenceOrder' => 'integer',
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

    public function items()
    {
        return $this->hasMany(AsScheduleActivityItem::class, 'activityId')->where('as_schedule_activity_items.deleteStatus', 1);
    }

    public function lots()
    {
        return $this->belongsToMany(
            AsScheduleLot::class,
            'as_schedule_activity_lots',
            'activityId',
            'lotId'
        );
    }

    public function workers()
    {
        return $this->belongsToMany(
            AsScheduleWorker::class,
            'as_schedule_activity_workers',
            'activityId',
            'workerId'
        );
    }
}
