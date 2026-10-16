{{-- ============================================ Pricing engine ======== --}}
{{-- 2026-10-15: the owner's lever over anee.io's credit prices. anee prices
     every paid tool by App\Support\AiPricing (cost at p90 times the target
     margin, over the cheapest a credit sells for, never under today's flat
     price); this tab reads the same numbers through App\Support\AiPricingModel
     and saves them to the shelf (as_site_settings `ai.pricing`). The table
     recomputes in the browser as the numbers are edited; nothing reaches
     anee.io until Save. --}}
@php
    $P = $pricing ?? null;
    $peBadge = function ($m) use ($P) {
        if ($m === null) {
            return '<span class="text-muted">no runs</span>';
        }
        $s = $P['settings'];
        $cls = $m < $s['minMargin'] ? 'bg-danger' : ($m > $s['maxMargin'] ? 'bg-info' : 'bg-success');
        $say = $m < $s['minMargin'] ? 'under' : ($m > $s['maxMargin'] ? 'over' : 'in range');

        return '<span class="badge ' . $cls . '" title="' . $say . '">' . number_format($m, 1) . 'x</span>';
    };
    $peFieldWords = [
        'units' => 'Typical size',
        'baseIn' => 'Tokens in at p90',
        'baseOut' => 'Tokens out at p90 (thinking included)',
        'unitIn' => 'More tokens in per extra unit',
        'unitOut' => 'More tokens out per extra unit',
        'outCap' => 'Most tokens out (the cap)',
        'search' => 'Web searched calls per run',
        'om' => 'Open-Meteo calls per run',
        'omUnit' => 'Open-Meteo calls per unit',
        'maps' => 'Google map loads per run',
        'mapsUnit' => 'Google map loads per unit',
        'mail' => 'Mails per run',
        'unitUsd' => 'Other fees per unit ($)',
        'useObserve' => 'Observes per lot in a season',
        'useAsk' => 'Asks per lot in a season',
    ];
