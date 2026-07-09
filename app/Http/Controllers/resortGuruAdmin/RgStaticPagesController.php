<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RgStaticPagesController extends Controller
{
    /**
     * Slug → public URL map for pages whose URL is set by a frontend
     * route → controller binding rather than the slug itself. Editing
     * the slug doesn't change the URL on these pages; the static_page
     * row is the editor for content that the controller reads at
     * render time (e.g. DestinationsController fetches the row by
     * slug='destinations' to render the block stream).
     */
    private const SPECIAL_SLUG_URLS = [
        'home' => '/',
        'about' => '/about-tourist-guide-ph',
        'contact' => '/contact',
        'terms' => '/terms',
        'privacy' => '/privacy',
        'destinations' => '/tourist-spots-destinations-philippines',
        // Shared template rendered on every /tourist-spots-destinations-philippines/{region} page.
        // Live edit opens a representative cluster so the admin sees a real render.
        'destination-cluster' => '/tourist-spots-destinations-philippines/north-luzon',
        'food-trip' => '/food-trip',
        'blog-index' => '/blog',
        'register-page' => '/register',
        'login-page' => '/login',
        'contact-page' => '/contact',
    ];

    /**
     * Legacy hub-* duplicate rows superseded by the real hub pages
     * (foods / activities / buys / cultures / food-trip / destinations).
     * They still hold old content but no code reads them, so they are
     * hidden from the Site Pages listing to avoid confusion.
     *
     * NOTE: dynamic templates (e.g. destination-cluster, which renders on
     * every /tourist-spots-destinations-philippines/{region} URL) are NOT
     * hidden — they appear under the "Templates" group so admins can build
     * every region page from one place.
     */
    private const HIDDEN_SLUGS = [
        'hub-activities', 'hub-buys', 'hub-cultures',
        'hub-destinations', 'hub-food-trip', 'hub-foods',
    ];

    public function index()
    {
        if (!Schema::hasTable('rg_static_pages')) {
            return view('resortGuruAdmin.pending', ['title' => 'Static Pages']);
        }
        $pages = DB::table('rg_static_pages')
            ->whereNotIn('slug', self::HIDDEN_SLUGS)
            ->orderBy('slug')
            ->get();
        return view('resortGuruAdmin.static-pages', compact('pages'));
    }

    public function edit(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_static_pages')->where('id', $id)->first();
        if (!$page) abort(404);
        return view('resortGuruAdmin.static-pages-edit', compact('page'));
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_static_pages')->where('id', $id)->first();
        if (!$page) abort(404);

        $data = $request->validate([
            'title' => 'required|string|max:255',
            'slug' => 'required|string|max:191|regex:/^[a-z0-9-]+$/',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string|max:500',
            'is_published' => 'nullable',
        ]);

        // Slug uniqueness (excluding the row being updated).
        $slugExists = DB::table('rg_static_pages')
            ->where('slug', $data['slug'])
            ->where('id', '!=', $id)
            ->exists();
        if ($slugExists) {
            return back()->withInput()->with('error', 'A page with slug "' . $data['slug'] . '" already exists.');
        }

        $data['is_published'] = $request->has('is_published') ? 1 : 0;
        $data['updated_at'] = now();

        DB::table('rg_static_pages')->where('id', $id)->update($data);

        return redirect()
            ->route('resort-guru-static.edit', ['id' => $id])
            ->with('success', 'Page saved.');
    }

    /**
     * SEO score endpoint for a static page. Reads the page row +
     * its attached blocks (rg_content_blocks owner_type=static_page)
     * and returns a 0-100 score with itemized checks. Drives the
     * "SEO Score" sidebar on the static-pages-edit screen.
     *
     * Mirrors the SEO scorer pattern from RgSeoPagesController but
     * targets the simpler shape of rg_static_pages (no h1 column,
     * no body_html, content lives in blocks).
     */
    public function seoAnalyze(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_static_pages')->where('id', $id)->first();
        if (!$page) return response()->json(['ok' => false]);

        $blocks = DB::table('rg_content_blocks')
            ->where('owner_type', 'static_page')
            ->where('owner_id', $id)
            ->orderBy('sort_order')
            ->get();

        $checks = [];

        // 1. Title length (20-60 chars)
        $titleLen = mb_strlen((string) $page->title);
        $checks[] = [
            'label' => 'Page title length',
            'status' => $titleLen >= 20 && $titleLen <= 60 ? 'pass' : ($titleLen >= 10 ? 'warn' : 'fail'),
            'message' => "$titleLen chars (ideal 20-60).",
        ];

        // 2. Meta title length (50-60 chars)
        $metaTitleLen = mb_strlen((string) $page->meta_title);
        $checks[] = [
            'label' => 'Meta title length',
            'status' => $metaTitleLen >= 50 && $metaTitleLen <= 60 ? 'pass' : ($metaTitleLen >= 30 && $metaTitleLen <= 70 ? 'warn' : 'fail'),
            'message' => $metaTitleLen === 0 ? 'Missing — uses page title as fallback.' : "$metaTitleLen chars (ideal 50-60).",
        ];

        // 3. Meta description length (140-160 chars)
        $metaDescLen = mb_strlen((string) $page->meta_description);
        $checks[] = [
            'label' => 'Meta description length',
            'status' => $metaDescLen >= 140 && $metaDescLen <= 160 ? 'pass' : ($metaDescLen >= 80 && $metaDescLen <= 200 ? 'warn' : 'fail'),
            'message' => $metaDescLen === 0 ? 'Missing — search engines may auto-generate one.' : "$metaDescLen chars (ideal 140-160).",
        ];

        // 4. Slug format (lowercase letters / digits / hyphens, no double hyphens or trailing hyphen)
        $slugOk = preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $page->slug) === 1;
        $checks[] = [
            'label' => 'Slug format',
            'status' => $slugOk ? 'pass' : 'warn',
            'message' => $slugOk ? "Clean slug: /$page->slug" : "Slug has unusual format.",
        ];

        // 5. Published?
        $checks[] = [
            'label' => 'Published',
            'status' => $page->is_published ? 'pass' : 'warn',
            'message' => $page->is_published ? 'Page is live.' : 'Draft — not visible to visitors.',
        ];

        // 6. Block count
        $blockCount = $blocks->count();
        $checks[] = [
            'label' => 'Content blocks',
            'status' => $blockCount >= 3 ? 'pass' : ($blockCount >= 1 ? 'warn' : 'fail'),
            'message' => "$blockCount block" . ($blockCount === 1 ? '' : 's') . " on this page.",
        ];

        // 7. At least one H1-shaped block (heading level=h1, hub_hero, dest_hero_search, or any *_hero block with a title)
        $hasH1 = false;
        $totalText = '';
        foreach ($blocks as $b) {
            $payload = json_decode($b->payload_json ?: '', true) ?: [];
            $type = $b->block_type;
            if ($type === 'heading' && (string) ($payload['level'] ?? 'h2') === 'h1') $hasH1 = true;
            if (in_array($type, ['hub_hero', 'dest_hero_search'], true) && !empty($payload['title'])) $hasH1 = true;
            // Build a textContent estimate for word count / vocab checks.
            foreach (['title', 'heading', 'body', 'text', 'subhead', 'intro', 'eyebrow', 'placeholder'] as $field) {
                if (!empty($payload[$field])) $totalText .= ' ' . strip_tags((string) $payload[$field]);
            }
            if (!empty($payload['paragraphs'])) {
                $paras = is_array($payload['paragraphs']) ? $payload['paragraphs'] : preg_split('/\n\n+/', (string) $payload['paragraphs']);
                foreach ($paras as $p) $totalText .= ' ' . strip_tags((string) $p);
            }
        }
        $checks[] = [
            'label' => 'H1 heading present',
            'status' => $hasH1 ? 'pass' : 'fail',
            'message' => $hasH1 ? 'Found an H1-level block.' : 'No H1 in the block stream. Add a heading (level=h1) or a hero block.',
        ];

        // 8. Word count from block text
        $wordCount = str_word_count(trim(preg_replace('/\s+/', ' ', $totalText)));
        $checks[] = [
            'label' => 'Word count (block text)',
            'status' => $wordCount >= 300 ? 'pass' : ($wordCount >= 100 ? 'warn' : 'fail'),
            'message' => "$wordCount words across all blocks (target 300+).",
        ];

        // 9. Banned vocabulary (Resort Guru content rules)
        $banned = ['delve','tapestry','vibrant','bustling','nestled','embark','breathtaking','plethora','myriad','robust','seamless','leverage','hidden gem','must-visit','must-try'];
        $bannedHits = [];
        $lower = mb_strtolower($totalText);
        foreach ($banned as $b) {
            if (str_contains($lower, $b)) $bannedHits[] = $b;
        }
        $checks[] = [
            'label' => 'Banned AI vocabulary',
            'status' => empty($bannedHits) ? 'pass' : 'warn',
            'message' => empty($bannedHits) ? 'Clean.' : 'Found: ' . implode(', ', $bannedHits),
        ];

        // 10. Em-dash check (Resort Guru rule: no em-dashes)
        $hasEmDash = str_contains($totalText, '—');
        $checks[] = [
            'label' => 'Em-dash check',
            'status' => $hasEmDash ? 'warn' : 'pass',
            'message' => $hasEmDash ? 'Found em-dash (—). Resort Guru rules: replace with comma or period.' : 'Clean.',
        ];

        // 11-12. Image alt + title coverage across every block, using the
        // shared analyzer so the homepage ranks images exactly like the
        // SEO-page + blog analyzers do.
        foreach (app(\App\Services\SeoAnalyzer::class)->imageChecks($blocks) as $ic) {
            $checks[] = ['label' => $ic['label'], 'status' => $ic['status'], 'message' => $ic['message']];
        }

        // Compute score: 10 points per pass, 5 per warn, 0 per fail,
        // normalized to a 0-100 scale (so adding checks never overflows).
        $points = 0;
        foreach ($checks as $c) {
            $points += $c['status'] === 'pass' ? 10 : ($c['status'] === 'warn' ? 5 : 0);
        }
        $maxPoints = count($checks) * 10;
        $score = $maxPoints > 0 ? (int) round(($points / $maxPoints) * 100) : 0;
        $label = $score >= 80 ? 'Excellent' : ($score >= 60 ? 'Needs work' : 'Poor');

        return response()->json([
            'ok' => true,
            'score' => $score,
            'label' => $label,
            'checks' => $checks,
        ]);
    }

    /**
     * Live Editor view for a static page. Mints an HMAC-signed _lt
     * token against the page slug, then opens the public URL inside
     * an iframe. The shared Live Editor JS surfaces edit toolbars on
     * each block.
     *
     * Special-slug pages (hub_*, destinations, food-trip, etc.) use
     * the controller-mapped public URL since the slug isn't the URL
     * path. Other static pages use their slug as the path.
     */
    public function liveEdit(Request $request)
    {
        $id = (int) $request->input('id');
        $page = DB::table('rg_static_pages')->where('id', $id)->first();
        if (!$page) abort(404);

        $secret = (string) env('LIVE_EDIT_SECRET');
        if ($secret === '') {
            return redirect()
                ->route('resort-guru-static.edit', ['id' => $id])
                ->with('error', 'LIVE_EDIT_SECRET is not set in the mother app .env.');
        }

        $publicPath = self::SPECIAL_SLUG_URLS[$page->slug] ?? ('/' . ltrim($page->slug, '/'));

        $expiry = time() + 3600;
        $token = hash_hmac('sha256', $page->slug . '|' . $expiry, $secret);
        $signedToken = $token . '.' . $expiry;

        $frontendBase = \App\Support\RgFrontend::url();
        $previewUrl = $frontendBase . $publicPath . '?_lt=' . urlencode($signedToken);

        return view('resortGuruAdmin.static-pages-live-edit', compact('page', 'previewUrl'));
    }
}
