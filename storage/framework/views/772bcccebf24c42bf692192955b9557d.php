<?php $__env->startSection('title'); ?> Update Crypto Set <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Crypto <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Update Crypto Set <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Update Crypto Set</h4>
                <p class="card-title-desc">Update your current crypto trading task settings.</p>

                <?php if(session('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>
                        <?php echo e(session('error')); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e(route('crypto-set-update.update')); ?>" method="POST" id="cryptoSetForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="task_id" value="<?php echo e($task->id); ?>">

                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label class="form-label fw-semibold">Current Task Type:</label>
                                <p class="text-muted mb-0"><?php echo e(ucfirst($task->taskType)); ?></p>
                                <input type="hidden" name="taskType" value="<?php echo e($task->taskType); ?>">
                            </div>
                        </div>
                    </div>

                    <!-- To Sell Fields -->
                    <div id="toSellFields" class="task-fields <?php echo e($task->taskType === 'to sell' ? '' : 'd-none'); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currentCoinValue" class="form-label">Your Current Coin Value (<?php echo e(strtoupper($task->taskCoin)); ?>) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.00000001" class="form-control"
                                           id="currentCoinValue" name="currentCoinValue"
                                           value="<?php echo e(old('currentCoinValue', $task->currentCoinValue)); ?>" required>
                                    <div class="invalid-feedback" id="currentCoinValue-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="startingPhpValue" class="form-label">Your Last PHP Value Before Buying Coin <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                           id="startingPhpValue" name="startingPhpValue"
                                           value="<?php echo e(old('startingPhpValue', $task->startingPhpValue)); ?>" required>
                                    <div class="invalid-feedback" id="startingPhpValue-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="minThreshold" class="form-label">Minimum Threshold to Get Notification (PHP) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                           id="minThreshold" name="minThreshold"
                                           value="<?php echo e(old('minThreshold', $task->minThreshold)); ?>" required>
                                    <div class="invalid-feedback" id="minThreshold-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="intervalThreshold" class="form-label">Threshold Interval (PHP) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                           id="intervalThreshold" name="intervalThreshold"
                                           value="<?php echo e(old('intervalThreshold', $task->intervalThreshold)); ?>" required>
                                    <div class="invalid-feedback" id="intervalThreshold-error"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- To Buy Fields -->
                    <div id="toBuyFields" class="task-fields <?php echo e($task->taskType === 'to buy' ? '' : 'd-none'); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="toBuyCurrentCashValue" class="form-label">Your Current PHP Value <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                           id="toBuyCurrentCashValue" name="toBuyCurrentCashValue"
                                           value="<?php echo e(old('toBuyCurrentCashValue', $task->toBuyCurrentCashValue)); ?>" required>
                                    <div class="invalid-feedback" id="toBuyCurrentCashValue-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="toBuyStartingCoinValue" class="form-label">Your Last Coin Value Before Selling Coin (<?php echo e(strtoupper($task->taskCoin)); ?>) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.00000001" class="form-control"
                                           id="toBuyStartingCoinValue" name="toBuyStartingCoinValue"
                                           value="<?php echo e(old('toBuyStartingCoinValue', $task->toBuyStartingCoinValue)); ?>" required>
                                    <div class="invalid-feedback" id="toBuyStartingCoinValue-error"></div>
                                </div>
                            </div>
                        </div>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="toBuyMinThreshold" class="form-label">Minimum Threshold to Get Notification (PHP) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                           id="toBuyMinThreshold" name="toBuyMinThreshold"
                                           value="<?php echo e(old('toBuyMinThreshold', $task->toBuyMinThreshold)); ?>" required>
                                    <div class="invalid-feedback" id="toBuyMinThreshold-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="toBuyIntervalThreshold" class="form-label">Threshold Interval (PHP) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                           id="toBuyIntervalThreshold" name="toBuyIntervalThreshold"
                                           value="<?php echo e(old('toBuyIntervalThreshold', $task->toBuyIntervalThreshold)); ?>" required>
                                    <div class="invalid-feedback" id="toBuyIntervalThreshold-error"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex gap-2">
                                <a href="<?php echo e(route('crypto-set')); ?>" class="btn btn-secondary waves-effect waves-light">
                                    <i class="bx bx-x me-1"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary waves-effect waves-light">
                                    <i class="bx bx-save me-1"></i> Update Set
                                </button>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
document.addEventListener('DOMContentLoaded', function() {
    const toSellFields = document.getElementById('toSellFields');
    const toBuyFields = document.getElementById('toBuyFields');
    const form = document.getElementById('cryptoSetForm');
    const taskType = '<?php echo e($task->taskType); ?>'; // Get task type from PHP

    // Prevent HTML5 default validation
    form.setAttribute('novalidate', true);

    function clearValidationErrors() {
        // Clear all validation errors
        document.querySelectorAll('.is-invalid').forEach(element => {
            element.classList.remove('is-invalid');
        });
        document.querySelectorAll('.invalid-feedback').forEach(element => {
            element.textContent = '';
        });
    }

    function showError(fieldId, message) {
        const field = document.getElementById(fieldId);
        const errorElement = document.getElementById(fieldId + '-error');

        if (field && errorElement) {
            field.classList.add('is-invalid');
            errorElement.textContent = message;
        }
    }

    function validateField(fieldId, value, fieldName, isRequired = true, minValue = 0) {
        if (isRequired && (!value || value.trim() === '')) {
            showError(fieldId, `${fieldName} is required.`);
            return false;
        }

        if (value && (isNaN(value) || parseFloat(value) < minValue)) {
            showError(fieldId, `${fieldName} must be a valid number greater than or equal to ${minValue}.`);
            return false;
        }

        return true;
    }

    function validateForm() {
        clearValidationErrors();
        let isValid = true;

        // Validate fields based on task type
        if (taskType === 'to sell') {
            const currentCoinValue = document.getElementById('currentCoinValue').value;
            const startingPhpValue = document.getElementById('startingPhpValue').value;
            const minThreshold = document.getElementById('minThreshold').value;
            const intervalThreshold = document.getElementById('intervalThreshold').value;

            if (!validateField('currentCoinValue', currentCoinValue, 'Current Coin Value', true, 0)) {
                isValid = false;
            }
            if (!validateField('startingPhpValue', startingPhpValue, 'PHP Value Before Buying Coin', true, 0)) {
                isValid = false;
            }
            if (!validateField('minThreshold', minThreshold, 'Minimum Threshold', true, 0)) {
                isValid = false;
            }
            if (!validateField('intervalThreshold', intervalThreshold, 'Threshold Interval', true, 0)) {
                isValid = false;
            }
        } else if (taskType === 'to buy') {
            const toBuyCurrentCashValue = document.getElementById('toBuyCurrentCashValue').value;
            const toBuyStartingCoinValue = document.getElementById('toBuyStartingCoinValue').value;
            const toBuyMinThreshold = document.getElementById('toBuyMinThreshold').value;
            const toBuyIntervalThreshold = document.getElementById('toBuyIntervalThreshold').value;

            if (!validateField('toBuyCurrentCashValue', toBuyCurrentCashValue, 'Current PHP Value', true, 0)) {
                isValid = false;
            }
            if (!validateField('toBuyStartingCoinValue', toBuyStartingCoinValue, 'Coin Value Before Selling', true, 0)) {
                isValid = false;
            }
            if (!validateField('toBuyMinThreshold', toBuyMinThreshold, 'Minimum Threshold', true, 0)) {
                isValid = false;
            }
            if (!validateField('toBuyIntervalThreshold', toBuyIntervalThreshold, 'Threshold Interval', true, 0)) {
                isValid = false;
            }
        }

        return isValid;
    }

    function setupFields() {
        // Show/hide fields based on task type (no switching allowed)
        if (taskType === 'to sell') {
            toSellFields.classList.remove('d-none');
            toBuyFields.classList.add('d-none');

            // Enable required attributes for to sell fields only
            toSellFields.querySelectorAll('input').forEach(input => {
                input.required = true;
            });
        } else if (taskType === 'to buy') {
            toSellFields.classList.add('d-none');
            toBuyFields.classList.remove('d-none');

            // Enable required attributes for to buy fields only
            toBuyFields.querySelectorAll('input').forEach(input => {
                input.required = true;
            });
        }
    }

    // Initial setup
    setupFields();

    // Add form submission handler
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        if (validateForm()) {
            form.submit();
        }
    });

    // Add real-time validation on input
    document.querySelectorAll('input[type="number"]').forEach(input => {
        input.addEventListener('blur', function() {
            const fieldId = this.id;
            const value = this.value;
            const fieldName = this.previousElementSibling.textContent.replace(' *', '').trim();

            if (this.required) {
                validateField(fieldId, value, fieldName, true, 0);
            }
        });

        input.addEventListener('input', function() {
            // Clear error when user starts typing
            if (this.classList.contains('is-invalid')) {
                this.classList.remove('is-invalid');
                const errorElement = document.getElementById(this.id + '-error');
                if (errorElement) {
                    errorElement.textContent = '';
                }
            }
        });
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/crypto-set-update.blade.php ENDPATH**/ ?>