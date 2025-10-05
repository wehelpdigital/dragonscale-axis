<?php $__env->startSection('title'); ?> Edit User <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> <a href="<?php echo e(route('users.index')); ?>">Users</a> <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Edit User <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row justify-content-center">
    <div class="col-md-8 col-lg-6">
        <div class="card">
            <div class="card-body">
                <div class="text-center mb-4">
                    <i class="bx bx-user-circle" style="font-size: 3rem; color: #007bff;"></i>
                    <h4 class="card-title mt-3">Edit User</h4>
                    <p class="card-title-desc text-muted">Update user account information.</p>
                </div>

                <!-- Edit User Form -->
                <form id="editUserForm" method="POST" action="<?php echo e(route('users.update', $user->id)); ?>" novalidate>
                    <?php echo csrf_field(); ?>

                    <div class="mb-3">
                        <label for="name" class="form-label">Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               id="name" name="name" value="<?php echo e(old('name', $user->name)); ?>"
                               placeholder="Enter full name">
                        <?php $__errorArgs = ['name'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php else: ?>
                            <div class="invalid-feedback"></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="mb-3">
                        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                        <input type="email" class="form-control <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                               id="email" name="email" value="<?php echo e(old('email', $user->email)); ?>"
                               placeholder="Enter email address">
                        <?php $__errorArgs = ['email'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback"><?php echo e($message); ?></div>
                        <?php else: ?>
                            <div class="invalid-feedback"></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label">Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   id="password" name="password" autocomplete="new-password"
                                   placeholder="Enter new password (leave blank to keep current)">
                            <button type="button" class="btn btn-outline-secondary" id="togglePassword">
                                <i class="bx bx-show" id="toggleIcon"></i>
                            </button>
                        </div>
                        <?php $__errorArgs = ['password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                        <?php else: ?>
                            <div class="invalid-feedback d-block" id="password-feedback"></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <div class="form-text">Leave blank to keep current password. If changing: 8+ chars, uppercase, lowercase, number, special char</div>
                    </div>

                    <div class="mb-4">
                        <label for="verify_password" class="form-label">Verify Password</label>
                        <div class="input-group">
                            <input type="password" class="form-control <?php $__errorArgs = ['verify_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                   id="verify_password" name="verify_password" autocomplete="new-password"
                                   placeholder="Re-enter new password">
                            <button type="button" class="btn btn-outline-secondary" id="toggleVerifyPassword">
                                <i class="bx bx-show" id="toggleVerifyIcon"></i>
                            </button>
                        </div>
                        <?php $__errorArgs = ['verify_password'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                            <div class="invalid-feedback d-block"><?php echo e($message); ?></div>
                        <?php else: ?>
                            <div class="invalid-feedback d-block" id="verify-password-feedback"></div>
                        <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                        <div class="form-text">Must match the password above (only required if changing password)</div>
                    </div>

                    <!-- Form Buttons -->
                    <div class="d-flex justify-content-between">
                        <a href="<?php echo e(route('users.index')); ?>" class="btn btn-secondary">
                            <i class="bx bx-x me-1"></i> Cancel
                        </a>
                        <button type="submit" class="btn btn-primary">
                            <i class="bx bx-save me-1"></i> Update User
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>

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
        name: true, // Start as true since it has existing valid data
        email: true, // Start as true since it has existing valid data
        password: true, // Start as true since password is optional
        verify_password: true // Start as true since password is optional
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
            url: '<?php echo e(route("users.checkEmail")); ?>',
            method: 'POST',
            data: {
                email: email,
                exclude_id: <?php echo e($user->id); ?>, // Exclude current user from uniqueness check
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
                    // For now, allow the email if there's a server error
                    emailField.removeClass('is-invalid');
                    emailField.addClass('is-valid');
                    feedback.text('');
                    formIsValid.email = true;
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

    // Password validation (optional for edit)
    $('#password').on('input blur', function() {
        const password = $(this).val();
        const passwordField = $(this);
        const feedback = $('#password-feedback');

        // If password is empty, it's valid (optional field for edit)
        if (!password) {
            passwordField.removeClass('is-invalid is-valid');
            feedback.text('').removeClass('d-block').addClass('d-none');
            formIsValid.password = true;
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

    // Verify password validation (only required if password is provided)
    $('#verify_password').on('input blur', function() {
        const password = $('#password').val();
        const verifyPassword = $(this).val();
        const verifyPasswordField = $(this);
        const feedback = $('#verify-password-feedback');

        // If no password is set, verify password should be empty too
        if (!password && !verifyPassword) {
            verifyPasswordField.removeClass('is-invalid is-valid');
            feedback.text('').removeClass('d-block').addClass('d-none');
            formIsValid.verify_password = true;
        } else if (password && !verifyPassword) {
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
    $('#editUserForm').on('submit', function(e) {
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
        submitBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Updating User...');

        $.ajax({
            url: $(this).attr('action'),
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            success: function(response) {
                if (response.success) {
                    // Set session flash and redirect
                    sessionStorage.setItem('userUpdateSuccess', 'User updated successfully!');
                    window.location.href = '<?php echo e(route('users.index')); ?>';
                } else {
                    alert('Error: ' + response.message);
                }
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while updating the user.';

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
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/users/edit.blade.php ENDPATH**/ ?>