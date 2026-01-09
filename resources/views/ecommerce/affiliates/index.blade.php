@extends('layouts.master')

@section('title') Affiliates @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Responsive datatable examples -->
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.affiliate-photo {
    width: 50px;
    height: 50px;
    object-fit: cover;
    border-radius: 50%;
    background-color: #f8f9fa;
}
.affiliate-photo-placeholder {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 50%;
    color: #adb5bd;
}

.badge-style {
    border-radius: 20px !important;
    padding: 4px 12px !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    border-width: 1px !important;
    transition: all 0.2s ease !important;
    min-width: auto !important;
    line-height: 1.2 !important;
}

.badge-style:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}

.badge-style:active {
    transform: translateY(0) !important;
}

/* Specific color enhancements for better badge appearance */
.btn-outline-primary.badge-style {
    color: #556ee6 !important;
    border-color: #556ee6 !important;
}

.btn-outline-primary.badge-style:hover {
    background-color: #556ee6 !important;
    color: white !important;
}

.btn-outline-success.badge-style {
    color: #198754 !important;
    border-color: #198754 !important;
}

.btn-outline-success.badge-style:hover {
    background-color: #198754 !important;
    color: white !important;
}

.btn-outline-danger.badge-style {
    color: #f46a6a !important;
    border-color: #f46a6a !important;
}

.btn-outline-danger.badge-style:hover {
    background-color: #f46a6a !important;
    color: white !important;
}

.btn-outline-info.badge-style {
    color: #50a5f1 !important;
    border-color: #50a5f1 !important;
}

.btn-outline-info.badge-style:hover {
    background-color: #50a5f1 !important;
    color: white !important;
}

.btn-outline-warning.badge-style {
    color: #f1b44c !important;
    border-color: #f1b44c !important;
}

.btn-outline-warning.badge-style:hover {
    background-color: #f1b44c !important;
    color: white !important;
}

/* Loading Overlay Styles */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.8);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
}

