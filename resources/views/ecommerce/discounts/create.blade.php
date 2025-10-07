@extends('layouts.master')

@section('title') Create New Discount @endsection

@section('css')
<!-- Bootstrap Datepicker CSS -->
<link href="{{ URL::asset('build/libs/bootstrap-datepicker/css/bootstrap-datepicker.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Bootstrap Timepicker CSS -->
<link href="{{ URL::asset('build/libs/bootstrap-timepicker/css/bootstrap-timepicker.min.css') }}" rel="stylesheet" type="text/css" />
<style>
.form-group {
    margin-bottom: 1.5rem;
}

.is-invalid {
    border-color: #f46a6a !important;
}

.invalid-feedback {
    display: block;
    color: #f46a6a;
    font-size: 0.875rem;
    margin-top: 0.25rem;
}

.is-valid {
    border-color: #34c38f !important;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') <a href="{{ route('ecom-discounts') }}">Discounts</a> @endslot
@slot('title') Create New Discount @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h4 class="card-title mb-0">Create New Discount</h4>
                    <a href="{{ route('ecom-discounts') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Discounts
                    </a>
                </div>

                <form id="discountForm" method="POST" action="{{ route('ecom-discounts.store') }}" novalidate>
                    @csrf

                    <!-- Discount Name -->
                    <div class="form-group">
                        <label for="discountName" class="form-label">Discount Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="discountName" name="discountName" placeholder="Enter discount name" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Discount Description -->
                    <div class="form-group">
                        <label for="discountDescription" class="form-label">Discount Description <span class="text-danger">*</span></label>
                        <textarea class="form-control" id="discountDescription" name="discountDescription" rows="4" placeholder="Enter discount description" required></textarea>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Discount Type -->
                    <div class="form-group">
                        <label for="discountType" class="form-label">Discount Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="discountType" name="discountType" required>
                            <option value="">Select Discount Type</option>
                            <option value="Product Discount">Product Discount</option>
                            <option value="Shipping Discount">Shipping Discount</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Discount Trigger -->
                    <div class="form-group">
                        <label for="discountTrigger" class="form-label">Discount Trigger <span class="text-danger">*</span></label>
                        <select class="form-select" id="discountTrigger" name="discountTrigger" required>
                            <option value="">Select Discount Trigger</option>
                            <option value="Auto Apply">Auto Apply</option>
                            <option value="Discount Code">Discount Code</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Discount Code (conditional) -->
                    <div class="form-group" id="discountCodeField" style="display: none;">
                        <label for="discountCode" class="form-label">Discount Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="discountCode" name="discountCode" placeholder="Enter discount code">
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Amount Type -->
                    <div class="form-group">
                        <label for="amountType" class="form-label">Amount Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="amountType" name="amountType" required>
                            <option value="">Select Amount Type</option>
                            <option value="Percentage">Percentage</option>
                            <option value="Specific Amount">Specific Amount</option>
                            <option value="Price Replacement">Price Replacement</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Value Percent (conditional) -->
                    <div class="form-group" id="valuePercentField" style="display: none;">
                        <label for="valuePercent" class="form-label">Value Percent <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="number" class="form-control" id="valuePercent" name="valuePercent" placeholder="Enter percentage value" step="0.01" min="0" max="100">
                            <span class="input-group-text">%</span>
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Value Amount (conditional) -->
                    <div class="form-group" id="valueAmountField" style="display: none;">
                        <label for="valueAmount" class="form-label">Value Amount <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Php</span>
                            <input type="number" class="form-control" id="valueAmount" name="valueAmount" placeholder="Enter amount" step="0.01" min="0">
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Value Replacement (conditional) -->
                    <div class="form-group" id="valueReplacementField" style="display: none;">
                        <label for="valueReplacement" class="form-label">Value Replacement <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Php</span>
                            <input type="number" class="form-control" id="valueReplacement" name="valueReplacement" placeholder="Enter replacement price" step="0.01" min="0">
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Discount Cap Type -->
                    <div class="form-group">
                        <label for="discountCapType" class="form-label">Discount Cap Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="discountCapType" name="discountCapType" required>
                            <option value="">Select Discount Cap Type</option>
                            <option value="None">None</option>
                            <option value="Total">Total</option>
                            <option value="Per Product">Per Product</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Discount Cap Value (conditional) -->
                    <div class="form-group" id="discountCapValueField" style="display: none;">
                        <label for="discountCapValue" class="form-label">Discount Cap Value <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <span class="input-group-text">Php</span>
                            <input type="number" class="form-control" id="discountCapValue" name="discountCapValue" placeholder="Enter cap value" step="0.01" min="0">
                        </div>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Usage Limit -->
                    <div class="form-group">
                        <label for="usageLimit" class="form-label">Usage Limit <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="usageLimit" name="usageLimit" placeholder="Enter usage limit" min="0" required>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Expiration Type -->
                    <div class="form-group">
                        <label for="expirationType" class="form-label">Expiration Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="expirationType" name="expirationType" required>
                            <option value="">Select Expiration Type</option>
                            <option value="Time and Date">Time and Date</option>
                            <option value="Countdown">Countdown</option>
                        </select>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Date Expiration (conditional) -->
                    <div class="form-group" id="dateExpirationField" style="display: none;">
                        <label for="dateExpiration" class="form-label">Date Expiration <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="dateExpiration" name="dateExpiration" placeholder="Select date" readonly>
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Time Expiration (conditional) -->
                    <div class="form-group" id="timeExpirationField" style="display: none;">
                        <label for="timeExpiration" class="form-label">Time Expiration <span class="text-danger">*</span></label>
                        <div class="row">
                            <div class="col-md-4">
                                <select class="form-select" id="timeHour" name="timeHour">
                                    <option value="">Hour</option>
                                    <option value="01">01</option>
                                    <option value="02">02</option>
                                    <option value="03">03</option>
                                    <option value="04">04</option>
                                    <option value="05">05</option>
                                    <option value="06">06</option>
                                    <option value="07">07</option>
                                    <option value="08">08</option>
                                    <option value="09">09</option>
                                    <option value="10">10</option>
                                    <option value="11">11</option>
                                    <option value="12">12</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="timeMinute" name="timeMinute">
                                    <option value="">Minute</option>
                                    <option value="00">00</option>
                                    <option value="15">15</option>
                                    <option value="30">30</option>
                                    <option value="45">45</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <select class="form-select" id="timePeriod" name="timePeriod">
                                    <option value="">AM/PM</option>
                                    <option value="AM">AM</option>
                                    <option value="PM">PM</option>
                                </select>
                            </div>
                        </div>
                        <input type="hidden" id="timeExpiration" name="timeExpiration">
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Countdown Minutes (conditional) -->
                    <div class="form-group" id="countdownMinutesField" style="display: none;">
                        <label for="countdownMinutes" class="form-label">Countdown Minutes <span class="text-danger">*</span></label>
                        <input type="number" class="form-control" id="countdownMinutes" name="countdownMinutes" placeholder="Enter countdown minutes" min="0">
                        <div class="invalid-feedback"></div>
                    </div>

                    <!-- Submit Button -->
                    <div class="form-group">
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Save Discount
                        </button>
                        <a href="{{ route('ecom-discounts') }}" class="btn btn-secondary ms-2">
                            <i class="bx bx-x me-1"></i> Cancel
                        </a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Bootstrap Datepicker JS -->
<script src="{{ URL::asset('build/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js') }}"></script>
<!-- Bootstrap Timepicker JS -->
<script src="{{ URL::asset('build/libs/bootstrap-timepicker/js/bootstrap-timepicker.min.js') }}"></script>
<!-- Moment.js -->
<script src="{{ URL::asset('build/libs/moment/moment.js') }}"></script>

<script>
    $(document).ready(function() {
        // Get tomorrow's date
        var tomorrow = new Date();
        tomorrow.setDate(tomorrow.getDate() + 1);

        // Initialize date picker (exclude today and past dates)
        $('#dateExpiration').datepicker({
            format: 'MM d, yyyy',
            autoclose: true,
            startDate: tomorrow,
            todayHighlight: false
        }).on('changeDate', function(e) {
            // Format the date to "January 25, 2025" format
            var date = moment(e.date);
            var formattedDate = date.format('MMMM D, YYYY');
            $('#dateExpiration').val(formattedDate);
        });

        // Combine time dropdowns into timeExpiration hidden field
        function updateTimeExpiration() {
            const hour = $('#timeHour').val();
            const minute = $('#timeMinute').val();
            const period = $('#timePeriod').val();

            if (hour && minute && period) {
                $('#timeExpiration').val(hour + ':' + minute + ' ' + period);
            } else {
                $('#timeExpiration').val('');
            }
        }

        $('#timeHour, #timeMinute, #timePeriod').on('change', function() {
            updateTimeExpiration();
            validateField($('#timeExpiration'));
        });

        // Discount Trigger change handler
        $('#discountTrigger').on('change', function() {
            const selectedTrigger = $(this).val();

            if (selectedTrigger === 'Discount Code') {
                $('#discountCodeField').slideDown();
            } else {
                $('#discountCodeField').slideUp();
                $('#discountCode').val(''); // Clear the value when hidden
            }
        });

        // Amount Type change handler
        $('#amountType').on('change', function() {
            const selectedType = $(this).val();

            // Hide all value fields first
            $('#valuePercentField').slideUp();
            $('#valueAmountField').slideUp();
            $('#valueReplacementField').slideUp();

            // Clear all values
            $('#valuePercent').val('');
            $('#valueAmount').val('');
            $('#valueReplacement').val('');

            // Show the appropriate field
            if (selectedType === 'Percentage') {
                $('#valuePercentField').slideDown();
            } else if (selectedType === 'Specific Amount') {
                $('#valueAmountField').slideDown();
            } else if (selectedType === 'Price Replacement') {
                $('#valueReplacementField').slideDown();
            }
        });

        // Discount Cap Type change handler
        $('#discountCapType').on('change', function() {
            const selectedType = $(this).val();

            if (selectedType && selectedType !== 'None') {
                $('#discountCapValueField').slideDown();
            } else {
                $('#discountCapValueField').slideUp();
                $('#discountCapValue').val(''); // Clear the value when hidden
            }
        });

        // Expiration Type change handler
        $('#expirationType').on('change', function() {
            const selectedType = $(this).val();

            // Hide all expiration fields first
            $('#dateExpirationField').slideUp();
            $('#timeExpirationField').slideUp();
            $('#countdownMinutesField').slideUp();

            // Clear all values
            $('#dateExpiration').val('');
            $('#timeExpiration').val('');
            $('#timeHour').val('');
            $('#timeMinute').val('');
            $('#timePeriod').val('');
            $('#countdownMinutes').val('');

            // Show the appropriate fields
            if (selectedType === 'Time and Date') {
                $('#dateExpirationField').slideDown();
                $('#timeExpirationField').slideDown();
            } else if (selectedType === 'Countdown') {
                $('#countdownMinutesField').slideDown();
            }
        });

        // Real-time validation function
        function validateField($field) {
            const fieldId = $field.attr('id');
            const fieldValue = $field.val().trim();
            const $formGroup = $field.closest('.form-group');
            const $feedback = $formGroup.find('.invalid-feedback');

            // Only validate if the field's parent form-group is visible
            if (!$formGroup.is(':visible')) {
                $field.removeClass('is-invalid is-valid');
                $feedback.text('');
                return true;
            }

            let isValid = true;
            let errorMessage = '';

            // Check if field is empty
            if (!fieldValue) {
                isValid = false;
                errorMessage = 'This field is required.';
            }
            // Validate number fields
            else if ($field.attr('type') === 'number') {
                const numValue = parseFloat(fieldValue);
                const min = parseFloat($field.attr('min'));
                const max = parseFloat($field.attr('max'));

                if (isNaN(numValue)) {
                    isValid = false;
                    errorMessage = 'Please enter a valid number.';
                } else if (min !== undefined && numValue < min) {
                    isValid = false;
                    errorMessage = `Value must be at least ${min}.`;
                } else if (max !== undefined && numValue > max) {
                    isValid = false;
                    errorMessage = `Value must not exceed ${max}.`;
                }
            }

            // Update field styling and feedback
            if (isValid) {
                $field.removeClass('is-invalid').addClass('is-valid');
                $feedback.text('');
            } else {
                $field.removeClass('is-valid').addClass('is-invalid');
                $feedback.text(errorMessage);
            }

            return isValid;
        }

        // Validate all visible fields
        function validateForm() {
            let isFormValid = true;

            // Get all form fields
            const $fields = $('#discountForm').find('input[required], select[required], textarea[required], input:visible:not([readonly]), select:visible, textarea:visible');

            $fields.each(function() {
                const $field = $(this);
                const $formGroup = $field.closest('.form-group');

                // Only validate visible fields
                if ($formGroup.is(':visible')) {
                    if (!validateField($field)) {
                        isFormValid = false;
                    }
                }
            });

            return isFormValid;
        }

        // Real-time validation on input/change for text inputs and textareas
        $('#discountForm').on('input', 'input[type="text"], input[type="number"], textarea', function() {
            validateField($(this));
        });

        // Real-time validation on change for select dropdowns
        $('#discountForm').on('change', 'select', function() {
            validateField($(this));
        });

        // Validation on blur for all fields
        $('#discountForm').on('blur', 'input, select, textarea', function() {
            const $field = $(this);
            const $formGroup = $field.closest('.form-group');

            // Only validate if visible
            if ($formGroup.is(':visible')) {
                validateField($field);
            }
        });

        // Validation for date picker
        $('#dateExpiration').on('changeDate', function() {
            validateField($(this));
        });


        // Form submit handler with validation
        let isSubmitting = false;

        $('#discountForm').on('submit', function(e) {
            // If already validated and submitting, allow the form to submit
            if (isSubmitting) {
                return true;
            }

            e.preventDefault();

            // Validate all visible fields
            if (validateForm()) {
                // Form is valid, prepare to submit
                const $submitBtn = $(this).find('button[type="submit"]');
                const $form = $(this);

                // Show loading state
                $submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

                // Set flag and submit the form
                isSubmitting = true;
                $form[0].submit();
            } else {
                // Form is invalid, scroll to first error
                const $firstError = $('.is-invalid:first');
                if ($firstError.length) {
                    $('html, body').animate({
                        scrollTop: $firstError.offset().top - 100
                    }, 300);
                }
            }
        });
    });
</script>
@endsection

