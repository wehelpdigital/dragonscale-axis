<?php

namespace App\Models;

class CommunityGroup extends BaseModel
{
    protected $table = 'as_community_groups';

    protected $fillable = [
        'name', 'slug', 'description',
        // The door: whether the room is listed at all, how somebody gets in,
        // and the password if it asks for one.
        'privacy', 'joinMode', 'joinPassword',
        'coverImagePath', 'bannerImagePath', 'bannerPos',
        'createdByUserId', 'deleteStatus',
    ];

    protected $casts = [
        // Encrypted, not hashed — and it MUST match the client app's cast,
        // because the organiser has to be able to read it back to tell the
        // next person. A plaintext write here would be unreadable there.
        'joinPassword' => 'encrypted',
        'bannerPos' => 'integer',
    ];

    public function scopeActive($q) { return $q->where('as_community_groups.deleteStatus', 1); }

    public function creator() { return $this->belongsTo(AnisystemUser::class, 'createdByUserId'); }
    public function members() { return $this->hasMany(CommunityGroupMember::class, 'groupId')->where('as_community_group_members.deleteStatus', 1); }
    public function posts() { return $this->hasMany(CommunityGroupPost::class, 'groupId')->where('as_community_group_posts.deleteStatus', 1); }
}
