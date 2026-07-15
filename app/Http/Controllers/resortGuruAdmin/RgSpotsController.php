<?php

namespace App\Http\Controllers\resortGuruAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

/**
 * Unified "Spots" screen: Tourist Spots, Restaurants, Adventures, and
 * Fiestas as tabs on one page. The tab tables load their data from the
 * original module routes (which still serve the DataTables JSON).
 */
class RgSpotsController extends Controller
{
    public const TABS = ['tourist-spots', 'restaurants', 'adventures', 'fiestas'];

    public function index(Request $request)
    {
        $tab = (string) $request->query('tab', 'tourist-spots');
        if (!in_array($tab, self::TABS, true)) {
            $tab = 'tourist-spots';
        }
        return view('resortGuruAdmin.spots', ['activeTab' => $tab]);
    }
}
