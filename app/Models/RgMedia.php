<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RgMedia extends Model
{
    protected $table = 'rg_media';

    protected $fillable = [
        'filename', 'path', 'mime', 'size_bytes', 'kind',
        'width', 'height', 'alt', 'caption', 'source', 'credit',
        'source_url', 'meta_json',
    ];
}
