@extends('layouts.master')

@section('title') Products @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Responsive datatable examples -->
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
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

.btn-outline-secondary.badge-style {
    color: #74788d !important;
    border-color: #74788d !important;
}

.btn-outline-secondary.badge-style:hover {
    background-color: #74788d !important;
    color: white !important;
}

.btn-outline-success.badge-style {
    color: #34c38f !important;
    border-color: #34c38f !important;
}

.btn-outline-success.badge-style:hover {
    background-color: #34c38f !important;
    color: white !important;
}

.btn-outline-dark.badge-style {
    color: #495057 !important;
    border-color: #495057 !important;
}

.btn-outline-dark.badge-style:hover {
    background-color: #495057 !important;
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
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('title') Products @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Products</h4>
                    <a href="{{ route('ecom-products.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add New Product
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
                        <form method="GET" action="{{ route('ecom-products') }}" class="d-flex">
                            <input type="text" name="name" class="form-control me-2" placeholder="Search by product name..." value="{{ request('name') }}">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bx bx-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('ecom-products') }}" class="d-flex">
                            <select name="store" class="form-select me-2">
                                <option value="">All Stores</option>
                                @foreach($stores as $store)
                                    <option value="{{ $store }}" {{ request('store') == $store ? 'selected' : '' }}>
                                        {{ $store }}
                                    </option>
                                @endforeach
                            </select>
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bx bx-filter"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-4 text-end">
                        @if(request('name') || request('store'))
                            <a href="{{ route('ecom-products') }}" class="btn btn-outline-danger">
                                <i class="bx bx-x"></i> Clear Filters
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Products Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Product Name</th>
                                <th>Product Store</th>
                                <th>Active</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($products as $product)
                                <tr>
                                    <td>{{ $product->productName }}</td>
                                    <td>{{ $product->productStore }}</td>
                                    <td>
                                        @if($product->isActive)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-danger">No</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap gap-1 justify-content-center">
                                            <a href="{{ route('ecom-products.variants', ['id' => $product->id]) }}"
                                               class="btn btn-sm btn-outline-primary badge-style"
                                               title="Variants">
                                                <i class="bx bx-list-ul me-1"></i>Variants
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-secondary badge-style" title="Content">
                                                <i class="bx bx-file me-1"></i>Content
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-success badge-style" title="Edit">
                                                <i class="bx bx-edit me-1"></i>Edit
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-dark badge-style" title="Discounts">
                                                <i class="bx bx-discount me-1"></i>Discounts
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-primary badge-style status-btn" title="Status"
                                                    data-product-id="{{ $product->id }}"
                                                    data-product-name="{{ $product->productName }}"
                                                    data-current-status="{{ $product->isActive ? 1 : 0 }}">
                                                <i class="bx bx-toggle-right me-1"></i>Status
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-danger badge-style delete-btn"
                                                    title="Delete"
                                                    data-product-id="{{ $product->id }}"
                                                    data-product-name="{{ $product->productName }}">
                                                <i class="bx bx-trash me-1"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i class="bx bx-package display-4"></i>
                                        <p class="mt-2">No products found</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($products->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $products->firstItem() }} to {{ $products->lastItem() }} of {{ $products->total() }} products
                        </div>
                        <div>
                            {{ $products->appends(request()->query())->links() }}
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
                <p>Are you sure you want to delete this product?</p>
                <p class="text-muted mb-0"><strong>Product:</strong> <span id="deleteProductName"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bx bx-trash me-1"></i>Delete Product
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
                    <i class="bx bx-toggle-right text-primary me-2"></i>Update Product Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Update the status for the following product:</p>
                <p class="text-muted mb-3"><strong>Product:</strong> <span id="statusProductName"></span></p>

                <div class="mb-3">
                    <label for="statusSelect" class="form-label">Status</label>
                    <select class="form-select" id="statusSelect">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
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
<!-- Buttons examples -->
<script src="{{ URL::asset('build/libs/datatables.net-buttons/js/dataTables.buttons.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-buttons-bs4/js/buttons.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/jszip/jszip.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/pdfmake/build/pdfmake.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/pdfmake/build/vfs_fonts.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-buttons/js/buttons.html5.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-buttons/js/buttons.print.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-buttons/js/buttons.colVis.min.js') }}"></script>
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
    // Auto-submit store filter when changed
    $('select[name="store"]').change(function() {
        $(this).closest('form').submit();
    });

    // Delete functionality
    let productToDelete = null;

    // Show delete confirmation modal
    $('.delete-btn').on('click', function() {
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');

        productToDelete = {
            id: productId,
            name: productName,
            row: $(this).closest('tr')
        };

        $('#deleteProductName').text(productName);
        $('#deleteModal').modal('show');
    });

    // Handle delete confirmation
    $('#confirmDelete').on('click', function() {
        if (!productToDelete) return;

        const $btn = $(this);
        const originalText = $btn.html();

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/ecom-products/' + productToDelete.id,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    // Hide modal
                    $('#deleteModal').modal('hide');

                    // Show success toastr notification
                    toastr.success(response.message, 'Success!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 3000
                    });

                    // Remove the row from the table with animation
                    productToDelete.row.fadeOut(400, function() {
                        $(this).remove();

                        // Check if table is empty
                        if ($('tbody tr').length === 0) {
                            $('tbody').html(`
                                <tr>
                                    <td colspan="4" class="text-center text-muted">
                                        <i class="bx bx-package display-4"></i>
                                        <p class="mt-2">No products found</p>
                                    </td>
                                </tr>
                            `);
                        }
                    });
                } else {
                    toastr.error(response.message, 'Error!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while deleting the product.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                toastr.error(errorMessage, 'Error!', {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 5000
                });
            },
            complete: function() {
                // Reset button state
                $btn.prop('disabled', false).html(originalText);
                productToDelete = null;
            }
        });
    });

    // Reset productToDelete when modal is hidden
    $('#deleteModal').on('hidden.bs.modal', function() {
        productToDelete = null;
    });

    // Status update functionality
    let productToUpdateStatus = null;

    // Show status update modal
    $('.status-btn').on('click', function() {
        const productId = $(this).data('product-id');
        const productName = $(this).data('product-name');
        const rawStatus = $(this).data('current-status');

        // Handle empty or null values
        let currentStatus = 0; // Default to 0 (No)
        if (rawStatus !== null && rawStatus !== undefined && rawStatus !== '') {
            currentStatus = parseInt(rawStatus);
            if (isNaN(currentStatus)) {
                currentStatus = 0; // Default to 0 if parsing fails
            }
        }

        productToUpdateStatus = {
            id: productId,
            name: productName,
            currentStatus: currentStatus,
            row: $(this).closest('tr')
        };

        $('#statusProductName').text(productName);
        $('#statusSelect').val(currentStatus);

        // Debug: Log the current status and selected value
        console.log('Raw Status:', rawStatus);
        console.log('Raw Status Type:', typeof rawStatus);
        console.log('Parsed Status:', currentStatus);
        console.log('Selected Value:', $('#statusSelect').val());

        $('#statusModal').modal('show');
    });

    // Handle status update confirmation
    $('#confirmStatusUpdate').on('click', function() {
        if (!productToUpdateStatus) return;

        const $btn = $(this);
        const originalText = $btn.html();
        const newStatus = $('#statusSelect').val();

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');

        $.ajax({
            url: '/ecom-products/' + productToUpdateStatus.id + '/status',
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
                    toastr.success(response.message, 'Success!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 3000
                    });

                    // Update the status badge in the table
                    const statusCell = productToUpdateStatus.row.find('td:nth-child(3)');
                    if (newStatus == 1) {
                        statusCell.html('<span class="badge bg-success">Yes</span>');
                    } else {
                        statusCell.html('<span class="badge bg-danger">No</span>');
                    }

                    // Update the data attribute on the status button
                    productToUpdateStatus.row.find('.status-btn').data('current-status', newStatus);
                } else {
                    toastr.error(response.message, 'Error!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while updating the product status.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                toastr.error(errorMessage, 'Error!', {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 5000
                });
            },
            complete: function() {
                // Reset button state
                $btn.prop('disabled', false).html(originalText);
                productToUpdateStatus = null;
            }
        });
    });

    // Reset productToUpdateStatus when modal is hidden
    $('#statusModal').on('hidden.bs.modal', function() {
        productToUpdateStatus = null;
    });
});
</script>
@endsection
