@extends('layouts.master')

@section('title') Dashboard @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .mini-stats-wid .mini-stat-icon {
        width: 48px;
        height: 48px;
    }
    .growth-badge {
        font-size: 11px;
        padding: 2px 6px;
    }
    .growth-positive {
        background-color: rgba(52, 195, 143, 0.18);
        color: #34c38f;
    }
    .growth-negative {
        background-color: rgba(244, 106, 106, 0.18);
        color: #f46a6a;
    }
    .kpi-value {
        font-size: 1.5rem;
        font-weight: 600;
    }
    .kpi-label {
        font-size: 0.85rem;
        color: #74788d;
    }
    .chart-container {
        min-height: 300px;
    }
    .province-progress {
        height: 8px;
        border-radius: 4px;
    }
    .table-dashboard th {
        font-weight: 500;
        font-size: 0.8rem;
        text-transform: uppercase;
        color: #74788d;
    }
    .table-dashboard td {
        vertical-align: middle;
    }
    .order-status-badge {
        font-size: 11px;
        padding: 4px 8px;
    }
    .skeleton-loader {
        background: linear-gradient(90deg, #f0f0f0 25%, #e0e0e0 50%, #f0f0f0 75%);
        background-size: 200% 100%;
        animation: loading 1.5s infinite;
        border-radius: 4px;
    }
    @keyframes loading {
        0% { background-position: 200% 0; }
        100% { background-position: -200% 0; }
    }
    .skeleton-text {
        height: 20px;
        width: 80%;
        margin-bottom: 8px;
    }
    .skeleton-number {
        height: 32px;
        width: 60%;
    }
</style>
@endsection

@section('content')

@component('components.breadcrumb')
    @slot('li_1') DS AXIS @endslot
    @slot('title') Dashboard @endslot
@endcomponent

<!-- Date Filter Row -->
<div class="row mb-3">
    <div class="col-12">
        <div class="card">
            <div class="card-body py-2">
                <form id="filterForm" class="row align-items-center g-2">
                    <div class="col-auto">
                        <label class="form-label mb-0 text-dark small">From:</label>
                    </div>
                    <div class="col-auto">
                        <input type="date" class="form-control form-control-sm" id="dateFrom" name="dateFrom">
                    </div>
                    <div class="col-auto">
                        <label class="form-label mb-0 text-dark small">To:</label>
                    </div>
                    <div class="col-auto">
                        <input type="date" class="form-control form-control-sm" id="dateTo" name="dateTo">
                    </div>
                    <div class="col-auto">
                        <label class="form-label mb-0 text-dark small">Store:</label>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="storeFilter" name="storeId" style="min-width: 150px;">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->storeName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="submit" class="btn btn-primary btn-sm">
                            <i class="bx bx-filter-alt me-1"></i>Apply
                        </button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnQuickLast7">Last 7 Days</button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnQuickLast30">Last 30 Days</button>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="btnQuickThisMonth">This Month</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- KPI Cards Row -->
<div class="row">
    <!-- Total Sales -->
    <div class="col-md-6 col-xl-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Net Sales</p>
                        <h4 class="mb-0 text-dark" id="kpiNetSales">
                            <span class="skeleton-loader skeleton-number d-inline-block"></span>
                        </h4>
                        <div class="mt-2">
                            <span class="badge growth-badge" id="kpiSalesGrowth">--</span>
                            <span class="text-muted small ms-1">vs previous period</span>
                        </div>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-primary">
                            <span class="avatar-title">
                                <i class="bx bx-money font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Total Orders -->
    <div class="col-md-6 col-xl-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Total Orders</p>
                        <h4 class="mb-0 text-dark" id="kpiTotalOrders">
                            <span class="skeleton-loader skeleton-number d-inline-block"></span>
                        </h4>
                        <div class="mt-2">
                            <span class="badge growth-badge" id="kpiOrdersGrowth">--</span>
                            <span class="text-muted small ms-1">vs previous period</span>
                        </div>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-success">
                            <span class="avatar-title">
                                <i class="bx bx-cart font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Net Profit -->
    <div class="col-md-6 col-xl-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Net Profit</p>
                        <h4 class="mb-0 text-dark" id="kpiNetProfit">
                            <span class="skeleton-loader skeleton-number d-inline-block"></span>
                        </h4>
                        <div class="mt-2">
                            <span class="text-muted small" id="kpiProfitMargin">Margin: --%</span>
                        </div>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-info">
                            <span class="avatar-title">
                                <i class="bx bx-trending-up font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Avg Order Value -->
    <div class="col-md-6 col-xl-3">
        <div class="card mini-stats-wid">
            <div class="card-body">
                <div class="d-flex">
                    <div class="flex-grow-1">
                        <p class="text-muted fw-medium mb-2">Avg Order Value</p>
                        <h4 class="mb-0 text-dark" id="kpiAvgOrder">
                            <span class="skeleton-loader skeleton-number d-inline-block"></span>
                        </h4>
                        <div class="mt-2">
                            <span class="text-muted small">Refund Rate: <span id="kpiRefundRate">--%</span></span>
                        </div>
                    </div>
                    <div class="flex-shrink-0 align-self-center">
                        <div class="mini-stat-icon avatar-sm rounded-circle bg-warning">
                            <span class="avatar-title bg-warning">
                                <i class="bx bx-receipt font-size-24"></i>
                            </span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Charts Row -->
<div class="row">
    <!-- Sales Trend Chart -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4 text-dark">Sales Trend</h4>
                <div id="salesTrendChart" class="chart-container"></div>
            </div>
        </div>
    </div>

    <!-- Sales by Store Chart -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4 text-dark">Sales by Store</h4>
                <div id="salesByStoreChart" class="chart-container"></div>
            </div>
        </div>
    </div>
</div>

<!-- Leads and Top Provinces Row -->
<div class="row">
    <!-- Leads Summary -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4 text-dark">Leads Overview</h4>
                <div class="row text-center mb-4">
                    <div class="col-4">
                        <h5 class="mb-1 text-dark" id="leadsTotal">--</h5>
                        <p class="text-muted mb-0 small">Total</p>
                    </div>
                    <div class="col-4">
                        <h5 class="mb-1 text-success" id="leadsWon">--</h5>
                        <p class="text-muted mb-0 small">Won</p>
                    </div>
                    <div class="col-4">
                        <h5 class="mb-1 text-primary" id="leadsConversion">--%</h5>
                        <p class="text-muted mb-0 small">Conversion</p>
                    </div>
                </div>
                <div id="leadsStatusChart" style="min-height: 200px;"></div>
            </div>
        </div>
    </div>

    <!-- Top Provinces -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4 text-dark">Top Provinces</h4>
                <div id="topProvincesContainer">
                    <div class="text-center py-4">
                        <div class="spinner-border text-primary spinner-border-sm" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Top Products -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4 text-dark">Top Products</h4>
                <div class="table-responsive">
                    <table class="table table-sm table-dashboard mb-0">
                        <thead>
                            <tr>
                                <th class="text-dark">Product</th>
                                <th class="text-end text-dark">Sales</th>
                            </tr>
                        </thead>
                        <tbody id="topProductsTable">
                            <tr>
                                <td colspan="2" class="text-center py-4">
                                    <div class="spinner-border text-primary spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Orders and Affiliates Row -->
<div class="row">
    <!-- Recent Orders -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0 text-dark">Recent Orders</h4>
                    <a href="{{ route('ecom-orders') }}" class="btn btn-sm btn-primary">View All</a>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm table-dashboard align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-dark">Order #</th>
                                <th class="text-dark">Client</th>
                                <th class="text-dark">Total</th>
                                <th class="text-dark">Status</th>
                                <th class="text-dark">Date</th>
                            </tr>
                        </thead>
                        <tbody id="recentOrdersTable">
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <div class="spinner-border text-primary spinner-border-sm" role="status">
                                        <span class="visually-hidden">Loading...</span>
                                    </div>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- Affiliates Summary -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-4 text-dark">Affiliates</h4>
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-soft-primary text-primary font-size-18">
                            <i class="bx bx-group"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 small">Total Affiliates</p>
                        <h5 class="mb-0 text-dark" id="affiliatesTotal">--</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center mb-3">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-soft-success text-success font-size-18">
                            <i class="bx bx-check-circle"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 small">Active</p>
                        <h5 class="mb-0 text-dark" id="affiliatesActive">--</h5>
                    </div>
                </div>
                <div class="d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle bg-soft-warning text-warning font-size-18">
                            <i class="bx bx-dollar-circle"></i>
                        </span>
                    </div>
                    <div class="flex-grow-1">
                        <p class="text-muted mb-1 small">Total Commissions</p>
                        <h5 class="mb-0 text-dark" id="affiliatesCommissions">--</h5>
                    </div>
                </div>
                <hr>
                <a href="{{ route('ecom-affiliates') }}" class="btn btn-sm btn-outline-primary w-100">
                    Manage Affiliates
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
$(document).ready(function() {
    // Toastr config
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };
    // Initialize date filters (default: last 30 days)
    const today = new Date();
    const last30 = new Date(today);
    last30.setDate(last30.getDate() - 30);

    $('#dateTo').val(formatDate(today));
    $('#dateFrom').val(formatDate(last30));

    // Chart instances
    let salesTrendChart = null;
    let salesByStoreChart = null;
    let leadsStatusChart = null;

    // Load dashboard data
    loadDashboardData();

    // Filter form submit
    $('#filterForm').on('submit', function(e) {
        e.preventDefault();
        loadDashboardData();
    });

    // Quick filters
    $('#btnQuickLast7').on('click', function() {
        setQuickFilter(7);
    });

    $('#btnQuickLast30').on('click', function() {
        setQuickFilter(30);
    });

    $('#btnQuickThisMonth').on('click', function() {
        const now = new Date();
        const firstDay = new Date(now.getFullYear(), now.getMonth(), 1);
        $('#dateFrom').val(formatDate(firstDay));
        $('#dateTo').val(formatDate(now));
        loadDashboardData();
    });

    function setQuickFilter(days) {
        const today = new Date();
        const past = new Date(today);
        past.setDate(past.getDate() - days);
        $('#dateTo').val(formatDate(today));
        $('#dateFrom').val(formatDate(past));
        loadDashboardData();
    }

    function formatDate(date) {
        return date.toISOString().split('T')[0];
    }

    function formatCurrency(amount) {
        return new Intl.NumberFormat('en-PH', {
            style: 'currency',
            currency: 'PHP',
            minimumFractionDigits: 0,
            maximumFractionDigits: 0
        }).format(amount);
    }

    function formatNumber(num) {
        return new Intl.NumberFormat('en-PH').format(num);
    }

    function loadDashboardData() {
        const params = {
            dateFrom: $('#dateFrom').val(),
            dateTo: $('#dateTo').val(),
            storeId: $('#storeFilter').val(),
            _token: '{{ csrf_token() }}'
        };

        $.ajax({
            url: '{{ route("dashboard.data") }}',
            type: 'GET',
            data: params,
            success: function(response) {
                if (response.success) {
                    updateKPIs(response.data.salesKPIs);
                    updateSalesTrendChart(response.data.salesTrend);
                    updateSalesByStoreChart(response.data.salesByStore);
                    updateLeadsData(response.data.leads);
                    updateTopProvinces(response.data.topProvinces);
                    updateTopProducts(response.data.topProducts);
                    updateRecentOrders(response.data.recentOrders);
                    updateAffiliates(response.data.affiliates);
                } else {
                    toastr.error(response.message || 'Failed to load dashboard data', 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load dashboard data', 'Error');
                console.error(xhr);
            }
        });
    }

    function updateKPIs(data) {
        $('#kpiNetSales').text(formatCurrency(data.netSales));
        $('#kpiTotalOrders').text(formatNumber(data.totalOrders));
        $('#kpiNetProfit').text(formatCurrency(data.netProfit));
        $('#kpiAvgOrder').text(formatCurrency(data.avgOrderValue));
        $('#kpiProfitMargin').text('Margin: ' + data.profitMargin + '%');
        $('#kpiRefundRate').text(data.refundRate + '%');

        // Growth badges
        updateGrowthBadge('#kpiSalesGrowth', data.salesGrowth);
        updateGrowthBadge('#kpiOrdersGrowth', data.ordersGrowth);
    }

    function updateGrowthBadge(selector, value) {
        const $badge = $(selector);
        const isPositive = value >= 0;
        $badge.removeClass('growth-positive growth-negative')
              .addClass(isPositive ? 'growth-positive' : 'growth-negative')
              .html((isPositive ? '+' : '') + value + '% <i class="mdi mdi-arrow-' + (isPositive ? 'up' : 'down') + '"></i>');
    }

    function updateSalesTrendChart(data) {
        const categories = data.map(d => d.label);
        const salesData = data.map(d => d.sales);
        const ordersData = data.map(d => d.orders);

        const options = {
            series: [{
                name: 'Sales',
                type: 'area',
                data: salesData
            }, {
                name: 'Orders',
                type: 'line',
                data: ordersData
            }],
            chart: {
                height: 300,
                type: 'line',
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            colors: ['#556ee6', '#34c38f'],
            stroke: {
                width: [0, 3],
                curve: 'smooth'
            },
            fill: {
                type: ['gradient', 'solid'],
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.4,
                    opacityTo: 0.1,
                }
            },
            labels: categories,
            markers: {
                size: 0
            },
            xaxis: {
                type: 'category',
                labels: {
                    rotate: -45,
                    style: { colors: '#74788d' }
                }
            },
            yaxis: [{
                title: { text: 'Sales (PHP)', style: { color: '#74788d' } },
                labels: {
                    formatter: function(val) {
                        return formatCurrency(val);
                    },
                    style: { colors: '#74788d' }
                }
            }, {
                opposite: true,
                title: { text: 'Orders', style: { color: '#74788d' } },
                labels: {
                    style: { colors: '#74788d' }
                }
            }],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex }) {
                        return seriesIndex === 0 ? formatCurrency(val) : val + ' orders';
                    }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right'
            }
        };

        if (salesTrendChart) {
            salesTrendChart.destroy();
        }
        salesTrendChart = new ApexCharts(document.querySelector("#salesTrendChart"), options);
        salesTrendChart.render();
    }

    function updateSalesByStoreChart(data) {
        if (data.length === 0) {
            $('#salesByStoreChart').html('<div class="text-center py-5 text-muted">No data available</div>');
            return;
        }

        const options = {
            series: data.map(d => d.sales),
            chart: {
                type: 'donut',
                height: 300
            },
            labels: data.map(d => d.name),
            colors: ['#556ee6', '#34c38f', '#f46a6a', '#50a5f1', '#f1b44c', '#74788d'],
            legend: {
                position: 'bottom',
                labels: { colors: '#74788d' }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                color: '#74788d',
                                formatter: function(w) {
                                    return formatCurrency(w.globals.seriesTotals.reduce((a, b) => a + b, 0));
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function(val) {
                        return formatCurrency(val);
                    }
                }
            },
            responsive: [{
                breakpoint: 480,
                options: {
                    legend: { position: 'bottom' }
                }
            }]
        };

        if (salesByStoreChart) {
            salesByStoreChart.destroy();
        }
        salesByStoreChart = new ApexCharts(document.querySelector("#salesByStoreChart"), options);
        salesByStoreChart.render();
    }

    function updateLeadsData(data) {
        $('#leadsTotal').text(formatNumber(data.total));
        $('#leadsWon').text(formatNumber(data.won));
        $('#leadsConversion').text(data.conversionRate + '%');

        const chartData = data.byStatus.filter(s => s.count > 0);

        if (chartData.length === 0) {
            $('#leadsStatusChart').html('<div class="text-center py-5 text-muted">No leads data</div>');
            return;
        }

        const options = {
            series: chartData.map(d => d.count),
            chart: {
                type: 'donut',
                height: 200
            },
            labels: chartData.map(d => d.status),
            colors: chartData.map(d => d.color),
            legend: {
                position: 'bottom',
                labels: { colors: '#74788d' }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '65%'
                    }
                }
            }
        };

        if (leadsStatusChart) {
            leadsStatusChart.destroy();
        }
        leadsStatusChart = new ApexCharts(document.querySelector("#leadsStatusChart"), options);
        leadsStatusChart.render();
    }

    function updateTopProvinces(data) {
        if (data.length === 0) {
            $('#topProvincesContainer').html('<div class="text-center py-4 text-muted">No data available</div>');
            return;
        }

        const maxSales = Math.max(...data.map(d => d.sales));
        let html = '';

        data.forEach((p, i) => {
            const percentage = maxSales > 0 ? (p.sales / maxSales) * 100 : 0;
            const colors = ['bg-primary', 'bg-success', 'bg-info', 'bg-warning', 'bg-danger'];
            html += `
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <span class="text-dark">${escapeHtml(p.province)}</span>
                        <span class="text-muted small">${formatCurrency(p.sales)} (${p.orders} orders)</span>
                    </div>
                    <div class="progress province-progress">
                        <div class="progress-bar ${colors[i % colors.length]}" role="progressbar" style="width: ${percentage}%"></div>
                    </div>
                </div>
            `;
        });

        $('#topProvincesContainer').html(html);
    }

    function updateTopProducts(data) {
        if (data.length === 0) {
            $('#topProductsTable').html('<tr><td colspan="2" class="text-center py-4 text-muted">No data available</td></tr>');
            return;
        }

        let html = '';
        data.forEach(p => {
            html += `
                <tr>
                    <td>
                        <div class="text-dark fw-medium text-truncate" style="max-width: 180px;" title="${escapeHtml(p.name)}">${escapeHtml(p.name)}</div>
                        <small class="text-muted">${escapeHtml(p.store)} - ${p.units} units</small>
                    </td>
                    <td class="text-end text-dark fw-medium">${formatCurrency(p.sales)}</td>
                </tr>
            `;
        });

        $('#topProductsTable').html(html);
    }

    function updateRecentOrders(data) {
        if (data.length === 0) {
            $('#recentOrdersTable').html('<tr><td colspan="5" class="text-center py-4 text-muted">No orders yet</td></tr>');
            return;
        }

        const statusColors = {
            'pending': 'bg-warning text-dark',
            'processing': 'bg-info text-white',
            'complete': 'bg-success',
            'refunded': 'bg-danger',
            'cancelled': 'bg-secondary'
        };

        let html = '';
        data.forEach(o => {
            const badgeClass = statusColors[o.status] || 'bg-secondary';
            html += `
                <tr>
                    <td><a href="{{ url('/ecom-orders-edit') }}?id=${o.id}" class="text-primary fw-medium">#${escapeHtml(o.orderNumber)}</a></td>
                    <td class="text-dark">${escapeHtml(o.client || 'N/A')}</td>
                    <td class="text-dark fw-medium">${formatCurrency(o.total)}</td>
                    <td><span class="badge order-status-badge ${badgeClass}">${o.status}</span></td>
                    <td class="text-muted small">${o.date}</td>
                </tr>
            `;
        });

        $('#recentOrdersTable').html(html);
    }

    function updateAffiliates(data) {
        $('#affiliatesTotal').text(formatNumber(data.total));
        $('#affiliatesActive').text(formatNumber(data.active));
        $('#affiliatesCommissions').text(formatCurrency(data.totalCommissions));
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection
