<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use App\Support\AnisystemMedia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

/**
 * AniSystem > Website pages (2026-10-01): anee.io's public guides, crop
 * problems, blog and feature pages, and the block builder that edits them.
 *
 * The pages live in the shared `as_site_pages` table; anee.io draws them
 * (App\Support\SitePages over there). A page is its settings (address, title,
 * SEO title, meta description, focus keyphrase, picture) and its blocks. The
 * builder previews live: every change is posted to anee.io's signed preview
 * address and drawn by anee.io itself, so what the builder shows is exactly
 * what a visitor will get.
 *
 * anee.io ships its pages as files and fills the table from them; a page
 * edited here is marked (editedAt) so a later shipment leaves it alone, and
 * "Put back the shipped version" restores the file's copy (seedJson).
 */
class AnisystemSitePagesController extends Controller
{
    public const SECTIONS = [
        'crops' => 'Crop guides',
        'problems' => 'Crop problems',
        'blog' => 'Blog',
        'features' => 'Features',
    ];

    /** The kinds of block, in the order the builder offers them. */
    public const BLOCKS = [
        'text' => ['Paragraphs', 'bx-paragraph'],
        'heading' => ['Heading', 'bx-heading'],
        'list' => ['List', 'bx-list-ul'],
        'steps' => ['Steps', 'bx-list-ol'],
        'table' => ['Table', 'bx-table'],
        'callout' => ['Tip or warning', 'bx-bulb'],
        'image' => ['Picture', 'bx-image'],
        'quote' => ['Quote', 'bxs-quote-alt-left'],
        'faq' => ['FAQ', 'bx-help-circle'],
        'cta' => ['anee.io call to action', 'bx-rocket'],
        'links' => ['Related links', 'bx-link'],
        'sources' => ['Sources', 'bx-book-bookmark'],
        'divider' => ['Divider', 'bx-minus'],
    ];

    public function index()
    {
        $rows = collect();
        $ready = true;
        try {
            $rows = DB::table('as_site_pages')->where('deleteStatus', 1)
                ->orderBy('section')->orderBy('sortOrder')->orderBy('title')
                ->get(['id', 'section', 'slug', 'lang', 'category', 'title', 'focusKeyword', 'status', 'blocks', 'excerpt', 'editedAt', 'editedBy', 'updated_at']);
        } catch (\Throwable $e) {
            $ready = false;
        }
        $rows = $rows->map(function ($r) {
            $blocks = json_decode((string) $r->blocks, true) ?: [];
            $r->words = self::words($r->excerpt, $blocks);
            $r->liveUrl = self::liveUrl($r->section, $r->slug);
            unset($r->blocks);

            return $r;
        });

        return view('aniSensoAdmin.site-pages.index', [
            'rows' => $rows,
            'ready' => $ready,
            'sections' => self::SECTIONS,
        ]);
    }

