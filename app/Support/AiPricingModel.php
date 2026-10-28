<?php

namespace App\Support;

use App\Models\AsSiteSetting;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * The twin of anee.io's App\Support\AiPricing (2026-10-15): the pricing
 * engine's numbers and arithmetic, read here so the owner can see and edit
 * them (AniSystem AI > Pricing engine). anee.io charges by its own copy;
 * both read the same shelf, as_site_settings `ai.pricing` (JSON). KEEP THE
 * DEFAULTS, THE RATES AND THE FORMULA IN STEP WITH anee's file.
 *
 * What a run costs the house is modelled per kind at the 90th percentile:
 * tokens in and out (a base plus an amount per unit of the job's size),
 * web search queries, and the other services (Open-Meteo, Google map loads,
 * mail, Earth Engine), at list prices times the peso rate. The price is
 * friendly(target x cost / the cheapest peso a credit sells for). With
 * flatFloor on, today's flat price is a floor under it (the rule until
 * 2026-10-16); off, the default since the owner lowered the margin to 4x to
 * 6x, the engine sets every price that has a cost model.
 */
final class AiPricingModel
{
    public const KEY = 'ai.pricing';

    public const FALLBACK_PESO_PER_CREDIT = 0.87;

    /** USD per million tokens, [input, output], by provider and family (list prices, 2026-10-15). */
    public const MODEL_RATES = [
        'gemini' => ['argon' => [2.00, 10.00], 'flash-lite' => [0.10, 0.40], 'flash' => [0.75, 3.75], 'pro' => [2.00, 12.00], '*' => [2.00, 12.00]],
        'claude' => ['fable' => [10.00, 50.00], 'opus' => [4.00, 20.00], 'haiku' => [0.10, 0.50], 'sonnet' => [2.00, 10.00], '*' => [2.00, 10.00]],
        'openai' => ['nano' => [0.10, 0.40], 'mini' => [0.15, 0.60], 'gpt-5' => [1.25, 10.00], 'gpt-4.1' => [2.00, 8.00], 'gpt-4o' => [2.50, 10.00], '*' => [2.50, 10.00]],
    ];

    /** USD per web search query. */
    public const SEARCH_FEE = ['gemini' => 0.014, 'claude' => 0.010, 'openai' => 0.030];

