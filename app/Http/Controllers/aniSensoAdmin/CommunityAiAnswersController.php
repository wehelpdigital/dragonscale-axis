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

    /**
     * Generate AI answers for the next unanswered questions.
     *
     * A batch of fifteen meant fifteen provider round-trips inside one
     * request: minutes of a spinner, a page that often timed out first, and
     * an answer you only saw by reloading. The page now asks for one question
     * at a time and draws each answer as it lands, so `limit` is what the
     * caller asks for rather than what this decides.
     */
    public function generate(Request $request)
    {
        if (! $this->ai->isUsable()) {
            return response()->json(['success' => false, 'message' => 'The AniSenso AI is not configured yet.'], 422);
        }

        $limit = (int) $request->input('limit', self::GENERATE_LIMIT);
        $limit = max(1, min(self::GENERATE_LIMIT, $limit));

        $posts = $this->unansweredPostsQuery()
            ->with('group', 'author')
            ->orderByDesc('id')
            ->limit($limit)
            ->get();

        if ($posts->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No new questions to answer.',
                'count' => 0,
                'drafts' => [],
                'remaining' => 0,
            ]);
        }

        $created = 0;
        $failed = 0;
        $made = [];
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

            $draft = CommunityAiAnswerDraft::create([
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
            $made[] = $this->draftPayload($draft, $post);
        }

        $msg = $created . ' answer' . ($created === 1 ? '' : 's') . ' generated for review.';
        if ($failed) {
            $msg .= ' ' . $failed . ' failed and were skipped.';
        }

        return response()->json([
            'success' => true,
            'message' => $msg,
            'count' => $created,
            'drafts' => $made,
            // Counted after the run, so the page can keep asking until the
            // queue is genuinely empty rather than guessing.
            'remaining' => $this->unansweredPostsQuery()->count(),
        ]);
    }

    /** One draft, in the shape the review page draws a card from. */
    private function draftPayload(CommunityAiAnswerDraft $draft, ?CommunityGroupPost $post = null): array
    {
        $post = $post ?: $draft->post;

        return [
            'id' => $draft->id,
            'questionTitle' => $draft->questionTitle ?: 'Untitled question',
            'questionBody' => (string) $draft->questionBody,
            'answerBody' => (string) $draft->answerBody,
            'model' => $draft->model,
            'groupName' => optional(optional($post)->group)->name ?: 'a group',
            'askedBy' => optional(optional($post)->author)->full_name ?: null,
            'postUrl' => $this->communityUrl($post),
        ];
    }

    /** Where this question lives in the farmer's own app. */
    private function communityUrl(?CommunityGroupPost $post): ?string
    {
        if (! $post) {
            return null;
        }

        return rtrim((string) config('anisystem.url'), '/')
            . '/app/community/groups/' . $post->groupId . '#post-' . $post->id;
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

        return response()->json([
            'success' => true,
            'message' => 'Posted to the community.',
            // The answer went somewhere; say where, so it can be read there.
            'url' => $this->communityUrl(CommunityGroupPost::find($draft->postId)),
        ]);
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

        $this->tellTheAsker($post, $reply, $persona);
    }

    /**
     * Ring the asker's bell, the way the farmer app does when a member
     * answers.
     *
     * Without this the answer was live but silent: it sat in a topic nobody
     * was told had changed, which from the asker's side is indistinguishable
     * from never having been answered at all.
     */
    private function tellTheAsker(CommunityGroupPost $post, CommunityGroupReply $reply, AnisystemUser $persona): void
    {
        if ((int) $post->userId === (int) $persona->id) {
            return;
        }

        try {
            \App\Models\AnisystemNotification::create([
                'userId' => (int) $post->userId,
                'type' => 'reply',
                'title' => (trim($persona->firstName . ' ' . $persona->lastName) ?: 'The AI Technician') . ' answered your question',
                'body' => \Illuminate\Support\Str::limit(trim(strip_tags((string) $reply->body)), 90),
                // The topic, not the room: being dropped at the top of a busy
                // group is the same as not being told which question it was.
                'url' => $this->communityUrl($post),
                'actorUserId' => (int) $persona->id,
                'deleteStatus' => 1,
            ]);
        } catch (\Throwable $e) {
            // A bell that fails is not a reason to un-post an answer.
            Log::warning('Community AI answer notification failed', [
                'postId' => $post->id, 'error' => $e->getMessage(),
            ]);
        }
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
