<?php

namespace App\Http\Controllers\aniSensoAdmin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * What Anee learned (2026-10-18, the owner: "what you get, save it in the db
 * as well so that next time, it won't need anee to research online").
 *
 * anee.io keeps what Anee looked up online so no module searches for it
 * again: the varieties (as_learned_varieties, App\Support\VarietyLearned on
 * anee.io) and the products (as_products rows with source 'anee', with their
 * other names in as_product_aliases, App\Support\ProductLibrary::learn).
 * Here the owner sees them, corrects them, marks them reviewed (a reviewed
 * variety never goes stale and keeps its facts when Anee meets it again) and
 * retires a wrong one (status 0: no module reads it).
 *
 * The rows of the product files (source 'file') are never changed here.
 * The sources are shown here only; anee.io never shows them to farmers.
 * Keys kept in step with anee.io: a variety's nameKey (its unique key; the
 * lookups read the name and the other names), a product's brandKey (what
 * ProductLibrary::find matches) and search (its haystack).
 */
class AnisystemLearnedController extends Controller
{
    private const VARIETIES = 'as_learned_varieties';

    private const PRODUCTS = 'as_products';

    private const ALIASES = 'as_product_aliases';

    /** anee.io's ProductLibrary::TYPES, for the type picker and the search words. */
    public const TYPES = [
        'insecticide' => 'Insecticide', 'fungicide' => 'Fungicide', 'herbicide' => 'Herbicide', 'molluscicide' => 'Molluscicide', 'nematicide' => 'Nematicide',
        'acaricide' => 'Miticide', 'rodenticide' => 'Rodenticide', 'pgr' => 'Growth regulator', 'adjuvant' => 'Spray helper', 'foliar' => 'Foliar fertilizer',
        'soluble' => 'Soluble fertilizer', 'granular' => 'Granular fertilizer', 'amendment' => 'Soil conditioner', 'biostimulant' => 'Biostimulant',
        'biological' => 'Biological', 'chemical' => 'Plain chemical', 'other' => 'Other',
    ];

    public const NUTRIENTS = ['N', 'P2O5', 'K2O', 'Ca', 'Mg', 'S', 'Zn', 'B', 'Fe', 'Mn', 'Cu', 'Mo', 'SiO2', 'Cl'];

    public function index(Request $request)
    {
        $tab = $request->query('tab') === 'products' ? 'products' : 'varieties';
        $q = trim((string) $request->query('q', ''));
        $show = in_array($request->query('show'), ['live', 'unreviewed', 'stale', 'retired', 'all'], true) ? $request->query('show') : 'live';
        $ready = ['varieties' => Schema::hasTable(self::VARIETIES), 'products' => Schema::hasTable(self::PRODUCTS)];
        $rows = null;
        $counts = [];
        if ($tab === 'varieties' && $ready['varieties']) {
            $qb = DB::table(self::VARIETIES);
            $counts = ['live' => (clone $qb)->where('status', 1)->count(), 'unreviewed' => (clone $qb)->where('status', 1)->where('reviewed', 0)->count(),
                'stale' => (clone $qb)->where('status', 1)->where('reviewed', 0)->whereNotNull('staleAt')->where('staleAt', '<', now())->count(),
                'retired' => (clone $qb)->where('status', 0)->count(), 'all' => (clone $qb)->count()];
            match ($show) {
                'live' => $qb->where('status', 1),
                'unreviewed' => $qb->where('status', 1)->where('reviewed', 0),
                'stale' => $qb->where('status', 1)->where('reviewed', 0)->whereNotNull('staleAt')->where('staleAt', '<', now()),
                'retired' => $qb->where('status', 0),
                default => null,
            };
            if ($q !== '') {
                $qb->where(fn ($w) => $w->where('name', 'like', '%' . $q . '%')->orWhere('aliases', 'like', '%' . $q . '%')->orWhere('nsic', 'like', '%' . $q . '%')
                    ->orWhere('company', 'like', '%' . $q . '%')->orWhere('crop', $q)->orWhere('aliasOf', 'like', '%' . $q . '%'));
            }
            $rows = $qb->orderByDesc('updated_at')->orderByDesc('id')->paginate(40)->withQueryString();
        }
        if ($tab === 'products' && $ready['products']) {
            $qb = DB::table(self::PRODUCTS)->where('source', 'anee');
            $counts = ['live' => (clone $qb)->where('status', 1)->count(), 'unreviewed' => (clone $qb)->where('status', 1)->where(fn ($w) => $w->whereNull('info')->orWhere('info', 'not like', '%"reviewed":true%'))->count(),
                'retired' => (clone $qb)->where('status', 0)->count(), 'all' => (clone $qb)->count()];
            match ($show) {
                'live', 'stale' => $qb->where('status', 1),
                'unreviewed' => $qb->where('status', 1)->where(fn ($w) => $w->whereNull('info')->orWhere('info', 'not like', '%"reviewed":true%')),
                'retired' => $qb->where('status', 0),
                default => null,
            };
            if ($q !== '') {
                $qb->where(fn ($w) => $w->where('brand', 'like', '%' . $q . '%')->orWhere('company', 'like', '%' . $q . '%')->orWhere('search', 'like', '%' . mb_strtolower($q) . '%'));
            }
            $rows = $qb->orderByDesc('updated_at')->orderByDesc('id')->paginate(40)->withQueryString();
            $ids = collect($rows->items())->pluck('id')->all();
            $aliases = $ids && Schema::hasTable(self::ALIASES) ? DB::table(self::ALIASES)->whereIn('productId', $ids)->orderBy('id')->get()->groupBy('productId') : collect();
            foreach ($rows->items() as $r) {
                $r->aliasList = ($aliases[$r->id] ?? collect())->pluck('alias')->all();
            }
        }

        return view('aniSensoAdmin.learned.index', compact('tab', 'q', 'show', 'ready', 'rows', 'counts'));
    }

