<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class RgKeywordsController extends Controller
{
    /**
     * Known keyword types, in display order. Any other category value found
     * in rg_keywords is appended automatically, so adding a new type needs
     * no code change here.
     */
    private const KNOWN_CATEGORIES = [
        'resort' => 'Resorts',
        'food' => 'Food',
        'destination' => 'Destinations',
    ];

    private const CATEGORY_BADGES = [
        'resort' => 'primary',
        'food' => 'warning',
        'destination' => 'info',
    ];

    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_keywords')) {
            return view('resortGuruAdmin.pending', ['title' => 'Keywords']);
        }
        if ($request->ajax()) {
            if ($request->input('view') === 'pages') {
                return $this->pagesData($request);
            }
            $category = trim((string) $request->input('category', 'all'));

            $query = DB::table('rg_keywords as k')
                ->leftJoin(DB::raw('(SELECT keyword_id, COUNT(*) as pages_total, SUM(is_published) as pages_published FROM rg_seo_pages GROUP BY keyword_id) p'), 'p.keyword_id', '=', 'k.id');

            $listingParts = [];
            if (Schema::hasTable('rg_listings')) {
                $query->leftJoin(DB::raw("(SELECT keyword_id, COUNT(*) as cnt FROM rg_listings WHERE status = 'active' GROUP BY keyword_id) lres"), 'lres.keyword_id', '=', 'k.id');
                $listingParts[] = 'COALESCE(lres.cnt, 0)';
            }
            if (Schema::hasTable('rg_restaurant_listings')) {
                $query->leftJoin(DB::raw("(SELECT keyword_id, COUNT(*) as cnt FROM rg_restaurant_listings WHERE status = 'active' GROUP BY keyword_id) lfood"), 'lfood.keyword_id', '=', 'k.id');
                $listingParts[] = 'COALESCE(lfood.cnt, 0)';
            }
            $listingsExpr = $listingParts ? implode(' + ', $listingParts) : '0';

            $query->select([
                'k.id', 'k.phrase', 'k.slug', 'k.category', 'k.cluster_tag',
                'k.search_volume_monthly', 'k.keyword_difficulty', 'k.status', 'k.updated_at',
                DB::raw('COALESCE(p.pages_total, 0) as pages_total'),
                DB::raw('COALESCE(p.pages_published, 0) as pages_published'),
                DB::raw($listingsExpr . ' as listings_count'),
            ]);

            if ($category !== '' && $category !== 'all') {
                $query->where('k.category', $category);
            }

            return DataTables::of($query)
                ->editColumn('phrase', fn($r) => '<div class="fw-semibold">' . e($r->phrase) . '</div><small class="text-muted">/' . e($r->slug) . '</small>')
                ->filterColumn('phrase', function ($q, $kw) {
                    $q->where(function ($qq) use ($kw) {
                        $qq->where('k.phrase', 'like', '%' . $kw . '%')
                           ->orWhere('k.slug', 'like', '%' . $kw . '%');
                    });
                })
                ->editColumn('category', fn($r) => $this->categoryBadge($r->category))
                ->editColumn('cluster_tag', fn($r) => $r->cluster_tag
                    ? e(Str::of($r->cluster_tag)->replace('-', ' ')->title())
                    : '<span class="text-muted small">&mdash;</span>')
                ->editColumn('updated_at', fn($r) => $r->updated_at ? \Carbon\Carbon::parse($r->updated_at)->format('Y-m-d H:i') : '')
                ->editColumn('status', function ($r) {
                    $colors = ['active' => 'success', 'draft' => 'secondary', 'archived' => 'dark'];
                    $c = $colors[$r->status] ?? 'secondary';
                    return '<span class="badge bg-' . $c . '">' . ucfirst($r->status) . '</span>';
                })
                ->addColumn('pages_summary', function ($r) {
                    $total = (int) $r->pages_total;
                    $live = (int) $r->pages_published;
                    if ($total === 0) return '<span class="text-muted small">0 pages</span>';
                    $color = $live > 0 ? 'success' : 'secondary';
                    return '<span class="badge bg-' . $color . '">' . $live . ' / ' . $total . '</span><br><small class="text-muted">live / total</small>';
                })
                ->addColumn('listings_summary', fn($r) => (int) $r->listings_count > 0
                    ? '<span class="badge bg-info">' . (int) $r->listings_count . '</span>'
                    : '<span class="text-muted small">0</span>')
                ->addColumn('actions', function ($r) {
                    $managePagesUrl = route('resort-guru-keywords-pages.index', ['id' => $r->id]);
                    $editKwUrl = route('resort-guru-keywords.edit', ['id' => $r->id]);
                    return '<a href="' . $managePagesUrl . '" class="btn btn-sm btn-success me-1" title="Manage pages for this keyword"><i class="bx bx-file"></i> Pages</a>'
                        . '<a href="' . $editKwUrl . '" class="btn btn-sm btn-primary me-1" title="Edit keyword"><i class="bx bx-cog"></i></a>'
                        . '<button class="btn btn-sm btn-danger" onclick="confirmDelete(' . $r->id . ')" title="Delete"><i class="bx bx-trash"></i></button>';
                })
                ->rawColumns(['phrase', 'category', 'cluster_tag', 'actions', 'status', 'pages_summary', 'listings_summary'])
                ->with('tabCounts', array_map(fn($t) => $t['count'], $this->categoryTabs()))
                ->make(true);
        }

        $tabs = $this->categoryTabs();
        $activeCategory = (string) $request->query('category', 'all');
        if ($activeCategory !== 'all' && !array_key_exists($activeCategory, $tabs)) {
            $activeCategory = 'all';
        }
        $activeView = $request->query('view') === 'pages' ? 'pages' : 'keywords';
        return view('resortGuruAdmin.keywords', compact('tabs', 'activeCategory', 'activeView'));
    }

    /**
     * DataTable for the "SEO Pages" view of the Keywords screen: every
     * rg_seo_pages row with its keyword, filtered by the same type tabs.
     */
    private function pagesData(Request $request)
    {
        $category = trim((string) $request->input('category', 'all'));

        $query = DB::table('rg_seo_pages as p')
            ->join('rg_keywords as k', 'k.id', '=', 'p.keyword_id')
            ->select([
                'p.id', 'p.slug', 'p.title', 'p.is_published', 'p.pageviews_30d', 'p.updated_at',
                'k.phrase', 'k.category', 'k.search_volume_monthly',
            ]);

        if ($category !== '' && $category !== 'all') {
            $query->where('k.category', $category);
        }

        return DataTables::of($query)
            ->editColumn('title', function ($r) {
                $slug = ltrim((string) $r->slug, '/');
                $sub = $slug !== '' ? '/' . e($slug) : '(no slug yet)';
                return '<div class="fw-semibold">' . e($r->title) . '</div><small class="text-muted">' . $sub . '</small>';
            })
            ->filterColumn('title', function ($q, $kw) {
                $q->where(function ($qq) use ($kw) {
                    $qq->where('p.title', 'like', '%' . $kw . '%')
                       ->orWhere('p.slug', 'like', '%' . $kw . '%');
                });
            })
            ->editColumn('category', fn($r) => $this->categoryBadge($r->category))
            ->editColumn('search_volume_monthly', fn($r) => number_format((int) $r->search_volume_monthly))
            ->editColumn('updated_at', fn($r) => $r->updated_at ? \Carbon\Carbon::parse($r->updated_at)->format('Y-m-d') : '')
            ->addColumn('status_pill', fn($r) => $r->is_published
                ? '<span class="badge bg-success">Live</span>'
                : '<span class="badge bg-secondary">Draft</span>')
            ->addColumn('actions', function ($r) {
                $editUrl = route('resort-guru-pages.edit', ['id' => $r->id]);
                $html = '<a href="' . $editUrl . '" class="btn btn-sm btn-primary me-1" title="Open block builder"><i class="bx bx-edit"></i> Edit</a>';
                $slug = ltrim((string) $r->slug, '/');
                if ($slug !== '') {
                    $viewUrl = \App\Support\RgFrontend::urlFor($slug);
                    $html .= '<a href="' . e($viewUrl) . '" target="_blank" class="btn btn-sm btn-light" title="Open on the public site"><i class="bx bx-link-external"></i></a>';
                }
                return $html;
            })
            ->rawColumns(['title', 'category', 'status_pill', 'actions'])
            ->with('tabCounts', $this->pagesTabCounts())
            ->make(true);
    }

    /**
     * Type-tab counts for the SEO Pages view: SEO page rows per keyword
     * category (a keyword can have more than one page).
     */
    private function pagesTabCounts(): array
    {
        $counts = DB::table('rg_seo_pages as p')
            ->join('rg_keywords as k', 'k.id', '=', 'p.keyword_id')
            ->select('k.category', DB::raw('COUNT(*) as c'))
            ->groupBy('k.category')
            ->pluck('c', 'category')
            ->map(fn($c) => (int) $c)
            ->all();
        return ['all' => array_sum($counts)] + $counts;
    }

    private function categoryBadge(?string $category): string
    {
        $cat = $category ?: 'resort';
        $c = self::CATEGORY_BADGES[$cat] ?? 'secondary';
        return '<span class="badge bg-' . $c . '">' . e(self::categoryLabel($cat)) . '</span>';
    }

    public function create(Request $request)
    {
        $categories = $this->categoryOptions();
        $selectedCategory = (string) $request->query('category', 'resort');
        if (!array_key_exists($selectedCategory, $categories)) {
            $selectedCategory = 'resort';
        }
        return view('resortGuruAdmin.keywords-add', compact('categories', 'selectedCategory'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'phrase' => 'required|string|max:255',
            'category' => 'required|string|alpha_dash|max:32',
            'search_volume_monthly' => 'nullable|integer',
            'keyword_difficulty' => 'nullable|integer',
            'cluster_tag' => 'nullable|string|max:100',
            'intent' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,draft,archived',
        ]);

        $data['slug'] = $this->uniqueSlug($data['phrase']);
        $data['created_at'] = now();
        $data['updated_at'] = now();

        DB::transaction(function () use ($data) {
            $id = DB::table('rg_keywords')->insertGetId($data);
            DB::table('rg_seo_pages')->insert([
                'keyword_id' => $id,
                'title' => $data['phrase'],
                'meta_title' => $data['phrase'],
                'meta_description' => '',
                'h1' => $data['phrase'],
                'is_published' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        });

        return redirect()->route('resort-guru-keywords.index', ['category' => $data['category']])
            ->with('success', 'Keyword created. A linked SEO page was auto-created.');
    }

    public function edit(Request $request)
    {
        $id = (int) $request->input('id');
        $keyword = DB::table('rg_keywords')->where('id', $id)->first();
        if (!$keyword) abort(404);
        $categories = $this->categoryOptions();
        return view('resortGuruAdmin.keywords-edit', compact('keyword', 'categories'));
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $data = $request->validate([
            'phrase' => 'required|string|max:255',
            'category' => 'required|string|alpha_dash|max:32',
            'search_volume_monthly' => 'nullable|integer',
            'keyword_difficulty' => 'nullable|integer',
            'cluster_tag' => 'nullable|string|max:100',
            'intent' => 'nullable|string|max:100',
            'notes' => 'nullable|string',
            'status' => 'required|in:active,draft,archived',
        ]);
        $data['updated_at'] = now();
        DB::table('rg_keywords')->where('id', $id)->update($data);
        return redirect()->route('resort-guru-keywords.index', ['category' => $data['category']])
            ->with('success', 'Keyword updated.');
    }

    public function destroy(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('rg_keywords')->where('id', $id)->delete();
        DB::table('rg_seo_pages')->where('keyword_id', $id)->delete();
        return response()->json(['ok' => true]);
    }

    public function import(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:csv,txt|max:10240',
            'default_category' => 'nullable|string|alpha_dash|max:32',
        ]);
        // Rows without a category column fall back to the tab the admin
        // imported from ('all' falls back to resort).
        $activeTab = (string) $request->input('default_category', 'all');
        $fallbackCategory = ($activeTab === '' || $activeTab === 'all') ? 'resort' : $activeTab;
        $path = $request->file('file')->getRealPath();
        $h = fopen($path, 'r');
        if (!$h) {
            return back()->with('error', 'Could not read CSV.');
        }
        $headers = fgetcsv($h);
        if (!$headers) {
            fclose($h);
            return back()->with('error', 'CSV is empty.');
        }
        $headers = array_map(fn($s) => strtolower(trim($s)), $headers);
        $rowsCreated = 0;
        DB::transaction(function () use ($h, $headers, $fallbackCategory, &$rowsCreated) {
            while (($row = fgetcsv($h)) !== false) {
                $assoc = @array_combine($headers, $row) ?: [];
                $phrase = trim($assoc['phrase'] ?? $assoc['keyword'] ?? $assoc['keyphrase'] ?? '');
                if ($phrase === '') continue;
                // Clamp to the column width (varchar 32) — strict mode would abort the whole import.
                $category = mb_substr(Str::slug(trim($assoc['category'] ?? $assoc['type'] ?? '')), 0, 32) ?: $fallbackCategory;
                $slug = $this->uniqueSlug($phrase);
                $id = DB::table('rg_keywords')->insertGetId([
                    'phrase' => $phrase,
                    'slug' => $slug,
                    'category' => $category,
                    'search_volume_monthly' => (int) ($assoc['volume'] ?? $assoc['search_volume_monthly'] ?? 0),
                    'keyword_difficulty' => (int) ($assoc['kd'] ?? $assoc['keyword_difficulty'] ?? 0),
                    'cluster_tag' => $assoc['cluster_tag'] ?? null,
                    'intent' => $assoc['intent'] ?? null,
                    'status' => 'active',
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                DB::table('rg_seo_pages')->insert([
                    'keyword_id' => $id,
                    'title' => $phrase,
                    'meta_title' => $phrase,
                    'meta_description' => '',
                    'h1' => $phrase,
                    'is_published' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
                $rowsCreated++;
            }
        });
        fclose($h);
        $params = ($activeTab !== '' && $activeTab !== 'all') ? ['category' => $activeTab] : [];
        return redirect()->route('resort-guru-keywords.index', $params)->with('success', "Imported $rowsCreated keywords.");
    }

    public static function categoryLabel(string $category): string
    {
        return self::KNOWN_CATEGORIES[$category]
            ?? Str::of($category)->replace(['-', '_'], ' ')->title()->toString();
    }

    /**
     * Tabs for the index screen: All + every category present in the table,
     * known types first, each with its row count.
     */
    private function categoryTabs(): array
    {
        $counts = DB::table('rg_keywords')
            ->select('category', DB::raw('COUNT(*) as c'))
            ->groupBy('category')
            ->pluck('c', 'category')
            ->all();

        $tabs = ['all' => ['label' => 'All', 'count' => array_sum($counts)]];
        foreach (array_keys(self::KNOWN_CATEGORIES) as $cat) {
            if (isset($counts[$cat])) {
                $tabs[$cat] = ['label' => self::categoryLabel($cat), 'count' => (int) $counts[$cat]];
            }
        }
        foreach ($counts as $cat => $c) {
            $cat = (string) $cat;
            if ($cat !== '' && !isset($tabs[$cat])) {
                $tabs[$cat] = ['label' => self::categoryLabel($cat), 'count' => (int) $c];
            }
        }
        return $tabs;
    }

    /**
     * Options for the add/edit Type dropdown: known types plus anything
     * already used in the table.
     */
    private function categoryOptions(): array
    {
        $options = self::KNOWN_CATEGORIES;
        $extra = DB::table('rg_keywords')->whereNotNull('category')->distinct()->pluck('category');
        foreach ($extra as $cat) {
            $cat = (string) $cat;
            if ($cat !== '' && !isset($options[$cat])) {
                $options[$cat] = self::categoryLabel($cat);
            }
        }
        return $options;
    }

    private function uniqueSlug(string $phrase): string
    {
        $slug = Str::slug($phrase);
        $base = $slug;
        $i = 1;
        while (DB::table('rg_keywords')->where('slug', $slug)->exists()) {
            $slug = $base . '-' . (++$i);
        }
        return $slug;
    }
}
