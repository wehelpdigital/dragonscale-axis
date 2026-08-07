<?php

namespace App\Models;

class AsCommunityBlogPost extends BaseModel
{
    protected $table = 'as_community_blog_posts';

    protected $fillable = [
        'title', 'slug', 'coverImagePath', 'excerpt', 'body',
        'authorName', 'isPublished', 'publishedAt', 'viewCount', 'deleteStatus',
    ];

    protected $casts = [
        'isPublished' => 'boolean',
        'publishedAt' => 'datetime',
    ];

    public function scopeActive($q)
    {
        return $q->where('as_community_blog_posts.deleteStatus', 1);
    }

    public function comments()
    {
        return $this->hasMany(AsCommunityBlogComment::class, 'blogPostId')
            ->where('as_community_blog_comments.deleteStatus', 1);
    }
}
