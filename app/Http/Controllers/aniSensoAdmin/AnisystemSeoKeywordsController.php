<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * SEO keywords (as_seo_keywords, 2026-10-01): the searches anee.io writes
 * toward. Anee's answers to Try and Ask Anee and every page drafted with
 * "Write with Anee" draw their focus and secondary keywords from this list,
 * nearest first, and count each one they use (usedCount), so a keyword not
 * yet worn out is reached for next.
 *
 * Loaded first from anisenso_keywords.csv by anee.io's migration; grown here
 * one at a time or by importing a CSV of the same shape (No, Keyword, Volume,
 * CPC, Paid Difficulty, SEO Difficulty; "Search Volume" works as a header
 * too, and a CPC like "₱45.86" is read as 45.86).
 */
class AnisystemSeoKeywordsController extends Controller
{
    private const TABLE = 'as_seo_keywords';

    public function index(Request $request)
    {
        $ready = Schema::hasTable(self::TABLE);
        $q = trim((string) $request->query('q', ''));
        $sort = in_array($request->query('sort'), ['volume', 'keyword', 'used', 'difficulty', 'newest'], true) ? $request->query('sort') : 'volume';
        $rows = collect();
        $totals = ['n' => 0, 'volume' => 0, 'used' => 0];
        if ($ready) {
            $base = DB::table(self::TABLE)->where('deleteStatus', 1);
            $totals = [
                'n' => (clone $base)->count(),
                'volume' => (int) (clone $base)->sum('volume'),
                'used' => (clone $base)->where('usedCount', '>', 0)->count(),
            ];
            $rows = $base->when($q !== '', fn ($w) => $w->where('keyword', 'like', '%' . addcslashes(mb_strtolower($q), '%_\\') . '%'))
                ->when($sort === 'volume', fn ($w) => $w->orderByDesc('volume'))
                ->when($sort === 'keyword', fn ($w) => $w->orderBy('keyword'))
                ->when($sort === 'used', fn ($w) => $w->orderByDesc('usedCount')->orderByDesc('volume'))
                ->when($sort === 'difficulty', fn ($w) => $w->orderBy('seoDifficulty')->orderByDesc('volume'))
                ->when($sort === 'newest', fn ($w) => $w->orderByDesc('id'))
                ->paginate(50)->withQueryString();
        }

        return view('aniSensoAdmin.seo-keywords.index', compact('ready', 'rows', 'q', 'sort', 'totals'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'keyword' => 'required|string|max:190',
            'volume' => 'nullable|integer|min:0',
            'cpc' => 'nullable|numeric|min:0',
            'paidDifficulty' => 'nullable|integer|min:0|max:100',
            'seoDifficulty' => 'nullable|integer|min:0|max:100',
        ]);
        $k = self::normalize($data['keyword']);
        if ($k === '') {
            return back()->with('error', 'Type a keyword.');
        }
        $row = [
            'volume' => (int) ($data['volume'] ?? 0),
            'cpc' => $data['cpc'] ?? null,
            'paidDifficulty' => $data['paidDifficulty'] ?? null,
            'seoDifficulty' => $data['seoDifficulty'] ?? null,
            'deleteStatus' => 1,
            'updated_at' => now(),
        ];
        if (DB::table(self::TABLE)->where('keyword', $k)->exists()) {
            DB::table(self::TABLE)->where('keyword', $k)->update($row);

            return back()->with('success', '"' . $k . '" was already on the list. Its numbers are updated.');
        }
        DB::table(self::TABLE)->insert($row + ['keyword' => $k, 'source' => 'manual', 'usedCount' => 0, 'created_at' => now()]);

        return back()->with('success', '"' . $k . '" added.');
    }

    /** A CSV in the export shape: every row added or refreshed. */
    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|max:5120']);
        $csv = (string) file_get_contents($request->file('file')->getRealPath());
        $csv = preg_replace('/^\xEF\xBB\xBF/', '', $csv);
        $lines = preg_split('/\r\n|\n|\r/', trim($csv)) ?: [];
        if (count($lines) < 2) {
            return back()->with('error', 'That file has no rows.');
        }
        $head = array_map(fn ($h) => strtolower(trim((string) $h)), str_getcsv(array_shift($lines)));
        $col = function (array $names) use ($head) {
            foreach ($names as $n) {
                $i = array_search($n, $head, true);
                if ($i !== false) {
                    return $i;
                }
            }

            return null;
        };
        $kw = $col(['keyword', 'keywords', 'key word']);
        if ($kw === null) {
            return back()->with('error', 'No "Keyword" column in that file. The first row must name the columns: No, Keyword, Volume, CPC, Paid Difficulty, SEO Difficulty.');
        }
        $vol = $col(['volume', 'search volume', 'avg. monthly searches']);
        $cpc = $col(['cpc', 'cost per click']);
        $paid = $col(['paid difficulty', 'pd']);
        $seo = $col(['seo difficulty', 'sd', 'keyword difficulty']);
        $num = fn ($v) => ($v = preg_replace('/[^0-9.]/', '', (string) $v)) === '' ? null : (float) $v;
        $added = $updated = $skipped = 0;
        foreach ($lines as $line) {
            if (trim($line) === '') {
                continue;
            }
            $r = str_getcsv($line);
            $k = self::normalize((string) ($r[$kw] ?? ''));
            if ($k === '' || mb_strlen($k) > 190) {
                $skipped++;

                continue;
            }
            $row = [
                'volume' => $vol !== null ? (int) ($num($r[$vol] ?? '') ?? 0) : 0,
                'cpc' => $cpc !== null ? $num($r[$cpc] ?? '') : null,
                'paidDifficulty' => $paid !== null && $num($r[$paid] ?? '') !== null ? (int) $num($r[$paid]) : null,
                'seoDifficulty' => $seo !== null && $num($r[$seo] ?? '') !== null ? (int) $num($r[$seo]) : null,
                'deleteStatus' => 1,
                'updated_at' => now(),
            ];
            if (DB::table(self::TABLE)->where('keyword', $k)->exists()) {
                DB::table(self::TABLE)->where('keyword', $k)->update($row);
                $updated++;
            } else {
                DB::table(self::TABLE)->insert($row + ['keyword' => $k, 'source' => mb_substr($request->file('file')->getClientOriginalName(), 0, 40), 'usedCount' => 0, 'created_at' => now()]);
                $added++;
            }
        }

        return back()->with('success', "Imported: {$added} new, {$updated} updated" . ($skipped ? ", {$skipped} skipped" : '') . '.');
    }

    public function destroy(Request $request)
    {
        $ids = array_filter(array_map('intval', (array) $request->input('ids', [])));
        if (! $ids) {
            return back()->with('error', 'Pick a keyword first.');
        }
        DB::table(self::TABLE)->whereIn('id', $ids)->update(['deleteStatus' => 0, 'updated_at' => now()]);

        return back()->with('success', count($ids) === 1 ? 'Keyword removed.' : count($ids) . ' keywords removed.');
    }

    private static function normalize(string $k): string
    {
        return trim((string) preg_replace('/\s+/', ' ', mb_strtolower(trim($k, " \t\"'"))));
    }
}
