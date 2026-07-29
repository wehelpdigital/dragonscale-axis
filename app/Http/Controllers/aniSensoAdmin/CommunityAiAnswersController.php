<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AnisystemUser;
use App\Models\CommunityAiAnswerDraft;
use App\Models\CommunityGroupPost;
use App\Models\CommunityGroupReply;
use App\Services\AniSenso\CommunityAiAnswerService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * AniSenso admin — AI community answers.
 *
 * One click generates AI Technician answers for community group questions that
 * have not been answered yet. The admin reviews/edits each draft, then posts it
 * (or all) as a community reply authored by the AI Technician persona.
 */
class CommunityAiAnswersController extends Controller
{
    /** Cap per generate run so a click can't fan out into hundreds of API calls. */
    private const GENERATE_LIMIT = 15;

    public function __construct(private CommunityAiAnswerService $ai)
    {
    }

    /** Review page: pending drafts + a count of still-unanswered questions. */
    public function index(Request $request)
    {
        $drafts = CommunityAiAnswerDraft::active()
            ->where('status', 'pending')
            ->with('post.group', 'post.author')
            ->orderByDesc('id')
            ->get();

        $unansweredCount = $this->unansweredPostsQuery()->count();

        return view('aniSensoAdmin.community.ai-answers', [
            'drafts' => $drafts,
            'unansweredCount' => $unansweredCount,
            'aiUsable' => $this->ai->isUsable(),
            'assistantName' => $this->ai->settings()->assistantName ?: 'AI Technician',
            'recentPosted' => CommunityAiAnswerDraft::active()->where('status', 'posted')
                ->orderByDesc('postedAt')->limit(10)->with('post.group')->get(),
        ]);
    }

    /** Generate AI answers for the next batch of unanswered questions. */
    public function generate(Request $request)
    {
        if (! $this->ai->isUsable()) {
            return response()->json(['success' => false, 'message' => 'The AniSenso AI is not configured yet.'], 422);
        }

        $posts = $this->unansweredPostsQuery()
            ->with('group')
            ->orderByDesc('id')
            ->limit(self::GENERATE_LIMIT)
            ->get();

        if ($posts->isEmpty()) {
            return response()->json(['success' => true, 'message' => 'No new questions to answer.', 'count' => 0]);
        }

        $created = 0;
        $failed = 0;
        foreach ($posts as $post) {
            $question = trim(($post->title ? $post->title . "\n\n" : '') . (string) $post->body);
            if ($question === '') {
                continue;
            }
            $context = 'This is a question posted by a farmer in the "' . ($post->group->name ?? 'community')
                . '" community group. Reply helpfully and concisely as the AI Technician. Do not mention that you are an AI unless asked.';
            try {
                $answer = $this->ai->answer($question, $context);
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Community AI answer failed', ['postId' => $post->id, 'error' => $e->getMessage()]);
                continue;
            }

            CommunityAiAnswerDraft::create([
                'postId' => $post->id,
                'groupId' => $post->groupId,
                'questionTitle' => $post->title,
                'questionBody' => $post->body,
                'answerBody' => $answer,
                'status' => 'pending',
                'model' => $this->ai->settings()->effectiveModel(),
                'generatedByUserId' => auth()->id(),
                'deleteStatus' => 1,
            ]);
            $created++;
        }

        $msg = $created . ' answer' . ($created === 1 ? '' : 's') . ' generated for review.';
        if ($failed) {
            $msg .= ' ' . $failed . ' failed and were skipped.';
        }
        return response()->json(['success' => true, 'message' => $msg, 'count' => $created]);
    }

    /** Save an admin edit to a draft's answer. */
    public function update(Request $request, $id)
    {
        $draft = CommunityAiAnswerDraft::active()->where('status', 'pending')->find($id);
        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'Draft not found.'], 404);
        }
        $body = trim((string) $request->input('answerBody', ''));
        if ($body === '') {
            return response()->json(['success' => false, 'message' => 'The answer cannot be empty.'], 422);
        }
        $draft->update(['answerBody' => $body]);
        return response()->json(['success' => true, 'message' => 'Answer saved.']);
    }

    /** Post one draft as a community reply authored by the AI persona. */
    public function post(Request $request, $id)
    {
        $draft = CommunityAiAnswerDraft::active()->where('status', 'pending')->find($id);
        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'Draft not found or already posted.'], 404);
        }
        // Optional inline edit passed with the post action.
        $body = trim((string) $request->input('answerBody', ''));
        if ($body !== '') {
            $draft->answerBody = $body;
        }

        try {
            $this->postDraft($draft);
        } catch (\Throwable $e) {
            return response()->json(['success' => false, 'message' => 'Could not post: ' . $e->getMessage()], 500);
        }

        return response()->json(['success' => true, 'message' => 'Posted to the community.']);
    }

    /** Post every pending draft. */
    public function postAll(Request $request)
    {
        $drafts = CommunityAiAnswerDraft::active()->where('status', 'pending')->get();
        $posted = 0;
        $failed = 0;
        foreach ($drafts as $draft) {
            try {
                $this->postDraft($draft);
                $posted++;
            } catch (\Throwable $e) {
                $failed++;
                Log::warning('Community AI post-all failed', ['draftId' => $draft->id, 'error' => $e->getMessage()]);
            }
        }
        $msg = $posted . ' answer' . ($posted === 1 ? '' : 's') . ' posted.';
        if ($failed) {
            $msg .= ' ' . $failed . ' failed.';
        }
        return response()->json(['success' => true, 'message' => $msg, 'posted' => $posted]);
    }

    /** Discard a draft without posting (soft-delete). */
    public function dismiss(Request $request, $id)
    {
        $draft = CommunityAiAnswerDraft::active()->where('status', 'pending')->find($id);
        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'Draft not found.'], 404);
        }
        $draft->update(['deleteStatus' => 0]);
        return response()->json(['success' => true, 'message' => 'Draft dismissed.']);
    }

    // ------------------------------------------------------------------

    /** Create the reply row (AI persona author) and mark the draft posted. */
    private function postDraft(CommunityAiAnswerDraft $draft): void
    {
        // Skip if the underlying post is gone.
        $post = CommunityGroupPost::active()->find($draft->postId);
        if (! $post) {
            $draft->update(['status' => 'dismissed', 'deleteStatus' => 0]);
            throw new \RuntimeException('The question was removed.');
        }
        $persona = $this->ai->personaUser();

        $reply = CommunityGroupReply::create([
            'postId' => $draft->postId,
            'userId' => $persona->id,
            'body' => $draft->answerBody,
            'deleteStatus' => 1,
        ]);

        $draft->update([
            'status' => 'posted',
            'postedReplyId' => $reply->id,
            'postedAt' => now(),
        ]);
    }

    /**
     * Group posts that have not been AI-answered yet: no persona reply and no
     * active (pending/posted) draft.
     */
    private function unansweredPostsQuery()
    {
        $personaId = optional(AnisystemUser::where('email', CommunityAiAnswerService::PERSONA_EMAIL)->first())->id;

        $draftedPostIds = CommunityAiAnswerDraft::active()
            ->whereIn('status', ['pending', 'posted'])
            ->pluck('postId')->all();

        $repliedPostIds = $personaId
            ? CommunityGroupReply::where('deleteStatus', 1)->where('userId', $personaId)->pluck('postId')->all()
            : [];

        $excluded = array_values(array_unique(array_merge($draftedPostIds, $repliedPostIds)));

        return CommunityGroupPost::active()->when($excluded, fn ($q) => $q->whereNotIn('id', $excluded));
    }
}
