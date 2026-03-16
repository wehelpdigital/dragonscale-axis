@extends('layouts.master')

@section('title') Access Logins - {{ $store->storeName }} @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.login-card {
    transition: all 0.2s ease;
    border: 1px solid #e9ecef;
}

.login-card:hover {
    box-shadow: 0 4px 12px rgba(0,0,0,0.1);
    transform: translateY(-2px);
}

.credential-field {
    background-color: #f8f9fa;
    border-radius: 4px;
    padding: 8px 12px;
    font-family: monospace;
    font-size: 13px;
    word-break: break-all;
}

.copy-btn {
    cursor: pointer;
    color: #6c757d;
    transition: color 0.2s;
}

.copy-btn:hover {
    color: #556ee6;
}

.empty-state {
    padding: 60px 20px;
    text-align: center;
}

.empty-state i {
    font-size: 4rem;
    color: #adb5bd;
    margin-bottom: 1rem;
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
}

.badge-style:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}

.client-avatar {
    width: 48px;
    height: 48px;
    border-radius: 50%;
    background: linear-gradient(135deg, #556ee6 0%, #34c38f 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-weight: 600;
    font-size: 16px;
}

.table-logins th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 12px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.table-logins td {
    vertical-align: middle;
}

/* Password Strength Indicator */
.password-strength .progress {
    background-color: #e9ecef;
    border-radius: 3px;
}

.password-strength .progress-bar.strength-weak {
    background-color: #dc3545;
}

.password-strength .progress-bar.strength-fair {
    background-color: #fd7e14;
}

.password-strength .progress-bar.strength-good {
    background-color: #ffc107;
}

.password-strength .progress-bar.strength-strong {
    background-color: #28a745;
}

.password-requirements small {
    font-size: 11px;
    color: #6c757d;
}

.password-requirements .bx-check {
    color: #28a745 !important;
}

.password-requirements .bx-x {
    color: #dc3545 !important;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') <a href="{{ route('ecom-stores') }}">Stores</a> @endslot
@slot('title') Access Logins - {{ $store->storeName }} @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Access Logins</h4>
                        <p class="text-secondary mb-0">Manage client access accounts for {{ $store->storeName }}</p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('ecom-stores') }}" class="btn btn-outline-secondary">
                            <i class="bx bx-arrow-back me-1"></i> Back to Stores
                        </a>
                        <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#loginModal">
                            <i class="bx bx-plus me-1"></i> Add Access Login
                        </button>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-4">
                    <div class="col-md-5">
                        <div class="input-group">
                            <input type="text" class="form-control" id="searchInput"
                                   placeholder="Search by name, phone, or email..."
                                   value="{{ request('search') }}">
                            <button class="btn btn-outline-secondary" type="button" id="searchBtn">
                                <i class="bx bx-search"></i>
                            </button>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="1" {{ request('status') === '1' ? 'selected' : '' }}>Active</option>
                            <option value="0" {{ request('status') === '0' ? 'selected' : '' }}>Inactive</option>
                        </select>
                    </div>
                    <div class="col-md-4 text-end">
                        @if(request('search') || request('status'))
                            <a href="{{ route('ecom-store-logins', ['id' => $store->id]) }}" class="btn btn-outline-danger">
                                <i class="bx bx-x me-1"></i> Clear Filters
                            </a>
                        @endif
                        <span class="badge bg-secondary ms-2">{{ $logins->count() }} {{ Str::plural('login', $logins->count()) }}</span>
                    </div>
                </div>

                <!-- Logins Table -->
                @if($logins->count() > 0)
                    <div class="table-responsive">
                        <table class="table table-hover table-logins mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 60px;"></th>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                    <th style="width: 100px;">Status</th>
                                    <th style="width: 180px;" class="text-center">Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($logins as $login)
                                <tr>
                                    <td>
                                        <div class="client-avatar">
                                            {{ strtoupper(substr($login->clientFirstName, 0, 1)) }}{{ strtoupper(substr($login->clientLastName, 0, 1)) }}
                                        </div>
                                    </td>
                                    <td>
                                        <h6 class="mb-0 text-dark">
                                            {{ $login->clientFirstName }}
                                            @if($login->clientMiddleName)
                                                {{ $login->clientMiddleName }}
                                            @endif
                                            {{ $login->clientLastName }}
                                        </h6>
                                        <small class="text-secondary">ID: {{ $login->id }}</small>
                                    </td>
                                    <td>
                                        @if($login->clientPhoneNumber)
                                            <span class="text-dark">{{ $login->clientPhoneNumber }}</span>
                                            <i class="bx bx-copy copy-btn ms-1" data-copy="{{ $login->clientPhoneNumber }}" title="Copy"></i>
                                        @else
                                            <span class="text-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($login->clientEmailAddress)
                                            <span class="text-dark">{{ $login->clientEmailAddress }}</span>
                                            <i class="bx bx-copy copy-btn ms-1" data-copy="{{ $login->clientEmailAddress }}" title="Copy"></i>
                                        @else
                                            <span class="text-secondary">-</span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($login->isActive)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td class="text-center">
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-primary edit-login"
                                                    data-login-id="{{ $login->id }}" title="Edit">
                                                <i class="bx bx-edit"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-warning toggle-status"
                                                    data-login-id="{{ $login->id }}"
                                                    data-login-name="{{ $login->clientFirstName }} {{ $login->clientLastName }}"
                                                    data-is-active="{{ $login->isActive ? '1' : '0' }}"
                                                    title="{{ $login->isActive ? 'Disable' : 'Enable' }}">
                                                <i class="bx bx-{{ $login->isActive ? 'block' : 'check' }}"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger delete-login"
                                                    data-login-id="{{ $login->id }}"
                                                    data-login-name="{{ $login->clientFirstName }} {{ $login->clientLastName }}"
                                                    title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @else
                    <div class="empty-state">
                        <div class="d-flex flex-column align-items-center justify-content-center">
                            <i class="bx bx-user-plus"></i>
                            <h5 class="text-dark">No Access Logins Found</h5>
                            <p class="text-secondary mb-0">
                                @if(request('search') || request('status'))
                                    No logins match your current filters.
                                @else
                                    Start by adding access login accounts for this store.
                                @endif
                            </p>
                            @if(request('search') || request('status'))
                                <a href="{{ route('ecom-store-logins', ['id' => $store->id]) }}" class="btn btn-outline-secondary mt-3">
                                    <i class="bx bx-x me-1"></i> Clear Filters
                                </a>
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Add/Edit Login Modal -->
<div class="modal fade" id="loginModal" tabindex="-1" aria-labelledby="loginModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="loginModalLabel">
                    <i class="bx bx-user-plus text-primary me-2"></i>
                    <span id="modalTitle">Add Access Login</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="loginForm">
                    <input type="hidden" id="loginId" name="loginId">

                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label for="clientFirstName" class="form-label">First Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="clientFirstName" name="clientFirstName"
                                   placeholder="Enter first name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="clientMiddleName" class="form-label">Middle Name</label>
                            <input type="text" class="form-control" id="clientMiddleName" name="clientMiddleName"
                                   placeholder="Enter middle name">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label for="clientLastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="clientLastName" name="clientLastName"
                                   placeholder="Enter last name">
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="clientPhoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="clientPhoneNumber" name="clientPhoneNumber"
                                   placeholder="e.g., 09123456789" maxlength="11">
                            <div class="invalid-feedback" id="phoneError">This phone number already exists.</div>
                            <div class="valid-feedback" id="phoneSuccess">Phone number is available.</div>
                            <small class="text-secondary" id="phoneFormatHint">Format: 09XXXXXXXXX (11 digits)</small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="clientEmailAddress" class="form-label">Email Address <span class="text-danger">*</span></label>
                            <input type="email" class="form-control" id="clientEmailAddress" name="clientEmailAddress"
                                   placeholder="e.g., client@example.com">
                            <div class="invalid-feedback" id="emailError">This email address already exists.</div>
                            <div class="valid-feedback" id="emailSuccess">Email address is available.</div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="clientPassword" class="form-label">Password <span class="text-danger" id="passwordRequired">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="clientPassword" name="clientPassword"
                                       placeholder="Enter password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                                    <i class="bx bx-show"></i>
                                </button>
                            </div>
                            <!-- Password Strength Indicator -->
                            <div class="password-strength mt-2" id="passwordStrengthContainer" style="display: none;">
                                <div class="d-flex align-items-center mb-1">
                                    <small class="text-secondary me-2">Strength:</small>
                                    <div class="progress flex-grow-1" style="height: 6px;">
                                        <div class="progress-bar" id="passwordStrengthBar" role="progressbar" style="width: 0%"></div>
                                    </div>
                                    <small class="ms-2 fw-semibold" id="passwordStrengthText">-</small>
                                </div>
                                <div class="password-requirements">
                                    <small class="d-block"><i class="bx bx-x text-danger" id="reqLength"></i> At least 8 characters</small>
                                    <small class="d-block"><i class="bx bx-x text-danger" id="reqUpper"></i> Uppercase letter</small>
                                    <small class="d-block"><i class="bx bx-x text-danger" id="reqLower"></i> Lowercase letter</small>
                                    <small class="d-block"><i class="bx bx-x text-danger" id="reqNumber"></i> Number</small>
                                    <small class="d-block"><i class="bx bx-x text-danger" id="reqSpecial"></i> Special character (!@#$%^&*)</small>
                                </div>
                            </div>
                            <small class="text-secondary" id="passwordHint" style="display: none;">
                                Leave blank to keep existing password
                            </small>
                        </div>
                        <div class="col-md-6 mb-3">
                            <label for="clientPasswordConfirm" class="form-label">Confirm Password <span class="text-danger" id="confirmPasswordRequired">*</span></label>
                            <div class="input-group">
                                <input type="password" class="form-control" id="clientPasswordConfirm" name="clientPasswordConfirm"
                                       placeholder="Confirm password">
                                <button class="btn btn-outline-secondary" type="button" id="togglePasswordConfirm">
                                    <i class="bx bx-show"></i>
                                </button>
                            </div>
                            <div class="invalid-feedback" id="passwordMismatch">
                                Passwords do not match
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <button type="button" class="btn btn-outline-secondary w-100" id="generatePassword">
                                <i class="bx bx-key me-1"></i> Generate Password
                            </button>
                        </div>
                    </div>

                    <div class="alert alert-info mb-0">
                        <i class="bx bx-info-circle me-2"></i>
                        <small>This login will be associated with store: <strong>{{ $store->storeName }}</strong></small>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-primary" id="saveLogin">
                    <i class="bx bx-save me-1"></i> <span id="saveButtonText">Save Login</span>
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
                <p class="text-dark">Are you sure you want to delete this access login?</p>
                <p class="text-secondary"><strong>Client:</strong> <span id="deleteLoginName"></span></p>
                <p class="text-secondary small mb-0">This will remove the client's access to this store.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bx bx-trash me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Toggle Status Confirmation Modal -->
<div class="modal fade" id="toggleStatusModal" tabindex="-1" aria-labelledby="toggleStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="toggleStatusModalLabel">
                    <i class="bx bx-check-circle text-warning me-2" id="toggleStatusIcon"></i>
                    <span id="toggleStatusTitle">Confirm Status Change</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark" id="toggleStatusMessage">Are you sure you want to change this user's status?</p>
                <p class="text-secondary"><strong>Client:</strong> <span id="toggleLoginName"></span></p>
                <p class="text-secondary small mb-0" id="toggleStatusDescription"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-warning" id="confirmToggleStatus">
                    <i class="bx bx-check me-1" id="confirmToggleIcon"></i> <span id="confirmToggleText">Confirm</span>
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
// Toastr configuration
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000,
    extendedTimeOut: 1000,
    preventDuplicates: true
};

