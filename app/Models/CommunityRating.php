<?php

namespace App\Models;

class CommunityRating extends BaseModel
{
    protected $table = 'as_community_ratings';

    protected $fillable = ['croppingScheduleId', 'anisystemUserId', 'rating', 'review', 'deleteStatus'];

    protected $casts = ['rating' => 'integer', 'deleteStatus' => 'integer'];

    public function scopeActive($q) { return $q->where('as_community_ratings.deleteStatus', 1); }

    public function author() { return $this->belongsTo(AnisystemUser::class, 'anisystemUserId'); }
    public function schedule() { return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId'); }
}
