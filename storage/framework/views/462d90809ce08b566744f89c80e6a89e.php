<?php $__env->startSection('title'); ?> Edit Product <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> E-commerce <?php $__env->endSlot(); ?>
<?php $__env->slot('li_2'); ?> Products <?php $__env->endSlot(); ?>
<?php $__env->slot('li_3'); ?> Edit <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Edit Product <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title">Edit Product</h4>
                <p class="card-title-desc">Update the product information below.</p>

                <?php if($errors->any()): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>
                        <strong>Error!</strong> Please fix the following errors:
                        <ul class="mb-0 mt-2">
                            <?php $__currentLoopData = $errors->all(); $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $error): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <li><?php echo e($error); ?></li>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </ul>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <?php if($product): ?>
                    <form id="editProductForm" method="POST" action="<?php echo e(route('ecom-products.update', $product->id)); ?>">
                        <?php echo csrf_field(); ?>
                        <?php echo method_field('PUT'); ?>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="productName" class="form-label">Product Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?php $__errorArgs = ['productName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           id="productName" name="productName"
                                           value="<?php echo e(old('productName', $product->productName)); ?>"
                                           placeholder="Enter product name">
                                    <div class="invalid-feedback" id="productNameError"></div>
                                </div>
                            </div>

                            <div class="col-md-6">
                            <div class="mb-3">
                                <label for="productStore" class="form-label">Product Store <span class="text-danger">*</span></label>
                                <select class="form-select <?php $__errorArgs = ['productStore'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                        id="productStore" name="productStore">
                                    <option value="">Select a store</option>
                                    <?php $__currentLoopData = $stores; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $store): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <option value="<?php echo e($store->storeName); ?>" <?php echo e(old('productStore', $product->productStore) == $store->storeName ? 'selected' : ''); ?>><?php echo e($store->storeName); ?></option>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </select>
                                <div class="invalid-feedback" id="productStoreError"></div>
                            </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="productType" class="form-label">Product Type <span class="text-danger">*</span></label>
                                    <select class="form-select <?php $__errorArgs = ['productType'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                            id="productType" name="productType">
                                        <option value="">Select product type</option>
                                        <option value="access" <?php echo e(old('productType', $product->productType) == 'access' ? 'selected' : ''); ?>>Access</option>
                                        <option value="ship" <?php echo e(old('productType', $product->productType) == 'ship' ? 'selected' : ''); ?>>Ship</option>
                                    </select>
                                    <div class="invalid-feedback" id="productTypeError"></div>
                                </div>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="productDescription" class="form-label">Product Description <span class="text-danger">*</span></label>
                            <textarea class="form-control <?php $__errorArgs = ['productDescription'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                      id="productDescription" name="productDescription"
                                      rows="4"
                                      placeholder="Enter product description"><?php echo e(old('productDescription', $product->productDescription)); ?></textarea>
                            <div class="invalid-feedback" id="productDescriptionError"></div>
                        </div>

                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?php echo e(route('ecom-products')); ?>" class="btn btn-secondary">
                                <i class="bx bx-arrow-back"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save"></i> Update Product
                            </button>
                        </div>
                    </form>
                <?php else: ?>
                    <div class="alert alert-warning">
                        <i class="bx bx-exclamation-triangle me-2"></i>
                        <strong>Warning!</strong> The requested product could not be found.
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<script>
$(document).ready(function() {
    // Remove validation classes on input
    $('input, select, textarea').on('input change', function() {
        $(this).removeClass('is-invalid');
        $('#' + $(this).attr('id') + 'Error').text('');
    });

    // Form submission with dynamic validation
    $('#editProductForm').on('submit', function(e) {
        e.preventDefault();

        // Reset previous errors
        $('.is-invalid').removeClass('is-invalid');
        $('.invalid-feedback').text('');

        let isValid = true;
        const errors = {};

        // Validate Product Name
        const productName = $('#productName').val().trim();
        if (!productName) {
            $('#productName').addClass('is-invalid');
            $('#productNameError').text('Product name is required.');
            isValid = false;
            errors.productName = 'Product name is required.';
        }

        // Validate Product Store
        const productStore = $('#productStore').val();
        if (!productStore) {
            $('#productStore').addClass('is-invalid');
            $('#productStoreError').text('Product store is required.');
            isValid = false;
            errors.productStore = 'Product store is required.';
        }

        // Validate Product Type
        const productType = $('#productType').val();
        if (!productType) {
            $('#productType').addClass('is-invalid');
            $('#productTypeError').text('Product type is required.');
            isValid = false;
            errors.productType = 'Product type is required.';
        }

        // Validate Product Description
        const productDescription = $('#productDescription').val().trim();
        if (!productDescription) {
            $('#productDescription').addClass('is-invalid');
            $('#productDescriptionError').text('Product description is required.');
            isValid = false;
            errors.productDescription = 'Product description is required.';
        }

        // If validation passes, submit the form
        if (isValid) {
            this.submit();
        } else {
            // Scroll to first error
            $('html, body').animate({
                scrollTop: $('.is-invalid').first().offset().top - 100
            }, 500);
        }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/products/edit.blade.php ENDPATH**/ ?>