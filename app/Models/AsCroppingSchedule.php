<?php

namespace App\Models;

class AsCroppingSchedule extends BaseModel
{
    protected $table = 'as_cropping_schedules';

    protected $fillable = [
        'usersId',
        'title',
        'description',
        'dayType',
        'defaultStaggerDays',
        'status',
        'isActive',
        'deleteStatus',
    ];

    protected $casts = [
        'defaultStaggerDays' => 'integer',
        'isActive' => 'boolean',
        'deleteStatus' => 'integer',
    ];

    public function scopeActive($q)
    {
        return $q->where('deleteStatus', 1);
    }

    public function scopeForUser($q, $userId)
    {
        return $q->where('usersId', $userId);
    }

    public function lots()
    {
        return $this->hasMany(AsScheduleLot::class, 'croppingScheduleId')->where('as_schedule_lots.deleteStatus', 1);
    }

    public function workers()
    {
        return $this->hasMany(AsScheduleWorker::class, 'croppingScheduleId')
            ->where('as_schedule_workers.deleteStatus', 1)
            ->orderBy('priority', 'asc');
    }

    public function protocol()
    {
        return $this->hasOne(AsScheduleProtocol::class, 'croppingScheduleId')->where('as_schedule_protocols.deleteStatus', 1);
    }

    public function materials()
    {
        return $this->hasMany(AsScheduleMaterial::class, 'croppingScheduleId')->where('as_schedule_materials.deleteStatus', 1);
    }

    public function services()
    {
        return $this->hasMany(AsScheduleService::class, 'croppingScheduleId')->where('as_schedule_services.deleteStatus', 1);
    }

    public function activities()
    {
        return $this->hasMany(AsScheduleActivity::class, 'croppingScheduleId')
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activities.isDraft', 0)
            ->orderBy('targetDate', 'asc');
    }

    public function drafts()
    {
        return $this->hasMany(AsScheduleActivity::class, 'croppingScheduleId')
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activities.isDraft', 1)
            ->orderBy('updated_at', 'desc');
    }

    public function irrigations()
    {
        return $this->hasMany(AsScheduleIrrigation::class, 'croppingScheduleId')
            ->where('as_schedule_irrigations.deleteStatus', 1)
            ->orderBy('startDay', 'asc');
    }

    public function generations()
    {
        return $this->hasMany(AsScheduleGeneration::class, 'croppingScheduleId')->where('as_schedule_generations.deleteStatus', 1);
    }

    public function currentGeneration()
    {
        return $this->hasOne(AsScheduleGeneration::class, 'croppingScheduleId')
            ->where('as_schedule_generations.deleteStatus', 1)
            ->where('isCurrent', 1)
            ->orderBy('id', 'desc');
    }

    public function defaultGroupings()
    {
        return $this->hasMany(AsScheduleDefaultGrouping::class, 'croppingScheduleId')
            ->where('as_schedule_default_groupings.deleteStatus', 1)
            ->orderBy('groupOrder');
    }

    /**
     * Return a list of human-readable issues preventing calendar generation.
     * Empty array means the schedule is ready.
     *
     * Uses *_count attributes when present (withCount()), otherwise queries.
     */
    public function getReadinessIssues(): array
    {
        $lotsCount       = $this->lots_count       ?? $this->lots()->count();
        $workersCount    = $this->workers_count    ?? $this->workers()->count();
        $activitiesCount = $this->activities_count ?? $this->activities()->count();

        $issues = [];
        if ($lotsCount === 0)       $issues[] = 'Add at least one lot';
        if ($workersCount === 0)    $issues[] = 'Add at least one worker';
        if ($activitiesCount === 0) $issues[] = 'Add at least one activity';
        return $issues;
    }

    public function isReadyToGenerate(): bool
    {
        return count($this->getReadinessIssues()) === 0;
    }
}
