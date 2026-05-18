<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class ResortGuruDashboardController extends Controller
{
    public function index(Request $request)
    {
        $kpis = [
            'owners' => 0,
            'resorts_published' => 0,
            'pages_published' => 0,
            'gp_minted_today' => 0,
            'topups_pending' => 0,
        ];

        if (Schema::hasTable('rg_owners')) {
            $kpis['owners'] = DB::table('rg_owners')->count();
        }
        if (Schema::hasTable('rg_resorts')) {
            $kpis['resorts_published'] = DB::table('rg_resorts')->where('status', 'published')->count();
        }
        if (Schema::hasTable('rg_seo_pages')) {
            $kpis['pages_published'] = DB::table('rg_seo_pages')->where('is_published', 1)->count();
        }
        if (Schema::hasTable('rg_gp_ledger')) {
            $kpis['gp_minted_today'] = (int) DB::table('rg_gp_ledger')
                ->where('reason', 'topup')
                ->where('status', 'posted')
                ->whereDate('created_at', now()->toDateString())
                ->sum('amount');
        }
        if (Schema::hasTable('rg_gp_topups')) {
            $kpis['topups_pending'] = DB::table('rg_gp_topups')->where('status', 'pending')->count();
        }

        $recent = collect();
        if (Schema::hasTable('rg_audit_logs')) {
            $recent = DB::table('rg_audit_logs')->orderByDesc('id')->limit(20)->get();
        }

        return view('resortGuruAdmin.dashboard', compact('kpis', 'recent'));
    }
}
