<?php $__env->startSection('title'); ?> Add New Access Tag - <?php echo e($course->courseName); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<!-- Custom styles -->
<style>
.form-section {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.form-section h5 {
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

.empty-state {
    text-align: center;
    padding: 3rem 1rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 3rem;
    margin-bottom: 1rem;
    opacity: 0.5;
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Ani-Senso <?php $__env->endSlot(); ?>
<?php $__env->slot('li_2'); ?> Courses <?php $__env->endSlot(); ?>
<?php $__env->slot('li_3'); ?> <?php echo e($course->courseName); ?> <?php $__env->endSlot(); ?>
<?php $__env->slot('li_4'); ?> Access Tags <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Add New Access Tag <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Add New Access Tag</h4>
                    <p class="card-title-desc">Create a new access tag for "<?php echo e($course->courseName); ?>"</p>
                </div>
                <a href="<?php echo e(route('anisenso-courses-tags', ['id' => $course->id])); ?>" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Access Tags
                </a>
            </div>
            <div class="card-body">
                <form action="<?php echo e(route('anisenso-courses-tags.store')); ?>" method="POST" id="accessTagForm">
                    <?php echo csrf_field(); ?>
                    <input type="hidden" name="courseId" value="<?php echo e($course->id); ?>">

                    <!-- Tag Information -->
                    <div class="form-section">
                        <h5><i class="bx bx-tag me-2"></i>Access Tag Information</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tagName" class="form-label">Tag Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control <?php $__errorArgs = ['tagName'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           id="tagName" name="tagName" value="<?php echo e(old('tagName')); ?>"
                                           placeholder="Enter tag name">
                                    <?php $__errorArgs = ['tagName'];
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
                                    <label for="expirationLength" class="form-label">Expiration Length In Days <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control <?php $__errorArgs = ['expirationLength'];
$__bag = $errors->getBag($__errorArgs[1] ?? 'default');
if ($__bag->has($__errorArgs[0])) :
if (isset($message)) { $__messageOriginal = $message; }
$message = $__bag->first($__errorArgs[0]); ?> is-invalid <?php unset($message);
if (isset($__messageOriginal)) { $message = $__messageOriginal; }
endif;
unset($__errorArgs, $__bag); ?>"
                                           id="expirationLength" name="expirationLength" value="<?php echo e(old('expirationLength')); ?>"
                                           placeholder="Enter number of days">
                                    <?php $__errorArgs = ['expirationLength'];
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
                    </div>

                    <!-- Form Actions -->
                    <div class="form-section">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="<?php echo e(route('anisenso-courses-tags', ['id' => $course->id])); ?>" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Save Access Tag
                            </button>
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
$(document).ready(function() {
    console.log('Add new access tag page loaded for course: <?php echo e($course->courseName); ?>');
});

// Form validation
$('#accessTagForm').on('submit', function(e) {
    let isValid = true;

    // Clear any existing validation errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Validate tag name
    if ($('#tagName').val().trim() === '') {
        $('#tagName').addClass('is-invalid');
        $('#tagName').after('<div class="invalid-feedback">Tag name is required.</div>');
        isValid = false;
    }

    // Validate expiration length
    if ($('#expirationLength').val().trim() === '' || parseInt($('#expirationLength').val()) < 1) {
        $('#expirationLength').addClass('is-invalid');
        $('#expirationLength').after('<div class="invalid-feedback">Expiration length must be at least 1 day.</div>');
        isValid = false;
    }

    if (!isValid) {
        e.preventDefault();
        // Scroll to first error
        $('html, body').animate({
            scrollTop: $('.is-invalid').first().offset().top - 100
        }, 500);
        return false;
    }

    // If validation passes, allow form submission
    console.log('Form validation passed, submitting...');
    return true;
});

// Real-time validation on input
$('#tagName').on('input blur', function() {
    if ($(this).val().trim() === '') {
        $(this).addClass('is-invalid');
        if (!$(this).siblings('.invalid-feedback').length) {
            $(this).after('<div class="invalid-feedback">Tag name is required.</div>');
        }
    } else {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
    }
});

$('#expirationLength').on('input blur', function() {
    if ($(this).val().trim() === '' || parseInt($(this).val()) < 1) {
        $(this).addClass('is-invalid');
        if (!$(this).siblings('.invalid-feedback').length) {
            $(this).after('<div class="invalid-feedback">Expiration length must be at least 1 day.</div>');
        }
    } else {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
    }
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/aniSensoAdmin/course-tags-add.blade.php ENDPATH**/ ?>