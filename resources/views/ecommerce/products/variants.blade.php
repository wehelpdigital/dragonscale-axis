@extends('layouts.master')

@section('title') Product Variants @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
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

.btn-outline-danger.badge-style {
    color: #f46a6a !important;
    border-color: #f46a6a !important;
}

.btn-outline-danger.badge-style:hover {
    background-color: #f46a6a !important;
    color: white !important;
}

.btn-outline-purple.badge-style {
    color: #6f42c1 !important;
    border-color: #6f42c1 !important;
}

.btn-outline-purple.badge-style:hover {
    background-color: #6f42c1 !important;
    color: white !important;
}

/* Toastr positioning override */
#toast-container {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 9999 !important;
}

.toast-top-right {
    top: 20px !important;
    right: 20px !important;
}

</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') Products @endslot
@slot('title') Product Variants @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Success/Error Messages -->
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

                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Product Variants</h4>
                        <p class="card-title-desc">Manage variants for: <strong>{{ $product->productName }}</strong></p>
                    </div>
                    <a href="{{ route('ecom-products') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back to Products
                    </a>
                </div>

                <!-- Add New Variant Button -->
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Product Variants</h5>
                    <a href="{{ route('ecom-products.variants.create', ['id' => $product->id]) }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add New Variant
                    </a>
                </div>

                <!-- Variants Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-striped" id="variants-datatable">
                        <thead class="table-light">
                            <tr>
                                <th>Variant Name</th>
                                <th>Stocks Available</th>
                                <th>Price (₱)</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($variants as $variant)
                                <tr>
                                    <td>{{ $variant->ecomVariantName }}</td>
                                    <td>
                                        <span class="badge bg-{{ $variant->stocksAvailable > 0 ? 'success' : 'danger' }}">
                                            {{ $variant->stocksAvailable }}
                                        </span>
                                    </td>
                                    <td>₱{{ number_format($variant->ecomVariantPrice, 2) }}</td>
                                    <td>
                                        @if($variant->isActive)
                                            <span class="badge bg-success">Yes</span>
                                        @else
                                            <span class="badge bg-danger">No</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex flex-wrap gap-1 justify-content-center">
                                            <a href="{{ route('ecom-products.variants.photos', ['id' => $variant->id]) }}"
                                               class="btn btn-sm btn-outline-info badge-style" title="Photos">
                                                <i class="bx bx-image me-1"></i>Photos
                                            </a>
                                            <a href="{{ route('ecom-products.variants.videos', ['id' => $variant->id]) }}"
                                               class="btn btn-sm btn-outline-warning badge-style" title="Videos">
                                                <i class="bx bx-video me-1"></i>Videos
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-primary badge-style variant-status-btn" title="Status"
                                                    data-variant-id="{{ $variant->id }}"
                                                    data-variant-name="{{ $variant->ecomVariantName }}"
                                                    data-current-status="{{ $variant->isActive ? 1 : 0 }}">
                                                <i class="bx bx-toggle-right me-1"></i>Status
                                            </button>
                                            <button type="button" class="btn btn-sm btn-outline-secondary badge-style variant-stocks-btn" title="Stocks"
                                                    data-variant-id="{{ $variant->id }}"
                                                    data-variant-name="{{ $variant->ecomVariantName }}"
                                                    data-current-stocks="{{ $variant->stocksAvailable }}">
                                                <i class="bx bx-package me-1"></i>Stocks
                                            </button>
                                            <a href="{{ route('ecom-products.variants.edit', ['id' => $variant->id]) }}"
                                               class="btn btn-sm btn-outline-success badge-style" title="Edit">
                                                <i class="bx bx-edit me-1"></i>Edit
                                            </a>
                                            <a href="{{ route('ecom-products.variants.triggers', ['id' => $variant->id]) }}"
                                               class="btn btn-sm btn-outline-purple badge-style" title="Triggers">
                                                <i class="bx bx-bulb me-1"></i>Triggers
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger badge-style delete-variant-btn"
                                                    title="Delete"
                                                    data-variant-id="{{ $variant->id }}"
                                                    data-variant-name="{{ $variant->ecomVariantName }}">
                                                <i class="bx bx-trash me-1"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted">
                                        <i class="bx bx-list-ul display-4"></i>
                                        <p class="mt-2">No variants found for this product</p>
                                        <p class="text-muted">Click "Add New Variant" to create the first variant</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Variant Status Update Modal -->
