<?php $__env->startSection('title'); ?>
    Product Discounts
<?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
    <!-- Add any specific CSS for discounts page here -->
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
    <?php $__env->startComponent('components.breadcrumb'); ?>
        <?php $__env->slot('li_1'); ?>
            E-commerce
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('li_2'); ?>
            Products
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('li_3'); ?>
            Discounts
        <?php $__env->endSlot(); ?>
        <?php $__env->slot('title'); ?>
            Product Discounts
        <?php $__env->endSlot(); ?>
    <?php echo $__env->renderComponent(); ?>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <div>
                            <h4 class="card-title">Product Discounts</h4>
                            <p class="card-title-desc">Manage discounts for: <strong><?php echo e($product->productName); ?></strong></p>
                        </div>
                        <a href="<?php echo e(route('ecom-products')); ?>" class="btn btn-secondary">
                            <i class="bx bx-arrow-back me-1"></i>Back to Products
                        </a>
                    </div>

                    <!-- Discounts content will go here -->
                    <div class="text-center py-5">
                        <i class="bx bx-tag display-1 text-muted"></i>
                        <h5 class="mt-3 text-muted">Discounts Management</h5>
                        <p class="text-muted">This page is currently blank. Add discount management functionality here.</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
    <!-- Add any specific JavaScript for discounts page here -->
    <script>
        // Discounts page specific JavaScript will go here
        console.log('Product Discounts page loaded');
    </script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/ecommerce/products/discounts.blade.php ENDPATH**/ ?>