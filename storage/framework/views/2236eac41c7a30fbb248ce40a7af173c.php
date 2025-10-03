<?php $__env->startSection('title'); ?> Shipping Settings <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<!-- Toastr -->
<link href="<?php echo e(URL::asset('build/libs/toastr/build/toastr.min.css')); ?>" rel="stylesheet" type="text/css" />

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

.btn-outline-success.badge-style {
    color: #198754 !important;
    border-color: #198754 !important;
}

.btn-outline-success.badge-style:hover {
    background-color: #198754 !important;
    color: white !important;
}

.btn-outline-danger.badge-style {
    color: #dc3545 !important;
    border-color: #dc3545 !important;
}

.btn-outline-danger.badge-style:hover {
    background-color: #dc3545 !important;
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
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> E-commerce <?php $__env->endSlot(); ?>
<?php $__env->slot('li_2'); ?> <a href="<?php echo e(route('ecom-shipping')); ?>">Shipping Methods</a> <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Shipping Settings <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Shipping Settings - <?php echo e($shipping->shippingName); ?></h4>
                    <div class="d-flex gap-2">
                        <button class="btn btn-primary" id="addTargetProvinceBtn">
                            <i class="bx bx-plus"></i> Add New Target Province
                        </button>
                        <a href="<?php echo e(route('ecom-shipping')); ?>" class="btn btn-secondary">
                            <i class="bx bx-arrow-back"></i> Back to Shipping Methods
                </a>
            </div>
                </div>
                <p class="card-title-desc">Configure and manage shipping method settings for your e-commerce store.</p>

                <!-- Search Section -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="d-flex">
                            <input type="text" class="form-control me-2" id="searchInput" placeholder="Search by province, quantity, or price...">
                            <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                <i class="bx bx-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <button class="btn btn-outline-danger" id="clearFilters">
                            <i class="bx bx-x"></i> Clear Search
                        </button>
                    </div>
                </div>

                <!-- Shipping Options Table -->
                <div class="table-responsive position-relative">
                    <!-- Loading Overlay -->
                    <div id="tableLoadingOverlay" class="loading-overlay" style="display: none;">
                        <div class="loading-spinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="loading-text mt-2">Loading shipping options...</div>
                        </div>
            </div>

                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Province Target</th>
                                <th>Max Order Quantity</th>
                                <th>Shipping Price</th>
                                <th>Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="shippingOptionsTableBody">
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
            </div>

                <!-- Pagination -->
                <div class="row mt-3">
                    <div class="col-sm-12 col-md-5">
                        <div class="dataTables_info" id="shippingOptionsTable_info" role="status" aria-live="polite">
                            Showing 0 to 0 of 0 entries
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-7">
                        <div class="dataTables_paginate paging_simple_numbers" id="shippingOptionsTable_paginate">
                            <ul class="pagination justify-content-end" id="paginationContainer">
                                <!-- Pagination will be generated dynamically -->
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Target Province Modal -->
<div class="modal fade" id="addTargetProvinceModal" tabindex="-1" role="dialog" aria-labelledby="addTargetProvinceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addTargetProvinceModalLabel">Add New Target Province</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addTargetProvinceForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="targetProvince">Target Province <span class="text-danger">*</span></label>
                        <select class="form-select" id="targetProvince" name="targetProvince">
                            <option value="">Select a province</option>
                            <?php if(count($availableProvinces) > 0): ?>
                                <?php $__currentLoopData = $availableProvinces; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $province): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                    <option value="<?php echo e($province); ?>"><?php echo e($province); ?></option>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            <?php else: ?>
                                <option value="" disabled>All provinces have been added</option>
                            <?php endif; ?>
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="maxOrderQuantity">Max Order Quantity <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="maxOrderQuantity" name="maxOrderQuantity" value="1" placeholder="Enter max order quantity">
                    </div>
                    <div class="form-group mb-3">
                        <label for="shippingPrice">Shipping Price (PHP) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="shippingPrice" name="shippingPrice" placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Target Province</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Edit Target Province Modal -->
