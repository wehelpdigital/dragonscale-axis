<?php

namespace App\Models;

class CommunityGroupMember extends BaseModel
{
    protected $table = 'as_community_group_members';

    protected $fillable = ['groupId', 'userId', 'role', 'deleteStatus'];

    public function scopeActive($q) { return $q->where('as_community_group_members.deleteStatus', 1); }

    public function user() { return $this->belongsTo(AnisystemUser::class, 'userId'); }
}
