<?php

namespace App\Models;

class CommunityGroupReply extends BaseModel
{
    protected $table = 'as_community_group_replies';

    protected $fillable = ['postId', 'userId', 'body', 'deleteStatus'];

    public function scopeActive($q) { return $q->where('as_community_group_replies.deleteStatus', 1); }

    public function author() { return $this->belongsTo(AnisystemUser::class, 'userId'); }
}