    public const DEFAULTS = [
        'on' => true,
        // 4x to 6x since 2026-10-16 (was 8x in 6x to 10x).
        'target' => 5.0,
        'minMargin' => 4.0,
        'maxMargin' => 6.0,
        // The owner's price list as the floor under the engine (anee.io's AiPricing twin, 2026-10-16).
        'flatFloor' => true,
        'pesoPerCredit' => null,
        'usdRate' => 58.0,
        'prices' => [
            'modelIn' => null,
            'modelOut' => null,
            'searchQuery' => null,
            'queriesPerSearch' => 8,
            'openMeteoCall' => 0.000198,
            'mapsLoad' => 0.007,
            'emailSend' => 0.0004,
            'earthEngineRun' => 0.0,
        ],
        'kinds' => [
            // A named variety: the research leads with it and the report times by it (measured 2026-10: ₱12 to ₱13 a run without, ₱15 to ₱22 with).
            'wtp' => ['unit' => '', 'baseIn' => 10500, 'baseOut' => 8000, 'search' => 1, 'opts' => ['variety' => ['in' => 4500, 'out' => 10500]]],
            // Twenty crops, and since 2026-10-17 a research step first (real yields and farmgate prices, the web open).
            'what' => ['unit' => 'crop', 'min' => 0, 'free' => 0, 'units' => 3, 'max' => 16, 'baseIn' => 10500, 'baseOut' => 15300, 'unitIn' => 20, 'unitOut' => 230, 'outCap' => 26048, 'search' => 1],
            // Ten ranked with the derbies' and farmers' harvests (2026-10-17): longer notes and a longer answer.
            'variety' => ['unit' => 'variety', 'free' => 3, 'units' => 3, 'max' => 8, 'baseIn' => 12500, 'baseOut' => 16000, 'unitIn' => 250, 'unitOut' => 120, 'search' => 1, 'om' => 1],
            // Several lots since 2026-10-18 (anee.io AiPricing twin): one research ask a crop or variety (`cases` beyond the first), the farm ask, one ask a lot.
            // The flat list price is protocol for the first lot, plus protocolLot a lot and protocolCase a crop or variety beyond it.
            'protocol' => ['unit' => 'lot', 'free' => 1, 'units' => 2, 'max' => 8, 'unitPrice' => 'protocolLot', 'optPrices' => ['cases' => 'protocolCase'],
                'baseIn' => 22000, 'baseOut' => 20000, 'unitIn' => 9000, 'unitOut' => 8000, 'search' => 1, 'om' => 2,
                'opts' => ['cases' => ['in' => 6000, 'out' => 6500, 'search' => 1]]],
            'season' => ['unit' => 'lot', 'free' => 1, 'units' => 2, 'max' => 200, 'baseIn' => 9000, 'baseOut' => 6000, 'unitIn' => 250, 'unitOut' => 80, 'outCap' => 10048, 'om' => 10],
            'sofar' => ['unit' => 'lot', 'free' => 1, 'units' => 2, 'max' => 200, 'baseIn' => 11500, 'baseOut' => 5000, 'unitIn' => 250, 'unitOut' => 80, 'outCap' => 10048, 'om' => 10],
            'compare' => ['unit' => '', 'baseIn' => 7000, 'baseOut' => 3200],
            'realign' => ['unit' => '', 'baseIn' => 7500, 'baseOut' => 3000, 'om' => 10],
            'builder' => ['unit' => 'task', 'free' => 40, 'units' => 31, 'max' => 300, 'baseIn' => 7000, 'baseOut' => 5000, 'unitIn' => 100, 'unitOut' => 10, 'outCap' => 9048],
            'satellite' => ['unit' => '', 'baseIn' => 10000, 'baseOut' => 8000, 'search' => 1, 'om' => 15, 'maps' => 2, 'ee' => 1],
            'skyweather' => ['unit' => '', 'baseIn' => 4500, 'baseOut' => 3000, 'om' => 3],
            'npk' => ['unit' => 'line', 'free' => 10, 'units' => 6, 'max' => 60, 'baseIn' => 7500, 'baseOut' => 6500, 'unitIn' => 50, 'unitOut' => 5, 'search' => 1],
            'npkplan' => ['unit' => 'lot', 'free' => 1, 'units' => 1, 'max' => 8, 'perUnitPrice' => true, 'baseIn' => 4500, 'baseOut' => 4500, 'unitIn' => 1400, 'unitOut' => 600, 'outCap' => 9048, 'om' => 2, 'omUnit' => 1],
            'npkproto' => ['unit' => 'task', 'free' => 10, 'units' => 6, 'max' => 120, 'baseIn' => 4000, 'baseOut' => 4000, 'unitIn' => 60, 'unitOut' => 40, 'outCap' => 8048],
            // 2026-10-28, the season grew: each lot's plan may be written again before it is approved (up to three
            // writes; one more at p90, +13K in and +24K out a lot) and its final season report comes with it
            // (the 'season' kind, +9K in and +6K out a lot). Measured live the same day: one lot's calendar 8.8K in, 9.4K out.
            'auto' => ['unit' => 'lot', 'free' => 1, 'units' => 1, 'max' => 20, 'perUnitPrice' => true, 'baseIn' => 57000, 'baseOut' => 75000, 'unitIn' => 35000, 'unitOut' => 54000,
                'search' => 1, 'om' => 10, 'omUnit' => 2, 'maps' => 1, 'mail' => 150, 'useObserve' => 10, 'useAsk' => 30],
            'autoObserve' => ['unit' => '', 'baseIn' => 11000, 'baseOut' => 4500],
            'autoAsk' => ['unit' => '', 'baseIn' => 12000, 'baseOut' => 2500],
            'skypass' => ['unit' => 'open', 'free' => 0, 'units' => 14, 'max' => 40, 'mapsUnit' => 1, 'unitUsd' => 0.0017],
            'lcc' => ['unit' => 'leaf', 'free' => 10, 'units' => 10, 'max' => 12, 'baseIn' => 4500, 'baseOut' => 3600, 'unitOut' => 150, 'outCap' => 5048],
            'landprep' => ['unit' => 'lot', 'free' => 1, 'units' => 1, 'max' => 12, 'perUnitPrice' => true, 'baseIn' => 7000, 'baseOut' => 4500, 'unitIn' => 400, 'unitOut' => 250, 'outCap' => 8048, 'om' => 3,
                'opts' => ['research' => ['in' => 2600, 'out' => 3800, 'search' => 1]]],
            // 2026-10-18: the Compatibility Checker's online check (anee.io AiPricing twin): one size, a flat 80 whatever the count.
            'compat' => ['unit' => '', 'baseIn' => 9000, 'baseOut' => 8000, 'search' => 1],
            // 2026-10-22: a plan into a season with work (anee.io AiPricing twin): one ask, a unit is a task read.
            'fitcheck' => ['unit' => 'task', 'free' => 40, 'units' => 60, 'max' => 600, 'baseIn' => 9000, 'baseOut' => 4000, 'unitIn' => 110, 'unitOut' => 30, 'outCap' => 18048],
        ],
    ];

