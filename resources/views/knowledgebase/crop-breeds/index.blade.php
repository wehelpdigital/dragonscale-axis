@extends('layouts.master')

@section('title') Crop Breeds @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .filter-card {
        background: #f8f9fa;
        border-radius: 8px;
        padding: 15px;
        margin-bottom: 20px;
    }
    .breed-badge {
        font-size: 0.75rem;
        padding: 4px 8px;
    }
    .table-action-btn {
        padding: 4px 8px;
        font-size: 0.8rem;
    }
    .gene-tag {
        display: inline-block;
        background: #e9ecef;
        color: #495057;
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 0.75rem;
        margin: 2px;
    }
    .empty-state {
        text-align: center;
        padding: 60px 20px;
    }
    .empty-state i {
        font-size: 4rem;
        color: #adb5bd;
        margin-bottom: 1rem;
    }
    .breed-image-thumb {
        width: 40px;
        height: 40px;
        object-fit: cover;
        border-radius: 4px;
    }
    .breed-name-cell {
        display: flex;
        align-items: flex-start;
        gap: 10px;
    }
    .breed-name-cell .breed-info {
        flex: 1;
    }
    /* View Modal Styles */
    .view-modal-image {
        max-width: 100%;
        max-height: 200px;
        border-radius: 8px;
        object-fit: contain;
    }
    .view-modal-placeholder {
        width: 100%;
        height: 150px;
        background: #f8f9fa;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        color: #adb5bd;
    }
    .view-modal-placeholder i {
        font-size: 3rem;
    }
    .detail-row {
        padding: 8px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    .detail-row:last-child {
        border-bottom: none;
    }
    .detail-label {
        font-weight: 600;
        color: #495057;
        font-size: 0.85rem;
    }
    .detail-value {
        color: #212529;
    }
    .gene-tag-modal {
        display: inline-block;
        background: #e3f2fd;
        color: #1565c0;
        padding: 4px 10px;
        border-radius: 15px;
        font-size: 0.8rem;
        margin: 3px;
    }
    /* Pagination styles */
    .pagination-container {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-top: 20px;
        padding-top: 15px;
        border-top: 1px solid #dee2e6;
    }
    .pagination-info {
        color: #6c757d;
        font-size: 0.9rem;
    }
    .pagination-controls {
        display: flex;
        align-items: center;
        gap: 15px;
    }
    .per-page-select {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .per-page-select select {
        width: auto;
        padding: 5px 25px 5px 10px;
    }
    .pagination {
        margin-bottom: 0;
    }

    /* =====================================================
       MOBILE RESPONSIVE STYLES WITH ANIMATIONS
       ===================================================== */

    /* Smooth transitions */
    .btn, .form-control, .form-select, .table tbody tr, .breed-card {
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

    .card {
        animation: slideInUp 0.4s ease forwards;
    }

    /* Table row hover effect */
    .table tbody tr {
        transition: background-color 0.2s ease, transform 0.2s ease;
    }

    .table tbody tr:hover {
        background-color: #f8f9fa;
    }

    /* Small monitors (1280px - 1400px) */
    @media (max-width: 1400px) {
        .filter-card {
            padding: 14px;
        }

        .table th, .table td {
            padding: 11px 10px;
            font-size: 13px;
        }

        .breed-badge {
            font-size: 0.7rem;
            padding: 3px 7px;
        }

        .gene-tag {
            font-size: 0.7rem;
            padding: 2px 6px;
        }

        .table-action-btn {
            padding: 4px 7px;
            font-size: 0.75rem;
        }

        .pagination-info {
            font-size: 0.85rem;
        }
    }

    /* iPad landscape / 1024px monitors */
    @media (max-width: 1024px) {
        .filter-card {
            padding: 12px;
        }

        .filter-card .form-label {
            font-size: 12px;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            font-size: 13px;
            padding: 7px 10px;
        }

        .table th {
            font-size: 11.5px;
            padding: 10px 8px;
        }

        .table td {
            font-size: 12.5px;
            padding: 10px 8px;
        }

        .breed-image-thumb {
            width: 36px;
            height: 36px;
        }

        .breed-badge {
            font-size: 0.65rem;
            padding: 3px 6px;
        }

        .gene-tag {
            font-size: 0.65rem;
            padding: 2px 5px;
        }

        .table-action-btn {
            padding: 3px 6px;
            font-size: 0.7rem;
        }

        .btn-sm {
            padding: 5px 10px;
            font-size: 12px;
        }

        /* Pagination */
        .pagination-container {
            gap: 10px;
        }

        .pagination-info {
            font-size: 12px;
        }

        .per-page-select select {
            padding: 4px 20px 4px 8px;
            font-size: 12px;
        }

        .pagination .page-link {
            padding: 6px 10px;
            font-size: 12px;
        }

        /* Hide some columns */
        .table th:nth-child(5),
        .table td:nth-child(5) {
            display: none;
        }
    }

    /* Tablet Styles */
    @media (max-width: 991px) {
        .filter-card {
            padding: 12px;
        }

        .filter-card .row > div {
            margin-bottom: 10px;
        }

        .table th, .table td {
            padding: 10px 8px;
            font-size: 13px;
        }

        .breed-image-thumb {
            width: 35px;
            height: 35px;
        }

        /* 2-column filter layout */
        .filter-card .row > [class*="col-md-"] {
            flex: 0 0 50%;
            max-width: 50%;
        }

        .filter-card .row > [class*="col-md-"]:last-child {
            flex: 0 0 100%;
            max-width: 100%;
        }
    }

    /* Mobile Styles */
    @media (max-width: 767px) {
        /* Filter card - stack vertically */
        .filter-card {
            padding: 15px;
        }

        .filter-card .row {
            margin: 0 -5px;
        }

        .filter-card .row > div {
            padding: 0 5px;
            margin-bottom: 12px;
        }

        .filter-card .form-control,
        .filter-card .form-select {
            font-size: 14px;
            padding: 10px 12px;
        }

        .filter-card .btn {
            width: 100%;
            padding: 10px;
        }

        /* Table - make responsive */
        .table-responsive {
            border: none;
        }

        .table {
            font-size: 13px;
        }

        .table thead th {
            font-size: 12px;
            padding: 10px 8px;
            white-space: nowrap;
        }

        .table tbody td {
            padding: 12px 8px;
            vertical-align: middle;
        }

        /* Hide less important columns on mobile */
        .table th:nth-child(4),
        .table td:nth-child(4),
        .table th:nth-child(5),
        .table td:nth-child(5) {
            display: none;
        }

        .breed-name-cell {
            flex-direction: column;
            align-items: flex-start;
            gap: 8px;
        }

        .breed-image-thumb {
            width: 50px;
            height: 50px;
        }

        /* Gene tags */
        .gene-tag {
            font-size: 10px;
            padding: 2px 6px;
            margin: 1px;
        }

        /* Action buttons */
        .table-action-btn {
            padding: 6px 8px;
            font-size: 12px;
        }

        /* Pagination */
        .pagination-container {
            flex-direction: column;
            align-items: stretch;
            gap: 12px;
        }

        .pagination-info {
            text-align: center;
            font-size: 13px;
        }

        .pagination-controls {
            flex-direction: column;
            gap: 12px;
        }

        .per-page-select {
            justify-content: center;
        }

        .pagination {
            justify-content: center;
        }

        .pagination .page-link {
            padding: 8px 12px;
            font-size: 13px;
        }

        /* Empty state */
        .empty-state {
            padding: 40px 20px;
        }

        .empty-state i {
            font-size: 3rem;
        }

        /* Modal adjustments */
        .modal-dialog {
            margin: 10px;
            max-width: calc(100% - 20px);
        }

        .view-modal-image {
            max-height: 150px;
        }

        .detail-row {
            padding: 10px 0;
        }

        .detail-label {
            font-size: 12px;
            margin-bottom: 4px;
        }

        .detail-value {
            font-size: 14px;
        }

        /* Card header buttons */
        .card-header .btn {
            padding: 8px 12px;
            font-size: 13px;
        }

        /* Breadcrumb */
        .page-title-box h4 {
            font-size: 16px;
        }
    }

    /* Small Mobile */
    @media (max-width: 575px) {
        .card-body {
            padding: 15px;
        }

        /* Stack card header */
        .card-header {
            flex-direction: column;
            gap: 10px;
        }

        .card-header .btn {
            width: 100%;
        }

        /* Table as cards on very small screens */
        .table thead {
            display: none;
        }

        .table tbody tr {
            display: block;
            margin-bottom: 15px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
            background: #fff;
            box-shadow: 0 2px 4px rgba(0,0,0,0.05);
        }

        .table tbody td {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
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
            font-size: 12px;
            margin-right: 10px;
        }

        /* Show hidden columns in card view */
        .table th:nth-child(4),
        .table td:nth-child(4),
        .table th:nth-child(5),
        .table td:nth-child(5) {
            display: flex;
        }

        .breed-name-cell {
            flex-direction: row;
            align-items: center;
        }

        /* Filter buttons side by side */
        .filter-card .col-auto {
            width: 50%;
        }

        .filter-card .col-auto .btn {
            width: 100%;
        }

        /* Pagination even more compact */
        .pagination .page-link {
            padding: 6px 10px;
            font-size: 12px;
        }

        /* Touch-friendly buttons */
        .btn {
            min-height: 44px;
        }

        .btn-sm {
            min-height: 36px;
        }

        .table-action-btn {
            min-height: 36px;
            min-width: 36px;
        }
    }

    /* Touch device improvements */
    @media (hover: none) and (pointer: coarse) {
        .table tbody tr:active {
            background-color: #e9ecef;
        }

        .btn:active {
            transform: scale(0.98);
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
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Knowledgebase @endslot
        @slot('title') Crop Breeds @endslot
    @endcomponent

    <!-- Flash Messages -->
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="card">
        <div class="card-header">
            <div class="d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-0">
                        <i class="bx bx-leaf text-success me-2"></i>Crop Breeds & Varieties
                    </h4>
                    <p class="text-secondary mb-0 mt-1">Manage corn and rice varieties for recommendations</p>
                </div>
                <a href="{{ route('knowledgebase.crop-breeds.create') }}" class="btn btn-primary">
                    <i class="bx bx-plus me-1"></i>Add New Breed
                </a>
            </div>
        </div>
        <div class="card-body">
            <!-- Filters -->
            <div class="filter-card">
                <form method="GET" action="{{ route('knowledgebase.crop-breeds') }}" id="filterForm">
                    <div class="row g-3 align-items-end">
                        <div class="col-md-3">
                            <label class="form-label text-dark">Search</label>
                            <input type="text" class="form-control" name="search" placeholder="Name or Manufacturer" value="{{ request('search') }}">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-dark">Crop Type</label>
                            <select class="form-select" name="crop_type" id="filterCropType">
                                <option value="">All Crops</option>
                                @foreach($cropTypeLabels as $value => $label)
                                    <option value="{{ $value }}" {{ request('crop_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-dark">Breed Type</label>
                            <select class="form-select" name="breed_type">
                                <option value="">All Types</option>
                                @foreach($breedTypeLabels as $value => $label)
                                    <option value="{{ $value }}" {{ request('breed_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2" id="cornTypeFilter" style="{{ request('crop_type') == 'corn' ? '' : 'display:none;' }}">
                            <label class="form-label text-dark">Corn Type</label>
                            <select class="form-select" name="corn_type">
                                <option value="">All</option>
                                @foreach($cornTypeLabels as $value => $label)
                                    <option value="{{ $value }}" {{ request('corn_type') == $value ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label text-dark">Per Page</label>
                            <select class="form-select" name="per_page" id="perPageSelect">
                                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15</option>
                                <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                        <div class="col-md-auto">
                            <label class="form-label d-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary me-2">
                                <i class="bx bx-search me-1"></i>Filter
                            </button>
                            <a href="{{ route('knowledgebase.crop-breeds') }}" class="btn btn-outline-secondary">
                                <i class="bx bx-reset me-1"></i>Reset
                            </a>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Breeds Table -->
            @if($breeds->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Crop</th>
                                <th>Breed Type</th>
                                <th>Corn Type</th>
                                <th>Manufacturer</th>
                                <th>Yield</th>
                                <th>Maturity</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($breeds as $breed)
                                <tr id="breed-row-{{ $breed->id }}">
                                    <td>
                                        <div class="breed-name-cell">
                                            @if($breed->imagePath)
                                                <img src="{{ asset($breed->imagePath) }}" alt="{{ $breed->name }}" class="breed-image-thumb">
                                            @else
                                                <div class="breed-image-thumb bg-light d-flex align-items-center justify-content-center">
                                                    <i class="bx bx-leaf text-muted"></i>
                                                </div>
                                            @endif
                                            <div class="breed-info">
                                                <strong class="text-dark">{{ $breed->name }}</strong>
                                                @if($breed->geneProtection && count($breed->geneProtection) > 0)
                                                    <br>
                                                    @foreach(array_slice($breed->geneProtection, 0, 3) as $gene)
                                                        <span class="gene-tag">{{ $gene }}</span>
                                                    @endforeach
                                                    @if(count($breed->geneProtection) > 3)
                                                        <span class="gene-tag">+{{ count($breed->geneProtection) - 3 }} more</span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </td>
                                    <td>
                                        @if($breed->cropType == 'corn')
                                            <span class="badge bg-warning text-dark breed-badge">
                                                <i class="bx bx-leaf me-1"></i>Corn
                                            </span>
                                        @else
                                            <span class="badge bg-success breed-badge">
                                                <i class="bx bx-leaf me-1"></i>Rice
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        <span class="text-dark">{{ $breedTypeLabels[$breed->breedType] ?? '-' }}</span>
                                    </td>
                                    <td>
                                        @if($breed->cropType == 'corn' && $breed->cornType)
                                            @if($breed->cornType == 'yellow')
                                                <span class="badge bg-warning text-dark breed-badge">Yellow</span>
                                            @elseif($breed->cornType == 'white')
                                                <span class="badge bg-light text-dark breed-badge">White</span>
                                            @else
                                                <span class="badge bg-info text-white breed-badge">Special</span>
                                            @endif
                                        @else
                                            <span class="text-muted">-</span>
                                        @endif
                                    </td>
                                    <td class="text-dark">{{ $breed->manufacturer ?? '-' }}</td>
                                    <td class="text-dark">{{ $breed->potentialYield ?? '-' }}</td>
                                    <td class="text-dark">{{ $breed->maturityDays ?? '-' }}</td>
                                    <td>
                                        <div class="form-check form-switch">
                                            <input class="form-check-input status-toggle" type="checkbox"
                                                   data-id="{{ $breed->id }}" {{ $breed->isActive ? 'checked' : '' }}>
                                        </div>
                                    </td>
                                    <td class="text-center">
                                        <button type="button" class="btn btn-sm btn-outline-info table-action-btn view-btn" title="View"
                                                data-id="{{ $breed->id }}"
                                                data-name="{{ $breed->name }}"
                                                data-crop-type="{{ $breed->cropType }}"
                                                data-breed-type="{{ $breedTypeLabels[$breed->breedType] ?? '-' }}"
                                                data-corn-type="{{ $breed->cornType ? ($cornTypeLabels[$breed->cornType] ?? '-') : '-' }}"
                                                data-manufacturer="{{ $breed->manufacturer ?? '-' }}"
                                                data-yield="{{ $breed->potentialYield ?? '-' }}"
                                                data-maturity="{{ $breed->maturityDays ?? '-' }}"
                                                data-gene-protection="{{ $breed->geneProtection ? implode(',', $breed->geneProtection) : '' }}"
                                                data-characteristics="{{ $breed->characteristics ?? '' }}"
                                                data-related-info="{{ $breed->relatedInformation ?? '' }}"
                                                data-image="{{ $breed->imagePath ? asset($breed->imagePath) : '' }}"
                                                data-brochure="{{ $breed->brochurePath ? asset($breed->brochurePath) : '' }}"
                                                data-source-url="{{ $breed->sourceUrl ?? '' }}"
                                                data-is-active="{{ $breed->isActive ? '1' : '0' }}">
                                            <i class="bx bx-show"></i>
                                        </button>
                                        <a href="{{ route('knowledgebase.crop-breeds.edit', ['id' => $breed->id]) }}"
                                           class="btn btn-sm btn-outline-primary table-action-btn" title="Edit">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-outline-danger table-action-btn delete-btn"
                                                data-id="{{ $breed->id }}" data-name="{{ $breed->name }}" title="Delete">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="pagination-container">
                    <div class="pagination-info">
                        Showing <strong>{{ $breeds->firstItem() ?? 0 }}</strong> to <strong>{{ $breeds->lastItem() ?? 0 }}</strong> of <strong>{{ $breeds->total() }}</strong> breed(s)
                    </div>
                    <div class="pagination-controls">
                        <div class="per-page-select">
                            <label class="text-secondary mb-0">Show:</label>
                            <select class="form-select form-select-sm" id="paginationPerPage">
                                <option value="10" {{ $perPage == 10 ? 'selected' : '' }}>10</option>
                                <option value="15" {{ $perPage == 15 ? 'selected' : '' }}>15</option>
                                <option value="25" {{ $perPage == 25 ? 'selected' : '' }}>25</option>
                                <option value="50" {{ $perPage == 50 ? 'selected' : '' }}>50</option>
                                <option value="100" {{ $perPage == 100 ? 'selected' : '' }}>100</option>
                            </select>
                        </div>
                        @if($breeds->hasPages())
                            {{ $breeds->links('pagination::bootstrap-5') }}
                        @endif
                    </div>
                </div>
            @else
                <div class="empty-state">
                    <i class="bx bx-leaf"></i>
                    <h5 class="text-dark">No Crop Breeds Found</h5>
                    <p class="text-secondary">Add your first crop breed using the button above.</p>
                </div>
            @endif
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-leaf text-success me-2"></i><span id="viewBreedName">Breed Details</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <!-- Left: Image -->
                        <div class="col-md-4 text-center mb-3">
                            <div id="viewImageContainer">
                                <div class="view-modal-placeholder">
                                    <i class="bx bx-leaf"></i>
                                </div>
                            </div>
                            <div id="viewBadges" class="mt-3"></div>
                            <div id="viewBrochureLink" class="mt-2"></div>
                        </div>
                        <!-- Right: Details -->
                        <div class="col-md-8">
                            <div class="detail-row">
                                <div class="row">
                                    <div class="col-6">
                                        <span class="detail-label">Manufacturer</span>
                                        <p class="detail-value mb-0" id="viewManufacturer">-</p>
                                    </div>
                                    <div class="col-6">
                                        <span class="detail-label">Status</span>
                                        <p class="detail-value mb-0" id="viewStatus">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-row">
                                <div class="row">
                                    <div class="col-6">
                                        <span class="detail-label">Potential Yield</span>
                                        <p class="detail-value mb-0" id="viewYield">-</p>
                                    </div>
                                    <div class="col-6">
                                        <span class="detail-label">Maturity</span>
                                        <p class="detail-value mb-0" id="viewMaturity">-</p>
                                    </div>
                                </div>
                            </div>
                            <div class="detail-row" id="viewGeneProtectionRow">
                                <span class="detail-label">Gene Protection / Traits</span>
                                <div id="viewGeneProtection" class="mt-1"></div>
                            </div>
                            <div class="detail-row" id="viewCharacteristicsRow">
                                <span class="detail-label">Characteristics</span>
                                <p class="detail-value mb-0" id="viewCharacteristics" style="white-space: pre-wrap;">-</p>
                            </div>
                            <div class="detail-row" id="viewRelatedInfoRow" style="display: none;">
                                <span class="detail-label">Related Information</span>
                                <p class="detail-value mb-0" id="viewRelatedInfo" style="white-space: pre-wrap;">-</p>
                            </div>
                            <div class="detail-row" id="viewSourceUrlRow" style="display: none;">
                                <span class="detail-label">Source</span>
                                <p class="detail-value mb-0"><a href="#" id="viewSourceUrl" target="_blank" class="text-primary"></a></p>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <a href="#" id="viewEditLink" class="btn btn-primary">
                        <i class="bx bx-edit me-1"></i>Edit
                    </a>
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">
                        <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark">Are you sure you want to delete <strong id="deleteBreedName"></strong>?</p>
                    <p class="text-secondary mb-0">This action cannot be undone.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">
                        <i class="bx bx-trash me-1"></i>Delete
                    </button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    // Toastr configuration
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Show/hide corn type filter based on crop type selection
    $('#filterCropType').on('change', function() {
        if ($(this).val() === 'corn') {
            $('#cornTypeFilter').show();
        } else {
            $('#cornTypeFilter').hide();
            $('#cornTypeFilter select').val('');
        }
    });

    // Dynamic per-page change (pagination controls)
    $('#paginationPerPage').on('change', function() {
        const perPage = $(this).val();
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', perPage);
        url.searchParams.delete('page'); // Reset to page 1 when changing per_page
        window.location.href = url.toString();
    });

    // Sync per-page selects (filter and pagination)
    $('#perPageSelect').on('change', function() {
        $('#paginationPerPage').val($(this).val());
    });

    // View Modal functionality
    $('.view-btn').on('click', function() {
        const $btn = $(this);
        const id = $btn.data('id');
        const name = $btn.data('name');
        const cropType = $btn.data('crop-type');
        const breedType = $btn.data('breed-type');
        const cornType = $btn.data('corn-type');
        const manufacturer = $btn.data('manufacturer');
        const yieldVal = $btn.data('yield');
        const maturity = $btn.data('maturity');
        const geneProtection = $btn.data('gene-protection');
        const characteristics = $btn.data('characteristics');
        const relatedInfo = $btn.data('related-info');
        const image = $btn.data('image');
        const brochure = $btn.data('brochure');
        const sourceUrl = $btn.data('source-url');
        const isActive = $btn.data('is-active');

        // Set modal title
        $('#viewBreedName').text(name);

        // Set image
        if (image) {
            $('#viewImageContainer').html('<img src="' + image + '" alt="' + name + '" class="view-modal-image">');
        } else {
            $('#viewImageContainer').html('<div class="view-modal-placeholder"><i class="bx bx-leaf"></i></div>');
        }

        // Set badges
        let badgesHtml = '';
        if (cropType === 'corn') {
            badgesHtml += '<span class="badge bg-warning text-dark me-1"><i class="bx bx-leaf me-1"></i>Corn</span>';
        } else {
            badgesHtml += '<span class="badge bg-success me-1"><i class="bx bx-leaf me-1"></i>Rice</span>';
        }
        badgesHtml += '<span class="badge bg-primary me-1">' + breedType + '</span>';
        if (cropType === 'corn' && cornType && cornType !== '-') {
            badgesHtml += '<span class="badge bg-info text-white">' + cornType + '</span>';
        }
        $('#viewBadges').html(badgesHtml);

        // Set brochure link
        if (brochure) {
            $('#viewBrochureLink').html('<a href="' + brochure + '" target="_blank" class="btn btn-sm btn-outline-danger"><i class="bx bxs-file-pdf me-1"></i>View Brochure</a>');
        } else {
            $('#viewBrochureLink').html('');
        }

        // Set details
        $('#viewManufacturer').text(manufacturer);
        $('#viewYield').text(yieldVal);
        $('#viewMaturity').text(maturity);

        // Set status
        if (isActive === '1' || isActive === 1) {
            $('#viewStatus').html('<span class="badge bg-success">Active</span>');
        } else {
            $('#viewStatus').html('<span class="badge bg-secondary">Inactive</span>');
        }

        // Set gene protection
        if (geneProtection) {
            const genes = geneProtection.split(',');
            let genesHtml = '';
            genes.forEach(function(gene) {
                if (gene.trim()) {
                    genesHtml += '<span class="gene-tag-modal">' + gene.trim() + '</span>';
                }
            });
            $('#viewGeneProtection').html(genesHtml);
            $('#viewGeneProtectionRow').show();
        } else {
            $('#viewGeneProtectionRow').hide();
        }

        // Set characteristics
        if (characteristics) {
            $('#viewCharacteristics').text(characteristics);
            $('#viewCharacteristicsRow').show();
        } else {
            $('#viewCharacteristicsRow').hide();
        }

        // Set related info
        if (relatedInfo) {
            $('#viewRelatedInfo').text(relatedInfo);
            $('#viewRelatedInfoRow').show();
        } else {
            $('#viewRelatedInfoRow').hide();
        }

        // Set source URL
        if (sourceUrl) {
            $('#viewSourceUrl').attr('href', sourceUrl).text(sourceUrl);
            $('#viewSourceUrlRow').show();
        } else {
            $('#viewSourceUrlRow').hide();
        }

        // Set edit link
        $('#viewEditLink').attr('href', '/knowledgebase-crop-breeds-edit?id=' + id);

        // Show modal
        $('#viewModal').modal('show');
    });

    // Status toggle
    $('.status-toggle').on('change', function() {
        const $this = $(this);
        const id = $this.data('id');

        $.ajax({
            url: '/knowledgebase-crop-breeds-toggle-status?id=' + id,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success');
                } else {
                    toastr.error(response.message, 'Error');
                    $this.prop('checked', !$this.prop('checked'));
                }
            },
            error: function() {
                toastr.error('Failed to update status.', 'Error');
                $this.prop('checked', !$this.prop('checked'));
            }
        });
    });

    // Delete functionality
    let deleteId = null;

    $('.delete-btn').on('click', function() {
        deleteId = $(this).data('id');
        const name = $(this).data('name');
        $('#deleteBreedName').text(name);
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        if (!deleteId) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/knowledgebase-crop-breeds-delete?id=' + deleteId,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    $('#breed-row-' + deleteId).fadeOut(400, function() {
                        $(this).remove();
                    });
                    toastr.success(response.message, 'Success');
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to delete.', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
                deleteId = null;
            }
        });
    });
</script>
@endsection
