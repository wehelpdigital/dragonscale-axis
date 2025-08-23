

<?php $__env->startSection('title'); ?> Welcome <?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>

<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Welcome <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Welcome Page <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="text-center">
                    <h1 class="display-4 text-primary">Welcome!</h1>
                    <p class="lead">You have successfully logged in to your account.</p>
                    <p class="text-muted">This is your welcome page after login.</p>
                </div>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/welcome.blade.php ENDPATH**/ ?>