    /* ======================================================= varieties */

    public function variety(Request $request)
    {
        $r = DB::table(self::VARIETIES)->where('id', (int) $request->query('id'))->first();
        abort_unless($r, 404);

        return view('aniSensoAdmin.learned.variety', ['r' => $r, 'seed' => json_decode((string) $r->seed, true) ?: [], 'facts' => json_decode((string) $r->facts, true) ?: [],
            'sources' => json_decode((string) $r->sources, true) ?: [], 'aliases' => json_decode((string) $r->aliases, true) ?: []]);
    }

    public function saveVariety(Request $request)
    {
        $id = (int) $request->input('id');
        $r = DB::table(self::VARIETIES)->where('id', $id)->first();
        abort_unless($r, 404);
        $v = $request->validate([
            'name' => 'required|string|max:120', 'aliases' => 'nullable|string|max:1000', 'aliasOf' => 'nullable|string|max:120', 'nsic' => 'nullable|string|max:60',
            'company' => 'nullable|string|max:120', 'type' => 'nullable|in:hybrid,inbred,open,other', 'year' => 'nullable|integer|min:1950|max:2100',
            'maturityDays' => 'nullable|integer|min:20|max:420', 'yieldAvg' => 'nullable|numeric|min:0|max:150', 'yieldTop' => 'nullable|numeric|min:0|max:150',
            'heightCm' => 'nullable|integer|min:10|max:600', 'season' => 'nullable|string|max:160', 'reactions' => 'nullable|string|max:400', 'tolerances' => 'nullable|string|max:400',
            'grain' => 'nullable|string|max:300', 'caution' => 'nullable|string|max:300', 'confidence' => 'required|in:high,medium,low',
            'soakLo' => 'nullable|integer|min:1|max:72', 'soakHi' => 'nullable|integer|min:1|max:72', 'refresh' => 'nullable|integer|min:2|max:24',
            'incLo' => 'nullable|integer|min:6|max:72', 'incHi' => 'nullable|integer|min:6|max:72', 'rateLo' => 'nullable|numeric|min:0.1|max:250', 'rateHi' => 'nullable|numeric|min:0.1|max:250',
            'seedNotes' => 'nullable|string|max:320', 'reviewed' => 'nullable|boolean',
        ]);
        $name = trim(preg_replace('/\s+/', ' ', $v['name']));
        $key = self::varietyKey($name);
        if ($key === '') {
            return back()->withInput()->with('error', 'The name needs letters or numbers.');
        }
        if (DB::table(self::VARIETIES)->where('crop', $r->crop)->where('nameKey', $key)->where('id', '!=', $id)->exists()) {
            return back()->withInput()->with('error', 'Another learned ' . $r->crop . ' variety already has that name. Retire one of them first.');
        }
        $aliases = array_values(array_unique(array_filter(array_map(fn ($a) => trim($a), explode(',', (string) ($v['aliases'] ?? ''))), fn ($a) => mb_strlen($a) >= 2 && self::varietyKey($a) !== $key)));
        $pair = fn ($lo, $hi) => $lo !== null && $lo !== '' ? [(float) $lo, (float) (($hi ?? '') !== '' ? $hi : $lo)] : null;
        $seed = array_filter([
            'soak' => ($s = $pair($v['soakLo'] ?? null, $v['soakHi'] ?? null)) ? array_map('intval', $s) : null,
            'refresh' => ($v['refresh'] ?? null) !== null ? (int) $v['refresh'] : null,
            'incubate' => ($s = $pair($v['incLo'] ?? null, $v['incHi'] ?? null)) ? array_map('intval', $s) : null,
            'rate' => $pair($v['rateLo'] ?? null, $v['rateHi'] ?? null),
            'notes' => trim((string) ($v['seedNotes'] ?? '')) ?: null,
        ], fn ($x) => $x !== null);
        $facts = json_decode((string) $r->facts, true) ?: [];
        if ($v['maturityDays'] ?? null) {
            $facts['daysRange'] = [(int) $v['maturityDays'], (int) $v['maturityDays']];
        }
        $null = fn ($x) => ($x === null || trim((string) $x) === '') ? null : trim((string) $x);
        $reviewed = $request->boolean('reviewed');
        DB::table(self::VARIETIES)->where('id', $id)->update([
            'name' => $name, 'nameKey' => $key, 'aliases' => json_encode($aliases, JSON_UNESCAPED_UNICODE), 'aliasOf' => $null($v['aliasOf'] ?? null),
            'nsic' => $null($v['nsic'] ?? null), 'company' => $null($v['company'] ?? null), 'type' => $null($v['type'] ?? null), 'year' => $v['year'] ?? null,
            'maturityDays' => $v['maturityDays'] ?? null, 'yieldAvg' => $v['yieldAvg'] ?? null, 'yieldTop' => $v['yieldTop'] ?? null, 'heightCm' => $v['heightCm'] ?? null,
            'season' => $null($v['season'] ?? null), 'reactions' => $null($v['reactions'] ?? null), 'tolerances' => $null($v['tolerances'] ?? null),
            'grain' => $null($v['grain'] ?? null), 'caution' => $null($v['caution'] ?? null), 'confidence' => $v['confidence'],
            'seed' => json_encode((object) $seed), 'facts' => json_encode((object) $facts, JSON_UNESCAPED_UNICODE),
            'reviewed' => $reviewed ? 1 : 0, 'reviewedAt' => $reviewed ? ($r->reviewedAt ?: now()) : null, 'updated_at' => now(),
        ]);

        return redirect()->route('anisenso-learned.variety', ['id' => $id])->with('success', 'Saved. Every anee.io module reads it this way from now on.');
    }

