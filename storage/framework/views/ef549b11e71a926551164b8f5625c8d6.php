<?php $__env->startSection('title'); ?> Change Crypto Set <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Crypto <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Change Crypto Set <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title"><?php echo e($task ? 'Change Crypto Set' : 'Create New Crypto Task'); ?></h4>
                <p class="card-title-desc"><?php echo e($task ? 'Update your crypto trading task settings.' : 'Create a new crypto trading task.'); ?></p>

                <?php if(session('error')): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>
                        <?php echo e(session('error')); ?>

                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <form action="<?php echo e(route('crypto-set-change.update')); ?>" method="POST" id="cryptoSetForm">
                    <?php echo csrf_field(); ?>
                    <?php if($task): ?>
                        <input type="hidden" name="task_id" value="<?php echo e($task->id); ?>">
                    <?php endif; ?>

                    <div class="row">
                        <?php if(!$task): ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="taskCoin" class="form-label">Cryptocurrency <span class="text-danger">*</span></label>
                                <select class="form-select <?php $__errorArgs = ['taskCoin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="taskCoin" name="taskCoin" required>
                                    <option value="">Select Cryptocurrency</option>
                                    <option value="btc" <?php echo e(old('taskCoin') === 'btc' ? 'selected' : ''); ?>>Bitcoin (BTC)</option>
                                    <option value="eth" <?php echo e(old('taskCoin') === 'eth' ? 'selected' : ''); ?>>Ethereum (ETH)</option>
                                </select>
                                <?php $__errorArgs = ['taskCoin'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                        <?php endif; ?>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="taskType" class="form-label">Task Type <span class="text-danger">*</span></label>
                                <select class="form-select <?php $__errorArgs = ['taskType'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>" id="taskType" name="taskType" required>
                                    <option value="">Select Task Type</option>
                                    <option value="to sell" <?php echo e(($task && $task->taskType === 'to buy') || old('taskType') === 'to sell' ? 'selected' : ''); ?>>To Sell</option>
                                    <option value="to buy" <?php echo e(($task && $task->taskType === 'to sell') || old('taskType') === 'to buy' ? 'selected' : ''); ?>>To Buy</option>
                                </select>
                                <?php $__errorArgs = ['taskType'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?>
                                    <div class="invalid-feedback"><?php echo e($message); ?></div>
                                <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>
                            </div>
                        </div>
                    </div>

                    <!-- To Sell Fields -->
                    <div id="toSellFields" class="task-fields <?php echo e(($task && $task->taskType === 'to buy') ? '' : 'd-none'); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="currentCoinValue" class="form-label">Your Current Coin Value (<span id="coinSymbol1"><?php echo e($task ? strtoupper($task->taskCoin) : 'COIN'); ?></span>) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.00000001" class="form-control"
                                           id="currentCoinValue" name="currentCoinValue"
                                           value="<?php echo e(old('currentCoinValue')); ?>" required>
                                    <div class="invalid-feedback" id="currentCoinValue-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="startingPhpValue" class="form-label">Your Last PHP Value Before Buying Coin <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                           id="startingPhpValue" name="startingPhpValue"
                                           value="<?php echo e(old('startingPhpValue')); ?>" required>
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
                                           value="<?php echo e(old('minThreshold')); ?>" required>
                                    <div class="invalid-feedback" id="minThreshold-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="intervalThreshold" class="form-label">Threshold Interval (PHP) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                           id="intervalThreshold" name="intervalThreshold"
                                           value="<?php echo e(old('intervalThreshold')); ?>" required>
                                    <div class="invalid-feedback" id="intervalThreshold-error"></div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- To Buy Fields -->
                    <div id="toBuyFields" class="task-fields <?php echo e(($task && $task->taskType === 'to sell') ? '' : 'd-none'); ?>">
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="toBuyCurrentCashValue" class="form-label">Your Current PHP Value <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                           id="toBuyCurrentCashValue" name="toBuyCurrentCashValue"
                                           value="<?php echo e(old('toBuyCurrentCashValue')); ?>" required>
                                    <div class="invalid-feedback" id="toBuyCurrentCashValue-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="toBuyStartingCoinValue" class="form-label">Your Last Coin Value Before Selling Coin (<span id="coinSymbol2"><?php echo e($task ? strtoupper($task->taskCoin) : 'COIN'); ?></span>) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.00000001" class="form-control"
                                           id="toBuyStartingCoinValue" name="toBuyStartingCoinValue"
                                           value="<?php echo e(old('toBuyStartingCoinValue')); ?>" required>
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
                                           value="<?php echo e(old('toBuyMinThreshold')); ?>" required>
                                    <div class="invalid-feedback" id="toBuyMinThreshold-error"></div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="toBuyIntervalThreshold" class="form-label">Threshold Interval (PHP) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control"
                                           id="toBuyIntervalThreshold" name="toBuyIntervalThreshold"
                                           value="<?php echo e(old('toBuyIntervalThreshold')); ?>" required>
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
                                    <i class="bx bx-save me-1"></i> Save and Set
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
    const taskTypeSelect = document.getElementById('taskType');
    const taskCoinSelect = document.getElementById('taskCoin');
    const toSellFields = document.getElementById('toSellFields');
    const toBuyFields = document.getElementById('toBuyFields');
    const form = document.getElementById('cryptoSetForm');
    const coinSymbol1 = document.getElementById('coinSymbol1');
    const coinSymbol2 = document.getElementById('coinSymbol2');

    // Prevent HTML5 default validation
    form.setAttribute('novalidate', true);

    function updateCoinSymbols() {
        if (taskCoinSelect && coinSymbol1 && coinSymbol2) {
            const selectedCoin = taskCoinSelect.value.toUpperCase();
            coinSymbol1.textContent = selectedCoin || 'COIN';
            coinSymbol2.textContent = selectedCoin || 'COIN';
        }
    }

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
        const selectedValue = taskTypeSelect.value;

        // Validate coin selection (only for new tasks)
        if (taskCoinSelect && !taskCoinSelect.value) {
            showError('taskCoin', 'Please select a cryptocurrency.');
            isValid = false;
        }

        // Validate task type
        if (!selectedValue) {
            showError('taskType', 'Please select a task type.');
            isValid = false;
        }

        // Validate fields based on selected task type
        if (selectedValue === 'to sell') {
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
        } else if (selectedValue === 'to buy') {
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

    function toggleFields() {
        const selectedValue = taskTypeSelect.value;

        // Clear validation errors when switching
        clearValidationErrors();

        if (selectedValue === 'to sell') {
            toSellFields.classList.remove('d-none');
            toBuyFields.classList.add('d-none');

            // Enable/disable required attributes
            toSellFields.querySelectorAll('input').forEach(input => {
                input.required = true;
            });
            toBuyFields.querySelectorAll('input').forEach(input => {
                input.required = false;
            });
        } else if (selectedValue === 'to buy') {
            toSellFields.classList.add('d-none');
            toBuyFields.classList.remove('d-none');

            // Enable/disable required attributes
            toSellFields.querySelectorAll('input').forEach(input => {
                input.required = false;
            });
            toBuyFields.querySelectorAll('input').forEach(input => {
                input.required = true;
            });
        } else {
            toSellFields.classList.add('d-none');
            toBuyFields.classList.add('d-none');

            // Disable all required attributes
            toSellFields.querySelectorAll('input').forEach(input => {
                input.required = false;
            });
            toBuyFields.querySelectorAll('input').forEach(input => {
                input.required = false;
            });
        }
    }

    // Initial setup
    toggleFields();

    // Add change event listener
    taskTypeSelect.addEventListener('change', toggleFields);

    // Add coin selection event listener
    if (taskCoinSelect) {
        taskCoinSelect.addEventListener('change', updateCoinSymbols);
        updateCoinSymbols(); // Initial update
    }

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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/crypto-set-change.blade.php ENDPATH**/ ?>