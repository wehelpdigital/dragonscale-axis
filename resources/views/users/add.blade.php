@extends('layouts.master')

@section('title') Add User @endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') <a href="{{ route('users.index') }}">Users</a> @endslot
@slot('title') Add User @endslot
@endcomponent

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="bx bx-user-plus" style="font-size: 3rem; color: #007bff;"></i>
                    <h4 class="card-title mt-3">Add New User</h4>
                    <p class="card-title-desc text-muted">Create a new user account for the system.</p>
                </div>



                <!-- Add User Form -->
                <form id="addUserForm" method="POST" action="{{ route('users.store') }}" novalidate>
                    @csrf

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                                                <input type="text" class="form-control @error('name') is-invalid @enderror"
                               id="name" name="name" value="{{ old('name') }}"
                               placeholder="Enter full name">
                        @error('name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback"></div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                                                <input type="email" class="form-control @error('email') is-invalid @enderror"
                               id="email" name="email" value="{{ old('email') }}"
                               placeholder="Enter email address">
                        @error('email')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback"></div>
                        @enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control @error('password') is-invalid @enderror"
                                   id="password" name="password" autocomplete="new-password"
                                   placeholder="Enter password (min. 8 characters)">
                            <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                <i class="bx bx-show" id="toggleIcon"></i>
                            </button>
                        </div>
                        @error('password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback d-block" id="password-feedback"></div>
                        @enderror
                        <div class="form-text">Must contain: 8+ chars, uppercase, lowercase, number, special char</div>
                    </div>

                    <div class="mb-4">
                        <label for="verify_password" class="form-label">Verify Password <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input type="password" class="form-control @error('verify_password') is-invalid @enderror"
                                   id="verify_password" name="verify_password" autocomplete="new-password"
                                   placeholder="Re-enter password">
                            <button type="button" class="btn btn-outline-secondary" id="toggleVerifyPassword">
                                <i class="bx bx-show" id="toggleVerifyIcon"></i>
                            </button>
                        </div>
                        @error('verify_password')
                            <div class="invalid-feedback d-block">{{ $message }}</div>
                        @else
                            <div class="invalid-feedback d-block" id="verify-password-feedback"></div>
                        @enderror
                        <div class="form-text">Must match the password above</div>
                    </div>

                    <!-- Form Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="{{ route('users.index') }}" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-user-plus me-1"></i> Register User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')

<script>
$(document).ready(function() {
    // Setup CSRF token for all AJAX requests
    $.ajaxSetup({
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        }
    });

    let emailCheckTimeout;
    let formIsValid = {
        name: false,
        email: false,
        password: false,
        verify_password: false
    };

    // Toggle password visibility
    $('#togglePassword').on('click', function(e) {
        e.preventDefault();
        const passwordField = $('#password');
        const toggleIcon = $('#toggleIcon');

        if (passwordField.attr('type') === 'password') {
            passwordField.attr('type', 'text');
            toggleIcon.removeClass('bx-show').addClass('bx-hide');
        } else {
            passwordField.attr('type', 'password');
            toggleIcon.removeClass('bx-hide').addClass('bx-show');
        }
    });

    // Toggle verify password visibility
    $('#toggleVerifyPassword').on('click', function(e) {
        e.preventDefault();
        const verifyPasswordField = $('#verify_password');
        const toggleVerifyIcon = $('#toggleVerifyIcon');

        if (verifyPasswordField.attr('type') === 'password') {
            verifyPasswordField.attr('type', 'text');
            toggleVerifyIcon.removeClass('bx-show').addClass('bx-hide');
        } else {
            verifyPasswordField.attr('type', 'password');
            toggleVerifyIcon.removeClass('bx-hide').addClass('bx-show');
        }
    });

    // Name validation
    $('#name').on('input blur', function() {
        const name = $(this).val().trim();
        const nameField = $(this);
        const feedback = nameField.next('.invalid-feedback');

        if (!name) {
            nameField.addClass('is-invalid');
            feedback.text('Name is required');
            formIsValid.name = false;
        } else if (name.length < 2) {
            nameField.addClass('is-invalid');
            feedback.text('Name must be at least 2 characters long');
            formIsValid.name = false;
        } else if (name.length > 50) {
            nameField.addClass('is-invalid');
            feedback.text('Name must be less than 50 characters');
            formIsValid.name = false;
        } else if (!/^[a-zA-Z\s]+$/.test(name)) {
            nameField.addClass('is-invalid');
            feedback.text('Name can only contain letters and spaces');
            formIsValid.name = false;
        } else {
            nameField.removeClass('is-invalid');
            nameField.addClass('is-valid');
            feedback.text('');
            formIsValid.name = true;
        }
    });

    // Email format validation
    function validateEmailFormat(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Email uniqueness check
    function checkEmailUniqueness(email) {
        return $.ajax({
            url: '{{ route("users.checkEmail") }}',
            method: 'POST',
            data: {
                email: email,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            dataType: 'json',
            timeout: 10000
        });
    }

    // Email validation
    $('#email').on('input blur', function() {
        const email = $(this).val().trim();
        const emailField = $(this);
        const feedback = emailField.next('.invalid-feedback');

        // Clear previous timeout
        if (emailCheckTimeout) {
            clearTimeout(emailCheckTimeout);
        }

        if (!email) {
            emailField.addClass('is-invalid');
            feedback.text('Email is required');
            formIsValid.email = false;
            return;
        }

        if (!validateEmailFormat(email)) {
            emailField.addClass('is-invalid');
            feedback.text('Please enter a valid email address');
            formIsValid.email = false;
            return;
        }

        // Show loading state
        emailField.removeClass('is-invalid is-valid');
        feedback.text('Checking email availability...');

        // Debounce email uniqueness check
        emailCheckTimeout = setTimeout(function() {
            checkEmailUniqueness(email)
                .done(function(response) {
                    console.log('Email check response:', response);
                    if (response.exists) {
                        emailField.addClass('is-invalid');
                        feedback.text('This email address is already taken');
                        formIsValid.email = false;
                    } else {
                        emailField.removeClass('is-invalid');
                        emailField.addClass('is-valid');
                        feedback.text('');
                        formIsValid.email = true;
                    }
                })
                .fail(function(xhr, status, error) {
                    console.error('Email check failed:', xhr.responseText, status, error);

                    // If it's a 401 (unauthorized), the user might not be logged in
                    if (xhr.status === 401) {
                        emailField.addClass('is-invalid');
                        feedback.text('Please log in to verify email');
                        formIsValid.email = false;
                    } else if (xhr.status === 419) {
                        // CSRF token mismatch
                        emailField.addClass('is-invalid');
                        feedback.text('Session expired, please refresh the page');
                        formIsValid.email = false;
                    } else {
                        // For now, allow the email if there's a server error
                        // but show a warning
                        emailField.removeClass('is-invalid');
                        emailField.addClass('is-valid');
                        feedback.text('');
                        formIsValid.email = true;
                        console.warn('Email uniqueness check failed, allowing email');
                    }
                });
        }, 500);
    });

    // Password strength validation
    function validatePasswordStrength(password) {
        const errors = [];

        if (password.length < 8) {
            errors.push('at least 8 characters');
        }
        if (!/(?=.*[a-z])/.test(password)) {
            errors.push('one lowercase letter');
        }
        if (!/(?=.*[A-Z])/.test(password)) {
            errors.push('one uppercase letter');
        }
        if (!/(?=.*\d)/.test(password)) {
            errors.push('one number');
        }
        if (!/(?=.*[^A-Za-z0-9])/.test(password)) {
            errors.push('one special character');
        }

        return {
            isValid: errors.length === 0,
            errors: errors
        };
    }

    // Password validation
    $('#password').on('input blur', function() {
        const password = $(this).val();
        const passwordField = $(this);

        const feedback = $('#password-feedback');



        if (!password) {
            passwordField.addClass('is-invalid');
            passwordField.removeClass('is-valid');
            feedback.text('Password is required').removeClass('d-none').addClass('d-block');
            formIsValid.password = false;
        } else {
            const validation = validatePasswordStrength(password);

            if (!validation.isValid) {
                passwordField.addClass('is-invalid');
                passwordField.removeClass('is-valid');
                feedback.text('Password must contain: ' + validation.errors.join(', ')).removeClass('d-none').addClass('d-block');
                formIsValid.password = false;
            } else {
                passwordField.removeClass('is-invalid');
                passwordField.addClass('is-valid');
                feedback.text('').removeClass('d-block').addClass('d-none');
                formIsValid.password = true;
            }
        }

        // Revalidate verify password if it has a value
        const verifyPassword = $('#verify_password').val();
        if (verifyPassword) {
            $('#verify_password').trigger('input');
        }
    });

    // Verify password validation
    $('#verify_password').on('input blur', function() {
        const password = $('#password').val();
        const verifyPassword = $(this).val();
        const verifyPasswordField = $(this);

        const feedback = $('#verify-password-feedback');



        if (!verifyPassword) {
            verifyPasswordField.addClass('is-invalid');
            verifyPasswordField.removeClass('is-valid');
            feedback.text('Please verify your password').removeClass('d-none').addClass('d-block');
            formIsValid.verify_password = false;
        } else if (password !== verifyPassword) {
            verifyPasswordField.addClass('is-invalid');
            verifyPasswordField.removeClass('is-valid');
            feedback.text('Passwords do not match').removeClass('d-none').addClass('d-block');
            formIsValid.verify_password = false;
        } else {
            verifyPasswordField.removeClass('is-invalid');
            verifyPasswordField.addClass('is-valid');
            feedback.text('').removeClass('d-block').addClass('d-none');
            formIsValid.verify_password = true;
        }
    });

    // Form submission handling
    $('#addUserForm').on('submit', function(e) {
        e.preventDefault();

        // Trigger validation on all fields
        $('#name, #email, #password, #verify_password').trigger('blur');

        // Check if all fields are valid
        const allValid = Object.values(formIsValid).every(valid => valid === true);

        if (!allValid) {
            const invalidFields = Object.keys(formIsValid).filter(key => !formIsValid[key]);

            alert('Please fix the errors in the form before submitting.');

            // Focus on first invalid field
            if (invalidFields.length > 0) {
                $('#' + invalidFields[0]).focus();
            }
            return false;
        }

        // Submit the form via AJAX if all validations pass
        const formData = new FormData(this);

        // Show loading state
        const submitBtn = $(this).find('button[type="submit"]');
        const originalText = submitBtn.html();
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Creating User...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
                        success: function(response) {
                if (response.success) {
                    // Redirect using the URL provided by the server
                    window.location.href = response.redirect_url || '{{ route('users.index') }}';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while creating the user.';

                if (xhr.responseJSON) {
                    if (xhr.responseJSON.message) {
                        errorMessage = xhr.responseJSON.message;
                    } else if (xhr.responseJSON.errors) {
                        // Handle validation errors
                        const errors = xhr.responseJSON.errors;
                        let errorList = [];

                        Object.keys(errors).forEach(field => {
                            if (Array.isArray(errors[field])) {
                                errorList = errorList.concat(errors[field]);
                            } else {
                                errorList.push(errors[field]);
                            }
                        });

                        errorMessage = errorList.join(', ');
                    }
                }

                alert('Error: ' + errorMessage);
            },
            complete: function() {
                // Restore button state
                submitBtn.prop('disabled', false).html(originalText);
            }
        });
    });


});
</script>
@endsection
