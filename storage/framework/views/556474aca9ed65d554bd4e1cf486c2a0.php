<?php $__env->startSection('title'); ?> Edit Variant <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<style>
    .form-control.is-invalid {
        border-color: #dc3545;
        box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
    }

    .form-control.is-valid {
        border-color: #198754;
        box-shadow: 0 0 0 0.2rem rgba(25, 135, 84, 0.25);
    }

    .invalid-feedback {
        display: block;
    }

    .valid-feedback {
        display: block;
    }
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> E-commerce <?php $__env->endSlot(); ?>
<?php $__env->slot('li_2'); ?> Products <?php $__env->endSlot(); ?>
<?php $__env->slot('li_3'); ?> Variants <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Edit Variant <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Edit Variant</h4>
                        <p class="card-title-desc">Edit variant: <strong><?php echo e($variant->ecomVariantName); ?></strong> (<?php echo e($product->productName); ?>)</p>
                    </div>
                    <a href="<?php echo e(route('ecom-products.variants', ['id' => $product->id])); ?>" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back to Variants
                    </a>
                </div>

                <!-- Edit Variant Form -->
                <form action="<?php echo e(route('ecom-products.variants.update')); ?>" method="POST" id="variantForm">
                    <?php echo csrf_field(); ?>
                    <?php echo method_field('PUT'); ?>
                    <input type="hidden" name="variantId" value="<?php echo e($variant->id); ?>">

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="ecomVariantName" class="form-label">Variant Name <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php $__errorArgs = ['ecomVariantName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="ecomVariantName" name="ecomVariantName"
                                       value="<?php echo e(old('ecomVariantName', $variant->ecomVariantName)); ?>"
                                       placeholder="Enter variant name">
                                <div class="invalid-feedback" id="ecomVariantName-error"></div>
                                <div class="valid-feedback" id="ecomVariantName-success">Looks good!</div>
                                <?php $__errorArgs = ['ecomVariantName'];
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

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="ecomVariantPrice" class="form-label">Variant Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control <?php $__errorArgs = ['ecomVariantPrice'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           id="ecomVariantPrice" name="ecomVariantPrice"
                                           value="<?php echo e(old('ecomVariantPrice', number_format($variant->ecomVariantPrice, 2))); ?>"
                                           placeholder="0.00">
                                </div>
                                <div class="invalid-feedback" id="ecomVariantPrice-error"></div>
                                <div class="valid-feedback" id="ecomVariantPrice-success">Looks good!</div>
                                <?php $__errorArgs = ['ecomVariantPrice'];
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

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="stocksAvailable" class="form-label">Stocks Available <span class="text-danger">*</span></label>
                                <input type="text" class="form-control <?php $__errorArgs = ['stocksAvailable'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="stocksAvailable" name="stocksAvailable"
                                       value="<?php echo e(old('stocksAvailable', $variant->stocksAvailable)); ?>"
                                       placeholder="0">
                                <div class="invalid-feedback" id="stocksAvailable-error"></div>
                                <div class="valid-feedback" id="stocksAvailable-success">Looks good!</div>
                                <?php $__errorArgs = ['stocksAvailable'];
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

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="maxOrderPerTransaction" class="form-label">Maximum Number of Order per Transaction <span class="text-danger">*</span></label>
                                <input type="number" class="form-control <?php $__errorArgs = ['maxOrderPerTransaction'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                       id="maxOrderPerTransaction" name="maxOrderPerTransaction"
                                       value="<?php echo e(old('maxOrderPerTransaction', $variant->maxOrderPerTransaction ?? 1)); ?>"
                                       min="1" placeholder="1">
                                <div class="invalid-feedback" id="maxOrderPerTransaction-error"></div>
                                <div class="valid-feedback" id="maxOrderPerTransaction-success">Looks good!</div>
                                <?php $__errorArgs = ['maxOrderPerTransaction'];
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

                    <div class="row">
                        <div class="col-12">
                            <div class="mb-3">
                                <label for="ecomVariantDescription" class="form-label">Variant Description <span class="text-danger">*</span></label>
                                <textarea class="form-control <?php $__errorArgs = ['ecomVariantDescription'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                          id="ecomVariantDescription" name="ecomVariantDescription"
                                          rows="3" placeholder="Enter variant description"><?php echo e(old('ecomVariantDescription', $variant->ecomVariantDescription)); ?></textarea>
                                <div class="invalid-feedback" id="ecomVariantDescription-error"></div>
                                <div class="valid-feedback" id="ecomVariantDescription-success">Looks good!</div>
                                <?php $__errorArgs = ['ecomVariantDescription'];
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

                    <div class="row">
                        <div class="col-12">
                            <div class="d-flex justify-content-end gap-2">
                                <a href="<?php echo e(route('ecom-products.variants', ['id' => $product->id])); ?>" class="btn btn-secondary">
                                    <i class="bx bx-x"></i> Cancel
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="bx bx-save"></i> Update Variant
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
    const form = document.getElementById('variantForm');
    const variantNameInput = document.getElementById('ecomVariantName');
    const variantPriceInput = document.getElementById('ecomVariantPrice');
    const stocksAvailableInput = document.getElementById('stocksAvailable');
    const maxOrderPerTransactionInput = document.getElementById('maxOrderPerTransaction');
    const variantDescriptionInput = document.getElementById('ecomVariantDescription');

    // Validation functions
    function validateVariantName() {
        const value = variantNameInput.value.trim();
        if (value === '') {
            showError(variantNameInput, 'ecomVariantName-error', 'Variant name is required.');
            return false;
        } else if (value.length > 255) {
            showError(variantNameInput, 'ecomVariantName-error', 'Variant name must not exceed 255 characters.');
            return false;
        } else {
            showSuccess(variantNameInput, 'ecomVariantName-success');
            return true;
        }
    }

    function validateVariantPrice() {
        const value = variantPriceInput.value.trim();
        if (value === '') {
            showError(variantPriceInput, 'ecomVariantPrice-error', 'Variant price is required.');
            return false;
        }

        // Remove currency symbol and commas, then validate
        const cleanValue = value.replace(/[₱,\s]/g, '');
        const price = parseFloat(cleanValue);

        if (isNaN(price)) {
            showError(variantPriceInput, 'ecomVariantPrice-error', 'Variant price must be a valid number.');
            return false;
        } else if (price < 0) {
            showError(variantPriceInput, 'ecomVariantPrice-error', 'Variant price must be greater than or equal to 0.');
            return false;
        } else {
            showSuccess(variantPriceInput, 'ecomVariantPrice-success');
            return true;
        }
    }

    function validateStocksAvailable() {
        const value = stocksAvailableInput.value.trim();
        if (value === '') {
            showError(stocksAvailableInput, 'stocksAvailable-error', 'Stocks available is required.');
            return false;
        }

        // Remove commas and validate
        const cleanValue = value.replace(/[,]/g, '');
        const stocks = parseInt(cleanValue);

        if (isNaN(stocks)) {
            showError(stocksAvailableInput, 'stocksAvailable-error', 'Stocks available must be a valid number.');
            return false;
        } else if (stocks < 0) {
            showError(stocksAvailableInput, 'stocksAvailable-error', 'Stocks available must be greater than or equal to 0.');
            return false;
        } else {
            showSuccess(stocksAvailableInput, 'stocksAvailable-success');
            return true;
        }
    }

    function validateMaxOrderPerTransaction() {
        const value = maxOrderPerTransactionInput.value.trim();
        if (value === '') {
            showError(maxOrderPerTransactionInput, 'maxOrderPerTransaction-error', 'Maximum order per transaction is required.');
            return false;
        }

        const maxOrder = parseInt(value);

        if (isNaN(maxOrder)) {
            showError(maxOrderPerTransactionInput, 'maxOrderPerTransaction-error', 'Please enter a valid number.');
            return false;
        } else if (maxOrder < 1) {
            showError(maxOrderPerTransactionInput, 'maxOrderPerTransaction-error', 'Maximum order per transaction must be at least 1.');
            return false;
        } else if (!Number.isInteger(maxOrder)) {
            showError(maxOrderPerTransactionInput, 'maxOrderPerTransaction-error', 'Maximum order per transaction must be a whole number.');
            return false;
        } else {
            showSuccess(maxOrderPerTransactionInput, 'maxOrderPerTransaction-success');
            return true;
        }
    }

    function validateVariantDescription() {
        const value = variantDescriptionInput.value.trim();
        if (value === '') {
            showError(variantDescriptionInput, 'ecomVariantDescription-error', 'Variant description is required.');
            return false;
        } else if (value.length > 1000) {
            showError(variantDescriptionInput, 'ecomVariantDescription-error', 'Variant description must not exceed 1000 characters.');
            return false;
        } else {
            showSuccess(variantDescriptionInput, 'ecomVariantDescription-success');
            return true;
        }
    }

    function showError(input, errorId, message) {
        input.classList.remove('is-valid');
        input.classList.add('is-invalid');
        document.getElementById(errorId).textContent = message;
        document.getElementById(errorId).style.display = 'block';
        document.getElementById(errorId.replace('-error', '-success')).style.display = 'none';
    }

    function showSuccess(input, successId) {
        input.classList.remove('is-invalid');
        input.classList.add('is-valid');
        document.getElementById(successId).style.display = 'block';
        document.getElementById(successId.replace('-success', '-error')).style.display = 'none';
    }

    function clearValidation(input, errorId, successId) {
        input.classList.remove('is-valid', 'is-invalid');
        document.getElementById(errorId).style.display = 'none';
        document.getElementById(successId).style.display = 'none';
    }

    // Event listeners for real-time validation
    variantNameInput.addEventListener('blur', validateVariantName);
    variantNameInput.addEventListener('input', function() {
        clearValidation(variantNameInput, 'ecomVariantName-error', 'ecomVariantName-success');
    });

    variantPriceInput.addEventListener('blur', validateVariantPrice);
    variantPriceInput.addEventListener('input', function() {
        clearValidation(variantPriceInput, 'ecomVariantPrice-error', 'ecomVariantPrice-success');
    });

    stocksAvailableInput.addEventListener('blur', validateStocksAvailable);
    stocksAvailableInput.addEventListener('input', function() {
        clearValidation(stocksAvailableInput, 'stocksAvailable-error', 'stocksAvailable-success');
    });

    maxOrderPerTransactionInput.addEventListener('blur', validateMaxOrderPerTransaction);
    maxOrderPerTransactionInput.addEventListener('input', function() {
        clearValidation(maxOrderPerTransactionInput, 'maxOrderPerTransaction-error', 'maxOrderPerTransaction-success');
    });

    variantDescriptionInput.addEventListener('blur', validateVariantDescription);
    variantDescriptionInput.addEventListener('input', function() {
        clearValidation(variantDescriptionInput, 'ecomVariantDescription-error', 'ecomVariantDescription-success');
    });

    // Form submission validation
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        const isVariantNameValid = validateVariantName();
        const isVariantPriceValid = validateVariantPrice();
        const isStocksAvailableValid = validateStocksAvailable();
        const isMaxOrderPerTransactionValid = validateMaxOrderPerTransaction();
        const isVariantDescriptionValid = validateVariantDescription();

        if (isVariantNameValid && isVariantPriceValid && isStocksAvailableValid && isMaxOrderPerTransactionValid && isVariantDescriptionValid) {
            // Clean up the values before submission
            const cleanPrice = variantPriceInput.value.replace(/[₱,\s]/g, '');
            const cleanStocks = stocksAvailableInput.value.replace(/[,]/g, '');

            // Set the cleaned values directly to the inputs
            variantPriceInput.value = cleanPrice;
            stocksAvailableInput.value = cleanStocks;

            // Submit the form
            form.submit();
        } else {
            // Focus on the first invalid field
            if (!isVariantNameValid) {
                variantNameInput.focus();
            } else if (!isVariantPriceValid) {
                variantPriceInput.focus();
            } else if (!isStocksAvailableValid) {
                stocksAvailableInput.focus();
            } else if (!isMaxOrderPerTransactionValid) {
                maxOrderPerTransactionInput.focus();
            } else if (!isVariantDescriptionValid) {
                variantDescriptionInput.focus();
            }
        }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/products/variants/edit.blade.php ENDPATH**/ ?>