<div class="modal fade" id="editTargetProvinceModal" tabindex="-1" role="dialog" aria-labelledby="editTargetProvinceModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editTargetProvinceModalLabel">Edit Target Province</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editTargetProvinceForm">
                <div class="modal-body">
                    <input type="hidden" id="editOptionId" name="optionId">
                    <div class="form-group mb-3">
                        <label for="editTargetProvince">Target Province <span class="text-danger">*</span></label>
                        <select class="form-select" id="editTargetProvince" name="editTargetProvince">
                            <option value="">Select a province</option>
                            <!-- Options will be populated dynamically via JavaScript -->
                        </select>
                    </div>
                    <div class="form-group mb-3">
                        <label for="editMaxOrderQuantity">Max Order Quantity <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editMaxOrderQuantity" name="editMaxOrderQuantity" placeholder="Enter max order quantity">
                    </div>
                    <div class="form-group mb-3">
                        <label for="editShippingPrice">Shipping Price (PHP) <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editShippingPrice" name="editShippingPrice" placeholder="0.00">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Target Province</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<!-- Toastr -->
<script src="<?php echo e(URL::asset('build/libs/toastr/build/toastr.min.js')); ?>"></script>

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

// Global variables for pagination and search
let currentPage = 1;
let perPage = 10;
let searchTerm = '';
let totalRecords = 0;
let lastPage = 1;
let shippingId = <?php echo e($shipping->id); ?>;

