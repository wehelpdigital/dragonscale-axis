<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class RgSeoPagesController extends Controller
{
    /**
     * List pages for a single keyword. URL: /resort-guru-keywords-pages?id={keyword_id}
     */
    public function keywordPages(Request $request)
    {
        if (!Schema::hasTable('rg_seo_pages')) {
            return view('resortGuruAdmin.pending', ['title' => 'Pages']);
        }
        $keywordId = (int) $request->input('id');
        $keyword = DB::table('rg_keywords')->where('id', $keywordId)->first();
        if (!$keyword) abort(404);

        $pages = DB::table('rg_seo_pages')
            ->where('keyword_id', $keywordId)
            ->orderByDesc('is_primary')
            ->orderBy('id')
            ->get();

        $listingsCount = Schema::hasTable('rg_listings')
            ? (int) DB::table('rg_listings')->where('keyword_id', $keywordId)->where('status', 'active')->count()
            : 0;

        return view('resortGuruAdmin.keywords-pages', compact('keyword', 'pages', 'listingsCount'));
    }

    /**
     * Show create-page form (must include keyword_id).
     */
    public function createForm(Request $request)
    {
        $keywordId = (int) $request->input('keyword_id');
        $keyword = DB::table('rg_keywords')->where('id', $keywordId)->first();
        if (!$keyword) abort(404);
        return view('resortGuruAdmin.keywords-pages-create', compact('keyword'));
    }

    /**
     * Store a new page for a keyword.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'keyword_id' => 'required|integer|exists:rg_keywords,id',
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:200|regex:/^[a-z0-9-]+$/',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'h1' => 'nullable|string|max:255',
            'is_primary' => 'nullable',
            'is_published' => 'nullable',
        ]);

        $keyword = DB::table('rg_keywords')->where('id', $data['keyword_id'])->first();
        if (!$keyword) abort(404);

        $slug = $data['slug'] ?: $this->uniqueSlug(Str::slug($data['title']));
        if ($slug !== $data['slug'] && DB::table('rg_seo_pages')->where('slug', $slug)->exists()) {
            $slug = $this->uniqueSlug($slug);
        } elseif (DB::table('rg_seo_pages')->where('slug', $slug)->exists()) {
            return back()->withErrors(['slug' => 'Slug already taken.'])->withInput();
        }

        $isPrimary = $request->has('is_primary') ? 1 : 0;
        $isPublished = $request->has('is_published') ? 1 : 0;

        DB::transaction(function () use ($data, $slug, $isPrimary, $isPublished, &$newId) {
            // If marking new page as primary, demote others
            if ($isPrimary) {
                DB::table('rg_seo_pages')->where('keyword_id', $data['keyword_id'])->update(['is_primary' => 0]);
            }
            $newId = DB::table('rg_seo_pages')->insertGetId([
                'keyword_id' => $data['keyword_id'],
                'slug' => $slug,
                'title' => $data['title'],
                'meta_title' => $data['meta_title'] ?? '',
                'meta_description' => $data['meta_description'] ?? '',
                'h1' => $data['h1'] ?? $data['title'],
                'is_primary' => $isPrimary,
                'is_published' => $isPublished,
                'published_at' => $isPublished ? now() : null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        $this->bustSitemapCache();
        return redirect()->route('resort-guru-pages.edit', ['id' => $newId])
            ->with('success', 'Page created. Use the builder to add content.');
    }

    public function edit(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_seo_pages')->where('id', $id)->first();
        if (!$page) abort(404);
        $keyword = DB::table('rg_keywords')->where('id', $page->keyword_id)->first();
        $siblingPages = DB::table('rg_seo_pages')
            ->where('keyword_id', $page->keyword_id)
            ->where('id', '<>', $id)
            ->select('id', 'title', 'slug', 'is_primary')
            ->get();
        return view('resortGuruAdmin.pages-edit', compact('page', 'keyword', 'siblingPages'));
    }

    /**
     * Live Editor view: a full-bleed iframe of the published frontend
     * page with admin chrome overlaid. Sets up an HMAC-signed _lt
     * token that the frontend's KeywordPageController validates before
     * exposing the edit attributes on each block. The view itself
     * carries the parent-side postMessage handler + AJAX calls to the
     * existing /resort-guru-blocks-{save,delete,reorder} endpoints.
     *
     * The token + the same secret are shared with the frontend via
     * LIVE_EDIT_SECRET in both apps' .env. Without it, redirect back
     * to the classic editor with a helpful message.
     */
    public function liveEdit(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_seo_pages')->where('id', $id)->first();
        if (!$page) abort(404);
        $keyword = DB::table('rg_keywords')->where('id', $page->keyword_id)->first();

        $secret = (string) env('LIVE_EDIT_SECRET');
        if ($secret === '') {
            return redirect()
                ->route('resort-guru-pages.edit', ['id' => $id])
                ->with('error', 'LIVE_EDIT_SECRET is not set in the mother app .env. Add it (matching the frontend\'s value) before opening the Live Editor.');
        }
        $expiry = time() + 3600;
        $token = hash_hmac('sha256', $page->slug . '|' . $expiry, $secret);
        $signedToken = $token . '.' . $expiry;

        $frontendBase = \App\Support\RgFrontend::url();
        $previewUrl = $frontendBase . '/' . $page->slug . '?_lt=' . urlencode($signedToken);

        return view('resortGuruAdmin.pages-live-edit', compact('page', 'keyword', 'previewUrl'));
    }

    /**
     * Focused single-field metadata editor used by the Live Editor when
     * the operator clicks Edit on a page-meta element (H1, eyebrow,
     * subtitle, WWWW summary). Renders a minimal view with just the
     * relevant field + a Save button that posts to the existing
     * update() action with only that one field, so we don't risk
     * blanking out anything else the operator hasn't touched.
     */
    public function editMetaSingle(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_seo_pages')->where('id', $id)->first();
        if (!$page) abort(404);

        $field = (string) $request->input('field', 'h1');

        // Whitelist of editable fields. Each entry carries the label,
        // input type, and (for h1_eyebrow / h1) the half it edits inside
        // the packed h1 column ("eyebrow ~~ heading").
        $fields = [
            'h1' => [
                'label' => 'H1 Heading',
                'type' => 'text',
                'help' => 'Main page heading. Renders large below the eyebrow.',
                'value_resolver' => function ($p) {
                    $stored = $p->h1 ?: $p->title;
                    if (str_contains($stored, ' ~~ ')) {
                        [, $h1] = explode(' ~~ ', $stored, 2);
                        return $h1;
                    }
                    return $stored;
                },
            ],
            'h1_eyebrow' => [
                'label' => 'Eyebrow (above H1)',
                'type' => 'text',
                'help' => 'Smaller lede line displayed above the H1, e.g. "Looking for a hotel in Cebu?".',
                'value_resolver' => function ($p) {
                    $stored = $p->h1 ?: '';
                    if (str_contains($stored, ' ~~ ')) {
                        [$eyebrow] = explode(' ~~ ', $stored, 2);
                        return $eyebrow;
                    }
                    return '';
                },
            ],
            // subtitle / tldr / wwww_json fields were edited here
            // until they moved to content blocks (subtitle_intro,
            // tldr_card, wwww_card). The Live Editor now surfaces
            // their block toolbars in the iframe — no page-meta
            // entry needed.
        ];

        if (!isset($fields[$field])) abort(400, 'Unknown metadata field: ' . $field);

        $spec = $fields[$field];
        $currentValue = ($spec['value_resolver'])($page);

        return view('resortGuruAdmin.pages-meta-edit-single', [
            'page' => $page,
            'field' => $field,
            'spec' => $spec,
            'currentValue' => $currentValue,
            'currentTldr' => '',
            'currentWwww' => '',
        ]);
    }

    /**
     * Backend for the focused single-field metadata editor. Updates
     * only the field that was edited (no risk of zeroing out untouched
     * columns). h1 + h1_eyebrow share the packed `h1` column, joined
     * by " ~~ ", so we recompose before saving.
     */
    public function updateMetaSingle(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_seo_pages')->where('id', $id)->first();
        if (!$page) abort(404);
        $field = (string) $request->input('field', '');

        $updates = ['updated_at' => now()];

        if (!in_array($field, ['h1', 'h1_eyebrow'], true)) {
            abort(400, 'Unknown metadata field: ' . $field);
        }

        $stored = $page->h1 ?: '';
        $eyebrow = '';
        $h1 = $stored;
        if (str_contains($stored, ' ~~ ')) {
            [$eyebrow, $h1] = explode(' ~~ ', $stored, 2);
        }
        if ($field === 'h1') {
            $h1 = (string) $request->input('value', '');
        } else {
            $eyebrow = (string) $request->input('value', '');
        }
        $updates['h1'] = $eyebrow === '' ? $h1 : ($eyebrow . ' ~~ ' . $h1);


        DB::table('rg_seo_pages')->where('id', $id)->update($updates);

        return view('resortGuruAdmin.pages-meta-edit-single-saved', [
            'page' => $page,
            'field' => $field,
        ]);
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_seo_pages')->where('id', $id)->first();
        if (!$page) abort(404);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'nullable|string|max:200|regex:/^[a-z0-9-]+$/',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'meta_keywords' => 'nullable|string|max:500',
            'canonical_url' => 'nullable|url|max:500',
            'robots' => 'nullable|string|max:50',
            'h1' => 'nullable|string|max:255',
            // subtitle / tldr / wwww_* fields removed — they live as
            // content blocks (subtitle_intro, tldr_card, wwww_card) now.
            'og_image_path' => 'nullable|string|max:500',
            'fallback_listing_html' => 'nullable|string',
            'schema_json' => 'nullable|string',
            'is_published' => 'nullable',
            'is_primary' => 'nullable',
            'author_id' => 'nullable|integer',
        ]);

        $isPublished = $request->has('is_published') ? 1 : 0;
        $isPrimary = $request->has('is_primary') ? 1 : 0;

        // Slug change — ensure unique
        $newSlug = $data['slug'] ?? $page->slug;
        if ($newSlug !== $page->slug && DB::table('rg_seo_pages')->where('slug', $newSlug)->where('id', '<>', $id)->exists()) {
            return back()->withErrors(['slug' => 'Slug already taken by another page.'])->withInput();
        }

        DB::transaction(function () use ($id, $page, $data, $newSlug, $isPublished, $isPrimary) {
            if ($isPrimary && !$page->is_primary) {
                // Demote other pages of this keyword
                DB::table('rg_seo_pages')->where('keyword_id', $page->keyword_id)->update(['is_primary' => 0]);
            }
            $updateRow = [
                'slug' => $newSlug,
                'title' => $data['title'],
                'meta_title' => $data['meta_title'] ?? '',
                'meta_description' => $data['meta_description'] ?? '',
                'meta_keywords' => $data['meta_keywords'] ?? '',
                'canonical_url' => $data['canonical_url'] ?? null,
                'robots' => $data['robots'] ?? null,
                'h1' => $data['h1'] ?? '',
                'og_image_path' => $data['og_image_path'] ?? null,
                'fallback_listing_html' => $data['fallback_listing_html'] ?? null,
                'schema_json' => $data['schema_json'] ?? null,
                'is_published' => $isPublished,
                'is_primary' => $isPrimary,
                'author_id' => $data['author_id'] ?: null,
                'published_at' => $isPublished && !$page->published_at ? now() : $page->published_at,
                'updated_at' => now(),
            ];
            // subtitle / tldr / wwww_json are NOT written here — they
            // moved to content blocks. The source columns stay
            // untouched on save so the migrated data is preserved as
            // a rollback safety net.
            DB::table('rg_seo_pages')->where('id', $id)->update($updateRow);
        });

        $this->bustSitemapCache();
        return redirect()->route('resort-guru-pages.edit', ['id' => $id])->with('success', 'Page saved. Frontend reflects changes immediately.');
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_seo_pages')->where('id', $id)->first();
        if (!$page) abort(404);
        DB::transaction(function () use ($id, $page) {
            DB::table('rg_content_blocks')->where('owner_type', 'seo_page')->where('owner_id', $id)->delete();
            DB::table('rg_seo_pages')->where('id', $id)->delete();
        });
        $this->bustSitemapCache();
        return response()->json(['ok' => true]);
    }

    public function setPrimary(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_seo_pages')->where('id', $id)->first();
        if (!$page) abort(404);
        DB::transaction(function () use ($id, $page) {
            DB::table('rg_seo_pages')->where('keyword_id', $page->keyword_id)->update(['is_primary' => 0]);
            DB::table('rg_seo_pages')->where('id', $id)->update(['is_primary' => 1, 'updated_at' => now()]);
        });
        $this->bustSitemapCache();
        return response()->json(['ok' => true]);
    }

    public function togglePublish(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_seo_pages')->where('id', $id)->first();
        if (!$page) abort(404);
        $new = !$page->is_published;
        DB::table('rg_seo_pages')->where('id', $id)->update([
            'is_published' => $new ? 1 : 0,
            'published_at' => $new && !$page->published_at ? now() : $page->published_at,
            'updated_at' => now(),
        ]);
        $this->bustSitemapCache();
        return response()->json(['ok' => true, 'is_published' => $new]);
    }

    public function aiGenerate(Request $request)
    {
        return response()->json([
            'ok' => false,
            'message' => 'AI generation lands in Phase 4. Coming soon.',
        ], 501);
    }

    public function seoAnalyze(Request $request)
    {
        $id = (int) $request->input('id');
        return response()->json(app(\App\Services\SeoAnalyzer::class)->analyzeSeoPage($id));
    }

    public function uploadImage(Request $request)
    {
        $request->validate(['file' => 'required|image|max:5120']);
        $path = $request->file('file')->store('resort-guru', 'public');
        return response()->json(['location' => Storage::url($path)]);
    }

    private function uniqueSlug(string $base): string
    {
        $slug = $base;
        $i = 1;
        while (DB::table('rg_seo_pages')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }

    private function bustSitemapCache(): void
    {
        try {
            Cache::forget('sitemap.xml');
            Cache::forget('rg.sitemap');
        } catch (\Throwable $e) {
        }
    }
}
