<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Models\AsCommunityBlogPost;
use App\Support\AnisystemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/**
 * Articles are built out of blocks, and the blocks are flattened to HTML.
 *
 * The client app renders one column — `body` — and knows nothing about
 * builders, which is the whole point: nothing over there has to change for an
 * article to stop being a wall of pasted markup. This side keeps the blocks in
 * `builderJson` so the page can be opened and edited again, and writes the
 * flattened HTML into `body` on every save.
 *
 * The HTML written here is deliberately inside what the client app's article
 * sanitiser allows. Anything outside that list would be quietly dropped on
 * the way to the screen, and a builder whose output disappears is worse than
 * no builder.
 */
class AniSensoBlogBuilderController extends Controller
{
    /** What an article can be made of. */
    public const BLOCKS = [
        'heading' => 'Section heading',
        'text' => 'Paragraphs',
        'image' => 'Picture',
        'gallery' => 'Row of pictures',
        'quote' => 'Pull quote',
        'list' => 'List',
        'note' => 'Note or warning',
        'button' => 'Button',
        'embed' => 'Video',
        'divider' => 'Divider',
    ];

    /** The builder itself: the pieces on the left, anee.io on the right. */
    public function page(Request $request)
    {
        $post = $this->post($request);

        return view('aniSensoAdmin.blog.build', [
            'post' => $post,
            'kinds' => self::BLOCKS,
            'previewUrl' => $this->previewUrl($post),
        ]);
    }

    /** The blocks of one article, for the builder to draw. */
    public function blocks(Request $request)
    {
        $post = $this->post($request);

        return response()->json([
            'success' => true,
            'blocks' => $this->readBlocks($post),
            'previewUrl' => $this->previewUrl($post),
        ]);
    }

    /**
     * Save the whole list at once.
     *
     * The builder holds the article in the browser and sends what it has, so
     * a reorder, an edit and a deletion are the same request. There is no
     * per-block endpoint to keep in step with it, and no way for the list on
     * screen and the list in the database to drift apart.
     */
    public function save(Request $request)
    {
        $post = $this->post($request);

        $blocks = $request->input('blocks');
        if (is_string($blocks)) {
            $blocks = json_decode($blocks, true);
        }
        $blocks = $this->clean(is_array($blocks) ? $blocks : []);

        $post->builderJson = json_encode($blocks);
        $post->body = $this->toHtml($blocks);
        $post->save();

        return response()->json([
            'success' => true,
            'message' => 'Saved.',
            'previewUrl' => $this->previewUrl($post),
            'blocks' => $blocks,
        ]);
    }