const storeId = {{ $store->id }};
const baseUrl = '{{ url("/") }}';

// Validation state
let phoneValidationState = { valid: true, checking: false };
let emailValidationState = { valid: true, checking: false };

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

// Phone format regex (09XXXXXXXXX - 11 digits starting with 09)
const phoneRegex = /^09\d{9}$/;

// Email format regex
const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

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

    let url = `${baseUrl}/ecom-store-logins/check-phone?id=${storeId}&phone=${encodeURIComponent(phone)}`;
    if (excludeId) {
        url += `&exclude_id=${excludeId}`;
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

    let url = `${baseUrl}/ecom-store-logins/check-email?id=${storeId}&email=${encodeURIComponent(email)}`;
    if (excludeId) {
        url += `&exclude_id=${excludeId}`;
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

// Password strength validation
let passwordStrengthState = { valid: false, score: 0 };

function checkPasswordStrength(password) {
    const requirements = {
        length: password.length >= 8,
        upper: /[A-Z]/.test(password),
        lower: /[a-z]/.test(password),
        number: /[0-9]/.test(password),
        special: /[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(password)
    };

    // Update requirement icons
    $('#reqLength').removeClass('bx-x bx-check text-danger text-success')
        .addClass(requirements.length ? 'bx-check text-success' : 'bx-x text-danger');
    $('#reqUpper').removeClass('bx-x bx-check text-danger text-success')
        .addClass(requirements.upper ? 'bx-check text-success' : 'bx-x text-danger');
    $('#reqLower').removeClass('bx-x bx-check text-danger text-success')
        .addClass(requirements.lower ? 'bx-check text-success' : 'bx-x text-danger');
    $('#reqNumber').removeClass('bx-x bx-check text-danger text-success')
        .addClass(requirements.number ? 'bx-check text-success' : 'bx-x text-danger');
    $('#reqSpecial').removeClass('bx-x bx-check text-danger text-success')
        .addClass(requirements.special ? 'bx-check text-success' : 'bx-x text-danger');

    // Calculate score (0-5)
    let score = Object.values(requirements).filter(Boolean).length;

    // Update progress bar
    const $bar = $('#passwordStrengthBar');
    const $text = $('#passwordStrengthText');

    $bar.removeClass('strength-weak strength-fair strength-good strength-strong');

    if (password.length === 0) {
        $bar.css('width', '0%');
        $text.text('-').removeClass('text-danger text-warning text-success');
        passwordStrengthState = { valid: false, score: 0 };
        return;
    }

    let width, strengthClass, strengthText, textClass;

    if (score <= 2) {
        width = '25%';
        strengthClass = 'strength-weak';
        strengthText = 'Weak';
        textClass = 'text-danger';
    } else if (score === 3) {
        width = '50%';
        strengthClass = 'strength-fair';
        strengthText = 'Fair';
        textClass = 'text-warning';
    } else if (score === 4) {
        width = '75%';
        strengthClass = 'strength-good';
        strengthText = 'Good';
        textClass = 'text-warning';
    } else {
        width = '100%';
        strengthClass = 'strength-strong';
        strengthText = 'Strong';
        textClass = 'text-success';
    }

    $bar.css('width', width).addClass(strengthClass);
    $text.text(strengthText).removeClass('text-danger text-warning text-success').addClass(textClass);

    // Password is valid if all requirements are met
    passwordStrengthState = {
        valid: Object.values(requirements).every(Boolean),
        score: score
    };
}

// Debounced validation functions
const debouncedPhoneValidation = debounce(function() {
    const phone = $('#clientPhoneNumber').val();
    const excludeId = $('#loginId').val() || null;
    validatePhone(phone, excludeId);
}, 500);

const debouncedEmailValidation = debounce(function() {
    const email = $('#clientEmailAddress').val();
    const excludeId = $('#loginId').val() || null;
    validateEmail(email, excludeId);
}, 500);

$(document).ready(function() {
    // Search functionality
    $('#searchBtn').on('click', function() {
        applyFilters();
    });

    $('#searchInput').on('keypress', function(e) {
        if (e.which === 13) {
            applyFilters();
        }
    });

    // Filter dropdown
    $('#statusFilter').on('change', function() {
        applyFilters();
    });

    function applyFilters() {
        const search = $('#searchInput').val();
        const status = $('#statusFilter').val();

        let url = new URL(window.location.href);
        url.searchParams.set('id', storeId);

        if (search) url.searchParams.set('search', search);
        else url.searchParams.delete('search');

        if (status !== '') url.searchParams.set('status', status);
        else url.searchParams.delete('status');

        window.location.href = url.toString();
    }

    // Toggle password visibility in form
    $('#togglePassword').on('click', function() {
        const input = $('#clientPassword');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bx-show').addClass('bx-hide');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bx-hide').addClass('bx-show');
        }
    });

    // Toggle confirm password visibility
    $('#togglePasswordConfirm').on('click', function() {
        const input = $('#clientPasswordConfirm');
        const icon = $(this).find('i');

        if (input.attr('type') === 'password') {
            input.attr('type', 'text');
            icon.removeClass('bx-show').addClass('bx-hide');
        } else {
            input.attr('type', 'password');
            icon.removeClass('bx-hide').addClass('bx-show');
        }
    });

    // Validate password match and strength on input
    $('#clientPassword').on('input', function() {
        const password = $(this).val();

        // Show/hide strength container
        if (password.length > 0) {
            $('#passwordStrengthContainer').show();
        } else {
            $('#passwordStrengthContainer').hide();
        }

        // Check password strength
        checkPasswordStrength(password);

        // Also validate match if confirm has value
        validatePasswordMatch();
    });

    $('#clientPasswordConfirm').on('input', function() {
        validatePasswordMatch();
    });

    // Phone number validation on blur and input
    $('#clientPhoneNumber').on('blur', function() {
        const phone = $(this).val();
        const excludeId = $('#loginId').val() || null;
        validatePhone(phone, excludeId);
    });

    $('#clientPhoneNumber').on('input', debouncedPhoneValidation);

    // Email validation on blur and input
    $('#clientEmailAddress').on('blur', function() {
        const email = $(this).val();
        const excludeId = $('#loginId').val() || null;
        validateEmail(email, excludeId);
    });

    $('#clientEmailAddress').on('input', debouncedEmailValidation);

    function validatePasswordMatch() {
        const password = $('#clientPassword').val();
        const confirmPassword = $('#clientPasswordConfirm').val();

        if (confirmPassword && password !== confirmPassword) {
            $('#clientPasswordConfirm').addClass('is-invalid');
            return false;
        } else {
            $('#clientPasswordConfirm').removeClass('is-invalid');
            return true;
        }
    }

    // Generate random strong password
    $('#generatePassword').on('click', function() {
        // Ensure generated password meets all requirements
        const upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        const lower = 'abcdefghjkmnpqrstuvwxyz';
        const numbers = '23456789';
        const special = '!@#$%^&*';

        // Ensure at least one of each type
        let password = '';
        password += upper.charAt(Math.floor(Math.random() * upper.length));
        password += lower.charAt(Math.floor(Math.random() * lower.length));
        password += numbers.charAt(Math.floor(Math.random() * numbers.length));
        password += special.charAt(Math.floor(Math.random() * special.length));

        // Fill remaining with mixed characters
        const allChars = upper + lower + numbers + special;
        for (let i = 0; i < 8; i++) {
            password += allChars.charAt(Math.floor(Math.random() * allChars.length));
        }

        // Shuffle the password
        password = password.split('').sort(() => Math.random() - 0.5).join('');

        $('#clientPassword').val(password).attr('type', 'text');
        $('#clientPasswordConfirm').val(password).attr('type', 'text');
        $('#togglePassword i').removeClass('bx-show').addClass('bx-hide');
        $('#togglePasswordConfirm i').removeClass('bx-show').addClass('bx-hide');
        $('#clientPasswordConfirm').removeClass('is-invalid');

        // Show strength indicator and check strength
        $('#passwordStrengthContainer').show();
        checkPasswordStrength(password);

        toastr.info('Strong password generated. Make sure to save it!', 'Generated');
    });

    // Reset modal on close
    $('#loginModal').on('hidden.bs.modal', function() {
        $('#loginForm')[0].reset();
        $('#loginId').val('');
        $('#modalTitle').text('Add Access Login');
        $('#saveButtonText').text('Save Login');
        $('#passwordHint').hide();
        $('#clientPassword').attr('type', 'password');
        $('#clientPasswordConfirm').attr('type', 'password').removeClass('is-invalid');
        $('#togglePassword i').removeClass('bx-hide').addClass('bx-show');
        $('#togglePasswordConfirm i').removeClass('bx-hide').addClass('bx-show');

        // Reset phone/email validation state (for new entries, these are required)
        phoneValidationState = { valid: false, checking: false, formatValid: false };
        emailValidationState = { valid: false, checking: false, formatValid: false };
        $('#clientPhoneNumber').removeClass('is-valid is-invalid');
        $('#clientEmailAddress').removeClass('is-valid is-invalid');

        // Reset password strength indicator
        passwordStrengthState = { valid: false, score: 0 };
        $('#passwordStrengthContainer').hide();
        $('#passwordStrengthBar').css('width', '0%').removeClass('strength-weak strength-fair strength-good strength-strong');
        $('#passwordStrengthText').text('-').removeClass('text-danger text-warning text-success');
        $('#reqLength, #reqUpper, #reqLower, #reqNumber, #reqSpecial')
            .removeClass('bx-check text-success').addClass('bx-x text-danger');

        // Show required asterisks for password (new entry)
        $('#passwordRequired, #confirmPasswordRequired').show();
    });

    // Save login
    $('#saveLogin').on('click', function() {
        const $btn = $(this);
        const originalText = $btn.html();
        const loginId = $('#loginId').val();
        const isEdit = loginId !== '';

        const firstName = $('#clientFirstName').val().trim();
        const lastName = $('#clientLastName').val().trim();
        const phone = $('#clientPhoneNumber').val().trim();
        const email = $('#clientEmailAddress').val().trim();
        const password = $('#clientPassword').val();
        const confirmPassword = $('#clientPasswordConfirm').val();

        // Validate required fields
        if (!firstName) {
            toastr.error('First name is required.', 'Error!');
            $('#clientFirstName').focus();
            return;
        }

        if (!lastName) {
            toastr.error('Last name is required.', 'Error!');
            $('#clientLastName').focus();
            return;
        }

        // Phone number validation
        if (!phone) {
            toastr.error('Phone number is required.', 'Error!');
            $('#clientPhoneNumber').addClass('is-invalid');
            $('#phoneError').text('Phone number is required.');
            $('#clientPhoneNumber').focus();
            return;
        }

        // Validate phone format
        const phoneFormatResult = validatePhoneFormat(phone);
        if (!phoneFormatResult.valid) {
            toastr.error(phoneFormatResult.message, 'Error!');
            $('#clientPhoneNumber').addClass('is-invalid');
            $('#phoneError').text(phoneFormatResult.message);
            $('#clientPhoneNumber').focus();
            return;
        }

        // Email validation
        if (!email) {
            toastr.error('Email address is required.', 'Error!');
            $('#clientEmailAddress').addClass('is-invalid');
            $('#emailError').text('Email address is required.');
            $('#clientEmailAddress').focus();
            return;
        }

        // Validate email format
        const emailFormatResult = validateEmailFormat(email);
        if (!emailFormatResult.valid) {
            toastr.error(emailFormatResult.message, 'Error!');
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
            toastr.error('Phone number already exists for this store.', 'Error!');
            $('#clientPhoneNumber').focus();
            return;
        }

        // Check if email uniqueness validation failed
        if (!emailValidationState.valid) {
            toastr.error('Email address already exists for this store.', 'Error!');
            $('#clientEmailAddress').focus();
            return;
        }

        // Password validation (required for new entries, optional for edit)
        if (!isEdit) {
            if (!password) {
                toastr.error('Password is required.', 'Error!');
                $('#clientPassword').focus();
                return;
            }

            // Check password strength
            if (!passwordStrengthState.valid) {
                toastr.error('Password does not meet all strength requirements.', 'Error!');
                $('#clientPassword').focus();
                $('#passwordStrengthContainer').show();
                return;
            }
        } else if (password) {
            // For edit, if password is provided, validate strength
            if (!passwordStrengthState.valid) {
                toastr.error('Password does not meet all strength requirements.', 'Error!');
                $('#clientPassword').focus();
                $('#passwordStrengthContainer').show();
                return;
            }
        }

        // Validate password match if password is being set
        if (password) {
            if (password !== confirmPassword) {
                $('#clientPasswordConfirm').addClass('is-invalid');
                toastr.error('Passwords do not match', 'Error!');
                return;
            }
        }

        const formData = {
            _token: '{{ csrf_token() }}',
            clientFirstName: $('#clientFirstName').val(),
            clientMiddleName: $('#clientMiddleName').val(),
            clientLastName: $('#clientLastName').val(),
            clientPhoneNumber: $('#clientPhoneNumber').val(),
            clientEmailAddress: $('#clientEmailAddress').val(),
            clientPassword: password
        };

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Saving...');

        let url = isEdit
            ? `${baseUrl}/ecom-store-logins/update?id=${storeId}&login_id=${loginId}`
            : `${baseUrl}/ecom-store-logins/store?id=${storeId}`;

        $.ajax({
            url: url,
            type: 'POST',
            data: formData,
            success: function(response) {
                if (response.success) {
                    $('#loginModal').modal('hide');
                    toastr.success(response.message, 'Success!');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while saving.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
            }
        });
    });

    // Edit login
    $('.edit-login').on('click', function(e) {
        e.preventDefault();
        const loginId = $(this).data('login-id');

        // Fetch login data
        $.ajax({
            url: `${baseUrl}/ecom-store-logins/show?id=${storeId}&login_id=${loginId}`,
            type: 'GET',
            success: function(response) {
                if (response.success) {
                    const login = response.login;

                    $('#loginId').val(login.id);
                    $('#clientFirstName').val(login.clientFirstName);
                    $('#clientMiddleName').val(login.clientMiddleName);
                    $('#clientLastName').val(login.clientLastName);
                    $('#clientPhoneNumber').val(login.clientPhoneNumber);
                    $('#clientEmailAddress').val(login.clientEmailAddress);
                    $('#clientPassword').val('');

                    $('#modalTitle').text('Edit Access Login');
                    $('#saveButtonText').text('Update Login');

                    // Mark existing phone and email as valid (they're already stored)
                    phoneValidationState = { valid: true, checking: false, formatValid: true };
                    emailValidationState = { valid: true, checking: false, formatValid: true };
                    $('#clientPhoneNumber').removeClass('is-invalid').addClass('is-valid');
                    $('#clientEmailAddress').removeClass('is-invalid').addClass('is-valid');

                    // Password is optional for edit - hide required asterisks
                    $('#passwordRequired, #confirmPasswordRequired').hide();
                    passwordStrengthState = { valid: true, score: 5 }; // Consider valid for edit (not changing password)

                    if (login.hasPassword) {
                        $('#passwordHint').show();
                    }

                    $('#loginModal').modal('show');
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error('Failed to load login details.', 'Error!');
            }
        });
    });

    // Delete login
    let loginToDelete = null;

    $('.delete-login').on('click', function(e) {
        e.preventDefault();
        loginToDelete = {
            id: $(this).data('login-id'),
            name: $(this).data('login-name')
        };

        $('#deleteLoginName').text(loginToDelete.name);
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function() {
        if (!loginToDelete) return;

        const $btn = $(this);
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: `${baseUrl}/ecom-store-logins/delete?id=${storeId}&login_id=${loginToDelete.id}`,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    toastr.success(response.message, 'Success!');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while deleting.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
                loginToDelete = null;
            }
        });
    });

    // Toggle status
    let loginToToggle = null;

    $('.toggle-status').on('click', function(e) {
        e.preventDefault();
        const loginId = $(this).data('login-id');
        const loginName = $(this).data('login-name');
        const isActive = $(this).data('is-active');
        const newStatus = isActive === '1' ? 'disable' : 'enable';

        loginToToggle = {
            id: loginId,
            name: loginName,
            isActive: isActive,
            action: newStatus
        };

        // Update modal content based on action
        $('#toggleLoginName').text(loginName);

        if (newStatus === 'disable') {
            $('#toggleStatusIcon').removeClass('bx-check-circle text-success').addClass('bx-block text-danger');
            $('#toggleStatusTitle').text('Disable Access Login');
            $('#toggleStatusMessage').text('Are you sure you want to disable this user?');
            $('#toggleStatusDescription').text('This user will no longer be able to access this store.');
            $('#confirmToggleStatus').removeClass('btn-success').addClass('btn-danger');
            $('#confirmToggleIcon').removeClass('bx-check').addClass('bx-block');
            $('#confirmToggleText').text('Disable');
        } else {
            $('#toggleStatusIcon').removeClass('bx-block text-danger').addClass('bx-check-circle text-success');
            $('#toggleStatusTitle').text('Enable Access Login');
            $('#toggleStatusMessage').text('Are you sure you want to enable this user?');
            $('#toggleStatusDescription').text('This user will be able to access this store again.');
            $('#confirmToggleStatus').removeClass('btn-danger').addClass('btn-success');
            $('#confirmToggleIcon').removeClass('bx-block').addClass('bx-check');
            $('#confirmToggleText').text('Enable');
        }

        $('#toggleStatusModal').modal('show');
    });

    $('#confirmToggleStatus').on('click', function() {
        if (!loginToToggle) return;

        const $btn = $(this);
        const originalText = $btn.html();

        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Processing...');

        $.ajax({
            url: `${baseUrl}/ecom-store-logins/toggle?id=${storeId}&login_id=${loginToToggle.id}`,
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#toggleStatusModal').modal('hide');
                    toastr.success(response.message, 'Success!');
                    setTimeout(function() {
                        location.reload();
                    }, 1000);
                } else {
                    toastr.error(response.message, 'Error!');
                }
            },
            error: function(xhr) {
                toastr.error('An error occurred while updating status.', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html(originalText);
                loginToToggle = null;
            }
        });
    });

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
});
</script>
@endsection
