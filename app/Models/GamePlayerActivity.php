<?php

namespace App\Models;

/**
 * One player's play on one day (games_player_activity). The day is the game
 * server's, in UTC; a past day's row is final, today's may still grow, so the
 * sync fetches again from yesterday every time.
 */
class GamePlayerActivity extends BaseModel
{
    protected $table = 'games_player_activity';

    protected $fillable = [
        'gameKey',
        'remotePlayerId',
        'activityDate',
        'platform',
        'sessions',
        'matchesPlayed',
        'matchesWon',
        'minutesPlayed',
        'firstSeenAt',
        'lastSeenAt',
        'delete_status',
    ];

    protected $casts = [
        'remotePlayerId' => 'integer',
        'activityDate' => 'date:Y-m-d',
        'sessions' => 'integer',
        'matchesPlayed' => 'integer',
        'matchesWon' => 'integer',
        'minutesPlayed' => 'integer',
        'firstSeenAt' => 'datetime',
        'lastSeenAt' => 'datetime',
        'created_at' => 'datetime:Y-m-d H:i:s',
        'updated_at' => 'datetime:Y-m-d H:i:s',
    ];

    public function scopeActive($query)
    {
        return $query->where('delete_status', 'active');
    }

    public function scopeForGame($query, string $gameKey)
    {
        return $query->where('gameKey', $gameKey);
    }
}
