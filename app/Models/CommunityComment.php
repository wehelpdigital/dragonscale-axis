<?php

namespace App\Models;

class CommunityComment extends BaseModel
{
    protected $table = 'as_community_comments';

    protected $fillable = ['croppingScheduleId', 'anisystemUserId', 'parentId', 'body', 'isQuestion', 'deleteStatus'];

    protected $casts = ['parentId' => 'integer', 'isQuestion' => 'boolean', 'deleteStatus' => 'integer'];

    public function scopeActive($q) { return $q->where('as_community_comments.deleteStatus', 1); }

    public function author() { return $this->belongsTo(AnisystemUser::class, 'anisystemUserId'); }
    public function schedule() { return $this->belongsTo(AsCroppingSchedule::class, 'croppingScheduleId'); }
    public function replies() { return $this->hasMany(self::class, 'parentId')->where('as_community_comments.deleteStatus', 1); }
}
