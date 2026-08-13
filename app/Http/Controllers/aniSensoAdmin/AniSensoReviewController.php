<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

/**
 * What AniSystem growers think of the app.
 *
 * The app asks each person once — a rating, and their words if they left any.
 * This is where those answers are read: the average, how the five ratings are
 * spread, and every written review with who wrote it and when.
 *
 * Reads the shared database directly rather than through an AniSystem model,
 * the way the rest of the AniSenso admin does.
 */
class AniSensoReviewController extends Controller
{
    public function index(Request $request)
    {
        $stars = (int) $request->query('stars', 0);
        $onlyWritten = $request->boolean('written');

        $q = DB::table('as_app_reviews as r')
            ->leftJoin('users as u', 'u.id', '=', 'r.userId')
            ->where('r.deleteStatus', 1)
            ->where('r.rating', '>', 0)
            ->select([
                'r.id', 'r.rating', 'r.review', 'r.device', 'r.created_at', 'r.updated_at',
                'u.email', 'u.name',
            ]);

        if ($stars >= 1 && $stars <= 5) {
            $q->where('r.rating', $stars);
        }
        if ($onlyWritten) {
            $q->whereNotNull('r.review')->where('r.review', '!=', '');
        }

        $reviews = $q->orderByDesc('r.updated_at')->paginate(20)->withQueryString();

        // The shape of the feedback, not just its average: four fives and a
        // one is a different story from five fours with the same mean.
        $counts = DB::table('as_app_reviews')
            ->where('deleteStatus', 1)->where('rating', '>', 0)
            ->select('rating', DB::raw('count(*) as n'))
            ->groupBy('rating')->pluck('n', 'rating')->all();

        $total = array_sum($counts);
        $avg = $total ? round(array_sum(array_map(fn ($r, $n) => $r * $n, array_keys($counts), $counts)) / $total, 2) : null;

        return view('aniSensoAdmin.reviews.index', [
            'reviews' => $reviews,
            'counts' => $counts,
            'total' => $total,
            'avg' => $avg,
            'stars' => $stars,
            'onlyWritten' => $onlyWritten,
            // People asked who have not answered — the silent majority is
            // worth a number too.
            'dismissed' => (int) DB::table('as_app_reviews')->where('deleteStatus', 1)->where('rating', 0)->count(),
        ]);
    }
}