    public function actVariety(Request $request)
    {
        $id = (int) $request->input('id');
        $act = (string) $request->input('act');
        $r = DB::table(self::VARIETIES)->where('id', $id)->first();
        abort_unless($r && in_array($act, ['review', 'unreview', 'retire', 'restore', 'refresh'], true), 404);
        $up = match ($act) {
            'review' => ['reviewed' => 1, 'reviewedAt' => now()],
            'unreview' => ['reviewed' => 0, 'reviewedAt' => null],
            'retire' => ['status' => 0],
            'restore' => ['status' => 1],
            // Looked up again by the next module that meets it (a stale row is searched and refreshed).
            'refresh' => ['staleAt' => now()->subMinute(), 'reviewed' => 0, 'reviewedAt' => null],
        };
        DB::table(self::VARIETIES)->where('id', $id)->update($up + ['updated_at' => now()]);
        $said = ['review' => 'Marked reviewed: it never goes stale, and Anee only adds names to it.', 'unreview' => 'No longer marked reviewed.',
            'retire' => 'Retired: no module reads it, and the next farmer who names it gets it looked up again.', 'restore' => 'Restored.',
            'refresh' => 'The next module that meets this variety looks it up again and refreshes the row.'][$act];

        return back()->with('success', $said);
    }

    /* ======================================================= products */

