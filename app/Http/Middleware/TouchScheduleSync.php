<?php

namespace App\Http\Middleware;

use App\Models\AsCroppingSchedule;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Live-sync change tracking for the Schedule Manager.
 *
 * Every mutating schedule-manager endpoint already carries the schedule id in
 * the query string (`?scheduleId=`, or `?id=` for the schedule-update route),
 * so instead of touching ~50 controller actions we bump a per-schedule
 * `syncVersion` counter here, after any successful POST/PUT/PATCH/DELETE.
 * Other browser tabs on the same setup page poll that counter (sync-status
 * endpoint) and refresh when it moves.
 */
class TouchScheduleSync
{
    public function handle(Request $request, Closure $next)
    {
        $response = $next($request);

        if ($this->isScheduleMutation($request, $response)) {
            $scheduleId = $request->query('scheduleId');
            // The schedule-update endpoint passes the schedule id as `id`;
            // on every other route `id` is a child-entity id, so only map it
            // for that specific path.
            if (!is_numeric($scheduleId) && $request->is('anisenso-schedule-manager-update')) {
                $scheduleId = $request->query('id');
            }
            if (is_numeric($scheduleId)) {
                try {
                    AsCroppingSchedule::where('id', (int) $scheduleId)->update([
                        'syncVersion'      => DB::raw('syncVersion + 1'),
                        'lastEditClientId' => substr((string) $request->header('X-Sync-Client', ''), 0, 40) ?: null,
                        'lastEditedByName' => optional(Auth::user())->name,
                    ]);
                } catch (\Throwable $e) {
                    // Non-fatal: live sync degrades, the mutation itself succeeded.
                }
            }
        }

        return $response;
    }

    private function isScheduleMutation(Request $request, $response): bool
    {
        return in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)
            && $request->is('anisenso-schedule-manager-*')
            && method_exists($response, 'getStatusCode')
            && $response->getStatusCode() < 400;
    }
}
