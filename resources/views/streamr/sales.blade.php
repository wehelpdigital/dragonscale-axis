@extends('layouts.master')

@section('title') Streamr Sales dashboard @endsection

@section('css')
@include('streamr._styles', ['tables' => false])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Streamr @endslot
        @slot('title') Sales dashboard @endslot
    @endcomponent

    <div id="st">
        <div class="card">
            <div class="card-body">
                <div class="filters row g-2 align-items-end">
                    <div class="col-sm-6 col-md-4 col-xl-3">
                        <label class="form-label" for="stRange">Period (Philippine time)</label>
                        <select class="form-select" id="stRange">
                            @foreach ($ranges as $key => $label)
                                <option value="{{ $key }}" @selected($range === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-sm-6 col-md-4 col-xl-2">
                        <a href="{{ route('streamr-sales.export', ['range' => $range]) }}" class="btn btn-outline-success w-100" id="stExport"><i class="bx bx-download me-1"></i>Export CSV</a>
                    </div>
                    <div class="col-12 col-md-4 col-xl-7">
                        <p class="sub mb-0">From <a href="{{ url('/ecom-orders') }}">/ecom-orders</a>: Streamr's orders that are complete or refunded, minus processed refunds (the Sales Report's rules).</p>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-3">
            <div class="col-xl-2 col-md-4 col-6"><div class="stat kpi"><i class="bx bx-money"></i><div><b id="kRevenue">—</b><span>Revenue</span></div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="stat kpi"><i class="bx bx-cart"></i><div><b id="kOrders">—</b><span>Orders</span></div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="stat kpi"><i class="bx bx-pie-chart-alt-2"></i><div><b id="kSplit">—</b><span>Plans / Credits</span></div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="stat kpi"><i class="bx bx-calculator"></i><div><b id="kAverage">—</b><span>Average order</span></div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="stat kpi"><i class="bx bx-undo"></i><div><b id="kRefunds">—</b><span>Refunds (processed)</span></div></div></div>
            <div class="col-xl-2 col-md-4 col-6"><div class="stat kpi"><i class="bx bx-time-five"></i><div><b><a href="{{ route('streamr-payments.index') }}" id="kPending">—</a></b><span>Pending payments</span></div></div></div>
        </div>

        <div class="row g-3">
            <div class="col-xl-8">
                <div class="card h-100"><div class="card-body">
                    <h4 class="card-title text-dark mb-3">Revenue per <span id="stBucket">day</span></h4>
                    <div id="stChart" class="chart-box"></div>
                    <div class="empty" id="stChartEmpty" hidden>No sales in this period.</div>
                </div></div>
            </div>
            <div class="col-xl-4">
                <div class="card h-100"><div class="card-body">
                    <h4 class="card-title text-dark mb-3">Active subscribers</h4>
                    <dl class="row kv mb-0" id="stSubscribers">
                        <dt class="col-7">Monthly</dt><dd class="col-5 text-end" data-sub="monthly">—</dd>
                        <dt class="col-7">Yearly</dt><dd class="col-5 text-end" data-sub="yearly">—</dd>
                        <dt class="col-7">Lifetime</dt><dd class="col-5 text-end" data-sub="lifetime">—</dd>
                        <dt class="col-7">Comped (no charge)</dt><dd class="col-5 text-end" data-sub="comped">—</dd>
                        <dt class="col-7">Lifetime sold (all time)</dt><dd class="col-5 text-end" data-sub="lifetimeSold">—</dd>
                    </dl>
                    <p class="sub mt-2 mb-0">From the client registry Streamr keeps up to date (<a href="{{ route('streamr-clients.index') }}">Clients</a>).</p>
                    <hr>
                    <h4 class="card-title text-dark mb-2">Plan mix</h4>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle mb-0">
                            <thead><tr><th>Item</th><th class="text-end">Sold</th><th class="text-end">Net</th></tr></thead>
                            <tbody id="stMix"><tr><td colspan="3" class="text-secondary">—</td></tr></tbody>
                        </table>
                    </div>
                </div></div>
            </div>
        </div>

        <div class="card mt-3">
            <div class="card-body">
                <h4 class="card-title text-dark mb-3">Recent orders</h4>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light"><tr><th>Order</th><th>Client</th><th>Item</th><th class="text-end">Total</th><th>Order</th><th>Payment</th><th>Date</th></tr></thead>
                        <tbody id="stRecent"><tr><td colspan="7"><div class="skel"></div></td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(function () {
@include('streamr._script-helpers')

    const U = { data: @json(route('streamr-sales.data')), exportBase: @json(route('streamr-sales.export')), payments: @json(route('streamr-payments.index')) };
    let chart = null;

    function paint(d) {
        const k = d.kpis;
        $('#kRevenue').text(peso(k.revenue));
        $('#kOrders').text(num(k.orders));
        $('#kSplit').html(esc(peso(k.plans)) + '<small>' + esc(peso(k.credits)) + '</small>');
        $('#kAverage').text(peso(k.average));
        $('#kRefunds').text(peso(k.refunds));
        $('#kPending').html(esc(num(k.pendingCount)) + '<small>' + esc(peso(k.pendingTotal)) + '</small>');
        Object.keys(d.subscribers).forEach((key) => $('[data-sub="' + key + '"]').text(num(d.subscribers[key])));
        $('#stMix').html(d.mix.length ? d.mix.map((m) => '<tr><td class="text-dark">' + esc(m.item) + ' <span class="sub">' + (m.kind === 'credits' ? 'credits' : 'plan') + '</span></td>'
            + '<td class="text-end text-dark">' + num(m.count) + '</td><td class="text-end text-dark">' + peso(m.net) + '</td></tr>').join('')
            : '<tr><td colspan="3" class="text-secondary">No sales in this period.</td></tr>');
        $('#stBucket').text(d.series.bucket);

        const hasSales = d.series.plans.concat(d.series.credits).some((v) => v > 0);
        $('#stChartEmpty').prop('hidden', hasSales);
        const options = {
            chart: { type: 'bar', stacked: true, height: 320, toolbar: { show: false } },
            series: [{ name: 'Plans', data: d.series.plans }, { name: 'Credits', data: d.series.credits }],
            xaxis: { categories: d.series.labels },
            yaxis: { labels: { formatter: (v) => peso(v) } },
            dataLabels: { enabled: false },
            tooltip: { y: { formatter: (v) => peso(v) } },
            colors: ['#556ee6', '#34c38f'],
            legend: { position: 'top' }
        };
        if (chart) { chart.updateOptions(options); } else { chart = new ApexCharts(document.querySelector('#stChart'), options); chart.render(); }

        $('#stRecent').html(d.recent.length ? d.recent.map((o) => '<tr><td><a class="st-mono" href="' + esc(U.payments + '?open=' + encodeURIComponent(o.number)) + '">' + esc(o.number) + '</a></td>'
            + '<td><span class="text-dark">' + esc(o.client) + '</span>' + (o.email && o.email !== o.client ? '<div class="sub">' + esc(o.email) + '</div>' : '') + '</td>'
            + '<td class="text-dark">' + esc(o.item) + '</td><td class="text-end text-dark">' + peso(o.total) + '</td><td>' + orderBadge(o.orderStatus) + '</td><td>' + paymentBadge(o.paymentStatus) + '</td>'
            + '<td class="text-secondary">' + esc(o.date) + '</td></tr>').join('')
            : '<tr><td colspan="7" class="text-center text-secondary py-4">No Streamr orders yet. They appear here once Streamr sends its first purchase.</td></tr>');
    }

    function load() {
        const range = $('#stRange').val();
        $('#stExport').attr('href', U.exportBase + '?' + $.param({ range }));
        $.get(U.data, { range }).done((r) => paint(r.data)).fail((x) => fail(x, 'The sales could not be loaded.'));
    }
    $('#stRange').on('change', load);
    load();
});
</script>
@endsection
