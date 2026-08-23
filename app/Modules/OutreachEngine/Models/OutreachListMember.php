<?php

namespace App\Modules\OutreachEngine\Models;

use App\Models\BaseModel;

/**
 * One contact's place in one finalised list.
 *
 * sourceBatchId records which sweep the contact came from. It is kept here
 * rather than read back off the lead because a batch can be removed from the
 * batch screen later, and the list should still be able to say where its
 * contacts originally came from.
 */
class OutreachListMember extends BaseModel
{
    protected $table = 'outreach_list_members';

    protected $fillable = [
        'usersId',
        'listId',
        'leadId',
        'sourceBatchId',
        'addedAt',
        'delete_status',
    ];

    protected $casts = [
        'usersId' => 'integer',
        'listId' => 'integer',
        'leadId' => 'integer',
        'addedAt' => 'datetime:Y-m-d H:i:s',
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

    public function list()
    {
        return $this->belongsTo(OutreachList::class, 'listId');
    }

    public function lead()
    {
        return $this->belongsTo(OutreachLead::class, 'leadId');
    }
}
