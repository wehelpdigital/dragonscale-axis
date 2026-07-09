<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RgBlogComment extends Model
{
    protected $table = 'rg_blog_comments';
    protected $guarded = ['id'];
    protected $casts = ['is_seeded' => 'boolean'];
}