    /** A new page: its address and title, then straight into the builder. */
    public function store(Request $request)
    {
        $v = Validator::make($request->all(), [
            'section' => 'required|in:' . implode(',', array_keys(self::SECTIONS)),
            'title' => 'required|string|max:200',
            'slug' => ['nullable', 'string', 'max:120', 'regex:/^[a-z0-9]+(?:-[a-z0-9]+)*$/'],
        ], ['slug.regex' => 'The address may use small letters, numbers and single dashes only.']);
        if ($v->fails()) {
            return back()->withErrors($v)->withInput();
        }
        $slug = $request->input('slug') ?: Str::slug($request->input('title'));
        if (DB::table('as_site_pages')->where('section', $request->input('section'))->where('slug', $slug)->exists()) {
            return back()->withErrors(['slug' => 'A page already lives at that address.'])->withInput();
        }
        $id = DB::table('as_site_pages')->insertGetId([
            'section' => $request->input('section'),
            'slug' => $slug,
            'lang' => 'en',
            'title' => $request->input('title'),
            'metaTitle' => mb_substr($request->input('title'), 0, 60),
            'blocks' => json_encode([['type' => 'text', 'text' => '']]),
            'keywords' => json_encode([]),
            'status' => 'draft',
            'sortOrder' => 999,
            'editedAt' => now(),
            'editedBy' => $this->who(),
            'deleteStatus' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return redirect()->route('anisenso-site-pages.build', ['id' => $id]);
    }

    public function build(Request $request)
    {
        $page = $this->page($request);

        return view('aniSensoAdmin.site-pages.build', [
            'page' => $page,
            'kinds' => self::BLOCKS,
            'sections' => self::SECTIONS,
            'preview' => $this->previewAddress(),
            'liveUrl' => self::liveUrl($page->section, $page->slug),
            'hasSeed' => filled($page->seedJson),
        ]);
    }

    /** The page as the builder holds it. */
    public function data(Request $request)
    {
        $p = $this->page($request);

        return response()->json(['success' => true, 'page' => self::shape($p), 'preview' => $this->previewAddress()]);
    }

    /** Save the whole page at once: settings and blocks. */
    public function save(Request $request)
    {
        $p = $this->page($request);
        $in = json_decode((string) $request->input('page'), true);
        if (! is_array($in)) {
            return response()->json(['success' => false, 'message' => 'Nothing to save.'], 422);
        }
        $section = (string) ($in['section'] ?? $p->section);
        $slug = (string) ($in['slug'] ?? $p->slug);
        if (! isset(self::SECTIONS[$section]) || ! preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $slug)) {
            return response()->json(['success' => false, 'message' => 'The address may use small letters, numbers and single dashes only.'], 422);
        }
        if (DB::table('as_site_pages')->where('section', $section)->where('slug', $slug)->where('id', '!=', $p->id)->exists()) {
            return response()->json(['success' => false, 'message' => 'Another page already lives at /' . $section . '/' . $slug . '.'], 422);
        }
        if (trim((string) ($in['title'] ?? '')) === '') {
            return response()->json(['success' => false, 'message' => 'The page needs a title.'], 422);
        }
        $status = in_array($in['status'] ?? '', ['published', 'draft'], true) ? $in['status'] : $p->status;
        DB::table('as_site_pages')->where('id', $p->id)->update([
            'section' => $section,
            'slug' => $slug,
            'lang' => in_array($in['lang'] ?? '', ['en', 'tl'], true) ? $in['lang'] : 'en',
            'category' => mb_substr(trim((string) ($in['category'] ?? '')), 0, 60) ?: null,
            'title' => mb_substr(trim((string) $in['title']), 0, 200),
            'metaTitle' => mb_substr(trim((string) ($in['metaTitle'] ?? '')), 0, 120) ?: null,
            'metaDescription' => mb_substr(trim((string) ($in['metaDescription'] ?? '')), 0, 320) ?: null,
            'focusKeyword' => mb_substr(trim((string) ($in['focusKeyword'] ?? '')), 0, 120) ?: null,
            'keywords' => json_encode(array_values(array_filter(array_map('trim', (array) ($in['keywords'] ?? [])))), JSON_UNESCAPED_UNICODE),
            'excerpt' => trim((string) ($in['excerpt'] ?? '')) ?: null,
            'heroImage' => json_encode(self::cleanHero($in['heroImage'] ?? []), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'blocks' => json_encode(self::clean((array) ($in['blocks'] ?? [])), JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'status' => $status,
            'publishedAt' => $status === 'published' ? ($p->publishedAt ?? now()) : $p->publishedAt,
            'editedAt' => now(),
            'editedBy' => $this->who(),
            'updated_at' => now(),
        ]);
        $fresh = DB::table('as_site_pages')->find($p->id);

        return response()->json([
            'success' => true,
            'message' => $status === 'published' ? 'Saved. It is live on anee.io.' : 'Saved as a draft.',
            'page' => self::shape($fresh),
            'liveUrl' => self::liveUrl($fresh->section, $fresh->slug),
        ]);
    }

    /** A picture for a block or the page's header, kept where anee.io can serve it. */
    public function upload(Request $request)
    {
        $p = $this->page($request);
        $file = $request->file('file');
        if (! $file || ! $file->isValid()) {
            return response()->json(['success' => false, 'message' => 'No file in that upload.'], 422);
        }
        $ext = strtolower($file->getClientOriginalExtension() ?: 'jpg');
        if (! in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'], true)) {
            return response()->json(['success' => false, 'message' => 'Pictures only: JPG, PNG, WEBP or GIF.'], 422);
        }
        if ($file->getSize() > 8_000_000) {
            return response()->json(['success' => false, 'message' => 'Under 8 MB, please.'], 413);
        }
        $name = Str::slug(pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME)) ?: 'picture';
        $path = 'anisystem/site/' . $p->section . '/' . $p->slug . '/' . Str::limit($name, 40, '') . '-' . Str::random(6) . '.' . $ext;
        Storage::disk('public')->put($path, file_get_contents($file->getRealPath()));

        return response()->json(['success' => true, 'url' => AnisystemMedia::url(AnisystemMedia::REMOTE_PREFIX . $path)]);
    }

    /** Publish or take down, from the list. */
    public function toggle(Request $request)
    {
        $p = $this->page($request);
        $to = $p->status === 'published' ? 'draft' : 'published';
        DB::table('as_site_pages')->where('id', $p->id)->update([
            'status' => $to,
            'publishedAt' => $to === 'published' ? ($p->publishedAt ?? now()) : $p->publishedAt,
            'editedAt' => now(), 'editedBy' => $this->who(), 'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'status' => $to,
            'message' => $to === 'published' ? 'Published: it is live on anee.io.' : 'Taken down: it is a draft now.']);
    }

    public function destroy(Request $request)
    {
        $p = $this->page($request);
        DB::table('as_site_pages')->where('id', $p->id)->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return response()->json(['success' => true, 'message' => 'Page removed.']);
    }

    /** Put back the version anee.io shipped. */
    public function reset(Request $request)
    {
        $p = $this->page($request);
        $seed = json_decode((string) $p->seedJson, true);
        if (! is_array($seed)) {
            return response()->json(['success' => false, 'message' => 'This page was made here; there is no shipped version to go back to.'], 422);
        }
        DB::table('as_site_pages')->where('id', $p->id)->update([
            'lang' => $seed['lang'] ?? 'en',
            'category' => $seed['category'] ?? null,
            'title' => $seed['title'] ?? $p->title,
            'metaTitle' => $seed['metaTitle'] ?? null,
            'metaDescription' => $seed['metaDescription'] ?? null,
            'focusKeyword' => $seed['focusKeyword'] ?? null,
            'keywords' => json_encode($seed['keywords'] ?? [], JSON_UNESCAPED_UNICODE),
            'excerpt' => $seed['excerpt'] ?? null,
            'heroImage' => json_encode($seed['heroImage'] ?? null, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'blocks' => json_encode($seed['blocks'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'editedAt' => null, 'editedBy' => null, 'updated_at' => now(),
        ]);

        return response()->json(['success' => true, 'message' => 'The shipped version is back.', 'page' => self::shape(DB::table('as_site_pages')->find($p->id))]);
    }

    /** A fresh signed preview address, for a builder left open a long time. */
    public function previewToken()
    {
        return response()->json(['success' => true, 'preview' => $this->previewAddress()]);
    }

    // ---------------------------------------------------------------------

    private function page(Request $request): object
    {
        $p = DB::table('as_site_pages')->where('deleteStatus', 1)->where('id', (int) $request->query('id'))->first();
        abort_unless($p, 404);

        return $p;
    }

    private function who(): string
    {
        return mb_substr((string) (auth()->user()->name ?? 'an admin'), 0, 80);
    }

    /** The builder's preview door on anee.io, signed for twelve hours. */
    private function previewAddress(): array
    {
        $secret = (string) config('services.anisystem_media.token');
        $e = time() + 12 * 3600;

        return [
            'url' => rtrim((string) config('anisystem.url'), '/') . '/site-preview?e=' . $e . '&t=' . hash_hmac('sha256', 'site-preview:' . $e, $secret),
            'expires' => $e,
            'linked' => $secret !== '',
        ];
    }

    public static function liveUrl(string $section, string $slug): string
    {
        return rtrim((string) config('anisystem.url'), '/') . '/' . $section . '/' . $slug;
    }

    private static function shape(object $p): array
    {
        return [
            'id' => (int) $p->id,
            'section' => $p->section,
            'slug' => $p->slug,
            'lang' => $p->lang ?: 'en',
            'category' => $p->category,
            'title' => $p->title,
            'metaTitle' => $p->metaTitle,
            'metaDescription' => $p->metaDescription,
            'focusKeyword' => $p->focusKeyword,
            'keywords' => json_decode((string) $p->keywords, true) ?: [],
            'excerpt' => $p->excerpt,
            'heroImage' => json_decode((string) $p->heroImage, true) ?: ['src' => '', 'alt' => '', 'credit' => ''],
            'blocks' => json_decode((string) $p->blocks, true) ?: [],
            'status' => $p->status,
            'editedAt' => $p->editedAt,
            'editedBy' => $p->editedBy,
            'updatedAt' => $p->updated_at,
        ];
    }

    private static function cleanHero($h): ?array
    {
        $h = is_array($h) ? $h : [];
        $src = trim((string) ($h['src'] ?? ''));

        return $src === '' ? null : [
            'src' => mb_substr($src, 0, 500),
            'alt' => mb_substr(trim((string) ($h['alt'] ?? '')), 0, 250),
            'credit' => mb_substr(trim((string) ($h['credit'] ?? '')), 0, 250),
        ];
    }

    /** Keep each block to the fields its kind has; drop anything else. */
    public static function clean(array $blocks): array
    {
        $str = fn ($v, $n = 20000) => mb_substr(trim((string) $v), 0, $n);
        $out = [];
        foreach (array_slice($blocks, 0, 200) as $b) {
            $t = (string) ($b['type'] ?? '');
            if (! isset(self::BLOCKS[$t])) {
                continue;
            }
            $k = ['type' => $t];
            switch ($t) {
                case 'heading':
                    $k['level'] = (int) ($b['level'] ?? 2) === 3 ? 3 : 2;
                    $k['text'] = $str($b['text'] ?? '', 250);
                    break;
                case 'text':
                    $k['text'] = $str($b['text'] ?? '');
                    break;
                case 'list':
                    $k['ordered'] = ! empty($b['ordered']);
                    $k['items'] = array_values(array_filter(array_map(fn ($x) => $str($x, 2000), (array) ($b['items'] ?? [])), 'strlen'));
                    break;
                case 'steps':
                    $k['items'] = array_values(array_filter(array_map(fn ($x) => ['title' => $str($x['title'] ?? '', 200), 'text' => $str($x['text'] ?? '', 4000)], array_filter((array) ($b['items'] ?? []), 'is_array')), fn ($x) => $x['title'] !== '' || $x['text'] !== ''));
                    break;
                case 'table':
                    $k['caption'] = $str($b['caption'] ?? '', 300);
                    $k['rows'] = array_values(array_map(fn ($r) => array_map(fn ($c) => $str($c, 500), array_values((array) $r)), array_filter((array) ($b['rows'] ?? []), 'is_array')));
                    break;
                case 'callout':
                    $k['tone'] = in_array($b['tone'] ?? '', ['tip', 'warn', 'info'], true) ? $b['tone'] : 'tip';
                    $k['title'] = $str($b['title'] ?? '', 200);
                    $k['text'] = $str($b['text'] ?? '', 4000);
                    break;
                case 'image':
                    $k['src'] = $str($b['src'] ?? ($b['url'] ?? ''), 500);
                    $k['alt'] = $str($b['alt'] ?? '', 250);
                    $k['caption'] = $str($b['caption'] ?? '', 400);
                    break;
                case 'quote':
                    $k['text'] = $str($b['text'] ?? '', 2000);
                    $k['cite'] = $str($b['cite'] ?? '', 200);
                    break;
                case 'faq':
                    $k['items'] = array_values(array_filter(array_map(fn ($x) => ['q' => $str($x['q'] ?? '', 300), 'a' => $str($x['a'] ?? '', 4000)], array_filter((array) ($b['items'] ?? []), 'is_array')), fn ($x) => $x['q'] !== ''));
                    break;
                case 'cta':
                    foreach (['title' => 200, 'text' => 1000, 'label' => 80, 'url' => 300] as $f => $n) {
                        $k[$f] = $str($b[$f] ?? '', $n);
                    }
                    break;
                case 'links':
                case 'sources':
                    if ($t === 'links') {
                        $k['title'] = $str($b['title'] ?? '', 200);
                    }
                    $k['items'] = array_values(array_filter(array_map(fn ($x) => ['label' => $str($x['label'] ?? '', 250), 'url' => $str($x['url'] ?? '', 500)], array_filter((array) ($b['items'] ?? []), 'is_array')), fn ($x) => $x['url'] !== ''));
                    break;
            }
            $out[] = $k;
        }

        return $out;
    }

    private static function words(?string $excerpt, array $blocks): int
    {
        $text = (string) $excerpt;
        array_walk_recursive($blocks, function ($v, $k) use (&$text) {
            if (is_string($v) && ! in_array($k, ['type', 'url', 'src', 'tone'], true)) {
                $text .= ' ' . $v;
            }
        });

        return str_word_count(strip_tags(preg_replace('/\[([^\]]+)\]\([^)]+\)/', '$1', $text)));
    }
}
