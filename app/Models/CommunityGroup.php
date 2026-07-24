<?php

namespace App\Models;

class CommunityGroup extends BaseModel
{
    protected $table = 'as_community_groups';

    protected $fillable = ['name', 'slug', 'description', 'coverImagePath', 'createdByUserId', 'deleteStatus'];

    public function scopeActive($q) { return $q->where('as_community_groups.deleteStatus', 1); }

    public function creator() { return $this->belongsTo(AnisystemUser::class, 'createdByUserId'); }
    public function members() { return $this->hasMany(CommunityGroupMember::class, 'groupId')->where('as_community_group_members.deleteStatus', 1); }
    public function posts() { return $this->hasMany(CommunityGroupPost::class, 'groupId')->where('as_community_group_posts.deleteStatus', 1); }
}
