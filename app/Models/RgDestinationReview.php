<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RgDestinationReview extends Model
{
    protected $table = 'rg_destination_reviews';
    protected $guarded = ['id'];
    protected $casts = ['review_date' => 'date', 'is_featured' => 'boolean'];
}
