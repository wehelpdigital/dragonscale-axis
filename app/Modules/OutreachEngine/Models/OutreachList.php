<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;

/**
 * A finalised list: contacts a human chose to keep out of a sweep.
 *
 * totalMembers is a cache of the member count. It is written by
 * ListMembershipService and by nothing else - two writers would drift, and a
 * list that claims 400 contacts while holding 380 is worse than no count.
 */
class OutreachList extends BaseModel
{
    protected $table = 'outreach_lists';

    protected $fillable = [
        'usersId',
        'name',
        'description',
        'totalMembers',
        'delete_status',
    ];

    protected $casts = [
        'usersId' => 'integer',
        'totalMembers' => 'integer',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForUser($query, $userId)
    {
        return $query->where('usersId', $userId);
    }

    /**
     * Membership rows for this list.
     */
    public function members()
    {
        return $this->hasMany(OutreachListMember::class, 'listId');
    }

    /**
     * The contacts themselves, through the membership table.
     */
    public function leads()
    {
        return $this->belongsToMany(OutreachLead::class, 'outreach_list_members', 'listId', 'leadId')
            ->wherePivot('delete_status', 'active');
    }

    /**
     * Campaigns built from this list.
     */
    public function tasks()
    {
        return $this->hasMany(OutreachTask::class, 'listId');
    }
}
