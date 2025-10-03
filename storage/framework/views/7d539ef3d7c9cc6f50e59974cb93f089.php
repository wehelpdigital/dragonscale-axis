<?php $__env->startSection('title'); ?> Shipping Settings <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<!-- DataTables CSS -->
<link href="<?php echo e(asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
<link href="<?php echo e(asset('build/libs/datatables.net-buttons-bs4/css/buttons.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />

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

/* Validation error styles */
.invalid-feedback {
    display: none;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875rem;
    color: #dc3545;
}

.invalid-feedback.show {
    display: block !important;
}

.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.form-control.is-valid {
    border-color: #28a745;
    box-shadow: 0 0 0 0.2rem rgba(40, 167, 69, 0.25);
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> E-commerce <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Shipping Settings <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Shipping Methods</h4>
                    <button class="btn btn-primary" id="addShippingBtn">
                        <i class="bx bx-plus"></i> Add New Shipping Method
                    </button>
                </div>

                <!-- Search Section -->
                <div class="row mb-3">
                    <div class="col-md-6">
                        <div class="d-flex">
                            <input type="text" class="form-control me-2" id="searchInput" placeholder="Search by shipping name...">
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

                <!-- Shipping Methods Table -->
                <div class="table-responsive position-relative">
                    <!-- Loading Overlay -->
                    <div id="tableLoadingOverlay" class="loading-overlay" style="display: none;">
                        <div class="loading-spinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="loading-text mt-2">Loading shipping methods...</div>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th>Shipping Name</th>
                                <th>Shipping Description</th>
                                <th>Default Price</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody id="shippingTableBody">
                            <!-- Data will be loaded dynamically -->
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="row mt-3">
                    <div class="col-sm-12 col-md-5">
                        <div class="dataTables_info" id="shippingTable_info" role="status" aria-live="polite">
                            Showing 0 to 0 of 0 entries
                        </div>
                    </div>
                    <div class="col-sm-12 col-md-7">
                        <div class="dataTables_paginate paging_simple_numbers" id="shippingTable_paginate">
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

<!-- Edit Modal -->
<div class="modal fade" id="editModal" tabindex="-1" role="dialog" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="editModalLabel">Edit Shipping Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm">
                <div class="modal-body">
                    <input type="hidden" id="editShippingId" name="id">
                    <div class="form-group mb-3">
                        <label for="editShippingName">Shipping Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="editShippingName" name="shippingName">
                        <div class="invalid-feedback" id="editShippingNameError"></div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="editShippingDescription">Shipping Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="editShippingDescription" name="shippingDescription" rows="3" placeholder="Enter shipping method description..."></textarea>
                        <div class="invalid-feedback" id="editShippingDescriptionError"></div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="editDefaultPrice">Default Price (PHP) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="editDefaultPrice" name="defaultPrice" step="0.01" min="0">
                        <div class="invalid-feedback" id="editDefaultPriceError"></div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="editDefaultMaxQuantity">Default Max Order Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="editDefaultMaxQuantity" name="defaultMaxQuantity" min="1" step="1">
                        <div class="invalid-feedback" id="editDefaultMaxQuantityError"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Shipping Method</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Add Shipping Modal -->
<div class="modal fade" id="addModal" tabindex="-1" role="dialog" aria-labelledby="addModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addModalLabel">Add New Shipping Method</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addForm">
                <div class="modal-body">
                    <div class="form-group mb-3">
                        <label for="addShippingName">Shipping Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="addShippingName" name="shippingName">
                        <div class="invalid-feedback" id="shippingNameError"></div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="addShippingDescription">Shipping Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="addShippingDescription" name="shippingDescription" rows="3" placeholder="Enter shipping method description..."></textarea>
                        <div class="invalid-feedback" id="shippingDescriptionError"></div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="addDefaultPrice">Default Price (PHP) <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="addDefaultPrice" name="defaultPrice" step="0.01" min="0">
                        <div class="invalid-feedback" id="defaultPriceError"></div>
                    </div>
                    <div class="form-group mb-3">
                        <label for="addDefaultMaxQuantity">Default Max Order Quantity <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="addDefaultMaxQuantity" name="defaultMaxQuantity" min="1" value="1" step="1">
                        <div class="invalid-feedback" id="defaultMaxQuantityError"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-success">Save Shipping Method</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" role="dialog" aria-labelledby="settingsModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="settingsModalLabel">Shipping Settings</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Advanced settings for this shipping method will be available here.</p>
                <div class="alert alert-info">
                    <i class="bx bx-info-circle"></i>
                    Settings functionality is under development.
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Success Modal -->
<div class="modal fade" id="successModal" tabindex="-1" role="dialog" aria-labelledby="successModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title" id="successModalLabel">
                    <i class="bx bx-check-circle me-2"></i>Success
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="bx bx-check-circle text-success" style="font-size: 3rem;"></i>
                </div>
                <h6 id="successMessage">Shipping method saved successfully!</h6>
                <p class="text-muted" id="successDescription">The shipping method has been updated in your system.</p>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-success" data-bs-dismiss="modal">OK</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" role="dialog" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bx bx-trash me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body text-center">
                <div class="mb-3">
                    <i class="bx bx-trash text-danger" style="font-size: 3rem;"></i>
                </div>
                <h6>Are you sure you want to delete this shipping method?</h6>
                <p class="text-muted">This action cannot be undone. The shipping method will be permanently removed from your system.</p>
                <div class="alert alert-warning">
                    <i class="bx bx-info-circle me-2"></i>
                    <strong>Shipping Method:</strong> <span id="deleteShippingName"></span>
                </div>
            </div>
            <div class="modal-footer justify-content-center">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bx bx-trash me-2"></i>Delete Shipping Method
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<!-- DataTables JS -->
<script src="<?php echo e(asset('build/libs/datatables.net/js/jquery.dataTables.min.js')); ?>"></script>
<script src="<?php echo e(asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js')); ?>"></script>
<script src="<?php echo e(asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js')); ?>"></script>
<script src="<?php echo e(asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js')); ?>"></script>

<script>
// Global variables for pagination and search
let currentPage = 1;
let perPage = 10;
let searchTerm = '';
let totalRecords = 0;
let lastPage = 1;

$(document).ready(function() {
    // Initialize the page
    loadShippingData();

    // Dynamic search functionality
    let searchTimeout;

    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        searchTimeout = setTimeout(function() {
            searchTerm = $('#searchInput').val();
            currentPage = 1;
            loadShippingData();
        }, 300); // 300ms delay to avoid too many requests
    });

    // Search button (optional - for manual trigger)
    $('#searchBtn').on('click', function() {
        clearTimeout(searchTimeout);
        searchTerm = $('#searchInput').val();
        currentPage = 1;
        loadShippingData();
    });

    // Clear search
    $('#clearFilters').on('click', function() {
        $('#searchInput').val('');
        searchTerm = '';
        currentPage = 1;
        loadShippingData();
    });

    // Add shipping method button
    $('#addShippingBtn').on('click', function() {
        // Clear form and validation when opening modal
        clearAddForm();
        $('#addModal').modal('show');
    });

    // Add form submission with validation
    $('#addForm').on('submit', function(e) {
        e.preventDefault();

        if (validateAddForm()) {
            // Form is valid, submit to server
            saveShippingMethod();
        }
    });

    // Real-time validation as user types
    $('#addShippingName').on('input blur', function() {
        validateField('addShippingName', $(this).val().trim(), 'Shipping name is required');
    });

    $('#addShippingDescription').on('input blur', function() {
        validateField('addShippingDescription', $(this).val().trim(), 'Shipping description is required');
    });

    $('#addDefaultPrice').on('input blur', function() {
        const value = $(this).val().trim();
        if (value === '') {
            validateField('addDefaultPrice', value, 'Default price is required');
        } else if (isNaN(value) || parseFloat(value) < 0) {
            validateField('addDefaultPrice', value, 'Default price must be a valid positive number');
        } else {
            clearFieldError('addDefaultPrice');
        }
    });

    $('#addDefaultMaxQuantity').on('input blur', function() {
        const value = $(this).val().trim();
        if (value === '') {
            validateField('addDefaultMaxQuantity', value, 'Default max order quantity is required');
        } else if (isNaN(value) || parseInt(value) <= 0) {
            validateField('addDefaultMaxQuantity', value, 'Default max order quantity must be greater than 0');
        } else {
            clearFieldError('addDefaultMaxQuantity');
        }
    });

    // Event delegation for edit buttons
    $(document).on('click', '.edit-btn', function() {
        const shippingId = $(this).data('id');
        loadShippingForEdit(shippingId);
    });

    // Edit form submission with validation
    $('#editForm').on('submit', function(e) {
        e.preventDefault();

        if (validateEditForm()) {
            // Form is valid, submit to server
            updateShippingMethod();
        }
    });

    // Real-time validation for edit form
    $('#editShippingName').on('input blur', function() {
        validateField('editShippingName', $(this).val().trim(), 'Shipping name is required');
    });

    $('#editShippingDescription').on('input blur', function() {
        validateField('editShippingDescription', $(this).val().trim(), 'Shipping description is required');
    });

    $('#editDefaultPrice').on('input blur', function() {
        const value = $(this).val().trim();
        if (value === '') {
            validateField('editDefaultPrice', value, 'Default price is required');
        } else if (isNaN(value) || parseFloat(value) < 0) {
            validateField('editDefaultPrice', value, 'Default price must be a valid positive number');
        } else {
            clearFieldError('editDefaultPrice');
        }
    });

    $('#editDefaultMaxQuantity').on('input blur', function() {
        const value = $(this).val().trim();
        if (value === '') {
            validateField('editDefaultMaxQuantity', value, 'Default max order quantity is required');
        } else if (isNaN(value) || parseInt(value) <= 0) {
            validateField('editDefaultMaxQuantity', value, 'Default max order quantity must be greater than 0');
        } else {
            clearFieldError('editDefaultMaxQuantity');
        }
    });

    // Event delegation for settings buttons
    $(document).on('click', '.settings-btn', function() {
        const shippingId = $(this).data('id');
        window.location.href = '/ecom-shipping-settings?id=' + shippingId;
    });

    // Event delegation for delete buttons
    $(document).on('click', '.delete-btn', function() {
        const shippingId = $(this).data('id');
        const shippingName = $(this).closest('tr').find('td:first').text();
        showDeleteConfirmation(shippingId, shippingName);
    });

    // Confirm delete button
    $('#confirmDeleteBtn').on('click', function() {
        const shippingId = $(this).data('shipping-id');
        deleteShippingMethod(shippingId);
    });

    // Pagination click handler
    $(document).on('click', '.pagination-btn', function(e) {
        e.preventDefault();
        var page = $(this).data('page');
        if (page && page !== currentPage) {
            currentPage = page;
            loadShippingData();
        }
    });
});