<div class="modal fade" id="variantStatusModal" tabindex="-1" aria-labelledby="variantStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="variantStatusModalLabel">
                    <i class="bx bx-toggle-right text-primary me-2"></i>Update Variant Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Update the status for the following variant:</p>
                <p class="text-muted mb-3"><strong>Variant:</strong> <span id="variantStatusName"></span></p>

                <div class="mb-3">
                    <label for="variantStatusSelect" class="form-label">Status</label>
                    <select class="form-select" id="variantStatusSelect">
                        <option value="1">Yes</option>
                        <option value="0">No</option>
                    </select>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmVariantStatusUpdate">
                    <i class="bx bx-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Variant Stocks Update Modal -->
<div class="modal fade" id="variantStocksModal" tabindex="-1" aria-labelledby="variantStocksModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="variantStocksModalLabel">
                    <i class="bx bx-package text-secondary me-2"></i>Update Variant Stocks
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Update the stocks for the following variant:</p>
                <p class="text-muted mb-3"><strong>Variant:</strong> <span id="variantStocksName"></span></p>

                <div class="mb-3">
                    <label for="variantStocksInput" class="form-label">Stocks Available</label>
                    <input type="number" class="form-control" id="variantStocksInput" min="0" placeholder="Enter number of stocks">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-primary" id="confirmVariantStocksUpdate">
                    <i class="bx bx-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- DataTables -->
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
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
    // Initialize DataTable for variants
    $('#variants-datatable').DataTable({
        responsive: true,
        pageLength: 10,
        lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "All"]],
        order: [[0, 'asc']], // Sort by Variant Name ascending
        columnDefs: [
            { orderable: false, targets: 4 } // Disable sorting on Actions column
        ],
        language: {
            search: "Search:",
            lengthMenu: "Show _MENU_ entries",
            info: "Showing _START_ to _END_ of _TOTAL_ variants",
            infoEmpty: "Showing 0 to 0 of 0 variants",
            infoFiltered: "(filtered from _MAX_ total variants)",
            paginate: {
                first: '<i class="bx bx-chevrons-left"></i>',
                previous: '<i class="bx bx-chevron-left"></i>',
                next: '<i class="bx bx-chevron-right"></i>',
                last: '<i class="bx bx-chevrons-right"></i>'
            },
            emptyTable: "No variants found for this product"
        }
    });
    // Variant status update functionality
    let variantToUpdateStatus = null;

    // Function to set dropdown value explicitly
    function setDropdownValue(value) {
        const dropdown = $('#variantStatusSelect');
        dropdown.val(value);

        // Force the dropdown to update its display
        if (value == 0) {
            dropdown.find('option[value="0"]').prop('selected', true);
            dropdown.find('option[value="1"]').prop('selected', false);
        } else if (value == 1) {
            dropdown.find('option[value="1"]').prop('selected', true);
            dropdown.find('option[value="0"]').prop('selected', false);
        }

        console.log('Dropdown set to:', value);
        console.log('Option 0 selected:', dropdown.find('option[value="0"]').prop('selected'));
        console.log('Option 1 selected:', dropdown.find('option[value="1"]').prop('selected'));
    }

    // Show variant status update modal
    $('.variant-status-btn').on('click', function() {
        const variantId = $(this).data('variant-id');
        const variantName = $(this).data('variant-name');
        const rawStatus = $(this).data('current-status');

        // Handle empty or null values
        let currentStatus = 0; // Default to 0 (No)
        if (rawStatus !== null && rawStatus !== undefined && rawStatus !== '') {
            currentStatus = parseInt(rawStatus);
            if (isNaN(currentStatus)) {
                currentStatus = 0; // Default to 0 if parsing fails
            }
        }

        variantToUpdateStatus = {
            id: variantId,
            name: variantName,
            currentStatus: currentStatus,
            row: $(this).closest('tr')
        };

        $('#variantStatusName').text(variantName);

        // Set the dropdown value explicitly
        setDropdownValue(currentStatus);

        // Debug: Log the current status and selected value
        console.log('Raw Status:', rawStatus);
        console.log('Raw Status Type:', typeof rawStatus);
        console.log('Parsed Status:', currentStatus);
        console.log('Selected Value:', $('#variantStatusSelect').val());
        console.log('Dropdown Options:', $('#variantStatusSelect option').map(function() { return $(this).val() + ':' + $(this).text(); }).get());

        $('#variantStatusModal').modal('show');
    });

    // Handle variant status update confirmation
    $('#confirmVariantStatusUpdate').on('click', function() {
        if (!variantToUpdateStatus) return;

        const $btn = $(this);
        const originalText = $btn.html();
        const newStatus = $('#variantStatusSelect').val();

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');

        $.ajax({
            url: '/ecom-products-variants/' + variantToUpdateStatus.id + '/status',
            type: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}',
                isActive: newStatus
            },
            success: function(response) {
                if (response.success) {
                    // Hide modal
                    $('#variantStatusModal').modal('hide');

                    // Show success toastr notification
                    toastr.success(response.message, 'Success!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 3000
                    });

                    // Update the status badge in the table
                    const statusCell = variantToUpdateStatus.row.find('td:nth-child(4)');
                    if (newStatus == 1) {
                        statusCell.html('<span class="badge bg-success">Yes</span>');
                    } else {
                        statusCell.html('<span class="badge bg-danger">No</span>');
                    }

                    // Update the data attribute on the status button
                    variantToUpdateStatus.row.find('.variant-status-btn').data('current-status', newStatus);
                } else {
                    toastr.error(response.message, 'Error!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while updating the variant status.';
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
                variantToUpdateStatus = null;
            }
        });
    });

    // Reset variantToUpdateStatus when modal is hidden
    $('#variantStatusModal').on('hidden.bs.modal', function() {
        variantToUpdateStatus = null;
    });

    // Ensure dropdown is set correctly when modal is shown
    $('#variantStatusModal').on('shown.bs.modal', function() {
        if (variantToUpdateStatus) {
            // Use a small delay to ensure the modal is fully rendered
            setTimeout(function() {
                setDropdownValue(variantToUpdateStatus.currentStatus);
                console.log('Modal shown - Setting dropdown to:', variantToUpdateStatus.currentStatus);
                console.log('Final dropdown value:', $('#variantStatusSelect').val());
            }, 100);
        }
    });

    // Variant stocks update functionality
    let variantToUpdateStocks = null;

    // Show variant stocks update modal
    $('.variant-stocks-btn').on('click', function() {
        const variantId = $(this).data('variant-id');
        const variantName = $(this).data('variant-name');
        const currentStocks = parseInt($(this).data('current-stocks')) || 0;

        variantToUpdateStocks = {
            id: variantId,
            name: variantName,
            currentStocks: currentStocks,
            row: $(this).closest('tr')
        };

        $('#variantStocksName').text(variantName);
        $('#variantStocksInput').val(currentStocks);

        // Debug: Log the current stocks
        console.log('Current Stocks:', currentStocks);

        $('#variantStocksModal').modal('show');
    });

    // Handle variant stocks update confirmation
    $('#confirmVariantStocksUpdate').on('click', function() {
        if (!variantToUpdateStocks) return;

        const $btn = $(this);
        const originalText = $btn.html();
        const newStocks = parseInt($('#variantStocksInput').val()) || 0;

        // Validate input
        if (newStocks < 0) {
            toastr.error('Stocks cannot be negative.', 'Error!', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000
            });
            return;
        }

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...');

        $.ajax({
            url: '/ecom-products-variants/' + variantToUpdateStocks.id + '/stocks',
            type: 'PATCH',
            data: {
                _token: '{{ csrf_token() }}',
                stocksAvailable: newStocks
            },
            success: function(response) {
                if (response.success) {
                    // Hide modal
                    $('#variantStocksModal').modal('hide');

                    // Show success toastr notification
                    toastr.success(response.message, 'Success!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 3000
                    });

                    // Update the stocks badge in the table
                    const stocksCell = variantToUpdateStocks.row.find('td:nth-child(2)');
                    const badgeClass = newStocks > 0 ? 'bg-success' : 'bg-danger';
                    stocksCell.html(`<span class="badge ${badgeClass}">${newStocks}</span>`);

                    // Update the data attribute on the stocks button
                    variantToUpdateStocks.row.find('.variant-stocks-btn').data('current-stocks', newStocks);
                } else {
                    toastr.error(response.message, 'Error!', {
                        closeButton: true,
                        progressBar: true,
                        timeOut: 5000
                    });
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while updating the variant stocks.';
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
                variantToUpdateStocks = null;
            }
        });
    });

    // Reset variantToUpdateStocks when modal is hidden
    $('#variantStocksModal').on('hidden.bs.modal', function() {
        variantToUpdateStocks = null;
    });
});
</script>
@endsection
