@extends('layouts.master')

@section('title') {{ $gameName }} Reports @endsection

@section('css')
@include('games.salpakan._styles', ['tables' => false])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Games @endslot
        @slot('li_2') {{ $gameName }} @endslot
        @slot('title') Reports @endslot
    @endcomponent

    <div id="gs">
        <div class="card">
            <div class="card-body">
                <div class="filters row g-2 align-items-end" id="gsFilters">
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fFrom">From</label>
                        <input type="date" class="form-control" id="fFrom" data-filter="dateFrom" value="{{ now()->subDays(29)->toDateString() }}">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fTo">To</label>
                        <input type="date" class="form-control" id="fTo" data-filter="dateTo" value="{{ now()->toDateString() }}">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fGroup">Group by</label>
                        <select class="form-select" id="fGroup" data-filter="group">
                            <option value="day">Day</option>
                            <option value="week">Week</option>
                            <option value="month">Month</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <button type="button" class="btn btn-primary w-100" id="gsRun"><i class="bx bx-filter-alt me-1"></i>Show</button>
                    </div>
                    <div class="col-12 col-xl-4">
                        <p class="sub mb-0">Revenue is completed orders (no store tests) in {{ $currency }}, dated when paid, Philippine time. Play is counted by the game server's days (UTC). Up to two years at a time.</p>
                    </div>
                </div>
            </div>
        </div>

        <ul class="nav nav-tabs nav-tabs-custom mb-3" role="tablist">
            <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tabSales" role="tab" data-report="sales"><i class="bx bx-money me-1"></i>Sales</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabUsage" role="tab" data-report="usage"><i class="bx bx-joystick me-1"></i>Usage</a></li>
            <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tabLocation" role="tab" data-report="location"><i class="bx bx-world me-1"></i>Location</a></li>
        </ul>

        <div class="tab-content">
            {{-- Sales --}}
            <div class="tab-pane active" id="tabSales" role="tabpanel">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="sRevenue">—</b><span>revenue</span></div></div></div>
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="sOrders">—</b><span>orders</span></div></div></div>
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="sAverage">—</b><span>average order</span></div></div></div>
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="sPayers">—</b><span>paying players</span></div></div></div>
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="sRefunded">—</b><span><span id="sRefunds">0</span> refunded</span></div></div></div>
                </div>
                <div class="card"><div class="card-body">
                    <h5 class="card-title text-dark">Revenue and orders</h5>
                    <div id="cSalesTrend" class="chart-box"></div>
                </div></div>
                <div class="row">
                    <div class="col-xl-5"><div class="card"><div class="card-body">
                        <h5 class="card-title text-dark">By store</h5>
                        <div id="cSalesStore" class="chart-box"></div>
                    </div></div></div>
                    <div class="col-xl-7"><div class="card"><div class="card-body">
                        <h5 class="card-title text-dark">By item category</h5>
                        <div id="cSalesCategory" class="chart-box"></div>
                    </div></div></div>
                </div>
                <div class="card"><div class="card-body">
                    <h5 class="card-title text-dark">Top items</h5>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead class="table-light"><tr><th>Item</th><th>Product ID</th><th class="text-end">Orders</th><th class="text-end">Revenue ({{ $currency }})</th></tr></thead>
                            <tbody id="tTopItems"><tr><td colspan="4" class="text-center text-secondary py-3">Loading…</td></tr></tbody>
                        </table>
                    </div>
                </div></div>
            </div>

            {{-- Usage --}}
            <div class="tab-pane" id="tabUsage" role="tabpanel">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="uDau">—</b><span>played today (UTC)</span></div></div></div>
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="uWau">—</b><span>last 7 days</span></div></div></div>
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="uMau">—</b><span>last 30 days</span></div></div></div>
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="uSignups">—</b><span>sign-ups in the period</span></div></div></div>
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="uMatches">—</b><span>matches in the period</span></div></div></div>
                    <div class="col-6 col-xl-2"><div class="stat"><div><b id="uHours">—</b><span>hours played</span></div></div></div>
                </div>
                <div class="card"><div class="card-body">
                    <h5 class="card-title text-dark">Sign-ups and <span id="uActiveLabel">active players</span></h5>
                    <div id="cUsageTrend" class="chart-box"></div>
                </div></div>
                <div class="row">
                    <div class="col-xl-6"><div class="card"><div class="card-body">
                        <h5 class="card-title text-dark">Matches played</h5>
                        <p class="sub">Each player's matches added up, so a match between two players counts twice.</p>
                        <div id="cUsageMatches" class="chart-box"></div>
                    </div></div></div>
                    <div class="col-xl-3"><div class="card"><div class="card-body">
                        <h5 class="card-title text-dark">Platforms played on</h5>
                        <p class="sub">Players active in the period, by platform.</p>
                        <div id="cUsagePlatforms" class="chart-box"></div>
                    </div></div></div>
                    <div class="col-xl-3"><div class="card"><div class="card-body">
                        <h5 class="card-title text-dark">Platform last used</h5>
                        <p class="sub">Every player, by the platform they used last.</p>
                        <div id="cUsageLastPlatforms" class="chart-box"></div>
                    </div></div></div>
                </div>
            </div>

            {{-- Location --}}
            <div class="tab-pane" id="tabLocation" role="tabpanel">
                <div class="row">
                    <div class="col-xl-5"><div class="card"><div class="card-body">
                        <h5 class="card-title text-dark">Top countries</h5>
                        <p class="sub">Players: everyone registered. Revenue: completed orders in the period.</p>
                        <div id="cLocation" class="chart-box"></div>
                    </div></div></div>
                    <div class="col-xl-7"><div class="card"><div class="card-body">
                        <h5 class="card-title text-dark">Countries</h5>
                        <p class="sub">Click a country for its regions and cities.</p>
                        <div class="table-responsive" style="max-height: 26rem;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light"><tr><th>Country</th><th class="text-end">Players</th><th class="text-end">Paying</th><th class="text-end">Orders</th><th class="text-end">Revenue ({{ $currency }})</th></tr></thead>
                                <tbody id="tCountries"><tr><td colspan="5" class="text-center text-secondary py-3">Loading…</td></tr></tbody>
                            </table>
                        </div>
                    </div></div></div>
                </div>
                <div class="row d-none" id="locationDetail">
                    <div class="col-12"><h5 class="text-dark mb-3"><i class="bx bx-map me-1"></i><span id="dCountry"></span></h5></div>
                    <div class="col-xl-6"><div class="card"><div class="card-body">
                        <h5 class="card-title text-dark">Regions</h5>
                        <div class="table-responsive" style="max-height: 24rem;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light"><tr><th>Region</th><th class="text-end">Players</th><th class="text-end">Paying</th><th class="text-end">Revenue</th></tr></thead>
                                <tbody id="tRegions"></tbody>
                            </table>
                        </div>
                    </div></div></div>
                    <div class="col-xl-6"><div class="card"><div class="card-body">
                        <h5 class="card-title text-dark">Cities</h5>
                        <div class="table-responsive" style="max-height: 24rem;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light"><tr><th>City</th><th class="text-end">Players</th><th class="text-end">Paying</th><th class="text-end">Revenue</th></tr></thead>
                                <tbody id="tCities"></tbody>
                            </table>
                        </div>
                    </div></div></div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function () {
@include('games.salpakan._script-helpers')

    const $filters = $('#gsFilters');
    const URLS = {
        sales: @json(route('games-salpakan-reports.sales')),
        usage: @json(route('games-salpakan-reports.usage')),
        location: @json(route('games-salpakan-reports.location'))
    };
    const currency = @json($currency);
    const money = (v) => (currency === 'USD' ? '$' : currency + ' ') + Number(v || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const COLORS = ['#556ee6', '#34c38f', '#f1b44c', '#50a5f1', '#f46a6a', '#74788d', '#343a40', '#e83e8c'];
    const charts = {};
    let current = 'sales';
    let selectedCountry = null;

    const draw = (id, options) => {
        if (charts[id]) { charts[id].destroy(); }
        const el = document.getElementById(id);
        el.innerHTML = '';
        charts[id] = new ApexCharts(el, Object.assign({ colors: COLORS }, options));
        charts[id].render();
    };
    const emptyRow = (cols, text) => '<tr><td colspan="' + cols + '" class="text-center text-secondary py-3">' + esc(text) + '</td></tr>';
    const donut = (id, rows, valueKey, format) => draw(id, {
        chart: { type: 'donut', height: 320 },
        series: rows.length ? rows.map((r) => Number(r[valueKey])) : [],
        labels: rows.map((r) => r.label),
        legend: { position: 'bottom' },
        noData: { text: 'Nothing in this period' },
        tooltip: { y: { formatter: format || ((v) => num(v)) } }
    });

    function loadSales(params) {
        return $.get(URLS.sales, params).done((r) => {
            const d = r.data;
            $('#sRevenue').text(d.kpis.revenue); $('#sOrders').text(num(d.kpis.orders)); $('#sAverage').text(d.kpis.average);
            $('#sPayers').text(num(d.kpis.payers)); $('#sRefunded').text(d.kpis.refunded); $('#sRefunds').text(num(d.kpis.refunds));
            draw('cSalesTrend', {
                chart: { height: 340, type: 'line', toolbar: { show: true } },
                series: [{ name: 'Revenue', type: 'area', data: d.trend.revenue }, { name: 'Orders', type: 'line', data: d.trend.orders }],
                labels: d.trend.labels,
                stroke: { width: [0, 3], curve: 'smooth' },
                fill: { type: ['gradient', 'solid'], gradient: { opacityFrom: 0.6, opacityTo: 0.15 } },
                yaxis: [{ title: { text: 'Revenue (' + currency + ')' }, labels: { formatter: (v) => money(v) } }, { opposite: true, title: { text: 'Orders' }, labels: { formatter: (v) => Math.round(v) } }],
                tooltip: { shared: true, intersect: false, y: { formatter: (v, o) => o.seriesIndex === 0 ? money(v) : num(v) } },
                xaxis: { labels: { rotate: -45 } }
            });
            donut('cSalesStore', d.byStore, 'amount', money);
            draw('cSalesCategory', {
                chart: { type: 'bar', height: 320, toolbar: { show: false } },
                series: [{ name: 'Revenue', data: d.byCategory.map((c) => c.amount) }],
                xaxis: { categories: d.byCategory.map((c) => c.label), labels: { formatter: (v) => money(v) } },
                plotOptions: { bar: { horizontal: true, borderRadius: 3 } },
                dataLabels: { enabled: false },
                noData: { text: 'Nothing in this period' },
                tooltip: { y: { formatter: (v) => money(v) } }
            });
            $('#tTopItems').html(d.topItems.length ? d.topItems.map((i) =>
                '<tr><td class="text-dark">' + esc(i.label) + '</td><td class="mono">' + esc(i.productId || '—') + '</td><td class="text-end text-dark">' + num(i.orders) + '</td><td class="text-end text-dark">' + money(i.amount) + '</td></tr>'
            ).join('') : emptyRow(4, 'No sales in this period.'));
        }).fail((xhr) => fail(xhr, 'The sales report could not be loaded.'));
    }

    function loadUsage(params) {
        return $.get(URLS.usage, params).done((r) => {
            const d = r.data;
            $('#uDau').text(num(d.kpis.dau)); $('#uWau').text(num(d.kpis.wau)); $('#uMau').text(num(d.kpis.mau));
            $('#uSignups').text(num(d.kpis.signups)); $('#uMatches').text(num(d.kpis.matches)); $('#uHours').text(num(d.kpis.hours));
            $('#uActiveLabel').text(d.activeLabel.toLowerCase());
            draw('cUsageTrend', {
                chart: { height: 340, type: 'line', toolbar: { show: true } },
                series: [{ name: 'Sign-ups', type: 'column', data: d.trend.signups }, { name: d.activeLabel, type: 'line', data: d.trend.active }],
                labels: d.trend.labels,
                stroke: { width: [0, 3], curve: 'smooth' },
                yaxis: [{ title: { text: 'Sign-ups' }, labels: { formatter: (v) => Math.round(v) } }, { opposite: true, title: { text: 'Active players' }, labels: { formatter: (v) => Math.round(v) } }],
                tooltip: { shared: true, intersect: false },
                xaxis: { labels: { rotate: -45 } }
            });
            draw('cUsageMatches', {
                chart: { type: 'bar', height: 300, toolbar: { show: false } },
                series: [{ name: 'Matches', data: d.trend.matches }],
                xaxis: { categories: d.trend.labels, labels: { rotate: -45 } },
                dataLabels: { enabled: false },
                plotOptions: { bar: { borderRadius: 2 } }
            });
            donut('cUsagePlatforms', d.platforms, 'players');
            donut('cUsageLastPlatforms', d.lastPlatforms, 'players');
        }).fail((xhr) => fail(xhr, 'The usage report could not be loaded.'));
    }

    function placeRows(rows, $body, cols) {
        $body.html(rows.length ? rows.map((row) =>
            '<tr><td class="text-dark">' + esc(row.name) + (row.region ? ' <span class="sub">' + esc(row.region) + '</span>' : '') + '</td><td class="text-end text-dark">' + num(row.players) +
            '</td><td class="text-end text-dark">' + num(row.payers) + '</td><td class="text-end text-dark">' + money(row.revenue) + '</td></tr>'
        ).join('') : emptyRow(cols, 'No players here yet.'));
    }

    function loadLocation(params) {
        if (selectedCountry) { params.country = selectedCountry; }
        return $.get(URLS.location, params).done((r) => {
            const d = r.data;
            const top = d.countries.slice(0, 10);
            draw('cLocation', {
                chart: { type: 'bar', height: 340, toolbar: { show: false } },
                series: [{ name: 'Players', data: top.map((c) => c.players) }, { name: 'Revenue', data: top.map((c) => c.revenue) }],
                xaxis: { categories: top.map((c) => c.name) },
                yaxis: [{ title: { text: 'Players' } }, { opposite: true, title: { text: 'Revenue' }, labels: { formatter: (v) => money(v) } }],
                plotOptions: { bar: { borderRadius: 2 } },
                dataLabels: { enabled: false },
                noData: { text: 'No players yet' },
                tooltip: { shared: true, intersect: false, y: { formatter: (v, o) => o.seriesIndex === 1 ? money(v) : num(v) } }
            });
            $('#tCountries').html(d.countries.length ? d.countries.map((c) =>
                '<tr class="is-clickable' + (c.code === selectedCountry ? ' table-active' : '') + '" data-code="' + esc(c.code || '') + '"><td class="text-dark">' + esc(c.name) +
                (c.code ? ' <span class="sub">' + esc(c.code) + '</span>' : '') + '</td><td class="text-end text-dark">' + num(c.players) + '</td><td class="text-end text-dark">' + num(c.payers) +
                '</td><td class="text-end text-dark">' + num(c.orders) + '</td><td class="text-end text-dark">' + money(c.revenue) + '</td></tr>'
            ).join('') : emptyRow(5, 'No players yet.'));
            if (d.detail) {
                $('#locationDetail').removeClass('d-none');
                const country = d.countries.find((c) => c.code === d.detail.country);
                $('#dCountry').text(country ? country.name : d.detail.country);
                placeRows(d.detail.regions, $('#tRegions'), 4);
                placeRows(d.detail.cities, $('#tCities'), 4);
            } else {
                $('#locationDetail').addClass('d-none');
            }
        }).fail((xhr) => fail(xhr, 'The location report could not be loaded.'));
    }

    const loaders = { sales: loadSales, usage: loadUsage, location: loadLocation };
    const loaded = {};
    const run = (report) => {
        const $btn = $('#gsRun');
        const mine = !$btn.prop('disabled');
        if (mine) { busy($btn, true, 'Loading'); }
        loaded[report] = true;
        loaders[report](filterValues($filters)).always(() => { if (mine) { busy($btn, false); } });
    };

    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function () {
        current = $(this).data('report');
        if (!loaded[current]) { run(current); }
    });
    $('#gsRun').on('click', () => { Object.keys(loaded).forEach((k) => { loaded[k] = false; }); run(current); });
    $('#tCountries').on('click', 'tr[data-code]', function () {
        const code = $(this).data('code');
        if (!code) { return; }
        selectedCountry = selectedCountry === code ? null : code;
        run('location');
    });

    run('sales');
});
</script>
@endsection
