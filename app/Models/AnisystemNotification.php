<?php

namespace App\Models;

class AnisystemNotification extends BaseModel
{
    protected $table = 'anisystem_notifications';

    protected $fillable = ['userId', 'type', 'title', 'body', 'url', 'actorUserId', 'croppingScheduleId', 'readAt', 'deleteStatus'];

    protected $casts = ['readAt' => 'datetime:Y-m-d H:i:s'];

    public function scopeActive($q) { return $q->where('anisystem_notifications.deleteStatus', 1); }

    public function user() { return $this->belongsTo(AnisystemUser::class, 'userId'); }
}
