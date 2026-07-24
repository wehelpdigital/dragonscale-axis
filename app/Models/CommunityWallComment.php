<?php

namespace App\Models;

class CommunityWallComment extends BaseModel
{
    protected $table = 'as_community_wall_comments';

    protected $fillable = ['wallPostId', 'userId', 'body', 'deleteStatus'];

    public function scopeActive($q) { return $q->where('as_community_wall_comments.deleteStatus', 1); }

    public function author() { return $this->belongsTo(AnisystemUser::class, 'userId'); }
}
