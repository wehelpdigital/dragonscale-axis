@extends('layouts.master')

@section('title') Sales Report @endsection

@section('css')
<!-- Bootstrap Datepicker -->
<link href="{{ URL::asset('build/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.report-card {
    border: none;
    border-radius: 0.5rem;
    box-shadow: 0 0.125rem 0.25rem rgba(0, 0, 0, 0.075);
    transition: transform 0.2s;
}
.report-card:hover {
    transform: translateY(-2px);
}
.kpi-card {
    background: #fff;
    border-left: 4px solid #556ee6;
    overflow: visible !important;
}
.kpi-card .card-body {
    padding-bottom: 1rem;
}
.kpi-card.sales { border-left-color: #34c38f; }
.kpi-card.orders { border-left-color: #556ee6; }
.kpi-card.avg { border-left-color: #f46a6a; }
.kpi-card.items { border-left-color: #50a5f1; }
.kpi-value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #495057;
}
.kpi-label {
    font-size: 0.875rem;
    color: #74788d;
}
.kpi-content {
    display: flex;
    flex-direction: column;
    gap: 0.25rem;
}
.kpi-value-row {
    display: flex;
    align-items: baseline;
    flex-wrap: wrap;
    gap: 0.5rem;
}
.growth-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    border-radius: 1rem;
    white-space: nowrap;
    display: inline-flex;
    align-items: center;
    flex-shrink: 0;
}
.growth-positive { background: #d4edda; color: #155724; }
.growth-negative { background: #f8d7da; color: #721c24; }
.filter-section {
    background: #f8f9fa;
    border-radius: 0.5rem;
    padding: 1rem;
    margin-bottom: 1.5rem;
}
/* Store Filter Checkbox Dropdown Styling */
.store-filter-container {
    position: relative;
}
.store-filter-field {
    min-height: 38px;
    cursor: pointer;
    background-color: #fff;
    padding: 5px 8px;
}
.store-filter-field:hover {
    border-color: #86b7fe;
}
.store-filter-field.show {
    border-color: #86b7fe;
    box-shadow: 0 0 0 0.25rem rgba(13, 110, 253, 0.25);
}
.store-filter-placeholder {
    font-size: 0.875rem;
    line-height: 26px;
}
.store-filter-tag {
    display: inline-flex;
    align-items: center;
    background-color: #e9f0fc;
    border: 1px solid #556ee6;
    border-radius: 4px;
    color: #495057;
    font-size: 0.8125rem;
    font-weight: 500;
    padding: 2px 6px 2px 8px;
    line-height: 1.4;
    gap: 4px;
}
.store-filter-tag .remove-tag {
    color: #6c757d;
    font-size: 1rem;
    cursor: pointer;
    line-height: 1;
    padding: 0;
    background: none;
    border: none;
    display: flex;
    align-items: center;
}
.store-filter-tag .remove-tag:hover {
    color: #dc3545;
}
.store-filter-dropdown {
    max-height: 300px;
    overflow-y: auto;
    box-shadow: 0 0.5rem 1rem rgba(0, 0, 0, 0.15);
}
.store-filter-dropdown .dropdown-item {
    cursor: pointer;
    user-select: none;
}
.store-filter-dropdown .dropdown-item:hover {
    background-color: #f8f9fa;
}
.store-filter-dropdown .dropdown-item:active {
    background-color: #e9ecef;
    color: #212529;
}
.store-filter-dropdown .form-check-input {
    cursor: pointer;
    margin-top: 0;
}
.store-filter-dropdown .form-check-input:checked {
    background-color: #556ee6;
    border-color: #556ee6;
}
.report-tabs .nav-link {
    color: #495057;
    border: none;
    padding: 0.75rem 1.25rem;
    font-weight: 500;
}
.report-tabs .nav-link.active {
    color: #556ee6;
    border-bottom: 2px solid #556ee6;
    background: transparent;
}
.chart-container {
    height: 350px;
    position: relative;
    overflow: hidden;
}
/* Ensure charts stay within their containers */
.card {
    position: relative;
    z-index: 1;
    overflow: hidden;
}
.tab-content {
    position: relative;
    z-index: 1;
}
.tab-pane {
    position: relative;
}
/* ApexCharts specific fixes */
.apexcharts-canvas {
    position: relative !important;
}
.apexcharts-svg {
    overflow: hidden;
}
/* All chart containers need overflow control */
#salesTrendChart,
#storePieChart,
#productBarChart,
#discountPieChart,
#commissionPieChart {
    position: relative;
    overflow: hidden;
}
.data-table th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 0.875rem;
}
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    min-height: 200px;
    background: rgba(255,255,255,0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10;
    border-radius: 0.25rem;
}
.saved-reports-list {
    max-height: 300px;
    overflow-y: auto;
}
/* Report tabs card - contains charts */
.report-tabs-card {
    position: relative;
    z-index: 2;
}
.report-tabs-card .card-body {
    overflow: hidden;
}
/* Saved reports card - should be below charts */
.saved-reports-card {
    position: relative;
    z-index: 1;
}
.saved-report-item {
    padding: 0.75rem;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: background 0.2s;
}
.saved-report-item:hover {
    background: #f8f9fa;
}
.saved-report-item:last-child {
    border-bottom: none;
}
.percentage-bar {
    height: 8px;
    background: #e9ecef;
    border-radius: 4px;
    overflow: hidden;
}
.percentage-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #556ee6, #34c38f);
    border-radius: 4px;
}
/* Custom badge colors */
.bg-purple {
    background-color: #7b5ea7 !important;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') Reports @endslot
@slot('title') Sales Report @endslot
@endcomponent

<div class="d-flex justify-content-end mb-3">
    <button type="button" class="btn btn-outline-info btn-sm" data-bs-toggle="modal" data-bs-target="#reportHelpModal">
        <i class="bx bx-help-circle me-1"></i>Report Guide
    </button>
</div>

<div class="row">
    <div class="col-12">
        <!-- Filter Section -->
        <div class="filter-section">
            <!-- Row 1: Date Filters and Group By -->
            <div class="row mb-3">
                <div class="col-6 col-md-3">
                    <label class="form-label text-dark fw-medium">Date From</label>
                    <input type="text" class="form-control" id="dateFrom" placeholder="Select date" readonly>
                </div>
                <div class="col-6 col-md-3">
                    <label class="form-label text-dark fw-medium">Date To</label>
                    <input type="text" class="form-control" id="dateTo" placeholder="Select date" readonly>
                </div>
                <div class="col-12 col-md-3 mt-3 mt-md-0">
                    <label class="form-label text-dark fw-medium">Group By</label>
                    <select class="form-select" id="groupByFilter">
                        <option value="daily">Daily</option>
                        <option value="weekly">Weekly</option>
                        <option value="monthly">Monthly</option>
                    </select>
                </div>
            </div>
            <!-- Row 2: Store Filter -->
            <div class="row mb-3">
                <div class="col-12">
                    <label class="form-label text-dark fw-medium">Store Filter</label>
                    <div class="store-filter-container">
                        <div class="store-filter-field form-control d-flex align-items-center flex-wrap gap-1" id="storeFilterField" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="store-filter-placeholder text-secondary">All Stores</span>
                        </div>
                        <ul class="dropdown-menu store-filter-dropdown w-100 p-2" id="storeFilterDropdown">
                            <li class="mb-2">
                                <input type="text" class="form-control form-control-sm" id="storeFilterSearch" placeholder="Search stores...">
                            </li>
                            <li><hr class="dropdown-divider my-1"></li>
                            @foreach($stores as $store)
                            <li>
                                <label class="dropdown-item d-flex align-items-center py-2 rounded">
                                    <input type="checkbox" class="form-check-input me-2 store-checkbox" value="{{ $store->id }}" data-name="{{ $store->storeName }}">
                                    <span class="text-dark">{{ $store->storeName }}</span>
                                </label>
                            </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
            <!-- Row 3: Action Buttons -->
            <div class="row">
                <div class="col-12">
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-primary" id="applyFiltersBtn">
                            <i class="bx bx-filter-alt me-1"></i>Generate Report
                        </button>
                        <button type="button" class="btn btn-outline-secondary" id="clearFiltersBtn">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                        <button type="button" class="btn btn-outline-danger" id="resetReportBtn">
                            <i class="bx bx-reset me-1"></i>Reset Report
                        </button>
                        <div class="dropdown">
                            <button class="btn btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown">
                                <i class="bx bx-download me-1"></i>Export
                            </button>
                            <ul class="dropdown-menu">
                                <li><a class="dropdown-item" href="#" onclick="exportReport('csv')"><i class="bx bx-file me-2"></i>Export CSV</a></li>
                                <li><a class="dropdown-item" href="#" onclick="window.print()"><i class="bx bx-printer me-2"></i>Print Report</a></li>
                            </ul>
                        </div>
                        <button type="button" class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#saveReportModal">
                            <i class="bx bx-save me-1"></i>Save Report
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- KPI Cards -->
        <div class="row mb-4" id="kpiCards">
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card report-card kpi-card sales h-100">
                    <div class="card-body">
                        <p class="kpi-label mb-2">Total Sales</p>
                        <div class="kpi-content">
                            <h4 class="kpi-value mb-0" id="kpiTotalSales">₱0.00</h4>
                            <div>
                                <span class="growth-badge growth-positive" id="kpiSalesGrowth">
                                    <i class="bx bx-trending-up me-1"></i>0%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card report-card kpi-card orders h-100">
                    <div class="card-body">
                        <p class="kpi-label mb-2">Total Orders</p>
                        <div class="kpi-content">
                            <h4 class="kpi-value mb-0" id="kpiTotalOrders">0</h4>
                            <div>
                                <span class="growth-badge growth-positive" id="kpiOrdersGrowth">
                                    <i class="bx bx-trending-up me-1"></i>0%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card report-card kpi-card avg h-100">
                    <div class="card-body">
                        <p class="kpi-label mb-2">Avg Order Value</p>
                        <h4 class="kpi-value mb-0" id="kpiAvgOrder">₱0.00</h4>
                    </div>
                </div>
            </div>
            <div class="col-xl-3 col-md-6 mb-3">
                <div class="card report-card kpi-card items h-100">
                    <div class="card-body">
                        <p class="kpi-label mb-2">Items Sold</p>
                        <h4 class="kpi-value mb-0" id="kpiItemsSold">0</h4>
                    </div>
                </div>
            </div>
        </div>

        <!-- Additional KPIs Row -->
        <div class="row mb-4">
            <div class="col-md-3 mb-3">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <i class="bx bx-money text-success" style="font-size: 2rem;"></i>
                        <h5 class="mt-2 mb-1 text-dark" id="kpiNetRevenue">₱0.00</h5>
                        <p class="text-secondary mb-0 small">Net Revenue</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <i class="bx bx-tag-alt text-warning" style="font-size: 2rem;"></i>
                        <h5 class="mt-2 mb-1 text-dark" id="kpiTotalDiscounts">₱0.00</h5>
                        <p class="text-secondary mb-0 small">Total Discounts</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <i class="bx bx-user-pin text-info" style="font-size: 2rem;"></i>
                        <h5 class="mt-2 mb-1 text-dark" id="kpiTotalCommissions">₱0.00</h5>
                        <p class="text-secondary mb-0 small">Total Commissions</p>
                    </div>
                </div>
            </div>
            <div class="col-md-3 mb-3">
                <div class="card report-card h-100">
                    <div class="card-body text-center">
                        <i class="bx bx-car text-primary" style="font-size: 2rem;"></i>
                        <h5 class="mt-2 mb-1 text-dark" id="kpiTotalShipping">₱0.00</h5>
                        <p class="text-secondary mb-0 small">Total Shipping</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Profit Metrics Row -->
        <div class="mb-3">
            <h6 class="text-dark fw-medium mb-2"><i class="bx bx-trending-up me-1"></i>Profitability Metrics</h6>
            <div class="row g-2">
                <div class="col-md-2 d-flex">
                    <div class="card report-card border-start border-danger border-3 mb-0 w-100">
                        <div class="card-body text-center py-2">
                            <i class="bx bx-package text-danger" style="font-size: 1.25rem;"></i>
                            <h6 class="mt-1 mb-0 text-dark" id="kpiTotalCost">₱0.00</h6>
                            <p class="text-secondary mb-0 small">Total Cost (COGS)</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 d-flex">
                    <div class="card report-card border-start border-success border-3 mb-0 w-100">
                        <div class="card-body text-center py-2">
                            <i class="bx bx-dollar-circle text-success" style="font-size: 1.25rem;"></i>
                            <h6 class="mt-1 mb-0 text-dark" id="kpiGrossProfit">₱0.00</h6>
                            <p class="text-secondary mb-0 small">Gross Profit</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 d-flex">
                    <div class="card report-card border-start border-info border-3 mb-0 w-100">
                        <div class="card-body text-center py-2">
                            <i class="bx bx-pie-chart-alt text-info" style="font-size: 1.25rem;"></i>
                            <h6 class="mt-1 mb-0 text-dark" id="kpiGrossMargin">0%</h6>
                            <p class="text-secondary mb-0 small">Gross Margin</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 d-flex">
                    <div class="card report-card border-start border-primary border-3 mb-0 w-100">
                        <div class="card-body text-center py-2">
                            <i class="bx bx-wallet text-primary" style="font-size: 1.25rem;"></i>
                            <h6 class="mt-1 mb-0 text-dark" id="kpiNetProfit">₱0.00</h6>
                            <p class="text-secondary mb-0 small">Net Profit</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 d-flex">
                    <div class="card report-card border-start border-warning border-3 mb-0 w-100">
                        <div class="card-body text-center py-2">
                            <i class="bx bx-pie-chart text-warning" style="font-size: 1.25rem;"></i>
                            <h6 class="mt-1 mb-0 text-dark" id="kpiNetMargin">0%</h6>
                            <p class="text-secondary mb-0 small">Net Margin</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-2 d-flex">
                    <div class="card report-card border-start border-secondary border-3 mb-0 w-100">
                        <div class="card-body text-center py-2">
                            <i class="bx bx-undo text-secondary" style="font-size: 1.25rem;"></i>
                            <h6 class="mt-1 mb-0 text-dark" id="kpiTotalRefunds">₱0.00</h6>
                            <p class="text-secondary mb-0 small">Total Refunds</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Report Tabs -->
        <div class="card report-tabs-card">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs report-tabs card-header-tabs" id="reportTabs" role="tablist">
                    <li class="nav-item">
                        <a class="nav-link active" id="trend-tab" data-bs-toggle="tab" href="#trend" role="tab">
                            <i class="bx bx-line-chart me-1"></i>Sales Trend
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="store-tab" data-bs-toggle="tab" href="#store" role="tab">
                            <i class="bx bx-store me-1"></i>By Store
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="product-tab" data-bs-toggle="tab" href="#product" role="tab">
                            <i class="bx bx-package me-1"></i>By Product
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="discount-tab" data-bs-toggle="tab" href="#discount" role="tab">
                            <i class="bx bx-purchase-tag me-1"></i>Discounts
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="commission-tab" data-bs-toggle="tab" href="#commission" role="tab">
                            <i class="bx bx-user-check me-1"></i>Commissions
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="profitability-tab" data-bs-toggle="tab" href="#profitability" role="tab">
                            <i class="bx bx-trending-up me-1"></i>Profitability
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" id="refunds-tab" data-bs-toggle="tab" href="#refunds" role="tab">
                            <i class="bx bx-undo me-1"></i>Refunds
                        </a>
                    </li>
                </ul>
            </div>
            <div class="card-body">
                <div class="tab-content">
                    <!-- Sales Trend Tab -->
                    <div class="tab-pane fade show active" id="trend" role="tabpanel">
                        <div class="row">
                            <div class="col-12">
                                <div class="position-relative">
                                    <div id="trendChartLoader" class="loading-overlay d-none">
                                        <div class="spinner-border text-primary" role="status">
                                            <span class="visually-hidden">Loading...</span>
                                        </div>
                                    </div>
                                    <div id="salesTrendChart" class="chart-container"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- By Store Tab -->
                    <div class="tab-pane fade" id="store" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-5">
                                <h6 class="text-dark mb-3">Sales Distribution by Store</h6>
                                <div class="position-relative">
                                    <div id="storeChartLoader" class="loading-overlay d-none">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                    <div id="storePieChart" style="height: 320px;"></div>
                                </div>
                                <div class="text-end mt-2 pe-2" id="storeTotalSummary">
                                    <!-- Total summary will be added here by JS -->
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <h6 class="text-dark mb-3">Store Performance</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover data-table" id="storeTable">
                                        <thead>
                                            <tr>
                                                <th>Store</th>
                                                <th class="text-end">Orders</th>
                                                <th class="text-end">Units</th>
                                                <th class="text-end">Sales</th>
                                                <th>Share</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- By Product Tab -->
                    <div class="tab-pane fade" id="product" role="tabpanel">
                        <div class="row">
                            <div class="col-lg-5">
                                <h6 class="text-dark mb-3">Top Products by Revenue</h6>
                                <div class="position-relative">
                                    <div id="productChartLoader" class="loading-overlay d-none">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                    <div id="productBarChart" style="height: 350px;"></div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <h6 class="text-dark mb-3">Product Performance</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover data-table" id="productTable">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Store</th>
                                                <th class="text-end">Units</th>
                                                <th class="text-end">Avg Price</th>
                                                <th class="text-end">Sales</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Discounts Tab -->
                    <div class="tab-pane fade" id="discount" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-dark mb-1" id="discountOrdersWithDiscount">0</h4>
                                        <small class="text-secondary">Orders with Discount</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-dark mb-1" id="discountOrdersWithoutDiscount">0</h4>
                                        <small class="text-secondary">Orders without Discount</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-success mb-1" id="discountRate">0%</h4>
                                        <small class="text-secondary">Discount Usage Rate</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-warning mb-1" id="discountTotalAmount">₱0.00</h4>
                                        <small class="text-secondary">Total Discounted</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-5">
                                <h6 class="text-dark mb-3">Discount Distribution</h6>
                                <div class="position-relative">
                                    <div id="discountChartLoader" class="loading-overlay d-none">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                    <div id="discountPieChart" style="height: 300px;"></div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <h6 class="text-dark mb-3">Discount Code Usage</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover data-table" id="discountTable">
                                        <thead>
                                            <tr>
                                                <th>Discount</th>
                                                <th>Code</th>
                                                <th>Trigger</th>
                                                <th class="text-end">Uses</th>
                                                <th class="text-end">Total Discounted</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Commissions Tab -->
                    <div class="tab-pane fade" id="commission" role="tabpanel">
                        <div class="row mb-4">
                            <div class="col-md-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-primary mb-1" id="commissionTotal">₱0.00</h4>
                                        <small class="text-secondary">Total Commission Paid</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-dark mb-1" id="commissionBaseAmount">₱0.00</h4>
                                        <small class="text-secondary">Commission Base Amount</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-info mb-1" id="commissionAvgRate">0%</h4>
                                        <small class="text-secondary">Average Commission Rate</small>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-lg-5">
                                <h6 class="text-dark mb-3">Commission by Store</h6>
                                <div class="position-relative">
                                    <div id="commissionChartLoader" class="loading-overlay d-none">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                    <div id="commissionPieChart" style="height: 300px;"></div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <h6 class="text-dark mb-3">Affiliate Performance</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover data-table" id="commissionTable">
                                        <thead>
                                            <tr>
                                                <th>Affiliate</th>
                                                <th>Store</th>
                                                <th class="text-end">Orders</th>
                                                <th class="text-end">Avg Rate</th>
                                                <th class="text-end">Commission</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Profitability Tab -->
                    <div class="tab-pane fade" id="profitability" role="tabpanel">
                        <!-- Profitability Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-2">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center py-3">
                                        <h5 class="text-dark mb-1" id="profitGrossRevenue">₱0.00</h5>
                                        <small class="text-secondary">Gross Revenue</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center py-3">
                                        <h5 class="text-danger mb-1" id="profitTotalCost">₱0.00</h5>
                                        <small class="text-secondary">Total Cost</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center py-3">
                                        <h5 class="text-warning mb-1" id="profitDiscounts">₱0.00</h5>
                                        <small class="text-secondary">Discounts</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center py-3">
                                        <h5 class="text-secondary mb-1" id="profitRefunds">₱0.00</h5>
                                        <small class="text-secondary">Refunds</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center py-3">
                                        <h5 class="text-success mb-1" id="profitGrossProfit">₱0.00</h5>
                                        <small class="text-secondary">Gross Profit</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-2">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center py-3">
                                        <h5 class="text-primary mb-1" id="profitNetProfit">₱0.00</h5>
                                        <small class="text-secondary">Net Profit</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Profit Breakdown Chart -->
                            <div class="col-lg-5">
                                <h6 class="text-dark mb-3">Profit Breakdown</h6>
                                <div class="position-relative">
                                    <div id="profitChartLoader" class="loading-overlay d-none">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                    <div id="profitBreakdownChart" style="height: 320px;"></div>
                                </div>
                            </div>
                            <!-- Profit by Store Table -->
                            <div class="col-lg-7">
                                <h6 class="text-dark mb-3">Profit by Store</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover data-table" id="profitByStoreTable">
                                        <thead>
                                            <tr>
                                                <th>Store</th>
                                                <th class="text-end">Revenue</th>
                                                <th class="text-end">Cost</th>
                                                <th class="text-end">Profit</th>
                                                <th class="text-end">Margin</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Profit by Product Table -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="text-dark mb-3">Top Products by Profit Margin</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover data-table" id="profitByProductTable">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Store</th>
                                                <th class="text-end">Units Sold</th>
                                                <th class="text-end">Selling Price</th>
                                                <th class="text-end">Cost Price</th>
                                                <th class="text-end">Revenue</th>
                                                <th class="text-end">Profit</th>
                                                <th class="text-end">Margin</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Refunds Tab -->
                    <div class="tab-pane fade" id="refunds" role="tabpanel">
                        <!-- Refunds Summary Cards -->
                        <div class="row mb-4">
                            <div class="col-md-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-danger mb-1" id="refundTotalApproved">₱0.00</h4>
                                        <small class="text-secondary">Total Refunded</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-warning mb-1" id="refundTotalPending">₱0.00</h4>
                                        <small class="text-secondary">Pending Amount</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-info mb-1" id="refundRate">0%</h4>
                                        <small class="text-secondary">Refund Rate</small>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <div class="card bg-light border-0">
                                    <div class="card-body text-center">
                                        <h4 class="text-dark mb-1" id="refundTotalOrders">0</h4>
                                        <small class="text-secondary">Orders with Refunds</small>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Refund Status Badges -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <div class="d-flex flex-wrap gap-3">
                                    <span class="badge bg-warning text-dark px-3 py-2">
                                        <i class="bx bx-time-five me-1"></i>Pending: <strong id="refundStatusPending">0</strong>
                                    </span>
                                    <span class="badge bg-info text-white px-3 py-2">
                                        <i class="bx bx-check me-1"></i>Approved: <strong id="refundStatusApproved">0</strong>
                                    </span>
                                    <span class="badge bg-success text-white px-3 py-2">
                                        <i class="bx bx-check-double me-1"></i>Processed: <strong id="refundStatusProcessed">0</strong>
                                    </span>
                                    <span class="badge bg-danger text-white px-3 py-2">
                                        <i class="bx bx-x me-1"></i>Rejected: <strong id="refundStatusRejected">0</strong>
                                    </span>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <!-- Refunds by Store -->
                            <div class="col-lg-5">
                                <h6 class="text-dark mb-3">Refunds by Store</h6>
                                <div class="position-relative">
                                    <div id="refundChartLoader" class="loading-overlay d-none">
                                        <div class="spinner-border text-primary" role="status"></div>
                                    </div>
                                    <div id="refundByStoreChart" style="height: 300px;"></div>
                                </div>
                            </div>
                            <!-- Refunds by Store Table -->
                            <div class="col-lg-7">
                                <h6 class="text-dark mb-3">Store Refund Details</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover data-table" id="refundByStoreTable">
                                        <thead>
                                            <tr>
                                                <th>Store</th>
                                                <th class="text-end">Refund Count</th>
                                                <th class="text-end">Total Refunded</th>
                                                <th class="text-end">Avg Refund</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>

                        <!-- Top Refunded Products -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <h6 class="text-dark mb-3">Most Refunded Products</h6>
                                <div class="table-responsive">
                                    <table class="table table-hover data-table" id="refundedProductsTable">
                                        <thead>
                                            <tr>
                                                <th>Product</th>
                                                <th>Store</th>
                                                <th class="text-end">Refund Count</th>
                                                <th class="text-end">Qty Refunded</th>
                                                <th class="text-end">Total Refunded</th>
                                            </tr>
                                        </thead>
                                        <tbody></tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Saved Reports Sidebar -->
        <div class="card mt-4 saved-reports-card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark"><i class="bx bx-folder me-2"></i>Saved Reports</h6>
                <button class="btn btn-sm btn-outline-primary" onclick="loadSavedReports()">
                    <i class="bx bx-refresh"></i>
                </button>
            </div>
            <div class="card-body p-0">
                <div class="saved-reports-list" id="savedReportsList">
                    @forelse($savedReports as $report)
                        <div class="saved-report-item" onclick="loadSavedReport({{ $report->id }})">
                            <div class="d-flex justify-content-between align-items-start">
                                <div>
                                    <h6 class="mb-1 text-dark">{{ $report->reportName }}</h6>
                                    <small class="text-secondary">{{ $report->reportTypeLabel }} &bull; {{ $report->dateRange }}</small>
                                </div>
                                <div class="d-flex gap-1">
                                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteSavedReport({{ $report->id }}, '{{ $report->reportName }}')">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bx bx-folder-open text-secondary" style="font-size: 2rem;"></i>
                            <p class="text-secondary mb-0 small">No saved reports yet</p>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Save Report Modal -->
<div class="modal fade" id="saveReportModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-save me-2"></i>Save Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-dark">Report Name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="saveReportName" placeholder="e.g., Q1 2026 Sales Summary">
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Report Type</label>
                    <select class="form-select" id="saveReportType">
                        <option value="overview">Sales Overview</option>
                        <option value="by_store">Sales by Store</option>
                        <option value="by_product">Sales by Product</option>
                        <option value="trend">Sales Trend</option>
                        <option value="discount">Discount Analysis</option>
                        <option value="commission">Commission Report</option>
                    </select>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Notes (Optional)</label>
                    <textarea class="form-control" id="saveReportNotes" rows="2" placeholder="Add any notes about this report..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmSaveReport">
                    <i class="bx bx-save me-1"></i>Save Report
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirm Modal -->
<div class="modal fade" id="deleteReportModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-trash text-danger me-2"></i>Delete Report</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete <strong id="deleteReportName"></strong>?</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteReport">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Report Help/Guide Modal -->
<div class="modal fade" id="reportHelpModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title"><i class="bx bx-help-circle me-2"></i>Sales Report Guide</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Filters Section -->
                <h6 class="text-primary fw-bold mb-3"><i class="bx bx-filter-alt me-1"></i>Filter Options</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <td class="fw-medium text-dark" style="width: 30%;">Date From / Date To</td>
                                <td class="text-secondary">Select the date range for the report. Only orders completed within this period will be included.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Group By</td>
                                <td class="text-secondary">Choose how to group trend data: Daily, Weekly, or Monthly views.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Store Filter</td>
                                <td class="text-secondary">Filter data by specific stores. Leave empty to include all stores.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- KPI Cards Section -->
                <h6 class="text-primary fw-bold mb-3"><i class="bx bx-bar-chart me-1"></i>Key Performance Indicators (KPIs)</h6>
                <div class="table-responsive mb-4">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <td class="fw-medium text-dark" style="width: 30%;">Total Sales</td>
                                <td class="text-secondary">The grand total of all completed orders (including shipping, after discounts). Growth % compares to previous period.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Total Orders</td>
                                <td class="text-secondary">Number of completed orders in the selected period. Growth % compares to previous period.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Avg Order Value</td>
                                <td class="text-secondary">The average peso amount customers spend per order. Calculated by dividing Total Sales by Total Orders. <em>Example: If Total Sales is ₱50,000 from 10 orders, Avg Order Value = ₱5,000.</em></td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Items Sold</td>
                                <td class="text-secondary">Total quantity of all products sold across all orders.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Net Revenue</td>
                                <td class="text-secondary">Total Sales minus Discounts and Commissions. The actual revenue after deductions.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Total Discounts</td>
                                <td class="text-secondary">Sum of all discounts applied across orders.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Total Commissions</td>
                                <td class="text-secondary">Sum of all affiliate commissions paid from orders.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Total Shipping</td>
                                <td class="text-secondary">Sum of all shipping fees collected from customers.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Report Tabs Section -->
                <h6 class="text-primary fw-bold mb-3"><i class="bx bx-spreadsheet me-1"></i>Report Tabs</h6>
                <div class="accordion" id="reportTabsAccordion">
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button py-2" type="button" data-bs-toggle="collapse" data-bs-target="#helpTrend">
                                <i class="bx bx-line-chart me-2 text-primary"></i>Sales Trend
                            </button>
                        </h2>
                        <div id="helpTrend" class="accordion-collapse collapse show" data-bs-parent="#reportTabsAccordion">
                            <div class="accordion-body text-secondary">
                                <p class="mb-2">Shows sales and order volume over time as a combination chart:</p>
                                <ul class="mb-0">
                                    <li><strong>Blue Area:</strong> Sales amount in PHP (₱)</li>
                                    <li><strong>Green Line:</strong> Number of orders</li>
                                </ul>
                                <p class="mt-2 mb-0">Use the Group By filter to switch between daily, weekly, or monthly views.</p>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#helpStore">
                                <i class="bx bx-store me-2 text-primary"></i>By Store
                            </button>
                        </h2>
                        <div id="helpStore" class="accordion-collapse collapse" data-bs-parent="#reportTabsAccordion">
                            <div class="accordion-body text-secondary">
                                <p class="mb-2">Breaks down sales performance by store:</p>
                                <ul class="mb-0">
                                    <li><strong>Donut Chart:</strong> Visual distribution of sales across stores</li>
                                    <li><strong>Table Columns:</strong> Store name, order count, units sold, total sales, and percentage share</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#helpProduct">
                                <i class="bx bx-package me-2 text-primary"></i>By Product
                            </button>
                        </h2>
                        <div id="helpProduct" class="accordion-collapse collapse" data-bs-parent="#reportTabsAccordion">
                            <div class="accordion-body text-secondary">
                                <p class="mb-2">Shows top-selling products by revenue:</p>
                                <ul class="mb-0">
                                    <li><strong>Bar Chart:</strong> Top 10 products by total sales</li>
                                    <li><strong>Table Columns:</strong> Product name, store, units sold, average price, and total sales</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#helpDiscount">
                                <i class="bx bx-purchase-tag me-2 text-primary"></i>Discounts
                            </button>
                        </h2>
                        <div id="helpDiscount" class="accordion-collapse collapse" data-bs-parent="#reportTabsAccordion">
                            <div class="accordion-body text-secondary">
                                <p class="mb-2">Analyzes discount usage:</p>
                                <ul class="mb-0">
                                    <li><strong>Summary Cards:</strong> Orders with/without discounts, usage rate, total discounted</li>
                                    <li><strong>Pie Chart:</strong> Distribution of discount amounts by discount name</li>
                                    <li><strong>Table Columns:</strong> Discount name, code, trigger type (Auto Apply or Discount Code), number of uses, total discounted amount</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="accordion-item">
                        <h2 class="accordion-header">
                            <button class="accordion-button collapsed py-2" type="button" data-bs-toggle="collapse" data-bs-target="#helpCommission">
                                <i class="bx bx-user-check me-2 text-primary"></i>Commissions
                            </button>
                        </h2>
                        <div id="helpCommission" class="accordion-collapse collapse" data-bs-parent="#reportTabsAccordion">
                            <div class="accordion-body text-secondary">
                                <p class="mb-2">Shows affiliate commission breakdown:</p>
                                <ul class="mb-0">
                                    <li><strong>Summary Cards:</strong> Total commission paid, base amount (sales before commission), average commission rate</li>
                                    <li><strong>Donut Chart:</strong> Commission distribution by store</li>
                                    <li><strong>Table Columns:</strong> Affiliate name, store, order count, average commission rate, total commission earned</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons Section -->
                <h6 class="text-primary fw-bold mt-4 mb-3"><i class="bx bx-mouse me-1"></i>Action Buttons</h6>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <tbody>
                            <tr>
                                <td class="fw-medium text-dark" style="width: 30%;">Generate Report</td>
                                <td class="text-secondary">Loads/refreshes all report data based on current filter settings.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Clear Filters</td>
                                <td class="text-secondary">Resets filters to default (last 30 days) and regenerates the report.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Reset Report</td>
                                <td class="text-secondary">Clears all data and returns to blank state. Useful for starting fresh.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Export</td>
                                <td class="text-secondary">Download report data as CSV file or print the current view.</td>
                            </tr>
                            <tr>
                                <td class="fw-medium text-dark">Save Report</td>
                                <td class="text-secondary">Save current filters and report type for quick access later.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Bootstrap Datepicker -->
<script src="{{ URL::asset('build/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<!-- ApexCharts -->
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

    // Initialize date pickers with default values
    var today = new Date();
    var thirtyDaysAgo = new Date();
    thirtyDaysAgo.setDate(today.getDate() - 30);

    // Format dates as yyyy-mm-dd
    function formatDate(date) {
        var year = date.getFullYear();
        var month = String(date.getMonth() + 1).padStart(2, '0');
        var day = String(date.getDate()).padStart(2, '0');
        return year + '-' + month + '-' + day;
    }

    // Set default values first
    $('#dateFrom').val(formatDate(thirtyDaysAgo));
    $('#dateTo').val(formatDate(today));

    // Initialize datepickers
    $('#dateFrom').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        endDate: today,
        orientation: 'bottom auto'
    });

    $('#dateTo').datepicker({
        format: 'yyyy-mm-dd',
        autoclose: true,
        todayHighlight: true,
        endDate: today,
        orientation: 'bottom auto'
    });

    // Initialize Select2 for store filter
    // Custom Store Filter with Checkboxes
    var storeFilterField = $('#storeFilterField');
    var storeFilterDropdown = new bootstrap.Dropdown(storeFilterField[0]);

    // Update the display of selected stores
    function updateStoreFilterDisplay() {
        var selectedStores = [];
        $('.store-checkbox:checked').each(function() {
            selectedStores.push({
                id: $(this).val(),
                name: $(this).data('name')
            });
        });

        storeFilterField.find('.store-filter-tag').remove();

        if (selectedStores.length === 0) {
            storeFilterField.find('.store-filter-placeholder').show();
        } else {
            storeFilterField.find('.store-filter-placeholder').hide();
            selectedStores.forEach(function(store) {
                var tag = $('<span class="store-filter-tag">' +
                    '<span class="tag-text">' + escapeHtmlSimple(store.name) + '</span>' +
                    '<button type="button" class="remove-tag" data-id="' + store.id + '">&times;</button>' +
                    '</span>');
                storeFilterField.append(tag);
            });
        }
    }

    // Simple HTML escape function
    function escapeHtmlSimple(text) {
        var div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Handle checkbox changes
    $(document).on('change', '.store-checkbox', function() {
        updateStoreFilterDisplay();
    });

    // Handle tag removal
    $(document).on('click', '.store-filter-tag .remove-tag', function(e) {
        e.stopPropagation();
        var id = $(this).data('id');
        $('.store-checkbox[value="' + id + '"]').prop('checked', false);
        updateStoreFilterDisplay();
    });

    // Search filter for stores
    $('#storeFilterSearch').on('input', function() {
        var searchTerm = $(this).val().toLowerCase();
        $('.store-checkbox').each(function() {
            var storeName = $(this).data('name').toLowerCase();
            var listItem = $(this).closest('li');
            if (storeName.includes(searchTerm)) {
                listItem.show();
            } else {
                listItem.hide();
            }
        });
    });

    // Prevent dropdown from closing when clicking inside
    $('.store-filter-dropdown').on('click', function(e) {
        e.stopPropagation();
    });

    // Get selected store IDs (used by getFilterParams)
    function getSelectedStoreIds() {
        var ids = [];
        $('.store-checkbox:checked').each(function() {
            ids.push($(this).val());
        });
        return ids.length > 0 ? ids : null;
    }

    // Chart instances
    let trendChart = null;
    let storePieChart = null;
    let productBarChart = null;
    let discountPieChart = null;
    let commissionPieChart = null;
    let profitBreakdownChart = null;
    let refundByStoreChart = null;

    // Current data storage
    let currentData = {
        overview: null,
        trend: null,
        store: null,
        product: null,
        discount: null,
        commission: null,
        profitability: null,
        refunds: null
    };

    // Show blank state on initial load (user must click Generate Report)
    showBlankState();

    // Apply filters
    $('#applyFiltersBtn').on('click', function() {
        loadAllData();
    });

    // Clear filters
    $('#clearFiltersBtn').on('click', function() {
        // Reset datepickers to default range
        $('#dateFrom').datepicker('setDate', thirtyDaysAgo);
        $('#dateTo').datepicker('setDate', today);
        $('.store-checkbox').prop('checked', false);
        updateStoreFilterDisplay();
        $('#groupByFilter').val('daily');
        loadAllData();
    });

    // Reset report - clear everything and show blank state
    $('#resetReportBtn').on('click', function() {
        // Clear filters
        $('#dateFrom').val('');
        $('#dateTo').val('');
        $('.store-checkbox').prop('checked', false);
        updateStoreFilterDisplay();
        $('#groupByFilter').val('daily');

        // Reset KPI values to blank/zero state
        $('#kpiTotalSales').text('₱0.00');
        $('#kpiTotalOrders').text('0');
        $('#kpiAvgOrder').text('₱0.00');
        $('#kpiItemsSold').text('0');
        $('#kpiNetRevenue').text('₱0.00');
        $('#kpiTotalDiscounts').text('₱0.00');
        $('#kpiTotalCommissions').text('₱0.00');
        $('#kpiTotalShipping').text('₱0.00');
        $('#kpiSalesGrowth').html('<i class="bx bx-trending-up me-1"></i>0%').removeClass('growth-negative').addClass('growth-positive');
        $('#kpiOrdersGrowth').html('<i class="bx bx-trending-up me-1"></i>0%').removeClass('growth-negative').addClass('growth-positive');

        // Reset profit metrics KPIs
        $('#kpiTotalCost').text('₱0.00');
        $('#kpiGrossProfit').text('₱0.00');
        $('#kpiGrossMargin').text('0%');
        $('#kpiNetProfit').text('₱0.00');
        $('#kpiNetMargin').text('0%');
        $('#kpiTotalRefunds').text('₱0.00');

        // Clear all chart data
        if (trendChart) { trendChart.destroy(); trendChart = null; }
        if (storePieChart) { storePieChart.destroy(); storePieChart = null; }
        if (productBarChart) { productBarChart.destroy(); productBarChart = null; }
        if (discountPieChart) { discountPieChart.destroy(); discountPieChart = null; }
        if (commissionPieChart) { commissionPieChart.destroy(); commissionPieChart = null; }
        if (profitBreakdownChart) { profitBreakdownChart.destroy(); profitBreakdownChart = null; }
        if (refundByStoreChart) { refundByStoreChart.destroy(); refundByStoreChart = null; }

        // Clear chart containers with empty state message
        $('#salesTrendChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-line-chart" style="font-size: 3rem;"></i><p class="mt-2 mb-0">Generate a report to see sales trend</p></div></div>');
        $('#storePieChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-pie-chart-alt-2" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');
        $('#storeTotalSummary').html('');
        $('#productBarChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-bar-chart-alt-2" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');
        $('#discountPieChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-purchase-tag" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');
        $('#commissionPieChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-user-check" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');
        $('#profitBreakdownChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-trending-up" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');
        $('#refundByStoreChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-undo" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');

        // Clear tables
        $('#storeTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see store data</td></tr>');
        $('#productTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see product data</td></tr>');
        $('#discountTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see discount data</td></tr>');
        $('#commissionTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see commission data</td></tr>');
        $('#profitByStoreTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see profit data</td></tr>');
        $('#profitByProductTable tbody').html('<tr><td colspan="8" class="text-center text-secondary py-4">Generate a report to see profit data</td></tr>');
        $('#refundByStoreTable tbody').html('<tr><td colspan="4" class="text-center text-secondary py-4">Generate a report to see refund data</td></tr>');
        $('#refundedProductsTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see refund data</td></tr>');

        // Reset discount summary
        $('#discountOrdersWithDiscount').text('0');
        $('#discountOrdersWithoutDiscount').text('0');
        $('#discountRate').text('0%');
        $('#discountTotalAmount').text('₱0.00');

        // Reset commission summary
        $('#commissionTotal').text('₱0.00');
        $('#commissionBaseAmount').text('₱0.00');
        $('#commissionAvgRate').text('0%');

        // Reset profitability summary
        $('#profitGrossRevenue').text('₱0.00');
        $('#profitTotalCost').text('₱0.00');
        $('#profitDiscounts').text('₱0.00');
        $('#profitRefunds').text('₱0.00');
        $('#profitGrossProfit').text('₱0.00');
        $('#profitNetProfit').text('₱0.00');

        // Reset refund summary
        $('#refundTotalApproved').text('₱0.00');
        $('#refundTotalPending').text('₱0.00');
        $('#refundRate').text('0%');
        $('#refundTotalOrders').text('0');
        $('#refundStatusPending').text('0');
        $('#refundStatusApproved').text('0');
        $('#refundStatusProcessed').text('0');
        $('#refundStatusRejected').text('0');

        // Clear current data storage
        currentData = {
            overview: null,
            trend: null,
            store: null,
            product: null,
            discount: null,
            commission: null,
            profitability: null,
            refunds: null
        };

        // Reset to first tab
        $('#reportTabs a[href="#trend"]').tab('show');

        toastr.info('Report has been reset');
    });

    // Tab change handler - ApexCharts needs visible container to render properly
    $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        const target = $(e.target).attr('href');

        // Use setTimeout to ensure tab content is fully visible before rendering
        setTimeout(function() {
            if (target === '#trend') {
                // Re-render trend chart if data exists but chart doesn't
                if (currentData.trend && !trendChart) {
                    renderTrendChart(currentData.trend);
                }
            } else if (target === '#store') {
                if (!currentData.store) {
                    loadStoreData();
                } else if (!storePieChart) {
                    // Data exists but chart doesn't - re-render
                    renderStorePieChart(currentData.store);
                    renderStoreTable(currentData.store);
                }
            } else if (target === '#product') {
                if (!currentData.product) {
                    loadProductData();
                } else if (!productBarChart) {
                    renderProductBarChart(currentData.product.slice(0, 10));
                    renderProductTable(currentData.product);
                }
            } else if (target === '#profitability') {
                if (!currentData.profitability) {
                    loadProfitabilityData();
                } else if (!profitBreakdownChart) {
                    renderProfitBreakdownChart(currentData.profitability.summary);
                    renderProfitByStoreTable(currentData.profitability.byStore);
                    renderProfitByProductTable(currentData.profitability.byProduct);
                }
            } else if (target === '#refunds') {
                if (!currentData.refunds) {
                    loadRefundsData();
                } else if (!refundByStoreChart) {
                    renderRefundByStoreChart(currentData.refunds.byStore);
                    renderRefundByStoreTable(currentData.refunds.byStore);
                    renderRefundedProductsTable(currentData.refunds.refundedProducts);
                }
            } else if (target === '#discount') {
                if (!currentData.discount) {
                    loadDiscountData();
                } else if (!discountPieChart && currentData.discount.data) {
                    renderDiscountPieChart(currentData.discount.data);
                    renderDiscountTable(currentData.discount.data);
                }
            } else if (target === '#commission') {
                if (!currentData.commission) {
                    loadCommissionData();
                } else if (!commissionPieChart && currentData.commission.byStore) {
                    renderCommissionPieChart(currentData.commission.byStore);
                    renderCommissionTable(currentData.commission.byAffiliate);
                }
            }
        }, 50);
    });

    // Get filter params
    function getFilterParams() {
        return {
            dateFrom: $('#dateFrom').val(),
            dateTo: $('#dateTo').val(),
            storeIds: getSelectedStoreIds(),
            groupBy: $('#groupByFilter').val()
        };
    }

    // Format currency
    function formatCurrency(amount) {
        return '₱' + parseFloat(amount || 0).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Show blank state
    function showBlankState() {
        // Set chart containers with empty state message
        $('#salesTrendChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-line-chart" style="font-size: 3rem;"></i><p class="mt-2 mb-0">Select date range and click Generate Report</p></div></div>');
        $('#storePieChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-pie-chart-alt-2" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');
        $('#storeTotalSummary').html('');
        $('#productBarChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-bar-chart-alt-2" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');
        $('#discountPieChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-purchase-tag" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');
        $('#commissionPieChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-user-check" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');
        $('#profitBreakdownChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-trending-up" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');
        $('#refundByStoreChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-undo" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No data</p></div></div>');

        // Set tables with empty state
        $('#storeTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see store data</td></tr>');
        $('#productTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see product data</td></tr>');
        $('#discountTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see discount data</td></tr>');
        $('#commissionTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see commission data</td></tr>');
        $('#profitByStoreTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see profit data</td></tr>');
        $('#profitByProductTable tbody').html('<tr><td colspan="8" class="text-center text-secondary py-4">Generate a report to see profit data</td></tr>');
        $('#refundByStoreTable tbody').html('<tr><td colspan="4" class="text-center text-secondary py-4">Generate a report to see refund data</td></tr>');
        $('#refundedProductsTable tbody').html('<tr><td colspan="5" class="text-center text-secondary py-4">Generate a report to see refund data</td></tr>');
    }

    // Load all data
    function loadAllData() {
        loadOverviewData();
        loadTrendData();
        currentData.store = null;
        currentData.product = null;
        currentData.discount = null;
        currentData.commission = null;
        currentData.profitability = null;
        currentData.refunds = null;
    }

    // Load overview data
    function loadOverviewData() {
        $.ajax({
            url: '{{ route("ecom-reports.sales.overview") }}',
            data: getFilterParams(),
            success: function(response) {
                if (response.success) {
                    const data = response.data;
                    currentData.overview = data;

                    $('#kpiTotalSales').text(formatCurrency(data.totalSales));
                    $('#kpiTotalOrders').text(data.totalOrders.toLocaleString());
                    $('#kpiAvgOrder').text(formatCurrency(data.avgOrderValue));
                    $('#kpiItemsSold').text(data.itemsSold.toLocaleString());
                    $('#kpiNetRevenue').text(formatCurrency(data.totalNetRevenue));
                    $('#kpiTotalDiscounts').text(formatCurrency(data.totalDiscounts));
                    $('#kpiTotalCommissions').text(formatCurrency(data.totalCommissions));
                    $('#kpiTotalShipping').text(formatCurrency(data.totalShipping));

                    // Profit metrics KPIs
                    $('#kpiTotalCost').text(formatCurrency(data.totalCost || 0));
                    $('#kpiGrossProfit').text(formatCurrency(data.grossProfit || 0));
                    $('#kpiGrossMargin').text((data.grossMargin || 0) + '%');
                    $('#kpiNetProfit').text(formatCurrency(data.netProfit || 0));
                    $('#kpiNetMargin').text((data.netMargin || 0) + '%');
                    $('#kpiTotalRefunds').text(formatCurrency(data.totalRefunds || 0));

                    // Growth indicators
                    updateGrowthBadge('#kpiSalesGrowth', data.salesGrowth);
                    updateGrowthBadge('#kpiOrdersGrowth', data.ordersGrowth);
                }
            },
            error: function() {
                toastr.error('Failed to load overview data');
            }
        });
    }

    // Update growth badge
    function updateGrowthBadge(selector, growth) {
        const $badge = $(selector);
        const isPositive = growth >= 0;
        $badge.removeClass('growth-positive growth-negative')
              .addClass(isPositive ? 'growth-positive' : 'growth-negative')
              .html(`<i class="bx ${isPositive ? 'bx-trending-up' : 'bx-trending-down'} me-1"></i>${Math.abs(growth)}%`);
    }

    // Load trend data
    function loadTrendData() {
        $('#trendChartLoader').removeClass('d-none');
        $.ajax({
            url: '{{ route("ecom-reports.sales.trend") }}',
            data: getFilterParams(),
            success: function(response) {
                if (response.success) {
                    currentData.trend = response.data;
                    renderTrendChart(response.data);
                }
            },
            error: function() {
                toastr.error('Failed to load trend data');
            },
            complete: function() {
                $('#trendChartLoader').addClass('d-none');
            }
        });
    }

    // Render trend chart
    function renderTrendChart(data) {
        const container = document.querySelector("#salesTrendChart");
        if (!container) return;

        // Clear any existing content (like blank state message)
        if (!trendChart) {
            $('#salesTrendChart').empty();
        }

        const labels = data.map(d => d.label);
        const salesData = data.map(d => d.totalSales);
        const ordersData = data.map(d => d.orderCount);

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
                height: 350,
                type: 'line',
                toolbar: { show: true },
                zoom: { enabled: true }
            },
            stroke: {
                width: [0, 3],
                curve: 'smooth'
            },
            fill: {
                type: ['gradient', 'solid'],
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.7,
                    opacityTo: 0.2,
                }
            },
            labels: labels,
            markers: { size: 0 },
            yaxis: [{
                title: { text: 'Sales (₱)' },
                labels: {
                    formatter: function(val) {
                        return '₱' + val.toLocaleString();
                    }
                }
            }, {
                opposite: true,
                title: { text: 'Orders' }
            }],
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function(val, { seriesIndex }) {
                        if (seriesIndex === 0) return '₱' + val.toLocaleString();
                        return val;
                    }
                }
            },
            colors: ['#556ee6', '#34c38f']
        };

        if (trendChart) trendChart.destroy();
        trendChart = new ApexCharts(document.querySelector("#salesTrendChart"), options);
        trendChart.render();
    }

    // Load store data
    function loadStoreData() {
        $('#storeChartLoader').removeClass('d-none');
        $.ajax({
            url: '{{ route("ecom-reports.sales.by-store") }}',
            data: getFilterParams(),
            success: function(response) {
                if (response.success) {
                    currentData.store = response.data;
                    renderStorePieChart(response.data);
                    renderStoreTable(response.data);
                }
            },
            error: function() {
                toastr.error('Failed to load store data');
            },
            complete: function() {
                $('#storeChartLoader').addClass('d-none');
            }
        });
    }

    // Render store pie chart
    function renderStorePieChart(data) {
        const container = document.querySelector("#storePieChart");
        if (!container) return;

        // Clear any existing content
        if (!storePieChart) {
            $('#storePieChart').empty();
        }

        if (data.length === 0) {
            $('#storePieChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-store" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No store data</p></div></div>');
            $('#storeTotalSummary').html('');
            return;
        }

        // Calculate total sales
        const totalSales = data.reduce((sum, d) => sum + d.totalSales, 0);

        const options = {
            series: data.map(d => d.totalSales),
            chart: { type: 'donut', height: 300 },
            labels: data.map(d => d.storeName),
            colors: ['#556ee6', '#34c38f', '#f1b44c', '#f46a6a', '#50a5f1', '#74788d'],
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: function(w) {
                                    return '₱' + w.globals.seriesTotals.reduce((a, b) => a + b, 0).toLocaleString();
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: { formatter: function(val) { return '₱' + val.toLocaleString(); } }
            }
        };

        if (storePieChart) storePieChart.destroy();
        storePieChart = new ApexCharts(document.querySelector("#storePieChart"), options);
        storePieChart.render();

        // Add total summary below chart
        $('#storeTotalSummary').html(`
            <span class="text-secondary small">Total Sales:</span>
            <span class="text-success fw-bold">${formatCurrency(totalSales)}</span>
            <span class="badge bg-success ms-1">100%</span>
        `);
    }

    // Render store table
    function renderStoreTable(data) {
        let html = '';
        data.forEach(store => {
            html += `<tr>
                <td class="text-dark">${escapeHtml(store.storeName)}</td>
                <td class="text-end text-dark">${store.orderCount}</td>
                <td class="text-end text-dark">${store.unitsSold}</td>
                <td class="text-end text-dark fw-medium">${formatCurrency(store.totalSales)}</td>
                <td style="min-width: 120px;">
                    <div class="d-flex align-items-center text-nowrap">
                        <div class="percentage-bar flex-grow-1 me-2" style="min-width: 50px;">
                            <div class="percentage-bar-fill" style="width: ${store.percentage}%"></div>
                        </div>
                        <span class="text-dark small" style="min-width: 45px;">${store.percentage}%</span>
                    </div>
                </td>
            </tr>`;
        });
        $('#storeTable tbody').html(html || '<tr><td colspan="5" class="text-center text-secondary">No data</td></tr>');
    }

    // Load product data
    function loadProductData() {
        $('#productChartLoader').removeClass('d-none');
        $.ajax({
            url: '{{ route("ecom-reports.sales.by-product") }}',
            data: getFilterParams(),
            success: function(response) {
                if (response.success) {
                    currentData.product = response.data;
                    renderProductBarChart(response.data.slice(0, 10));
                    renderProductTable(response.data);
                }
            },
            error: function() {
                toastr.error('Failed to load product data');
            },
            complete: function() {
                $('#productChartLoader').addClass('d-none');
            }
        });
    }

    // Render product bar chart
    function renderProductBarChart(data) {
        const container = document.querySelector("#productBarChart");
        if (!container) return;

        // Clear any existing content
        if (!productBarChart) {
            $('#productBarChart').empty();
        }

        if (data.length === 0) {
            $('#productBarChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-package" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No product data</p></div></div>');
            return;
        }

        const options = {
            series: [{
                name: 'Sales',
                data: data.map(d => d.totalSales)
            }],
            chart: { type: 'bar', height: 350 },
            plotOptions: {
                bar: { horizontal: true, borderRadius: 4 }
            },
            dataLabels: { enabled: false },
            xaxis: {
                categories: data.map(d => d.productName.substring(0, 20)),
                labels: {
                    formatter: function(val) { return '₱' + val.toLocaleString(); }
                }
            },
            colors: ['#556ee6'],
            tooltip: {
                y: { formatter: function(val) { return '₱' + val.toLocaleString(); } }
            }
        };

        if (productBarChart) productBarChart.destroy();
        productBarChart = new ApexCharts(document.querySelector("#productBarChart"), options);
        productBarChart.render();
    }

    // Render product table
    function renderProductTable(data) {
        let html = '';
        data.forEach(product => {
            html += `<tr>
                <td class="text-dark">${escapeHtml(product.productName)}</td>
                <td class="text-secondary">${escapeHtml(product.storeName)}</td>
                <td class="text-end text-dark">${product.unitsSold}</td>
                <td class="text-end text-dark">${formatCurrency(product.avgPrice)}</td>
                <td class="text-end text-dark fw-medium">${formatCurrency(product.totalSales)}</td>
            </tr>`;
        });
        $('#productTable tbody').html(html || '<tr><td colspan="5" class="text-center text-secondary">No data</td></tr>');
    }

    // Load discount data
    function loadDiscountData() {
        $('#discountChartLoader').removeClass('d-none');
        $.ajax({
            url: '{{ route("ecom-reports.sales.discount") }}',
            data: getFilterParams(),
            success: function(response) {
                if (response.success) {
                    currentData.discount = response;
                    const summary = response.summary;

                    $('#discountOrdersWithDiscount').text(summary.ordersWithDiscount);
                    $('#discountOrdersWithoutDiscount').text(summary.ordersWithoutDiscount);
                    $('#discountRate').text(summary.discountRate + '%');
                    $('#discountTotalAmount').text(formatCurrency(summary.totalDiscountAmount));

                    renderDiscountPieChart(response.data);
                    renderDiscountTable(response.data);
                }
            },
            error: function() {
                toastr.error('Failed to load discount data');
            },
            complete: function() {
                $('#discountChartLoader').addClass('d-none');
            }
        });
    }

    // Render discount pie chart
    function renderDiscountPieChart(data) {
        const container = document.querySelector("#discountPieChart");
        if (!container) return;

        // Clear any existing content
        if (!discountPieChart) {
            $('#discountPieChart').empty();
        }

        if (data.length === 0) {
            $('#discountPieChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-purchase-tag" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No discount data</p></div></div>');
            return;
        }

        const options = {
            series: data.map(d => d.totalDiscounted),
            chart: { type: 'pie', height: 300 },
            labels: data.map(d => d.discountName || d.discountCode),
            colors: ['#f1b44c', '#f46a6a', '#50a5f1', '#556ee6', '#34c38f'],
            legend: { position: 'bottom' },
            tooltip: {
                y: { formatter: function(val) { return '₱' + val.toLocaleString(); } }
            }
        };

        if (discountPieChart) discountPieChart.destroy();
        discountPieChart = new ApexCharts(document.querySelector("#discountPieChart"), options);
        discountPieChart.render();
    }

    // Render discount table
    function renderDiscountTable(data) {
        let html = '';
        data.forEach(discount => {
            // Trigger type badge color: Auto Apply = purple, Discount Code = info
            const triggerBadgeClass = discount.triggerType === 'Auto Apply' ? 'bg-purple text-white' : 'bg-info text-white';

            // For auto-apply discounts, show "Auto" instead of code; for code-based show actual code or N/A
            let codeDisplay;
            if (discount.triggerType === 'Auto Apply') {
                codeDisplay = '<span class="text-secondary fst-italic">Auto</span>';
            } else {
                codeDisplay = discount.discountCode ? `<code>${escapeHtml(discount.discountCode)}</code>` : '<span class="text-danger">N/A</span>';
            }

            html += `<tr>
                <td class="text-dark">${escapeHtml(discount.discountName)}</td>
                <td>${codeDisplay}</td>
                <td><span class="badge ${triggerBadgeClass}">${discount.triggerType}</span></td>
                <td class="text-end text-dark">${discount.usageCount}</td>
                <td class="text-end text-dark fw-medium">${formatCurrency(discount.totalDiscounted)}</td>
            </tr>`;
        });
        $('#discountTable tbody').html(html || '<tr><td colspan="5" class="text-center text-secondary">No discounts used</td></tr>');
    }

    // Load commission data
    function loadCommissionData() {
        $('#commissionChartLoader').removeClass('d-none');
        $.ajax({
            url: '{{ route("ecom-reports.sales.commission") }}',
            data: getFilterParams(),
            success: function(response) {
                if (response.success) {
                    currentData.commission = response;
                    const summary = response.summary;

                    $('#commissionTotal').text(formatCurrency(summary.totalCommission));
                    $('#commissionBaseAmount').text(formatCurrency(summary.totalBaseAmount));
                    $('#commissionAvgRate').text(summary.avgCommissionRate + '%');

                    renderCommissionPieChart(response.byStore);
                    renderCommissionTable(response.byAffiliate);
                }
            },
            error: function() {
                toastr.error('Failed to load commission data');
            },
            complete: function() {
                $('#commissionChartLoader').addClass('d-none');
            }
        });
    }

    // Render commission pie chart
    function renderCommissionPieChart(data) {
        const container = document.querySelector("#commissionPieChart");
        if (!container) return;

        // Clear any existing content
        if (!commissionPieChart) {
            $('#commissionPieChart').empty();
        }

        if (data.length === 0) {
            $('#commissionPieChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-user-check" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No commission data</p></div></div>');
            return;
        }

        const options = {
            series: data.map(d => d.totalCommission),
            chart: { type: 'donut', height: 300 },
            labels: data.map(d => d.storeName),
            colors: ['#556ee6', '#34c38f', '#f1b44c', '#f46a6a', '#50a5f1'],
            legend: { position: 'bottom' },
            plotOptions: {
                pie: { donut: { size: '55%' } }
            },
            tooltip: {
                y: { formatter: function(val) { return '₱' + val.toLocaleString(); } }
            }
        };

        if (commissionPieChart) commissionPieChart.destroy();
        commissionPieChart = new ApexCharts(document.querySelector("#commissionPieChart"), options);
        commissionPieChart.render();
    }

    // Render commission table
    function renderCommissionTable(data) {
        let html = '';
        data.forEach(item => {
            html += `<tr>
                <td class="text-dark">${escapeHtml(item.affiliateName)}</td>
                <td class="text-secondary">${escapeHtml(item.storeName)}</td>
                <td class="text-end text-dark">${item.orderCount}</td>
                <td class="text-end text-dark">${item.avgCommissionRate.toFixed(1)}%</td>
                <td class="text-end text-dark fw-medium">${formatCurrency(item.totalCommission)}</td>
            </tr>`;
        });
        $('#commissionTable tbody').html(html || '<tr><td colspan="5" class="text-center text-secondary">No commission data</td></tr>');
    }

    // =====================================================
    // PROFITABILITY FUNCTIONS
    // =====================================================

    // Load profitability data
    function loadProfitabilityData() {
        $('#profitChartLoader').removeClass('d-none');
        $.ajax({
            url: '{{ route("ecom-reports.sales.profitability") }}',
            data: getFilterParams(),
            success: function(response) {
                if (response.success) {
                    currentData.profitability = response;
                    const summary = response.summary;

                    // Update summary cards
                    $('#profitGrossRevenue').text(formatCurrency(summary.grossRevenue));
                    $('#profitTotalCost').text(formatCurrency(summary.totalCost));
                    $('#profitDiscounts').text(formatCurrency(summary.totalDiscounts));
                    $('#profitRefunds').text(formatCurrency(summary.totalRefunds));
                    $('#profitGrossProfit').text(formatCurrency(summary.grossProfit));
                    $('#profitNetProfit').text(formatCurrency(summary.netProfit));

                    // Render charts and tables
                    renderProfitBreakdownChart(summary);
                    renderProfitByStoreTable(response.byStore);
                    renderProfitByProductTable(response.byProduct);
                }
            },
            error: function() {
                toastr.error('Failed to load profitability data');
            },
            complete: function() {
                $('#profitChartLoader').addClass('d-none');
            }
        });
    }

    // Render profit breakdown chart
    function renderProfitBreakdownChart(summary) {
        const container = document.querySelector("#profitBreakdownChart");
        if (!container) return;

        if (!profitBreakdownChart) {
            $('#profitBreakdownChart').empty();
        }

        const options = {
            series: [
                Math.max(0, summary.grossRevenue),
                Math.max(0, summary.totalCost),
                Math.max(0, summary.totalDiscounts),
                Math.max(0, summary.totalRefunds),
                Math.max(0, summary.totalCommissions)
            ],
            chart: { type: 'donut', height: 300 },
            labels: ['Gross Revenue', 'Cost (COGS)', 'Discounts', 'Refunds', 'Commissions'],
            colors: ['#34c38f', '#f46a6a', '#f1b44c', '#74788d', '#556ee6'],
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '60%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Net Profit',
                                formatter: function() {
                                    return formatCurrency(summary.netProfit);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: { formatter: function(val) { return formatCurrency(val); } }
            }
        };

        if (profitBreakdownChart) profitBreakdownChart.destroy();
        profitBreakdownChart = new ApexCharts(document.querySelector("#profitBreakdownChart"), options);
        profitBreakdownChart.render();
    }

    // Render profit by store table
    function renderProfitByStoreTable(data) {
        let html = '';
        data.forEach(store => {
            const marginClass = store.margin >= 30 ? 'text-success' : (store.margin >= 15 ? 'text-warning' : 'text-danger');
            html += `<tr>
                <td class="text-dark">${escapeHtml(store.storeName)}</td>
                <td class="text-end text-dark">${formatCurrency(store.revenue)}</td>
                <td class="text-end text-danger">${formatCurrency(store.cost)}</td>
                <td class="text-end text-success fw-medium">${formatCurrency(store.profit)}</td>
                <td class="text-end ${marginClass} fw-bold">${store.margin}%</td>
            </tr>`;
        });
        $('#profitByStoreTable tbody').html(html || '<tr><td colspan="5" class="text-center text-secondary">No profit data</td></tr>');
    }

    // Render profit by product table
    function renderProfitByProductTable(data) {
        let html = '';
        data.forEach(product => {
            const marginClass = product.margin >= 30 ? 'text-success' : (product.margin >= 15 ? 'text-warning' : 'text-danger');
            html += `<tr>
                <td class="text-dark">${escapeHtml(product.productName)}</td>
                <td class="text-secondary">${escapeHtml(product.storeName)}</td>
                <td class="text-end text-dark">${product.unitsSold}</td>
                <td class="text-end text-dark">${formatCurrency(product.avgSellingPrice)}</td>
                <td class="text-end text-danger">${formatCurrency(product.avgCostPrice)}</td>
                <td class="text-end text-dark">${formatCurrency(product.revenue)}</td>
                <td class="text-end text-success fw-medium">${formatCurrency(product.profit)}</td>
                <td class="text-end ${marginClass} fw-bold">${product.margin}%</td>
            </tr>`;
        });
        $('#profitByProductTable tbody').html(html || '<tr><td colspan="8" class="text-center text-secondary">No profit data</td></tr>');
    }

    // =====================================================
    // REFUNDS FUNCTIONS
    // =====================================================

    // Load refunds data
    function loadRefundsData() {
        $('#refundChartLoader').removeClass('d-none');
        $.ajax({
            url: '{{ route("ecom-reports.sales.refunds") }}',
            data: getFilterParams(),
            success: function(response) {
                if (response.success) {
                    currentData.refunds = response;
                    const summary = response.summary;
                    const byStatus = response.byStatus;

                    // Update summary cards
                    $('#refundTotalApproved').text(formatCurrency(summary.totalApproved));
                    $('#refundTotalPending').text(formatCurrency(summary.totalPending));
                    $('#refundRate').text(summary.refundRate + '%');
                    $('#refundTotalOrders').text(byStatus.processed);

                    // Update status badges
                    $('#refundStatusPending').text(byStatus.pending);
                    $('#refundStatusApproved').text(byStatus.approved);
                    $('#refundStatusProcessed').text(byStatus.processed);
                    $('#refundStatusRejected').text(byStatus.rejected);

                    // Render charts and tables
                    renderRefundByStoreChart(response.byStore);
                    renderRefundByStoreTable(response.byStore);
                    renderRefundedProductsTable(response.refundedProducts);
                }
            },
            error: function() {
                toastr.error('Failed to load refunds data');
            },
            complete: function() {
                $('#refundChartLoader').addClass('d-none');
            }
        });
    }

    // Render refund by store chart
    function renderRefundByStoreChart(data) {
        const container = document.querySelector("#refundByStoreChart");
        if (!container) return;

        if (!refundByStoreChart) {
            $('#refundByStoreChart').empty();
        }

        if (data.length === 0) {
            $('#refundByStoreChart').html('<div class="d-flex align-items-center justify-content-center h-100 text-secondary"><div class="text-center"><i class="bx bx-undo" style="font-size: 2.5rem;"></i><p class="mt-2 mb-0">No refund data</p></div></div>');
            return;
        }

        const options = {
            series: data.map(d => d.totalRefunded),
            chart: { type: 'donut', height: 280 },
            labels: data.map(d => d.storeName),
            colors: ['#f46a6a', '#f1b44c', '#50a5f1', '#556ee6', '#34c38f'],
            legend: { position: 'bottom' },
            plotOptions: {
                pie: {
                    donut: {
                        size: '55%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                formatter: function(w) {
                                    return formatCurrency(w.globals.seriesTotals.reduce((a, b) => a + b, 0));
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: { formatter: function(val) { return formatCurrency(val); } }
            }
        };

        if (refundByStoreChart) refundByStoreChart.destroy();
        refundByStoreChart = new ApexCharts(document.querySelector("#refundByStoreChart"), options);
        refundByStoreChart.render();
    }

    // Render refund by store table
    function renderRefundByStoreTable(data) {
        let html = '';
        data.forEach(store => {
            html += `<tr>
                <td class="text-dark">${escapeHtml(store.storeName)}</td>
                <td class="text-end text-dark">${store.refundCount}</td>
                <td class="text-end text-danger fw-medium">${formatCurrency(store.totalRefunded)}</td>
                <td class="text-end text-dark">${formatCurrency(store.avgRefund)}</td>
            </tr>`;
        });
        $('#refundByStoreTable tbody').html(html || '<tr><td colspan="4" class="text-center text-secondary">No refund data</td></tr>');
    }

    // Render refunded products table
    function renderRefundedProductsTable(data) {
        let html = '';
        data.forEach(product => {
            html += `<tr>
                <td class="text-dark">${escapeHtml(product.productName)}</td>
                <td class="text-secondary">${escapeHtml(product.storeName)}</td>
                <td class="text-end text-dark">${product.refundCount}</td>
                <td class="text-end text-dark">${product.totalQuantity}</td>
                <td class="text-end text-danger fw-medium">${formatCurrency(product.totalRefunded)}</td>
            </tr>`;
        });
        $('#refundedProductsTable tbody').html(html || '<tr><td colspan="5" class="text-center text-secondary">No refunded products</td></tr>');
    }

    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Save report
    $('#confirmSaveReport').on('click', function() {
        const reportName = $('#saveReportName').val().trim();
        const reportType = $('#saveReportType').val();
        const notes = $('#saveReportNotes').val().trim();

        if (!reportName) {
            toastr.error('Please enter a report name');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        // Get current data based on report type
        let reportData = currentData[reportType === 'by_store' ? 'store' :
                                     reportType === 'by_product' ? 'product' :
                                     reportType === 'discount' ? 'discount' :
                                     reportType === 'commission' ? 'commission' :
                                     reportType === 'trend' ? 'trend' : 'overview'];

        $.ajax({
            url: '{{ route("ecom-reports.sales.save") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                reportName: reportName,
                reportType: reportType,
                dateFrom: $('#dateFrom').val(),
                dateTo: $('#dateTo').val(),
                filters: {
                    storeIds: getSelectedStoreIds(),
                    groupBy: $('#groupByFilter').val()
                },
                reportData: reportData,
                notes: notes
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#saveReportModal').modal('hide');
                    $('#saveReportName').val('');
                    $('#saveReportNotes').val('');
                    loadSavedReports();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Error saving report');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Report');
            }
        });
    });

    // Load saved reports
    window.loadSavedReports = function() {
        $.ajax({
            url: '{{ route("ecom-reports.sales.saved") }}',
            success: function(response) {
                if (response.success) {
                    renderSavedReportsList(response.data);
                }
            }
        });
    };

    // Render saved reports list
    function renderSavedReportsList(reports) {
        if (reports.length === 0) {
            $('#savedReportsList').html('<div class="text-center py-4"><i class="bx bx-folder-open text-secondary" style="font-size: 2rem;"></i><p class="text-secondary mb-0 small">No saved reports yet</p></div>');
            return;
        }

        let html = '';
        reports.forEach(report => {
            html += `<div class="saved-report-item" onclick="loadSavedReport(${report.id})">
                <div class="d-flex justify-content-between align-items-start">
                    <div>
                        <h6 class="mb-1 text-dark">${escapeHtml(report.reportName)}</h6>
                        <small class="text-secondary">${report.reportTypeLabel} &bull; ${report.dateRange}</small>
                    </div>
                    <button class="btn btn-sm btn-outline-danger" onclick="event.stopPropagation(); deleteSavedReport(${report.id}, '${escapeHtml(report.reportName)}')">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>`;
        });
        $('#savedReportsList').html(html);
    }

    // Load saved report
    window.loadSavedReport = function(id) {
        $.ajax({
            url: `/ecom-reports-sales/load/${id}`,
            success: function(response) {
                if (response.success) {
                    const report = response.report;
                    toastr.info('Loading saved report: ' + report.reportName);

                    // Set filters
                    if (report.dateFrom) $('#dateFrom').val(report.dateFrom);
                    if (report.dateTo) $('#dateTo').val(report.dateTo);
                    if (report.filters?.storeIds) {
                        $('.store-checkbox').prop('checked', false);
                        report.filters.storeIds.forEach(function(id) {
                            $('.store-checkbox[value="' + id + '"]').prop('checked', true);
                        });
                        updateStoreFilterDisplay();
                    }
                    if (report.filters?.groupBy) {
                        $('#groupByFilter').val(report.filters.groupBy);
                    }

                    // Reload with saved filters
                    loadAllData();
                }
            },
            error: function() {
                toastr.error('Failed to load report');
            }
        });
    };

    // Delete saved report
    let reportToDelete = null;
    window.deleteSavedReport = function(id, name) {
        reportToDelete = id;
        $('#deleteReportName').text(name);
        $('#deleteReportModal').modal('show');
    };

    $('#confirmDeleteReport').on('click', function() {
        if (!reportToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

        $.ajax({
            url: `/ecom-reports-sales/${reportToDelete}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                    $('#deleteReportModal').modal('hide');
                    loadSavedReports();
                } else {
                    toastr.error(response.message);
                }
            },
            error: function() {
                toastr.error('Failed to delete report');
            },
            complete: function() {
                $btn.prop('disabled', false).html('Delete');
                reportToDelete = null;
            }
        });
    });

    // Export report
    window.exportReport = function(format) {
        const params = new URLSearchParams(getFilterParams());
        params.append('format', format);
        params.append('reportType', $('#saveReportType').val() || 'overview');

        window.location.href = '{{ route("ecom-reports.sales.export") }}?' + params.toString();
    };
});
</script>
@endsection
