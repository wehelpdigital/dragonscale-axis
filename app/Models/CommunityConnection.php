<?php

namespace App\Models;

class CommunityConnection extends BaseModel
{
    protected $table = 'as_community_connections';

    protected $fillable = ['userId', 'friendUserId', 'status', 'respondedAt', 'deleteStatus'];

    protected $casts = ['respondedAt' => 'datetime:Y-m-d H:i:s'];

    public function scopeActive($q) { return $q->where('as_community_connections.deleteStatus', 1); }

    public static function connectedIds(int $userId): array
    {
        return static::query()->where('deleteStatus', 1)->where('status', 'accepted')
            ->where(fn ($q) => $q->where('userId', $userId)->orWhere('friendUserId', $userId))
            ->get()
            ->map(fn ($r) => (int) $r->userId === $userId ? (int) $r->friendUserId : (int) $r->userId)
            ->unique()->values()->all();
    }
}
