@extends('layouts.master')

@section('title') {{ $gameName }} Sales &amp; Orders @endsection

@section('css')
@include('games.salpakan._styles', ['tables' => true])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Games @endslot
        @slot('li_2') {{ $gameName }} @endslot
        @slot('title') Sales &amp; Orders @endslot
    @endcomponent

    <div id="gs">
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-money"></i><div><b id="tRevenue">—</b><span>revenue (completed orders)</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-receipt"></i><div><b id="tCompleted">—</b><span>completed of <span id="tOrders">—</span> orders shown</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-cart"></i><div><b id="tAverage">—</b><span>average completed order</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-undo"></i><div><b id="tRefunded">—</b><span><span id="tRefunds">0</span> refunded</span></div></div></div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h4 class="card-title mb-1 text-dark">Sales &amp; orders</h4>
                        <p class="text-secondary mb-0 small">Every real-money order placed in {{ $gameName }} through Google Play or Steam. Totals follow the filters; store test purchases are left out unless you ask for them. Completed and refunded sales are also logged in <a href="{{ route('ecom-orders') }}">E-commerce → Orders</a> (in pesos). <span id="tByStore"></span></p>
                    </div>
                    <a href="{{ route('games-salpakan-sales.export') }}" class="btn btn-outline-success" id="gsExport"><i class="bx bx-download me-1"></i>Export CSV</a>
                </div>

                <div class="filters row g-2 mb-3" id="gsFilters">
                    @if (request()->filled('player'))
                        <input type="hidden" data-filter="player" value="{{ (int) request('player') }}">
                        <div class="col-12"><span class="chip">Only player #{{ (int) request('player') }}
                            <a href="{{ route('games-salpakan-sales.index') }}" class="ms-1 text-secondary" title="Show everyone"><i class="bx bx-x"></i></a></span></div>
                    @endif
                    <div class="col-md-4 col-xl-3">
                        <label class="form-label" for="fSearch">Search</label>
                        <input type="text" class="form-control" id="fSearch" data-filter="searchFilter" placeholder="Order ID, store order, item, player">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="fStore">Store</label>
                        <select class="form-select" id="fStore" data-filter="store">
                            <option value="">Any</option>
                            @foreach ($stores as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="fStatus">Status</label>
                        <select class="form-select" id="fStatus" data-filter="status">
                            <option value="">Any</option>
                            @foreach ($statuses as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="fKind">Kind</label>
                        <select class="form-select" id="fKind" data-filter="kind">
                            <option value="">Any</option>
                            @foreach ($kinds as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="fCategory">Item category</label>
                        <select class="form-select" id="fCategory" data-filter="category">
                            <option value="">Any</option>
                            @foreach ($categories as $category)
                                <option value="{{ $category }}">{{ ucwords(str_replace(['_', '.'], ' ', $category)) }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="fTest">Test purchases</label>
                        <select class="form-select" id="fTest" data-filter="test">
                            <option value="exclude">Leave out</option>
                            <option value="include">Include</option>
                            <option value="only">Only tests</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="fFrom">Ordered from</label>
                        <input type="date" class="form-control" id="fFrom" data-filter="dateFrom">
                    </div>
                    <div class="col-6 col-md-2">
                        <label class="form-label" for="fTo">Ordered to</label>
                        <input type="date" class="form-control" id="fTo" data-filter="dateTo">
                    </div>
                    <div class="col-6 col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary w-100" id="gsClear"><i class="bx bx-x me-1"></i>Clear</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="gsOrders" class="table table-hover align-middle nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Ordered</th>
                                <th>Player</th>
                                <th>Item</th>
                                <th>Store</th>
                                <th class="text-end">Price</th>
                                <th>Status</th>
                                <th>Completed</th>
                                <th>/ecom-orders</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function () {
@include('games.salpakan._script-helpers')

    const $filters = $('#gsFilters');
    const exportBase = @json(route('games-salpakan-sales.export'));
    const ecomOrders = @json(route('ecom-orders'));
    const prefix = @json(config('games.salpakan.ecom_order_prefix', 'SAL'));

    const table = $('#gsOrders').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        searching: false,
        order: [[0, 'desc']],
        pageLength: 25,
        ajax: {
            url: @json(route('games-salpakan-sales.data')),
            data: (d) => Object.assign(d, filterValues($filters)),
            error: () => toastr.error('The orders could not be loaded.', 'Error!')
        },
        columns: [
            { data: 'orderedFormatted', name: 'games_orders.orderedAt', render: (d, t, row) => '<span class="text-dark">' + esc(d || '—') + '</span><div class="sub mono" title="' + esc(row.remoteOrderId) + '">' + esc(String(row.remoteOrderId).slice(0, 13)) + '…</div>' },
            { data: 'playerUsername', name: 'p.username', render: (d, t, row) => {
                const name = esc(d || (row.remotePlayerId ? 'Player #' + row.remotePlayerId : 'Unknown'));
                const link = row.playerUrl ? '<a href="' + esc(row.playerUrl) + '" class="fw-semibold text-dark">' + name + '</a>' : '<span class="text-dark">' + name + '</span>';
                return link + (row.playerEmail ? '<div class="sub">' + esc(row.playerEmail) + '</div>' : '');
            } },
            { data: 'itemName', name: 'games_orders.itemName', render: (d, t, row) => '<span class="text-dark">' + esc(d || row.productId || '—') + '</span><div class="sub">' + esc(row.kindLabel) + (row.itemCategory ? ' · ' + esc(row.itemCategory) : '') + (row.productId ? ' · <span class="mono">' + esc(row.productId) + '</span>' : '') + '</div>' },
            { data: 'storeLabel', name: 'games_orders.store', render: (d, t, row) => '<span class="text-dark">' + esc(d) + '</span>' + (row.storeOrderId ? '<div class="sub mono">' + esc(row.storeOrderId) + '</div>' : '') },
            { data: 'priceFormatted', name: 'games_orders.priceCents', className: 'text-end', render: (d) => '<span class="text-dark fw-semibold">' + esc(d) + '</span>' },
            { data: 'orderStatus', name: 'games_orders.orderStatus', render: (d, t, row) => orderBadge(d, row.statusLabel) + (row.isTestPurchase ? ' <span class="badge bg-light text-dark">Test</span>' : '') },
            { data: 'completedFormatted', name: 'games_orders.completedAt', render: (d) => d ? '<span class="text-dark">' + esc(d) + '</span>' : dash },
            { data: 'ecomOrderId', name: 'games_orders.ecomOrderId', render: (d, t, row) => {
                if (d) { return '<a href="' + esc(ecomOrders) + '" target="_blank" title="' + esc(prefix + '-' + row.remoteOrderId) + '">' + esc(row.ecomPhpFormatted || 'Logged') + ' <i class="bx bx-link-external"></i></a>'; }
                if (row.ecomError) { return '<span class="bad small" title="' + esc(row.ecomError) + '">Not logged</span>'; }
                return row.isTestPurchase || row.orderStatus === 'pending' || row.orderStatus === 'cancelled' ? '<span class="sub">not a sale</span>' : dash;
            } }
        ]
    });

    const loadTotals = () => {
        $.get(@json(route('games-salpakan-sales.totals')), filterValues($filters)).done((r) => {
            const t = r.data;
            $('#tRevenue').text(t.revenue.join(' + '));
            $('#tCompleted').text(num(t.byStatus.completed || 0));
            $('#tOrders').text(num(t.orders));
            $('#tAverage').text(t.average);
            $('#tRefunded').text(t.refunded.join(' + '));
            $('#tRefunds').text(num(t.byStatus.refunded || 0));
            $('#tByStore').html(t.byStore.length ? 'By store: ' + t.byStore.map((s) => '<span class="chip">' + esc(s.store) + ' ' + esc(s.revenue) + '</span>').join('') : '');
        }).fail((xhr) => fail(xhr, 'The totals could not be loaded.'));
    };

    const reload = () => {
        table.ajax.reload();
        loadTotals();
        $('#gsExport').attr('href', exportBase + '?' + $.param(filterValues($filters)));
    };
    let timer = null;
    $('#fSearch').on('keyup', () => { clearTimeout(timer); timer = setTimeout(reload, 400); });
    $filters.find('select, input[type=date]').on('change', reload);
    $('#gsClear').on('click', () => { $filters.find('input:not([type=hidden]), select').val(''); $('#fTest').val('exclude'); reload(); });
    loadTotals();
    $('#gsExport').attr('href', exportBase + '?' + $.param(filterValues($filters)));
});
</script>
@endsection
