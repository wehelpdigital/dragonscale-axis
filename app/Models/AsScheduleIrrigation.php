<?php

namespace App\Models;

class AsScheduleIrrigation extends BaseModel
{
    protected $table = 'as_schedule_irrigations';

    protected $fillable = [
        'croppingScheduleId',
        'irrigationTitle',
        'description',
        'startDay',
        'endDay',
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