    /** A picture for a block, kept where the client app can serve it. */
    public function upload(Request $request)
    {
        $post = $this->post($request);
        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            return response()->json(['success' => false, 'message' => 'No file in that upload.'], 422);
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'gif', 'webp'], true)) {
            return response()->json(['success' => false, 'message' => 'Images only.'], 422);
        }
        if ($file->getSize() > 8_000_000) {
            return response()->json(['success' => false, 'message' => 'Under 8 MB, please.'], 413);
        }

        $path = 'anisystem/blog/' . $post->id . '/' . Str::random(24) . '.' . $ext;
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return response()->json([
            'success' => true,
            'url' => AnisystemMedia::url(AnisystemMedia::REMOTE_PREFIX . $path),
        ]);
    }

    // ---------------------------------------------------------------------

    private function post(Request $request): AsCommunityBlogPost
    {
        return AsCommunityBlogPost::active()
            ->where('id', (int) $request->query('id'))
            ->firstOrFail();
    }

    private function previewUrl(AsCommunityBlogPost $post): string
    {
        // The token has to match what the client app computes, so it is the
        // same HMAC over the same secret — the one the two apps already share
        // for media, rather than a second thing to keep in step.
        $secret = (string) (config('services.anisystem_media.token') ?: config('app.key'));
        $token = substr(hash_hmac('sha256', 'blog-preview:' . $post->id, $secret), 0, 32);
        $base = rtrim((string) config('anisystem.url'), '/');

        return $base . '/blog-preview/' . $post->id . '?t=' . $token;
    }

    private function readBlocks(AsCommunityBlogPost $post): array
    {
        $raw = json_decode((string) $post->builderJson, true);
        if (is_array($raw) && $raw) {
            return $this->clean($raw);
        }

        // An article written before the builder existed opens as one block
        // holding what it already said, rather than as a blank page.
        $body = trim((string) $post->body);

        return $body === '' ? [] : [['type' => 'text', 'html' => $body]];
    }

    /** Keep the shape, drop the rest — a block is only what it says it is. */
    private function clean(array $blocks): array
    {
        $out = [];
        foreach ($blocks as $b) {
            $type = (string) ($b['type'] ?? '');
            if (! isset(self::BLOCKS[$type])) {
                continue;
            }
            $keep = ['type' => $type];
            foreach (['text', 'html', 'level', 'url', 'caption', 'alt', 'label', 'kind', 'title', 'ordered'] as $k) {
                if (isset($b[$k]) && is_scalar($b[$k])) {
                    $keep[$k] = mb_substr((string) $b[$k], 0, 20000);
                }
            }
            foreach (['items', 'images'] as $k) {
                if (isset($b[$k]) && is_array($b[$k])) {
                    $keep[$k] = array_values(array_map(
                        fn ($v) => is_array($v)
                            ? array_map(fn ($x) => mb_substr((string) $x, 0, 2000), array_filter($v, 'is_scalar'))
                            : mb_substr((string) $v, 0, 2000),
                        array_slice($b[$k], 0, 40)
                    ));
                }
            }
            $out[] = $keep;
        }

        return $out;
    }

    /**
     * The blocks as the markup the client app will render.
     *
     * Every tag and class here is one the article sanitiser over there keeps.
     * A video is turned into an embed address rather than trusted as typed,
     * because that sanitiser only lets a frame through when it points at a
     * host it knows.
     */
    private function toHtml(array $blocks): string
    {
        $e = fn ($v) => e((string) $v);
        $out = [];

        foreach ($blocks as $b) {
            switch ($b['type']) {
                case 'heading':
                    $level = in_array((string) ($b['level'] ?? '2'), ['2', '3', '4'], true) ? $b['level'] : '2';
                    $out[] = "<h{$level}>" . $e($b['text'] ?? '') . "</h{$level}>";
                    break;

                case 'text':
                    // Written as plain paragraphs unless the block already
                    // holds markup — an article carried over from before the
                    // builder does, and re-escaping it would show its tags.
                    $html = (string) ($b['html'] ?? $b['text'] ?? '');
                    if ($html !== strip_tags($html)) {
                        $out[] = $html;
                        break;
                    }
                    foreach (preg_split('/\n{2,}/', trim($html)) ?: [] as $para) {
                        if (trim($para) !== '') {
                            $out[] = '<p>' . nl2br($e($para)) . '</p>';
                        }
                    }
                    break;

                case 'image':
                    $url = trim((string) ($b['url'] ?? ''));
                    if ($url === '') {
                        break;
                    }
                    $cap = trim((string) ($b['caption'] ?? ''));
                    $out[] = '<div class="a-fig"><img src="' . $e($url) . '" alt="' . $e($b['alt'] ?? '') . '">'
                        . ($cap !== '' ? '<div class="a-cap">' . $e($cap) . '</div>' : '')
                        . '</div>';
                    break;

                case 'gallery':
                    $imgs = array_filter((array) ($b['images'] ?? []), fn ($u) => trim((string) $u) !== '');
                    if (! $imgs) {
                        break;
                    }
                    $out[] = '<div class="a-gallery">'
                        . implode('', array_map(fn ($u) => '<img src="' . $e($u) . '" alt="">', $imgs))
                        . '</div>';
                    break;

                case 'quote':
                    $out[] = '<blockquote>' . $e($b['text'] ?? '')
                        . (trim((string) ($b['label'] ?? '')) !== '' ? ' <cite>— ' . $e($b['label']) . '</cite>' : '')
                        . '</blockquote>';
                    break;

                case 'list':
                    $items = array_filter((array) ($b['items'] ?? []), fn ($i) => trim((string) $i) !== '');
                    if (! $items) {
                        break;
                    }
                    $tag = ! empty($b['ordered']) && $b['ordered'] !== 'false' ? 'ol' : 'ul';
                    $out[] = "<{$tag}>" . implode('', array_map(fn ($i) => '<li>' . $e($i) . '</li>', $items)) . "</{$tag}>";
                    break;

                case 'note':
                    $warn = ($b['kind'] ?? '') === 'warn';
                    $out[] = '<div class="a-note' . ($warn ? ' is-warn' : '') . '">'
                        . (trim((string) ($b['title'] ?? '')) !== '' ? '<b>' . $e($b['title']) . '</b>' : '')
                        . nl2br($e($b['text'] ?? '')) . '</div>';
                    break;

                case 'button':
                    $url = trim((string) ($b['url'] ?? ''));
                    if ($url === '' || ! preg_match('~^https?://~i', $url)) {
                        break;
                    }
                    $out[] = '<p><a class="a-btn" href="' . $e($url) . '">' . $e($b['label'] ?? 'Read more') . '</a></p>';
                    break;

                case 'embed':
                    $src = $this->embedSrc((string) ($b['url'] ?? ''));
                    if ($src === null) {
                        break;
                    }
                    $out[] = '<div class="a-embed"><iframe src="' . $e($src) . '" frameborder="0"></iframe></div>';
                    break;

                case 'divider':
                    $out[] = '<hr>';
                    break;
            }
        }

        return implode("\n", $out);
    }

    /**
     * A watch address turned into an embed address.
     *
     * The sanitiser on the other side only lets a frame through when it
     * points at youtube's or vimeo's embed host, so pasting a normal video
     * link would silently produce nothing. This is where the link somebody
     * actually copies becomes the one that survives.
     */
    private function embedSrc(string $url): ?string
    {
        $url = trim($url);
        if ($url === '') {
            return null;
        }
        if (preg_match('~(?:youtube\.com/watch\?v=|youtu\.be/|youtube\.com/embed/)([A-Za-z0-9_-]{6,20})~', $url, $m)) {
            return 'https://www.youtube.com/embed/' . $m[1];
        }
        if (preg_match('~vimeo\.com/(?:video/)?(\d{6,12})~', $url, $m)) {
            return 'https://player.vimeo.com/video/' . $m[1];
        }

        return null;
    }
}
