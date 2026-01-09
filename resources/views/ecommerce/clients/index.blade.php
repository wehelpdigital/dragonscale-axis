@extends('layouts.master')

@section('title') All Clients @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
    .client-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 600;
        font-size: 14px;
        color: #fff;
    }
    .search-box {
        position: relative;
        min-width: 250px;
    }
    .search-box .form-control {
        padding-left: 38px;
        height: 38px;
    }
    .search-box .bx-search {
        position: absolute;
        left: 13px;
        top: 0;
        height: 38px;
        display: flex;
        align-items: center;
        color: #74788d;
        font-size: 16px;
        z-index: 4;
    }
    .client-row {
        transition: background-color 0.15s ease;
    }
    .client-row:hover {
        background-color: rgba(var(--bs-primary-rgb), 0.05);
    }
    .copy-btn {
        opacity: 0;
        transition: opacity 0.15s ease;
    }
    .client-row:hover .copy-btn {
        opacity: 1;
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
</style>
@endsection

@section('content')

    @component('components.breadcrumb')
        @slot('li_1') E-commerce @endslot
        @slot('title') All Clients @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap align-items-center mb-4 gap-2">
                        <h4 class="card-title me-3 mb-0">All Clients</h4>
                        <div class="ms-auto d-flex gap-2 align-items-center flex-wrap">
                            <!-- Per Page -->
                            <select class="form-select form-select-sm" id="perPageSelect" style="width: auto;">
                                <option value="15">15 per page</option>
                                <option value="25">25 per page</option>
                                <option value="50">50 per page</option>
                                <option value="100">100 per page</option>
                            </select>

                            <!-- Search -->
                            <div class="search-box">
                                <i class="bx bx-search"></i>
                                <input type="text" class="form-control" id="searchInput"
                                       placeholder="Search clients...">
                            </div>

                            <!-- Add Client Button -->
                            <button type="button" class="btn btn-primary" id="addClientBtn">
                                <i class="bx bx-plus me-1"></i> Add Client
                            </button>
                        </div>
                    </div>

                    <!-- Clients Table -->
                    <div class="table-container">
                        <div class="loading-overlay" id="loadingOverlay" style="display: none;">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover mb-0" id="clientsTable">
                                <thead class="table-light">
                                    <tr>
                                        <th style="width: 60px;"></th>
                                        <th>Client Name</th>
                                        <th>Phone Number</th>
                                        <th>Email Address</th>
                                        <th style="width: 100px;" class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody id="clientsTableBody">
                                    <!-- Data will be loaded via AJAX -->
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Pagination and Info -->
                    <div class="d-flex flex-wrap justify-content-between align-items-center mt-3 gap-2">
                        <div class="pagination-info text-secondary" id="paginationInfo">
                            <!-- Showing X to Y of Z entries -->
                        </div>
                        <nav aria-label="Clients pagination">
                            <ul class="pagination pagination-sm mb-0" id="paginationContainer">
                                <!-- Pagination will be rendered here -->
                            </ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Add/Edit Client Modal -->
    <div class="modal fade" id="clientModal" tabindex="-1" aria-labelledby="clientModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="clientModalLabel">
                        <i class="bx bx-user-plus me-2"></i>Add Client
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <form id="clientForm">
                        <input type="hidden" id="editClientId" value="">

                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label for="clientFirstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="clientFirstName" name="clientFirstName"
                                       placeholder="First name" required>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="clientMiddleName" class="form-label">Middle Name</label>
                                <input type="text" class="form-control" id="clientMiddleName" name="clientMiddleName"
                                       placeholder="Middle name">
                            </div>
                            <div class="col-md-4 mb-3">
                                <label for="clientLastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="clientLastName" name="clientLastName"
                                       placeholder="Last name" required>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="clientPhoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" id="clientPhoneNumber" name="clientPhoneNumber"
                                       placeholder="e.g., 09123456789" maxlength="11">
                                <div class="invalid-feedback" id="phoneError">Phone number is required.</div>
                                <div class="valid-feedback" id="phoneSuccess">Phone number is available.</div>
                                <small class="text-secondary" id="phoneFormatHint">Format: 09XXXXXXXXX (11 digits)</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label for="clientEmailAddress" class="form-label">Email Address <span class="text-danger">*</span></label>
                                <input type="email" class="form-control" id="clientEmailAddress" name="clientEmailAddress"
                                       placeholder="e.g., client@example.com">
                                <div class="invalid-feedback" id="emailError">Email address is required.</div>
                                <div class="valid-feedback" id="emailSuccess">Email address is available.</div>
                            </div>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveClientBtn">
                        <i class="bx bx-save me-1"></i> Save
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
                    <p class="text-dark mb-0">Are you sure you want to delete <strong id="deleteClientName"></strong>?</p>
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

@endsection

@section('script')
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
// Phone format regex (09XXXXXXXXX - 11 digits starting with 09)
const phoneRegex = /^09\d{9}$/;

// Email format regex
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

// Validation state
let phoneValidationState = { valid: false, checking: false, formatValid: false };
let emailValidationState = { valid: false, checking: false, formatValid: false };

// Debounce function
function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => {
            clearTimeout(timeout);
            func(...args);
        };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

// Validate phone format
function validatePhoneFormat(phone) {
    if (!phone || phone.trim() === '') {
        return { valid: false, message: 'Phone number is required.' };
    }
    if (!phoneRegex.test(phone)) {
        return { valid: false, message: 'Invalid format. Use 09XXXXXXXXX (11 digits).' };
    }
    return { valid: true, message: '' };
}

// Validate email format
function validateEmailFormat(email) {
    if (!email || email.trim() === '') {
        return { valid: false, message: 'Email address is required.' };
    }
    if (!emailRegex.test(email)) {
        return { valid: false, message: 'Please enter a valid email address.' };
    }
    return { valid: true, message: '' };
}

// Validate phone number (format + uniqueness)
function validatePhone(phone, excludeId = null) {
    const $input = $('#clientPhoneNumber');

    // Clear validation if empty
    if (!phone || phone.trim() === '') {
        $input.removeClass('is-valid is-invalid');
        $('#phoneError').text('Phone number is required.');
        phoneValidationState = { valid: false, checking: false, formatValid: false };
        return;
    }

    // Check format first
    const formatResult = validatePhoneFormat(phone);
    if (!formatResult.valid) {
        $input.removeClass('is-valid').addClass('is-invalid');
        $('#phoneError').text(formatResult.message);
        phoneValidationState = { valid: false, checking: false, formatValid: false };
        return;
    }

    phoneValidationState.checking = true;
    phoneValidationState.formatValid = true;

    let url = '{{ route("ecom-clients.check-phone") }}?phone=' + encodeURIComponent(phone);
    if (excludeId) {
        url += '&exclude_id=' + excludeId;
    }

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            phoneValidationState.checking = false;
            if (response.exists) {
                $input.removeClass('is-valid').addClass('is-invalid');
                $('#phoneError').text(response.message);
                phoneValidationState.valid = false;
            } else {
                $input.removeClass('is-invalid').addClass('is-valid');
                phoneValidationState.valid = true;
            }
        },
        error: function() {
            phoneValidationState.checking = false;
            phoneValidationState.valid = true; // Allow submission on error
            $input.removeClass('is-valid is-invalid');
        }
    });
}

