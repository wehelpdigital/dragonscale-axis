<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\CommunityGroup;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Models\CommunityReport;
use App\Models\CommunityWallComment;
use App\Models\CommunityWallPost;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

/**
 * AniSenso admin — what members have reported.
 *
 * The app takes reports and changes nothing; this is where somebody decides.
 * Three answers: it has been looked at, it was nothing, or the content goes.
 * The third is the only one that touches what was posted, and it is the one
 * that also closes every other report about the same thing — a queue that
 * asks five people to judge one post five times is a queue nobody finishes.
 */
class AniSensoReportsController extends Controller
{
    /** The queue, newest first, with what each report points at. */
    public function index(Request $request)
    {
        $status = $request->query('status', 'open');
        if (! in_array($status, ['open', 'reviewed', 'dismissed', 'actioned', 'all'], true)) {
            $status = 'open';
        }
        $search = trim((string) $request->query('q'));

        $reports = CommunityReport::active()
            ->with(['reporter', 'reportedUser'])
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when($search !== '', function ($q) use ($search) {
                $q->where(function ($w) use ($search) {
                    $w->where('snapshot', 'like', "%{$search}%")
                        ->orWhere('details', 'like', "%{$search}%")
                        ->orWhere('reason', 'like', "%{$search}%");
                });
            })
            ->orderByDesc('id')
            ->paginate(25)
            ->withQueryString();

        // What each one is about, as it stands now — the snapshot is what it
        // said when it was reported, and the two differing is itself useful.
        foreach ($reports as $report) {
            $report->liveText = $this->liveText($report);
        }

        $counts = CommunityReport::active()
            ->selectRaw('status, COUNT(*) as n')
            ->groupBy('status')
            ->pluck('n', 'status');

        return view('aniSensoAdmin.community.reports', [
            'reports' => $reports,
            'status' => $status,
            'search' => $search,
            'counts' => $counts,
        ]);
    }

    /** Looked at, and nothing more needed. */
    public function review(Request $request, int $id)
    {
        return $this->settle($id, 'reviewed', $request->input('note'), 'Marked as reviewed.');
    }

    /** Nothing wrong with it. */
    public function dismiss(Request $request, int $id)
    {
        return $this->settle($id, 'dismissed', $request->input('note'), 'Report dismissed.');
    }

    /**
     * The content goes.
     *
     * Soft-deleted the way the rest of this admin removes things
     * (deleteStatus = 0), and every other open report about the same thing is
     * closed with it.
     */
    public function remove(Request $request, int $id)
    {
        $report = CommunityReport::active()->findOrFail($id);
        $removed = $this->removeTarget($report->targetType, (int) $report->targetId);

        if (! $removed) {
            return response()->json([
                'success' => false,
                'message' => 'That content is already gone — mark the report reviewed instead.',
            ], 422);
        }

        CommunityReport::active()
            ->where('targetType', $report->targetType)
            ->where('targetId', $report->targetId)
            ->whereIn('status', ['open', 'reviewed'])
            ->update([
                'status' => 'actioned',
                'reviewedByUserId' => (int) Auth::id(),
                'reviewedAt' => Carbon::now('Asia/Manila'),
            ]);

        return response()->json(['success' => true, 'message' => 'Content removed and the reports closed.']);
    }

    private function settle(int $id, string $status, $note, string $message)
    {
        $report = CommunityReport::active()->findOrFail($id);
        $report->update([
            'status' => $status,
            'note' => filled($note) ? Str::limit((string) $note, 500, '') : $report->note,
            'reviewedByUserId' => (int) Auth::id(),
            'reviewedAt' => Carbon::now('Asia/Manila'),
        ]);

        return response()->json(['success' => true, 'message' => $message]);
    }

    /** What the reported thing says now, or null if it has gone. */
    private function liveText(CommunityReport $report): ?string
    {
        $row = $this->targetRow($report->targetType, (int) $report->targetId);
        if (! $row) {
            return null;
        }
        $text = match ($report->targetType) {
            'topic' => trim(($row->title ?? '') . ' — ' . ($row->body ?? '')),
            'group' => trim(($row->name ?? '') . ' — ' . ($row->description ?? '')),
            default => (string) ($row->body ?? ''),
        };

        return Str::limit(trim(strip_tags($text)), 400);
    }

    private function targetRow(string $type, int $id)
    {
        return match ($type) {
            'post', 'story' => CommunityWallPost::where('deleteStatus', 1)->find($id),
            'comment' => CommunityWallComment::where('deleteStatus', 1)->find($id),
            'topic' => CommunityGroupPost::where('deleteStatus', 1)->find($id),
            'reply' => CommunityGroupReply::where('deleteStatus', 1)->find($id),
            'group' => CommunityGroup::where('deleteStatus', 1)->find($id),
            default => null,
        };
    }

    private function removeTarget(string $type, int $id): bool
    {
        $row = $this->targetRow($type, $id);
        if (! $row) {
            return false;
        }

        // A topic takes its replies with it, the way this admin's own delete
        // already does.
        if ($type === 'topic') {
            CommunityGroupReply::where('postId', $row->id)->update(['deleteStatus' => 0]);
        }

        $row->update(['deleteStatus' => 0]);

        return true;
    }
}
