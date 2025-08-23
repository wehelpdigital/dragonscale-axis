<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AsImageLibrary extends Model
{
    protected $table = 'as_image_library';

    protected $fillable = [
        'imageUrl'
    ];
}