$(document).ready(function() {
    // Initialize the page
    loadShippingOptionsData();

    // Dynamic search functionality
    let searchTimeout;

    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchTerm = $('#searchInput').val();
            currentPage = 1;
            loadShippingOptionsData();
        }, 300); // 300ms delay to avoid too many requests
    });

    // Search button (optional - for manual trigger)
    $('#searchBtn').on('click', function() {
        clearTimeout(searchTimeout);
        searchTerm = $('#searchInput').val();
        currentPage = 1;
        loadShippingOptionsData();
    });

    // Clear search
    $('#clearFilters').on('click', function() {
        $('#searchInput').val('');
        searchTerm = '';
        currentPage = 1;
        loadShippingOptionsData();
    });

    // Add New Target Province button
    $('#addTargetProvinceBtn').on('click', function() {
        // Clear form and validation when opening modal
        clearAddForm();
        $('#addTargetProvinceModal').modal('show');
    });

    // Edit button click handler
    $(document).on('click', '.edit-btn', function() {
        const optionId = $(this).data('option-id');
        loadShippingOptionForEdit(optionId);
    });

    // Status button click handler
    $(document).on('click', '.status-btn', function() {
        const optionId = $(this).data('option-id');
        const currentStatus = $(this).data('status');
        loadShippingOptionForStatusChange(optionId, currentStatus);
    });

    // Delete button click handler
    $(document).on('click', '.delete-btn', function() {
        const optionId = $(this).data('option-id');
        const provinceName = $(this).data('province-target');
        showDeleteConfirmation(optionId, provinceName);
    });

    // Confirm delete button click handler
    $('#confirmDeleteBtn').on('click', function() {
        const optionId = $('#deleteConfirmationModal').data('option-id');
        deleteShippingOption(optionId);
    });

    // Real-time validation for form fields
    $('#targetProvince').on('change blur', function() {
        validateField('targetProvince', $(this).val(), 'Target province is required');
    });

    $('#maxOrderQuantity').on('input blur', function() {
        const value = $(this).val().trim();
        if (value === '') {
            showFieldError('maxOrderQuantity', 'Max order quantity is required');
        } else if (!/^\d+$/.test(value)) {
            showFieldError('maxOrderQuantity', 'Max order quantity must be a valid positive number');
        } else if (parseInt(value) < 1) {
            showFieldError('maxOrderQuantity', 'Max order quantity must be at least 1');
        } else {
            clearFieldError('maxOrderQuantity');
        }
    });

    $('#shippingPrice').on('input blur', function() {
        const value = $(this).val().trim();
        if (value === '') {
            showFieldError('shippingPrice', 'Shipping price is required');
        } else if (!/^\d+(\.\d{1,2})?$/.test(value)) {
            showFieldError('shippingPrice', 'Shipping price must be a valid number (e.g., 100 or 100.50)');
        } else if (parseFloat(value) < 0) {
            showFieldError('shippingPrice', 'Shipping price cannot be negative');
        } else {
            clearFieldError('shippingPrice');
        }
    });

    // Real-time validation for edit form fields
    $('#editTargetProvince').on('change blur', function() {
        validateField('editTargetProvince', $(this).val(), 'Target province is required');
    });

    $('#editMaxOrderQuantity').on('input blur', function() {
        const value = $(this).val().trim();
        if (value === '') {
            showFieldError('editMaxOrderQuantity', 'Max order quantity is required');
        } else if (!/^\d+$/.test(value)) {
            showFieldError('editMaxOrderQuantity', 'Max order quantity must be a valid positive number');
        } else if (parseInt(value) < 1) {
            showFieldError('editMaxOrderQuantity', 'Max order quantity must be at least 1');
        } else {
            clearFieldError('editMaxOrderQuantity');
        }
    });

    $('#editShippingPrice').on('input blur', function() {
        const value = $(this).val().trim();
        if (value === '') {
            showFieldError('editShippingPrice', 'Shipping price is required');
        } else if (!/^\d+(\.\d{1,2})?$/.test(value)) {
            showFieldError('editShippingPrice', 'Shipping price must be a valid number (e.g., 100 or 100.50)');
        } else if (parseFloat(value) < 0) {
            showFieldError('editShippingPrice', 'Shipping price cannot be negative');
        } else {
            clearFieldError('editShippingPrice');
        }
    });

    // Form submission with validation
    $('#addTargetProvinceForm').on('submit', function(e) {
        e.preventDefault();

        // Reset previous validation states
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        let isValid = true;

        // Validate Target Province
        const targetProvince = $('#targetProvince').val();
        if (!targetProvince) {
            showFieldError('targetProvince', 'Target province is required');
            isValid = false;
        }

        // Validate Max Order Quantity
        const maxOrderQuantity = $('#maxOrderQuantity').val().trim();
        if (maxOrderQuantity === '') {
            showFieldError('maxOrderQuantity', 'Max order quantity is required');
            isValid = false;
        } else if (!/^\d+$/.test(maxOrderQuantity)) {
            showFieldError('maxOrderQuantity', 'Max order quantity must be a valid positive number');
            isValid = false;
        } else if (parseInt(maxOrderQuantity) < 1) {
            showFieldError('maxOrderQuantity', 'Max order quantity must be at least 1');
            isValid = false;
        }

        // Validate Shipping Price
        const shippingPrice = $('#shippingPrice').val().trim();
        if (shippingPrice === '') {
            showFieldError('shippingPrice', 'Shipping price is required');
            isValid = false;
        } else if (!/^\d+(\.\d{1,2})?$/.test(shippingPrice)) {
            showFieldError('shippingPrice', 'Shipping price must be a valid number (e.g., 100 or 100.50)');
            isValid = false;
        } else if (parseFloat(shippingPrice) < 0) {
            showFieldError('shippingPrice', 'Shipping price cannot be negative');
            isValid = false;
        }

        if (isValid) {
            // Form is valid - save the data
            saveTargetProvince();
        } else {
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.is-invalid').first().offset().top - 100
            }, 500);
        }
    });

    // Edit form submission with validation
    $('#editTargetProvinceForm').on('submit', function(e) {
        e.preventDefault();

        // Reset previous validation states
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').remove();

        let isValid = true;

        // Validate Target Province
        const targetProvince = $('#editTargetProvince').val();
        if (!targetProvince) {
            showFieldError('editTargetProvince', 'Target province is required');
            isValid = false;
        }

        // Validate Max Order Quantity
        const maxOrderQuantity = $('#editMaxOrderQuantity').val().trim();
        if (maxOrderQuantity === '') {
            showFieldError('editMaxOrderQuantity', 'Max order quantity is required');
            isValid = false;
        } else if (!/^\d+$/.test(maxOrderQuantity)) {
            showFieldError('editMaxOrderQuantity', 'Max order quantity must be a valid positive number');
            isValid = false;
        } else if (parseInt(maxOrderQuantity) < 1) {
            showFieldError('editMaxOrderQuantity', 'Max order quantity must be at least 1');
            isValid = false;
        }

        // Validate Shipping Price
        const shippingPrice = $('#editShippingPrice').val().trim();
        if (shippingPrice === '') {
            showFieldError('editShippingPrice', 'Shipping price is required');
            isValid = false;
        } else if (!/^\d+(\.\d{1,2})?$/.test(shippingPrice)) {
            showFieldError('editShippingPrice', 'Shipping price must be a valid number (e.g., 100 or 100.50)');
            isValid = false;
        } else if (parseFloat(shippingPrice) < 0) {
            showFieldError('editShippingPrice', 'Shipping price cannot be negative');
            isValid = false;
        }

        if (isValid) {
            // Form is valid - update the data
            updateTargetProvince();
        } else {
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.is-invalid').first().offset().top - 100
            }, 500);
        }
    });

    // Status change form submission
    $('#statusChangeForm').on('submit', function(e) {
        e.preventDefault();
        updateShippingOptionStatus();
    });

    // Pagination click handler
    $(document).on('click', '.pagination-btn', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadShippingOptionsData();
        }
    });
});

