@extends('layouts.master')

@section('title') Leads @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .status-filter-btn {
        padding: 0.35rem 0.75rem;
        border-radius: 20px;
        font-size: 13px;
        border: 1px solid #e9ebec;
        background: #fff;
        color: #495057;
        transition: all 0.2s ease;
    }
    .status-filter-btn:hover {
        background: #f8f9fa;
    }
    .status-filter-btn.active {
        background: var(--bs-primary);
        color: #fff;
        border-color: var(--bs-primary);
    }
    .lead-card {
        transition: all 0.2s ease;
        border-left: 3px solid transparent;
    }
    .lead-card:hover {
        box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    }
    .lead-card.status-new { border-left-color: #50a5f1; }
    .lead-card.status-contacted { border-left-color: #556ee6; }
    .lead-card.status-qualified { border-left-color: #34c38f; }
    .lead-card.status-proposal { border-left-color: #f1b44c; }
    .lead-card.status-negotiation { border-left-color: #74788d; }
    .lead-card.status-won { border-left-color: #34c38f; }
    .lead-card.status-lost { border-left-color: #f46a6a; }
    .lead-card.status-dormant { border-left-color: #343a40; }
    .priority-badge {
        font-size: 11px;
        padding: 2px 8px;
        border-radius: 10px;
    }
    .avatar-circle {
        width: 42px;
        height: 42px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        color: #fff;
    }
    .lead-row {
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .lead-row:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.05);
    }
    .stats-card {
        border-radius: 8px;
        padding: 1rem;
        text-align: center;
    }
    .stats-card h3 {
        margin-bottom: 0.25rem;
    }
    .loading-overlay {
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background: rgba(255,255,255,0.7);
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 10;
    }
    .table-container {
        position: relative;
        min-height: 200px;
    }
    .source-badge {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 12px;
    }
    .column-toggle-item {
        padding: 6px 12px;
        cursor: pointer;
        transition: background-color 0.15s ease;
    }
    .column-toggle-item:hover {
        background-color: #f8f9fa;
    }
    .column-toggle-item .form-check-input {
        cursor: pointer;
    }
    .custom-field-filter-group {
        display: flex;
        gap: 4px;
        align-items: center;
    }
    .filter-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }
    /* Import Modal Styles */
    .import-steps {
        position: relative;
    }
    .import-step {
        display: flex;
        flex-direction: column;
        align-items: center;
        position: relative;
        z-index: 1;
    }
    .step-circle {
        width: 36px;
        height: 36px;
        border-radius: 50%;
        background: #e9ebec;
        color: #74788d;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        transition: all 0.3s ease;
    }
    .import-step.active .step-circle,
    .import-step.completed .step-circle {
        background: #34c38f;
        color: #fff;
    }
    .step-label {
        font-size: 12px;
        color: #74788d;
        margin-top: 4px;
        white-space: nowrap;
    }
    .import-step.active .step-label {
        color: #34c38f;
        font-weight: 600;
    }
    .step-line {
        width: 60px;
        height: 2px;
        background: #e9ebec;
        margin: 0 8px;
        margin-bottom: 20px;
    }
    .upload-zone {
        border: 2px dashed #ced4da;
        border-radius: 8px;
        padding: 2rem;
        transition: all 0.3s ease;
        cursor: pointer;
    }
    .upload-zone:hover,
    .upload-zone.dragover {
        border-color: #556ee6;
        background: rgba(85, 110, 230, 0.05);
    }
    .mapping-select {
        font-size: 13px;
    }
    .custom-field-input {
        font-size: 13px;
    }
    .required-field::after {
        content: ' *';
        color: #f46a6a;
    }

    /* =====================================================
       MOBILE RESPONSIVE STYLES WITH ANIMATIONS
       ===================================================== */

    /* Smooth transitions */
    .btn, .form-control, .form-select, .lead-card, .status-filter-btn {
        transition: all 0.3s ease;
    }

    /* Card entrance animation */
    @keyframes slideInUp {
        from {
            opacity: 0;
            transform: translateY(15px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    @keyframes fadeIn {
        from { opacity: 0; }
        to { opacity: 1; }
    }

    .lead-card {
        animation: slideInUp 0.3s ease forwards;
    }

    /* Staggered animation for lead cards */
    .lead-card:nth-child(1) { animation-delay: 0.05s; }
    .lead-card:nth-child(2) { animation-delay: 0.1s; }
    .lead-card:nth-child(3) { animation-delay: 0.15s; }
    .lead-card:nth-child(4) { animation-delay: 0.2s; }
    .lead-card:nth-child(5) { animation-delay: 0.25s; }

    /* Small monitors (1280px - 1400px) */
    @media (max-width: 1400px) {
        .stats-card {
            padding: 0.85rem;
        }

        .stats-card h3 {
            font-size: 1.3rem;
        }

        .status-filter-btn {
            padding: 0.3rem 0.65rem;
            font-size: 12.5px;
        }

        .lead-row td {
            padding: 11px 10px;
            font-size: 13px;
        }

        .avatar-circle {
            width: 40px;
            height: 40px;
            font-size: 13px;
        }

        .priority-badge {
            font-size: 10.5px;
            padding: 2px 7px;
        }

        .source-badge {
            font-size: 11.5px;
            padding: 3px 7px;
        }
    }

    /* iPad landscape / 1024px monitors */
    @media (max-width: 1024px) {
        .stats-card {
            padding: 0.75rem;
        }

        .stats-card h3 {
            font-size: 1.2rem;
        }

        .stats-card p {
            font-size: 12px;
        }

        .status-filter-btn {
            padding: 0.28rem 0.55rem;
            font-size: 12px;
        }

        .filter-row {
            gap: 6px;
        }

        .lead-row td {
            padding: 10px 8px;
            font-size: 12.5px;
        }

        .avatar-circle {
            width: 36px;
            height: 36px;
            font-size: 12px;
        }

        .lead-card h6 {
            font-size: 13px;
        }

        .lead-card .text-secondary {
            font-size: 12px;
        }

        .priority-badge {
            font-size: 10px;
            padding: 2px 6px;
        }

        .source-badge {
            font-size: 11px;
            padding: 2px 6px;
        }

        /* Hide less important columns */
        .table th:nth-child(5),
        .table td:nth-child(5),
        .table th:nth-child(6),
        .table td:nth-child(6) {
            display: none;
        }

        /* Form controls */
        .form-control, .form-select {
            font-size: 13px;
            padding: 7px 10px;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Stats row - 4 columns still but compact */
        .stats-row > div {
            padding-left: 6px;
            padding-right: 6px;
        }
    }

    /* Tablet Styles */
    @media (max-width: 991px) {
        .stats-card {
            padding: 0.75rem;
        }

        .stats-card h3 {
            font-size: 1.25rem;
        }

        .filter-row {
            gap: 6px;
        }

        .status-filter-btn {
            padding: 0.3rem 0.6rem;
            font-size: 12px;
        }

        .lead-row td {
            padding: 10px 8px;
            font-size: 13px;
        }

        .avatar-circle {
            width: 36px;
            height: 36px;
            font-size: 12px;
        }

        /* Stats row - 2 columns on tablet */
        .stats-row > [class*="col-md-"] {
            flex: 0 0 50%;
            max-width: 50%;
            margin-bottom: 10px;
        }
    }

    /* Mobile Styles */
    @media (max-width: 767px) {
        /* Stats cards - 2 per row */
        .stats-row .col-6 {
            margin-bottom: 10px;
        }

        .stats-card {
            padding: 12px 10px;
        }

        .stats-card h3 {
            font-size: 1.1rem;
        }

        .stats-card p {
            font-size: 11px;
        }

        /* Filter section */
        .filter-row {
            flex-direction: column;
            align-items: stretch;
            gap: 10px;
        }

        /* Status filter - horizontal scroll */
        .status-filter-container {
            display: flex;
            overflow-x: auto;
            -webkit-overflow-scrolling: touch;
            scrollbar-width: none;
            -ms-overflow-style: none;
            padding-bottom: 5px;
            gap: 6px;
        }

        .status-filter-container::-webkit-scrollbar {
            display: none;
        }

        .status-filter-btn {
            flex: 0 0 auto;
            padding: 8px 14px;
            font-size: 13px;
        }

        /* Search and filter inputs */
        .search-input-group {
            width: 100%;
        }

        /* Table responsive */
        .table-responsive {
            border: none;
        }

        .table {
            font-size: 12px;
        }

        .table thead th {
            font-size: 11px;
            padding: 10px 6px;
            white-space: nowrap;
        }

        /* Hide less important columns */
        .table th:nth-child(5),
        .table td:nth-child(5),
        .table th:nth-child(6),
        .table td:nth-child(6) {
            display: none;
        }

        .lead-row td {
            padding: 10px 6px;
            vertical-align: middle;
        }

        .avatar-circle {
            width: 32px;
            height: 32px;
            font-size: 11px;
        }

        .priority-badge {
            font-size: 10px;
            padding: 2px 6px;
        }

        .source-badge {
            font-size: 10px;
            padding: 2px 6px;
        }

        /* Action buttons */
        .btn-sm {
            padding: 5px 8px;
            font-size: 12px;
        }

        /* Card header */
        .card-header {
            flex-wrap: wrap;
            gap: 10px;
        }

        .card-header h4 {
            width: 100%;
            font-size: 16px;
        }

        .card-header .btn {
            flex: 1;
            min-width: 120px;
        }

        /* Pagination */
        .pagination .page-link {
            padding: 8px 12px;
            font-size: 13px;
        }

        /* Modal */
        .modal-dialog {
            margin: 10px;
            max-width: calc(100% - 20px);
        }

        .modal-body {
            padding: 15px;
        }

        /* Import steps */
        .import-step .step-label {
            font-size: 10px;
        }

        .step-circle {
            width: 30px;
            height: 30px;
            font-size: 12px;
        }

        /* Column toggle dropdown */
        .dropdown-menu {
            max-height: 300px;
            overflow-y: auto;
        }

        /* Breadcrumb */
        .page-title-box h4 {
            font-size: 16px;
        }
    }

    /* Small Mobile */
    @media (max-width: 575px) {
        .card-body {
            padding: 12px;
        }

        /* Table as cards */
        .table thead {
            display: none;
        }

        .table tbody tr {
            display: block;
            margin-bottom: 12px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
            border-left: 3px solid var(--bs-primary);
        }

        .table tbody tr.status-new { border-left-color: #50a5f1; }
        .table tbody tr.status-contacted { border-left-color: #556ee6; }
        .table tbody tr.status-qualified { border-left-color: #34c38f; }
        .table tbody tr.status-proposal { border-left-color: #f1b44c; }
        .table tbody tr.status-negotiation { border-left-color: #74788d; }
        .table tbody tr.status-won { border-left-color: #34c38f; }
        .table tbody tr.status-lost { border-left-color: #f46a6a; }

        .table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 8px 0;
            border: none;
            border-bottom: 1px solid #f0f0f0;
        }

        .table tbody td:last-child {
            border-bottom: none;
            justify-content: flex-end;
            padding-top: 12px;
        }

        .table tbody td::before {
            content: attr(data-label);
            font-weight: 600;
            color: #495057;
            font-size: 11px;
        }

        /* Show hidden columns */
        .table th:nth-child(5),
        .table td:nth-child(5),
        .table th:nth-child(6),
        .table td:nth-child(6) {
            display: flex;
        }

        /* Stats - single column on very small */
        .stats-row .col-6 {
            width: 100%;
        }

        /* Header buttons stack */
        .card-header .btn {
            width: 100%;
        }

        /* Touch-friendly */
        .btn {
            min-height: 44px;
        }

        .btn-sm {
            min-height: 36px;
        }

        .status-filter-btn {
            min-height: 40px;
        }

        /* Form inputs */
        .form-control, .form-select {
            font-size: 16px; /* Prevent zoom on iOS */
            padding: 10px 12px;
        }
    }

    /* Touch device improvements */
    @media (hover: none) and (pointer: coarse) {
        .lead-row:active {
            background-color: rgba(var(--bs-primary-rgb), 0.1);
        }

        .btn:active {
            transform: scale(0.98);
        }

        .status-filter-btn:active {
            transform: scale(0.95);
        }

        .form-check-input {
            width: 20px;
            height: 20px;
        }
    }

    /* Loading animation */
    .btn .bx-loader-alt {
        animation: spin 1s linear infinite;
    }

    @keyframes spin {
        from { transform: rotate(0deg); }
        to { transform: rotate(360deg); }
    }
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') CRM @endslot
        @slot('title') Leads @endslot
    @endcomponent

    <!-- Stats Cards -->
    <div class="row mb-3">
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="stats-card bg-light">
                <h3 class="text-dark mb-1" id="stat-total">0</h3>
                <small class="text-secondary">Total Leads</small>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="stats-card bg-info bg-opacity-10">
                <h3 class="text-info mb-1" id="stat-new">0</h3>
                <small class="text-secondary">New</small>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="stats-card bg-primary bg-opacity-10">
                <h3 class="text-primary mb-1" id="stat-contacted">0</h3>
                <small class="text-secondary">Contacted</small>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="stats-card bg-success bg-opacity-10">
                <h3 class="text-success mb-1" id="stat-qualified">0</h3>
                <small class="text-secondary">Qualified</small>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="stats-card bg-warning bg-opacity-10">
                <h3 class="text-warning mb-1" id="stat-proposal">0</h3>
                <small class="text-secondary">Proposal</small>
            </div>
        </div>
        <div class="col-md-2 col-sm-4 col-6 mb-2">
            <div class="stats-card bg-success bg-opacity-10">
                <h3 class="text-success mb-1" id="stat-won">0</h3>
                <small class="text-secondary">Won</small>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Header -->
                    <div class="d-flex flex-wrap align-items-center mb-3 gap-2">
                        <h4 class="card-title me-3 mb-0">Lead Management</h4>
                        <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                            <!-- Column Settings -->
                            <div class="dropdown">
                                <button class="btn btn-outline-secondary btn-sm dropdown-toggle" type="button" id="columnSettingsBtn" data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false" title="Column Settings">
                                    <i class="bx bx-columns me-1"></i> Columns
                                </button>
                                <div class="dropdown-menu dropdown-menu-end p-2" style="min-width: 220px; max-height: 400px; overflow-y: auto;">
                                    <h6 class="dropdown-header px-2">Show/Hide Columns</h6>
                                    <div class="dropdown-divider"></div>
                                    <div id="columnToggleList">
                                        <!-- Will be populated by JS -->
                                    </div>
                                    <div class="dropdown-divider"></div>
                                    <div class="d-flex gap-2 px-2">
                                        <button type="button" class="btn btn-sm btn-outline-secondary flex-grow-1" id="resetColumnsBtn">
                                            <i class="bx bx-reset"></i> Reset
                                        </button>
                                        <button type="button" class="btn btn-sm btn-primary flex-grow-1" id="saveColumnsBtn">
                                            <i class="bx bx-save"></i> Save
                                        </button>
                                    </div>
                                </div>
                            </div>

                            <!-- Import Button -->
                            <button type="button" class="btn btn-success btn-sm" id="importBtn">
                                <i class="bx bx-import me-1"></i> Import
                            </button>

                            <!-- Add Lead Button -->
                            <a href="{{ route('crm-leads.create') }}" class="btn btn-primary btn-sm">
                                <i class="bx bx-plus me-1"></i> Add Lead
                            </a>
                        </div>
                    </div>

                    <!-- Filters Row -->
                    <div class="filter-row mb-3">
                        <!-- Search -->
                        <input type="text" class="form-control form-control-sm" id="searchInput"
                               placeholder="Search leads..." style="width: 180px;">

                        <!-- Source Filter -->
                        <select class="form-select form-select-sm" id="sourceFilter" style="width: auto; min-width: 130px;">
                            <option value="">All Sources</option>
                            @foreach($sources as $source)
                                <option value="{{ $source->id }}">{{ $source->sourceName }}</option>
                            @endforeach
                        </select>

                        <!-- Priority Filter -->
                        <select class="form-select form-select-sm" id="priorityFilter" style="width: auto; min-width: 120px;">
                            <option value="">All Priorities</option>
                            <option value="urgent">Urgent</option>
                            <option value="high">High</option>
                            <option value="medium">Medium</option>
                            <option value="low">Low</option>
                        </select>

                        <!-- Store Target Filter -->
                        @if($stores->count() > 0)
                        <select class="form-select form-select-sm" id="storeFilter" style="width: auto; min-width: 130px;">
                            <option value="">All Stores</option>
                            @foreach($stores as $store)
                                <option value="{{ $store->id }}">{{ $store->storeName }}</option>
                            @endforeach
                        </select>
                        @endif

                        <!-- Custom Field Filter -->
                        @if($customFieldNames->count() > 0)
                        <div class="custom-field-filter-group">
                            <select class="form-select form-select-sm" id="customFieldFilter" style="width: auto; min-width: 130px;">
                                <option value="">Custom Field</option>
                                @foreach($customFieldNames as $fieldName)
                                    <option value="{{ $fieldName }}">{{ $fieldName }}</option>
                                @endforeach
                            </select>
                            <input type="text" class="form-control form-control-sm" id="customFieldValueFilter"
                                   placeholder="Value..." style="width: 100px; display: none;">
                        </div>
                        @endif

                        <!-- Clear Filters -->
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearFiltersBtn" title="Clear all filters">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>

                    <!-- Status Filter Pills -->
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        <button type="button" class="status-filter-btn active" data-status="">
                            <i class="bx bx-list-ul me-1"></i> All
                        </button>
                        <button type="button" class="status-filter-btn" data-status="new">
                            <i class="mdi mdi-star-outline me-1"></i> New
                        </button>
                        <button type="button" class="status-filter-btn" data-status="contacted">
                            <i class="mdi mdi-phone-check me-1"></i> Contacted
                        </button>
                        <button type="button" class="status-filter-btn" data-status="qualified">
                            <i class="mdi mdi-check-circle me-1"></i> Qualified
                        </button>
                        <button type="button" class="status-filter-btn" data-status="proposal">
                            <i class="mdi mdi-file-document-outline me-1"></i> Proposal
                        </button>
                        <button type="button" class="status-filter-btn" data-status="negotiation">
                            <i class="mdi mdi-handshake me-1"></i> Negotiation
                        </button>
                        <button type="button" class="status-filter-btn" data-status="won">
                            <i class="mdi mdi-trophy me-1"></i> Won
                        </button>
                        <button type="button" class="status-filter-btn" data-status="lost">
                            <i class="mdi mdi-close-circle me-1"></i> Lost
                        </button>
                        <button type="button" class="status-filter-btn" data-status="dormant">
                            <i class="mdi mdi-sleep me-1"></i> Dormant
                        </button>
                    </div>

                    <!-- Leads Table -->
                    <div class="table-container">
                        <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="leadsTable">
                                <thead class="table-light">
                                    <tr id="tableHeaderRow">
                                        <!-- Headers will be generated dynamically -->
                                    </tr>
                                </thead>
                                <tbody id="leadsTableBody">
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                        <div class="d-flex align-items-center gap-2">
                            <span class="text-secondary small">Show</span>
                            <select class="form-select form-select-sm" id="perPageSelect" style="width: auto;">
                                <option value="10">10</option>
                                <option value="25" selected>25</option>
                                <option value="50">50</option>
                                <option value="100">100</option>
                            </select>
                            <span class="text-secondary small">entries</span>
                            <span class="text-secondary small ms-2" id="paginationInfo"></span>
                        </div>
                        <nav aria-label="Leads pagination">
                            <ul class="pagination pagination-sm mb-0" id="paginationContainer"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Status Update Modal -->
    <div class="modal fade" id="statusModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="mdi mdi-swap-horizontal me-2"></i>Update Status</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="statusLeadId">
                    <p class="text-dark mb-3">Update status for: <strong id="statusLeadName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">New Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="newStatus">
                            <option value="new">New</option>
                            <option value="contacted">Contacted</option>
                            <option value="qualified">Qualified</option>
                            <option value="proposal">Proposal Sent</option>
                            <option value="negotiation">Negotiation</option>
                            <option value="won">Won</option>
                            <option value="lost">Lost</option>
                            <option value="dormant">Dormant</option>
                        </select>
                    </div>
                    <div class="mb-3" id="lossReasonGroup" style="display: none;">
                        <label class="form-label">Loss Reason</label>
                        <input type="text" class="form-control" id="lossReason" placeholder="Why was this lead lost?">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveStatusBtn">
                        <i class="bx bx-save me-1"></i> Update
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Activity Modal -->
    <div class="modal fade" id="activityModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="mdi mdi-note-plus me-2"></i>Log Activity</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="activityLeadId">
                    <p class="text-dark mb-3">Log activity for: <strong id="activityLeadName"></strong></p>
                    <div class="mb-3">
                        <label class="form-label">Activity Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="activityType">
                            <option value="call_outbound">Outbound Call</option>
                            <option value="call_inbound">Inbound Call</option>
                            <option value="email_sent">Email Sent</option>
                            <option value="email_received">Email Received</option>
                            <option value="meeting">Meeting</option>
                            <option value="note" selected>Note</option>
                            <option value="follow_up">Follow-up</option>
                            <option value="proposal_sent">Proposal Sent</option>
                            <option value="document_sent">Document Sent</option>
                            <option value="social_media">Social Media</option>
                            <option value="other">Other</option>
                        </select>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="activityDescription" rows="3" placeholder="Describe the activity..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveActivityBtn">
                        <i class="bx bx-save me-1"></i> Save
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-trash text-danger me-2"></i>Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-0">Are you sure you want to delete lead <strong id="deleteLeadName"></strong>?</p>
                    <p class="text-secondary small mt-2 mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                        <i class="bx bx-trash me-1"></i> Delete
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Import Leads Modal -->
    <div class="modal fade" id="importModal" tabindex="-1" data-bs-backdrop="static">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-import text-success me-2"></i>Import Leads</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <!-- Step Indicators -->
                    <div class="d-flex justify-content-center mb-4">
                        <div class="import-steps d-flex align-items-center">
                            <div class="import-step active" data-step="1">
                                <div class="step-circle">1</div>
                                <div class="step-label">Upload File</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="import-step" data-step="2">
                                <div class="step-circle">2</div>
                                <div class="step-label">Map Columns</div>
                            </div>
                            <div class="step-line"></div>
                            <div class="import-step" data-step="3">
                                <div class="step-circle">3</div>
                                <div class="step-label">Import</div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 1: Upload File -->
                    <div class="import-step-content" id="importStep1">
                        <div class="text-center py-4">
                            <div class="upload-zone" id="uploadZone">
                                <i class="bx bx-cloud-upload" style="font-size: 3rem; color: #556ee6;"></i>
                                <h5 class="text-dark mt-2">Upload CSV or Excel File</h5>
                                <p class="text-secondary mb-3">Drag & drop your file here, or click to browse</p>
                                <input type="file" id="importFile" accept=".csv,.txt,.xlsx,.xls" style="display: none;">
                                <button type="button" class="btn btn-primary" id="browseFileBtn">
                                    <i class="bx bx-folder-open me-1"></i> Browse Files
                                </button>
                                <p class="text-secondary small mt-3 mb-0">Supported formats: CSV, Excel (xlsx, xls) - Max 10MB</p>
                            </div>
                            <div class="selected-file mt-3" id="selectedFileInfo" style="display: none;">
                                <div class="alert alert-success d-flex align-items-center">
                                    <i class="bx bx-file me-2" style="font-size: 1.5rem;"></i>
                                    <div class="flex-grow-1 text-start">
                                        <strong id="selectedFileName"></strong>
                                        <br><small id="selectedFileSize"></small>
                                    </div>
                                    <button type="button" class="btn btn-sm btn-outline-danger" id="removeFileBtn">
                                        <i class="bx bx-x"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Step 2: Map Columns -->
                    <div class="import-step-content" id="importStep2" style="display: none;">
                        <div class="alert alert-info d-flex align-items-start mb-3">
                            <i class="bx bx-info-circle me-2 mt-1"></i>
                            <div>
                                <strong>Map your columns to lead fields</strong>
                                <p class="mb-0 small">Required fields are marked with *. Use "Custom Data" to add fields that don't exist in the standard lead fields.</p>
                            </div>
                        </div>

                        <!-- Default Settings -->
                        <div class="card bg-light mb-3">
                            <div class="card-body py-2">
                                <h6 class="text-dark mb-2"><i class="mdi mdi-cog me-1"></i>Default Settings for All Imported Leads</h6>
                                <div class="row">
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label small">Status</label>
                                        <select class="form-select form-select-sm" id="importDefaultStatus">
                                            @foreach(\App\Models\CrmLead::STATUS_OPTIONS as $value => $option)
                                                <option value="{{ $value }}" {{ $value == 'new' ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label small">Priority</label>
                                        <select class="form-select form-select-sm" id="importDefaultPriority">
                                            @foreach(\App\Models\CrmLead::PRIORITY_OPTIONS as $value => $option)
                                                <option value="{{ $value }}" {{ $value == 'medium' ? 'selected' : '' }}>{{ $option['label'] }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label small">Lead Source</label>
                                        <select class="form-select form-select-sm" id="importDefaultSource">
                                            <option value="">None</option>
                                            @foreach($sources as $source)
                                                <option value="{{ $source->id }}">{{ $source->sourceName }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div class="col-md-3 mb-2">
                                        <label class="form-label small">Referred By</label>
                                        <input type="text" class="form-control form-control-sm" id="importReferredBy" placeholder="Referral name...">
                                    </div>
                                </div>

                                <!-- Store Targets -->
                                @if($stores->count() > 0)
                                <div class="mt-2 pt-2 border-top">
                                    <label class="form-label small mb-1"><i class="mdi mdi-store me-1"></i>Store Targets</label>
                                    <div class="d-flex flex-wrap gap-2">
                                        @foreach($stores as $store)
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input import-store-target" type="checkbox" value="{{ $store->id }}" id="importStore_{{ $store->id }}">
                                            <label class="form-check-label small text-dark" for="importStore_{{ $store->id }}">
                                                @if($store->storeLogo)
                                                    <img src="{{ asset($store->storeLogo) }}" alt="{{ $store->storeName }}" style="width: 16px; height: 16px; object-fit: contain; margin-right: 2px;">
                                                @endif
                                                {{ $store->storeName }}
                                            </label>
                                        </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif

                                <!-- Custom Fields -->
                                <div class="mt-2 pt-2 border-top">
                                    <div class="d-flex justify-content-between align-items-center mb-2">
                                        <label class="form-label small mb-0"><i class="mdi mdi-database-plus me-1"></i>Custom Fields (applied to all)</label>
                                        <button type="button" class="btn btn-soft-warning btn-sm py-0" id="addImportCustomField">
                                            <i class="mdi mdi-plus"></i> Add
                                        </button>
                                    </div>
                                    <div id="importCustomFieldsList">
                                        <!-- Custom fields will be added here -->
                                    </div>
                                    <small class="text-secondary" id="noImportCustomFields">No custom fields added. Click "Add" to add static custom data to all imported leads.</small>
                                </div>
                            </div>
                        </div>

                        <!-- Column Mappings -->
                        <div class="table-responsive" style="max-height: 350px; overflow-y: auto;">
                            <table class="table table-sm table-bordered mb-0">
                                <thead class="table-light sticky-top">
                                    <tr>
                                        <th style="width: 40%;">File Column</th>
                                        <th style="width: 40%;">Map To Field</th>
                                        <th style="width: 20%;">Custom Field Name</th>
                                    </tr>
                                </thead>
                                <tbody id="columnMappingsBody">
                                    <!-- Will be populated dynamically -->
                                </tbody>
                            </table>
                        </div>

                        <!-- Data Preview -->
                        <div class="mt-3">
                            <h6 class="text-dark"><i class="bx bx-show me-1"></i>Data Preview (First 5 rows)</h6>
                            <div class="table-responsive" style="max-height: 150px; overflow-y: auto;">
                                <table class="table table-sm table-striped mb-0" id="previewTable">
                                    <thead class="table-light">
                                        <tr id="previewHeaders"></tr>
                                    </thead>
                                    <tbody id="previewBody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>

                    <!-- Step 3: Import Results -->
                    <div class="import-step-content" id="importStep3" style="display: none;">
                        <div class="text-center py-4" id="importingStatus">
                            <div class="spinner-border text-success mb-3" role="status" style="width: 3rem; height: 3rem;">
                                <span class="visually-hidden">Importing...</span>
                            </div>
                            <h5 class="text-dark">Importing Leads...</h5>
                            <p class="text-secondary">Please wait while we process your file.</p>
                        </div>
                        <div id="importResults" style="display: none;">
                            <div class="text-center mb-4">
                                <i class="bx bx-check-circle text-success" style="font-size: 4rem;"></i>
                                <h4 class="text-dark mt-2">Import Complete!</h4>
                            </div>
                            <div class="row text-center mb-3">
                                <div class="col-6">
                                    <div class="stats-card bg-success bg-opacity-10 py-3">
                                        <h3 class="text-success mb-0" id="importedCount">0</h3>
                                        <small class="text-secondary">Leads Imported</small>
                                    </div>
                                </div>
                                <div class="col-6">
                                    <div class="stats-card bg-warning bg-opacity-10 py-3">
                                        <h3 class="text-warning mb-0" id="skippedCount">0</h3>
                                        <small class="text-secondary">Rows Skipped</small>
                                    </div>
                                </div>
                            </div>
                            <div id="importErrors" style="display: none;">
                                <h6 class="text-danger"><i class="bx bx-error-circle me-1"></i>Errors (showing first 10)</h6>
                                <ul class="list-unstyled small text-secondary" id="errorsList"></ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal" id="importCancelBtn">Cancel</button>
                    <button type="button" class="btn btn-outline-primary" id="importBackBtn" style="display: none;">
                        <i class="bx bx-arrow-back me-1"></i> Back
                    </button>
                    <button type="button" class="btn btn-primary" id="importNextBtn" disabled>
                        Next <i class="bx bx-arrow-right ms-1"></i>
                    </button>
                    <button type="button" class="btn btn-success" id="importStartBtn" style="display: none;">
                        <i class="bx bx-import me-1"></i> Start Import
                    </button>
                    <button type="button" class="btn btn-primary" id="importDoneBtn" style="display: none;">
                        <i class="bx bx-check me-1"></i> Done
                    </button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/select2/js/select2.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Status colors mapping
    const statusColors = {
        'new': { bg: 'info', text: 'white' },
        'contacted': { bg: 'primary', text: 'white' },
        'qualified': { bg: 'success', text: 'white' },
        'proposal': { bg: 'warning', text: 'dark' },
        'negotiation': { bg: 'secondary', text: 'white' },
        'won': { bg: 'success', text: 'white' },
        'lost': { bg: 'danger', text: 'white' },
        'dormant': { bg: 'dark', text: 'white' }
    };

    const statusLabels = {
        'new': 'New',
        'contacted': 'Contacted',
        'qualified': 'Qualified',
        'proposal': 'Proposal Sent',
        'negotiation': 'Negotiation',
        'won': 'Won',
        'lost': 'Lost',
        'dormant': 'Dormant'
    };

    const priorityColors = {
        'low': 'secondary',
        'medium': 'info',
        'high': 'warning',
        'urgent': 'danger'
    };

    // Avatar colors
    const avatarColors = ['#556ee6', '#34c38f', '#50a5f1', '#f1b44c', '#f46a6a', '#74788d', '#343a40', '#0ab39c'];

    // Available columns configuration
    const availableColumns = {
        'avatar': { label: 'Avatar', default: true, width: '50px' },
        'name': { label: 'Lead Name', default: true },
        'company': { label: 'Company', default: false },
        'email': { label: 'Email', default: false },
        'phone': { label: 'Phone', default: false },
        'contact': { label: 'Contact (Email & Phone)', default: true },
        'source': { label: 'Source', default: true },
        'status': { label: 'Status', default: true },
        'priority': { label: 'Priority', default: true },
        'referredBy': { label: 'Referred By', default: false },
        'createdAt': { label: 'Created Date', default: false },
        'lastContact': { label: 'Last Contact', default: false },
        'assignedTo': { label: 'Assigned To', default: false },
        'storeTargets': { label: 'Store Targets', default: false },
        'actions': { label: 'Actions', default: true, width: '120px' }
    };

    // Custom field names for dynamic columns
    const customFieldNames = @json($customFieldNames);
    customFieldNames.forEach(function(fieldName) {
        availableColumns['custom_' + fieldName] = {
            label: fieldName + ' (Custom)',
            default: false,
            isCustomField: true,
            fieldName: fieldName
        };
    });

    // Modals
    let statusModal = new bootstrap.Modal(document.getElementById('statusModal'));
    let activityModal = new bootstrap.Modal(document.getElementById('activityModal'));
    let deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));

    // State
    let currentPage = 1;
    let perPage = parseInt(localStorage.getItem('crm_leads_per_page') || '25');
    let currentSearch = '';
    let currentStatus = '';
    let currentSource = '';
    let currentPriority = '';
    let currentStore = '';
    let currentCustomField = '';
    let currentCustomFieldValue = '';
    let leadToDelete = null;
    let searchTimeout = null;
    let visibleColumns = [];

    // Load saved column settings
    function loadColumnSettings() {
        const saved = localStorage.getItem('crm_leads_columns');
        if (saved) {
            try {
                visibleColumns = JSON.parse(saved);
            } catch (e) {
                visibleColumns = getDefaultColumns();
            }
        } else {
            visibleColumns = getDefaultColumns();
        }
        renderColumnToggles();
        renderTableHeaders();
    }

    // Get default columns
    function getDefaultColumns() {
        return Object.keys(availableColumns).filter(key => availableColumns[key].default);
    }

    // Render column toggle checkboxes
    function renderColumnToggles() {
        const $list = $('#columnToggleList');
        $list.empty();

        for (const [key, config] of Object.entries(availableColumns)) {
            const isChecked = visibleColumns.includes(key);
            const $item = $(`
                <div class="column-toggle-item">
                    <div class="form-check mb-0">
                        <input class="form-check-input column-toggle" type="checkbox" value="${key}" id="col_${key}" ${isChecked ? 'checked' : ''}>
                        <label class="form-check-label text-dark" for="col_${key}">${escapeHtml(config.label)}</label>
                    </div>
                </div>
            `);
            $list.append($item);
        }
    }

    // Render table headers based on visible columns
    function renderTableHeaders() {
        const $headerRow = $('#tableHeaderRow');
        $headerRow.empty();

        visibleColumns.forEach(function(colKey) {
            const config = availableColumns[colKey];
            if (config) {
                const width = config.width ? ` style="width: ${config.width};"` : '';
                const align = colKey === 'actions' ? ' class="text-center"' : '';
                $headerRow.append(`<th${width}${align}>${escapeHtml(config.label)}</th>`);
            }
        });
    }

    // Save column settings
    $('#saveColumnsBtn').on('click', function() {
        visibleColumns = [];
        $('.column-toggle:checked').each(function() {
            visibleColumns.push($(this).val());
        });

        // Ensure actions column is always included
        if (!visibleColumns.includes('actions')) {
            visibleColumns.push('actions');
        }

        localStorage.setItem('crm_leads_columns', JSON.stringify(visibleColumns));
        renderTableHeaders();
        loadLeads();
        toastr.success('Column settings saved!', 'Success');
    });

    // Reset columns to default
    $('#resetColumnsBtn').on('click', function() {
        visibleColumns = getDefaultColumns();
        localStorage.removeItem('crm_leads_columns');
        renderColumnToggles();
        renderTableHeaders();
        loadLeads();
        toastr.info('Columns reset to default', 'Reset');
    });

    // Per page change
    $('#perPageSelect').val(perPage);
    $('#perPageSelect').on('change', function() {
        perPage = parseInt($(this).val());
        localStorage.setItem('crm_leads_per_page', perPage);
        currentPage = 1;
        loadLeads();
    });

    // Clear all filters
    $('#clearFiltersBtn').on('click', function() {
        $('#searchInput').val('');
        $('#sourceFilter').val('');
        $('#priorityFilter').val('');
        $('#storeFilter').val('');
        if ($('#customFieldFilter').length) {
            $('#customFieldFilter').val('');
            $('#customFieldValueFilter').val('').hide();
        }
        $('.status-filter-btn').removeClass('active');
        $('.status-filter-btn[data-status=""]').addClass('active');

        currentSearch = '';
        currentStatus = '';
        currentSource = '';
        currentPriority = '';
        currentStore = '';
        currentCustomField = '';
        currentCustomFieldValue = '';
        currentPage = 1;
        loadLeads();
    });

    // Initialize columns and load leads
    loadColumnSettings();
    loadLeads();

    // Status filter buttons
    $('.status-filter-btn').on('click', function() {
        $('.status-filter-btn').removeClass('active');
        $(this).addClass('active');
        currentStatus = $(this).data('status');
        currentPage = 1;
        loadLeads();
    });

    // Search input
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        const search = $(this).val();
        searchTimeout = setTimeout(function() {
            currentSearch = search;
            currentPage = 1;
            loadLeads();
        }, 300);
    });

    // Source filter
    $('#sourceFilter').on('change', function() {
        currentSource = $(this).val();
        currentPage = 1;
        loadLeads();
    });

    // Priority filter
    $('#priorityFilter').on('change', function() {
        currentPriority = $(this).val();
        currentPage = 1;
        loadLeads();
    });

    // Store filter
    $('#storeFilter').on('change', function() {
        currentStore = $(this).val();
        currentPage = 1;
        loadLeads();
    });

    // Custom field filter (only if element exists)
    if ($('#customFieldFilter').length) {
        $('#customFieldFilter').on('change', function() {
            currentCustomField = $(this).val();
            if (currentCustomField) {
                $('#customFieldValueFilter').show();
            } else {
                $('#customFieldValueFilter').hide().val('');
                currentCustomFieldValue = '';
            }
            currentPage = 1;
            loadLeads();
        });

        // Custom field value filter
        let customFieldTimeout = null;
        $('#customFieldValueFilter').on('input', function() {
            clearTimeout(customFieldTimeout);
            const value = $(this).val();
            customFieldTimeout = setTimeout(function() {
                currentCustomFieldValue = value;
                currentPage = 1;
                loadLeads();
            }, 300);
        });
    }

    // Load leads
    function loadLeads() {
        showLoading(true);

        $.ajax({
            url: '{{ route("crm-leads.data") }}',
            type: 'GET',
            data: {
                search: currentSearch,
                status: currentStatus,
                source: currentSource,
                priority: currentPriority,
                store: currentStore,
                custom_field: currentCustomField,
                custom_field_value: currentCustomFieldValue,
                page: currentPage,
                per_page: perPage
            },
            success: function(response) {
                if (response.success) {
                    renderLeads(response.data);
                    renderPagination(response.pagination);
                    updateStats(response.stats);
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load leads', 'Error!');
            },
            complete: function() {
                showLoading(false);
            }
        });
    }

    // Render leads table
    function renderLeads(leads) {
        const $tbody = $('#leadsTableBody');
        $tbody.empty();

        if (leads.length === 0) {
            $tbody.html(`
                <tr>
                    <td colspan="${visibleColumns.length}" class="text-center py-4">
                        <i class="mdi mdi-account-search text-secondary" style="font-size: 2.5rem;"></i>
                        <p class="text-dark mt-2 mb-0">No leads found.</p>
                        <small class="text-secondary">Add a new lead or adjust your filters.</small>
                    </td>
                </tr>
            `);
            return;
        }

        leads.forEach(function(lead, index) {
            const initials = getInitials(lead.firstName, lead.lastName);
            const avatarColor = avatarColors[index % avatarColors.length];
            const statusInfo = statusColors[lead.leadStatus] || { bg: 'secondary', text: 'white' };
            const statusLabel = statusLabels[lead.leadStatus] || lead.leadStatus;
            const priorityColor = priorityColors[lead.leadPriority] || 'secondary';

            let rowHtml = `<tr class="lead-row" data-lead-id="${lead.id}">`;

            visibleColumns.forEach(function(colKey) {
                rowHtml += renderColumnCell(colKey, lead, {
                    initials, avatarColor, statusInfo, statusLabel, priorityColor, index
                });
            });

            rowHtml += '</tr>';
            $tbody.append(rowHtml);
        });
    }

    // Render individual column cell
    function renderColumnCell(colKey, lead, helpers) {
        const config = availableColumns[colKey];
        if (!config) return '<td>-</td>';

        // Handle custom fields
        if (config.isCustomField) {
            const customValue = lead.customData && lead.customData.find(d => d.fieldName === config.fieldName);
            return `<td class="text-dark">${customValue ? escapeHtml(customValue.fieldValue) : '-'}</td>`;
        }

        switch (colKey) {
            case 'avatar':
                return `
                    <td>
                        <div class="avatar-circle" style="background-color: ${helpers.avatarColor};">
                            ${escapeHtml(helpers.initials)}
                        </div>
                    </td>
                `;

            case 'name':
                return `
                    <td>
                        <strong class="text-dark d-block">${escapeHtml(lead.fullName)}</strong>
                        ${lead.companyName ? `<small class="text-secondary">${escapeHtml(lead.companyName)}</small>` : ''}
                    </td>
                `;

            case 'company':
                return `<td class="text-dark">${lead.companyName ? escapeHtml(lead.companyName) : '-'}</td>`;

            case 'email':
                return `<td>${lead.email ? `<a href="mailto:${escapeHtml(lead.email)}" class="text-primary">${escapeHtml(lead.email)}</a>` : '<span class="text-secondary">-</span>'}</td>`;

            case 'phone':
                return `<td class="text-dark">${lead.phone ? escapeHtml(lead.phone) : '-'}</td>`;

            case 'contact':
                return `
                    <td>
                        ${lead.phone ? `<div class="text-dark"><i class="bx bx-phone text-muted me-1"></i>${escapeHtml(lead.phone)}</div>` : ''}
                        ${lead.email ? `<div class="text-secondary small"><i class="bx bx-envelope text-muted me-1"></i>${escapeHtml(lead.email)}</div>` : ''}
                        ${!lead.phone && !lead.email ? '<span class="text-secondary">-</span>' : ''}
                    </td>
                `;

            case 'source':
                let sourceBadge = '<span class="text-secondary">-</span>';
                if (lead.source) {
                    const sourceColor = lead.source.sourceColor || '#74788d';
                    // Fix: Ensure proper MDI icon class format (mdi mdi-iconname)
                    let iconClass = lead.source.sourceIcon || 'mdi-tag';
                    // If icon doesn't start with 'mdi ' (with space), prepend it
                    if (iconClass && !iconClass.startsWith('mdi ')) {
                        // If it starts with 'mdi-', add 'mdi ' prefix
                        if (iconClass.startsWith('mdi-')) {
                            iconClass = 'mdi ' + iconClass;
                        } else if (iconClass.startsWith('bx')) {
                            // Boxicons format - already correct
                        } else {
                            // Plain icon name, add full prefix
                            iconClass = 'mdi mdi-' + iconClass;
                        }
                    }
                    sourceBadge = `
                        <span class="source-badge" style="background-color: ${sourceColor}20; color: ${sourceColor};">
                            <i class="${iconClass}"></i>
                            ${escapeHtml(lead.source.sourceName)}
                        </span>
                    `;
                }
                return `<td>${sourceBadge}</td>`;

            case 'status':
                return `
                    <td>
                        <span class="badge bg-${helpers.statusInfo.bg} text-${helpers.statusInfo.text}">${helpers.statusLabel}</span>
                    </td>
                `;

            case 'priority':
                return `
                    <td>
                        <span class="badge bg-${helpers.priorityColor} priority-badge">${lead.leadPriority ? lead.leadPriority.charAt(0).toUpperCase() + lead.leadPriority.slice(1) : '-'}</span>
                    </td>
                `;

            case 'referredBy':
                return `<td class="text-dark">${lead.referredBy ? escapeHtml(lead.referredBy) : '-'}</td>`;

            case 'createdAt':
                return `<td class="text-dark small">${lead.created_at ? formatDate(lead.created_at) : '-'}</td>`;

            case 'lastContact':
                return `<td class="text-dark small">${lead.lastContactDate ? formatDate(lead.lastContactDate) : '-'}</td>`;

            case 'assignedTo':
                return `<td class="text-dark">${lead.assignee ? escapeHtml(lead.assignee.name) : '-'}</td>`;

            case 'storeTargets':
                let stores = '-';
                if (lead.target_stores && lead.target_stores.length > 0) {
                    stores = lead.target_stores.map(s => escapeHtml(s.storeName)).join(', ');
                }
                return `<td class="text-dark small">${stores}</td>`;

            case 'actions':
                return `
                    <td class="text-center">
                        <div class="btn-group btn-group-sm">
                            <a href="{{ url('crm-leads-view') }}?id=${lead.id}" class="btn btn-soft-secondary" title="View">
                                <i class="bx bx-show"></i>
                            </a>
                            <button type="button" class="btn btn-soft-info status-btn" data-lead-id="${lead.id}" data-lead-name="${escapeHtml(lead.fullName)}" data-current-status="${lead.leadStatus}" title="Update Status">
                                <i class="mdi mdi-swap-horizontal"></i>
                            </button>
                            <button type="button" class="btn btn-soft-success activity-btn" data-lead-id="${lead.id}" data-lead-name="${escapeHtml(lead.fullName)}" title="Log Activity">
                                <i class="mdi mdi-note-plus"></i>
                            </button>
                            <a href="{{ url('crm-leads-edit') }}?id=${lead.id}" class="btn btn-soft-primary" title="Edit">
                                <i class="bx bx-edit-alt"></i>
                            </a>
                            <button type="button" class="btn btn-soft-danger delete-btn" data-lead-id="${lead.id}" data-lead-name="${escapeHtml(lead.fullName)}" title="Delete">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </td>
                `;

            default:
                return '<td>-</td>';
        }
    }

    // Update stats
    function updateStats(stats) {
        $('#stat-total').text(stats.total || 0);
        $('#stat-new').text(stats.new || 0);
        $('#stat-contacted').text(stats.contacted || 0);
        $('#stat-qualified').text(stats.qualified || 0);
        $('#stat-proposal').text(stats.proposal || 0);
        $('#stat-won').text(stats.won || 0);
    }

    // Render pagination
    function renderPagination(pagination) {
        const $container = $('#paginationContainer');
        const $info = $('#paginationInfo');
        $container.empty();

        if (pagination.total === 0) {
            $info.html('No leads found');
            return;
        }

        $info.html(`Showing <strong class="text-dark">${pagination.from}</strong> to <strong class="text-dark">${pagination.to}</strong> of <strong class="text-dark">${pagination.total}</strong> leads`);

        if (pagination.last_page <= 1) return;

        // Previous
        $container.append(`
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}"><i class="bx bx-chevron-left"></i></a>
            </li>
        `);

        // Pages
        const start = Math.max(1, pagination.current_page - 2);
        const end = Math.min(pagination.last_page, pagination.current_page + 2);

        if (start > 1) {
            $container.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
            if (start > 2) $container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }

        for (let i = start; i <= end; i++) {
            $container.append(`
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        if (end < pagination.last_page) {
            if (end < pagination.last_page - 1) $container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
            $container.append(`<li class="page-item"><a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a></li>`);
        }

        // Next
        $container.append(`
            <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}"><i class="bx bx-chevron-right"></i></a>
            </li>
        `);
    }

    // Pagination click
    $(document).on('click', '#paginationContainer .page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
            currentPage = page;
            loadLeads();
        }
    });

    // Row click - go to view
    $(document).on('click', '.lead-row', function(e) {
        if ($(e.target).closest('button, a').length) return;
        const leadId = $(this).data('lead-id');
        window.location.href = '{{ url("crm-leads-view") }}?id=' + leadId;
    });

    // Status button
    $(document).on('click', '.status-btn', function(e) {
        e.stopPropagation();
        const leadId = $(this).data('lead-id');
        const leadName = $(this).data('lead-name');
        const currentStatus = $(this).data('current-status');

        $('#statusLeadId').val(leadId);
        $('#statusLeadName').text(leadName);
        $('#newStatus').val(currentStatus);
        $('#lossReason').val('');
        $('#lossReasonGroup').hide();
        statusModal.show();
    });

    // Show/hide loss reason
    $('#newStatus').on('change', function() {
        if ($(this).val() === 'lost') {
            $('#lossReasonGroup').show();
        } else {
            $('#lossReasonGroup').hide();
        }
    });

    // Save status
    $('#saveStatusBtn').on('click', function() {
        const leadId = $('#statusLeadId').val();
        const newStatus = $('#newStatus').val();
        const lossReason = $('#lossReason').val();

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Updating...');

        $.ajax({
            url: '{{ route("crm-leads.update-status") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                id: leadId,
                status: newStatus,
                loss_reason: lossReason
            },
            success: function(response) {
                if (response.success) {
                    statusModal.hide();
                    toastr.success(response.message, 'Success!');
                    loadLeads();
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to update status', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Update');
            }
        });
    });

    // Activity button
    $(document).on('click', '.activity-btn', function(e) {
        e.stopPropagation();
        const leadId = $(this).data('lead-id');
        const leadName = $(this).data('lead-name');

        $('#activityLeadId').val(leadId);
        $('#activityLeadName').text(leadName);
        $('#activityType').val('note');
        $('#activityDescription').val('');
        activityModal.show();
    });

    // Save activity
    $('#saveActivityBtn').on('click', function() {
        const leadId = $('#activityLeadId').val();
        const activityType = $('#activityType').val();
        const description = $('#activityDescription').val().trim();

        if (!description) {
            toastr.error('Please enter a description', 'Validation Error');
            return;
        }

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        $.ajax({
            url: '{{ route("crm-leads.add-activity") }}',
            type: 'POST',
            data: {
                _token: '{{ csrf_token() }}',
                lead_id: leadId,
                activity_type: activityType,
                description: description
            },
            success: function(response) {
                if (response.success) {
                    activityModal.hide();
                    toastr.success(response.message, 'Success!');
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to save activity', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save');
            }
        });
    });

    // Delete button
    $(document).on('click', '.delete-btn', function(e) {
        e.stopPropagation();
        leadToDelete = {
            id: $(this).data('lead-id'),
            name: $(this).data('lead-name')
        };
        $('#deleteLeadName').text(leadToDelete.name);
        deleteModal.show();
    });

    // Confirm delete
    $('#confirmDeleteBtn').on('click', function() {
        if (!leadToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: '{{ route("crm-leads.destroy") }}',
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}',
                id: leadToDelete.id
            },
            success: function(response) {
                if (response.success) {
                    deleteModal.hide();
                    toastr.success(response.message, 'Success!');
                    loadLeads();
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete lead', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
                leadToDelete = null;
            }
        });
    });

    // Helpers
    function showLoading(show) {
        $('#loadingOverlay').toggle(show);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getInitials(firstName, lastName) {
        const first = firstName ? firstName.charAt(0).toUpperCase() : '';
        const last = lastName ? lastName.charAt(0).toUpperCase() : '';
        return first + last || '?';
    }

    function formatDate(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric' });
    }

    function formatDateTime(dateStr) {
        if (!dateStr) return '';
        const date = new Date(dateStr);
        if (isNaN(date.getTime())) return dateStr;
        return date.toLocaleDateString('en-US', { month: 'short', day: 'numeric', year: 'numeric', hour: '2-digit', minute: '2-digit' });
    }

    // Toastr options
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // ============================
    // IMPORT FUNCTIONALITY
    // ============================
    let importModal = new bootstrap.Modal(document.getElementById('importModal'));
    let importStep = 1;
    let importFile = null;
    let importHeaders = [];
    let importPreviewData = [];
    let importLeadFields = {};

    // Available lead fields for dropdown (loaded from server)
    const leadFieldsOptions = @json(\App\Models\CrmLead::IMPORTABLE_FIELDS);

    // Open import modal
    $('#importBtn').on('click', function() {
        resetImport();
        importModal.show();
    });

    // Add custom field for import
    let importCustomFieldCount = 0;
    $('#addImportCustomField').on('click', function() {
        importCustomFieldCount++;
        $('#noImportCustomFields').hide();
        const fieldHtml = `
            <div class="import-custom-field d-flex gap-2 mb-2" data-index="${importCustomFieldCount}">
                <input type="text" class="form-control form-control-sm custom-field-name" placeholder="Field Name" style="flex: 1;">
                <input type="text" class="form-control form-control-sm custom-field-value" placeholder="Value" style="flex: 1;">
                <button type="button" class="btn btn-soft-danger btn-sm remove-import-custom-field"><i class="bx bx-x"></i></button>
            </div>
        `;
        $('#importCustomFieldsList').append(fieldHtml);
    });

    // Remove custom field
    $(document).on('click', '.remove-import-custom-field', function() {
        $(this).closest('.import-custom-field').remove();
        if ($('#importCustomFieldsList .import-custom-field').length === 0) {
            $('#noImportCustomFields').show();
        }
    });

    // Browse file button
    $('#browseFileBtn').on('click', function(e) {
        e.stopPropagation(); // Prevent bubbling to uploadZone
        $('#importFile').click();
    });

    // File input change
    $('#importFile').on('change', function(e) {
        const file = e.target.files[0];
        if (file) {
            handleFileSelected(file);
        }
    });

    // Drag and drop
    const uploadZone = document.getElementById('uploadZone');
    uploadZone.addEventListener('dragover', function(e) {
        e.preventDefault();
        $(this).addClass('dragover');
    });
    uploadZone.addEventListener('dragleave', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
    });
    uploadZone.addEventListener('drop', function(e) {
        e.preventDefault();
        $(this).removeClass('dragover');
        const file = e.dataTransfer.files[0];
        if (file) {
            handleFileSelected(file);
        }
    });
    uploadZone.addEventListener('click', function(e) {
        // Only trigger if clicking on the zone itself, not on buttons inside
        if (e.target.tagName !== 'BUTTON' && !$(e.target).closest('button').length) {
            $('#importFile').click();
        }
    });

    // Handle file selected
    function handleFileSelected(file) {
        const validExtensions = ['csv', 'txt', 'xlsx', 'xls'];
        const extension = file.name.split('.').pop().toLowerCase();

        if (!validExtensions.includes(extension)) {
            toastr.error('Please upload a CSV or Excel file', 'Invalid File');
            return;
        }

        if (file.size > 10 * 1024 * 1024) {
            toastr.error('File size must be less than 10MB', 'File Too Large');
            return;
        }

        importFile = file;
        $('#selectedFileName').text(file.name);
        $('#selectedFileSize').text(formatFileSize(file.size));
        $('#selectedFileInfo').show();
        $('#uploadZone').hide();
        $('#importNextBtn').prop('disabled', false);
    }

    // Remove file
    $('#removeFileBtn').on('click', function(e) {
        e.stopPropagation();
        importFile = null;
        $('#importFile').val('');
        $('#selectedFileInfo').hide();
        $('#uploadZone').show();
        $('#importNextBtn').prop('disabled', true);
    });

    // Next button
    $('#importNextBtn').on('click', function() {
        if (importStep === 1) {
            parseFile();
        }
    });

    // Back button
    $('#importBackBtn').on('click', function() {
        if (importStep === 2) {
            goToStep(1);
        }
    });

    // Start import button
    $('#importStartBtn').on('click', function() {
        startImport();
    });

    // Done button
    $('#importDoneBtn').on('click', function() {
        importModal.hide();
        loadLeads();
    });

    // Parse file
    function parseFile() {
        if (!importFile) return;

        const formData = new FormData();
        formData.append('file', importFile);
        formData.append('_token', '{{ csrf_token() }}');

        $('#importNextBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Processing...');

        $.ajax({
            url: '{{ route("crm-leads.parse-import") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    importHeaders = response.data.headers;
                    importPreviewData = response.data.preview;
                    importLeadFields = response.data.leadFields;
                    renderColumnMappings();
                    renderPreview();
                    goToStep(2);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to parse file', 'Error');
            },
            complete: function() {
                $('#importNextBtn').prop('disabled', false).html('Next <i class="bx bx-arrow-right ms-1"></i>');
            }
        });
    }

    // Render column mappings
    function renderColumnMappings() {
        const $tbody = $('#columnMappingsBody');
        $tbody.empty();

        // Build field options HTML
        let fieldOptionsHtml = '<option value="" class="text-secondary">-- Do Not Import (Skip) --</option>';
        fieldOptionsHtml += '<optgroup label="Standard Fields">';
        for (const [field, info] of Object.entries(leadFieldsOptions)) {
            const required = info.required ? ' class="required-field"' : '';
            fieldOptionsHtml += `<option value="${field}"${required}>${info.label}${info.required ? ' *' : ''}</option>`;
        }
        fieldOptionsHtml += '</optgroup>';
        fieldOptionsHtml += '<option value="custom">-- Custom Data --</option>';

        importHeaders.forEach(function(header, index) {
            // Try to auto-match
            let autoMatch = '';
            const headerLower = header.toLowerCase().replace(/[^a-z0-9]/g, '');

            for (const [field, info] of Object.entries(leadFieldsOptions)) {
                const labelLower = info.label.toLowerCase().replace(/[^a-z0-9]/g, '');
                const fieldLower = field.toLowerCase();
                if (headerLower === labelLower || headerLower === fieldLower ||
                    headerLower.includes(fieldLower) || fieldLower.includes(headerLower)) {
                    autoMatch = field;
                    break;
                }
            }

            const row = `
                <tr>
                    <td><strong class="text-dark">${escapeHtml(header)}</strong></td>
                    <td>
                        <select class="form-select form-select-sm mapping-select" data-column="${index}">
                            ${fieldOptionsHtml}
                        </select>
                    </td>
                    <td>
                        <input type="text" class="form-control form-control-sm custom-field-input"
                               data-column="${index}" placeholder="Field name" style="display: none;">
                    </td>
                </tr>
            `;
            $tbody.append(row);

            // Set auto-match
            if (autoMatch) {
                $tbody.find(`select[data-column="${index}"]`).val(autoMatch);
            }
        });

        // Handle custom field selection
        $tbody.on('change', '.mapping-select', function() {
            const $row = $(this).closest('tr');
            const $customInput = $row.find('.custom-field-input');
            if ($(this).val() === 'custom') {
                $customInput.show().prop('required', true);
            } else {
                $customInput.hide().prop('required', false).val('');
            }
        });
    }

    // Render preview
    function renderPreview() {
        const $headers = $('#previewHeaders');
        const $body = $('#previewBody');
        $headers.empty();
        $body.empty();

        // Headers
        importHeaders.forEach(function(header) {
            $headers.append(`<th class="text-dark small">${escapeHtml(header)}</th>`);
        });

        // Rows
        importPreviewData.forEach(function(row) {
            let rowHtml = '<tr>';
            row.forEach(function(cell) {
                rowHtml += `<td class="small">${escapeHtml(cell || '')}</td>`;
            });
            rowHtml += '</tr>';
            $body.append(rowHtml);
        });
    }

    // Start import
    function startImport() {
        // Collect mappings
        const mappings = [];
        let hasRequired = { firstName: false, lastName: false, fullName: false };

        $('#columnMappingsBody .mapping-select').each(function() {
            const colIndex = $(this).data('column');
            let field = $(this).val();

            if (!field) return;

            if (field === 'custom') {
                const customName = $(this).closest('tr').find('.custom-field-input').val().trim();
                if (!customName) {
                    toastr.error('Please enter a name for custom fields', 'Validation Error');
                    return false;
                }
                field = 'custom:' + customName;
            }

            if (field === 'firstName') hasRequired.firstName = true;
            if (field === 'lastName') hasRequired.lastName = true;
            if (field === 'fullName') hasRequired.fullName = true;

            mappings.push({ column: colIndex, field: field });
        });

        // Validate: need either (firstName AND lastName) OR fullName
        const hasNames = (hasRequired.firstName && hasRequired.lastName) || hasRequired.fullName;
        if (!hasNames) {
            toastr.error('Please map either "Full Name" OR both "First Name" and "Last Name".', 'Validation Error');
            return;
        }

        goToStep(3);

        // Collect store targets
        const storeTargets = [];
        $('.import-store-target:checked').each(function() {
            storeTargets.push($(this).val());
        });

        // Collect custom fields
        const customFields = [];
        $('#importCustomFieldsList .import-custom-field').each(function() {
            const name = $(this).find('.custom-field-name').val().trim();
            const value = $(this).find('.custom-field-value').val().trim();
            if (name) {
                customFields.push({ name: name, value: value });
            }
        });

        const formData = new FormData();
        formData.append('file', importFile);
        formData.append('_token', '{{ csrf_token() }}');
        formData.append('mappings', JSON.stringify(mappings));
        formData.append('defaultStatus', $('#importDefaultStatus').val());
        formData.append('defaultPriority', $('#importDefaultPriority').val());
        formData.append('defaultSourceId', $('#importDefaultSource').val());
        formData.append('referredBy', $('#importReferredBy').val());
        formData.append('storeTargets', JSON.stringify(storeTargets));
        formData.append('customFields', JSON.stringify(customFields));

        $.ajax({
            url: '{{ route("crm-leads.process-import") }}',
            type: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                $('#importingStatus').hide();
                $('#importResults').show();
                $('#importedCount').text(response.data.imported);
                $('#skippedCount').text(response.data.skipped);

                if (response.data.errors && response.data.errors.length > 0) {
                    $('#importErrors').show();
                    const $errorsList = $('#errorsList');
                    $errorsList.empty();
                    response.data.errors.forEach(function(error) {
                        $errorsList.append(`<li><i class="bx bx-x text-danger me-1"></i>${escapeHtml(error)}</li>`);
                    });
                }

                toastr.success(response.message, 'Success!');
            },
            error: function(xhr) {
                $('#importingStatus').hide();
                $('#importResults').show();
                $('#importedCount').text('0');
                $('#skippedCount').text('Error');
                toastr.error(xhr.responseJSON?.message || 'Import failed', 'Error');
            }
        });
    }

    // Go to step
    function goToStep(step) {
        importStep = step;

        // Update step indicators
        $('.import-step').removeClass('active completed');
        for (let i = 1; i < step; i++) {
            $(`.import-step[data-step="${i}"]`).addClass('completed');
        }
        $(`.import-step[data-step="${step}"]`).addClass('active');

        // Show/hide content
        $('.import-step-content').hide();
        $(`#importStep${step}`).show();

        // Update buttons
        $('#importBackBtn').toggle(step === 2);
        $('#importNextBtn').toggle(step === 1);
        $('#importStartBtn').toggle(step === 2);
        $('#importDoneBtn').toggle(step === 3);
        $('#importCancelBtn').toggle(step !== 3);
    }

    // Reset import
    function resetImport() {
        importStep = 1;
        importFile = null;
        importHeaders = [];
        importPreviewData = [];

        $('#importFile').val('');
        $('#selectedFileInfo').hide();
        $('#uploadZone').show();
        $('#importNextBtn').prop('disabled', true).html('Next <i class="bx bx-arrow-right ms-1"></i>');
        $('#importingStatus').show();
        $('#importResults').hide();
        $('#importErrors').hide();
        $('#errorsList').empty();
        $('#columnMappingsBody').empty();
        $('#previewHeaders').empty();
        $('#previewBody').empty();

        // Reset default settings
        $('#importDefaultStatus').val('new');
        $('#importDefaultPriority').val('medium');
        $('#importDefaultSource').val('');
        $('#importReferredBy').val('');
        $('.import-store-target').prop('checked', false);
        $('#importCustomFieldsList').empty();
        $('#noImportCustomFields').show();
        importCustomFieldCount = 0;

        goToStep(1);
    }

    // Format file size
    function formatFileSize(bytes) {
        if (bytes === 0) return '0 Bytes';
        const k = 1024;
        const sizes = ['Bytes', 'KB', 'MB', 'GB'];
        const i = Math.floor(Math.log(bytes) / Math.log(k));
        return parseFloat((bytes / Math.pow(k, i)).toFixed(2)) + ' ' + sizes[i];
    }
});
</script>
@endsection
