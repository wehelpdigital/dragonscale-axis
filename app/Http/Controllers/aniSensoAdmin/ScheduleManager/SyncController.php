<?php

namespace App\Http\Controllers\aniSensoAdmin\ScheduleManager;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

/**
 * Live-sync polling endpoint for the setup page.
 *
 * Each open tab polls this every few seconds with its per-tab client id.
 * The response carries the schedule's current syncVersion (bumped by the
 * TouchScheduleSync middleware on every mutation) plus a presence list of
 * the other tabs currently viewing the same schedule, so the page can show
 * "who's here" and refresh itself when someone else changes something.
 */
class SyncController extends BaseScheduleController
{
    /** Viewers with no poll in this many seconds are considered gone. */
    private const PRESENCE_STALE_SECS = 12;

    public function status(Request $request)
    {
        $schedule = $this->scheduleFromRequest($request);

        $clientId = substr((string) $request->query('clientId', ''), 0, 40);
        $state    = $request->query('state');
        if (!in_array($state, ['idle', 'editing', 'dragging'], true)) {
            $state = 'idle';
        }

        // Presence: one small cache entry per schedule, clientId → viewer.
        // File cache means a read-modify-write race between two polls can
        // momentarily drop a viewer; it self-heals on their next poll.
        $viewers = [];
        if ($clientId !== '') {
            $key = 'smgr-presence:' . $schedule->id;
            $now = time();
            $all = Cache::get($key, []);
            $all = array_filter(is_array($all) ? $all : [], function ($v) use ($now) {
                return ($v['ts'] ?? 0) > $now - self::PRESENCE_STALE_SECS;
            });
            $all[$clientId] = [
                'name'   => (string) (optional(Auth::user())->name ?: 'Someone'),
                'userId' => Auth::id(),
                'state'  => $state,
                'ts'     => $now,
            ];
            Cache::put($key, $all, 60);

            foreach ($all as $cid => $v) {
                if ($cid === $clientId) {
                    continue;
                }
                $viewers[] = [
                    'name'  => $v['name'] ?? 'Someone',
                    'state' => $v['state'] ?? 'idle',
                    // Same login in another tab/browser — the UI labels it "(you)".
                    'self'  => ($v['userId'] ?? null) === Auth::id(),
                ];
            }
        }

        return $this->jsonOk('OK', [
            'version'      => (int) ($schedule->syncVersion ?? 0),
            'editedBy'     => $schedule->lastEditedByName,
            'editClientId' => $schedule->lastEditClientId,
            'viewers'      => $viewers,
        ]);
    }
}
