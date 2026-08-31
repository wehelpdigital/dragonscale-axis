<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AnisystemUser;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * Who a client farms alongside.
 *
 * Two different things, and the difference matters: a co-farmer is a
 * handshake — one side asked, the other agreed, and it can be pending — while
 * a follow is one-sided and needs nobody's permission. The client app shows
 * them on separate shelves for that reason, and this reads them the same way.
 *
 * There has never been a screen on this side that showed either. A member's
 * page counted their connections; nothing listed the pending ones, and
 * nothing could sever a handshake that should not have been made.
 */
class AniSensoCoFarmerController extends Controller
{
    public function index(Request $request)
    {
        $search = trim((string) $request->query('q'));
        $status = (string) $request->query('status', '');

        $rows = DB::table('as_community_connections as c')
            ->leftJoin('anisystem_users as a', 'a.id', '=', 'c.userId')
            ->leftJoin('anisystem_users as b', 'b.id', '=', 'c.friendUserId')
            ->where('c.deleteStatus', 1)
            ->when($status !== '', fn ($q) => $q->where('c.status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    foreach (['a.firstName', 'a.lastName', 'a.email', 'b.firstName', 'b.lastName', 'b.email'] as $col) {
                        $w->orWhere($col, 'like', "%{$search}%");
                    }
                });
            })
            ->orderByDesc('c.id')
            ->limit(400)
            ->get([
                'c.id', 'c.status', 'c.respondedAt', 'c.created_at',
                'c.userId', 'c.friendUserId',
                'a.firstName as aFirst', 'a.lastName as aLast', 'a.email as aEmail',
                'b.firstName as bFirst', 'b.lastName as bLast', 'b.email as bEmail',
            ]);

        // A follow is one-sided, so it is listed as "who follows whom" rather
        // than as a pair.
        $follows = DB::table('as_community_follows as f')
            ->leftJoin('anisystem_users as a', 'a.id', '=', 'f.followerUserId')
            ->leftJoin('anisystem_users as b', 'b.id', '=', 'f.followedUserId')
            ->where('f.deleteStatus', 1)
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    foreach (['a.firstName', 'a.lastName', 'a.email', 'b.firstName', 'b.lastName', 'b.email'] as $col) {
                        $w->orWhere($col, 'like', "%{$search}%");
                    }
                });
            })
            ->orderByDesc('f.id')
            ->limit(400)
            ->get([
                'f.id', 'f.created_at', 'f.followerUserId', 'f.followedUserId',
                'a.firstName as aFirst', 'a.lastName as aLast',
                'b.firstName as bFirst', 'b.lastName as bLast',
            ]);

        $counts = [
            'accepted' => DB::table('as_community_connections')->where('deleteStatus', 1)->where('status', 'accepted')->count(),
            'pending' => DB::table('as_community_connections')->where('deleteStatus', 1)->where('status', 'pending')->count(),
            'follows' => DB::table('as_community_follows')->where('deleteStatus', 1)->count(),
        ];

        return view('aniSensoAdmin.community.cofarmers', compact('rows', 'follows', 'search', 'status', 'counts'));
    }

    /** Sever a handshake. Both sides lose it, which is what severing means. */
    public function destroy(Request $request)
    {
        $hit = DB::table('as_community_connections')
            ->where('id', (int) $request->query('id'))
            ->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return response()->json($hit
            ? ['success' => true, 'message' => 'They are no longer co-farmers.']
            : ['success' => false, 'message' => 'That link is already gone.'], $hit ? 200 : 404);
    }

    public function unfollow(Request $request)
    {
        $hit = DB::table('as_community_follows')
            ->where('id', (int) $request->query('id'))
            ->where('deleteStatus', 1)
            ->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return response()->json($hit
            ? ['success' => true, 'message' => 'Follow removed.']
            : ['success' => false, 'message' => 'That follow is already gone.'], $hit ? 200 : 404);
    }

    /** One member's people, for the member page to show in full. */
    public static function peopleAround(int $userId): array
    {
        $name = fn ($f, $l) => trim(($f ?? '') . ' ' . ($l ?? '')) ?: 'Someone';

        $connections = DB::table('as_community_connections as c')
            ->leftJoin('anisystem_users as a', 'a.id', '=', 'c.userId')
            ->leftJoin('anisystem_users as b', 'b.id', '=', 'c.friendUserId')
            ->where('c.deleteStatus', 1)
            ->where(fn ($q) => $q->where('c.userId', $userId)->orWhere('c.friendUserId', $userId))
            ->orderByDesc('c.id')
            ->get(['c.id', 'c.status', 'c.userId', 'c.friendUserId',
                'a.firstName as aFirst', 'a.lastName as aLast', 'b.firstName as bFirst', 'b.lastName as bLast'])
            ->map(fn ($r) => [
                'id' => (int) $r->id,
                'status' => (string) $r->status,
                // Whoever it is that is not the member whose page this is.
                'who' => (int) $r->userId === $userId
                    ? $name($r->bFirst, $r->bLast)
                    : $name($r->aFirst, $r->aLast),
                'whoId' => (int) $r->userId === $userId ? (int) $r->friendUserId : (int) $r->userId,
                // Who asked, which is the difference between a request they
                // have not answered and one they are waiting on.
                'theyAsked' => (int) $r->userId !== $userId,
            ])->all();

        return [
            'connections' => $connections,
            'following' => DB::table('as_community_follows as f')
                ->leftJoin('anisystem_users as b', 'b.id', '=', 'f.followedUserId')
                ->where('f.deleteStatus', 1)->where('f.followerUserId', $userId)
                ->get(['f.id', 'b.firstName', 'b.lastName'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'who' => $name($r->firstName, $r->lastName)])->all(),
            'followers' => DB::table('as_community_follows as f')
                ->leftJoin('anisystem_users as a', 'a.id', '=', 'f.followerUserId')
                ->where('f.deleteStatus', 1)->where('f.followedUserId', $userId)
                ->get(['f.id', 'a.firstName', 'a.lastName'])
                ->map(fn ($r) => ['id' => (int) $r->id, 'who' => $name($r->firstName, $r->lastName)])->all(),
        ];
    }
}
