<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Yajra\DataTables\Facades\DataTables;

class RgOwnersController extends Controller
{
    public function index(Request $request)
    {
        if (!Schema::hasTable('rg_owners')) {
            return view('resortGuruAdmin.pending', ['title' => 'Clients']);
        }
        if ($request->ajax()) {
            $query = DB::table('rg_owners')->select(['id', 'name', 'email', 'phone', 'status', 'last_login_at', 'created_at']);
            return DataTables::of($query)
                ->editColumn('created_at', fn($r) => $r->created_at ? \Carbon\Carbon::parse($r->created_at)->format('Y-m-d') : '')
                ->editColumn('last_login_at', fn($r) => $r->last_login_at ? \Carbon\Carbon::parse($r->last_login_at)->diffForHumans() : 'never')
                ->editColumn('status', function ($r) {
                    $colors = ['active' => 'success', 'suspended' => 'danger', 'pending' => 'warning'];
                    $c = $colors[$r->status] ?? 'secondary';
                    return '<span class="badge bg-' . $c . '">' . ucfirst($r->status) . '</span>';
                })
                ->addColumn('actions', fn($r) => '<a href="' . route('resort-guru-owners.show', ['id' => $r->id]) . '" class="btn btn-sm btn-primary"><i class="bx bx-show"></i> View</a>')
                ->rawColumns(['actions', 'status'])
                ->make(true);
        }
        return view('resortGuruAdmin.owners');
    }

    public function create()
    {
        return view('resortGuruAdmin.owners-add');
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:rg_owners,email',
            'phone' => 'nullable|string|max:50',
            'password' => 'nullable|string|min:8',
            'status' => 'required|in:active,suspended,pending',
            'initial_gp' => 'nullable|integer|min:0',
            'send_credentials' => 'nullable',
        ]);

        $rawPassword = $data['password'] ?: Str::random(12);
        $ownerId = DB::table('rg_owners')->insertGetId([
            'name' => $data['name'],
            'email' => $data['email'],
            'phone' => $data['phone'] ?? null,
            'password' => Hash::make($rawPassword),
            'status' => $data['status'],
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        if (!empty($data['initial_gp']) && $data['initial_gp'] > 0) {
            DB::table('rg_gp_ledger')->insert([
                'owner_id' => $ownerId,
                'amount' => (int) $data['initial_gp'],
                'reason' => 'admin_adjustment',
                'ref_type' => 'admin_grant',
                'ref_id' => Auth::id(),
                'status' => 'posted',
                'meta_json' => json_encode(['note' => 'Initial GP at client creation']),
                'created_at' => now(),
            ]);
        }

        return redirect()->route('resort-guru-owners.show', ['id' => $ownerId])
            ->with('success', "Client created. Temporary password: $rawPassword (please share securely).");
    }

    public function show(Request $request)
    {
        $id = (int) $request->input('id');
        $owner = DB::table('rg_owners')->where('id', $id)->first();
        if (!$owner) abort(404);
        $resorts = Schema::hasTable('rg_resorts')
            ? DB::table('rg_resorts')->where('owner_id', $id)->get()
            : collect();
        $posted = Schema::hasTable('rg_gp_ledger')
            ? (int) DB::table('rg_gp_ledger')->where('owner_id', $id)->where('status', 'posted')->sum('amount')
            : 0;
        $held = Schema::hasTable('rg_gp_holds')
            ? (int) DB::table('rg_gp_holds')->where('owner_id', $id)->where('status', 'active')->sum('amount')
            : 0;
        $balance = $posted - $held;
        $recentLedger = Schema::hasTable('rg_gp_ledger')
            ? DB::table('rg_gp_ledger')->where('owner_id', $id)->orderByDesc('id')->limit(10)->get()
            : collect();
        return view('resortGuruAdmin.owners-show', compact('owner', 'resorts', 'balance', 'posted', 'held', 'recentLedger'));
    }

    public function update(Request $request)
    {
        $id = (int) $request->input('id');
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:rg_owners,email,' . $id,
            'phone' => 'nullable|string|max:50',
            'status' => 'required|in:active,suspended,pending',
        ]);
        $data['updated_at'] = now();
        DB::table('rg_owners')->where('id', $id)->update($data);
        return redirect()->route('resort-guru-owners.show', ['id' => $id])->with('success', 'Client updated.');
    }

    public function toggleStatus(Request $request)
    {
        $id = (int) $request->input('id');
        $owner = DB::table('rg_owners')->where('id', $id)->first();
        if (!$owner) abort(404);
        $new = $owner->status === 'active' ? 'suspended' : 'active';
        DB::table('rg_owners')->where('id', $id)->update(['status' => $new, 'updated_at' => now()]);
        return response()->json(['ok' => true, 'status' => $new]);
    }

    public function resetPassword(Request $request)
    {
        $id = (int) $request->input('id');
        $data = $request->validate([
            'password' => 'nullable|string|min:8',
        ]);
        $newPassword = $data['password'] ?: Str::random(12);
        DB::table('rg_owners')->where('id', $id)->update([
            'password' => Hash::make($newPassword),
            'updated_at' => now(),
        ]);
        return response()->json(['ok' => true, 'temporary_password' => $newPassword]);
    }

    public function creditGp(Request $request)
    {
        $data = $request->validate([
            'id' => 'required|integer|exists:rg_owners,id',
            'amount' => 'required|integer|not_in:0',
            'reason' => 'required|string|max:255',
        ]);
        DB::table('rg_gp_ledger')->insert([
            'owner_id' => $data['id'],
            'amount' => $data['amount'],
            'reason' => 'admin_adjustment',
            'ref_type' => 'admin',
            'ref_id' => Auth::id(),
            'status' => 'posted',
            'meta_json' => json_encode(['note' => $data['reason']]),
            'created_at' => now(),
        ]);
        return response()->json(['ok' => true]);
    }
}
