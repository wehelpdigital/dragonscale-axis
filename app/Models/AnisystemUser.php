<?php

namespace App\Models;

class AnisystemUser extends BaseModel
{
    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'anisystem_users';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'firstName',
        'lastName',
        'phone',
        'email',
        'password',
        'clientId',
        'status',
        'deleteStatus',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'clientId' => 'integer',
        'deleteStatus' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    /**
     * Scope to get only active rows (deleteStatus = 1).
     */
    public function scopeActive($query)
    {
        return $query->where('deleteStatus', 1);
    }

    /**
     * All subscriptions of this client (active rows only), newest first.
     */
    public function subscriptions()
    {
        return $this->hasMany(AnisystemSubscription::class, 'userId')
            ->where('anisystem_subscriptions.deleteStatus', 1)
            ->orderBy('id', 'desc');
    }

    /**
     * The most recent (highest id) active-row subscription for this client.
     */
    public function latestSubscription()
    {
        return $this->hasOne(AnisystemSubscription::class, 'userId')
            ->where('anisystem_subscriptions.deleteStatus', 1)
            ->latestOfMany('id');
    }

    /**
     * Cropping schedules owned by this AniSystem client.
     */
    public function croppingSchedules()
    {
        return $this->hasMany(AsCroppingSchedule::class, 'anisystemUserId')
            ->where('as_cropping_schedules.deleteStatus', 1);
    }

    /**
     * Convenience full name accessor.
     */
    public function getFullNameAttribute()
    {
        return trim(($this->firstName ?? '') . ' ' . ($this->lastName ?? ''));
    }

    /**
     * "Town, Province" — or whichever half is filled. Empty string if neither.
     */
    public function getLocationAttribute()
    {
        return collect([$this->city, $this->province])
            ->filter(fn ($p) => filled($p))
            ->implode(', ');
    }

    /**
     * Two-letter initials for avatar chips.
     */
    public function getInitialsAttribute()
    {
        return strtoupper(mb_substr((string) $this->firstName, 0, 1) . mb_substr((string) $this->lastName, 0, 1));
    }
}