    /** How each kind reads on the owner's screen. */
    public const NAMES = [
        'autoObserve' => 'Anee Auto, one Observe (photos read against the lot)',
        'autoAsk' => 'Anee Auto, one Ask',
    ];

    public const UNIT_WORDS = [
        '' => ['run', 'runs'], 'lot' => ['lot', 'lots'], 'crop' => ['crop named', 'crops named'], 'variety' => ['variety', 'varieties'],
        'task' => ['task', 'tasks'], 'line' => ['fertilizer line', 'fertilizer lines'], 'leaf' => ['leaf', 'leaves'], 'open' => ['opening', 'openings'],
    ];

    /** The fields of a kind the owner may edit, in the order the table shows them. */
    public const EDITABLE = ['units', 'baseIn', 'baseOut', 'unitIn', 'unitOut', 'outCap', 'search', 'om', 'omUnit', 'maps', 'mapsUnit', 'mail', 'unitUsd', 'useObserve', 'useAsk'];

    /** @var array<string, mixed>|null */
    private static ?array $memo = null;

    public static function forget(): void
    {
        self::$memo = null;
    }

    /** The owner's numbers over the defaults (the same merge as anee's). */
    public static function settings(): array
    {
        if (self::$memo !== null) {
            return self::$memo;
        }
        $out = self::DEFAULTS;
        $set = json_decode((string) AsSiteSetting::get(self::KEY, ''), true);
        if (is_array($set)) {
            if (array_key_exists('on', $set)) {
                $out['on'] = (bool) $set['on'];
            }
            if (array_key_exists('flatFloor', $set)) {
                $out['flatFloor'] = (bool) $set['flatFloor'];
            }
            foreach (['target', 'minMargin', 'maxMargin', 'usdRate'] as $k) {
                if (isset($set[$k]) && is_numeric($set[$k]) && (float) $set[$k] > 0) {
                    $out[$k] = (float) $set[$k];
                }
            }
            if (isset($set['pesoPerCredit']) && is_numeric($set['pesoPerCredit']) && (float) $set['pesoPerCredit'] > 0) {
                $out['pesoPerCredit'] = (float) $set['pesoPerCredit'];
            }
            foreach ((array) ($set['prices'] ?? []) as $k => $v) {
                if (array_key_exists($k, $out['prices']) && is_numeric($v) && (float) $v >= 0) {
                    $out['prices'][$k] = (float) $v;
                }
            }
            foreach ((array) ($set['kinds'] ?? []) as $kind => $row) {
                if (! isset($out['kinds'][$kind]) || ! is_array($row)) {
                    continue;
                }
                foreach ($row as $k => $v) {
                    if (in_array($k, ['opts', 'unit', 'perUnitPrice', 'unitPrice', 'optPrices'], true)) {
                        continue;
                    }
                    if (is_numeric($v) && (float) $v >= 0) {
                        $out['kinds'][$kind][$k] = (float) $v;
                    }
                }
            }
        }
        $out['minMargin'] = max(1.0, $out['minMargin']);
        $out['target'] = max($out['minMargin'], $out['target']);
        $out['maxMargin'] = max($out['target'], $out['maxMargin']);

        return self::$memo = $out;
    }