    public function product(Request $request)
    {
        $r = DB::table(self::PRODUCTS)->where('id', (int) $request->query('id'))->where('source', 'anee')->first();
        abort_unless($r, 404);
        $aliases = Schema::hasTable(self::ALIASES) ? DB::table(self::ALIASES)->where('productId', $r->id)->orderBy('id')->get() : collect();

        return view('aniSensoAdmin.learned.product', ['r' => $r, 'ai' => json_decode((string) $r->ai, true) ?: [], 'nut' => json_decode((string) $r->nutrients, true) ?: [],
            'info' => json_decode((string) $r->info, true) ?: [], 'aliases' => $aliases, 'types' => self::TYPES]);
    }

    public function saveProduct(Request $request)
    {
        $id = (int) $request->input('id');
        $r = DB::table(self::PRODUCTS)->where('id', $id)->where('source', 'anee')->first();
        abort_unless($r, 404);
        $v = $request->validate([
            'brand' => 'required|string|max:190', 'company' => 'nullable|string|max:120', 'type' => 'required|in:' . implode(',', array_keys(self::TYPES)),
            'form' => 'nullable|string|max:16', 'ai' => 'nullable|string|max:1000', 'nutrients' => 'nullable|string|max:600', 'aka' => 'nullable|string|max:1000',
            'confidence' => 'required|in:high,medium,low', 'reviewed' => 'nullable|boolean',
        ]);
        $brand = trim(preg_replace('/\s+/', ' ', $v['brand']));
        $brandKey = self::productKey($brand);
        if (strlen($brandKey) < 2) {
            return back()->withInput()->with('error', 'The brand needs letters or numbers.');
        }
        // Ingredients: "Cypermethrin 50 g/L; Chlorpyrifos 500 g/L".
        $ai = [];
        foreach (array_filter(array_map('trim', preg_split('/\s*;\s*|\n/', (string) ($v['ai'] ?? '')) ?: [])) as $line) {
            if (preg_match('/^(.+?)\s+(\d+(?:\.\d+)?)\s*(g\/L|g\/kg|%|mL\/L|CFU\/g|CFU\/mL)\s*$/i', $line, $m)) {
                $unit = ['g/l' => 'g/L', 'g/kg' => 'g/kg', '%' => '%', 'ml/l' => 'mL/L', 'cfu/g' => 'CFU/g', 'cfu/ml' => 'CFU/mL'][mb_strtolower($m[3])];
                $ai[] = ['n' => mb_substr(trim($m[1]), 0, 80), 'a' => (float) $m[2], 'u' => $unit];
            } else {
                $ai[] = ['n' => mb_substr($line, 0, 80)];
            }
        }
        // Nutrients: "N 21, S 24" in percent.
        $nut = [];
        foreach (array_filter(array_map('trim', preg_split('/\s*[,;]\s*/', (string) ($v['nutrients'] ?? '')) ?: [])) as $pair) {
            if (preg_match('/^([A-Za-z0-9]+)\s*[:= ]\s*(\d+(?:\.\d+)?)$/', $pair, $m)) {
                $el = collect(self::NUTRIENTS)->first(fn ($n) => strcasecmp($n, $m[1]) === 0);
                if ($el && (float) $m[2] > 0 && (float) $m[2] <= 100) {
                    $nut[$el] = (float) $m[2];
                }
            }
        }
        if (! $ai && ! $nut) {
            return back()->withInput()->with('error', 'Give its ingredients or its analysis: a product with neither tells the modules nothing.');
        }
        $aka = array_values(array_unique(array_filter(array_map('trim', explode(',', (string) ($v['aka'] ?? ''))), fn ($a) => mb_strlen($a) >= 2)));
        $info = json_decode((string) $r->info, true) ?: [];
        $info['aka'] = $aka;
        $info['confidence'] = $v['confidence'];
        $info['reviewed'] = $request->boolean('reviewed');
        $info['editedAt'] = now('Asia/Manila')->toDateString();
        $hay = [$brand, (string) ($v['company'] ?? ''), self::TYPES[$v['type']] ?? '', implode(' ', $aka)];
        foreach ($ai as $a) {
            $hay[] = $a['n'];
        }
        foreach ($nut as $el => $x) {
            $hay[] = $el;
        }
        DB::table(self::PRODUCTS)->where('id', $id)->update([
            'brand' => mb_substr($brand, 0, 190), 'brandKey' => mb_substr($brandKey, 0, 190), 'company' => mb_substr(trim((string) ($v['company'] ?? '')), 0, 120),
            'type' => $v['type'], 'form' => mb_substr(trim((string) ($v['form'] ?? '')), 0, 16), 'ai' => json_encode($ai, JSON_UNESCAPED_UNICODE),
            'nutrients' => json_encode((object) $nut), 'info' => json_encode((object) $info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'search' => mb_substr(self::norm(implode(' ', $hay)), 0, 1000), 'updated_at' => now(),
        ]);
        // The other names point at it (kept apart from the row, as anee.io's ProductLibrary::alias does).
        if (Schema::hasTable(self::ALIASES)) {
            foreach ($aka as $a) {
                $k = self::productKey($a);
                if (strlen($k) >= 2) {
                    DB::table(self::ALIASES)->updateOrInsert(['productId' => $id, 'aliasKey' => mb_substr($k, 0, 190)], ['alias' => mb_substr($a, 0, 190), 'via' => 'mother', 'updated_at' => now(), 'created_at' => now()]);
                }
            }
        }

        return redirect()->route('anisenso-learned.product', ['id' => $id])->with('success', 'Saved. The Compatibility Checker, NPK Plus and the Protocol Builder read it this way from now on.');
    }

    public function actProduct(Request $request)
    {
        $id = (int) $request->input('id');
        $act = (string) $request->input('act');
        $r = DB::table(self::PRODUCTS)->where('id', $id)->where('source', 'anee')->first();
        abort_unless($r && in_array($act, ['review', 'unreview', 'retire', 'restore', 'unalias'], true), 404);
        if ($act === 'unalias') {
            DB::table(self::ALIASES)->where('productId', $id)->where('id', (int) $request->input('aliasId'))->delete();

            return back()->with('success', 'That name no longer points at this product.');
        }
        if (in_array($act, ['review', 'unreview'], true)) {
            $info = json_decode((string) $r->info, true) ?: [];
            $info['reviewed'] = $act === 'review';
            DB::table(self::PRODUCTS)->where('id', $id)->update(['info' => json_encode((object) $info, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), 'updated_at' => now()]);
        } else {
            DB::table(self::PRODUCTS)->where('id', $id)->update(['status' => $act === 'retire' ? 0 : 1, 'updated_at' => now()]);
        }

        return back()->with('success', ['review' => 'Marked reviewed.', 'unreview' => 'No longer marked reviewed.', 'retire' => 'Retired: no module reads it.', 'restore' => 'Restored.'][$act]);
    }

    /* ======================================================= keys (anee.io's twins) */

    /** VarietyBook::keys()'s first key: lower case, ñ as n, letters and digits only, Dekalb as DK, Pioneer as P, no trailing "hybrid". */
    public static function varietyKey(string $n): string
    {
        $s = strtr(mb_strtolower(str_ireplace(['dekalb', 'pioneer'], ['dk', 'p'], $n)), ['ñ' => 'n']);
        $s = (string) preg_replace('/[^a-z0-9]+/', '', $s);

        return (string) preg_replace('/(f1|hybrid|variety|seeds?)$/', '', $s);
    }

    /** ProductLibrary::key(): lower case, & as "and", letters and digits only. */
    public static function productKey(string $s): string
    {
        return (string) preg_replace('/[^a-z0-9]+/', '', str_replace('&', 'and', mb_strtolower($s)));
    }

    /** ProductLibrary::norm(), for the search haystack. */
    public static function norm(string $s): string
    {
        $s = str_replace(['-', '_', '/', ',', '.', '(', ')', '+'], ' ', mb_strtolower($s));

        return trim((string) preg_replace('/\s+/', ' ', $s));
    }
}
