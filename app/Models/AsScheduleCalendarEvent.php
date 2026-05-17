<?php

namespace App\Models;

class AsScheduleCalendarEvent extends BaseModel
{
    protected $table = 'as_schedule_calendar_events';

    protected $fillable = [
        'generationId',
        'croppingScheduleId',
        'groupingId',
        'lotId',
        'eventType',
        'activityId',
        'irrigationId',
        'eventTitle',
        'scheduledDate',
        'originalDate',
        'timeOfDay',
        'targetDayValue',
        'priority',
        'status',
        'completedAt',
        'completedBy',
        'extraCost',
        'extraCostDescription',
        'remarks',
        'assignedWorkerIds',
        'sequenceOrder',
        'deleteStatus',
    ];

    protected $casts = [
        'scheduledDate' => 'date:Y-m-d',
        'originalDate' => 'date:Y-m-d',
        'completedAt' => 'datetime:Y-m-d H:i:s',
        'extraCost' => 'decimal:2',
        'targetDayValue' => 'integer',
        'sequenceOrder' => 'integer',
        'assignedWorkerIds' => 'array',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }

    public function scopePending($q)
    {
        return $q->where('status', 'pending');
    }

    public function scopeCompleted($q)
    {
        return $q->where('status', 'completed');
    }

    public function generation()
    {
        return $this->belongsTo(AsScheduleGeneration::class, 'generationId');
    }

    public function schedule()
    {
        return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId');
    }

    public function lot()
    {
        return $this->belongsTo(AsScheduleLot::class, 'lotId');
    }

    public function grouping()
    {
        return $this->belongsTo(AsGenerationGrouping::class, 'groupingId');
    }

    public function activity()
    {
        return $this->belongsTo(AsScheduleActivity::class, 'activityId');
    }

    public function irrigation()
    {
        return $this->belongsTo(AsScheduleIrrigation::class, 'irrigationId');
    }
}
