<?php

namespace App\Models;

class CommunityWallPost extends BaseModel
{
    protected $table = 'as_community_wall_posts';

    protected $fillable = ['wallUserId', 'authorUserId', 'body', 'imagePath', 'deleteStatus'];

    public function scopeActive($q) { return $q->where('as_community_wall_posts.deleteStatus', 1); }

    public function author() { return $this->belongsTo(AnisystemUser::class, 'authorUserId'); }
    public function wallOwner() { return $this->belongsTo(AnisystemUser::class, 'wallUserId'); }
    public function comments() { return $this->hasMany(CommunityWallComment::class, 'wallPostId')->where('as_community_wall_comments.deleteStatus', 1); }
}
