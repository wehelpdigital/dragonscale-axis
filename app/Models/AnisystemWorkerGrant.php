<?php

namespace App\Models;

/**
 * A worker login in AniSystem: the farm owner (boss) who invited someone, and
 * what that person may do once they accept. Workers never hold a subscription
 * of their own — access is inherited from the boss, so revoking a grant here
 * is what actually removes a worker's way into the client's farm.
 *
 * Mirrors App\Models\WorkerGrant in the AniSystem app over the shared database.
 */
class AnisystemWorkerGrant extends BaseModel
{
    protected $table = 'as_worker_grants';

    public const STATUS_PENDING = 'pending';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_REVOKED = 'revoked';

    /** Schedule access levels, in ascending order of power. */
    public const ACCESS_LEVELS = ['none', 'view', 'edit'];

    /**
     * What a grant answers per module, and the shape of each answer.
     *
     * 'level' modules take the same none/view/edit the schedule does; 'open'
     * modules are had or not had. Mirrors WorkerGrant::MODULES in AniSystem —
     * the two apps read one table, so the list has to be the same list.
     */
    public const MODULES = [
        'notes' => ['column' => 'notesAccess', 'shape' => 'level', 'label' => 'Notes'],
        'reports' => ['column' => 'reportsAccess', 'shape' => 'level', 'label' => 'Reports'],
        'maps' => ['column' => 'mapsAccess', 'shape' => 'open', 'label' => 'Maps'],
        'draw' => ['column' => 'drawAccess', 'shape' => 'open', 'label' => 'Draw'],
        'ai' => ['column' => 'aiAccess', 'shape' => 'open', 'label' => 'AI Technician'],
        'camera' => ['column' => 'cameraAccess', 'shape' => 'open', 'label' => 'Camera'],
        'video' => ['column' => 'videoAccess', 'shape' => 'open', 'label' => 'Video record'],
    ];

    protected $fillable = [
        'bossUserId',
        'workerUserId',
        'scheduleWorkerId',
        'invitedEmail',
        'inviteToken',
        'scheduleAccess',
        'communityAccess',
        'canAddNotes',
        'notesAccess',
        'reportsAccess',
        'mapsAccess',
        'drawAccess',
        'aiAccess',
        'cameraAccess',
        'videoAccess',
        'status',
        'acceptedAt',
        'deleteStatus',
    ];

    protected $casts = [
        'bossUserId' => 'integer',
        'workerUserId' => 'integer',
        'scheduleWorkerId' => 'integer',
        'communityAccess' => 'boolean',
        'canAddNotes' => 'boolean',
        'mapsAccess' => 'boolean',
        'drawAccess' => 'boolean',
        'aiAccess' => 'boolean',
        'cameraAccess' => 'boolean',
        'videoAccess' => 'boolean',
        'acceptedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    protected $hidden = [
        'inviteToken',
    ];

    /**
     * What this grant says about one module, in AniSystem's own words:
     * 'none', 'view' or 'edit'. An open/shut module answers in the same words
     * so a caller never has to know which shape it is.
     */
    public function moduleAccess(string $key): string
    {
        $spec = self::MODULES[$key] ?? null;
        if (! $spec) {
            return 'none';
        }

        if ($spec['shape'] === 'open') {
            return $this->{$spec['column']} ? 'edit' : 'none';
        }

        $level = (string) ($this->{$spec['column']} ?? 'none');

        return in_array($level, self::ACCESS_LEVELS, true) ? $level : 'none';
    }

    /** Every module's answer, keyed by column name — what the screens draw. */
    public function moduleRights(): array
    {
        $out = [];
        foreach (self::MODULES as $spec) {
            $out[$spec['column']] = $spec['shape'] === 'open'
                ? (bool) $this->{$spec['column']}
                : (string) ($this->{$spec['column']} ?? 'none');
        }

        return $out;
    }

    /** Scope to get only active rows (deleteStatus = 1). */
    public function scopeActive($query)
    {
        return $query->where('as_worker_grants.deleteStatus', 1);
    }

    /** The farm owner who issued this grant. */
    public function boss()
    {
        return $this->belongsTo(AnisystemUser::class, 'bossUserId');
    }

    /** The AniSystem account the worker signs in with (null until accepted). */
    public function workerUser()
    {
        return $this->belongsTo(AnisystemUser::class, 'workerUserId');
    }

    /** The roster entry this login is tied to, when the boss linked one. */
    public function scheduleWorker()
    {
        return $this->belongsTo(AsScheduleWorker::class, 'scheduleWorkerId');
    }

    /**
     * A grant is only usable while it is active AND not soft-deleted; the
     * listing shows 'deleted' separately so an admin can tell a revoked grant
     * from one the boss removed entirely.
     */
    public function getEffectiveStatusAttribute()
    {
        if ((int) $this->deleteStatus !== 1) {
            return 'deleted';
        }

        return $this->status;
    }

    /** Human label for the schedule access column. */
    public function getAccessLabelAttribute()
    {
        return [
            'none' => 'No schedule access',
            'view' => 'View only',
            'edit' => 'Can edit',
        ][$this->scheduleAccess] ?? $this->scheduleAccess;
    }
}