// Load shipping data with AJAX
function loadShippingData() {
    showLoading();

    $.ajax({
        url: '/ecom-shipping/data',
        type: 'GET',
        data: {
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
            console.error('Error loading shipping data:', xhr);
            $('#shippingTableBody').html('<tr><td colspan="4" class="text-center text-danger">Error loading data. Please try again.</td></tr>');
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
    let tbody = $('#shippingTableBody');
    tbody.empty();

    if (data.length === 0) {
        tbody.html(`
            <tr>
                <td colspan="4" class="text-center text-muted">
                    <i class="bx bx-car display-4"></i>
                    <p class="mt-2">No shipping methods found</p>
                </td>
            </tr>
        `);
        return;
    }

    data.forEach(function(shipping) {
        let row = `
            <tr>
                <td>${shipping.shippingName}</td>
                <td>${shipping.shippingDescription}</td>
                <td>${shipping.defaultPrice}</td>
                <td class="text-center">
                    <div class="d-flex flex-wrap gap-1 justify-content-center">
                        <button type="button" class="btn btn-sm btn-outline-success badge-style edit-btn" data-id="${shipping.id}" title="Edit">
                            <i class="bx bx-edit me-1"></i>Edit
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-info badge-style settings-btn" data-id="${shipping.id}" title="Settings">
                            <i class="bx bx-cog me-1"></i>Settings
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger badge-style delete-btn" data-id="${shipping.id}" title="Delete">
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
    $('#shippingTable_info').text(info);
}

// Edit shipping method (temporarily disabled)
// function editShipping(id) {
//     // Edit functionality removed temporarily
// }

// Update shipping method (temporarily disabled)
// $('#editForm').on('submit', function(e) {
//     // Form submission handler removed temporarily
// });

// Add shipping method (temporarily disabled)
// $('#addForm').on('submit', function(e) {
//     // Form submission handler removed temporarily
// });

// Settings shipping method (temporarily disabled)
// function settingsShipping(id) {
//     // Settings functionality removed temporarily
// }

// Delete shipping method (temporarily disabled)
// function deleteShipping(id) {
//     // Delete functionality removed temporarily
// }

// Validation functions for add form
function validateAddForm() {
    let isValid = true;

    // Clear previous validation states
    clearValidationErrors();

    // Validate Shipping Name
    const shippingName = $('#addShippingName').val().trim();
    if (shippingName === '') {
        showFieldError('addShippingName', 'Shipping name is required');
        isValid = false;
    }

    // Validate Shipping Description
    const shippingDescription = $('#addShippingDescription').val().trim();
    if (shippingDescription === '') {
        showFieldError('addShippingDescription', 'Shipping description is required');
        isValid = false;
    }

    // Validate Default Price
    const defaultPrice = $('#addDefaultPrice').val().trim();
    if (defaultPrice === '') {
        showFieldError('addDefaultPrice', 'Default price is required');
        isValid = false;
    } else if (isNaN(defaultPrice) || parseFloat(defaultPrice) < 0) {
        showFieldError('addDefaultPrice', 'Default price must be a valid positive number');
        isValid = false;
    }

    // Validate Default Max Order Quantity
    const defaultMaxQuantity = $('#addDefaultMaxQuantity').val().trim();
    if (defaultMaxQuantity === '') {
        showFieldError('addDefaultMaxQuantity', 'Default max order quantity is required');
        isValid = false;
    } else if (isNaN(defaultMaxQuantity) || parseInt(defaultMaxQuantity) <= 0) {
        showFieldError('addDefaultMaxQuantity', 'Default max order quantity must be greater than 0');
        isValid = false;
    }

    return isValid;
}

function validateField(fieldId, value, errorMessage) {
    if (value === '') {
        showFieldError(fieldId, errorMessage);
    } else {
        clearFieldError(fieldId);
    }
}

function showFieldError(fieldId, message) {
    $('#' + fieldId).addClass('is-invalid').removeClass('is-valid');
    $('#' + fieldId + 'Error').text(message).addClass('show');
}

function clearFieldError(fieldId) {
    $('#' + fieldId).removeClass('is-invalid').addClass('is-valid');
    $('#' + fieldId + 'Error').removeClass('show');
}

function clearValidationErrors() {
    // Remove invalid/valid classes from all fields
    $('#addShippingName, #addShippingDescription, #addDefaultPrice, #addDefaultMaxQuantity').removeClass('is-invalid is-valid');

    // Hide all error messages
    $('#shippingNameError, #shippingDescriptionError, #defaultPriceError, #defaultMaxQuantityError').removeClass('show');
}

function clearAddForm() {
    // Clear form fields
    $('#addForm')[0].reset();

    // Clear validation states
    clearValidationErrors();
}

// Save shipping method to database
function saveShippingMethod() {
    // Show loading state on submit button
    const submitBtn = $('#addForm button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Saving...');

    // Prepare form data
    const formData = {
        shippingName: $('#addShippingName').val().trim(),
        shippingDescription: $('#addShippingDescription').val().trim(),
        defaultPrice: $('#addDefaultPrice').val().trim(),
        defaultMaxQuantity: $('#addDefaultMaxQuantity').val().trim(),
        _token: $('meta[name="csrf-token"]').attr('content')
    };

    $.ajax({
        url: '/ecom-shipping',
        type: 'POST',
        data: formData,
        success: function(response) {
            // Reset button state
            submitBtn.prop('disabled', false).text(originalText);

            if (response.success) {
                // Close add modal
                $('#addModal').modal('hide');

                // Update success modal message
                $('#successMessage').text('Shipping method saved successfully!');
                $('#successDescription').text('The new shipping method has been added to your system.');

                // Show success modal
                $('#successModal').modal('show');

                // Refresh the table
                loadShippingData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            // Reset button state
            submitBtn.prop('disabled', false).text(originalText);

            let errorMessage = 'An error occurred while saving the shipping method.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            alert('Error: ' + errorMessage);
        }
    });
}

// Load shipping data for editing
function loadShippingForEdit(shippingId) {
    $.ajax({
        url: '/ecom-shipping/' + shippingId + '/edit',
        type: 'GET',
        success: function(response) {
            // Populate edit form
            $('#editShippingId').val(response.id);
            $('#editShippingName').val(response.shippingName);
            $('#editShippingDescription').val(response.shippingDescription);
            $('#editDefaultPrice').val(response.defaultPrice);
            $('#editDefaultMaxQuantity').val(response.defaultMaxQuantity);

            // Clear validation states
            clearEditValidationErrors();

            // Show edit modal
            $('#editModal').modal('show');
        },
        error: function(xhr) {
            alert('Error loading shipping method data.');
        }
    });
}

// Validate edit form
function validateEditForm() {
    let isValid = true;

    // Clear previous validation states
    clearEditValidationErrors();

    // Validate Shipping Name
    const shippingName = $('#editShippingName').val().trim();
    if (shippingName === '') {
        showFieldError('editShippingName', 'Shipping name is required');
        isValid = false;
    }

    // Validate Shipping Description
    const shippingDescription = $('#editShippingDescription').val().trim();
    if (shippingDescription === '') {
        showFieldError('editShippingDescription', 'Shipping description is required');
        isValid = false;
    }

    // Validate Default Price
    const defaultPrice = $('#editDefaultPrice').val().trim();
    if (defaultPrice === '') {
        showFieldError('editDefaultPrice', 'Default price is required');
        isValid = false;
    } else if (isNaN(defaultPrice) || parseFloat(defaultPrice) < 0) {
        showFieldError('editDefaultPrice', 'Default price must be a valid positive number');
        isValid = false;
    }

    // Validate Default Max Order Quantity
    const defaultMaxQuantity = $('#editDefaultMaxQuantity').val().trim();
    if (defaultMaxQuantity === '') {
        showFieldError('editDefaultMaxQuantity', 'Default max order quantity is required');
        isValid = false;
    } else if (isNaN(defaultMaxQuantity) || parseInt(defaultMaxQuantity) <= 0) {
        showFieldError('editDefaultMaxQuantity', 'Default max order quantity must be greater than 0');
        isValid = false;
    }

    return isValid;
}

// Update shipping method
function updateShippingMethod() {
    // Show loading state on submit button
    const submitBtn = $('#editForm button[type="submit"]');
    const originalText = submitBtn.text();
    submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Updating...');

    const shippingId = $('#editShippingId').val();

    // Prepare form data
    const formData = {
        shippingName: $('#editShippingName').val().trim(),
        shippingDescription: $('#editShippingDescription').val().trim(),
        defaultPrice: $('#editDefaultPrice').val().trim(),
        defaultMaxQuantity: $('#editDefaultMaxQuantity').val().trim(),
        _token: $('meta[name="csrf-token"]').attr('content'),
        _method: 'PUT'
    };

    $.ajax({
        url: '/ecom-shipping/' + shippingId,
        type: 'POST',
        data: formData,
        success: function(response) {
            // Reset button state
            submitBtn.prop('disabled', false).text(originalText);

            if (response.success) {
                // Close edit modal
                $('#editModal').modal('hide');

                // Update success modal message
                $('#successMessage').text('Shipping method updated successfully!');
                $('#successDescription').text('The shipping method has been updated in your system.');

                // Show success modal
                $('#successModal').modal('show');

                // Refresh the table
                loadShippingData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            // Reset button state
            submitBtn.prop('disabled', false).text(originalText);

            let errorMessage = 'An error occurred while updating the shipping method.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            alert('Error: ' + errorMessage);
        }
    });
}

// Clear edit form validation errors
function clearEditValidationErrors() {
    // Remove invalid/valid classes from all edit fields
    $('#editShippingName, #editShippingDescription, #editDefaultPrice, #editDefaultMaxQuantity').removeClass('is-invalid is-valid');

    // Hide all error messages
    $('#editShippingNameError, #editShippingDescriptionError, #editDefaultPriceError, #editDefaultMaxQuantityError').removeClass('show');
}

// Show delete confirmation modal
function showDeleteConfirmation(shippingId, shippingName) {
    // Store shipping ID in the confirm button
    $('#confirmDeleteBtn').data('shipping-id', shippingId);

    // Update modal content
    $('#deleteShippingName').text(shippingName);

    // Show delete modal
    $('#deleteModal').modal('show');
}

// Delete shipping method
function deleteShippingMethod(shippingId) {
    // Show loading state on delete button
    const deleteBtn = $('#confirmDeleteBtn');
    const originalText = deleteBtn.html();
    deleteBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-2"></i>Deleting...');

    $.ajax({
        url: '/ecom-shipping/' + shippingId,
        type: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            // Reset button state
            deleteBtn.prop('disabled', false).html(originalText);

            if (response.success) {
                // Close delete modal
                $('#deleteModal').modal('hide');

                // Update success modal message
                $('#successMessage').text('Shipping method deleted successfully!');
                $('#successDescription').text('The shipping method has been removed from your system.');

                // Show success modal
                $('#successModal').modal('show');

                // Refresh the table
                loadShippingData();
            } else {
                alert('Error: ' + response.message);
            }
        },
        error: function(xhr) {
            // Reset button state
            deleteBtn.prop('disabled', false).html(originalText);

            let errorMessage = 'An error occurred while deleting the shipping method.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            alert('Error: ' + errorMessage);
        }
    });
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/shipping.blade.php ENDPATH**/ ?>