.loading-spinner {
    text-align: center;
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.loading-text {
    color: #6c757d;
    font-weight: 500;
    font-size: 0.9rem;
}

/* Store badges */
.store-badge {
    display: inline-block;
    padding: 2px 8px;
    margin: 2px;
    font-size: 11px;
    border-radius: 12px;
    background-color: #e9ecef;
    color: #495057;
}

/* Expired badge */
.badge-expired {
    background-color: #f8d7da;
    color: #842029;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('title') Affiliates @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Affiliates</h4>
                    <a href="{{ route('ecom-affiliates.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add New Affiliate
                    </a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bx bx-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <form method="GET" action="{{ route('ecom-affiliates') }}" class="d-flex" id="nameSearchForm">
                            <input type="text" name="name" class="form-control me-2" placeholder="Search by name..." value="{{ request('name') }}">
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('store'))
                                <input type="hidden" name="store" value="{{ request('store') }}">
                            @endif
                            @if(request('expiration'))
                                <input type="hidden" name="expiration" value="{{ request('expiration') }}">
                            @endif
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bx bx-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-2">
                        <form method="GET" action="{{ route('ecom-affiliates') }}" id="statusFilterForm">
                            @if(request('name'))
                                <input type="hidden" name="name" value="{{ request('name') }}">
                            @endif
                            @if(request('store'))
                                <input type="hidden" name="store" value="{{ request('store') }}">
                            @endif
                            @if(request('expiration'))
                                <input type="hidden" name="expiration" value="{{ request('expiration') }}">
                            @endif
                            <select name="status" class="form-select" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-2">
                        <form method="GET" action="{{ route('ecom-affiliates') }}" id="storeFilterForm">
                            @if(request('name'))
                                <input type="hidden" name="name" value="{{ request('name') }}">
                            @endif
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('expiration'))
                                <input type="hidden" name="expiration" value="{{ request('expiration') }}">
                            @endif
                            <select name="store" class="form-select" onchange="this.form.submit()">
                                <option value="">All Stores</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store->id }}" {{ request('store') == $store->id ? 'selected' : '' }}>
                                        {{ $store->storeName }}
                                    </option>
                                @endforeach
                            </select>
                        </form>
                    </div>
                    <div class="col-md-2">
                        <form method="GET" action="{{ route('ecom-affiliates') }}" id="expirationFilterForm">
                            @if(request('name'))
                                <input type="hidden" name="name" value="{{ request('name') }}">
                            @endif
                            @if(request('status'))
                                <input type="hidden" name="status" value="{{ request('status') }}">
                            @endif
                            @if(request('store'))
                                <input type="hidden" name="store" value="{{ request('store') }}">
                            @endif
                            <select name="expiration" class="form-select" onchange="this.form.submit()">
                                <option value="">All Expiration</option>
                                <option value="active" {{ request('expiration') === 'active' ? 'selected' : '' }}>Not Expired</option>
                                <option value="expired" {{ request('expiration') === 'expired' ? 'selected' : '' }}>Expired</option>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-3 text-end">
                        @if(request('name') || request('status') || request('store') || request('expiration'))
                            <a href="{{ route('ecom-affiliates') }}" class="btn btn-outline-danger">
                                <i class="bx bx-x"></i> Clear Filters
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Affiliates Table -->
                <div class="table-responsive position-relative">
                    <!-- Loading Overlay -->
                    <div id="tableLoadingOverlay" class="loading-overlay" style="display: none;">
                        <div class="loading-spinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="loading-text mt-2">Loading affiliates...</div>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 60px;">Photo</th>
                                <th style="width: 140px;">Name</th>
                                <th style="width: 150px;">Contact</th>
                                <th style="width: 100px;">Stores</th>
                                <th style="width: 95px;" class="text-end">Earnings</th>
                                <th style="width: 95px;" class="text-end">Pending</th>
                                <th style="width: 100px;">Expiration</th>
                                <th style="width: 80px;">Status</th>
                                <th style="width: 300px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($affiliates as $affiliate)
                            <tr>
                                <td>
                                    @if($affiliate->userPhoto)
                                        <img src="{{ asset($affiliate->userPhoto) }}" alt="{{ $affiliate->full_name }}" class="affiliate-photo">
                                    @else
                                        <div class="affiliate-photo-placeholder">
                                            <i class="bx bx-user bx-sm"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <h5 class="font-size-14 mb-0">{{ $affiliate->full_name }}</h5>
                                    @if($affiliate->client)
                                        <small class="text-muted">Client ID: {{ $affiliate->clientId }}</small>
                                    @endif
                                </td>
                                <td>
                                    <div><i class="bx bx-phone me-1"></i>{{ $affiliate->phoneNumber }}</div>
                                    @if($affiliate->emailAddress)
                                        <div><i class="bx bx-envelope me-1"></i>{{ $affiliate->emailAddress }}</div>
                                    @endif
                                </td>
                                <td>
                                    @foreach($affiliate->stores as $store)
                                        <span class="store-badge">{{ $store->storeName }}</span>
                                    @endforeach
                                </td>
                                <td class="text-end">
                                    <span class="text-success fw-medium">₱{{ number_format($affiliate->total_earnings, 2) }}</span>
                                </td>
                                <td class="text-end">
                                    <span class="text-warning fw-medium">₱{{ number_format($affiliate->total_pending, 2) }}</span>
                                </td>
                                <td>
                                    @if($affiliate->expirationDate)
                                        @if($affiliate->is_expired)
                                            <span class="badge badge-expired">
                                                <i class="bx bx-calendar-x me-1"></i>{{ $affiliate->expirationDate->format('M d, Y') }}
                                            </span>
                                        @else
                                            <span class="badge bg-light text-dark">
                                                <i class="bx bx-calendar me-1"></i>{{ $affiliate->expirationDate->format('M d, Y') }}
                                            </span>
                                        @endif
                                    @else
                                        <span class="text-muted">No expiration</span>
                                    @endif
                                </td>
                                <td>
                                    @if($affiliate->accountStatus === 'active')
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                                        <button type="button"
                                                class="btn btn-sm btn-outline-info badge-style view-details-btn"
                                                title="View Details"
                                                data-affiliate-id="{{ $affiliate->id }}"
                                                data-affiliate-name="{{ $affiliate->full_name }}">
                                            <i class="bx bx-show me-1"></i>Details
                                        </button>

                                        <button type="button"
                                                class="btn btn-sm btn-outline-warning badge-style earnings-btn"
                                                title="View Earnings"
                                                data-affiliate-id="{{ $affiliate->id }}"
                                                data-affiliate-name="{{ $affiliate->full_name }}">
                                            <i class="bx bx-money me-1"></i>Earnings
                                        </button>

                                        <a href="{{ route('ecom-affiliates.referrals-page', ['id' => $affiliate->id]) }}"
                                           class="btn btn-sm btn-outline-secondary badge-style"
                                           title="Customers Referred">
                                            <i class="bx bx-group me-1"></i>Referred
                                        </a>

                                        <a href="{{ route('ecom-affiliates.edit', ['id' => $affiliate->id]) }}"
                                           class="btn btn-sm btn-outline-success badge-style" title="Edit">
                                            <i class="bx bx-edit me-1"></i>Edit
                                        </a>

                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary badge-style status-btn"
                                                title="Toggle Status"
                                                data-affiliate-id="{{ $affiliate->id }}"
                                                data-affiliate-name="{{ $affiliate->full_name }}"
                                                data-current-status="{{ $affiliate->accountStatus }}">
                                            <i class="bx bx-toggle-right me-1"></i>Status
                                        </button>

                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger badge-style delete-btn"
                                                data-affiliate-id="{{ $affiliate->id }}"
                                                data-affiliate-name="{{ $affiliate->full_name }}"
                                                title="Delete">
                                            <i class="bx bx-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="9" class="text-center text-muted py-4">
                                    <i class="bx bx-user-plus display-4"></i>
                                    <p class="mt-2 mb-0">No affiliates found. <a href="{{ route('ecom-affiliates.create') }}">Add your first affiliate</a></p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($affiliates instanceof \Illuminate\Pagination\LengthAwarePaginator && $affiliates->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $affiliates->firstItem() }} to {{ $affiliates->lastItem() }} of {{ $affiliates->total() }} affiliates
                        </div>
                        <div>
                            {{ $affiliates->appends(request()->query())->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this affiliate?</p>
                <p class="text-muted mb-2"><strong>Affiliate:</strong> <span id="deleteAffiliateName"></span></p>
                <p class="text-muted small mb-0">This action can be undone by a database administrator.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bx bx-trash me-1"></i>Delete Affiliate
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Status Update Modal -->
<div class="modal fade" id="statusModal" tabindex="-1" aria-labelledby="statusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusModalLabel">
                    <i class="bx bx-toggle-right text-primary me-2"></i>Update Affiliate Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Update the status for the following affiliate:</p>
                <p class="text-muted mb-3"><strong>Affiliate:</strong> <span id="statusAffiliateName"></span></p>

                <div class="mb-3">
                    <label for="statusSelect" class="form-label">Status</label>
                    <select class="form-select" id="statusSelect">
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                    </select>
                    <small class="text-muted">Inactive affiliates won't be available for order assignments.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmStatusUpdate">
                    <i class="bx bx-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- View Details Modal -->
<div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title" id="viewDetailsModalLabel">
                    <i class="bx bx-user-circle me-2"></i>Affiliate Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading State -->
                <div id="detailsLoading" class="text-center py-4">
                    <div class="spinner-border text-info" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-secondary mt-2 mb-0">Loading affiliate details...</p>
                </div>

                <!-- Details Content -->
                <div id="detailsContent" style="display: none;">
                    <div class="row">
                        <!-- Photo and Basic Info -->
                        <div class="col-md-4 text-center mb-3">
                            <div id="detailsPhoto" class="mb-3">
                                <!-- Photo will be inserted here -->
                            </div>
                            <h5 class="text-dark mb-1" id="detailsFullName"></h5>
                            <div id="detailsStatusBadge" class="mb-2"></div>
                            <div id="detailsClientInfo" class="text-secondary small"></div>
                        </div>

                        <!-- Contact & Details -->
                        <div class="col-md-8">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-dark mb-2"><i class="bx bx-phone me-1"></i>Contact Information</h6>
                                    <div class="ps-3">
                                        <div class="mb-1"><strong class="text-dark">Phone:</strong> <span id="detailsPhone" class="text-dark"></span></div>
                                        <div><strong class="text-dark">Email:</strong> <span id="detailsEmail" class="text-dark"></span></div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-dark mb-2"><i class="bx bx-calendar me-1"></i>Account Info</h6>
                                    <div class="ps-3">
                                        <div class="mb-1"><strong class="text-dark">Expiration:</strong> <span id="detailsExpiration" class="text-dark"></span></div>
                                        <div><strong class="text-dark">Created:</strong> <span id="detailsCreated" class="text-dark"></span></div>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-dark mb-2"><i class="bx bx-wallet me-1"></i>Payment Details</h6>
                                    <div class="ps-3">
                                        <div class="mb-1"><strong class="text-dark">GCash:</strong> <span id="detailsGcash" class="text-dark"></span></div>
                                        <div><strong class="text-dark">Bank:</strong> <span id="detailsBank" class="text-dark"></span></div>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <h6 class="text-dark mb-2"><i class="bx bx-store me-1"></i>Assigned Stores</h6>
                                    <div class="ps-3" id="detailsStores">
                                        <!-- Stores will be inserted here -->
                                    </div>
                                </div>
                            </div>

                            <!-- Documents Section -->
                            <div class="mb-3">
                                <h6 class="text-dark mb-2"><i class="bx bx-file me-1"></i>Documents</h6>
                                <div class="ps-3" id="detailsDocuments">
                                    <!-- Documents will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Earnings by Store Section -->
                    <div class="row mt-3">
                        <div class="col-12">
                            <div class="card mb-0 border">
                                <div class="card-header bg-light py-2">
                                    <h6 class="mb-0 text-dark"><i class="bx bx-money me-1"></i>Earnings by Store</h6>
                                </div>
                                <div class="card-body p-0" id="detailsEarningsByStore">
                                    <!-- Earnings will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <a href="#" id="detailsEditLink" class="btn btn-success">
                    <i class="bx bx-edit me-1"></i>Edit Affiliate
                </a>
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Earnings Modal -->
<div class="modal fade" id="earningsModal" tabindex="-1" aria-labelledby="earningsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark" id="earningsModalLabel">
                    <i class="bx bx-money me-2"></i>Affiliate Earnings
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <!-- Loading State -->
                <div id="earningsLoading" class="text-center py-4">
                    <div class="spinner-border text-warning" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="text-secondary mt-2 mb-0">Loading earnings data...</p>
                </div>

                <!-- Earnings Content -->
                <div id="earningsContent" style="display: none;">
                    <div class="text-center mb-3">
                        <h5 class="text-dark" id="earningsAffiliateName"></h5>
                    </div>

                    <!-- Earnings Summary Cards -->
                    <div class="row mb-4">
                        <div class="col-md-4">
                            <div class="card bg-success text-white mb-0">
                                <div class="card-body text-center py-3">
                                    <h3 class="mb-1" id="earningsTotalPaid">₱0.00</h3>
                                    <small>Total Paid</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-warning mb-0">
                                <div class="card-body text-center py-3">
                                    <h3 class="mb-1 text-dark" id="earningsTotalPending">₱0.00</h3>
                                    <small class="text-dark">Pending</small>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="card bg-primary text-white mb-0">
                                <div class="card-body text-center py-3">
                                    <h3 class="mb-1" id="earningsTotal">₱0.00</h3>
                                    <small>Total Earnings</small>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Earnings by Store -->
                    <div class="row mb-4">
                        <div class="col-12">
                            <div class="card mb-0">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 text-dark"><i class="bx bx-store me-1"></i>Earnings by Store</h6>
                                </div>
                                <div class="card-body p-0" id="earningsByStoreContainer">
                                    <!-- Store earnings will be inserted here -->
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Period Summary -->
                    <div class="row">
                        <div class="col-12">
                            <div class="card mb-0">
                                <div class="card-header bg-light">
                                    <h6 class="mb-0 text-dark"><i class="bx bx-bar-chart-alt-2 me-1"></i>Period Summary</h6>
                                </div>
                                <div class="card-body">
                                    <div class="row text-center">
                                        <div class="col-md-4">
                                            <h5 class="text-dark" id="earningsThisMonth">₱0.00</h5>
                                            <small class="text-secondary">This Month</small>
                                        </div>
                                        <div class="col-md-4">
                                            <h5 class="text-dark" id="earningsLastMonth">₱0.00</h5>
                                            <small class="text-secondary">Last Month</small>
                                        </div>
                                        <div class="col-md-4">
                                            <h5 class="text-dark" id="earningsThisYear">₱0.00</h5>
                                            <small class="text-secondary">This Year</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Close
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Required datatable js -->
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<!-- Responsive examples -->
<script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
// Toastr configuration
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000,
    extendedTimeOut: 1000,
    preventDuplicates: true
};

$(document).ready(function() {
    // Show loading overlay
    function showLoading() {
        $('#tableLoadingOverlay').show();
    }

    // Hide loading overlay
    function hideLoading() {
        $('#tableLoadingOverlay').hide();
    }

    // Show loading on page load for better UX
    showLoading();
    setTimeout(function() {
        hideLoading();
    }, 300);

    // Show loading on form submissions
    $('form').on('submit', function() {
        showLoading();
    });

    // Show loading on pagination links
    $('.pagination a').on('click', function() {
        showLoading();
    });

    // Show loading on clear filters link
    $('a[href="{{ route("ecom-affiliates") }}"]').on('click', function() {
        showLoading();
    });

    // Delete functionality
    let affiliateToDelete = null;

    // Show delete confirmation modal
    $('.delete-btn').on('click', function() {
        affiliateToDelete = {
            id: $(this).data('affiliate-id'),
            name: $(this).data('affiliate-name'),
            row: $(this).closest('tr')
        };

        $('#deleteAffiliateName').text(affiliateToDelete.name);
        $('#deleteModal').modal('show');
    });

    // Handle delete confirmation
    $('#confirmDelete').on('click', function() {
        if (!affiliateToDelete) return;

        const $btn = $(this);
        const originalText = $btn.html();

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');
        showLoading();

        $.ajax({
            url: '/ecom-affiliates/' + affiliateToDelete.id,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Hide modal
                    $('#deleteModal').modal('hide');

                    // Show success toastr notification
                    toastr.success(response.message, 'Success!');

                    // Remove the row from the table with animation
                    affiliateToDelete.row.fadeOut(400, function() {
                        $(this).remove();

                        // Check if table is empty
                        if ($('tbody tr').length === 0) {
                            $('tbody').html(`
                                <tr>
                                    <td colspan="9" class="text-center text-muted py-4">
                                        <i class="bx bx-user-plus display-4"></i>
                                        <p class="mt-2 mb-0">No affiliates found. <a href="{{ route('ecom-affiliates.create') }}">Add your first affiliate</a></p>
                                    </td>
                                </tr>
                            `);
                        }
                    });
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while deleting the affiliate.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, 'Error!');
            },
            complete: function() {
                // Reset button state
                $btn.prop('disabled', false).html(originalText);
                affiliateToDelete = null;
                hideLoading();
            }
        });
    });

    // Reset affiliateToDelete when modal is hidden
    $('#deleteModal').on('hidden.bs.modal', function() {
        affiliateToDelete = null;
    });

    // Status update functionality
    let affiliateToUpdateStatus = null;

    // Show status update modal
    $('.status-btn').on('click', function() {
        const affiliateId = $(this).data('affiliate-id');
        const affiliateName = $(this).data('affiliate-name');
        const currentStatus = $(this).data('current-status');

        affiliateToUpdateStatus = {
            id: affiliateId,
            name: affiliateName,
            currentStatus: currentStatus,
            row: $(this).closest('tr')
        };

        $('#statusAffiliateName').text(affiliateName);
        $('#statusSelect').val(currentStatus);

        $('#statusModal').modal('show');
    });

    // Handle status update confirmation
    $('#confirmStatusUpdate').on('click', function() {
        if (!affiliateToUpdateStatus) return;

        const $btn = $(this);
        const originalText = $btn.html();
        const newStatus = $('#statusSelect').val();

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');
        showLoading();

        $.ajax({
            url: '/ecom-affiliates/' + affiliateToUpdateStatus.id + '/status',
            type: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}',
                accountStatus: newStatus
            },
            success: function(response) {
                if (response.success) {
                    // Hide modal
                    $('#statusModal').modal('hide');

                    // Show success toastr notification
                    toastr.success(response.message, 'Success!');

                    // Update the status badge in the table (column 8 with new columns)
                    const statusCell = affiliateToUpdateStatus.row.find('td:nth-child(8)');
                    if (newStatus === 'active') {
                        statusCell.html('<span class="badge bg-success">Active</span>');
                    } else {
                        statusCell.html('<span class="badge bg-secondary">Inactive</span>');
                    }

                    // Update the data attribute on the status button
                    affiliateToUpdateStatus.row.find('.status-btn').data('current-status', newStatus);
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while updating the affiliate status.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, 'Error!');
            },
            complete: function() {
                // Reset button state
                $btn.prop('disabled', false).html(originalText);
                affiliateToUpdateStatus = null;
                hideLoading();
            }
        });
    });

    // Reset affiliateToUpdateStatus when modal is hidden
    $('#statusModal').on('hidden.bs.modal', function() {
        affiliateToUpdateStatus = null;
    });

    // ===== VIEW DETAILS FUNCTIONALITY =====

    // Helper function to escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Helper function to format currency
    function formatCurrency(amount) {
        return '₱' + parseFloat(amount).toLocaleString('en-PH', {
            minimumFractionDigits: 2,
            maximumFractionDigits: 2
        });
    }

    // Show View Details modal
    $('.view-details-btn').on('click', function() {
        const affiliateId = $(this).data('affiliate-id');
        const affiliateName = $(this).data('affiliate-name');

        // Show loading, hide content
        $('#detailsLoading').show();
        $('#detailsContent').hide();

        // Update edit link
        $('#detailsEditLink').attr('href', '/ecom-affiliates-edit?id=' + affiliateId);

        // Show modal
        $('#viewDetailsModal').modal('show');

        // Fetch affiliate details
        $.ajax({
            url: '/ecom-affiliates/' + affiliateId + '/details',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;

                    // Photo
                    if (data.userPhoto) {
                        $('#detailsPhoto').html(`
                            <img src="${data.userPhoto}" alt="${escapeHtml(data.fullName)}"
                                 class="rounded-circle" style="width: 120px; height: 120px; object-fit: cover;">
                        `);
                    } else {
                        $('#detailsPhoto').html(`
                            <div class="d-inline-flex align-items-center justify-content-center bg-light rounded-circle"
                                 style="width: 120px; height: 120px;">
                                <i class="bx bx-user text-secondary" style="font-size: 3rem;"></i>
                            </div>
                        `);
                    }

                    // Basic info
                    $('#detailsFullName').text(data.fullName);

                    // Status badge
                    if (data.accountStatus === 'active') {
                        $('#detailsStatusBadge').html('<span class="badge bg-success">Active</span>');
                    } else {
                        $('#detailsStatusBadge').html('<span class="badge bg-secondary">Inactive</span>');
                    }

                    // Client info
                    if (data.clientId) {
                        $('#detailsClientInfo').html(`<i class="bx bx-link me-1"></i>Linked to Client: ${escapeHtml(data.clientName)}`);
                    } else {
                        $('#detailsClientInfo').text('Not linked to a client');
                    }

                    // Contact info
                    $('#detailsPhone').text(data.phoneNumber || 'N/A');
                    $('#detailsEmail').text(data.emailAddress || 'N/A');

                    // Account info
                    if (data.expirationDate) {
                        if (data.isExpired) {
                            $('#detailsExpiration').html(`<span class="text-danger">${escapeHtml(data.expirationDate)} (Expired)</span>`);
                        } else {
                            $('#detailsExpiration').text(data.expirationDate);
                        }
                    } else {
                        $('#detailsExpiration').text('No expiration');
                    }
                    $('#detailsCreated').text(data.createdAt);

                    // Payment details
                    $('#detailsGcash').text(data.gcashNumber || 'N/A');
                    if (data.bankDetails) {
                        const bank = data.bankDetails;
                        $('#detailsBank').html(`
                            ${escapeHtml(bank.bankName || 'N/A')}<br>
                            <small class="text-secondary">Acct: ${escapeHtml(bank.accountNumber || 'N/A')}</small><br>
                            <small class="text-secondary">Name: ${escapeHtml(bank.accountName || 'N/A')}</small>
                        `);
                    } else {
                        $('#detailsBank').text('N/A');
                    }

                    // Stores
                    if (data.stores && data.stores.length > 0) {
                        let storesHtml = '';
                        data.stores.forEach(function(store) {
                            storesHtml += `<span class="badge bg-light text-dark me-1 mb-1">${escapeHtml(store.name)}</span>`;
                        });
                        $('#detailsStores').html(storesHtml);
                    } else {
                        $('#detailsStores').html('<span class="text-secondary">No stores assigned</span>');
                    }

                    // Documents
                    if (data.documents && data.documents.length > 0) {
                        let docsHtml = '<ul class="list-unstyled mb-0">';
                        data.documents.forEach(function(doc) {
                            docsHtml += `
                                <li class="mb-2">
                                    <a href="${doc.path}" target="_blank" class="text-primary">
                                        <i class="bx bx-file me-1"></i>${escapeHtml(doc.name)}
                                    </a>
                                    <small class="text-secondary d-block">Type: ${escapeHtml(doc.type)} | Added: ${escapeHtml(doc.created_at)}</small>
                                </li>
                            `;
                        });
                        docsHtml += '</ul>';
                        $('#detailsDocuments').html(docsHtml);
                    } else {
                        $('#detailsDocuments').html('<span class="text-secondary">No documents uploaded</span>');
                    }

                    // Earnings by Store
                    if (data.earningsByStore && data.earningsByStore.length > 0) {
                        let earningsHtml = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">';
                        earningsHtml += '<thead class="table-light"><tr>';
                        earningsHtml += '<th class="text-dark">Store</th>';
                        earningsHtml += '<th class="text-dark text-end">Earnings</th>';
                        earningsHtml += '<th class="text-dark text-end">Pending</th>';
                        earningsHtml += '<th class="text-dark text-end">Total</th>';
                        earningsHtml += '</tr></thead><tbody>';

                        let grandEarnings = 0;
                        let grandPending = 0;

                        data.earningsByStore.forEach(function(store) {
                            const storeTotal = store.totalEarnings + store.totalPending;
                            grandEarnings += store.totalEarnings;
                            grandPending += store.totalPending;

                            earningsHtml += `
                                <tr>
                                    <td class="text-dark"><i class="bx bx-store text-secondary me-1"></i>${escapeHtml(store.storeName)}</td>
                                    <td class="text-end text-success">${formatCurrency(store.totalEarnings)}</td>
                                    <td class="text-end text-warning">${formatCurrency(store.totalPending)}</td>
                                    <td class="text-end text-dark fw-medium">${formatCurrency(storeTotal)}</td>
                                </tr>
                            `;
                        });

                        // Totals row
                        earningsHtml += `
                            <tr class="table-secondary fw-bold">
                                <td class="text-dark">TOTAL</td>
                                <td class="text-end text-success">${formatCurrency(grandEarnings)}</td>
                                <td class="text-end text-warning">${formatCurrency(grandPending)}</td>
                                <td class="text-end text-dark">${formatCurrency(grandEarnings + grandPending)}</td>
                            </tr>
                        `;

                        earningsHtml += '</tbody></table></div>';
                        $('#detailsEarningsByStore').html(earningsHtml);
                    } else {
                        $('#detailsEarningsByStore').html(`
                            <div class="text-center py-3 text-secondary">
                                <i class="bx bx-money" style="font-size: 2rem;"></i>
                                <p class="mb-0 small">No earnings recorded yet.</p>
                            </div>
                        `);
                    }

                    // Show content, hide loading
                    $('#detailsLoading').hide();
                    $('#detailsContent').show();
                } else {
                    toastr.error(response.message, 'Error!');
                    $('#viewDetailsModal').modal('hide');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while fetching affiliate details.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, 'Error!');
                $('#viewDetailsModal').modal('hide');
            }
        });
    });

    // Reset View Details modal when hidden
    $('#viewDetailsModal').on('hidden.bs.modal', function() {
        $('#detailsLoading').show();
        $('#detailsContent').hide();
    });

    // ===== EARNINGS FUNCTIONALITY =====

    // Show Earnings modal
    $('.earnings-btn').on('click', function() {
        const affiliateId = $(this).data('affiliate-id');
        const affiliateName = $(this).data('affiliate-name');

        // Show loading, hide content
        $('#earningsLoading').show();
        $('#earningsContent').hide();

        // Show modal
        $('#earningsModal').modal('show');

        // Fetch affiliate earnings
        $.ajax({
            url: '/ecom-affiliates/' + affiliateId + '/earnings',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const data = response.data;

                    // Affiliate name
                    $('#earningsAffiliateName').text(data.affiliateName);

                    // Summary cards
                    $('#earningsTotalPaid').text(formatCurrency(data.totalPaid));
                    $('#earningsTotalPending').text(formatCurrency(data.totalPending));
                    $('#earningsTotal').text(formatCurrency(data.totalEarnings));

                    // Period summary
                    $('#earningsThisMonth').text(formatCurrency(data.summary.thisMonth));
                    $('#earningsLastMonth').text(formatCurrency(data.summary.lastMonth));
                    $('#earningsThisYear').text(formatCurrency(data.summary.thisYear));

                    // Earnings by Store
                    if (data.earningsByStore && data.earningsByStore.length > 0) {
                        let storeHtml = '<div class="table-responsive"><table class="table table-sm table-hover mb-0">';
                        storeHtml += '<thead class="table-light"><tr>';
                        storeHtml += '<th class="text-dark">Store</th>';
                        storeHtml += '<th class="text-dark text-end">Earnings</th>';
                        storeHtml += '<th class="text-dark text-end">Pending</th>';
                        storeHtml += '<th class="text-dark text-end">Total</th>';
                        storeHtml += '</tr></thead><tbody>';

                        let grandTotalEarnings = 0;
                        let grandTotalPending = 0;

                        data.earningsByStore.forEach(function(store) {
                            const storeTotal = store.totalEarnings + store.totalPending;
                            grandTotalEarnings += store.totalEarnings;
                            grandTotalPending += store.totalPending;

                            storeHtml += `
                                <tr>
                                    <td class="text-dark">
                                        <i class="bx bx-store text-secondary me-1"></i>
                                        ${escapeHtml(store.storeName)}
                                    </td>
                                    <td class="text-end text-success fw-medium">${formatCurrency(store.totalEarnings)}</td>
                                    <td class="text-end text-warning fw-medium">${formatCurrency(store.totalPending)}</td>
                                    <td class="text-end text-dark fw-bold">${formatCurrency(storeTotal)}</td>
                                </tr>
                            `;
                        });

                        // Add totals row
                        const grandTotal = grandTotalEarnings + grandTotalPending;
                        storeHtml += `
                            <tr class="table-secondary fw-bold">
                                <td class="text-dark">TOTAL</td>
                                <td class="text-end text-success">${formatCurrency(grandTotalEarnings)}</td>
                                <td class="text-end text-warning">${formatCurrency(grandTotalPending)}</td>
                                <td class="text-end text-dark">${formatCurrency(grandTotal)}</td>
                            </tr>
                        `;

                        storeHtml += '</tbody></table></div>';
                        $('#earningsByStoreContainer').html(storeHtml);
                    } else {
                        $('#earningsByStoreContainer').html(`
                            <div class="text-center py-4 text-secondary">
                                <i class="bx bx-store display-4"></i>
                                <p class="mt-2 mb-0">No store earnings yet.</p>
                                <small>Earnings will appear here when sales are made through assigned stores.</small>
                            </div>
                        `);
                    }

                    // Show content, hide loading
                    $('#earningsLoading').hide();
                    $('#earningsContent').show();
                } else {
                    toastr.error(response.message, 'Error!');
                    $('#earningsModal').modal('hide');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while fetching affiliate earnings.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, 'Error!');
                $('#earningsModal').modal('hide');
            }
        });
    });

    // Reset Earnings modal when hidden
    $('#earningsModal').on('hidden.bs.modal', function() {
        $('#earningsLoading').show();
        $('#earningsContent').hide();
    });
});
</script>
@endsection
