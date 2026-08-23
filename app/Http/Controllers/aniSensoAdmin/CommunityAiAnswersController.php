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
            'postedCount' => CommunityAiAnswerDraft::active()->where('status', 'posted')->count(),
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
            try {
                $answer = $this->ai->answer($question, $this->groupContext($post));
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

    /**
     * Answers that are already live in the community.
     *
     * Their own tab rather than a list of links: an answer that has been
     * posted is the one most likely to need a second look, and until now the
     * only way back to it was to open the community and find the topic.
     */
    public function posted(Request $request)
    {
        $search = trim((string) $request->query('searchFilter'));
        $start = max(0, (int) $request->query('start', 0));
        $length = (int) $request->query('length', 10);
        $length = $length < 1 ? 10 : min(50, $length);

        $query = CommunityAiAnswerDraft::active()
            ->where('status', 'posted')
            ->with('post.group', 'post.author')
            ->when($search !== '', fn ($q) => $q->where(fn ($w) => $w
                ->where('questionTitle', 'like', "%{$search}%")
                ->orWhere('answerBody', 'like', "%{$search}%")))
            ->orderByDesc('postedAt');

        $total = (clone $query)->count();
        $rows = $query->skip($start)->take($length)->get();

        // The live reply is the truth about what the community can read: a
        // draft's answerBody is only what was sent. They part company the
        // moment a member's own edit or a takedown happens elsewhere.
        $replies = CommunityGroupReply::whereIn('id', $rows->pluck('postedReplyId')->filter()->all())
            ->get(['id', 'body', 'deleteStatus', 'isDeleted'])
            ->keyBy('id');

        return response()->json([
            'success' => true,
            'total' => $total,
            'start' => $start,
            'rows' => $rows->map(function ($draft) use ($replies) {
                $reply = $draft->postedReplyId ? $replies->get($draft->postedReplyId) : null;
                $live = $reply && (int) $reply->deleteStatus === 1 && ! $reply->isDeleted;

                return array_merge($this->draftPayload($draft), [
                    'postedAt' => $draft->postedAt?->format('M j, Y g:i A'),
                    'replyId' => $draft->postedReplyId,
                    'live' => $live,
                    // Show what is actually on the page, not what was sent.
                    'answerBody' => $live ? (string) $reply->body : (string) $draft->answerBody,
                ]);
            })->values(),
        ]);
    }

    /** Re-word an answer that is already live, in place. */
    public function updatePosted(Request $request)
    {
        $draft = CommunityAiAnswerDraft::active()->where('status', 'posted')
            ->find($this->draftId($request));
        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'Posted answer not found.'], 404);
        }

        $body = trim((string) $request->input('answerBody', ''));
        if ($body === '') {
            return response()->json(['success' => false, 'message' => 'The answer cannot be empty.'], 422);
        }

        $reply = $draft->postedReplyId ? CommunityGroupReply::find($draft->postedReplyId) : null;
        if (! $reply || (int) $reply->deleteStatus !== 1) {
            return response()->json([
                'success' => false,
                'message' => 'That answer is no longer in the community, so there is nothing to edit.',
            ], 404);
        }

        $reply->update(['body' => $body]);
        $draft->update(['answerBody' => $body]);

        return response()->json(['success' => true, 'message' => 'The community now shows the edited answer.']);
    }

    /**
     * Take a posted answer back off the community.
     *
     * The reply goes the way everything goes here — hidden, not destroyed —
     * and the draft returns to the review shelf rather than disappearing, so
     * a takedown is a second chance at the wording, not a loss of the work.
     */
    public function unpost(Request $request)
    {
        $draft = CommunityAiAnswerDraft::active()->where('status', 'posted')
            ->find($this->draftId($request));
        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'Posted answer not found.'], 404);
        }

        if ($draft->postedReplyId) {
            CommunityGroupReply::where('id', $draft->postedReplyId)->update(['deleteStatus' => 0]);
        }
        $draft->update(['status' => 'pending', 'postedReplyId' => null, 'postedAt' => null]);

        return response()->json([
            'success' => true,
            'message' => 'Taken down. It is back on the review shelf.',
            'draft' => $this->draftPayload($draft->fresh()),
        ]);
    }

    /**
     * Ask again, with a note about what to change.
     *
     * The instruction is the operator's, and it is about the answer that is
     * already there — "shorter", "give the rates in bags", "answer in
     * Tagalog" — so the previous answer goes to the model with it.
     */
    public function regenerate(Request $request)
    {
        if (! $this->ai->isUsable()) {
            return response()->json(['success' => false, 'message' => 'The AniSenso AI is not configured yet.'], 422);
        }

        $draft = CommunityAiAnswerDraft::active()->find($this->draftId($request));
        if (! $draft) {
            return response()->json(['success' => false, 'message' => 'Answer not found.'], 404);
        }

        $post = $draft->post;
        $question = trim(($draft->questionTitle ? $draft->questionTitle . "\n\n" : '') . (string) $draft->questionBody);
        if ($question === '') {
            return response()->json(['success' => false, 'message' => 'That question has no text to answer.'], 422);
        }

        // What the page is showing is what the operator is asking to improve;
        // for a posted answer that is the live reply, not the stored draft.
        $previous = trim((string) $request->input('answerBody', '')) ?: (string) $draft->answerBody;

        try {
            $answer = $this->ai->refine(
                $question,
                $previous,
                (string) $request->input('instruction', ''),
                $this->groupContext($post)
            );
        } catch (\Throwable $e) {
            Log::warning('Community AI regenerate failed', ['draftId' => $draft->id, 'error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'The AI could not answer again: ' . $e->getMessage()], 502);
        }

        // A pending draft keeps the new wording; a posted one waits for the
        // operator to press Save, because saving it means editing what the
        // community is already reading.
        if ($draft->status === 'pending') {
            $draft->update(['answerBody' => $answer, 'model' => $this->ai->settings()->effectiveModel()]);
        }

        return response()->json([
            'success' => true,
            'message' => 'Answered again.',
            'answerBody' => $answer,
            'model' => $this->ai->settings()->effectiveModel(),
            'saved' => $draft->status === 'pending',
        ]);
    }

    /**
     * Strip the markdown out of an answer, without asking the model again.
     *
     * Everything generated before the house style existed is full of
     * asterisks and hashes that the community shows literally. Rewriting
     * those through the AI costs a call and changes the words; this only
     * changes the punctuation, and it is the same tidy() every new answer
     * already passes through.
     */
    public function tidy(Request $request)
    {
        $body = (string) $request->input('answerBody', '');
        if (trim($body) === '') {
            return response()->json(['success' => false, 'message' => 'There is nothing to tidy.'], 422);
        }

        $clean = CommunityAiAnswerService::tidy($body);

        return response()->json([
            'success' => true,
            'message' => $clean === trim($body) ? 'Nothing to clean up — it was already plain.' : 'Formatting cleaned up.',
            'answerBody' => $clean,
            'changed' => $clean !== trim($body),
        ]);
    }

    /** The id every one of these actions is addressed by. */
    private function draftId(Request $request): int
    {
        return (int) ($request->query('id') ?: $request->input('id'));
    }

    /** What the model is told about where the question was asked. */
    private function groupContext(?CommunityGroupPost $post): string
    {
        return 'This is a question posted by a farmer in the "' . (optional(optional($post)->group)->name ?? 'community')
            . '" community group. Reply helpfully and concisely as the AI Technician.'
            . ' Do not mention that you are an AI unless asked.';
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
