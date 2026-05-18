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
            'og_image_path' => 'nullable|string|max:500',
            'fallback_listing_html' => 'nullable|string',
            'schema_json' => 'nullable|string',
            'is_published' => 'nullable',
            'is_primary' => 'nullable',
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
            DB::table('rg_seo_pages')->where('id', $id)->update([
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
                'published_at' => $isPublished && !$page->published_at ? now() : $page->published_at,
                'updated_at' => now(),
            ]);
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
