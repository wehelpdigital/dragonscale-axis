<?php

namespace App\Models;

/**
 * Where a game's sync of one data type got to, and how it went
 * (games_sync_states): players, activity, orders, ecom (logging into
 * /ecom-orders), acumbamail, analytics.
 */
class GameSyncState extends BaseModel
{
    protected $table = 'games_sync_states';

    public const TYPES = [
        'players' => 'Players',
        'activity' => 'Daily activity',
        'orders' => 'Orders',
        'ecom' => 'Logged to /ecom-orders',
        'acumbamail' => 'Acumbamail',
        'analytics' => 'Analytics codes',
    ];

    protected $fillable = [
        'gameKey',
        'dataType',
        'cursorValue',
        'resumeCursor',
        'resumePage',
        'lastStatus',
        'lastMessage',
        'lastCount',
        'remoteTotal',
        'failCount',
        'lastRunAt',
        'lastSuccessAt',
        'delete_status',
    ];

    protected $casts = [
        'resumePage' => 'integer',
        'lastCount' => 'integer',
        'remoteTotal' => 'integer',
        'failCount' => 'integer',
        'lastRunAt' => 'datetime',
        'lastSuccessAt' => 'datetime',
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

    public static function for(string $gameKey, string $dataType): self
    {
        return static::firstOrCreate(
            ['gameKey' => $gameKey, 'dataType' => $dataType],
            ['delete_status' => 'active', 'lastCount' => 0, 'failCount' => 0]
        );
    }

    public function markRunning(): void
    {
        $this->forceFill(['lastStatus' => 'running', 'lastRunAt' => now()])->save();
    }

    /** ok, or partial when the run stopped early and the next one carries on. */
    public function markDone(int $count, string $message, bool $partial = false): void
    {
        $this->forceFill([
            'lastStatus' => $partial ? 'partial' : 'ok',
            'lastMessage' => mb_substr($message, 0, 1000),
            'lastCount' => $count,
            'failCount' => 0,
            'lastSuccessAt' => now(),
        ])->save();
    }

    public function markFailed(string $message, int $count = 0): void
    {
        $this->forceFill([
            'lastStatus' => 'failed',
            'lastMessage' => mb_substr($message, 0, 1000),
            'lastCount' => $count,
            'failCount' => $this->failCount + 1,
            'lastRunAt' => $this->lastRunAt ?? now(),
        ])->save();
    }

    public function markSkipped(string $message): void
    {
        $this->forceFill([
            'lastStatus' => 'skipped',
            'lastMessage' => mb_substr($message, 0, 1000),
            'lastCount' => 0,
            'lastRunAt' => now(),
        ])->save();
    }

    public function label(): string
    {
        return self::TYPES[$this->dataType] ?? ucfirst($this->dataType);
    }
}
