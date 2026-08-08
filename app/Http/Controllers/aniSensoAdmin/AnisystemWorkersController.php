<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AnisystemWorkerGrant;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Yajra\DataTables\Facades\DataTables;

/**
 * AniSystem worker logins.
 *
 * A worker is someone a paying client invited into their farm. They hold no
 * subscription of their own, so this grant is the whole of their access —
 * changing the level here changes what they can do in AniSystem immediately.
 */
class AnisystemWorkersController extends Controller
{
    /**
     * Display the AniSystem workers listing page.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        return view('aniSensoAdmin.anisystemWorkers.index');
    }

    /**
     * Get worker grants for DataTables (each row = one worker login).
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function data(Request $request)
    {
        $now = Carbon::now('Asia/Manila')->format('Y-m-d H:i:s');

        // Whether the boss is currently paying decides if this worker can get
        // in at all, so it is resolved per row rather than left to the admin
        // to cross-check against the Clients page.
        $bossActiveSub = DB::table('anisystem_subscriptions')
            ->selectRaw('COUNT(*)')
            ->whereColumn('anisystem_subscriptions.userId', 'as_worker_grants.bossUserId')
            ->where('anisystem_subscriptions.deleteStatus', 1)
            ->where('anisystem_subscriptions.status', 'active')
            ->where(function ($w) use ($now) {
                $w->whereNull('anisystem_subscriptions.expiresAt')
                    ->orWhere('anisystem_subscriptions.expiresAt', '>', $now);
            });

        $query = AnisystemWorkerGrant::query()
            ->leftJoin('anisystem_users as boss', 'boss.id', '=', 'as_worker_grants.bossUserId')
            ->leftJoin('anisystem_users as worker', 'worker.id', '=', 'as_worker_grants.workerUserId')
            ->leftJoin('as_schedule_workers as roster', 'roster.id', '=', 'as_worker_grants.scheduleWorkerId')
            ->select(
                'as_worker_grants.*',
                DB::raw("TRIM(CONCAT(COALESCE(boss.firstName,''), ' ', COALESCE(boss.lastName,''))) as bossName"),
                'boss.email as bossEmail',
                DB::raw("TRIM(CONCAT(COALESCE(worker.firstName,''), ' ', COALESCE(worker.lastName,''))) as workerName"),
                'worker.email as workerEmail',
                'worker.status as workerAccountStatus',
                'roster.workerName as rosterName'
            )
            ->selectSub($bossActiveSub, 'bossSubActive')
            ->orderBy('as_worker_grants.id', 'desc');

        // Filter: worker / boss name or email (custom param — DataTables reserves 'search')
        if ($request->filled('searchFilter')) {
            $s = $request->searchFilter;
            $query->where(function ($w) use ($s) {
                $w->where('as_worker_grants.invitedEmail', 'like', "%{$s}%")
                    ->orWhere('worker.email', 'like', "%{$s}%")
                    ->orWhere('boss.email', 'like', "%{$s}%")
                    ->orWhere(DB::raw("CONCAT(COALESCE(worker.firstName,''), ' ', COALESCE(worker.lastName,''))"), 'like', "%{$s}%")
                    ->orWhere(DB::raw("CONCAT(COALESCE(boss.firstName,''), ' ', COALESCE(boss.lastName,''))"), 'like', "%{$s}%")
                    ->orWhere('roster.workerName', 'like', "%{$s}%");
            });
        }

        // Filter: grant status. 'deleted' is a soft-delete, not a status value.
        if ($request->filled('status')) {
            if ($request->status === 'deleted') {
                $query->where('as_worker_grants.deleteStatus', '!=', 1);
            } else {
                $query->where('as_worker_grants.deleteStatus', 1)
                    ->where('as_worker_grants.status', $request->status);
            }
        }

        // Filter: schedule access level
        if ($request->filled('access')) {
            $query->where('as_worker_grants.scheduleAccess', $request->access);
        }

        // Filter: one farm owner
        if ($request->filled('bossId')) {
            $query->where('as_worker_grants.bossUserId', (int) $request->bossId);
        }

        return DataTables::of($query)
            ->addIndexColumn()
            ->addColumn('effectiveStatus', function ($grant) {
                return (int) $grant->deleteStatus !== 1 ? 'deleted' : $grant->status;
            })
            ->addColumn('displayName', function ($grant) {
                // Before the invite is accepted there is no account yet, so fall
                // back to the roster name and then the address it was sent to.
                return $grant->workerName ?: ($grant->rosterName ?: $grant->invitedEmail);
            })
            ->addColumn('displayEmail', function ($grant) {
                return $grant->workerEmail ?: $grant->invitedEmail;
            })
            ->addColumn('accessLabel', function ($grant) {
                return $grant->access_label;
            })
            ->addColumn('bossSubscribed', function ($grant) {
                return (int) ($grant->bossSubActive ?? 0) > 0;
            })
            ->addColumn('acceptedFormatted', function ($grant) {
                return $grant->acceptedAt ? $grant->acceptedAt->format('M j, Y') : null;
            })
            ->addColumn('invitedFormatted', function ($grant) {
                return $grant->created_at ? $grant->created_at->format('M j, Y') : '';
            })
            ->make(true);
    }

    /**
     * Farm owners who have issued at least one worker login — populates the
     * boss filter without listing every client.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function bosses()
    {
        try {
            $bosses = DB::table('as_worker_grants')
                ->join('anisystem_users as boss', 'boss.id', '=', 'as_worker_grants.bossUserId')
                ->select(
                    'boss.id',
                    DB::raw("TRIM(CONCAT(COALESCE(boss.firstName,''), ' ', COALESCE(boss.lastName,''))) as name"),
                    'boss.email'
                )
                ->distinct()
                ->orderBy('name')
                ->get();

            return response()->json([
                'success' => true,
                'message' => 'Farm owners loaded',
                'data' => $bosses,
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading AniSystem worker bosses: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error loading farm owners'], 500);
        }
    }

    /**
     * Worker grant details: the login, the farm owner, and the roster entry.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $grant = AnisystemWorkerGrant::with(['boss', 'workerUser', 'scheduleWorker'])->find($id);

            if (! $grant) {
                return response()->json(['success' => false, 'message' => 'Worker not found'], 404);
            }

            $boss = $grant->boss;
            $worker = $grant->workerUser;

            // Schedules the worker can reach — everything the boss owns, since a
            // grant is farm-wide rather than per schedule.
            $schedules = collect();
            if ($boss) {
                $schedules = DB::table('as_cropping_schedules')
                    ->where('anisystemUserId', $boss->id)
                    ->where('deleteStatus', 1)
                    ->orderBy('id', 'desc')
                    ->limit(50)
                    ->get()
                    ->map(function ($s) {
                        return [
                            'id' => $s->id,
                            'name' => $s->title ?: ('Schedule #' . $s->id),
                        ];
                    })
                    ->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Worker details loaded',
                'data' => [
                    'grant' => [
                        'id' => $grant->id,
                        'status' => $grant->status,
                        'effectiveStatus' => $grant->effective_status,
                        'scheduleAccess' => $grant->scheduleAccess,
                        'accessLabel' => $grant->access_label,
                        'communityAccess' => (bool) $grant->communityAccess,
                        'invitedEmail' => $grant->invitedEmail,
                        'invitePending' => $grant->status === AnisystemWorkerGrant::STATUS_PENDING,
                        'acceptedAt' => $grant->acceptedAt ? $grant->acceptedAt->format('M j, Y g:i A') : null,
                        'invitedAt' => $grant->created_at ? $grant->created_at->format('M j, Y g:i A') : null,
                    ],
                    'worker' => $worker ? [
                        'id' => $worker->id,
                        'fullName' => $worker->fullName,
                        'email' => $worker->email,
                        'phone' => $worker->phone,
                        'accountStatus' => $worker->status,
                    ] : null,
                    'boss' => $boss ? [
                        'id' => $boss->id,
                        'fullName' => $boss->fullName,
                        'email' => $boss->email,
                        'phone' => $boss->phone,
                    ] : null,
                    'rosterName' => $grant->scheduleWorker->workerName ?? null,
                    'schedules' => $schedules,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error loading AniSystem worker details: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error loading worker details'], 500);
        }
    }

    /**
     * Change what a worker may do: schedule access level and community access.
     *
     * @param  int  $id
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update($id, Request $request)
    {
        try {
            $grant = AnisystemWorkerGrant::active()->find($id);

            if (! $grant) {
                return response()->json(['success' => false, 'message' => 'Worker not found'], 404);
            }

            $validator = Validator::make($request->all(), [
                'scheduleAccess' => 'required|in:' . implode(',', AnisystemWorkerGrant::ACCESS_LEVELS),
                'communityAccess' => 'required|boolean',
            ], [
                'scheduleAccess.in' => 'Pick one of: no access, view only, or can edit.',
            ]);

            if ($validator->fails()) {
                return response()->json(['success' => false, 'message' => $validator->errors()->first()], 422);
            }

            $grant->scheduleAccess = $request->input('scheduleAccess');
            $grant->communityAccess = $request->boolean('communityAccess');
            $grant->save();

            return response()->json([
                'success' => true,
                'message' => 'Access updated — it applies on their next request.',
                'data' => [
                    'id' => $grant->id,
                    'scheduleAccess' => $grant->scheduleAccess,
                    'accessLabel' => $grant->access_label,
                    'communityAccess' => (bool) $grant->communityAccess,
                ],
            ]);
        } catch (\Exception $e) {
            Log::error('Error updating AniSystem worker access: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error updating access'], 500);
        }
    }

    /**
     * Revoke a worker's access. Reversible — the row is kept so the boss can
     * see the history and an admin can restore it.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function revoke($id)
    {
        try {
            $grant = AnisystemWorkerGrant::active()->find($id);

            if (! $grant) {
                return response()->json(['success' => false, 'message' => 'Worker not found'], 404);
            }

            if ($grant->status === AnisystemWorkerGrant::STATUS_REVOKED) {
                return response()->json(['success' => false, 'message' => 'That access is already revoked.'], 400);
            }

            $grant->status = AnisystemWorkerGrant::STATUS_REVOKED;
            $grant->save();

            return response()->json([
                'success' => true,
                'message' => 'Access revoked — they can no longer open this farm.',
                'data' => ['id' => $grant->id, 'status' => $grant->status],
            ]);
        } catch (\Exception $e) {
            Log::error('Error revoking AniSystem worker access: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error revoking access'], 500);
        }
    }

    /**
     * Restore revoked access. A grant that was never accepted goes back to
     * pending so the worker still has to use their invite.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function restore($id)
    {
        try {
            $grant = AnisystemWorkerGrant::find($id);

            if (! $grant) {
                return response()->json(['success' => false, 'message' => 'Worker not found'], 404);
            }

            $grant->status = $grant->workerUserId
                ? AnisystemWorkerGrant::STATUS_ACTIVE
                : AnisystemWorkerGrant::STATUS_PENDING;
            $grant->deleteStatus = 1;
            $grant->save();

            return response()->json([
                'success' => true,
                'message' => $grant->status === AnisystemWorkerGrant::STATUS_ACTIVE
                    ? 'Access restored.'
                    : 'Restored as a pending invite — they still need to accept it.',
                'data' => ['id' => $grant->id, 'status' => $grant->status],
            ]);
        } catch (\Exception $e) {
            Log::error('Error restoring AniSystem worker access: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error restoring access'], 500);
        }
    }

    /**
     * Delete the grant (soft — deleteStatus 0), removing it from the boss's
     * worker list. The worker's own AniSystem account is left untouched.
     *
     * @param  int  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            $grant = AnisystemWorkerGrant::active()->find($id);

            if (! $grant) {
                return response()->json(['success' => false, 'message' => 'Worker not found'], 404);
            }

            $grant->status = AnisystemWorkerGrant::STATUS_REVOKED;
            $grant->deleteStatus = 0;
            $grant->save();

            return response()->json([
                'success' => true,
                'message' => 'Worker login deleted. Their AniSystem account still exists.',
                'data' => ['id' => $grant->id],
            ]);
        } catch (\Exception $e) {
            Log::error('Error deleting AniSystem worker grant: ' . $e->getMessage());

            return response()->json(['success' => false, 'message' => 'Error deleting worker login'], 500);
        }
    }
}
