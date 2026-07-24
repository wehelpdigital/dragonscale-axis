<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AnisystemUser;
use App\Models\AsCroppingSchedule;
use App\Models\CommunityComment;
use App\Models\CommunityConnection;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupMember;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Models\CommunityRating;
use App\Models\CommunityWallComment;
use App\Models\CommunityWallPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * AniSenso admin — Community moderation. Staff review and moderate the
 * give-to-get space AniSystem members use: shared plans (with their comments
 * and ratings), groups, member walls, and broadcast announcements.
 *
 * All data lives in the shared `as_community_*` / `anisystem_*` tables.
 */
class AniSensoCommunityController extends Controller
{
    // ================================================================
    // SHARED PLANS
    // ================================================================

    /** Published plans with owner, crop, rating + comment counts. */
    public function plans(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $ratings = DB::table('as_community_ratings')->where('deleteStatus', 1)
            ->select('croppingScheduleId', DB::raw('AVG(rating) as avgRating'), DB::raw('COUNT(*) as ratingCount'))
            ->groupBy('croppingScheduleId');
        $comments = DB::table('as_community_comments')->where('deleteStatus', 1)
            ->select('croppingScheduleId', DB::raw('COUNT(*) as commentCount'))
            ->groupBy('croppingScheduleId');

        $plans = AsCroppingSchedule::query()
            ->where('as_cropping_schedules.deleteStatus', 1)
            ->where('as_cropping_schedules.isPublic', 1)
            ->leftJoinSub($ratings, 'r', 'r.croppingScheduleId', '=', 'as_cropping_schedules.id')
            ->leftJoinSub($comments, 'c', 'c.croppingScheduleId', '=', 'as_cropping_schedules.id')
            ->with('anisystemUser')
            ->when($search !== '', function ($w) use ($search) {
                $w->where(function ($q) use ($search) {
                    $q->where('title', 'like', "%{$search}%")
                        ->orWhere('cropType', 'like', "%{$search}%")
                        ->orWhere('publicRegion', 'like', "%{$search}%");
                });
            })
            ->select('as_cropping_schedules.*', 'r.avgRating', 'r.ratingCount', 'c.commentCount')
            ->orderByDesc('publishedAt')
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('aniSensoAdmin.community.plans', compact('plans', 'search'));
    }

    /** One shared plan, read-only, with its thread and ratings. */
    public function planShow($id)
    {
        $plan = AsCroppingSchedule::where('deleteStatus', 1)->where('id', $id)->with('anisystemUser')->firstOrFail();
        $plan->load(['lots', 'workers']);

        $comments = CommunityComment::active()
            ->where('croppingScheduleId', $plan->id)
            ->with('author')
            ->orderBy('id')
            ->get();
        $byParent = $comments->whereNotNull('parentId')->groupBy('parentId');
        $thread = $comments->whereNull('parentId')->values()->map(function ($c) use ($byParent) {
            $c->setRelation('replies', $byParent->get($c->id, collect())->values());
            return $c;
        });

        $ratings = CommunityRating::active()->where('croppingScheduleId', $plan->id)->with('author')->orderByDesc('id')->get();
        $avg = $ratings->count() ? round($ratings->avg('rating'), 1) : null;

        return view('aniSensoAdmin.community.plan-show', compact('plan', 'thread', 'ratings', 'avg'));
    }

    public function unpublishPlan($id)
    {
        $plan = AsCroppingSchedule::where('deleteStatus', 1)->where('id', $id)->firstOrFail();
        $plan->update(['isPublic' => 0]);

        return response()->json(['success' => true, 'message' => 'Plan removed from the Community.']);
    }

    public function deleteComment($id)
    {
        $comment = CommunityComment::active()->where('id', $id)->firstOrFail();
        DB::transaction(function () use ($comment) {
            CommunityComment::where('parentId', $comment->id)->update(['deleteStatus' => 0]);
            $comment->update(['deleteStatus' => 0]);
        });

        return response()->json(['success' => true, 'message' => 'Comment removed.']);
    }

    // ================================================================
    // GROUPS
    // ================================================================

    public function groups(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $groups = CommunityGroup::active()
            ->with('creator')
            ->withCount([
                'members as member_count',
                'posts as post_count',
            ])
            ->when($search !== '', fn ($w) => $w->where('name', 'like', "%{$search}%"))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('aniSensoAdmin.community.groups', compact('groups', 'search'));
    }

    public function groupShow($id)
    {
        $group = CommunityGroup::active()->where('id', $id)->with('creator')->firstOrFail();
        $memberCount = $group->members()->count();

        $posts = CommunityGroupPost::active()
            ->where('groupId', $group->id)
            ->with(['author', 'replies.author'])
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('aniSensoAdmin.community.group-show', compact('group', 'posts', 'memberCount'));
    }

    public function deleteGroup($id)
    {
        $group = CommunityGroup::active()->where('id', $id)->firstOrFail();
        DB::transaction(function () use ($group) {
            $postIds = CommunityGroupPost::where('groupId', $group->id)->pluck('id');
            CommunityGroupReply::whereIn('postId', $postIds)->update(['deleteStatus' => 0]);
            CommunityGroupPost::where('groupId', $group->id)->update(['deleteStatus' => 0]);
            CommunityGroupMember::where('groupId', $group->id)->update(['deleteStatus' => 0]);
            $group->update(['deleteStatus' => 0]);
        });

        return response()->json(['success' => true, 'message' => 'Group removed.']);
    }

