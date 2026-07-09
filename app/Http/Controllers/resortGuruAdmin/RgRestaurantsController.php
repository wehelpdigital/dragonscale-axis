<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

/**
 * Master list of restaurant businesses (whether owner-claimed or seeded
 * demo). Each row is the SOURCE OF TRUTH for everything that renders as
 * a restaurant card across keyword pages.
 */
class RgRestaurantsController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_restaurants')) {
            return view('resortGuruAdmin.pending', ['title' => 'Restaurants']);
        }

        if ($request->ajax()) {
            $rows = DB::table('rg_restaurants as r')
                ->leftJoin('rg_owners as o', 'o.id', '=', 'r.owner_id')
                ->leftJoin(DB::raw('(SELECT restaurant_id, COUNT(*) as listings_count, SUM(bid_gp) as total_bid FROM rg_restaurant_listings WHERE status="active" GROUP BY restaurant_id) l'),
                    'l.restaurant_id', '=', 'r.id')
                ->select([
                    'r.id', 'r.name', 'r.slug', 'r.cuisine', 'r.price_range',
                    'r.city', 'r.province', 'r.status', 'r.updated_at',
                    'o.name as owner_name',
                    DB::raw('COALESCE(l.listings_count, 0) as listings_count'),
                    DB::raw('COALESCE(l.total_bid, 0) as total_bid'),
                ]);

            return DataTables::of($rows)
                ->addColumn('owner', fn($r) => $r->owner_name ?: '<span class="text-muted">seeded demo</span>')
                ->addColumn('location', fn($r) => trim(($r->city ?: '') . ($r->province ? ', ' . $r->province : '')))
                ->addColumn('listings', fn($r) => $r->listings_count > 0
                    ? '<span class="badge bg-info">' . $r->listings_count . ' pages · ' . number_format($r->total_bid) . ' GP</span>'
                    : '<span class="text-muted small">no active listings</span>')
                ->addColumn('status_pill', function ($r) {
                    $cls = match ($r->status) {
                        'published'     => 'success',
                        'pending_review'=> 'warning',
                        'suspended'     => 'danger',
                        default         => 'secondary',
                    };
                    return '<span class="badge bg-' . $cls . '">' . ucfirst(str_replace('_', ' ', $r->status)) . '</span>';
                })
                ->editColumn('updated_at', fn($r) => $r->updated_at
                    ? \Carbon\Carbon::parse($r->updated_at)->format('Y-m-d')
                    : '')
                ->addColumn('actions', function ($r) {
                    return '<a href="#" class="btn btn-sm btn-info disabled" title="Edit form is Phase 2"><i class="bx bx-edit"></i></a>';
                })
                ->rawColumns(['owner', 'listings', 'status_pill', 'actions'])
                ->make(true);
        }

        return view('resortGuruAdmin.restaurants-index');
    }
}
