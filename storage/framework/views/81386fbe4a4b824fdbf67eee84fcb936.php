<?php $__env->startSection('title'); ?> Add New Variant <?php $__env->stopSection(); ?>

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
<?php $__env->slot('title'); ?> Add New Variant <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Add New Variant</h4>
                        <p class="card-title-desc">Add a new variant for: <strong><?php echo e($product->productName); ?></strong></p>
                    </div>
                    <a href="<?php echo e(route('ecom-products.variants', ['id' => $product->id])); ?>" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back to Variants
                    </a>
                </div>

                <!-- Add Variant Form -->
                <form action="<?php echo e(route('ecom-products.variants.store')); ?>" method="POST" id="variantForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="ecomProductsId" value="<?php echo e($product->id); ?>">

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
                                       value="<?php echo e(old('ecomVariantName')); ?>"
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
                                           value="<?php echo e(old('ecomVariantPrice')); ?>"
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="costPrice" class="form-label">Cost Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control <?php $__errorArgs = ['costPrice'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           id="costPrice" name="costPrice"
                                           value="<?php echo e(old('costPrice', '0.00')); ?>"
                                           placeholder="0.00">
                                </div>
                                <div class="invalid-feedback" id="costPrice-error"></div>
                                <div class="valid-feedback" id="costPrice-success">Looks good!</div>
                                <?php $__errorArgs = ['costPrice'];
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
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="affiliatePrice" class="form-label">Affiliate Price <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="text" class="form-control <?php $__errorArgs = ['affiliatePrice'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           id="affiliatePrice" name="affiliatePrice"
                                           value="<?php echo e(old('affiliatePrice', '0.00')); ?>"
                                           placeholder="0.00">
                                </div>
                                <div class="invalid-feedback" id="affiliatePrice-error"></div>
                                <div class="valid-feedback" id="affiliatePrice-success">Looks good!</div>
                                <?php $__errorArgs = ['affiliatePrice'];
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
                                       value="<?php echo e(old('stocksAvailable')); ?>"
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
                                       value="<?php echo e(old('maxOrderPerTransaction', 1)); ?>"
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
                                          rows="3" placeholder="Enter variant description"><?php echo e(old('ecomVariantDescription')); ?></textarea>
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
                                    <i class="bx bx-save"></i> Save
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
    const costPriceInput = document.getElementById('costPrice');
    const affiliatePriceInput = document.getElementById('affiliatePrice');
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

        // Remove peso symbol and commas, then validate as number
        const cleanValue = value.replace(/[₱,\s]/g, '');
        const price = parseFloat(cleanValue);

        if (isNaN(price)) {
            showError(variantPriceInput, 'ecomVariantPrice-error', 'Please enter a valid price.');
            return false;
        } else if (price < 0) {
            showError(variantPriceInput, 'ecomVariantPrice-error', 'Price must be greater than or equal to 0.');
            return false;
        } else {
            // Format the price with peso symbol and commas
            const formattedPrice = '₱' + price.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            variantPriceInput.value = formattedPrice;
            showSuccess(variantPriceInput, 'ecomVariantPrice-success');
            return true;
        }
    }

    function validateCostPrice() {
        const value = costPriceInput.value.trim();
        if (value === '') {
            showError(costPriceInput, 'costPrice-error', 'Cost price is required.');
            return false;
        }

        // Remove peso symbol and commas, then validate as number
        const cleanValue = value.replace(/[₱,\s]/g, '');
        const price = parseFloat(cleanValue);

        if (isNaN(price)) {
            showError(costPriceInput, 'costPrice-error', 'Please enter a valid price.');
            return false;
        } else if (price < 0) {
            showError(costPriceInput, 'costPrice-error', 'Cost price must be greater than or equal to 0.');
            return false;
        } else {
            // Format the price with peso symbol and commas
            const formattedPrice = '₱' + price.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            costPriceInput.value = formattedPrice;
            showSuccess(costPriceInput, 'costPrice-success');
            return true;
        }
    }

    function validateAffiliatePrice() {
        const value = affiliatePriceInput.value.trim();
        if (value === '') {
            showError(affiliatePriceInput, 'affiliatePrice-error', 'Affiliate price is required.');
            return false;
        }

        // Remove peso symbol and commas, then validate as number
        const cleanValue = value.replace(/[₱,\s]/g, '');
        const price = parseFloat(cleanValue);

        if (isNaN(price)) {
            showError(affiliatePriceInput, 'affiliatePrice-error', 'Please enter a valid price.');
            return false;
        } else if (price < 0) {
            showError(affiliatePriceInput, 'affiliatePrice-error', 'Affiliate price must be greater than or equal to 0.');
            return false;
        } else {
            // Format the price with peso symbol and commas
            const formattedPrice = '₱' + price.toLocaleString('en-PH', {
                minimumFractionDigits: 2,
                maximumFractionDigits: 2
            });
            affiliatePriceInput.value = formattedPrice;
            showSuccess(affiliatePriceInput, 'affiliatePrice-success');
            return true;
        }
    }

    function validateStocksAvailable() {
        const value = stocksAvailableInput.value.trim();
        if (value === '') {
            showError(stocksAvailableInput, 'stocksAvailable-error', 'Stocks available is required.');
            return false;
        }

        // Remove commas and validate as integer
        const cleanValue = value.replace(/[,]/g, '');
        const stocks = parseInt(cleanValue);

        if (isNaN(stocks)) {
            showError(stocksAvailableInput, 'stocksAvailable-error', 'Please enter a valid number.');
            return false;
        } else if (stocks < 0) {
            showError(stocksAvailableInput, 'stocksAvailable-error', 'Stocks must be greater than or equal to 0.');
            return false;
        } else if (!Number.isInteger(stocks)) {
            showError(stocksAvailableInput, 'stocksAvailable-error', 'Stocks must be a whole number.');
            return false;
        } else {
            // Format with commas
            const formattedStocks = stocks.toLocaleString('en-PH');
            stocksAvailableInput.value = formattedStocks;
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
            showError(variantDescriptionInput, 'ecomVariantDescription-error', 'Description must not exceed 1000 characters.');
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

    costPriceInput.addEventListener('blur', validateCostPrice);
    costPriceInput.addEventListener('input', function() {
        clearValidation(costPriceInput, 'costPrice-error', 'costPrice-success');
    });

    affiliatePriceInput.addEventListener('blur', validateAffiliatePrice);
    affiliatePriceInput.addEventListener('input', function() {
        clearValidation(affiliatePriceInput, 'affiliatePrice-error', 'affiliatePrice-success');
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
        const isCostPriceValid = validateCostPrice();
        const isAffiliatePriceValid = validateAffiliatePrice();
        const isStocksAvailableValid = validateStocksAvailable();
        const isMaxOrderPerTransactionValid = validateMaxOrderPerTransaction();
        const isVariantDescriptionValid = validateVariantDescription();

        if (isVariantNameValid && isVariantPriceValid && isCostPriceValid && isAffiliatePriceValid && isStocksAvailableValid && isMaxOrderPerTransactionValid && isVariantDescriptionValid) {
            // Clean up the values before submission
            const cleanPrice = variantPriceInput.value.replace(/[₱,\s]/g, '');
            const cleanCostPrice = costPriceInput.value.replace(/[₱,\s]/g, '');
            const cleanAffiliatePrice = affiliatePriceInput.value.replace(/[₱,\s]/g, '');
            const cleanStocks = stocksAvailableInput.value.replace(/[,]/g, '');

            // Create hidden inputs with clean values
            const priceInput = document.createElement('input');
            priceInput.type = 'hidden';
            priceInput.name = 'ecomVariantPrice';
            priceInput.value = cleanPrice;

            const costPriceHiddenInput = document.createElement('input');
            costPriceHiddenInput.type = 'hidden';
            costPriceHiddenInput.name = 'costPrice';
            costPriceHiddenInput.value = cleanCostPrice;

            const affiliatePriceHiddenInput = document.createElement('input');
            affiliatePriceHiddenInput.type = 'hidden';
            affiliatePriceHiddenInput.name = 'affiliatePrice';
            affiliatePriceHiddenInput.value = cleanAffiliatePrice;

            const stocksInput = document.createElement('input');
            stocksInput.type = 'hidden';
            stocksInput.name = 'stocksAvailable';
            stocksInput.value = cleanStocks;

            // Remove the original inputs and add clean ones
            variantPriceInput.remove();
            costPriceInput.remove();
            affiliatePriceInput.remove();
            stocksAvailableInput.remove();
            form.appendChild(priceInput);
            form.appendChild(costPriceHiddenInput);
            form.appendChild(affiliatePriceHiddenInput);
            form.appendChild(stocksInput);

            // Submit the form
            form.submit();
        } else {
            // Focus on the first invalid field
            if (!isVariantNameValid) {
                variantNameInput.focus();
            } else if (!isVariantPriceValid) {
                variantPriceInput.focus();
            } else if (!isCostPriceValid) {
                costPriceInput.focus();
            } else if (!isAffiliatePriceValid) {
                affiliatePriceInput.focus();
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

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/products/variants/create.blade.php ENDPATH**/ ?>