// Validate email address (format + uniqueness)
function validateEmail(email, excludeId = null) {
    const $input = $('#clientEmailAddress');

    // Clear validation if empty
    if (!email || email.trim() === '') {
        $input.removeClass('is-valid is-invalid');
        $('#emailError').text('Email address is required.');
        emailValidationState = { valid: false, checking: false, formatValid: false };
        return;
    }

    // Check format first
    const formatResult = validateEmailFormat(email);
    if (!formatResult.valid) {
        $input.removeClass('is-valid').addClass('is-invalid');
        $('#emailError').text(formatResult.message);
        emailValidationState = { valid: false, checking: false, formatValid: false };
        return;
    }

    emailValidationState.checking = true;
    emailValidationState.formatValid = true;

    let url = '{{ route("ecom-clients.check-email") }}?email=' + encodeURIComponent(email);
    if (excludeId) {
        url += '&exclude_id=' + excludeId;
    }

    $.ajax({
        url: url,
        type: 'GET',
        success: function(response) {
            emailValidationState.checking = false;
            if (response.exists) {
                $input.removeClass('is-valid').addClass('is-invalid');
                $('#emailError').text(response.message);
                emailValidationState.valid = false;
            } else {
                $input.removeClass('is-invalid').addClass('is-valid');
                emailValidationState.valid = true;
            }
        },
        error: function() {
            emailValidationState.checking = false;
            emailValidationState.valid = true; // Allow submission on error
            $input.removeClass('is-valid is-invalid');
        }
    });
}

// Debounced validation functions
const debouncedPhoneValidation = debounce(function() {
    const phone = $('#clientPhoneNumber').val();
    const excludeId = $('#editClientId').val() || null;
    validatePhone(phone, excludeId);
}, 500);

