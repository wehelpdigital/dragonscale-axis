@extends('layouts.master')

@section('title') Stores @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Responsive datatable examples -->
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.store-logo {
    width: 50px;
    height: 50px;
    object-fit: contain;
    border-radius: 8px;
    background-color: #f8f9fa;
    padding: 4px;
}
.store-logo-placeholder {
    width: 50px;
    height: 50px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 8px;
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

.btn-outline-secondary.badge-style {
    color: #74788d !important;
    border-color: #74788d !important;
}

.btn-outline-secondary.badge-style:hover {
    background-color: #74788d !important;
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

/* Disable interactions during loading */
.loading-overlay.active {
    pointer-events: all;
}

.loading-overlay.active ~ * {
    pointer-events: none;
    opacity: 0.6;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('title') Stores @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Stores</h4>
                    <a href="{{ route('ecom-stores.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add New Store
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
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('ecom-stores') }}" class="d-flex">
                            <input type="text" name="name" class="form-control me-2" placeholder="Search by store name..." value="{{ request('name') }}">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bx bx-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-3">
                        <form method="GET" action="{{ route('ecom-stores') }}" class="d-flex">
                            <select name="status" class="form-select me-2">
                                <option value="">All Status</option>
                                <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                                <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bx bx-filter"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-5 text-end">
                        @if(request('name') || request('status') !== null && request('status') !== '')
                            <a href="{{ route('ecom-stores') }}" class="btn btn-outline-danger">
                                <i class="bx bx-x"></i> Clear Filters
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Stores Table -->
                <div class="table-responsive position-relative">
                    <!-- Loading Overlay -->
                    <div id="tableLoadingOverlay" class="loading-overlay" style="display: none;">
                        <div class="loading-spinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="loading-text mt-2">Loading stores...</div>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 70px;">Logo</th>
                                <th>Store Name</th>
                                <th style="width: 100px;">Products</th>
                                <th style="width: 100px;">Status</th>
                                <th style="width: 440px;" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($stores as $store)
                            <tr>
                                <td>
                                    @if($store->storeLogo)
                                        <img src="{{ asset($store->storeLogo) }}" alt="{{ $store->storeName }}" class="store-logo">
                                    @else
                                        <div class="store-logo-placeholder">
                                            <i class="bx bx-store bx-sm"></i>
                                        </div>
                                    @endif
                                </td>
                                <td>
                                    <h5 class="font-size-14 mb-0">{{ $store->storeName }}</h5>
                                </td>
                                <td>
                                    <span class="badge bg-info">{{ $store->active_products_count }} Products</span>
                                </td>
                                <td>
                                    @if($store->isActive)
                                        <span class="badge bg-success">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                                        <a href="{{ route('ecom-stores.edit', ['id' => $store->id]) }}"
                                           class="btn btn-sm btn-outline-success badge-style" title="Edit">
                                            <i class="bx bx-edit me-1"></i>Edit
                                        </a>

                                        <a href="{{ route('ecom-store-settings', ['id' => $store->id]) }}"
                                           class="btn btn-sm btn-outline-secondary badge-style" title="Settings">
                                            <i class="bx bx-cog me-1"></i>Settings
                                        </a>

                                        <a href="{{ route('ecom-store-logins', ['id' => $store->id]) }}"
                                           class="btn btn-sm btn-outline-info badge-style" title="Logins">
                                            <i class="bx bx-key me-1"></i>Logins
                                        </a>

                                        <button type="button"
                                                class="btn btn-sm btn-outline-primary badge-style status-btn"
                                                title="Toggle Status"
                                                data-store-id="{{ $store->id }}"
                                                data-store-name="{{ $store->storeName }}"
                                                data-current-status="{{ $store->isActive ? 1 : 0 }}">
                                            <i class="bx bx-toggle-right me-1"></i>Status
                                        </button>

                                        <button type="button"
                                                class="btn btn-sm btn-outline-danger badge-style delete-btn"
                                                data-store-id="{{ $store->id }}"
                                                data-store-name="{{ $store->storeName }}"
                                                data-products-count="{{ $store->active_products_count }}"
                                                title="Delete">
                                            <i class="bx bx-trash me-1"></i>Delete
                                        </button>
                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center text-muted py-4">
                                    <i class="bx bx-store display-4"></i>
                                    <p class="mt-2 mb-0">No stores found. <a href="{{ route('ecom-stores.create') }}">Add your first store</a></p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($stores instanceof \Illuminate\Pagination\LengthAwarePaginator && $stores->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $stores->firstItem() }} to {{ $stores->lastItem() }} of {{ $stores->total() }} stores
                        </div>
                        <div>
                            {{ $stores->appends(request()->query())->links() }}
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
                <p>Are you sure you want to delete this store?</p>
                <p class="text-muted mb-2"><strong>Store:</strong> <span id="deleteStoreName"></span></p>
                <div id="productsWarning" class="alert alert-warning d-none">
                    <i class="bx bx-error-circle me-1"></i>
                    This store has <strong id="productsCount"></strong> product(s). They will remain but won't be visible in store selection dropdowns.
                </div>
                <p class="text-muted small mb-0">This action can be undone by a database administrator.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bx bx-trash me-1"></i>Delete Store
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
                    <i class="bx bx-toggle-right text-primary me-2"></i>Update Store Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Update the status for the following store:</p>
                <p class="text-muted mb-3"><strong>Store:</strong> <span id="statusStoreName"></span></p>

                <div class="mb-3">
                    <label for="statusSelect" class="form-label">Status</label>
                    <select class="form-select" id="statusSelect">
                        <option value="1">Active</option>
                        <option value="0">Inactive</option>
                    </select>
                    <small class="text-muted">Inactive stores won't appear in product creation forms.</small>
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

    // Auto-submit status filter when changed
    $('select[name="status"]').change(function() {
        showLoading();
        $(this).closest('form').submit();
    });

    // Show loading on name search form submission
    $('form input[name="name"]').closest('form').on('submit', function() {
        showLoading();
    });

    // Show loading on pagination links
    $('.pagination a').on('click', function() {
        showLoading();
    });

    // Show loading on clear filters link
    $('a[href="{{ route("ecom-stores") }}"]').on('click', function() {
        showLoading();
    });

    // Delete functionality
    let storeToDelete = null;

    // Show delete confirmation modal
    $('.delete-btn').on('click', function() {
        storeToDelete = {
            id: $(this).data('store-id'),
            name: $(this).data('store-name'),
            productsCount: $(this).data('products-count'),
            row: $(this).closest('tr')
        };

        $('#deleteStoreName').text(storeToDelete.name);

        if (storeToDelete.productsCount > 0) {
            $('#productsCount').text(storeToDelete.productsCount);
            $('#productsWarning').removeClass('d-none');
        } else {
            $('#productsWarning').addClass('d-none');
        }

        $('#deleteModal').modal('show');
    });

    // Handle delete confirmation
    $('#confirmDelete').on('click', function() {
        if (!storeToDelete) return;

        const $btn = $(this);
        const originalText = $btn.html();

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');
        showLoading();

        $.ajax({
            url: '/ecom-stores/' + storeToDelete.id,
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
                    storeToDelete.row.fadeOut(400, function() {
                        $(this).remove();

                        // Check if table is empty
                        if ($('tbody tr').length === 0) {
                            $('tbody').html(`
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">
                                        <i class="bx bx-store display-4"></i>
                                        <p class="mt-2 mb-0">No stores found. <a href="{{ route('ecom-stores.create') }}">Add your first store</a></p>
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
                let errorMessage = 'An error occurred while deleting the store.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, 'Error!');
            },
            complete: function() {
                // Reset button state
                $btn.prop('disabled', false).html(originalText);
                storeToDelete = null;
                hideLoading();
            }
        });
    });

    // Reset storeToDelete when modal is hidden
    $('#deleteModal').on('hidden.bs.modal', function() {
        storeToDelete = null;
    });

    // Status update functionality
    let storeToUpdateStatus = null;

    // Show status update modal
    $('.status-btn').on('click', function() {
        const storeId = $(this).data('store-id');
        const storeName = $(this).data('store-name');
        const rawStatus = $(this).data('current-status');

        // Handle empty or null values
        let currentStatus = 0; // Default to 0 (Inactive)
        if (rawStatus !== null && rawStatus !== undefined && rawStatus !== '') {
            currentStatus = parseInt(rawStatus);
            if (isNaN(currentStatus)) {
                currentStatus = 0;
            }
        }

        storeToUpdateStatus = {
            id: storeId,
            name: storeName,
            currentStatus: currentStatus,
            row: $(this).closest('tr')
        };

        $('#statusStoreName').text(storeName);
        $('#statusSelect').val(currentStatus);

        $('#statusModal').modal('show');
    });

    // Handle status update confirmation
    $('#confirmStatusUpdate').on('click', function() {
        if (!storeToUpdateStatus) return;

        const $btn = $(this);
        const originalText = $btn.html();
        const newStatus = $('#statusSelect').val();

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');
        showLoading();

        $.ajax({
            url: '/ecom-stores/' + storeToUpdateStatus.id + '/status',
            type: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}',
                isActive: newStatus
            },
            success: function(response) {
                if (response.success) {
                    // Hide modal
                    $('#statusModal').modal('hide');

                    // Show success toastr notification
                    toastr.success(response.message, 'Success!');

                    // Update the status badge in the table
                    const statusCell = storeToUpdateStatus.row.find('td:nth-child(4)');
                    if (newStatus == 1) {
                        statusCell.html('<span class="badge bg-success">Active</span>');
                    } else {
                        statusCell.html('<span class="badge bg-secondary">Inactive</span>');
                    }

                    // Update the data attribute on the status button
                    storeToUpdateStatus.row.find('.status-btn').data('current-status', newStatus);
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while updating the store status.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, 'Error!');
            },
            complete: function() {
                // Reset button state
                $btn.prop('disabled', false).html(originalText);
                storeToUpdateStatus = null;
                hideLoading();
            }
        });
    });

    // Reset storeToUpdateStatus when modal is hidden
    $('#statusModal').on('hidden.bs.modal', function() {
        storeToUpdateStatus = null;
    });
});
</script>
@endsection
