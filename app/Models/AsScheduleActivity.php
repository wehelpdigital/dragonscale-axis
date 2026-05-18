<?php

namespace App\Models;

class AsScheduleActivity extends BaseModel
{
    protected $table = 'as_schedule_activities';

    /**
     * Canonical catalog of activity types. Keys are the slugs stored in the
     * activityType column, values are the human-readable labels rendered in
     * the UI. Single source of truth — controller validation, view rendering,
     * the modal select, and the auto-categorizer all read from here.
     */
    public const ACTIVITY_TYPES = [
        'land_prep'      => 'Land Preparation',
        'seed_treatment' => 'Seed Treatment',
        'planting'       => 'Planting',
        'irrigation'     => 'Irrigation',
        'fertilizer'     => 'Fertilizer (Granular)',
        'foliar_spray'   => 'Foliar Spray',
        'microbial'      => 'Microbial / Bio',
        'harvest'        => 'Harvest',
        'monitoring'     => 'Monitoring',
        'other'          => 'Other',
    ];

    protected $fillable = [
        'croppingScheduleId',
        'versionId',
        'sourceActivityId',
        'activityTitle',
        'targetDate',
        'targetEndDate',
        'priority',
        'activityType',
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

    public function version()
    {
        return $this->belongsTo(AsScheduleActivityVersion::class, 'versionId');
    }

    public function sourceActivity()
    {
        return $this->belongsTo(self::class, 'sourceActivityId');
    }

    public function scopeForVersion($q, $versionId)
    {
        return $q->where('versionId', $versionId);
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