    public static function kind(string $kind): ?array
    {
        $k = self::settings()['kinds'][$kind] ?? null;
        if ($k === null) {
            return null;
        }

        return $k + ['unit' => '', 'min' => 1, 'free' => 1, 'units' => 1, 'max' => 1, 'perUnitPrice' => false, 'baseIn' => 0, 'baseOut' => 0, 'unitIn' => 0, 'unitOut' => 0,
            'outCap' => 0, 'search' => 0, 'om' => 0, 'omUnit' => 0, 'maps' => 0, 'mapsUnit' => 0, 'mail' => 0, 'ee' => 0, 'usd' => 0, 'unitUsd' => 0,
            'useObserve' => 0, 'useAsk' => 0, 'opts' => [], 'unitPrice' => null, 'optPrices' => []];
    }

    /** How many times a job option counts: a flag once, a number that many times (anee's AiPricing::times). */
    public static function times(array $size, string $opt): float
    {
        $v = $size[$opt] ?? null;
        if (is_bool($v) || $v === null || $v === '') {
            return ! empty($v) ? 1.0 : 0.0;
        }

        return is_numeric($v) ? max(0.0, (float) $v) : (! empty($v) ? 1.0 : 0.0);
    }

    /** One price of Anee's list (the shelf anee reads), the default when it is not set. */
    public static function listPrice(string $key): int
    {
        static $list = null;
        if ($list === null) {
            $list = \App\Http\Controllers\aniSensoAdmin\AnisystemAiSettingsController::PRICE_DEFAULTS;
            foreach ((array) (json_decode((string) AsSiteSetting::get('ai.prices', ''), true) ?: []) as $k => $v) {
                if (isset($list[$k]) && is_numeric($v) && (int) $v >= 1) {
                    $list[$k] = (int) $v;
                }
            }
        }

        return (int) ($list[$key] ?? 0);
    }

    /** The list prices alone, before the owner's overrides: [USD per million in, per million out, per search query]. */
    public static function familyRates(?string $provider, ?string $model): array
    {
        $table = self::MODEL_RATES[$provider] ?? self::MODEL_RATES['gemini'];
        $m = strtolower((string) $model);
        $rate = $table['*'];
        foreach ($table as $family => $r) {
            if ($family !== '*' && str_contains($m, $family)) {
                $rate = $r;
                break;
            }
        }

        return [$rate[0], $rate[1], self::SEARCH_FEE[$provider] ?? 0.014];
    }

    /** [USD per million in, per million out, per search query] for a provider and model, the owner's over the list. */
    public static function modelRates(?string $provider, ?string $model): array
    {
        $p = self::settings()['prices'];
        $rate = self::familyRates($provider, $model);

        return [
            $p['modelIn'] !== null ? (float) $p['modelIn'] : $rate[0],
            $p['modelOut'] !== null ? (float) $p['modelOut'] : $rate[1],
            $p['searchQuery'] !== null ? (float) $p['searchQuery'] : $rate[2],
        ];
    }

