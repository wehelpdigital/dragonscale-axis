<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class RgGoldPointsController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_gp_ledger')) {
            return view('resortGuruAdmin.pending', ['title' => 'Gold Points']);
        }
        $ledger = DB::table('rg_gp_ledger as l')
            ->leftJoin('rg_owners as o', 'o.id', '=', 'l.owner_id')
            ->select('l.*', 'o.name as owner_name', 'o.email as owner_email')
            ->orderByDesc('l.id')
            ->paginate(50);
        $owners = DB::table('rg_owners')->select('id', 'name', 'email')->orderBy('name')->get();
        return view('resortGuruAdmin.gold-points', compact('ledger', 'owners'));
    }

    public function adjust(Request $request)
    {
        $data = $request->validate([
            'owner_id' => 'required|exists:rg_owners,id',
            'amount' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:255',
        ]);
        DB::table('rg_gp_ledger')->insert([
            'owner_id' => $data['owner_id'],
            'amount' => $data['amount'],
            'reason' => 'admin_adjustment',
            'ref_type' => 'admin',
            'ref_id' => Auth::id(),
            'status' => 'posted',
            'meta_json' => json_encode(['note' => $data['reason']]),
            'created_at' => now(),
        ]);
        return redirect()->route('resort-guru-gp.index')->with('success', 'Adjustment posted.');
    }
}
