<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RgSchemasController extends Controller
{
    /**
     * Read-only view of what JSON-LD schemas the frontend auto-emits on each
     * SEO page, plus a per-page editor for custom schema_json overrides that
     * the controller merges into the JSON-LD output. The auto-emitted schemas
     * adapt to dynamic listings: ItemList rebuilds from current rg_listings on
     * every request, AggregateRating rebuilds from current rg_destination_reviews,
     * so admins do not need to manually keep them in sync.
     */
    public function index(Request $request)
    {
        $autoEmitted = [
            [
                'type' => 'BreadcrumbList',
                'scope' => 'Every keyword page',
                'source' => 'Built from Home > Destinations > [Cluster] > [Page title]',
                'dynamic' => 'Static per page',
            ],
            [
                'type' => 'Article',
                'scope' => 'Every keyword page',
                'source' => 'Built from page title, h1, meta_description, author, datePublished/dateModified',
                'dynamic' => 'Re-emitted on each request with current dateModified',
            ],
            [
                'type' => 'FAQPage',
                'scope' => 'Keyword pages with at least one FAQ block',
                'source' => 'Pulled from rg_content_blocks where block_type = faq',
                'dynamic' => 'Adapts as admin edits FAQ block items',
            ],
            [
                'type' => 'ItemList',
                'scope' => 'Keyword pages with at least one active listing',
                'source' => 'Built live from rg_listings where keyword_id = page.keyword_id and status = active',
                'dynamic' => 'YES — every new bid or listing change reflects on next page load',
            ],
            [
                'type' => 'TouristAttraction + AggregateRating + Review',
                'scope' => 'Keyword pages with at least one published review',
                'source' => 'Built live from rg_destination_reviews where keyword_id matches or is global',
                'dynamic' => 'YES — every new review added in admin appears on next page load',
            ],
            [
                'type' => 'Organization + WebSite',
                'scope' => 'Site-wide (homepage)',
                'source' => 'Built from rg_settings (site_name, social links)',
                'dynamic' => 'Adapts when settings change',
            ],
            [
                'type' => 'LodgingBusiness',
                'scope' => 'Resort detail pages (/listing/{slug})',
                'source' => 'Built from rg_resorts row + amenities_json',
                'dynamic' => 'Adapts when resort owner edits their listing',
            ],
            [
                'type' => 'BlogPosting',
                'scope' => 'Blog post pages',
                'source' => 'Built from rg_blog_posts row + author',
                'dynamic' => 'Adapts on edit',
            ],
        ];

        $pagesWithCustomSchema = Schema::hasTable('rg_seo_pages')
            ? DB::table('rg_seo_pages')->whereNotNull('schema_json')->where('schema_json', '<>', '')
                ->orderBy('slug')->get(['id', 'slug', 'title', 'schema_json'])
            : collect();

        return view('resortGuruAdmin.schemas-index', compact('autoEmitted', 'pagesWithCustomSchema'));
    }

    public function editForPage(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_seo_pages')->where('id', $id)->first();
        if (!$page) abort(404);
        return view('resortGuruAdmin.schemas-edit', compact('page'));
    }

    public function updateForPage(Request $request)
    {
        $id = (int) $request->input('id');
        $json = (string) $request->input('schema_json', '');
        $json = trim($json);
        if ($json !== '') {
            $decoded = json_decode($json, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                return back()->withErrors(['schema_json' => 'Invalid JSON: ' . json_last_error_msg()])->withInput();
            }
        }
        DB::table('rg_seo_pages')->where('id', $id)->update([
            'schema_json' => $json !== '' ? $json : null,
            'updated_at' => now(),
        ]);
        return redirect()->route('resort-guru-keywords.index', ['view' => 'pages'])->with('success', 'Custom schema saved.');
    }

    /**
     * Live preview: render the JSON-LD that would be emitted for a given page
     * RIGHT NOW, including all dynamic data (listings, reviews, etc).
     */
    public function preview(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_seo_pages')->where('id', $id)->first();
        if (!$page) abort(404);
        $url = \App\Support\RgFrontend::urlFor($page->slug);
        try {
            $response = \Illuminate\Support\Facades\Http::timeout(20)->get($url);
            $html = $response->body();
            preg_match_all('#<script type="application/ld\+json">([\s\S]*?)</script>#i', $html, $m);
            $blocks = [];
            foreach ($m[1] ?? [] as $raw) {
                $decoded = json_decode($raw, true);
                $blocks[] = $decoded
                    ? json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE)
                    : $raw;
            }
            return view('resortGuruAdmin.schemas-preview', compact('page', 'blocks', 'url'));
        } catch (\Throwable $e) {
            return view('resortGuruAdmin.schemas-preview', [
                'page' => $page,
                'blocks' => [],
                'url' => $url,
                'error' => 'Could not reach frontend: ' . $e->getMessage(),
            ]);
        }
    }
}
