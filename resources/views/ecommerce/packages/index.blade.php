@extends('layouts.master')

@section('title') Packages @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.badge-style {
    font-size: 0.75rem;
    padding: 0.35rem 0.65rem;
}
.filter-section {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 0.25rem;
    margin-bottom: 1rem;
}
.package-item-img {
    width: 40px;
    height: 40px;
    object-fit: cover;
    border-radius: 4px;
}
.package-item-placeholder {
    width: 40px;
    height: 40px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f0f0f0;
    border-radius: 4px;
    color: #adb5bd;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('title') Packages @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Filter Section -->
                <div class="filter-section">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label text-dark fw-medium">Package Name</label>
                            <input type="text" class="form-control" id="filterPackageName" placeholder="Search package...">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label text-dark fw-medium">Status</label>
                            <select class="form-select" id="filterPackageStatus">
                                <option value="">All Statuses</option>
                                <option value="active">Active</option>
                                <option value="inactive">Inactive</option>
                            </select>
                        </div>
                        <div class="col-md-5 d-flex align-items-end">
                            <button type="button" class="btn btn-secondary me-2" id="clearFilters">
                                <i class="bx bx-reset me-1"></i>Clear
                            </button>
                            <a href="{{ route('ecom-packages.create') }}" class="btn btn-primary ms-auto">
                                <i class="bx bx-plus me-1"></i>Add New Package
                            </a>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table align-middle table-nowrap dt-responsive nowrap w-100" id="packages-table">
                        <thead class="table-light">
                            <tr>
                                <th>Package Name</th>
                                <th>Items</th>
                                <th>Calculated Price</th>
                                <th>Package Price</th>
                                <th>Discount</th>
                                <th>Status</th>
                                <th>Created</th>
                                <th>Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            <!-- Data will be loaded via AJAX -->
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- View Package Details Modal -->
<div class="modal fade" id="viewPackageModal" tabindex="-1" aria-labelledby="viewPackageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="viewPackageModalLabel">
                    <i class="bx bx-package me-2"></i>Package Details
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" id="viewPackageBody">
                <div class="text-center py-4">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <p class="mt-2 text-secondary">Loading package details...</p>
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
                <p class="text-dark">Are you sure you want to delete this package?</p>
                <p class="text-muted mb-0"><strong>Package:</strong> <span id="deletePackageName"></span></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bx bx-trash me-1"></i>Delete
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
$(document).ready(function() {
    // Toastr options
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Initialize DataTable
    var table = $('#packages-table').DataTable({
        processing: true,
        serverSide: true,
        ajax: {
            url: "{{ route('ecom-packages.data') }}",
            type: "GET",
            data: function(d) {
                d.packageName = $('#filterPackageName').val();
                d.packageStatus = $('#filterPackageStatus').val();
            },
            error: function(xhr, error, thrown) {
                console.error('DataTables AJAX Error:', error);
                toastr.error('Failed to load packages', 'Error');
            }
        },
        columns: [
            { data: 'packageName', name: 'packageName' },
            {
                data: 'itemCount',
                name: 'itemCount',
                render: function(data) {
                    return '<span class="badge bg-info">' + data + ' item(s)</span>';
                }
            },
            { data: 'formatted_calculated_price', name: 'calculatedPrice' },
            {
                data: 'formatted_package_price',
                name: 'packagePrice',
                render: function(data) {
                    return '<strong class="text-primary">' + data + '</strong>';
                }
            },
            { data: 'discount_info', name: 'discount_info' },
            { data: 'statusBadge', name: 'packageStatus' },
            { data: 'formatted_date', name: 'created_at' },
            {
                data: 'id',
                name: 'action',
                orderable: false,
                searchable: false,
                render: function(data, type, row) {
                    return `
                        <div class="d-flex flex-wrap gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary badge-style view-btn"
                                    data-id="${row.id}"
                                    data-name="${escapeHtml(row.packageName)}"
                                    title="View Details">
                                <i class="bx bx-show me-1"></i>View
                            </button>
                            <a href="/ecom-packages-edit?id=${row.id}" class="btn btn-sm btn-outline-success badge-style" title="Edit">
                                <i class="bx bx-edit me-1"></i>Edit
                            </a>
                            <button type="button" class="btn btn-sm btn-outline-info badge-style status-btn"
                                    data-id="${row.id}"
                                    data-name="${escapeHtml(row.packageName)}"
                                    data-status="${row.packageStatus}"
                                    title="Toggle Status">
                                <i class="bx bx-toggle-right me-1"></i>Status
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger badge-style delete-btn"
                                    data-id="${row.id}"
                                    data-name="${escapeHtml(row.packageName)}"
                                    title="Delete">
                                <i class="bx bx-trash me-1"></i>Delete
                            </button>
                        </div>
                    `;
                }
            }
        ],
        order: [[6, 'desc']],
        pageLength: 25,
        lengthMenu: [[25, 50, 100, -1], [25, 50, 100, "All"]],
        responsive: true,
        language: {
            emptyTable: "No packages found",
            zeroRecords: "No matching packages found"
        }
    });

    // Filter handlers
    $('#filterPackageName').on('keyup', function() {
        table.draw();
    });

    $('#filterPackageStatus').on('change', function() {
        table.draw();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#filterPackageName').val('');
        $('#filterPackageStatus').val('');
        table.draw();
    });

    // View package details
    $(document).on('click', '.view-btn', function() {
        const packageId = $(this).data('id');
        const packageName = $(this).data('name');

        $('#viewPackageModalLabel').html('<i class="bx bx-package me-2"></i>Package: ' + packageName);
        $('#viewPackageBody').html(`
            <div class="text-center py-4">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-secondary">Loading package details...</p>
            </div>
        `);
        $('#viewPackageModal').modal('show');

        $.ajax({
            url: '/ecom-packages/' + packageId + '/details',
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    renderPackageDetails(response.package);
                } else {
                    $('#viewPackageBody').html('<div class="alert alert-danger">Failed to load package details.</div>');
                }
            },
            error: function(xhr) {
                $('#viewPackageBody').html('<div class="alert alert-danger">Error loading package details.</div>');
            }
        });
    });

    // Render package details
    function renderPackageDetails(pkg) {
        let itemsHtml = '';
        if (pkg.items && pkg.items.length > 0) {
            pkg.items.forEach(function(item) {
                const imgHtml = item.imageUrl
                    ? `<img src="${item.imageUrl}" alt="${escapeHtml(item.variantName)}" class="package-item-img">`
                    : `<div class="package-item-placeholder"><i class="bx bx-image"></i></div>`;

                itemsHtml += `
                    <tr>
                        <td>${imgHtml}</td>
                        <td class="text-dark">
                            <strong>${escapeHtml(item.productName)}</strong><br>
                            <small class="text-secondary">${escapeHtml(item.variantName)}</small>
                        </td>
                        <td class="text-dark">${escapeHtml(item.storeName || 'N/A')}</td>
                        <td class="text-center text-dark">${item.quantity}</td>
                        <td class="text-end text-dark">₱${parseFloat(item.unitPrice).toFixed(2)}</td>
                        <td class="text-end text-dark">₱${parseFloat(item.subtotal).toFixed(2)}</td>
                    </tr>
                `;
            });
        }

        const discount = pkg.calculatedPrice - pkg.packagePrice;
        const discountPercentage = pkg.calculatedPrice > 0 ? ((discount / pkg.calculatedPrice) * 100).toFixed(1) : 0;

        const html = `
            <div class="row mb-4">
                <div class="col-md-6">
                    <h6 class="text-dark fw-bold mb-3"><i class="bx bx-info-circle me-2"></i>Package Information</h6>
                    <p class="mb-2"><strong>Name:</strong> ${escapeHtml(pkg.packageName)}</p>
                    <p class="mb-2"><strong>Description:</strong> ${pkg.packageDescription ? escapeHtml(pkg.packageDescription) : '<span class="text-secondary">No description</span>'}</p>
                    <p class="mb-2"><strong>Status:</strong>
                        <span class="badge ${pkg.packageStatus === 'active' ? 'bg-success' : 'bg-secondary'}">${pkg.packageStatus}</span>
                    </p>
                    <p class="mb-0"><strong>Created:</strong> ${pkg.createdAt}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-dark fw-bold mb-3"><i class="bx bx-calculator me-2"></i>Pricing</h6>
                    <div class="bg-light p-3 rounded">
                        <div class="row mb-2">
                            <div class="col-6 text-dark">Calculated Total:</div>
                            <div class="col-6 text-end text-dark">₱${parseFloat(pkg.calculatedPrice).toFixed(2)}</div>
                        </div>
                        ${discount > 0 ? `
                        <div class="row mb-2">
                            <div class="col-6 text-dark">Discount:</div>
                            <div class="col-6 text-end text-success">-₱${discount.toFixed(2)} (${discountPercentage}%)</div>
                        </div>
                        ` : ''}
                        <hr class="my-2">
                        <div class="row">
                            <div class="col-6"><strong class="text-dark">Package Price:</strong></div>
                            <div class="col-6 text-end"><strong class="text-primary fs-5">₱${parseFloat(pkg.packagePrice).toFixed(2)}</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <h6 class="text-dark fw-bold mb-3"><i class="bx bx-list-ul me-2"></i>Package Items (${pkg.items ? pkg.items.length : 0})</h6>
            <div class="table-responsive">
                <table class="table table-bordered table-sm">
                    <thead class="table-light">
                        <tr>
                            <th style="width: 50px;"></th>
                            <th class="text-dark">Product / Variant</th>
                            <th class="text-dark">Store</th>
                            <th class="text-center text-dark">Qty</th>
                            <th class="text-end text-dark">Unit Price</th>
                            <th class="text-end text-dark">Subtotal</th>
                        </tr>
                    </thead>
                    <tbody>
                        ${itemsHtml || '<tr><td colspan="6" class="text-center text-secondary">No items</td></tr>'}
                    </tbody>
                </table>
            </div>
        `;

        $('#viewPackageBody').html(html);
    }

    // Toggle status
    $(document).on('click', '.status-btn', function() {
        const packageId = $(this).data('id');
        const packageName = $(this).data('name');
        const currentStatus = $(this).data('status');
        const newStatus = currentStatus === 'active' ? 'inactive' : 'active';

        if (confirm(`Change status of "${packageName}" from ${currentStatus} to ${newStatus}?`)) {
            $.ajax({
                url: '/ecom-packages/' + packageId + '/toggle-status',
                type: 'PATCH',
                data: { _token: '{{ csrf_token() }}' },
                success: function(response) {
                    if (response.success) {
                        toastr.success(response.message, 'Success');
                        table.ajax.reload(null, false);
                    } else {
                        toastr.error(response.message, 'Error');
                    }
                },
                error: function() {
                    toastr.error('Failed to update status', 'Error');
                }
            });
        }
    });

    // Delete package
    let packageToDelete = null;

    $(document).on('click', '.delete-btn', function() {
        packageToDelete = {
            id: $(this).data('id'),
            name: $(this).data('name')
        };
        $('#deletePackageName').text(packageToDelete.name);
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        if (!packageToDelete) return;

        const $btn = $(this);
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        $.ajax({
            url: '/ecom-packages/' + packageToDelete.id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    toastr.success(response.message, 'Success');
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(response.message, 'Error');
                }
            },
            error: function() {
                toastr.error('Failed to delete package', 'Error');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
                packageToDelete = null;
            }
        });
    });

    // Escape HTML helper
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }
});
</script>
@endsection
