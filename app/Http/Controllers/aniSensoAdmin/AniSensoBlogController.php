<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AnisystemUser;
use App\Models\AsCommunityBlogComment;
use App\Models\AsCommunityBlogPost;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Technician's Blog — the AniSenso team authors articles here that surface in
 * the AniSystem community (beside Discussions). Publishing an article notifies
 * every member's bell.
 */
class AniSensoBlogController extends Controller
{
    public function index()
    {
        $posts = AsCommunityBlogPost::active()
            ->withCount('comments')
            ->orderByDesc('id')
            ->paginate(20);

        return view('aniSensoAdmin.blog.index', compact('posts'));
    }

    public function create()
    {
        return view('aniSensoAdmin.blog.form', ['post' => new AsCommunityBlogPost(), 'mode' => 'create']);
    }

    public function edit($id)
    {
        $post = AsCommunityBlogPost::active()->where('id', $id)->firstOrFail();

        return view('aniSensoAdmin.blog.form', ['post' => $post, 'mode' => 'edit']);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $post = new AsCommunityBlogPost();
        $this->fill($post, $request, $data);
        $wasPublished = false;
        $this->applyPublish($post, $request, $wasPublished);
        $post->save();

        if ($post->isPublished && ! $wasPublished) {
            $this->notifyMembers($post);
        }

        return redirect()->route('anisenso-blog.index')->with('success', 'Article saved.');
    }

    public function update(Request $request, $id)
    {
        $post = AsCommunityBlogPost::active()->where('id', $id)->firstOrFail();
        $data = $this->validated($request);
        $wasPublished = (bool) $post->isPublished;
        $this->fill($post, $request, $data);
        $this->applyPublish($post, $request, $wasPublished);
        $post->save();

        // Notify only on the first transition to published.
        if ($post->isPublished && ! $wasPublished) {
            $this->notifyMembers($post);
        }

        return redirect()->route('anisenso-blog.index')->with('success', 'Article updated.');
    }

    /**
     * The conversation under one article.
     *
     * Read here rather than on the edit form: editing an article and
     * moderating what people said about it are two jobs, and the form is long
     * enough already.
     */
    public function comments($id)
    {
        $post = AsCommunityBlogPost::active()->where('id', $id)->firstOrFail();

        $comments = AsCommunityBlogComment::active()
            ->where('blogPostId', $post->id)
            ->with('author')
            ->orderByDesc('id')
            ->paginate(30);

        return view('aniSensoAdmin.blog.comments', compact('post', 'comments'));
    }

    /** Take a comment down. Reversible in the row, gone from the app. */
    public function deleteComment($id)
    {
        $comment = AsCommunityBlogComment::active()->where('id', $id)->firstOrFail();
        $comment->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Comment removed.']);
    }

    public function destroy($id)
    {
        $post = AsCommunityBlogPost::active()->where('id', $id)->firstOrFail();
        $post->update(['deleteStatus' => 0]);

        return response()->json(['success' => true, 'message' => 'Article removed.']);
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'title' => 'required|string|max:191',
            'authorName' => 'nullable|string|max:120',
            'excerpt' => 'nullable|string|max:500',
            'body' => 'nullable|string',
            'cover' => 'nullable|image|max:8192',
            'isPublished' => 'nullable|boolean',
        ]);
    }

    private function fill(AsCommunityBlogPost $post, Request $request, array $data): void
    {
        $post->title = $data['title'];
        $post->slug = Str::slug($data['title']) ?: null;
        $post->authorName = $data['authorName'] ?? null;
        $post->excerpt = $data['excerpt'] ?? null;
        $post->body = $data['body'] ?? null;
        $post->deleteStatus = 1;

        if ($request->hasFile('cover')) {
            $post->coverImagePath = $request->file('cover')->store('community/blog', 'public');
        }
    }

    private function applyPublish(AsCommunityBlogPost $post, Request $request, bool $wasPublished): void
    {
        $publish = $request->boolean('isPublished');
        $post->isPublished = $publish ? 1 : 0;
        if ($publish && ! $wasPublished) {
            $post->publishedAt = now();
        }
    }

    /** Drop a bell notification into every active member, linking to the article. */
    private function notifyMembers(AsCommunityBlogPost $post): void
    {
        $url = rtrim(config('anisystem.url'), '/') . '/app/community/blog/' . $post->id;
        $now = now();

        AnisystemUser::where('deleteStatus', 1)->pluck('id')->chunk(500)->each(function ($chunk) use ($post, $url, $now) {
            $rows = $chunk->map(fn ($uid) => [
                'userId' => $uid,
                'type' => 'blog',
                'title' => 'New from the Technician\'s Blog: ' . Str::limit($post->title, 80),
                'body' => $post->excerpt ? Str::limit($post->excerpt, 120) : null,
                'url' => $url,
                'actorUserId' => null,
                'croppingScheduleId' => null,
                'readAt' => null,
                'deleteStatus' => 1,
                'created_at' => $now,
                'updated_at' => $now,
            ])->all();
            DB::table('anisystem_notifications')->insert($rows);
        });
    }
}
