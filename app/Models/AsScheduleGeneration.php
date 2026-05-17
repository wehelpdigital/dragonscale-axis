<?php

namespace App\Models;

class AsScheduleGeneration extends BaseModel
{
    protected $table = 'as_schedule_generations';

    protected $fillable = [
        'croppingScheduleId',
        'generationNumber',
        'seasonStartDate',
        'generatedAt',
        'generatedBy',
        'isCurrent',
        'notes',
        'deleteStatus',
    ];

    protected $casts = [
        'seasonStartDate' => 'date:Y-m-d',
        'generatedAt' => 'datetime:Y-m-d H:i:s',
        'isCurrent' => 'integer',
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

    public function groupings()
    {
        return $this->hasMany(AsGenerationGrouping::class, 'generationId')
            ->where('as_generation_groupings.deleteStatus', 1)
            ->orderBy('groupOrder');
    }

    public function offDates()
    {
        return $this->hasMany(AsGenerationOffDate::class, 'generationId');
    }

    public function offDays()
    {
        return $this->hasMany(AsGenerationOffDay::class, 'generationId');
    }

    public function events()
    {
        return $this->hasMany(AsScheduleCalendarEvent::class, 'generationId')
            ->where('as_schedule_calendar_events.deleteStatus', 1)
            ->orderBy('scheduledDate');
    }
}
