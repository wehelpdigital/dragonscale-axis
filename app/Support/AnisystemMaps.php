<?php

namespace App\Support;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Which of anee.io's saved maps belong to a season (2026-09-30).
 *
 * anee moved Maps and Draw out of the seasons into Global and Quick Tools:
 * a saved map (as_schedule_map_saves) is now its OWNER's -- scheduleId 0,
 * userId -- and a season uses it through a link (as_schedule_map_links),
 * made when it is drawn in the season's Collab Room, worn by a lot, attached
 * to an activity or a day, or tagged. `originScheduleId` is the season it
 * was first drawn in. (anee's twin of this is App\Support\MapAccess.)
 *
 * The console shows a season's maps as: the ones linked to it, the season
 * owner's own maps, and -- for rows written before the move or by an older
 * copy of either app -- any still carrying the season's id.
 */
class AnisystemMaps
{
    /** A season's maps, as a query on `as_schedule_map_saves` (alias optional). */
    public static function forSeason(object $schedule, string $alias = ''): Builder
    {
        $col = fn (string $c) => $alias !== '' ? $alias . '.' . $c : $c;
        $q = $alias !== '' ? DB::table('as_schedule_map_saves as ' . $alias) : DB::table('as_schedule_map_saves');

        return $q->where($col('deleteStatus'), 1)->where(function ($w) use ($schedule, $col) {
            self::seasonClause($w, (int) $schedule->id, (int) ($schedule->anisystemUserId ?? 0), $col);
        });
    }

    /** The same rule, added to a query someone else built. */
    public static function whereSeason($query, int $scheduleId, int $ownerId, string $alias = ''): void
    {
        $col = fn (string $c) => $alias !== '' ? $alias . '.' . $c : $c;
        $query->where(fn ($w) => self::seasonClause($w, $scheduleId, $ownerId, $col));
    }

    /** Remember that a season uses a map. Idempotent. */
    public static function link(int $saveId, int $scheduleId): void
    {
        if ($saveId <= 0 || $scheduleId <= 0) {
            return;
        }
        DB::table('as_schedule_map_links')->insertOrIgnore([
            'saveId' => $saveId, 'scheduleId' => $scheduleId, 'created_at' => now(), 'updated_at' => now(),
        ]);
    }

    /** Where a saved map opens on anee.io. */
    public static function aneeUrl(int $saveId): string
    {
        return rtrim((string) config('anisystem.url'), '/') . '/app/maps?save=' . $saveId;
    }

    private static function seasonClause($w, int $scheduleId, int $ownerId, callable $col): void
    {
        $w->where($col('scheduleId'), $scheduleId)
            ->orWhereIn($col('id'), DB::table('as_schedule_map_links')->where('scheduleId', $scheduleId)->select('saveId'));
        if ($ownerId > 0) {
            $w->orWhere(fn ($o) => $o->where($col('scheduleId'), 0)->where($col('userId'), $ownerId));
        }
    }
}