@endphp
<style>
    .form-control.pe-num { width: 7rem; }
    .pe-kind-name { font-weight: 600; }
    .pe-sub { font-size: 11.5px; color: #74788d; }
    .pe-q { white-space: nowrap; font-size: 12.5px; }
    .pe-q b { font-size: 13.5px; }
    .pe-q .badge { margin-left: 4px; }
    .pe-q.is-raised b { color: #c2570c; }
    .pe-edit-box { background: #f8f9fa; border: 1px solid #eff2f7; border-radius: 6px; padding: 12px 14px; margin: 4px 0 8px; }
    .pe-flag { font-size: 11.5px; }
    [data-bs-theme="dark"] .pe-edit-box { background: rgba(255, 255, 255, .03); border-color: rgba(255, 255, 255, .08); }
    [data-bs-theme="dark"] .pe-sub { color: #a6b0cf; }
    [data-bs-theme="dark"] .pe-q.is-raised b { color: #f6a35b; }
    .pe-savebar { position: sticky; bottom: 0; z-index: 3; background: var(--bs-body-bg, #fff); border-top: 1px solid var(--bs-border-color, #eff2f7); padding: 10px 0; }
    @media (prefers-reduced-motion: reduce) { #aiPricingTab .collapsing { transition: none; } }
</style>

<div class="tab-pane" id="aiPricingTab" role="tabpanel">
@if (! $P)
    <div class="alert alert-warning">The pricing table could not be read just now. The prices on anee.io are unaffected. Reload the page to try again.</div>
@else
    @php $S = $P['settings']; @endphp
    <form id="peForm" autocomplete="off">
    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-1">How every price keeps its margin</h5>
            <p class="text-secondary mb-3">Each paid tool is priced from what one run really costs the house at the 90th percentile, a heavy run and not the average: Anee's tokens in and out, her web searches, and the other services (Open-Meteo weather, Google map loads, mail). The price is the target margin times that cost, counted in credits at the cheapest a credit is sold, rounded up to fives and tens. Since 2026-10-16 the target is 5x between a 4x floor and a 6x ceiling, and the engine sets the price itself, under today's flat price too. Switch on "Flat prices are a floor" to go back to the old rule, where the engine never lowered a price. A real run that comes in under the floor is written to anee.io's log. Farmers never see any of this, only the price.</p>
            <div class="row g-3 align-items-end mb-3">
                <div class="col-auto">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" id="peOn" {{ $S['on'] ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="peOn">Engine on</label>
                    </div>
                    <div class="pe-sub">Off: every tool sells at its flat price</div>
                </div>
                <div class="col-auto">
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" id="peFloor" {{ ! empty($S['flatFloor']) ? 'checked' : '' }}>
                        <label class="form-check-label fw-semibold" for="peFloor">Flat prices are a floor</label>
                    </div>
                    <div class="pe-sub">Off: the engine may price under the flat price</div>
                </div>
                <div class="col-auto">
                    <label class="form-label font-size-12 mb-1">Target margin (x)</label>
                    <input type="number" class="form-control form-control-sm pe-num js-pe-top" id="peTarget" value="{{ $S['target'] }}" min="1" max="50" step="0.5">
                </div>
                <div class="col-auto">
                    <label class="form-label font-size-12 mb-1">Floor (x), never under</label>
                    <input type="number" class="form-control form-control-sm pe-num js-pe-top" id="peMin" value="{{ $S['minMargin'] }}" min="1" max="50" step="0.5">
                </div>
                <div class="col-auto">
                    <label class="form-label font-size-12 mb-1">Ceiling (x), marked above</label>
                    <input type="number" class="form-control form-control-sm pe-num js-pe-top" id="peMax" value="{{ $S['maxMargin'] }}" min="1" max="100" step="0.5">
                </div>
                <div class="col-auto">
                    <label class="form-label font-size-12 mb-1">Pesos per credit</label>
                    <input type="number" class="form-control form-control-sm pe-num js-pe-top" id="pePpc" value="{{ $S['pesoPerCredit'] }}" placeholder="{{ number_format($P['ppc'], 3) }}" min="0.01" step="0.001">
                    <div class="pe-sub">Blank: the cheapest pack</div>
                </div>
                <div class="col-auto">
                    <label class="form-label font-size-12 mb-1">Pesos per dollar</label>
                    <input type="number" class="form-control form-control-sm pe-num js-pe-top" id="peRate" value="{{ $S['usdRate'] }}" min="1" max="1000" step="0.5">
                </div>
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6 col-lg-3"><div class="stat-tile"><div class="label">Credit floor today</div><div class="value" id="pePpcShown">₱{{ number_format($S['pesoPerCredit'] ?: $P['ppc'], 3) }}</div><div class="pe-sub">{{ $S['pesoPerCredit'] ? 'your number' : 'the cheapest peso pack' }}</div></div></div>
                <div class="col-6 col-lg-3"><div class="stat-tile"><div class="label">If anee.io sells abroad again</div><div class="value">₱{{ number_format($P['ppcAbroad'], 3) }}</div><div class="pe-sub">today's dollar packs set the floor then</div></div></div>
                <div class="col-6 col-lg-3"><div class="stat-tile"><div class="label">Anee's model</div><div class="value" style="font-size:15px">{{ $P['model'] }}</div><div class="pe-sub">${{ rtrim(rtrim(number_format($P['rates']['in'], 2), '0'), '.') }} in, ${{ rtrim(rtrim(number_format($P['rates']['out'], 2), '0'), '.') }} out per million tokens</div></div></div>
                <div class="col-6 col-lg-3"><div class="stat-tile"><div class="label">Web search</div><div class="value">${{ rtrim(rtrim(number_format($P['rates']['search'], 4), '0'), '.') }}</div><div class="pe-sub">a query, {{ (int) $S['prices']['queriesPerSearch'] }} queries a searched call</div></div></div>
            </div>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Where a credit comes from</th><th>Pesos per credit</th><th>Sets the floor</th><th>Note</th></tr></thead>
                    <tbody>
                    @foreach ($P['channels'] as $c)
                        <tr>
                            <td>{{ $c['channel'] }}</td>
                            <td class="text-nowrap">₱{{ number_format($c['pesoPerCredit'], 3) }}</td>
                            <td>{!! $c['counts'] ? '<span class="badge bg-success">yes</span>' : '<span class="text-muted">no</span>' !!}</td>
                            <td class="pe-sub">{{ $c['note'] }}</td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-1">Unit prices</h5>
            <p class="text-secondary mb-3">List prices in US dollars. Blank model and search prices follow the list for the model Anee uses now ({{ $P['provider'] }}, {{ $P['model'] }}). Open-Meteo is the Professional plan, $99 a month, spread over the weighted calls a month you expect. A Google map load is billed past the free 10,000 a month.</p>
            <div class="row g-3">
                @foreach ([
                    'modelIn' => ['Tokens in, $ per million', $P['family'][0]],
                    'modelOut' => ['Tokens out, $ per million', $P['family'][1]],
                    'searchQuery' => ['Web search, $ per query', $P['family'][2]],
                    'queriesPerSearch' => ['Queries per searched call (p90)', null],
                    'openMeteoCall' => ['Open-Meteo, $ per weighted call', null],
                    'mapsLoad' => ['Google map load, $', null],
                    'emailSend' => ['One mail (Resend), $', null],
                    'earthEngineRun' => ['Earth Engine, $ per field read', null],
                ] as $k => [$label, $list])
                    <div class="col-6 col-md-4 col-xl-3">
                        <label class="form-label font-size-12 mb-1">{{ $label }}</label>
                        <input type="number" class="form-control form-control-sm js-pe-price" data-key="{{ $k }}" value="{{ $S['prices'][$k] }}" @if ($list !== null) placeholder="{{ $list }} (list)" @endif min="0" step="any">
                    </div>
                @endforeach
            </div>
            <p class="pe-sub mt-2 mb-0">Earth Engine stays at 0 while Microsoft Planetary Computer (free) serves the satellite reads. When the Cloud Run service is switched on, set the EECU time plus the $500 a month platform fee spread over the reads you expect.</p>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-1">Every tool, priced by the size of the job</h5>
            <p class="text-secondary mb-3">For each tool: what a heavy run costs at its typical size, today's flat price, and the engine's price at 1, 5, 20 and 50 units of the job (lots, varieties, leaves and so on, within what the tool allows), each with the margin it keeps at the credit floor. <span class="badge bg-danger">under</span> the floor, <span class="badge bg-success">in range</span>, <span class="badge bg-info">over</span> the ceiling. A price in orange is raised above today's. Open a tool's model to change its numbers.</p>
            <div class="table-responsive">
                <table class="table align-middle mb-0" id="peKinds">
                    <thead>
                        <tr>
                            <th>Tool</th>
                            <th class="text-nowrap">Heavy run cost</th>
                            <th class="text-nowrap">Today</th>
                            <th class="text-nowrap">1</th>
                            <th class="text-nowrap">5</th>
                            <th class="text-nowrap">20</th>
                            <th class="text-nowrap">50</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody>
                    @foreach ($P['kinds'] as $kind => $k)
                        @php $m = $k['model']; $hasUnit = $m['unit'] !== ''; @endphp
                        <tr data-kind="{{ $kind }}">
                            <td>
                                <div class="pe-kind-name">{{ $k['name'] }}</div>
                                <div class="pe-sub">{{ $hasUnit ? 'by ' . $k['unitWords'][1] . ', typical ' . ($kind === 'skypass' ? $P['skypassOpens'] : (int) $m['units']) . ($k['perUnitPrice'] ? ', today priced per ' . $k['unitWords'][0] : '') : 'one size' }}</div>
                            </td>
                            <td class="text-nowrap js-pe-cost">₱{{ number_format($k['typical']['costPhp'], 2) }}</td>
                            <td class="text-nowrap js-pe-flat">{!! $k['typical']['flat'] > 0 ? e(number_format($k['typical']['flat'])) . ' ' . $peBadge($k['typical']['flatMargin']) : '<span class="text-muted">not sold alone</span>' !!}</td>
                            @for ($i = 0; $i < 4; $i++)
                                <td class="pe-q js-pe-size" data-i="{{ $i }}">
                                    @if (isset($k['sizes'][$i]))
                                        @php $q = $k['sizes'][$i]; @endphp
                                        <span class="{{ $q['credits'] > $q['flat'] ? 'is-raised' : '' }}"><b>{{ number_format($q['credits']) }}</b>{!! $peBadge($q['margin']) !!}</span>
                                        @if ($hasUnit && $q['units'] != [1, 5, 20, 50][$i])<div class="pe-sub">at {{ $q['units'] }}</div>@endif
                                    @else
                                        <span class="text-muted">same</span>
                                    @endif
                                </td>
                            @endfor
                            <td class="text-end"><button type="button" class="btn btn-light btn-sm js-pe-open" data-bs-toggle="collapse" data-bs-target="#peEdit-{{ $kind }}" aria-expanded="false">Model</button></td>
                        </tr>
                        <tr class="pe-edit-row" data-kind-edit="{{ $kind }}">
                            <td colspan="8" class="p-0 border-0">
                                <div class="collapse" id="peEdit-{{ $kind }}">
                                    <div class="pe-edit-box mx-2">
                                        <div class="row g-2">
                                            @foreach ($P['editable'] as $f)
                                                @php
                                                    $show = in_array($f, ['baseIn', 'baseOut', 'search', 'om'], true)
                                                        || ($hasUnit && in_array($f, ['units', 'unitIn', 'unitOut', 'outCap', 'omUnit'], true))
                                                        || (float) ($P['defaults']['kinds'][$kind][$f] ?? 0) > 0;
                                                @endphp
                                                @if ($show)
                                                    <div class="col-6 col-md-4 col-xl-2">
                                                        <label class="form-label font-size-12 mb-1">{{ $peFieldWords[$f] ?? $f }}</label>
                                                        <input type="number" class="form-control form-control-sm js-pe-field" data-kind="{{ $kind }}" data-field="{{ $f }}" value="{{ $m[$f] ?? 0 }}" min="0" step="any">
                                                    </div>
                                                @endif
                                            @endforeach
                                        </div>
                                        @foreach ($k['opts'] as $opt => $oq)
                                            <div class="pe-sub mt-2">{{ ['research' => 'With the variety lookup (a variety not on anee\'s list), one lot', 'variety' => 'With a variety named (the research leads with it)'][$opt] ?? $opt }}: <b class="js-pe-opt" data-opt="{{ $opt }}">{{ number_format($oq['credits']) }} credits</b>, cost ₱<span class="js-pe-opt-cost" data-opt="{{ $opt }}">{{ number_format($oq['costPhp'], 2) }}</span>. Its own numbers live in anee's code.</div>
                                        @endforeach
                                        @if ($kind === 'skypass')
                                            <div class="pe-sub mt-2">The typical size here is the openings a pass sees at p90. Once ten passes have ended anee.io reads it from them ({{ $P['skypassOpens'] }} now). A pass allows up to 40 openings.</div>
                                        @endif
                                        @if ($kind === 'auto')
                                            <div class="pe-sub mt-2">Setup is Anee's reading plus one calendar per lot. The season's own Observe and Ask calls are counted at the numbers above, at their own costs (the two rows under this one). The daily fair use allows far more than that, so a farm that uses it fully runs at a loss on today's price.</div>
                                        @endif
                                    </div>
                                </div>
                            </td>
                        </tr>
                    @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <h5 class="card-title mb-1">What the runs really cost (last 90 days)</h5>
            <p class="text-secondary mb-3">Every finished run of anee.io's paid tools, from its own ledger, at the unit prices above and the credit floor. A failed run writes no row, so its tokens are not here. When a tool's real p90 is above the model's, raise the model's tokens.</p>
            <div class="table-responsive">
                <table class="table table-sm align-middle mb-0">
                    <thead><tr><th>Tool</th><th>Runs</th><th>Searched</th><th>Median cost</th><th>p90 cost</th><th>Model's heavy run</th><th>Charged</th><th>Median margin</th><th>Lowest margin</th><th>Under floor</th></tr></thead>
                    <tbody>
                    @php $anyRuns = false; @endphp
                    @foreach ($P['kinds'] as $kind => $k)
                        @if ($k['measured'])
                            @php
                                $mm = $k['measured'];
                                $anyRuns = true;
                                // The model's heaviest run: its typical size, or a variant (a named variety, the variety lookup) when dearer.
                                $heavy = max(array_merge([$k['typical']['costPhp']], array_map(fn ($o) => $o['costPhp'], array_values($k['opts']))));
                            @endphp
                            <tr>
                                <td>{{ $k['name'] }}</td>
                                <td>{{ $mm['runs'] }}</td>
                                <td>{{ $mm['searched'] }}</td>
                                <td class="text-nowrap">₱{{ number_format((float) $mm['median'], 2) }}</td>
                                <td class="text-nowrap">₱{{ number_format((float) $mm['p90'], 2) }}</td>
                                <td class="text-nowrap">₱{{ number_format($heavy, 2) }}@if ((float) $mm['p90'] > 1.1 * $heavy) <span class="badge bg-warning text-dark pe-flag">model is low</span>@endif</td>
                                <td>{{ $mm['charged'] !== null ? number_format((float) $mm['charged']) : 'free' }}</td>
                                <td>{!! $peBadge($mm['marginMedian']) !!}</td>
                                <td>{!! $peBadge($mm['marginLow']) !!}</td>
                                <td>{!! $mm['under'] ? '<span class="badge bg-danger">' . $mm['under'] . '</span>' : '0' !!}</td>
                            </tr>
                        @endif
                    @endforeach
                    @if (! $anyRuns)
                        <tr><td colspan="10" class="text-muted">No paid runs in the last 90 days.</td></tr>
                    @endif
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            @php $C = $P['chat']; @endphp
            <h5 class="card-title mb-1">The metered chat</h5>
            <p class="text-secondary mb-3">The chat is charged by the token at the rates on the Settings tab, so its margin is a ratio: what a thousand tokens sell for at the credit floor over what they cost. Photos are charged their own credits on top of their tokens.</p>
            <div class="row g-2">
                <div class="col-6 col-lg-3"><div class="stat-tile"><div class="label">Per thousand in</div><div class="value">{{ rtrim(rtrim(number_format($C['perInputK'], 2), '0'), '.') }} credits {!! $peBadge($C['inMargin']) !!}</div><div class="pe-sub">holds the floor from {{ $C['okInputK'] }}</div></div></div>
                <div class="col-6 col-lg-3"><div class="stat-tile"><div class="label">Per thousand out</div><div class="value">{{ rtrim(rtrim(number_format($C['perOutputK'], 2), '0'), '.') }} credits {!! $peBadge($C['outMargin']) !!}</div><div class="pe-sub">holds the floor from {{ $C['okOutputK'] }}</div></div></div>
                <div class="col-6 col-lg-3"><div class="stat-tile"><div class="label">Last 90 days</div><div class="value">{{ number_format($C['answers']) }} answers</div><div class="pe-sub">{{ number_format($C['tokensIn']) }} in, {{ number_format($C['tokensOut']) }} out, {{ number_format($C['credits']) }} credits</div></div></div>
                <div class="col-6 col-lg-3"><div class="stat-tile"><div class="label">Cost and margin</div><div class="value">₱{{ number_format($C['cost'], 2) }} {!! $peBadge($C['margin']) !!}</div><div class="pe-sub">the personal chat (the team chat keeps no token counts)</div></div></div>
            </div>
        </div>
    </div>

    <div class="pe-savebar d-flex flex-wrap gap-2 align-items-center">
        <button type="submit" class="btn btn-primary" id="peSave"><i class="mdi mdi-content-save"></i> Save the pricing engine</button>
        <button type="button" class="btn btn-light" id="peDefaults">Fill in the defaults</button>
        <span class="pe-sub">The table above follows your edits. anee.io quotes by them after Save.</span>
    </div>
    </form>
@endif
</div>{{-- /#aiPricingTab --}}

@if ($P)
@php
    $peJs = [
        'settings' => $P['settings'], 'defaults' => $P['defaults'], 'ppc' => $P['ppc'], 'family' => $P['family'],
        'flat' => array_map(fn ($k) => $k['flat'], $P['kinds']),
        'skypassOpens' => $P['skypassOpens'],
        'save' => route('anisenso-ai-settings.pricing'), 'csrf' => csrf_token(),
    ];
@endphp
<script>
document.addEventListener('DOMContentLoaded', function () {
    /* The engine's arithmetic, the same as anee's App\Support\AiPricing::cost/quote. */
    const PE = @json($peJs);
    const form = document.getElementById('peForm');
    if (!form) return;
    const $ = (s, r) => (r || document).querySelector(s);
    const $$ = (s, r) => Array.from((r || document).querySelectorAll(s));
    const numOr = (v, d) => { const n = parseFloat(v); return Number.isFinite(n) ? n : d; };
    const esc = (s) => String(s).replace(/[&<>"]/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;' }[c]));

    function read() {
        const s = {
            on: $('#peOn').checked,
            flatFloor: $('#peFloor').checked,
            target: numOr($('#peTarget').value, 5), minMargin: numOr($('#peMin').value, 4), maxMargin: numOr($('#peMax').value, 6),
            pesoPerCredit: $('#pePpc').value === '' ? null : numOr($('#pePpc').value, null), usdRate: numOr($('#peRate').value, 58),
            prices: {}, kinds: {},
        };
        $$('.js-pe-price').forEach((el) => { s.prices[el.dataset.key] = el.value === '' ? null : numOr(el.value, null); });
        Object.keys(PE.settings.kinds).forEach((k) => { s.kinds[k] = Object.assign({}, PE.settings.kinds[k]); });
        $$('.js-pe-field').forEach((el) => { s.kinds[el.dataset.kind][el.dataset.field] = el.value === '' ? 0 : numOr(el.value, 0); });
        s.minMargin = Math.max(1, s.minMargin);
        s.target = Math.max(s.minMargin, s.target);
        s.maxMargin = Math.max(s.target, s.maxMargin);
        return s;
    }
    const kindOf = (s, kind) => Object.assign({ unit: '', min: 1, free: 1, units: 1, max: 1, perUnitPrice: false, baseIn: 0, baseOut: 0, unitIn: 0, unitOut: 0, outCap: 0,
        search: 0, om: 0, omUnit: 0, maps: 0, mapsUnit: 0, mail: 0, ee: 0, usd: 0, unitUsd: 0, useObserve: 0, useAsk: 0, opts: {} }, s.kinds[kind] || {});
    function rates(s) {
        return [s.prices.modelIn ?? PE.family[0], s.prices.modelOut ?? PE.family[1], s.prices.searchQuery ?? PE.family[2]];
    }
    function cost(s, kind, size) {
        const k = kindOf(s, kind), p = s.prices, [pin, pout, fee] = rates(s);
        let u = 1;
        if (k.unit !== '') {
            u = size.units ?? (kind === 'skypass' ? PE.skypassOpens : k.units);
            u = Math.max(k.min, Math.min(k.max > 0 ? k.max : 1e9, Math.ceil(u)));
        }
        const extra = Math.max(0, u - k.free);
        let tin = k.baseIn + k.unitIn * extra, tout = k.baseOut + k.unitOut * extra, search = k.search;
        if (k.outCap > 0) tout = Math.min(tout, Math.max(k.baseOut, k.outCap));
        Object.keys(k.opts || {}).forEach((o) => { if (size[o]) { tin += k.opts[o].in || 0; tout += k.opts[o].out || 0; search += k.opts[o].search || 0; } });
        let use = 0;
        [['autoObserve', k.useObserve], ['autoAsk', k.useAsk]].forEach(([sub, n]) => {
            if (n > 0) { const sk = kindOf(s, sub); use += n * u * ((sk.baseIn * pin + sk.baseOut * pout) / 1e6); }
        });
        const usd = (tin * pin + tout * pout) / 1e6 + use + search * (p.queriesPerSearch ?? 8) * fee
            + (k.om + k.omUnit * u) * (p.openMeteoCall ?? 0) + (k.maps + k.mapsUnit * u) * (p.mapsLoad ?? 0)
            + k.mail * (p.emailSend ?? 0) + k.ee * (p.earthEngineRun ?? 0) + k.usd + k.unitUsd * u;
        return { units: u, costPhp: Math.round(usd * s.usdRate * 100) / 100 };
    }
    const friendly = (n) => n <= 0 ? 0 : Math.ceil(n / (n < 100 ? 5 : (n < 1000 ? 10 : 50))) * (n < 100 ? 5 : (n < 1000 ? 10 : 50));
    function quote(s, kind, size) {
        const k = kindOf(s, kind), c = cost(s, kind, size), ppc = s.pesoPerCredit || PE.ppc;
        const flat = (PE.flat[kind] || 0) * (k.perUnitPrice ? c.units : 1);
        const engine = c.costPhp > 0 ? friendly(Math.ceil(s.target * c.costPhp / ppc - 1e-9)) : 0;
        const credits = !s.on ? flat : (s.flatFloor ? Math.max(flat, engine) : (engine > 0 ? engine : flat));
        return { units: c.units, costPhp: c.costPhp, flat, credits, margin: c.costPhp > 0 ? credits * ppc / c.costPhp : null, flatMargin: c.costPhp > 0 ? flat * ppc / c.costPhp : null };
    }
    function badge(s, m) {
        if (m === null) return '<span class="text-muted">no runs</span>';
        const cls = m < s.minMargin ? 'bg-danger' : (m > s.maxMargin ? 'bg-info' : 'bg-success');
        return ' <span class="badge ' + cls + '">' + m.toFixed(1) + 'x</span>';
    }
    function paint() {
        const s = read();
        $('#pePpcShown').textContent = '₱' + (s.pesoPerCredit || PE.ppc).toFixed(3);
        $$('#peKinds tr[data-kind]').forEach((tr) => {
            const kind = tr.dataset.kind, k = kindOf(s, kind);
            const t = quote(s, kind, {});
            tr.querySelector('.js-pe-cost').textContent = '₱' + t.costPhp.toFixed(2);
            tr.querySelector('.js-pe-flat').innerHTML = t.flat > 0 ? esc(t.flat.toLocaleString()) + badge(s, t.flatMargin) : '<span class="text-muted">not sold alone</span>';
            const sizes = k.unit === '' ? [1] : [1, 5, 20, 50].map((n) => Math.max(k.min, Math.min(k.max > 0 ? k.max : 999, n))).filter((n, i, a) => a.indexOf(n) === i);
            tr.querySelectorAll('.js-pe-size').forEach((td) => {
                const i = Number(td.dataset.i), n = sizes[i];
                if (n === undefined) { td.innerHTML = '<span class="text-muted">same</span>'; return; }
                const q = quote(s, kind, { units: n });
                td.innerHTML = '<span class="' + (q.credits > q.flat ? 'is-raised' : '') + '"><b>' + q.credits.toLocaleString() + '</b>' + badge(s, q.margin) + '</span>'
                    + (k.unit !== '' && n !== [1, 5, 20, 50][i] ? '<div class="pe-sub">at ' + n + '</div>' : '');
            });
            document.querySelectorAll('[data-kind-edit="' + kind + '"] .js-pe-opt').forEach((b) => {
                const q = quote(s, kind, { units: 1, [b.dataset.opt]: true });
                b.textContent = q.credits.toLocaleString() + ' credits';
                const c = document.querySelector('[data-kind-edit="' + kind + '"] .js-pe-opt-cost[data-opt="' + b.dataset.opt + '"]');
                if (c) c.textContent = q.costPhp.toFixed(2);
            });
        });
    }
    form.addEventListener('input', paint);
    form.addEventListener('change', paint);

    $('#peDefaults').addEventListener('click', () => {
        const d = PE.defaults;
        $('#peOn').checked = !!d.on;
        $('#peFloor').checked = !!d.flatFloor;
        $('#peTarget').value = d.target; $('#peMin').value = d.minMargin; $('#peMax').value = d.maxMargin; $('#pePpc').value = ''; $('#peRate').value = d.usdRate;
        $$('.js-pe-price').forEach((el) => { const v = d.prices[el.dataset.key]; el.value = v === null ? '' : v; });
        $$('.js-pe-field').forEach((el) => { const v = (d.kinds[el.dataset.kind] || {})[el.dataset.field]; el.value = v === undefined ? 0 : v; });
        paint();
        if (window.toastr) toastr.info('The defaults are filled in. Save to use them.');
    });

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        const s = read(), btn = $('#peSave');
        const body = { on: s.on ? 1 : 0, flatFloor: s.flatFloor ? 1 : 0, target: s.target, minMargin: s.minMargin, maxMargin: s.maxMargin, pesoPerCredit: s.pesoPerCredit, usdRate: s.usdRate, prices: {}, kinds: {} };
        Object.keys(s.prices).forEach((k) => { if (s.prices[k] !== null) body.prices[k] = s.prices[k]; });
        $$('.js-pe-field').forEach((el) => { (body.kinds[el.dataset.kind] ||= {})[el.dataset.field] = el.value === '' ? 0 : numOr(el.value, 0); });
        btn.disabled = true;
        try {
            const res = await fetch(PE.save, { method: 'POST', headers: { 'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': PE.csrf }, body: JSON.stringify(body) });
            const j = await res.json().catch(() => ({}));
            if (!res.ok || !j.success) throw new Error(j.message || 'Could not save.');
            if (window.toastr) toastr.success(j.message); else alert(j.message);
        } catch (err) {
            if (window.toastr) toastr.error(err.message); else alert(err.message);
        } finally {
            btn.disabled = false;
        }
    });
});
</script>
@endif
