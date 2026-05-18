<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RgListingsController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_listings')) {
            return view('resortGuruAdmin.pending', ['title' => 'Listings & Bids']);
        }

        $status = $request->get('status', 'active');
        $keywordId = $request->get('keyword_id');

        $query = DB::table('rg_listings as l')
            ->leftJoin('rg_keywords as k', 'k.id', '=', 'l.keyword_id')
            ->leftJoin('rg_resorts as r', 'r.id', '=', 'l.resort_id')
            ->leftJoin('rg_owners as o', 'o.id', '=', 'l.owner_id')
            ->select(
                'l.id', 'l.base_gp', 'l.bid_gp', 'l.starts_at', 'l.expires_at',
                'l.last_bid_at', 'l.status', 'l.keyword_id',
                'k.phrase as keyword_phrase', 'k.slug as keyword_slug',
                'r.name as resort_name', 'r.slug as resort_slug',
                'o.name as owner_name', 'o.email as owner_email'
            )
            ->where('l.status', $status)
            ->orderByDesc('l.bid_gp')
            ->orderBy('l.last_bid_at');

        if ($keywordId) {
            $query->where('l.keyword_id', $keywordId);
        }

        $listings = $query->paginate(40)->withQueryString();
        $listings->each(function ($r) {
            $r->total_gp = (int) $r->base_gp + (int) $r->bid_gp;
            $r->days_left = $r->expires_at ? max(0, (int) \Carbon\Carbon::parse($r->expires_at)->diffInDays(now(), false) * -1) : null;
        });

        $counts = [
            'active' => DB::table('rg_listings')->where('status', 'active')->count(),
            'expired' => DB::table('rg_listings')->where('status', 'expired')->count(),
            'cancelled' => DB::table('rg_listings')->where('status', 'cancelled')->count(),
        ];

        $topKeywords = DB::table('rg_listings as l')
            ->leftJoin('rg_keywords as k', 'k.id', '=', 'l.keyword_id')
            ->select('k.id', 'k.phrase', DB::raw('COUNT(*) as listing_count'), DB::raw('MAX(l.bid_gp) as top_bid'))
            ->where('l.status', 'active')
            ->groupBy('k.id', 'k.phrase')
            ->orderByDesc('listing_count')
            ->limit(10)
            ->get();

        return view('resortGuruAdmin.listings', compact('listings', 'counts', 'status', 'topKeywords', 'keywordId'));
    }

    public function show(Request $request)
    {
        $id = (int) $request->input('id');
        $listing = DB::table('rg_listings as l')
            ->leftJoin('rg_keywords as k', 'k.id', '=', 'l.keyword_id')
            ->leftJoin('rg_resorts as r', 'r.id', '=', 'l.resort_id')
            ->leftJoin('rg_owners as o', 'o.id', '=', 'l.owner_id')
            ->select(
                'l.*',
                'k.phrase as keyword_phrase', 'k.slug as keyword_slug', 'k.search_volume_monthly',
                'r.name as resort_name', 'r.slug as resort_slug',
                'o.name as owner_name', 'o.email as owner_email'
            )
            ->where('l.id', $id)
            ->first();
        if (!$listing) abort(404);

        $bids = DB::table('rg_listing_bids')
            ->where('listing_id', $id)
            ->orderByDesc('id')
            ->get();

        return view('resortGuruAdmin.listings-show', compact('listing', 'bids'));
    }
}
