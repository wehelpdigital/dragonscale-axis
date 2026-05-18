<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Yajra\DataTables\Facades\DataTables;

class RgResortsController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_resorts')) {
            return view('resortGuruAdmin.pending', ['title' => 'Resorts']);
        }
        if ($request->ajax()) {
            $query = DB::table('rg_resorts as r')
                ->leftJoin('rg_owners as o', 'o.id', '=', 'r.owner_id')
                ->select('r.id', 'r.name', 'r.slug', 'r.city', 'r.province', 'r.status', 'r.updated_at', 'o.name as owner_name');
            return DataTables::of($query)
                ->editColumn('status', function ($r) {
                    $colors = ['draft' => 'secondary', 'pending_review' => 'warning', 'published' => 'success', 'suspended' => 'danger'];
                    $c = $colors[$r->status] ?? 'secondary';
                    return '<span class="badge bg-' . $c . '">' . ucwords(str_replace('_', ' ', $r->status)) . '</span>';
                })
                ->editColumn('updated_at', fn($r) => $r->updated_at ? \Carbon\Carbon::parse($r->updated_at)->format('Y-m-d H:i') : '')
                ->addColumn('actions', fn($r) => '<a href="' . route('resort-guru-resorts.show', ['id' => $r->id]) . '" class="btn btn-sm btn-primary">Review</a>')
                ->rawColumns(['status', 'actions'])
                ->make(true);
        }
        return view('resortGuruAdmin.resorts');
    }

    public function show(Request $request)
    {
        $id = (int) $request->input('id');
        $resort = DB::table('rg_resorts')->where('id', $id)->first();
        if (!$resort) abort(404);
        $owner = DB::table('rg_owners')->where('id', $resort->owner_id)->first();
        $media = Schema::hasTable('rg_resort_media')
            ? DB::table('rg_resort_media')->where('resort_id', $id)->orderBy('sort_order')->get()
            : collect();
        return view('resortGuruAdmin.resorts-show', compact('resort', 'owner', 'media'));
    }

    public function approve(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('rg_resorts')->where('id', $id)->update(['status' => 'published', 'approved_at' => now(), 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function reject(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('rg_resorts')->where('id', $id)->update(['status' => 'draft', 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }

    public function suspend(Request $request)
    {
        $id = (int) $request->input('id');
        DB::table('rg_resorts')->where('id', $id)->update(['status' => 'suspended', 'updated_at' => now()]);
        return response()->json(['ok' => true]);
    }
}
