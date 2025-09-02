<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EcomProductVariantVideo extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'ecom_products_variants_videos';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'ecomVariantsId',
        'videoLink',
        'videoOrder',
        'deleteStatus',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'videoOrder' => 'integer',
        'deleteStatus' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    /**
     * Get the variant that owns the video.
     */
    public function variant()
    {
        return $this->belongsTo(EcomProductVariant::class, 'ecomVariantsId', 'id');
    }

    /**
     * Scope to get only active videos (deleteStatus = 1 or true)
     */
    public function scopeActive($query)
    {
        return $query->where(function($q) {
            $q->where('deleteStatus', 1)
              ->orWhere('deleteStatus', true);
        });
    }

    /**
     * Scope to filter by variant ID
     */
    public function scopeByVariant($query, $variantId)
    {
        return $query->where('ecomVariantsId', $variantId);
    }

    /**
     * Get the YouTube video ID from the video link
     */
    public function getVideoIdAttribute()
    {
        if (preg_match('/embed\/([a-zA-Z0-9_-]+)/', $this->videoLink, $matches)) {
            return $matches[1];
        }
        return null;
    }

    /**
     * Get the YouTube thumbnail URL
     */
    public function getThumbnailUrlAttribute()
    {
        $videoId = $this->video_id;
        if ($videoId) {
            return "https://img.youtube.com/vi/{$videoId}/maxresdefault.jpg";
        }
        return null;
    }
}
