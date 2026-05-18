<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RgGcashApprovalsController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_gp_topups')) {
            return view('resortGuruAdmin.pending', ['title' => 'GCash Approvals']);
        }
        $status = $request->get('status', 'pending');
        $topups = DB::table('rg_gp_topups as t')
            ->leftJoin('rg_owners as o', 'o.id', '=', 't.owner_id')
            ->select('t.*', 'o.name as owner_name', 'o.email as owner_email')
            ->where('t.status', $status)
            ->orderByDesc('t.id')
            ->paginate(20)
            ->withQueryString();
        $counts = [
            'pending' => DB::table('rg_gp_topups')->where('status', 'pending')->count(),
            'approved' => DB::table('rg_gp_topups')->where('status', 'approved')->count(),
            'rejected' => DB::table('rg_gp_topups')->where('status', 'rejected')->count(),
        ];
        return view('resortGuruAdmin.gcash', compact('topups', 'counts', 'status'));
    }

    public function show(Request $request)
    {
        $id = (int) $request->input('id');
        $topup = DB::table('rg_gp_topups')->where('id', $id)->first();
        if (!$topup) abort(404);
        $owner = DB::table('rg_owners')->where('id', $topup->owner_id)->first();
        return view('resortGuruAdmin.gcash-show', compact('topup', 'owner'));
    }

    public function approve(Request $request)
    {
        $id = (int) $request->input('id');
        $topup = DB::table('rg_gp_topups')->where('id', $id)->first();
        if (!$topup) abort(404);
        if ($topup->status !== 'pending') {
            return response()->json(['ok' => false, 'message' => 'Already reviewed.'], 422);
        }
        DB::transaction(function () use ($topup) {
            DB::table('rg_gp_topups')->where('id', $topup->id)->update([
                'status' => 'approved',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
                'updated_at' => now(),
            ]);
            DB::table('rg_gp_ledger')->insert([
                'owner_id' => $topup->owner_id,
                'amount' => (int) $topup->gp_amount,
                'reason' => 'topup',
                'ref_type' => 'rg_gp_topups',
                'ref_id' => $topup->id,
                'status' => 'posted',
                'meta_json' => json_encode(['php_amount' => $topup->php_amount, 'gcash_ref' => $topup->gcash_ref_number]),
                'created_at' => now(),
            ]);
        });
        return response()->json(['ok' => true]);
    }

    public function reject(Request $request)
    {
        $id = (int) $request->input('id');
        $data = $request->validate(['rejection_reason' => 'required|string|max:500']);
        DB::table('rg_gp_topups')->where('id', $id)->update([
            'status' => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
            'rejection_reason' => $data['rejection_reason'],
            'updated_at' => now(),
        ]);
        return response()->json(['ok' => true]);
    }
}
