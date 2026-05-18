<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RgSettingsController extends Controller
{
    public function index()
    {
        if (!Schema::hasTable('rg_settings')) {
            return view('resortGuruAdmin.pending', ['title' => 'Settings']);
        }
        $rows = DB::table('rg_settings')->orderBy('group')->orderBy('key')->get();
        $grouped = $rows->groupBy('group');
        return view('resortGuruAdmin.settings', compact('grouped'));
    }

    public function update(Request $request)
    {
        $inputs = $request->except(['_token', '_method']);
        foreach ($inputs as $key => $value) {
            DB::table('rg_settings')->where('key', $key)->update([
                'value' => is_array($value) ? json_encode($value) : (string) $value,
                'updated_at' => now(),
            ]);
        }
        return redirect()->route('resort-guru-settings.index')->with('success', 'Settings saved.');
    }

    public function testGuides()
    {
        $dummyClients = Schema::hasTable('rg_owners')
            ? DB::table('rg_owners')->where('email', 'LIKE', '%@dummy.test')->orderBy('id')->get()
            : collect();

        $sampleProperties = Schema::hasTable('rg_resorts')
            ? DB::table('rg_resorts')->where('status', 'published')->orderBy('id')->limit(4)->get()
            : collect();

        // Keywords with active listings — these are the ones to click on
        $keywordsWithBids = collect();
        if (Schema::hasTable('rg_listings') && Schema::hasTable('rg_keywords')) {
            $rows = DB::table('rg_listings as l')
                ->join('rg_keywords as k', 'k.id', '=', 'l.keyword_id')
                ->join('rg_seo_pages as p', 'p.keyword_id', '=', 'k.id')
                ->where('l.status', 'active')
                ->where('p.is_published', 1)
                ->select(
                    'k.id as keyword_id',
                    'k.phrase',
                    'k.search_volume_monthly',
                    'p.slug as page_slug',
                    DB::raw('COUNT(l.id) as bid_count'),
                    DB::raw('MAX(l.bid_gp) as top_bid'),
                    DB::raw('SUM(l.base_gp + l.bid_gp) as total_gp_committed')
                )
                ->groupBy('k.id', 'k.phrase', 'k.search_volume_monthly', 'p.slug')
                ->orderByDesc('total_gp_committed')
                ->limit(10)
                ->get();
            $keywordsWithBids = $rows;
        }

        $stats = [
            'keywords' => Schema::hasTable('rg_keywords') ? DB::table('rg_keywords')->count() : 0,
            'pages_total' => Schema::hasTable('rg_seo_pages') ? DB::table('rg_seo_pages')->count() : 0,
            'pages_published' => Schema::hasTable('rg_seo_pages') ? DB::table('rg_seo_pages')->where('is_published', 1)->count() : 0,
            'clients' => Schema::hasTable('rg_owners') ? DB::table('rg_owners')->count() : 0,
            'properties' => Schema::hasTable('rg_resorts') ? DB::table('rg_resorts')->count() : 0,
            'properties_published' => Schema::hasTable('rg_resorts') ? DB::table('rg_resorts')->where('status', 'published')->count() : 0,
            'listings_active' => Schema::hasTable('rg_listings') ? DB::table('rg_listings')->where('status', 'active')->count() : 0,
            'bid_events' => Schema::hasTable('rg_listing_bids') ? DB::table('rg_listing_bids')->count() : 0,
            'gp_topups_pending' => Schema::hasTable('rg_gp_topups') ? DB::table('rg_gp_topups')->where('status', 'pending')->count() : 0,
        ];

        $frontendUrl = rtrim(config('app.frontend_url', 'http://localhost:8001'), '/');

        return view('resortGuruAdmin.test-guides', compact(
            'dummyClients', 'sampleProperties', 'keywordsWithBids', 'stats', 'frontendUrl'
        ));
    }
}
