@extends('layouts.master')

@section('title') Add Affiliate @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.photo-preview-container {
    width: 120px;
    height: 120px;
    border: 2px dashed #dee2e6;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    cursor: pointer;
    transition: all 0.2s ease;
    overflow: hidden;
}
.photo-preview-container:hover {
    border-color: #556ee6;
    background-color: #f0f4ff;
}
.photo-preview-container img {
    width: 100%;
    height: 100%;
    object-fit: cover;
}
.photo-preview-container .placeholder-content {
    text-align: center;
    color: #adb5bd;
}
.photo-preview-container .placeholder-content i {
    font-size: 28px;
    margin-bottom: 4px;
}
.photo-preview-container.loading .placeholder-content,
.photo-preview-container.loading img {
    display: none !important;
}
.photo-preview-container.loading .upload-loader {
    display: block !important;
}
.upload-loader {
    display: none;
    text-align: center;
}
.upload-loader i {
    font-size: 28px;
    color: #556ee6;
}

/* Selection Tables */
.selection-table-container {
    max-height: 250px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
}
.selection-table {
    margin-bottom: 0;
}
.selection-table tbody tr {
    cursor: pointer;
    transition: background-color 0.15s ease;
}
.selection-table tbody tr:hover {
    background-color: #f8f9fa;
}
.selection-table tbody tr.selected {
    background-color: #e7f1ff !important;
}
.selection-table tbody tr.selected td {
    border-color: #b6d4fe;
}

/* Store checkboxes */
.store-checkbox-container {
    max-height: 200px;
    overflow-y: auto;
    border: 1px solid #dee2e6;
    border-radius: 0.375rem;
    padding: 0.5rem;
}
.store-checkbox-item {
    padding: 0.5rem;
    border-radius: 0.25rem;
    transition: background-color 0.15s ease;
}
.store-checkbox-item:hover {
    background-color: #f8f9fa;
}
.store-checkbox-item.selected {
    background-color: #e7f1ff;
}

/* Document preview */
.document-preview {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
}
.document-item {
    position: relative;
    display: inline-block;
    margin: 5px;
}
.document-remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #dc3545;
    color: white;
    border: none;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.document-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: #f8f9fa;
}
.document-upload-area:hover {
    border-color: #556ee6;
    background-color: #f0f4ff;
}
.document-upload-area i {
    font-size: 2rem;
    color: #adb5bd;
}

/* Selected info badges */
.selected-info {
    background-color: #e7f1ff;
    border: 1px solid #b6d4fe;
    padding: 0.5rem 0.75rem;
    border-radius: 0.375rem;
    display: inline-flex;
    align-items: center;
    gap: 0.5rem;
}

/* Document upload area */
.document-upload-area {
    border: 2px dashed #dee2e6;
    border-radius: 0.375rem;
    padding: 1.5rem;
    text-align: center;
    cursor: pointer;
    transition: all 0.2s ease;
    background-color: #f8f9fa;
}
.document-upload-area:hover {
    border-color: #556ee6;
    background-color: #f0f4ff;
}
.document-upload-area i {
    font-size: 2rem;
    color: #adb5bd;
}

/* Document preview items */
.document-preview-item {
    position: relative;
    width: 80px;
    text-align: center;
}
.document-preview-item img {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
}
.document-preview-item .doc-icon {
    width: 80px;
    height: 80px;
    display: flex;
    align-items: center;
    justify-content: center;
    background-color: #f8f9fa;
    border-radius: 0.375rem;
    border: 1px solid #dee2e6;
}
.document-preview-item .doc-icon i {
    font-size: 2rem;
    color: #6c757d;
}
.document-preview-item .remove-btn {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 22px;
    height: 22px;
    border-radius: 50%;
    background: #dc3545;
    color: white;
    border: none;
    font-size: 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
}
.document-preview-item .doc-name {
    font-size: 10px;
    overflow: hidden;
    text-overflow: ellipsis;
    white-space: nowrap;
    margin-top: 4px;
}

