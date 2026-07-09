<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class RgTouristSpot extends Model
{
    protected $table = 'rg_tourist_spots';

    protected $fillable = [
        'name', 'slug', 'location', 'region_label', 'cluster_tag',
        'destination_key', 'keyword_id', 'media_id', 'description',
        'featured_order', 'status',
    ];

    public function media(): BelongsTo
    {
        return $this->belongsTo(RgMedia::class, 'media_id');
    }
}