    /**
     * Every way a credit is bought, in pesos per credit. anee.io counts the
     * dollar packs only while its international face is open (its
     * INTERNATIONAL_OPEN); this screen cannot see that switch, so it lists
     * them apart and the floor here is the peso packs, as anee's is today.
     */
    public static function channels(): array
    {
        $rate = (float) self::settings()['usdRate'];
        $out = [];
        $packs = DB::table('anisystem_ai_credit_packs')->where('deleteStatus', 1)->where('isActive', 1)->orderBy('sortOrder')->get();
        $usd = json_decode((string) AsSiteSetting::get('prices.usd', ''), true) ?: [];
        $usdPacks = (array) ($usd['packs'] ?? []) + ['starter' => 1.99, 'farmer' => 8.99, 'season' => 17.99];
        foreach ($packs as $p) {
            if ((int) $p->credits <= 0) {
                continue;
            }
            $out[] = ['channel' => $p->packName . ' pack, ₱' . number_format((float) $p->price, 0) . ' for ' . number_format((int) $p->credits),
                'pesoPerCredit' => round((float) $p->price / (int) $p->credits, 4), 'counts' => true, 'note' => 'Sold in the Philippines'];
            $dollars = $usdPacks[$p->packKey] ?? null;
            if (is_numeric($dollars) && (float) $dollars > 0) {
                $out[] = ['channel' => $p->packName . ' pack abroad, $' . rtrim(rtrim(number_format((float) $dollars, 2), '0'), '.') . ' for ' . number_format((int) $p->credits),
                    'pesoPerCredit' => round((float) $dollars * $rate / (int) $p->credits, 4), 'counts' => false, 'abroad' => true,
                    'note' => 'Counts only while anee.io sells abroad (closed for maintenance now)'];
            }
        }
        foreach (['Solo Farmer' => [200, 30], 'Farm Owner' => [600, 100]] as $name => [$peso, $credits]) {
            $out[] = ['channel' => $name . ' plan, ₱' . $peso . ' a month with ' . $credits . ' credits', 'pesoPerCredit' => round($peso / $credits, 4), 'counts' => false,
                'note' => 'Bundled with the plan, valued at the whole plan price'];
        }
        $signup = (int) (DB::table('anisystem_ai_settings')->orderByDesc('id')->value('freeCreditsOnSignup') ?? 0);
        $out[] = ['channel' => 'Sign up gift (' . $signup . '), Libre starter (20), admin grants', 'pesoPerCredit' => 0.0, 'counts' => false, 'note' => 'Free, so no margin can hold on them'];

        return $out;
    }

    /** The floor anee.io prices at: the owner's override, else the cheapest peso pack. */
    public static function pesoPerCredit(?array $channels = null): float
    {
        $set = self::settings()['pesoPerCredit'];
        if ($set !== null && (float) $set > 0) {
            return (float) $set;
        }
        $counted = array_filter($channels ?? self::channels(), fn ($c) => $c['counts'] && $c['pesoPerCredit'] > 0);

        return $counted ? (float) min(array_column($counted, 'pesoPerCredit')) : self::FALLBACK_PESO_PER_CREDIT;
    }

    /** The floor if anee.io sold abroad again with today's dollar packs. */
    public static function pesoPerCreditAbroad(?array $channels = null): float
    {
        $all = array_filter($channels ?? self::channels(), fn ($c) => ($c['counts'] || ! empty($c['abroad'])) && $c['pesoPerCredit'] > 0);

        return $all ? (float) min(array_column($all, 'pesoPerCredit')) : self::FALLBACK_PESO_PER_CREDIT;
    }

    /** Openings per week pass at p90 from the passes that ended (ten or more), else the model's number. */
    public static function skypassOpens(): int
    {
        $default = (int) (self::kind('skypass')['units'] ?? 14);
        try {
            if (! Schema::hasTable('as_sky_passes')) {
                return $default;
            }
            $opens = DB::table('as_sky_passes')->where('deleteStatus', 1)->where('endsAt', '<', now())->orderBy('opens')->pluck('opens')->map(fn ($o) => (int) $o)->all();

            return count($opens) < 10 ? $default : max(1, $opens[(int) floor(0.9 * (count($opens) - 1))]);
        } catch (\Throwable $e) {
            return $default;
        }
    }

    public static function units(string $kind, array $size = []): int
    {
        $k = self::kind($kind);
        if (! $k || $k['unit'] === '') {
            return 1;
        }
        $u = $size['units'] ?? null;
        if ($u === null) {
            $u = $kind === 'skypass' ? self::skypassOpens() : $k['units'];
        }
        $max = (int) $k['max'] > 0 ? (int) $k['max'] : PHP_INT_MAX;

        return max((int) $k['min'], min($max, (int) ceil((float) $u)));
    }

