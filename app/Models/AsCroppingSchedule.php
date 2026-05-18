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

    public function versions()
    {
        return $this->hasMany(AsScheduleActivityVersion::class, 'croppingScheduleId')
            ->where('as_schedule_activity_versions.deleteStatus', 1)
            ->orderBy('versionOrder', 'asc')
            ->orderBy('id', 'asc');
    }

    public function activeVersion()
    {
        return $this->hasOne(AsScheduleActivityVersion::class, 'croppingScheduleId')
            ->where('as_schedule_activity_versions.deleteStatus', 1)
            ->where('as_schedule_activity_versions.isActive', 1);
    }

    /**
     * Activities are scoped to the schedule's currently-active version. This
     * makes every consumer ($schedule->activities) automatically reflect the
     * selected version — calendar generator, worker presentation, export,
     * labor summary all inherit the filter for free.
     */
    public function activities()
    {
        return $this->hasMany(AsScheduleActivity::class, 'croppingScheduleId')
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activities.isDraft', 0)
            ->whereIn('as_schedule_activities.versionId', function ($sub) {
                // Correlate against the activity row's own croppingScheduleId
                // so this works whether the relation is loaded as a property
                // (auto-join to parent) or invoked as a method (no parent in
                // scope). Activity rows always carry croppingScheduleId.
                $sub->select('id')
                    ->from('as_schedule_activity_versions')
                    ->whereColumn('as_schedule_activity_versions.croppingScheduleId', 'as_schedule_activities.croppingScheduleId')
                    ->where('as_schedule_activity_versions.isActive', 1)
                    ->where('as_schedule_activity_versions.deleteStatus', 1);
            })
            ->orderBy('targetDate', 'asc');
    }

    public function drafts()
    {
        return $this->hasMany(AsScheduleActivity::class, 'croppingScheduleId')
            ->where('as_schedule_activities.deleteStatus', 1)
            ->where('as_schedule_activities.isDraft', 1)
            ->whereIn('as_schedule_activities.versionId', function ($sub) {
                // Correlate against the activity row's own croppingScheduleId
                // so this works whether the relation is loaded as a property
                // (auto-join to parent) or invoked as a method (no parent in
                // scope). Activity rows always carry croppingScheduleId.
                $sub->select('id')
                    ->from('as_schedule_activity_versions')
                    ->whereColumn('as_schedule_activity_versions.croppingScheduleId', 'as_schedule_activities.croppingScheduleId')
                    ->where('as_schedule_activity_versions.isActive', 1)
                    ->where('as_schedule_activity_versions.deleteStatus', 1);
            })
            ->orderBy('updated_at', 'desc');
    }

    /**
     * Per-date commentary attached to the activity timeline. Scoped to the
     * schedule's active version using the same correlated-subquery trick as
     * activities() so the export view, the worker presentation, and the
     * setup screen all see the same notes for the currently-selected branch.
     */
    public function dateNotes()
    {
        return $this->hasMany(AsScheduleDateNote::class, 'croppingScheduleId')
            ->where('as_schedule_date_notes.deleteStatus', 1)
            ->whereIn('as_schedule_date_notes.versionId', function ($sub) {
                $sub->select('id')
                    ->from('as_schedule_activity_versions')
                    ->whereColumn('as_schedule_activity_versions.croppingScheduleId', 'as_schedule_date_notes.croppingScheduleId')
                    ->where('as_schedule_activity_versions.isActive', 1)
                    ->where('as_schedule_activity_versions.deleteStatus', 1);
            })
            ->orderBy('noteDate', 'asc');
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