const debouncedEmailValidation = debounce(function() {
    const email = $('#clientEmailAddress').val();
    const excludeId = $('#editClientId').val() || null;
    validateEmail(email, excludeId);
}, 500);

$(document).ready(function() {
    let clientModal = new bootstrap.Modal(document.getElementById('clientModal'));
    let deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    let clientToDelete = null;
    let searchTimeout = null;
    let currentPage = 1;
    let perPage = 15;
    let currentSearch = '';

    // Initialize
    loadClients();

    // Search with debounce
    $('#searchInput').on('input', function() {
        clearTimeout(searchTimeout);
        const search = $(this).val();

        searchTimeout = setTimeout(function() {
            currentSearch = search;
            currentPage = 1;
            loadClients();
        }, 300);
    });

    // Per page change
    $('#perPageSelect').on('change', function() {
        perPage = parseInt($(this).val());
        currentPage = 1;
        loadClients();
    });

    // Load clients via AJAX
    function loadClients() {
        showLoading(true);

        $.ajax({
            url: '{{ route("ecom-clients.data") }}',
            type: 'GET',
            data: {
                search: currentSearch,
                page: currentPage,
                per_page: perPage
            },
            success: function(response) {
                if (response.success) {
                    renderClients(response.data);
                    renderPagination(response.pagination);
                    updatePaginationInfo(response.pagination, response.filtered_count);
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load clients', 'Error!');
            },
            complete: function() {
                showLoading(false);
            }
        });
    }

    // Render clients table
    function renderClients(clients) {
        const $tbody = $('#clientsTableBody');
        $tbody.empty();

        if (clients.length === 0) {
            $tbody.html(`
                <tr id="emptyRow">
                    <td colspan="5" class="text-center py-4">
                        <i class="bx bx-user-x text-secondary" style="font-size: 2.5rem;"></i>
                        <p class="text-dark mt-2 mb-0">No clients found.</p>
                        <small class="text-secondary">Add clients or adjust your search.</small>
                    </td>
                </tr>
            `);
            return;
        }

        clients.forEach(function(client) {
            const fullName = client.fullName || 'Unknown';
            const initials = client.initials || '?';
            const phone = client.clientPhoneNumber || '';
            const email = client.clientEmailAddress || '';

            const row = `
                <tr class="client-row" data-client-id="${client.id}">
                    <td>
                        <div class="client-avatar" style="background-color: ${client.avatarColor};">
                            ${escapeHtml(initials)}
                        </div>
                    </td>
                    <td>
                        <strong class="text-dark">${escapeHtml(fullName)}</strong>
                    </td>
                    <td>
                        ${phone ? `
                            <span class="text-dark">${escapeHtml(phone)}</span>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-1 copy-btn"
                                    data-copy="${escapeHtml(phone)}" title="Copy phone">
                                <i class="bx bx-copy text-muted"></i>
                            </button>
                        ` : '<span class="text-secondary">-</span>'}
                    </td>
                    <td>
                        ${email ? `
                            <span class="text-dark">${escapeHtml(email)}</span>
                            <button type="button" class="btn btn-link btn-sm p-0 ms-1 copy-btn"
                                    data-copy="${escapeHtml(email)}" title="Copy email">
                                <i class="bx bx-copy text-muted"></i>
                            </button>
                        ` : '<span class="text-secondary">-</span>'}
                    </td>
                    <td class="text-center">
                        <button type="button" class="btn btn-sm btn-soft-primary edit-btn"
                                data-client-id="${client.id}" title="Edit">
                            <i class="bx bx-edit-alt"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-soft-danger delete-btn"
                                data-client-id="${client.id}"
                                data-client-name="${escapeHtml(fullName)}" title="Delete">
                            <i class="bx bx-trash"></i>
                        </button>
                    </td>
                </tr>
            `;
            $tbody.append(row);
        });
    }

    // Render pagination
    function renderPagination(pagination) {
        const $container = $('#paginationContainer');
        $container.empty();

        if (pagination.last_page <= 1) {
            return;
        }

        // Previous button
        $container.append(`
            <li class="page-item ${pagination.current_page === 1 ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page - 1}" aria-label="Previous">
                    <i class="bx bx-chevron-left"></i>
                </a>
            </li>
        `);

        // Page numbers
        const startPage = Math.max(1, pagination.current_page - 2);
        const endPage = Math.min(pagination.last_page, pagination.current_page + 2);

        if (startPage > 1) {
            $container.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="1">1</a>
                </li>
            `);
            if (startPage > 2) {
                $container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
            }
        }

        for (let i = startPage; i <= endPage; i++) {
            $container.append(`
                <li class="page-item ${i === pagination.current_page ? 'active' : ''}">
                    <a class="page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `);
        }

        if (endPage < pagination.last_page) {
            if (endPage < pagination.last_page - 1) {
                $container.append(`<li class="page-item disabled"><span class="page-link">...</span></li>`);
            }
            $container.append(`
                <li class="page-item">
                    <a class="page-link" href="#" data-page="${pagination.last_page}">${pagination.last_page}</a>
                </li>
            `);
        }

        // Next button
        $container.append(`
            <li class="page-item ${pagination.current_page === pagination.last_page ? 'disabled' : ''}">
                <a class="page-link" href="#" data-page="${pagination.current_page + 1}" aria-label="Next">
                    <i class="bx bx-chevron-right"></i>
                </a>
            </li>
        `);
    }

    // Update pagination info
    function updatePaginationInfo(pagination, filteredCount) {
        const $info = $('#paginationInfo');
        if (pagination.total === 0) {
            $info.html('No clients found');
        } else {
            $info.html(`Showing <strong class="text-dark">${pagination.from}</strong> to <strong class="text-dark">${pagination.to}</strong> of <strong class="text-dark">${pagination.total}</strong> client(s)`);
        }
    }

    // Pagination click handler
    $(document).on('click', '#paginationContainer .page-link', function(e) {
        e.preventDefault();
        const page = $(this).data('page');
        if (page && !$(this).parent().hasClass('disabled') && !$(this).parent().hasClass('active')) {
            currentPage = page;
            loadClients();
        }
    });

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

    // Phone number validation on blur and input
    $('#clientPhoneNumber').on('blur', function() {
        const phone = $(this).val();
        const excludeId = $('#editClientId').val() || null;
        validatePhone(phone, excludeId);
    });

    $('#clientPhoneNumber').on('input', debouncedPhoneValidation);

    // Email validation on blur and input
    $('#clientEmailAddress').on('blur', function() {
        const email = $(this).val();
        const excludeId = $('#editClientId').val() || null;
        validateEmail(email, excludeId);
    });

    $('#clientEmailAddress').on('input', debouncedEmailValidation);

    // Add client button
    $('#addClientBtn').on('click', function() {
        resetForm();
        $('#clientModalLabel').html('<i class="bx bx-user-plus me-2"></i>Add Client');
        $('#editClientId').val('');
        clientModal.show();
    });

    // Edit client button
    $(document).on('click', '.edit-btn', function(e) {
        e.stopPropagation();
        const clientId = $(this).data('client-id');
        loadClientData(clientId);
    });

    // Delete client button
    $(document).on('click', '.delete-btn', function(e) {
        e.stopPropagation();
        clientToDelete = {
            id: $(this).data('client-id'),
            name: $(this).data('client-name')
        };
        $('#deleteClientName').text(clientToDelete.name || 'this client');
        deleteModal.show();
    });

    // Confirm delete
    $('#confirmDeleteBtn').on('click', function() {
        if (!clientToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: '{{ route("ecom-clients.delete") }}?id=' + clientToDelete.id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    deleteModal.hide();
                    toastr.success(response.message, 'Success!');
                    loadClients();
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
                clientToDelete = null;
            }
        });
    });

    // Save client
    $('#saveClientBtn').on('click', function() {
        const $btn = $(this);
        const editId = $('#editClientId').val();
        const isEdit = editId !== '';

        const firstName = $('#clientFirstName').val().trim();
        const lastName = $('#clientLastName').val().trim();
        const phone = $('#clientPhoneNumber').val().trim();
        const email = $('#clientEmailAddress').val().trim();

        // Validate required fields
        if (!firstName) {
            toastr.error('First name is required', 'Validation Error');
            $('#clientFirstName').focus();
            return;
        }
        if (!lastName) {
            toastr.error('Last name is required', 'Validation Error');
            $('#clientLastName').focus();
            return;
        }

        // Phone number validation
        if (!phone) {
            toastr.error('Phone number is required.', 'Validation Error');
            $('#clientPhoneNumber').addClass('is-invalid');
            $('#phoneError').text('Phone number is required.');
            $('#clientPhoneNumber').focus();
            return;
        }

        // Validate phone format
        const phoneFormatResult = validatePhoneFormat(phone);
        if (!phoneFormatResult.valid) {
            toastr.error(phoneFormatResult.message, 'Validation Error');
            $('#clientPhoneNumber').addClass('is-invalid');
            $('#phoneError').text(phoneFormatResult.message);
            $('#clientPhoneNumber').focus();
            return;
        }

        // Email validation
        if (!email) {
            toastr.error('Email address is required.', 'Validation Error');
            $('#clientEmailAddress').addClass('is-invalid');
            $('#emailError').text('Email address is required.');
            $('#clientEmailAddress').focus();
            return;
        }

        // Validate email format
        const emailFormatResult = validateEmailFormat(email);
        if (!emailFormatResult.valid) {
            toastr.error(emailFormatResult.message, 'Validation Error');
            $('#clientEmailAddress').addClass('is-invalid');
            $('#emailError').text(emailFormatResult.message);
            $('#clientEmailAddress').focus();
            return;
        }

        // Check if validation is still in progress
        if (phoneValidationState.checking || emailValidationState.checking) {
            toastr.warning('Please wait while we validate your input...', 'Validating');
            return;
        }

        // Check if phone number uniqueness validation failed
        if (!phoneValidationState.valid) {
            toastr.error('Phone number already exists.', 'Validation Error');
            $('#clientPhoneNumber').focus();
            return;
        }

        // Check if email uniqueness validation failed
        if (!emailValidationState.valid) {
            toastr.error('Email address already exists.', 'Validation Error');
            $('#clientEmailAddress').focus();
            return;
        }

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        const formData = {
            _token: '{{ csrf_token() }}',
            clientFirstName: $('#clientFirstName').val(),
            clientMiddleName: $('#clientMiddleName').val(),
            clientLastName: $('#clientLastName').val(),
            clientPhoneNumber: $('#clientPhoneNumber').val(),
            clientEmailAddress: $('#clientEmailAddress').val()
        };

        const url = isEdit
            ? '{{ route("ecom-clients.update") }}?id=' + editId
            : '{{ route("ecom-clients.store") }}';

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    clientModal.hide();
                    toastr.success(response.message, 'Success!');
                    loadClients();
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'An error occurred', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save');
            }
        });
    });

    // Load client data for editing
    function loadClientData(clientId) {
        $.ajax({
            url: '{{ route("ecom-clients.show") }}?id=' + clientId,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const client = response.client;
                    resetForm();
                    $('#clientModalLabel').html('<i class="bx bx-edit me-2"></i>Edit Client');
                    $('#editClientId').val(client.id);
                    $('#clientFirstName').val(client.clientFirstName);
                    $('#clientMiddleName').val(client.clientMiddleName);
                    $('#clientLastName').val(client.clientLastName);
                    $('#clientPhoneNumber').val(client.clientPhoneNumber);
                    $('#clientEmailAddress').val(client.clientEmailAddress);

                    // Mark existing phone and email as valid (they're already stored)
                    phoneValidationState = { valid: true, checking: false, formatValid: true };
                    emailValidationState = { valid: true, checking: false, formatValid: true };
                    $('#clientPhoneNumber').removeClass('is-invalid').addClass('is-valid');
                    $('#clientEmailAddress').removeClass('is-invalid').addClass('is-valid');

                    clientModal.show();
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Failed to load client data', 'Error!');
            }
        });
    }

    // Reset form
    function resetForm() {
        $('#clientForm')[0].reset();
        $('#editClientId').val('');
        $('.is-invalid').removeClass('is-invalid');
        $('.is-valid').removeClass('is-valid');

        // Reset validation states (for new entries, these are required)
        phoneValidationState = { valid: false, checking: false, formatValid: false };
        emailValidationState = { valid: false, checking: false, formatValid: false };
    }

    // Copy to clipboard with fallback
    $(document).on('click', '.copy-btn', function(e) {
        e.preventDefault();
        e.stopPropagation();
        const text = $(this).data('copy');
        copyToClipboard(text);
    });

    function copyToClipboard(text) {
        if (navigator.clipboard && window.isSecureContext) {
            navigator.clipboard.writeText(text).then(function() {
                toastr.success('Copied to clipboard!', 'Success!');
            }).catch(function() {
                fallbackCopyToClipboard(text);
            });
        } else {
            fallbackCopyToClipboard(text);
        }
    }

    function fallbackCopyToClipboard(text) {
        const textArea = document.createElement('textarea');
        textArea.value = text;
        textArea.style.position = 'fixed';
        textArea.style.left = '-999999px';
        textArea.style.top = '-999999px';
        document.body.appendChild(textArea);
        textArea.focus();
        textArea.select();
        try {
            document.execCommand('copy');
            toastr.success('Copied to clipboard!', 'Success!');
        } catch (err) {
            toastr.error('Failed to copy to clipboard', 'Error!');
        }
        document.body.removeChild(textArea);
    }

    // Reset form on modal close
    $('#clientModal').on('hidden.bs.modal', function() {
        resetForm();
    });

    // Toastr options
    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };
});
</script>
@endsection