// Load shipping options data with AJAX
function loadShippingOptionsData() {
    showLoading();

    $.ajax({
        url: '/ecom-shipping-options/data',
        type: 'GET',
        data: {
            shipping_id: shippingId,
            search: searchTerm,
            page: currentPage,
            per_page: perPage
        },
        success: function(response) {
            hideLoading();
            updateTable(response.data);
            updatePagination(response);
            updateInfo(response);
        },
        error: function(xhr) {
            hideLoading();
            console.error('Error loading shipping options data:', xhr);
            $('#shippingOptionsTableBody').html('<tr><td colspan="5" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
        }
    });
}

// Show loading indicator
function showLoading() {
    $('#tableLoadingOverlay').show();
}

// Hide loading indicator
function hideLoading() {
    $('#tableLoadingOverlay').hide();
}

// Update table with data
function updateTable(data) {
    let tbody = $('#shippingOptionsTableBody');
    tbody.empty();

    if (data.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="5" class="text-center text-muted">
                    <i class="bx bx-package display-4"></i>
                    <p class="mt-2">No shipping options found</p>
                </td>
            </tr>
        `);
        return;
    }

    data.forEach(function(option) {
        let row = `
            <tr>
                <td>${option.provinceTarget}</td>
                <td>${option.maxQuantity}</td>
                <td>${option.shippingPrice}</td>
                <td>
                    <span class="badge ${option.statusBadgeClass}">
                        ${option.status}
                    </span>
                </td>
                <td class="text-center">
                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-primary badge-style status-btn"
                                data-option-id="${option.id}"
                                data-status="${option.isActive ? 1 : 0}"
                                title="Status">
                            <i class="bx bx-toggle-right me-1"></i>Status
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-success badge-style edit-btn"
                                data-option-id="${option.id}"
                                title="Edit">
                            <i class="bx bx-edit me-1"></i>Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger badge-style delete-btn"
                                data-option-id="${option.id}"
                                data-province-target="${option.provinceTarget}"
                                title="Delete">
                            <i class="bx bx-trash me-1"></i>Delete
                        </button>
                    </div>
                </td>
            </tr>
        `;
        tbody.append(row);
    });
}

// Update pagination
function updatePagination(response) {
    let pagination = $('#paginationContainer');
    pagination.empty();

    if (response.last_page <= 1) {
        return;
    }

    // Previous button
    let prevDisabled = response.current_page === 1 ? 'disabled' : '';
    let prevPage = response.current_page > 1 ? response.current_page - 1 : 1;
    pagination.append(`
        <li class="page-item ${prevDisabled}">
            <a class="page-link pagination-btn" href="#" data-page="${prevPage}">Previous</a>
        </li>
    `);

    // Page numbers
    let startPage = Math.max(1, response.current_page - 2);
    let endPage = Math.min(response.last_page, response.current_page + 2);

    if (startPage > 1) {
        pagination.append(`<li class="page-item"><a class="page-link pagination-btn" href="#" data-page="1">1</a></li>`);
        if (startPage > 2) {
            pagination.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
    }

    for (let i = startPage; i <= endPage; i++) {
        let activeClass = i === response.current_page ? 'active' : '';
        pagination.append(`
            <li class="page-item ${activeClass}">
                <a class="page-link pagination-btn" href="#" data-page="${i}">${i}</a>
            </li>
        `);
    }

    if (endPage < response.last_page) {
        if (endPage < response.last_page - 1) {
            pagination.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
        }
        pagination.append(`<li class="page-item"><a class="page-link pagination-btn" href="#" data-page="${response.last_page}">${response.last_page}</a></li>`);
    }

    // Next button
    let nextDisabled = response.current_page === response.last_page ? 'disabled' : '';
    let nextPage = response.current_page < response.last_page ? response.current_page + 1 : response.last_page;
    pagination.append(`
        <li class="page-item ${nextDisabled}">
            <a class="page-link pagination-btn" href="#" data-page="${nextPage}">Next</a>
        </li>
    `);
}

// Update info text
function updateInfo(response) {
    let info = `Showing ${response.from} to ${response.to} of ${response.total} entries`;
    $('#shippingOptionsTable_info').text(info);
}

// Validation helper functions
function validateField(fieldId, value, errorMessage) {
    if (value === '' || value === null || value === undefined) {
        showFieldError(fieldId, errorMessage);
    } else {
        clearFieldError(fieldId);
    }
}

function showFieldError(fieldId, message) {
    $('#' + fieldId).addClass('is-invalid').removeClass('is-valid');

    // Remove existing error message
    $('#' + fieldId).siblings('.invalid-feedback').remove();

    // Add new error message below the field
    $('#' + fieldId).after(`<div class="invalid-feedback d-block">${message}</div>`);
}

function clearFieldError(fieldId) {
    $('#' + fieldId).removeClass('is-invalid').addClass('is-valid');
    $('#' + fieldId).siblings('.invalid-feedback').remove();
}

function clearAddForm() {
    // Clear form fields
    $('#addTargetProvinceForm')[0].reset();

    // Clear validation states
    $('#targetProvince, #maxOrderQuantity, #shippingPrice').removeClass('is-invalid is-valid');
    $('.invalid-feedback').remove();
}

// Save target province function
function saveTargetProvince() {
    // Show loading state on submit button
    const submitBtn = $('#addTargetProvinceForm button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Saving...');

    // Prepare form data
    const formData = {
        shippingId: shippingId,
        provinceTarget: $('#targetProvince').val(),
        maxQuantity: $('#maxOrderQuantity').val(),
        shippingPrice: $('#shippingPrice').val(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    $.ajax({
        url: '/ecom-shipping-options',
        type: 'POST',
        data: formData,
        success: function(response) {
            // Reset button state
            submitBtn.prop('disabled', false).text(originalText);

            if (response.success) {
                // Close add modal
                $('#addTargetProvinceModal').modal('hide');

                // Show success toastr notification
                toastr.success('Target province saved successfully!', 'Success!', {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 3000,
                    positionClass: "toast-top-right"
                });

                // Refresh the table
                loadShippingOptionsData();

                // Refresh the province dropdown
                refreshProvinceDropdown();
            } else {
                toastr.error(response.message, 'Error!', {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 5000,
                    positionClass: "toast-top-right"
                });
            }
        },
        error: function(xhr) {
            // Reset button state
            submitBtn.prop('disabled', false).text(originalText);

            let errorMessage = 'An error occurred while saving the target province.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            toastr.error(errorMessage, 'Error!', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000,
                positionClass: "toast-top-right"
            });
        }
    });
}

// Load shipping option for editing
function loadShippingOptionForEdit(optionId) {
    $.ajax({
        url: '/ecom-shipping-options/' + optionId + '/edit',
        type: 'GET',
        success: function(response) {
            // Populate edit form
            $('#editOptionId').val(response.id);
            $('#editMaxOrderQuantity').val(response.maxQuantity);
            $('#editShippingPrice').val(response.shippingPrice);

            // Populate province dropdown with all provinces
            populateEditProvinceDropdown(response.provinceTarget);

            // Clear validation states
            clearEditForm();

            // Show edit modal
            $('#editTargetProvinceModal').modal('show');
        },
        error: function(xhr) {
            toastr.error('Error loading shipping option data.', 'Error!', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000,
                positionClass: "toast-top-right"
            });
        }
    });
}

// Populate edit province dropdown with available provinces + current province
function populateEditProvinceDropdown(currentProvince) {
    // First get available provinces (excluding already used ones)
    $.ajax({
        url: '/ecom-shipping-options/available-provinces',
        type: 'GET',
        data: {
            shipping_id: shippingId
        },
        success: function(response) {
            const select = $('#editTargetProvince');
            select.empty();
            select.append('<option value="">Select a province</option>');

            // Add current province first (always available for editing)
            select.append(`<option value="${currentProvince}" selected>${currentProvince}</option>`);

            // Add other available provinces (excluding current one to avoid duplicates)
            if (response.provinces.length > 0) {
                response.provinces.forEach(function(province) {
                    if (province !== currentProvince) {
                        select.append(`<option value="${province}">${province}</option>`);
                    }
                });
            }
        },
        error: function() {
            console.error('Error loading available provinces for edit');
            // Fallback: just show current province
            const select = $('#editTargetProvince');
            select.empty();
            select.append('<option value="">Select a province</option>');
            select.append(`<option value="${currentProvince}" selected>${currentProvince}</option>`);
        }
    });
}

// Update target province function
function updateTargetProvince() {
    // Show loading state on submit button
    const submitBtn = $('#editTargetProvinceForm button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Updating...');

    const optionId = $('#editOptionId').val();

    // Prepare form data
    const formData = {
        provinceTarget: $('#editTargetProvince').val(),
        maxQuantity: $('#editMaxOrderQuantity').val(),
        shippingPrice: $('#editShippingPrice').val(),
        _token: $('meta[name="csrf-token"]').attr('content'),
        _method: 'PUT'
    };

    $.ajax({
        url: '/ecom-shipping-options/' + optionId,
        type: 'POST',
        data: formData,
        success: function(response) {
            // Reset button state
            submitBtn.prop('disabled', false).text(originalText);

            if (response.success) {
                // Close edit modal
                $('#editTargetProvinceModal').modal('hide');

                // Show success toastr notification
                toastr.success('Target province updated successfully!', 'Success!', {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 3000,
                    positionClass: "toast-top-right"
                });

                // Refresh the table
                loadShippingOptionsData();

                // Refresh the province dropdown
                refreshProvinceDropdown();
            } else {
                toastr.error(response.message, 'Error!', {
                    closeButton: true,
                    progressBar: true,
                    timeOut: 5000,
                    positionClass: "toast-top-right"
                });
            }
        },
        error: function(xhr) {
            // Reset button state
            submitBtn.prop('disabled', false).text(originalText);

            let errorMessage = 'An error occurred while updating the target province.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            toastr.error(errorMessage, 'Error!', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000,
                positionClass: "toast-top-right"
            });
        }
    });
}

// Clear edit form
function clearEditForm() {
    // Clear validation states
    $('#editTargetProvince, #editMaxOrderQuantity, #editShippingPrice').removeClass('is-invalid is-valid');
    $('.invalid-feedback').remove();
}

// Load shipping option for status change
function loadShippingOptionForStatusChange(optionId, currentStatus) {
    $('#statusOptionId').val(optionId);

    // Map current status to dropdown value
    // currentStatus is already 1 or 0 from the data-status attribute
    $('#statusSelect').val(currentStatus);

    $('#statusChangeModal').modal('show');
}

// Update shipping option status
function updateShippingOptionStatus() {
    const optionId = $('#statusOptionId').val();
    const status = $('#statusSelect').val();

    if (!status) {
        toastr.error('Please select a status.', 'Error!', {
            closeButton: true,
            progressBar: true,
            timeOut: 5000,
            positionClass: "toast-top-right"
        });
        return;
    }

    // Show loading state on submit button
    const submitBtn = $('#statusChangeForm button[type="submit"]');
    const originalText = submitBtn.html();
    submitBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Updating...');

    $.ajax({
        url: '/ecom-shipping-options/' + optionId + '/status',
        type: 'PUT',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content'),
            status: status
        },
        success: function(response) {
            // Close modal
            $('#statusChangeModal').modal('hide');

            // Show success message
            toastr.success(response.message || 'Status updated successfully!', 'Success!', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000,
                positionClass: "toast-top-right"
            });

            // Refresh table data
            loadShippingOptionsData();
        },
        error: function(xhr) {
            let errorMessage = 'An error occurred while updating the status.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            toastr.error(errorMessage, 'Error!', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000,
                positionClass: "toast-top-right"
            });
        },
        complete: function() {
            // Reset button state
            submitBtn.prop('disabled', false).html(originalText);
        }
    });
}

// Show delete confirmation modal
function showDeleteConfirmation(optionId, provinceName) {
    $('#deleteConfirmationModal').data('option-id', optionId);
    $('#deleteProvinceName').text(provinceName);
    $('#deleteConfirmationModal').modal('show');
}

// Delete shipping option
function deleteShippingOption(optionId) {
    // Show loading state on delete button
    const deleteBtn = $('#confirmDeleteBtn');
    const originalText = deleteBtn.html();
    deleteBtn.prop('disabled', true).html('<span class="spinner-border spinner-border-sm me-2"></span>Deleting...');

    $.ajax({
        url: '/ecom-shipping-options/' + optionId,
        type: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            // Close modal
            $('#deleteConfirmationModal').modal('hide');

            // Show success message
            toastr.success(response.message || 'Target province deleted successfully!', 'Success!', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000,
                positionClass: "toast-top-right"
            });

            // Refresh table data
            loadShippingOptionsData();

            // Refresh province dropdown
            refreshProvinceDropdown();
        },
        error: function(xhr) {
            let errorMessage = 'An error occurred while deleting the target province.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            toastr.error(errorMessage, 'Error!', {
                closeButton: true,
                progressBar: true,
                timeOut: 5000,
                positionClass: "toast-top-right"
            });
        },
        complete: function() {
            // Reset button state
            deleteBtn.prop('disabled', false).html(originalText);
        }
    });
}

// Refresh province dropdown after saving
function refreshProvinceDropdown() {
    $.ajax({
        url: '/ecom-shipping-options/available-provinces',
        type: 'GET',
        data: {
            shipping_id: shippingId
        },
        success: function(response) {
            const select = $('#targetProvince');
            select.empty();
            select.append('<option value="">Select a province</option>');

            if (response.provinces.length > 0) {
                response.provinces.forEach(function(province) {
                    select.append(`<option value="${province}">${province}</option>`);
                });
            } else {
                select.append('<option value="" disabled>All provinces have been added</option>');
            }
        },
        error: function() {
            console.error('Error refreshing province dropdown');
        }
    });
}
</script>

<!-- Status Change Modal -->
<div class="modal fade" id="statusChangeModal" tabindex="-1" aria-labelledby="statusChangeModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="statusChangeModalLabel">Change Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="statusChangeForm">
                <div class="modal-body">
                    <input type="hidden" id="statusOptionId" name="optionId">
                    <div class="form-group mb-3">
                        <label for="statusSelect">Status <span class="text-danger">*</span></label>
                        <select class="form-select" id="statusSelect" name="status">
                            <option value="">Select status</option>
                            <option value="1">Active</option>
                            <option value="0">Inactive</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteConfirmationModal" tabindex="-1" aria-labelledby="deleteConfirmationModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteConfirmationModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="bx bx-trash text-danger" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Are you sure you want to delete this target province?</h5>
                    <p class="text-muted" id="deleteProvinceName"></p>
                    <p class="text-warning"><strong>This action cannot be undone.</strong></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bx bx-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/shipping-settings.blade.php ENDPATH**/ ?>