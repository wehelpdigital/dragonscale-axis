<?php

namespace App\Models;

class AsCommunityBlogComment extends BaseModel
{
    protected $table = 'as_community_blog_comments';

    protected $fillable = ['blogPostId', 'userId', 'body', 'deleteStatus'];

    public function scopeActive($q)
    {
        return $q->where('as_community_blog_comments.deleteStatus', 1);
    }

    public function author()
    {
        return $this->belongsTo(AnisystemUser::class, 'userId');
    }
}
