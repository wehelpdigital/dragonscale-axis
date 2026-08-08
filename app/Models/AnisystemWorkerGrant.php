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

    protected $fillable = [
        'bossUserId',
        'workerUserId',
        'scheduleWorkerId',
        'invitedEmail',
        'inviteToken',
        'scheduleAccess',
        'communityAccess',
        'status',
        'acceptedAt',
        'deleteStatus',
    ];

    protected $casts = [
        'bossUserId' => 'integer',
        'workerUserId' => 'integer',
        'scheduleWorkerId' => 'integer',
        'communityAccess' => 'boolean',
        'acceptedAt' => 'datetime',
        'deleteStatus' => 'integer',
    ];

    protected $hidden = [
        'inviteToken',
    ];

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
