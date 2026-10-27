<?php

namespace App\Models;

use Illuminate\Support\Carbon;

/**
 * A game's settings, one row per key (games_settings).
 *
 *   analytics             the analytics codes as the game server last
 *                         confirmed them (JSON; secrets are never stored, only
 *                         whether the game holds one)
 *   analyticsFetchedAt    when they were last read from the game
 *   analyticsPushedAt     when a save from the Settings page last reached it
 *   analyticsPushStatus   accepted | rejected | failed
 *   analyticsPushMessage  what the game (or the connection) said
 *   usdToPhpRate          the peso rate game orders are logged into /ecom-orders at
 *   lastRunAt/lastRunBy   the last sync, and what started it (scheduler, admin)
 */
class GameSetting extends BaseModel
{
    protected $table = 'games_settings';

    protected $fillable = ['gameKey', 'settingKey', 'settingValue'];

    public static function getValue(string $gameKey, string $key, $default = null)
    {
        return static::where('gameKey', $gameKey)->where('settingKey', $key)->value('settingValue') ?? $default;
    }

    public static function setValue(string $gameKey, string $key, $value): void
    {
        static::updateOrCreate(
            ['gameKey' => $gameKey, 'settingKey' => $key],
            ['settingValue' => $value === null ? null : (string) $value]
        );
    }

    public static function getJson(string $gameKey, string $key, array $default = []): array
    {
        $decoded = json_decode((string) static::getValue($gameKey, $key, ''), true);

        return is_array($decoded) ? $decoded : $default;
    }

    public static function setJson(string $gameKey, string $key, array $value): void
    {
        static::setValue($gameKey, $key, json_encode($value));
    }

    public static function getTime(string $gameKey, string $key): ?Carbon
    {
        $at = static::getValue($gameKey, $key);

        return $at ? Carbon::parse($at) : null;
    }

    public static function touchTime(string $gameKey, string $key): void
    {
        static::setValue($gameKey, $key, now()->format('Y-m-d H:i:s'));
    }
}