    public function deletePost($id)
    {
        $post = CommunityGroupPost::active()->where('id', $id)->firstOrFail();
        DB::transaction(function () use ($post) {
            CommunityGroupReply::where('postId', $post->id)->update(['deleteStatus' => 0]);
            $post->update(['deleteStatus' => 0]);
        });

        return response()->json(['success' => true, 'message' => 'Post removed.']);
    }

    public function deleteReply($id)
    {
        $reply = CommunityGroupReply::active()->where('id', $id)->firstOrFail();
        $reply->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Reply removed.']);
    }

    // ================================================================
    // MEMBERS & WALLS
    // ================================================================

    public function members(Request $request)
    {
        $search = trim((string) $request->query('q'));

        $members = AnisystemUser::query()
            ->where('anisystem_users.deleteStatus', 1)
            ->when($search !== '', function ($w) use ($search) {
                $w->where(function ($q) use ($search) {
                    $q->where('firstName', 'like', "%{$search}%")
                        ->orWhere('lastName', 'like', "%{$search}%")
                        ->orWhere('city', 'like', "%{$search}%")
                        ->orWhere('province', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->selectSub(
                DB::table('as_cropping_schedules')->selectRaw('COUNT(*)')
                    ->whereColumn('anisystemUserId', 'anisystem_users.id')
                    ->where('deleteStatus', 1)->where('isPublic', 1),
                'sharedPlanCount'
            )
            ->selectSub(
                DB::table('as_community_connections')->selectRaw('COUNT(*)')
                    ->where('deleteStatus', 1)->where('status', 'accepted')
                    ->where(fn ($q) => $q->whereColumn('userId', 'anisystem_users.id')->orWhereColumn('friendUserId', 'anisystem_users.id')),
                'connectionCount'
            )
            ->select('anisystem_users.*')
            ->orderBy('firstName')->orderBy('lastName')
            ->paginate(20)
            ->withQueryString();

        return view('aniSensoAdmin.community.members', compact('members', 'search'));
    }

    public function memberShow($id)
    {
        $member = AnisystemUser::where('deleteStatus', 1)->where('id', $id)->firstOrFail();

        $plans = AsCroppingSchedule::where('deleteStatus', 1)
            ->where('anisystemUserId', $member->id)->where('isPublic', 1)
            ->orderByDesc('publishedAt')->get();

        $groups = CommunityGroupMember::active()->where('userId', $member->id)
            ->get()->pluck('groupId');
        $groupList = CommunityGroup::active()->whereIn('id', $groups)->get();

        $connectionIds = CommunityConnection::connectedIds($member->id);
        $connections = AnisystemUser::whereIn('id', $connectionIds)->where('deleteStatus', 1)->get();

        $wallPosts = CommunityWallPost::active()
            ->where('wallUserId', $member->id)
            ->with(['author', 'comments.author'])
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();

        return view('aniSensoAdmin.community.member-show', compact('member', 'plans', 'groupList', 'connections', 'wallPosts'));
    }

    public function deleteWallPost($id)
    {
        $post = CommunityWallPost::active()->where('id', $id)->firstOrFail();
        DB::transaction(function () use ($post) {
            CommunityWallComment::where('wallPostId', $post->id)->update(['deleteStatus' => 0]);
            $post->update(['deleteStatus' => 0]);
        });

        return response()->json(['success' => true, 'message' => 'Wall post removed.']);
    }

    public function deleteWallComment($id)
    {
        $comment = CommunityWallComment::active()->where('id', $id)->firstOrFail();
        $comment->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Comment removed.']);
    }

    // ================================================================
    // ANNOUNCEMENTS (broadcast into members' notification bells)
    // ================================================================

    public function announcements()
    {
        $memberCount = AnisystemUser::where('deleteStatus', 1)->count();

        // Recent broadcasts, grouped so one announcement = one row.
        $recent = DB::table('anisystem_notifications')
            ->where('deleteStatus', 1)
            ->where('type', 'system')
            ->select('title', 'body', 'url', DB::raw('MAX(created_at) as sentAt'), DB::raw('COUNT(*) as recipients'))
            ->groupBy('title', 'body', 'url')
            ->orderByDesc('sentAt')
            ->limit(20)
            ->get();

        return view('aniSensoAdmin.community.announcements', compact('memberCount', 'recent'));
    }

    public function broadcast(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:191',
            'body' => 'nullable|string|max:500',
            'url' => 'nullable|url|max:500',
        ]);

        $now = now();
        $memberIds = AnisystemUser::where('deleteStatus', 1)->pluck('id');

        $memberIds->chunk(500)->each(function ($chunk) use ($data, $now) {
            $rows = $chunk->map(fn ($uid) => [
                'userId' => $uid,
                'type' => 'system',
                'title' => $data['title'],
                'body' => $data['body'] ?? null,
                'url' => $data['url'] ?? null,
                'actorUserId' => null,
                'croppingScheduleId' => null,
                'readAt' => null,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            DB::table('anisystem_notifications')->insert($rows);
        });

        return redirect()->route('anisenso-community.announcements')
            ->with('success', 'Announcement sent to ' . $memberIds->count() . ' members.');
    }
}
