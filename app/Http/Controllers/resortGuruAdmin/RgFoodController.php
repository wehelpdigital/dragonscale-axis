<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

/**
 * Manages the Food Trip vertical — both food-category keywords and
 * their SEO pages. Kept as separate views but one controller because
 * they share data shape with the existing keyword + page modules.
 */
class RgFoodController extends Controller
{
    public function keywords(Request $request)
    {
        if (!Schema::hasTable('rg_keywords')) {
            return view('resortGuruAdmin.pending', ['title' => 'Food Keywords']);
        }

        if ($request->ajax()) {
            $rows = DB::table('rg_keywords as k')
                ->leftJoin(DB::raw('(SELECT keyword_id, COUNT(*) as pages_total, SUM(is_published) as pages_published FROM rg_seo_pages GROUP BY keyword_id) p'),
                    'p.keyword_id', '=', 'k.id')
                ->leftJoin(DB::raw('(SELECT keyword_id, COUNT(*) as listings_count FROM rg_restaurant_listings WHERE status="active" GROUP BY keyword_id) rl'),
                    'rl.keyword_id', '=', 'k.id')
                ->where('k.category', 'food')
                ->select([
                    'k.id', 'k.phrase', 'k.slug', 'k.search_volume_monthly', 'k.keyword_difficulty',
                    'k.cluster_tag', 'k.status', 'k.updated_at',
                    DB::raw('COALESCE(p.pages_published, 0) as pages_published'),
                    DB::raw('COALESCE(rl.listings_count, 0) as restaurant_listings'),
                ]);

            return DataTables::of($rows)
                ->addColumn('volume_fmt', fn($r) => number_format($r->search_volume_monthly) . ' / mo')
                ->addColumn('pages_summary', fn($r) => '<span class="badge bg-success">' . $r->pages_published . ' live</span>')
                ->addColumn('listings_count', fn($r) => $r->restaurant_listings > 0
                    ? '<span class="badge bg-info">' . $r->restaurant_listings . '</span>'
                    : '<span class="text-muted small">0</span>')
                ->editColumn('status', function ($r) {
                    $cls = $r->status === 'active' ? 'success' : 'secondary';
                    return '<span class="badge bg-' . $cls . '">' . ucfirst($r->status) . '</span>';
                })
                ->editColumn('updated_at', fn($r) => $r->updated_at
                    ? \Carbon\Carbon::parse($r->updated_at)->format('Y-m-d')
                    : '')
                ->addColumn('actions', function ($r) {
                    return '<a href="#" class="btn btn-sm btn-info disabled" title="Edit form is Phase 2"><i class="bx bx-edit"></i></a>';
                })
                ->rawColumns(['pages_summary', 'listings_count', 'status', 'actions'])
                ->make(true);
        }

        return view('resortGuruAdmin.food-keywords-index');
    }

    public function pages(Request $request)
    {
        if (!Schema::hasTable('rg_seo_pages')) {
            return view('resortGuruAdmin.pending', ['title' => 'Food Pages']);
        }

        if ($request->ajax()) {
            $rows = DB::table('rg_seo_pages as p')
                ->join('rg_keywords as k', 'k.id', '=', 'p.keyword_id')
                ->where('k.category', 'food')
                ->select([
                    'p.id', 'p.slug', 'p.title', 'p.is_published', 'p.pageviews_30d', 'p.updated_at',
                    'k.phrase', 'k.search_volume_monthly',
                ]);

            return DataTables::of($rows)
                ->addColumn('status_pill', function ($r) {
                    return $r->is_published
                        ? '<span class="badge bg-success">Live</span>'
                        : '<span class="badge bg-secondary">Draft</span>';
                })
                ->addColumn('volume_fmt', fn($r) => number_format($r->search_volume_monthly) . ' / mo')
                ->editColumn('updated_at', fn($r) => $r->updated_at
                    ? \Carbon\Carbon::parse($r->updated_at)->format('Y-m-d')
                    : '')
                ->addColumn('actions', function ($r) {
                    $editUrl = route('resort-guru-pages.edit', ['id' => $r->id]);
                    $viewUrl = \App\Support\RgFrontend::urlFor(ltrim($r->slug, '/'));
                    return '<a href="' . $editUrl . '" class="btn btn-sm btn-primary" title="Open block builder"><i class="bx bx-edit"></i> Edit</a> '
                        . '<a href="' . $viewUrl . '" target="_blank" class="btn btn-sm btn-light" title="Open on the public site"><i class="bx bx-link-external"></i></a>';
                })
                ->rawColumns(['status_pill', 'actions'])
                ->make(true);
        }

        return view('resortGuruAdmin.food-pages-index');
    }
}