/* Validation error highlight */
.is-invalid {
    border-color: #dc3545 !important;
}
.validation-error {
    color: #dc3545;
    font-size: 0.875em;
    margin-top: 0.25rem;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
    @slot('li_1') E-commerce @endslot
    @slot('li_2') <a href="{{ route('ecom-affiliates') }}">Affiliates</a> @endslot
    @slot('title') Add Affiliate @endslot
@endcomponent

<!-- Flash Messages -->
@if(session('error'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

@if($errors->has('payment'))
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="bx bx-error-circle me-2"></i>{{ $errors->first('payment') }}
        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
    </div>
@endif

<form action="{{ route('ecom-affiliates.store') }}" method="POST" enctype="multipart/form-data" id="affiliateForm">
    @csrf

    <div class="row">
        <div class="col-lg-8">
            <!-- Client Selection Card -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bx bx-user-check me-2"></i>Link to Existing Client (Optional)</h5>
                    <p class="text-muted small mb-3">Select a client from the table below to auto-fill their details. Click on a row to select.</p>

                    <!-- Search -->
                    <div class="mb-3">
                        <input type="text" class="form-control" id="clientSearch" placeholder="Search clients by name or phone...">
                    </div>

                    <!-- Selected Client Display -->
                    <div class="mb-3 d-none" id="selectedClientInfo">
                        <div class="selected-info">
                            <i class="bx bx-user-check text-primary"></i>
                            <span id="selectedClientName"></span>
                            <button type="button" class="btn-close btn-sm" id="clearClientSelection" aria-label="Clear"></button>
                        </div>
                    </div>

                    <!-- Client Table -->
                    <div class="selection-table-container">
                        <table class="table table-sm selection-table" id="clientsTable">
                            <thead class="table-light sticky-top">
                                <tr>
                                    <th>Name</th>
                                    <th>Phone</th>
                                    <th>Email</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($clients as $client)
                                <tr data-client-id="{{ $client->id }}"
                                    data-firstname="{{ $client->clientFirstName }}"
                                    data-middlename="{{ $client->clientMiddleName }}"
                                    data-lastname="{{ $client->clientLastName }}"
                                    data-phone="{{ $client->clientPhoneNumber }}"
                                    data-email="{{ $client->clientEmailAddress }}">
                                    <td>{{ $client->full_name }}</td>
                                    <td>{{ $client->clientPhoneNumber }}</td>
                                    <td>{{ $client->clientEmailAddress ?: '-' }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted">No available clients</td>
                                </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <input type="hidden" name="clientId" id="clientId" value="{{ old('clientId') }}">
                </div>
            </div>

            <!-- Personal Information Card -->
            <div class="card">
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-9">
                            <h5 class="card-title mb-3"><i class="bx bx-user me-2"></i>Personal Information</h5>

                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="firstName" class="form-label">First Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('firstName') is-invalid @enderror"
                                               id="firstName" name="firstName" value="{{ old('firstName') }}"
                                               placeholder="Enter first name" required>
                                        @error('firstName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="middleName" class="form-label">Middle Name</label>
                                        <input type="text" class="form-control @error('middleName') is-invalid @enderror"
                                               id="middleName" name="middleName" value="{{ old('middleName') }}"
                                               placeholder="Enter middle name">
                                        @error('middleName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="lastName" class="form-label">Last Name <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('lastName') is-invalid @enderror"
                                               id="lastName" name="lastName" value="{{ old('lastName') }}"
                                               placeholder="Enter last name" required>
                                        @error('lastName')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="phoneNumber" class="form-label">Phone Number <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control @error('phoneNumber') is-invalid @enderror"
                                               id="phoneNumber" name="phoneNumber" value="{{ old('phoneNumber') }}"
                                               placeholder="Enter phone number" required>
                                        @error('phoneNumber')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="emailAddress" class="form-label">Email Address</label>
                                        <input type="email" class="form-control @error('emailAddress') is-invalid @enderror"
                                               id="emailAddress" name="emailAddress" value="{{ old('emailAddress') }}"
                                               placeholder="Enter email address">
                                        @error('emailAddress')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-3 text-center">
                            <label class="form-label">Photo</label>
                            <div class="photo-preview-container mx-auto" id="photoPreviewContainer" onclick="document.getElementById('userPhoto').click()">
                                <div class="placeholder-content" id="photoPlaceholder">
                                    <i class="bx bx-camera d-block"></i>
                                    <span class="small">Upload</span>
                                </div>
                                <div class="upload-loader" id="uploadLoader">
                                    <i class="bx bx-loader-alt bx-spin d-block"></i>
                                </div>
                                <img src="" alt="Photo Preview" id="photoPreview" class="d-none">
                            </div>
                            <input type="file" class="d-none" id="userPhoto" name="userPhoto" accept="image/jpeg,image/png,image/jpg,image/gif">
                            <button type="button" class="btn btn-sm btn-outline-danger mt-2 d-none" id="removePhoto">
                                <i class="bx bx-trash"></i> Remove
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Payment Information Card -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bx bx-wallet me-2"></i>Payment Information</h5>
                    <p class="text-muted small mb-3">At least one payment method is required (Bank or GCash).</p>

                    <div class="row">
                        <div class="col-md-8">
                            <div class="card bg-light mb-3">
                                <div class="card-body py-3">
                                    <h6 class="mb-3">Bank Details</h6>
                                    <div class="row">
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <label for="bankName" class="form-label small">Bank Name</label>
                                                <input type="text" class="form-control form-control-sm" id="bankName" name="bankName"
                                                       value="{{ old('bankName') }}" placeholder="e.g., BDO, BPI">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <label for="bankAccountNumber" class="form-label small">Account Number</label>
                                                <input type="text" class="form-control form-control-sm" id="bankAccountNumber" name="bankAccountNumber"
                                                       value="{{ old('bankAccountNumber') }}" placeholder="Account number">
                                            </div>
                                        </div>
                                        <div class="col-md-4">
                                            <div class="mb-2">
                                                <label for="bankAccountName" class="form-label small">Account Name</label>
                                                <input type="text" class="form-control form-control-sm" id="bankAccountName" name="bankAccountName"
                                                       value="{{ old('bankAccountName') }}" placeholder="Name on account">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="mb-3">
                                <label for="gcashNumber" class="form-label">GCash Number</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="bx bx-wallet"></i></span>
                                    <input type="text" class="form-control" id="gcashNumber" name="gcashNumber"
                                           value="{{ old('gcashNumber') }}" placeholder="09123456789">
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Store Assignment Card -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bx bx-store me-2"></i>Store Assignment <span class="text-danger">*</span></h5>
                    <p class="text-muted small mb-3">Select one or more stores this affiliate will be associated with.</p>

                    @error('stores')
                        <div class="alert alert-danger py-2">{{ $message }}</div>
                    @enderror

                    <div class="store-checkbox-container">
                        @foreach($stores as $store)
                        <div class="store-checkbox-item {{ in_array($store->id, old('stores', [])) ? 'selected' : '' }}">
                            <div class="form-check">
                                <input class="form-check-input store-checkbox" type="checkbox"
                                       name="stores[]" value="{{ $store->id }}" id="store{{ $store->id }}"
                                       {{ in_array($store->id, old('stores', [])) ? 'checked' : '' }}>
                                <label class="form-check-label w-100" for="store{{ $store->id }}">
                                    <strong>{{ $store->storeName }}</strong>
                                    @if($store->storeDescription)
                                        <br><small class="text-muted">{{ Str::limit($store->storeDescription, 60) }}</small>
                                    @endif
                                </label>
                            </div>
                        </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Documents Card -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bx bx-file me-2"></i>Documents / IDs</h5>
                    <p class="text-muted small mb-3">Upload ID documents, certificates, or other files for this affiliate.</p>

                    <!-- Document Upload Area -->
                    <div class="document-upload-area" onclick="document.getElementById('documentFiles').click()">
                        <i class="bx bx-cloud-upload d-block mb-2"></i>
                        <p class="mb-0 text-muted">Click to upload documents</p>
                        <small class="text-muted">Max 5MB each. Formats: JPG, PNG, PDF, DOC</small>
                    </div>
                    <input type="file" class="d-none" id="documentFiles" name="documents[]" multiple accept="image/jpeg,image/png,image/jpg,application/pdf,.doc,.docx">

                    <!-- Documents Preview -->
                    <div id="documentsPreview" class="mt-3 d-flex flex-wrap gap-2"></div>
                </div>
            </div>

            <!-- Account Settings Card -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bx bx-cog me-2"></i>Account Settings</h5>

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="expirationDate" class="form-label">Expiration Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control @error('expirationDate') is-invalid @enderror" id="expirationDate" name="expirationDate"
                                       value="{{ old('expirationDate') }}" required>
                                @error('expirationDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                                <small class="text-muted">When will this affiliate's account expire?</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="accountStatus" class="form-label">Account Status <span class="text-danger">*</span></label>
                                <select class="form-select" id="accountStatus" name="accountStatus" required>
                                    <option value="active" {{ old('accountStatus', 'active') == 'active' ? 'selected' : '' }}>Active</option>
                                    <option value="inactive" {{ old('accountStatus') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <!-- Tips Card -->
            <div class="card">
                <div class="card-body">
                    <h5 class="card-title mb-3"><i class="bx bx-info-circle me-2 text-info"></i>Tips</h5>
                    <ul class="text-muted mb-0 small" style="padding-left: 1.2rem;">
                        <li class="mb-2">Click on a client row to link and auto-fill their details</li>
                        <li class="mb-2">At least one payment method (Bank or GCash) is required</li>
                        <li class="mb-2">Select one or more stores for the affiliate</li>
                        <li class="mb-2">Upload IDs and documents before creating</li>
                        <li class="mb-2">Expiration date is required</li>
                        <li>Expired affiliates will be flagged but remain accessible</li>
                    </ul>
                </div>
            </div>

            <!-- Submit Actions -->
            <div class="card">
                <div class="card-body">
                    <div class="d-grid gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i>Create Affiliate
                        </button>
                        <a href="{{ route('ecom-affiliates') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i>Cancel
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

@endsection

@section('script')
<!-- DataTables -->
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
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

    // ========================================
    // Dynamic Form Validation
    // ========================================

    // Phone number formatting (Philippine format: 09XX XXX XXXX - 11 digits)
    $('#phoneNumber, #gcashNumber').on('input', function() {
        let value = $(this).val().replace(/\D/g, ''); // Remove non-digits

        // Handle +63 prefix - convert to 0
        if (value.startsWith('63')) {
            value = '0' + value.substring(2);
        }

        // Limit to 11 digits
        if (value.length > 11) {
            value = value.substring(0, 11);
        }

        // Format: 09XX XXX XXXX (4-3-4)
        if (value.length > 0) {
            if (value.length <= 4) {
                value = value;
            } else if (value.length <= 7) {
                value = value.substring(0, 4) + ' ' + value.substring(4);
            } else {
                value = value.substring(0, 4) + ' ' + value.substring(4, 7) + ' ' + value.substring(7);
            }
        }

        $(this).val(value);
        validatePhoneNumber($(this));
    });

    function validatePhoneNumber($input) {
        const value = $input.val().replace(/\s/g, '');
        // Philippine mobile: 09XX XXX XXXX = 11 digits starting with 09
        const isValid = /^09\d{9}$/.test(value) || value === '';
        const $container = $input.closest('.mb-3, .input-group').parent();

        // Remove existing error
        $container.find('.validation-error').remove();

        if (!isValid && value.length > 0) {
            $input.addClass('is-invalid');
            if (value.length < 11) {
                $input.after('<div class="validation-error">Phone number must be 11 digits (e.g., 09559958833)</div>');
            } else {
                $input.after('<div class="validation-error">Please enter a valid Philippine phone number starting with 09</div>');
            }
        } else {
            $input.removeClass('is-invalid');
        }
        return isValid || value === '';
    }

    // Email validation
    $('#emailAddress').on('input blur', function() {
        validateEmail($(this));
    });

    function validateEmail($input) {
        const value = $input.val().trim();
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        const isValid = emailRegex.test(value) || value === '';

        // Remove existing error
        $input.siblings('.validation-error').remove();

        if (!isValid && value.length > 0) {
            $input.addClass('is-invalid');
            $input.after('<div class="validation-error">Please enter a valid email address</div>');
        } else {
            $input.removeClass('is-invalid');
        }
        return isValid || value === '';
    }

    // Expiration date validation
    function validateExpirationDate() {
        const $input = $('#expirationDate');
        const value = $input.val();
        const isValid = value !== '';

        $input.siblings('.validation-error').remove();

        if (!isValid) {
            $input.addClass('is-invalid');
            $input.after('<div class="validation-error">Expiration date is required</div>');
        } else {
            $input.removeClass('is-invalid');
        }
        return isValid;
    }

    $('#expirationDate').on('change blur', function() {
        validateExpirationDate();
    });

    // Bank account number formatting (numbers only, with spaces every 4 digits)
    $('#bankAccountNumber').on('input', function() {
        let value = $(this).val().replace(/\D/g, '');

        // Format with spaces every 4 digits
        if (value.length > 0) {
            value = value.match(/.{1,4}/g).join(' ');
        }

        $(this).val(value);
    });

    // Bank name validation (letters, spaces, and common characters)
    $('#bankName').on('input', function() {
        let value = $(this).val().replace(/[^a-zA-Z\s\-\.]/g, '');
        $(this).val(value);
    });

    // Name fields validation (letters, spaces, hyphens, apostrophes)
    $('#firstName, #middleName, #lastName, #bankAccountName').on('input', function() {
        let value = $(this).val().replace(/[^a-zA-ZÀ-ÿ\s\-\'\.]/g, '');
        $(this).val(value);
    });

    // Form submission validation
    $('#affiliateForm').on('submit', function(e) {
        let isValid = true;
        let firstError = null;

        // Validate phone number
        if (!validatePhoneNumber($('#phoneNumber'))) {
            isValid = false;
            if (!firstError) firstError = $('#phoneNumber');
        }

        // Validate GCash if filled
        if ($('#gcashNumber').val().trim() !== '' && !validatePhoneNumber($('#gcashNumber'))) {
            isValid = false;
            if (!firstError) firstError = $('#gcashNumber');
        }

        // Validate email if filled
        if (!validateEmail($('#emailAddress'))) {
            isValid = false;
            if (!firstError) firstError = $('#emailAddress');
        }

        // Validate expiration date
        if (!validateExpirationDate()) {
            isValid = false;
            if (!firstError) firstError = $('#expirationDate');
        }

        // Validate at least one payment method
        const hasBankDetails = $('#bankName').val().trim() !== '' &&
                               $('#bankAccountNumber').val().trim() !== '' &&
                               $('#bankAccountName').val().trim() !== '';
        const hasGcash = $('#gcashNumber').val().replace(/\s/g, '').length === 11;

        if (!hasBankDetails && !hasGcash) {
            toastr.error('Please provide at least one payment method (complete bank details or GCash number)', 'Validation Error');
            // Highlight payment fields
            if (!$('#bankName').val().trim()) $('#bankName').addClass('is-invalid');
            if (!$('#bankAccountNumber').val().trim()) $('#bankAccountNumber').addClass('is-invalid');
            if (!$('#bankAccountName').val().trim()) $('#bankAccountName').addClass('is-invalid');
            if (!hasGcash) $('#gcashNumber').addClass('is-invalid');
            isValid = false;
        }

        // Validate at least one store selected
        if ($('.store-checkbox:checked').length === 0) {
            toastr.error('Please select at least one store', 'Validation Error');
            $('.store-checkbox-container').css('border-color', '#dc3545');
            isValid = false;
        } else {
            $('.store-checkbox-container').css('border-color', '#dee2e6');
        }

        if (!isValid) {
            e.preventDefault();
            // Scroll to first error
            if (firstError) {
                $('html, body').animate({
                    scrollTop: firstError.offset().top - 100
                }, 300);
                firstError.focus();
            }
        }
    });

    // ========================================
    // Client Table Selection
    // ========================================

    // Client table search
    $('#clientSearch').on('keyup', function() {
        const searchText = $(this).val().toLowerCase();
        $('#clientsTable tbody tr').each(function() {
            const rowText = $(this).text().toLowerCase();
            $(this).toggle(rowText.indexOf(searchText) > -1);
        });
    });

    // Client row selection
    $('#clientsTable tbody').on('click', 'tr', function() {
        const $row = $(this);
        const clientId = $row.data('client-id');

        if (!clientId) return;

        // Toggle selection
        if ($row.hasClass('selected')) {
            // Deselect
            $row.removeClass('selected');
            $('#clientId').val('');
            $('#selectedClientInfo').addClass('d-none');
        } else {
            // Select
            $('#clientsTable tbody tr').removeClass('selected');
            $row.addClass('selected');
            $('#clientId').val(clientId);

            // Auto-fill fields
            $('#firstName').val($row.data('firstname') || '');
            $('#middleName').val($row.data('middlename') || '');
            $('#lastName').val($row.data('lastname') || '');

            // Format phone number (11 digits: 09XX XXX XXXX)
            let phone = ($row.data('phone') || '').toString().replace(/\D/g, '');
            if (phone.startsWith('63')) phone = '0' + phone.substring(2);
            if (phone.length === 11) {
                phone = phone.substring(0, 4) + ' ' + phone.substring(4, 7) + ' ' + phone.substring(7);
            }
            $('#phoneNumber').val(phone);

            $('#emailAddress').val($row.data('email') || '');

            // Show selected info
            const fullName = [$row.data('firstname'), $row.data('middlename'), $row.data('lastname')]
                .filter(Boolean).join(' ');
            $('#selectedClientName').text(fullName);
            $('#selectedClientInfo').removeClass('d-none');

            toastr.info('Client details have been auto-filled.', 'Info');
        }
    });

    // Clear client selection
    $('#clearClientSelection').on('click', function() {
        $('#clientsTable tbody tr').removeClass('selected');
        $('#clientId').val('');
        $('#selectedClientInfo').addClass('d-none');
    });

    // ========================================
    // Store Checkbox Selection
    // ========================================

    $('.store-checkbox').on('change', function() {
        $(this).closest('.store-checkbox-item').toggleClass('selected', $(this).is(':checked'));
    });

    // ========================================
    // Photo Upload Handler
    // ========================================

    $('#userPhoto').on('change', function() {
        const file = this.files[0];
        if (file) {
            if (file.size > 2 * 1024 * 1024) {
                toastr.error('File size must be less than 2MB', 'Error!');
                this.value = '';
                return;
            }

            const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'image/gif'];
            if (!validTypes.includes(file.type)) {
                toastr.error('Invalid file type. Please upload an image.', 'Error!');
                this.value = '';
                return;
            }

            $('#photoPreviewContainer').addClass('loading');

            const reader = new FileReader();
            reader.onload = function(e) {
                const img = new Image();
                img.onload = function() {
                    $('#photoPreview').attr('src', e.target.result).removeClass('d-none');
                    $('#photoPlaceholder').addClass('d-none');
                    $('#removePhoto').removeClass('d-none');
                    $('#photoPreviewContainer').removeClass('loading');
                };
                img.src = e.target.result;
            };
            reader.readAsDataURL(file);
        }
    });

    // Remove photo handler
    $('#removePhoto').on('click', function(e) {
        e.stopPropagation();
        $('#userPhoto').val('');
        $('#photoPreview').addClass('d-none').attr('src', '');
        $('#photoPlaceholder').removeClass('d-none');
        $(this).addClass('d-none');
    });

    // ========================================
    // Document Upload Handler
    // ========================================

    // Store files in a DataTransfer object to maintain file list
    let documentDataTransfer = new DataTransfer();

    $('#documentFiles').on('change', function() {
        const files = Array.from(this.files);
        const maxSize = 5 * 1024 * 1024; // 5MB
        const validTypes = ['image/jpeg', 'image/png', 'image/jpg', 'application/pdf', 'application/msword', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document'];

        files.forEach((file, index) => {
            if (file.size > maxSize) {
                toastr.error(`${file.name} is too large. Max 5MB allowed.`, 'Error!');
                return;
            }

            if (!validTypes.includes(file.type)) {
                toastr.error(`${file.name} has invalid file type.`, 'Error!');
                return;
            }

            // Add to DataTransfer
            documentDataTransfer.items.add(file);

            // Create preview
            addDocumentPreview(file, documentDataTransfer.files.length - 1);
        });

        // Update input files
        this.files = documentDataTransfer.files;
    });

    function addDocumentPreview(file, index) {
        const isImage = file.type.startsWith('image/');
        const isPdf = file.type === 'application/pdf';
        const isDoc = file.type.includes('msword') || file.type.includes('wordprocessingml');

        let iconClass = 'bx-file-blank';
        if (isPdf) iconClass = 'bx-file';
        if (isDoc) iconClass = 'bx-file';

        if (isImage) {
            const reader = new FileReader();
            reader.onload = function(e) {
                const html = `
                    <div class="document-preview-item" data-index="${index}">
                        <img src="${e.target.result}" alt="${file.name}">
                        <button type="button" class="remove-btn" onclick="removeDocument(${index})">
                            <i class="bx bx-x"></i>
                        </button>
                        <div class="doc-name" title="${file.name}">${file.name}</div>
                    </div>
                `;
                $('#documentsPreview').append(html);
            };
            reader.readAsDataURL(file);
        } else {
            const html = `
                <div class="document-preview-item" data-index="${index}">
                    <div class="doc-icon">
                        <i class="bx ${iconClass}"></i>
                    </div>
                    <button type="button" class="remove-btn" onclick="removeDocument(${index})">
                        <i class="bx bx-x"></i>
                    </button>
                    <div class="doc-name" title="${file.name}">${file.name}</div>
                </div>
            `;
            $('#documentsPreview').append(html);
        }
    }
});

// Global function to remove document
function removeDocument(index) {
    // Create new DataTransfer without the removed file
    const newDataTransfer = new DataTransfer();
    const files = document.getElementById('documentFiles').files;

    for (let i = 0; i < files.length; i++) {
        if (i !== index) {
            newDataTransfer.items.add(files[i]);
        }
    }

    // Update input and DataTransfer
    document.getElementById('documentFiles').files = newDataTransfer.files;

    // Remove preview and re-index
    $('#documentsPreview').empty();
    for (let i = 0; i < newDataTransfer.files.length; i++) {
        addDocumentPreviewGlobal(newDataTransfer.files[i], i);
    }

    // Update global reference
    documentDataTransfer = newDataTransfer;
}

// Global version of addDocumentPreview
function addDocumentPreviewGlobal(file, index) {
    const isImage = file.type.startsWith('image/');
    const isPdf = file.type === 'application/pdf';
    const isDoc = file.type.includes('msword') || file.type.includes('wordprocessingml');

    let iconClass = 'bx-file-blank';
    if (isPdf) iconClass = 'bx-file';
    if (isDoc) iconClass = 'bx-file';

    if (isImage) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const html = `
                <div class="document-preview-item" data-index="${index}">
                    <img src="${e.target.result}" alt="${file.name}">
                    <button type="button" class="remove-btn" onclick="removeDocument(${index})">
                        <i class="bx bx-x"></i>
                    </button>
                    <div class="doc-name" title="${file.name}">${file.name}</div>
                </div>
            `;
            $('#documentsPreview').append(html);
        };
        reader.readAsDataURL(file);
    } else {
        const html = `
            <div class="document-preview-item" data-index="${index}">
                <div class="doc-icon">
                    <i class="bx ${iconClass}"></i>
                </div>
                <button type="button" class="remove-btn" onclick="removeDocument(${index})">
                    <i class="bx bx-x"></i>
                </button>
                <div class="doc-name" title="${file.name}">${file.name}</div>
            </div>
        `;
        $('#documentsPreview').append(html);
    }
}

// Global DataTransfer reference
var documentDataTransfer = new DataTransfer();
</script>
@endsection
