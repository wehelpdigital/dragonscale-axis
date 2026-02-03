@extends('layouts.master')

@section('title') Client Shippings @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
    .address-row {
        transition: background-color 0.15s ease;
    }
    .address-row:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.05);
    }
    .pagination-info {
        font-size: 13px;
    }
    .page-link {
        padding: 0.375rem 0.75rem;
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
    .filter-section {
        background: #f8f9fa;
        padding: 1rem;
        border-radius: 0.25rem;
        margin-bottom: 1rem;
    }
    .detail-label {
        font-weight: 600;
        color: #495057;
        font-size: 0.85rem;
    }
    .detail-value {
        color: #212529;
    }
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') E-commerce @endslot
        @slot('title') Client Shippings @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <!-- Filter Section -->
                    <div class="filter-section">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label text-dark fw-medium">Search</label>
                                <input type="text" class="form-control" id="searchInput"
                                       placeholder="Search name, phone, address...">
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-dark fw-medium">Province</label>
                                <select class="form-select" id="filterProvince">
                                    <option value="">All Provinces</option>
                                    @foreach($provinces as $province)
                                        <option value="{{ $province }}">{{ $province }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label text-dark fw-medium">Municipality</label>
                                <input type="text" class="form-control" id="filterMunicipality"
                                       placeholder="Filter municipality...">
                            </div>
                        </div>
                        <div class="row mt-3">
                            <div class="col-12 text-end">
                                <button type="button" class="btn btn-secondary me-2" id="clearFilters">
                                    <i class="bx bx-reset me-1"></i>Clear Filters
                                </button>
                                <button type="button" class="btn btn-success" id="addShippingBtn">
                                    <i class="bx bx-plus me-1"></i>Add New Address
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Stats Bar -->
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div class="pagination-info text-secondary">
                            Showing <span id="showingFrom">0</span> to <span id="showingTo">0</span>
                            of <span id="totalCount">0</span> addresses
                            <span id="filteredInfo"></span>
                        </div>
                        <div>
                            <select class="form-select form-select-sm" id="perPage" style="width: auto;">
                                <option value="15">15 per page</option>
                                <option value="30">30 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                            </select>
                        </div>
                    </div>

                    <!-- Table -->
                    <div class="table-container">
                        <div id="loadingOverlay" class="loading-overlay">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-dark">Recipient</th>
                                        <th class="text-dark">Address</th>
                                        <th class="text-dark">Contact</th>
                                        <th class="text-dark">Added</th>
                                        <th class="text-dark text-center" style="width: 80px;">Action</th>
                                    </tr>
                                </thead>
                                <tbody id="addressTableBody">
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination -->
                    <div class="d-flex justify-content-center mt-3">
                        <nav aria-label="Page navigation">
                            <ul class="pagination mb-0" id="pagination">
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Shipping Modal -->
    <div class="modal fade" id="addShippingModal" tabindex="-1" aria-labelledby="addShippingModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title" id="addShippingModalLabel">
                        <i class="bx bx-map-pin me-2"></i>Add Shipping Address
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="addShippingForm">
                        <h6 class="text-dark mb-3"><i class="bx bx-user me-1"></i> Recipient Details</h6>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="firstName" name="firstName" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="middleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="middleName" name="middleName">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="lastName" name="lastName" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="phoneNumber" name="phoneNumber"
                                       placeholder="09123456789" maxlength="11" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="emailAddress" class="form-label">Email Address</label>
                                <input type="email" class="form-control" id="emailAddress" name="emailAddress"
                                       placeholder="recipient@example.com">
                            </div>
                        </div>

                        <hr class="my-3">
                        <h6 class="text-dark mb-3"><i class="bx bx-home me-1"></i> Address Details</h6>

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="addressLabel" class="form-label">Address Label</label>
                                <input type="text" class="form-control" id="addressLabel" name="addressLabel"
                                       placeholder="e.g., Home, Office">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="houseNumber" class="form-label">House/Unit No.</label>
                                <input type="text" class="form-control" id="houseNumber" name="houseNumber">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="street" class="form-label">Street/Barangay</label>
                                <input type="text" class="form-control" id="street" name="street">
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-3 mb-3">
                                <label for="zone" class="form-label">Zone/Purok</label>
                                <input type="text" class="form-control" id="zone" name="zone">
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="municipality" class="form-label">Municipality/City <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="municipality" name="municipality" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="province" class="form-label">Province <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="province" name="province" required>
                            </div>
                            <div class="col-md-3 mb-3">
                                <label for="zipCode" class="form-label">Zip Code</label>
                                <input type="text" class="form-control" id="zipCode" name="zipCode" maxlength="10">
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                        <i class="bx bx-x me-1"></i>Cancel
                    </button>
                    <button type="button" class="btn btn-success" id="saveShippingBtn">
                        <i class="bx bx-save me-1"></i>Save Address
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- View Details Modal -->
    <div class="modal fade" id="viewDetailsModal" tabindex="-1" aria-labelledby="viewDetailsModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header bg-info text-white">
                    <h5 class="modal-title" id="viewDetailsModalLabel">
                        <i class="bx bx-map-pin me-2"></i>Shipping Address Details
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <!-- Recipient Section -->
                    <h6 class="text-primary mb-3"><i class="bx bx-user me-1"></i> Recipient</h6>
                    <div class="row mb-3">
                        <div class="col-4">
                            <span class="detail-label">Full Name</span>
                        </div>
                        <div class="col-8">
                            <span class="detail-value" id="viewRecipientName">-</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <span class="detail-label">Phone</span>
                        </div>
                        <div class="col-8">
                            <span class="detail-value" id="viewPhone">-</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <span class="detail-label">Email</span>
                        </div>
                        <div class="col-8">
                            <span class="detail-value" id="viewEmail">-</span>
                        </div>
                    </div>

                    <hr>

                    <!-- Address Section -->
                    <h6 class="text-primary mb-3"><i class="bx bx-home me-1"></i> Address</h6>
                    <div class="row mb-3" id="viewLabelRow" style="display: none;">
                        <div class="col-4">
                            <span class="detail-label">Label</span>
                        </div>
                        <div class="col-8">
                            <span class="badge bg-info text-white" id="viewAddressLabel">-</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <span class="detail-label">House/Unit</span>
                        </div>
                        <div class="col-8">
                            <span class="detail-value" id="viewHouseNumber">-</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <span class="detail-label">Street/Brgy</span>
                        </div>
                        <div class="col-8">
                            <span class="detail-value" id="viewStreet">-</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <span class="detail-label">Zone/Purok</span>
                        </div>
                        <div class="col-8">
                            <span class="detail-value" id="viewZone">-</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <span class="detail-label">Municipality</span>
                        </div>
                        <div class="col-8">
                            <span class="detail-value" id="viewMunicipality">-</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <span class="detail-label">Province</span>
                        </div>
                        <div class="col-8">
                            <span class="detail-value" id="viewProvince">-</span>
                        </div>
                    </div>
                    <div class="row mb-3">
                        <div class="col-4">
                            <span class="detail-label">Zip Code</span>
                        </div>
                        <div class="col-8">
                            <span class="detail-value" id="viewZipCode">-</span>
                        </div>
                    </div>

                    <hr>

                    <div class="row">
                        <div class="col-4">
                            <span class="detail-label">Date Added</span>
                        </div>
                        <div class="col-8">
                            <span class="detail-value text-secondary" id="viewCreatedAt">-</span>
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
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    let addShippingModal = new bootstrap.Modal(document.getElementById('addShippingModal'));
    let viewDetailsModal = new bootstrap.Modal(document.getElementById('viewDetailsModal'));
    let searchTimeout = null;
    let currentPage = 1;
    let perPage = 15;
    let addressesData = []; // Store addresses for view details

    // Toastr options
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // Load initial data
    loadAddresses();

    // Search with debounce
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadAddresses();
        }, 300);
    });

    // Filter change handlers
    $('#filterProvince').on('change', function() {
        currentPage = 1;
        loadAddresses();
    });

    $('#filterMunicipality').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            currentPage = 1;
            loadAddresses();
        }, 300);
    });

    // Per page change
    $('#perPage').on('change', function() {
        perPage = parseInt($(this).val());
        currentPage = 1;
        loadAddresses();
    });

    // Clear filters
    $('#clearFilters').on('click', function() {
        $('#searchInput').val('');
        $('#filterProvince').val('');
        $('#filterMunicipality').val('');
        currentPage = 1;
        loadAddresses();
    });

    // Load addresses function
    function loadAddresses() {
        showLoading(true);

        $.ajax({
            url: '{{ route("ecom-client-shippings.data") }}',
            type: 'GET',
            data: {
                search: $('#searchInput').val(),
                province: $('#filterProvince').val(),
                municipality: $('#filterMunicipality').val(),
                page: currentPage,
                per_page: perPage
            },
            success: function(response) {
                if (response.success) {
                    addressesData = response.data; // Store for view details
                    renderTable(response.data);
                    renderPagination(response.pagination);
                    updateStats(response);
                } else {
                    toastr.error(response.message || 'Failed to load addresses', 'Error');
                }
            },
            error: function(xhr) {
                toastr.error('An error occurred while loading addresses', 'Error');
                console.error('Error:', xhr);
            },
            complete: function() {
                showLoading(false);
            }
        });
    }

    // Render table
    function renderTable(data) {
        const tbody = $('#addressTableBody');
        tbody.empty();

        if (data.length === 0) {
            tbody.html(`
                <tr>
                    <td colspan="5" class="text-center py-4">
                        <i class="bx bx-map-pin text-secondary" style="font-size: 3rem;"></i>
                        <p class="text-dark mt-2 mb-0">No shipping addresses found.</p>
                        <small class="text-secondary">Click "Add" to create a new shipping address.</small>
                    </td>
                </tr>
            `);
            return;
        }

        data.forEach(function(address, index) {
            const labelBadge = address.addressLabel
                ? `<span class="badge bg-info text-white me-1">${escapeHtml(address.addressLabel)}</span>`
                : '';

            tbody.append(`
                <tr class="address-row">
                    <td>
                        <strong class="text-dark">${escapeHtml(address.recipientName)}</strong>
                    </td>
                    <td>
                        ${labelBadge}
                        <span class="text-dark">${escapeHtml(address.fullAddress)}</span>
                    </td>
                    <td>
                        ${address.phoneNumber ? `<a href="tel:${address.phoneNumber}" class="text-primary">${escapeHtml(address.phoneNumber)}</a>` : '-'}
                        ${address.emailAddress ? `<br><small><a href="mailto:${address.emailAddress}">${escapeHtml(address.emailAddress)}</a></small>` : ''}
                    </td>
                    <td><small class="text-secondary">${escapeHtml(address.createdAt)}</small></td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-info view-details-btn" data-id="${address.id}" title="View Details">
                            <i class="bx bx-show"></i>
                        </button>
                    </td>
                </tr>
            `);
        });
    }

    // View details button click
    $(document).on('click', '.view-details-btn', function() {
        const addressId = $(this).data('id');
        const address = addressesData.find(a => a.id === addressId);

        if (address) {
            // Populate modal
            $('#viewRecipientName').text(address.recipientName || '-');
            $('#viewPhone').html(address.phoneNumber ? `<a href="tel:${address.phoneNumber}">${escapeHtml(address.phoneNumber)}</a>` : '-');
            $('#viewEmail').html(address.emailAddress ? `<a href="mailto:${address.emailAddress}">${escapeHtml(address.emailAddress)}</a>` : '-');

            if (address.addressLabel) {
                $('#viewLabelRow').show();
                $('#viewAddressLabel').text(address.addressLabel);
            } else {
                $('#viewLabelRow').hide();
            }

            $('#viewHouseNumber').text(address.houseNumber || '-');
            $('#viewStreet').text(address.street || '-');
            $('#viewZone').text(address.zone || '-');
            $('#viewMunicipality').text(address.municipality || '-');
            $('#viewProvince').text(address.province || '-');
            $('#viewZipCode').text(address.zipCode || '-');
            $('#viewCreatedAt').text(address.createdAt || '-');

            viewDetailsModal.show();
        }
    });

    // Render pagination
    function renderPagination(pagination) {
        const paginationEl = $('#pagination');
        paginationEl.empty();

        if (pagination.last_page <= 1) return;

        // Previous button
        paginationEl.append(`
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}">&laquo;</a>
            </li>
        `);

        // Page numbers
        let startPage = Math.max(1, pagination.current_page - 2);
        let endPage = Math.min(pagination.last_page, pagination.current_page + 2);

        if (startPage > 1) {
            paginationEl.append(`<li class="page-item"><a class="page-link" href="#" data-page="1">1</a></li>`);
            if (startPage > 2) {
                paginationEl.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            paginationEl.append(`
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        if (endPage < pagination.last_page) {
            if (endPage < pagination.last_page - 1) {
                paginationEl.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
            }
            paginationEl.append(`<li class="page-item"><a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a></li>`);
        }

        // Next button
        paginationEl.append(`
            <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}">&raquo;</a>
            </li>
        `);
    }

    // Pagination click handler
    $(document).on('click', '#pagination .page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
            currentPage = page;
            loadAddresses();
        }
    });

    // Update stats
    function updateStats(response) {
        const pagination = response.pagination;
        $('#showingFrom').text(pagination.from || 0);
        $('#showingTo').text(pagination.to || 0);
        $('#totalCount').text(response.total_count || 0);

        if (response.filtered_count < response.total_count) {
            $('#filteredInfo').text(` (filtered from ${response.total_count})`);
        } else {
            $('#filteredInfo').text('');
        }
    }

    // Show/hide loading
    function showLoading(show) {
        if (show) {
            $('#loadingOverlay').show();
        } else {
            $('#loadingOverlay').hide();
        }
    }

    // Escape HTML
    function escapeHtml(text) {
        if (!text) return '';
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Open add shipping modal
    $('#addShippingBtn').on('click', function() {
        resetForm();
        addShippingModal.show();
    });

    // Reset form
    function resetForm() {
        $('#addShippingForm')[0].reset();
    }

    // Reset form on modal close
    $('#addShippingModal').on('hidden.bs.modal', function() {
        resetForm();
    });

    // Save shipping address
    $('#saveShippingBtn').on('click', function() {
        const $btn = $(this);

        // Validate required fields
        if (!$('#firstName').val().trim()) {
            toastr.error('First name is required', 'Validation Error');
            $('#firstName').focus();
            return;
        }
        if (!$('#lastName').val().trim()) {
            toastr.error('Last name is required', 'Validation Error');
            $('#lastName').focus();
            return;
        }
        if (!$('#phoneNumber').val().trim()) {
            toastr.error('Phone number is required', 'Validation Error');
            $('#phoneNumber').focus();
            return;
        }
        if (!$('#municipality').val().trim()) {
            toastr.error('Municipality/City is required', 'Validation Error');
            $('#municipality').focus();
            return;
        }
        if (!$('#province').val().trim()) {
            toastr.error('Province is required', 'Validation Error');
            $('#province').focus();
            return;
        }

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        const formData = {
            _token: '{{ csrf_token() }}',
            addressLabel: $('#addressLabel').val(),
            firstName: $('#firstName').val(),
            middleName: $('#middleName').val(),
            lastName: $('#lastName').val(),
            phoneNumber: $('#phoneNumber').val(),
            emailAddress: $('#emailAddress').val(),
            houseNumber: $('#houseNumber').val(),
            street: $('#street').val(),
            zone: $('#zone').val(),
            municipality: $('#municipality').val(),
            province: $('#province').val(),
            zipCode: $('#zipCode').val()
        };

        $.ajax({
            url: '{{ route("ecom-client-shippings.store") }}',
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message, 'Success!');
                    addShippingModal.hide();
                    loadAddresses();
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Address');
            }
        });
    });
});
</script>
@endsection
