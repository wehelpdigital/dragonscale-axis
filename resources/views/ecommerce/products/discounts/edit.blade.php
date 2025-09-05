@extends('layouts.master')

@section('title')
    Edit Discount
@endsection

@section('css')
    <!-- Add any specific CSS for edit discount page here -->
@endsection

@section('head')
    <meta name="csrf-token" content="{{ csrf_token() }}">
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            E-commerce
        @endslot
        @slot('li_2')
            Products
        @endslot
        @slot('li_3')
            Discounts
        @endslot
        @slot('li_4')
            Edit
        @endslot
        @slot('title')
            Edit Discount
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title">Edit Discount</h4>
                            <p class="card-title-desc">Edit discount for: <strong>{{ $product->productName }}</strong></p>
                        </div>
                        <a href="{{ route('ecom-products.discounts', ['id' => $product->id]) }}" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Discounts
                        </a>
                    </div>

                    @if(session('error'))
                        <div class="alert alert-danger alert-dismissible fade show" role="alert">
                            {{ session('error') }}
                            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                        </div>
                    @endif

                    <form id="discountForm" action="{{ route('ecom-products.discounts.update', ['id' => $discount->id]) }}" method="POST" novalidate>
                        @csrf
                        @method('PUT')
                        <input type="hidden" name="ecomProductsId" value="{{ $product->id }}">

                        <!-- Single Column Form Layout -->
                        <div class="row">
                            <div class="col-md-8 mx-auto">
                                <!-- Basic Information -->
                                <div class="mb-3">
                                    <label for="discountName" class="form-label">Discount Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('discountName') is-invalid @enderror"
                                           id="discountName" name="discountName"
                                           value="{{ old('discountName', $discount->discountName) }}">
                                    @error('discountName')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="discountType" class="form-label">Discount Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('discountType') is-invalid @enderror"
                                            id="discountType" name="discountType">
                                        <option value="">Select Discount Type</option>
                                        <option value="discount code" {{ old('discountType', $discount->discountType) == 'discount code' ? 'selected' : '' }}>Discount Code</option>
                                        <option value="auto apply" {{ old('discountType', $discount->discountType) == 'auto apply' ? 'selected' : '' }}>Auto Apply</option>
                                    </select>
                                    @error('discountType')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Discount Code Field -->
                                <div id="discountCodeField" class="mb-3" style="display: none;">
                                    <label for="discountCode" class="form-label">Discount Code to be Applied <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('discountCode') is-invalid @enderror"
                                           id="discountCode" name="discountCode"
                                           value="{{ old('discountCode', $discount->discountCode) }}" placeholder="Enter discount code">
                                    @error('discountCode')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="timerType" class="form-label">Timer Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('timerType') is-invalid @enderror"
                                            id="timerType" name="timerType">
                                        <option value="">Select Timer Type</option>
                                        <option value="cookie countdown" {{ old('timerType', $discount->timerType) == 'cookie countdown' ? 'selected' : '' }}>Cookie Countdown</option>
                                        <option value="date and time" {{ old('timerType', $discount->timerType) == 'date and time' ? 'selected' : '' }}>Date and Time</option>
                                        <option value="slots remaining" {{ old('timerType', $discount->timerType) == 'slots remaining' ? 'selected' : '' }}>Slots Remaining</option>
                                    </select>
                                    @error('timerType')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Cookie Countdown Fields -->
                                <div id="cookieCountdownFields" class="mb-3" style="display: none;">
                                    <div class="row">
                                        <div class="col-md-6">
                                            <label for="countdownValueDays" class="form-label">Countdown Value Days <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('countdownValueDays') is-invalid @enderror"
                                                   id="countdownValueDays" name="countdownValueDays"
                                                   value="{{ old('countdownValueDays', $discount->countdownValueDays) }}" min="0">
                                            @error('countdownValueDays')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                        <div class="col-md-6">
                                            <label for="countdownValueMinutes" class="form-label">Countdown Value Minutes <span class="text-danger">*</span></label>
                                            <input type="number" class="form-control @error('countdownValueMinutes') is-invalid @enderror"
                                                   id="countdownValueMinutes" name="countdownValueMinutes"
                                                   value="{{ old('countdownValueMinutes', $discount->countdownValueMinutes) }}" min="0">
                                            @error('countdownValueMinutes')
                                                <div class="invalid-feedback">{{ $message }}</div>
                                            @enderror
                                        </div>
                                    </div>
                                </div>

                                <!-- Date and Time Field -->
                                <div id="dateTimeField" class="mb-3" style="display: none;">
                                    <label for="scheduledEnding" class="form-label">Promo Ends Schedule <span class="text-danger">*</span></label>
                                    <input type="datetime-local" class="form-control @error('scheduledEnding') is-invalid @enderror"
                                           id="scheduledEnding" name="scheduledEnding"
                                           value="{{ old('scheduledEnding', $discount->scheduledEnding ? $discount->scheduledEnding->format('Y-m-d\TH:i') : '') }}">
                                    @error('scheduledEnding')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Slots Remaining Field -->
                                <div id="slotsRemainingField" class="mb-3" style="display: none;">
                                    <label for="slotsRemainingValue" class="form-label">How many slots: <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('slotsRemainingValue') is-invalid @enderror"
                                           id="slotsRemainingValue" name="slotsRemainingValue"
                                           value="{{ old('slotsRemainingValue', $discount->slotsRemainingValue) }}" min="0">
                                    @error('slotsRemainingValue')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="discountValueType" class="form-label">Discount Value Type <span class="text-danger">*</span></label>
                                    <select class="form-select @error('discountValueType') is-invalid @enderror"
                                            id="discountValueType" name="discountValueType">
                                        <option value="">Select Discount Value Type</option>
                                        <option value="percentage" {{ old('discountValueType', $discount->discountValueType) == 'percentage' ? 'selected' : '' }}>Percentage</option>
                                        <option value="discount amount" {{ old('discountValueType', $discount->discountValueType) == 'discount amount' ? 'selected' : '' }}>Discount Amount</option>
                                        <option value="price change" {{ old('discountValueType', $discount->discountValueType) == 'price change' ? 'selected' : '' }}>Price Change</option>
                                    </select>
                                    @error('discountValueType')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Percentage Field -->
                                <div id="percentageField" class="mb-3" style="display: none;">
                                    <label for="discountValuePercentage" class="form-label">Discount Value Percentage <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <input type="number" class="form-control @error('discountValuePercentage') is-invalid @enderror"
                                               id="discountValuePercentage" name="discountValuePercentage"
                                               value="{{ old('discountValuePercentage', $discount->discountValuePercentage) }}" min="0" max="100" step="0.01">
                                        <span class="input-group-text">%</span>
                                    </div>
                                    @error('discountValuePercentage')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Discount Amount Field -->
                                <div id="discountAmountField" class="mb-3" style="display: none;">
                                    <label for="discountValueChange" class="form-label">Discount Value Amount <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control @error('discountValueChange') is-invalid @enderror"
                                               id="discountValueChange" name="discountValueChange"
                                               value="{{ old('discountValueChange', $discount->discountValueAmount) }}" min="0" step="0.01">
                                    </div>
                                    @error('discountValueChange')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Price Change Field -->
                                <div id="priceChangeField" class="mb-3" style="display: none;">
                                    <label for="newDiscountedPrice" class="form-label">New Discounted Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control @error('newDiscountedPrice') is-invalid @enderror"
                                               id="newDiscountedPrice" name="newDiscountedPrice"
                                               value="{{ old('newDiscountedPrice', $discount->discountValueChange) }}" min="0" step="0.01">
                                    </div>
                                    @error('newDiscountedPrice')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Always Visible Fields -->
                                <div class="mb-3">
                                    <label for="discountValueMax" class="form-label">Discount Value Max Ceiling <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control @error('discountValueMax') is-invalid @enderror"
                                               id="discountValueMax" name="discountValueMax"
                                               value="{{ old('discountValueMax', $discount->discountValueMax) }}" min="0" step="0.01">
                                    </div>
                                    @error('discountValueMax')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <div class="mb-3">
                                    <label for="discountPriceMax" class="form-label">Discount Price Max Ceiling <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control @error('discountPriceMax') is-invalid @enderror"
                                               id="discountPriceMax" name="discountPriceMax"
                                               value="{{ old('discountPriceMax', $discount->discountPriceMax) }}" min="0" step="0.01">
                                    </div>
                                    @error('discountPriceMax')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>

                                <!-- Form Actions -->
                                <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                                    <a href="{{ route('ecom-products.discounts', ['id' => $product->id]) }}" class="btn btn-secondary">
                                        <i class="bx bx-x me-1"></i>Cancel
                                    </a>
                                    <button type="button" class="btn btn-primary" onclick="submitForm()">
                                        <i class="bx bx-save me-1"></i>Update
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function() {
            // Set minimum datetime for scheduled ending to current time
            const now = new Date();
            const year = now.getFullYear();
            const month = String(now.getMonth() + 1).padStart(2, '0');
            const day = String(now.getDate()).padStart(2, '0');
            const hours = String(now.getHours()).padStart(2, '0');
            const minutes = String(now.getMinutes()).padStart(2, '0');

            const minDateTime = `${year}-${month}-${day}T${hours}:${minutes}`;
            document.getElementById('scheduledEnding').min = minDateTime;

            // Initialize form state based on existing values
            initializeFormState();

            // Add event listeners
            document.getElementById('discountType').addEventListener('change', handleDiscountTypeChange);
            document.getElementById('timerType').addEventListener('change', handleTimerTypeChange);
            document.getElementById('discountValueType').addEventListener('change', handleDiscountValueTypeChange);

            // Add enhanced validation and visual feedback
            addEnhancedValidation();
            addVisualFeedback();

            // Initialize form status
            updateFormStatus();
        });

        function initializeFormState() {
            // Show fields based on existing values
            const discountType = document.getElementById('discountType').value;
            const timerType = document.getElementById('timerType').value;
            const discountValueType = document.getElementById('discountValueType').value;

            handleDiscountTypeChange();
            handleTimerTypeChange();
            handleDiscountValueTypeChange();
        }

        function handleDiscountTypeChange() {
            const discountType = document.getElementById('discountType').value;

            // Hide discount code field first
            document.getElementById('discountCodeField').style.display = 'none';

            // Show discount code field if discount code is selected
            if (discountType === 'discount code') {
                document.getElementById('discountCodeField').style.display = 'block';
            }

            // Update form status after showing/hiding fields
            updateFormStatus();
        }

        function handleTimerTypeChange() {
            const timerType = document.getElementById('timerType').value;

            // Hide all timer-related fields first
            document.getElementById('cookieCountdownFields').style.display = 'none';
            document.getElementById('dateTimeField').style.display = 'none';
            document.getElementById('slotsRemainingField').style.display = 'none';

            // Show relevant fields based on selection
            switch(timerType) {
                case 'cookie countdown':
                    document.getElementById('cookieCountdownFields').style.display = 'block';
                    break;
                case 'date and time':
                    document.getElementById('dateTimeField').style.display = 'block';
                    break;
                case 'slots remaining':
                    document.getElementById('slotsRemainingField').style.display = 'block';
                    break;
            }

            // Update form status after showing/hiding fields
            updateFormStatus();
        }

        function handleDiscountValueTypeChange() {
            const discountValueType = document.getElementById('discountValueType').value;

            // Hide all discount value fields first
            document.getElementById('percentageField').style.display = 'none';
            document.getElementById('discountAmountField').style.display = 'none';
            document.getElementById('priceChangeField').style.display = 'none';

            // Show relevant fields based on selection
            switch(discountValueType) {
                case 'percentage':
                    document.getElementById('percentageField').style.display = 'block';
                    break;
                case 'discount amount':
                    document.getElementById('discountAmountField').style.display = 'block';
                    break;
                case 'price change':
                    document.getElementById('priceChangeField').style.display = 'block';
                    break;
            }

            // Update form status after showing/hiding fields
            updateFormStatus();
        }

        function validateField(field) {
            const value = field.value.trim();
            const fieldId = field.id;
            let isValid = true;
            let errorMessage = '';

            // Clear previous error styling
            field.classList.remove('is-invalid');
            const existingError = field.parentNode.querySelector('.invalid-feedback');
            if (existingError) {
                existingError.remove();
            }

            // Basic required field validation
            if (field.hasAttribute('required') && !value) {
                isValid = false;
                errorMessage = 'This field is required.';
            }

            // Specific field validations
            if (value && isValid) {
                switch(fieldId) {
                    case 'discountName':
                        if (value.length < 2) {
                            isValid = false;
                            errorMessage = 'Discount name must be at least 2 characters long.';
                        }
                        break;

                    case 'discountCode':
                        if (value.length < 2) {
                            isValid = false;
                            errorMessage = 'Discount code must be at least 2 characters long.';
                        }
                        break;

                    case 'countdownValueDays':
                    case 'countdownValueMinutes':
                    case 'slotsRemainingValue':
                        if (parseInt(value) < 0) {
                            isValid = false;
                            errorMessage = 'Value must be 0 or greater.';
                        }
                        break;

                    case 'discountValuePercentage':
                        const percentage = parseFloat(value);
                        if (percentage < 0 || percentage > 100) {
                            isValid = false;
                            errorMessage = 'Percentage must be between 0 and 100.';
                        }
                        break;

                    case 'discountValueChange':
                    case 'newDiscountedPrice':
                    case 'discountValueMax':
                    case 'discountPriceMax':
                        const amount = parseFloat(value);
                        if (amount < 0) {
                            isValid = false;
                            errorMessage = 'Amount must be 0 or greater.';
                        }
                        break;

                    case 'scheduledEnding':
                        const selectedDate = new Date(value);
                        const now = new Date();
                        if (selectedDate <= now) {
                            isValid = false;
                            errorMessage = 'Scheduled ending must be in the future.';
                        }
                        break;
                }
            }

            // Show error if validation failed
            if (!isValid) {
                field.classList.add('is-invalid');
                const errorDiv = document.createElement('div');
                errorDiv.className = 'invalid-feedback';
                errorDiv.textContent = errorMessage;
                field.parentNode.appendChild(errorDiv);
            }

            return isValid;
        }

        // Global validation function
        function validateForm() {
            let isValid = true;
            const inputs = document.querySelectorAll('#discountForm input, #discountForm select');

            inputs.forEach(input => {
                if (!validateField(input)) {
                    isValid = false;
                }
            });

            // Additional conditional validations
            const discountType = document.getElementById('discountType').value;
            const timerType = document.getElementById('timerType').value;
            const discountValueType = document.getElementById('discountValueType').value;

            // Validate discount code field if discount type is discount code
            if (discountType === 'discount code') {
                const discountCode = document.getElementById('discountCode').value;
                if (!discountCode || discountCode.trim() === '') {
                    showFieldError('discountCode', 'Discount code is required when discount type is discount code.');
                    isValid = false;
                } else if (discountCode.trim().length < 2) {
                    showFieldError('discountCode', 'Discount code must be at least 2 characters long.');
                    isValid = false;
                }
            }

            // Validate timer type specific fields
            if (timerType === 'cookie countdown') {
                const days = document.getElementById('countdownValueDays').value;
                const minutes = document.getElementById('countdownValueMinutes').value;

                if (!days || days < 0) {
                    showFieldError('countdownValueDays', 'Countdown days is required and must be 0 or greater.');
                    isValid = false;
                }
                if (!minutes || minutes < 0) {
                    showFieldError('countdownValueMinutes', 'Countdown minutes is required and must be 0 or greater.');
                    isValid = false;
                }
            } else if (timerType === 'date and time') {
                const scheduledEnding = document.getElementById('scheduledEnding').value;
                if (!scheduledEnding) {
                    showFieldError('scheduledEnding', 'Promo ends schedule is required.');
                    isValid = false;
                } else {
                    const selectedDate = new Date(scheduledEnding);
                    const now = new Date();
                    if (selectedDate <= now) {
                        showFieldError('scheduledEnding', 'Promo ends schedule must be in the future.');
                        isValid = false;
                    }
                }
            } else if (timerType === 'slots remaining') {
                const slots = document.getElementById('slotsRemainingValue').value;
                if (!slots || slots < 0) {
                    showFieldError('slotsRemainingValue', 'How many slots is required and must be 0 or greater.');
                    isValid = false;
                }
            }

            // Validate discount value type specific fields
            if (discountValueType === 'percentage') {
                const percentage = document.getElementById('discountValuePercentage').value;
                if (!percentage || percentage < 0 || percentage > 100) {
                    showFieldError('discountValuePercentage', 'Discount percentage is required and must be between 0 and 100.');
                    isValid = false;
                }
            } else if (discountValueType === 'discount amount') {
                const amount = document.getElementById('discountValueChange').value;
                if (!amount || amount < 0) {
                    showFieldError('discountValueChange', 'Discount amount is required and must be 0 or greater.');
                    isValid = false;
                }
            } else if (discountValueType === 'price change') {
                const newPrice = document.getElementById('newDiscountedPrice').value;
                if (!newPrice || newPrice < 0) {
                    showFieldError('newDiscountedPrice', 'New discounted price is required and must be 0 or greater.');
                    isValid = false;
                }
            }

            return isValid;
        }

        function showFieldError(fieldId, message) {
            const field = document.getElementById(fieldId);
            field.classList.add('is-invalid');

            const errorDiv = document.createElement('div');
            errorDiv.className = 'invalid-feedback';
            errorDiv.textContent = message;

            field.parentNode.appendChild(errorDiv);
        }

        // Form submission function
        function submitForm() {
            // Clear all previous errors
            clearAllErrors();

            // Validate the entire form
            if (validateForm()) {
                // Show loading state
                const saveButton = document.querySelector('button[onclick="submitForm()"]');
                const originalText = saveButton.innerHTML;
                saveButton.disabled = true;
                saveButton.innerHTML = '<i class="bx bx-loader-alt bx-spin me-1"></i>Updating...';

                // Prepare form data
                const formData = new FormData(document.getElementById('discountForm'));

                // Submit via AJAX
                fetch(document.getElementById('discountForm').action, {
                    method: 'POST',
                    body: formData,
                    headers: {
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                    }
                })
                .then(response => {
                    if (response.ok) {
                        return response.json();
                    } else if (response.status === 422) {
                        // Handle validation errors
                        return response.json().then(data => {
                            throw new Error(JSON.stringify(data));
                        });
                    }
                    throw new Error('Network response was not ok');
                })
                .then(data => {
                    if (data.success) {
                        // Show brief success indication before redirect
                        const saveButton = document.querySelector('button[onclick="submitForm()"]');
                        saveButton.innerHTML = '<i class="bx bx-check me-1"></i>Updated!';
                        saveButton.classList.remove('btn-primary');
                        saveButton.classList.add('btn-success');

                        // Redirect after brief delay to show success state
                        setTimeout(() => {
                            window.location.href = data.redirect;
                        }, 500);
                    } else {
                        // Handle validation errors from server
                        showErrorMessage(data.message || 'Please check the form for errors and try again.');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);

                    // Check if it's a validation error
                    try {
                        const errorData = JSON.parse(error.message);
                        if (errorData.errors) {
                            // Handle server validation errors
                            handleServerValidationErrors(errorData.errors);
                            showErrorMessage(errorData.message || 'Please check the form for errors and try again.');
                        } else {
                            showErrorMessage(errorData.message || 'An error occurred while updating the discount. Please try again.');
                        }
                    } catch (e) {
                        // Handle other errors
                        showErrorMessage('An error occurred while updating the discount. Please try again.');
                    }
                })
                .finally(() => {
                    // Reset button state
                    saveButton.disabled = false;
                    saveButton.innerHTML = originalText;
                });
            } else {
                // Show validation summary
                showValidationSummary();

                // Scroll to first error
                const firstError = document.querySelector('.is-invalid');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    firstError.focus();
                }
            }
        }

        // Clear all validation errors
        function clearAllErrors() {
            // Remove error styling from all fields
            document.querySelectorAll('.is-invalid').forEach(field => {
                field.classList.remove('is-invalid');
            });

            // Remove all error messages
            document.querySelectorAll('.invalid-feedback').forEach(error => {
                error.remove();
            });

            // Remove validation summary if exists
            const existingSummary = document.querySelector('.validation-summary');
            if (existingSummary) {
                existingSummary.remove();
            }
        }

        // Show validation summary
        function showValidationSummary() {
            const invalidFields = document.querySelectorAll('.is-invalid');

            if (invalidFields.length > 0) {
                // Create validation summary
                const summaryDiv = document.createElement('div');
                summaryDiv.className = 'alert alert-danger validation-summary';
                summaryDiv.innerHTML = `
                    <h6><i class="bx bx-error me-1"></i>Please fix the following errors:</h6>
                    <ul class="mb-0">
                        ${Array.from(invalidFields).map(field => {
                            const errorMessage = field.parentNode.querySelector('.invalid-feedback');
                            return `<li>${field.previousElementSibling?.textContent?.replace('*', '').trim() || field.name}: ${errorMessage?.textContent || 'Invalid value'}</li>`;
                        }).join('')}
                    </ul>
                `;

                // Insert at the top of the form
                const form = document.getElementById('discountForm');
                form.parentNode.insertBefore(summaryDiv, form);

                // Auto-remove after 10 seconds
                setTimeout(() => {
                    if (summaryDiv.parentNode) {
                        summaryDiv.remove();
                    }
                }, 10000);
            }
        }

        // Enhanced real-time validation with visual feedback
        function addEnhancedValidation() {
            const inputs = document.querySelectorAll('#discountForm input, #discountForm select');

            inputs.forEach(input => {
                // Add validation on blur
                input.addEventListener('blur', function() {
                    validateField(this);
                    updateFormStatus();
                });

                // Add validation on input for number fields
                if (input.type === 'number') {
                    input.addEventListener('input', function() {
                        validateField(this);
                        updateFormStatus();
                    });
                }

                // Add validation on change for select fields
                if (input.tagName === 'SELECT') {
                    input.addEventListener('change', function() {
                        validateField(this);
                        updateFormStatus();
                    });
                }
            });
        }

        // Update form status (enable/disable save button)
        function updateFormStatus() {
            const saveButton = document.querySelector('button[onclick="submitForm()"]');
            const requiredFields = document.querySelectorAll('#discountForm input[required], #discountForm select[required]');
            let allRequiredFilled = true;

            requiredFields.forEach(field => {
                if (!field.value.trim()) {
                    allRequiredFilled = false;
                }
            });

            // Check conditional required fields
            const discountType = document.getElementById('discountType').value;
            const timerType = document.getElementById('timerType').value;
            const discountValueType = document.getElementById('discountValueType').value;

            // Check discount code field if discount type is discount code
            if (discountType === 'discount code') {
                const discountCode = document.getElementById('discountCode').value;
                if (!discountCode || discountCode.trim() === '') {
                    allRequiredFilled = false;
                }
            }

            if (timerType === 'cookie countdown') {
                const days = document.getElementById('countdownValueDays').value;
                const minutes = document.getElementById('countdownValueMinutes').value;
                if (!days || !minutes) allRequiredFilled = false;
            } else if (timerType === 'date and time') {
                const scheduledEnding = document.getElementById('scheduledEnding').value;
                if (!scheduledEnding) allRequiredFilled = false;
            } else if (timerType === 'slots remaining') {
                const slots = document.getElementById('slotsRemainingValue').value;
                if (!slots) allRequiredFilled = false;
            }

            if (discountValueType === 'percentage') {
                const percentage = document.getElementById('discountValuePercentage').value;
                if (!percentage) allRequiredFilled = false;
            } else if (discountValueType === 'discount amount') {
                const amount = document.getElementById('discountValueChange').value;
                if (!amount) allRequiredFilled = false;
            } else if (discountValueType === 'price change') {
                const newPrice = document.getElementById('newDiscountedPrice').value;
                if (!newPrice) allRequiredFilled = false;
            }

            // Update save button state
            if (allRequiredFilled) {
                saveButton.disabled = false;
                saveButton.classList.remove('btn-secondary');
                saveButton.classList.add('btn-primary');
            } else {
                saveButton.disabled = true;
                saveButton.classList.remove('btn-primary');
                saveButton.classList.add('btn-secondary');
            }
        }

        // Add visual feedback for field validation
        function addVisualFeedback() {
            const inputs = document.querySelectorAll('#discountForm input, #discountForm select');

            inputs.forEach(input => {
                input.addEventListener('focus', function() {
                    this.classList.remove('is-invalid');
                    const errorDiv = this.parentNode.querySelector('.invalid-feedback');
                    if (errorDiv) {
                        errorDiv.remove();
                    }
                });

                input.addEventListener('input', function() {
                    if (this.classList.contains('is-invalid')) {
                        this.classList.remove('is-invalid');
                        const errorDiv = this.parentNode.querySelector('.invalid-feedback');
                        if (errorDiv) {
                            errorDiv.remove();
                        }
                    }
                });
            });
        }

        // Show error message
        function showErrorMessage(message) {
            // Remove existing alerts
            const existingAlerts = document.querySelectorAll('.alert');
            existingAlerts.forEach(alert => alert.remove());

            // Create error alert
            const alertDiv = document.createElement('div');
            alertDiv.className = 'alert alert-danger alert-dismissible fade show';
            alertDiv.innerHTML = `
                <i class="bx bx-error-circle me-2"></i>${message}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            `;

            // Insert alert at the top of the form
            const form = document.getElementById('discountForm');
            form.parentNode.insertBefore(alertDiv, form);

            // Auto-dismiss after 8 seconds
            setTimeout(() => {
                if (alertDiv.parentNode) {
                    alertDiv.remove();
                }
            }, 8000);
        }

        // Handle server validation errors
        function handleServerValidationErrors(errors) {
            // Clear existing errors
            clearAllErrors();

            // Apply server validation errors to form fields
            Object.keys(errors).forEach(fieldName => {
                const field = document.querySelector(`[name="${fieldName}"]`);
                if (field) {
                    field.classList.add('is-invalid');

                    const errorDiv = document.createElement('div');
                    errorDiv.className = 'invalid-feedback';
                    errorDiv.textContent = errors[fieldName][0]; // Get first error message

                    field.parentNode.appendChild(errorDiv);
                }
            });
        }
    </script>
@endsection