    /** What a run of this size costs the house at p90, in pesos (anee's AiPricing::cost). */
    public static function cost(string $kind, array $size, ?string $provider, ?string $model): array
    {
        $k = self::kind($kind);
        $s = self::settings();
        $p = $s['prices'];
        $rate = (float) $s['usdRate'];
        [$pin, $pout, $fee] = self::modelRates($provider, $model);
        $u = self::units($kind, $size);
        $extra = max(0, $u - (int) $k['free']);
        $in = $k['baseIn'] + $k['unitIn'] * $extra;
        $out = $k['baseOut'] + $k['unitOut'] * $extra;
        if ($k['outCap'] > 0) {
            $out = min($out, max($k['baseOut'], $k['outCap']));
        }
        $search = (float) $k['search'];
        foreach ((array) $k['opts'] as $opt => $add) {
            $n = self::times($size, (string) $opt);
            if ($n > 0) {
                $in += (float) ($add['in'] ?? 0) * $n;
                $out += (float) ($add['out'] ?? 0) * $n;
                $search += (float) ($add['search'] ?? 0) * $n;
            }
        }
        $useUsd = 0.0;
        foreach (['autoObserve' => $k['useObserve'], 'autoAsk' => $k['useAsk']] as $sub => $n) {
            $sk = $n > 0 ? self::kind($sub) : null;
            if ($sk) {
                $useUsd += $n * $u * (($sk['baseIn'] * $pin + $sk['baseOut'] * $pout) / 1e6);
            }
        }
        $aiUsd = ($in * $pin + $out * $pout) / 1e6 + $useUsd;
        $searchUsd = $search * (float) $p['queriesPerSearch'] * $fee;
        $thirdUsd = ($k['om'] + $k['omUnit'] * $u) * (float) $p['openMeteoCall'] + ($k['maps'] + $k['mapsUnit'] * $u) * (float) $p['mapsLoad']
            + $k['mail'] * (float) $p['emailSend'] + $k['ee'] * (float) $p['earthEngineRun'] + $k['usd'] + $k['unitUsd'] * $u;

        return ['units' => $u, 'tokensIn' => (int) round($in), 'tokensOut' => (int) round($out), 'searches' => $search,
            'aiPhp' => round($aiUsd * $rate, 2), 'searchPhp' => round($searchUsd * $rate, 2), 'thirdPhp' => round($thirdUsd * $rate, 2),
            'costPhp' => round(($aiUsd + $searchUsd + $thirdUsd) * $rate, 2)];
    }

    public static function friendly(int $credits): int
    {
        if ($credits <= 0) {
            return 0;
        }
        $step = $credits < 100 ? 5 : ($credits < 1000 ? 10 : 50);

        return (int) (ceil($credits / $step) * $step);
    }

    /** The price of a run of this size (anee's AiPricing::quote), given today's flat price for one unit. */
    public static function quote(string $kind, array $size, int $flatOne, ?string $provider, ?string $model, ?float $ppc = null): array
    {
        $s = self::settings();
        $k = self::kind($kind);
        $c = self::cost($kind, $size, $provider, $model);
        $ppc ??= self::pesoPerCredit();
        $flat = $flatOne * (! empty($k['perUnitPrice']) ? $c['units'] : 1);
        // A list price for every unit past the free ones, and for every counted option (the Crop Protocol's lots and crops).
        if (! empty($k['unitPrice'])) {
            $flat += self::listPrice((string) $k['unitPrice']) * max(0, $c['units'] - max((int) $k['min'], (int) $k['free']));
        }
        foreach ((array) $k['optPrices'] as $opt => $key) {
            $flat += (int) round(self::listPrice((string) $key) * self::times($size, (string) $opt));
        }
        $engine = $c['costPhp'] > 0 ? self::friendly((int) ceil($s['target'] * $c['costPhp'] / $ppc - 1e-9)) : 0;
        $credits = ! $s['on'] ? $flat : (! empty($s['flatFloor']) ? max($flat, $engine) : ($engine > 0 ? $engine : $flat));

        return $c + ['credits' => (int) $credits, 'flat' => (int) $flat, 'engine' => $engine,
            'margin' => $c['costPhp'] > 0 ? round($credits * $ppc / $c['costPhp'], 2) : 0.0,
            'flatMargin' => $c['costPhp'] > 0 ? round($flat * $ppc / $c['costPhp'], 2) : 0.0];
    }

    /** What a finished run cost the house, in pesos, from its ledger row. */
    public static function runCost(?string $provider, ?string $model, int $tokensIn, int $tokensOut, bool $searched): float
    {
        $s = self::settings();
        [$pin, $pout, $fee] = self::modelRates($provider, $model);
        $usd = ($tokensIn * $pin + $tokensOut * $pout) / 1e6 + ($searched ? (float) $s['prices']['queriesPerSearch'] * $fee : 0.0);

        return round($usd * (float) $s['usdRate'], 2);
    }
}
