<?php $__env->startSection('title'); ?> Ani-Senso Courses <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<!-- DataTables -->
<link href="<?php echo e(URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
<!-- Sweet Alert -->
<link href="<?php echo e(URL::asset('/build/libs/sweetalert2/sweetalert2.min.css')); ?>" rel="stylesheet" type="text/css" />

<style>
.course-card {
    transition: all 0.3s ease;
    border: none;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
}

.course-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.course-image {
    width: 170px !important;
    height: 170px !important;
    object-fit: cover;
    border: 8px solid #f8f9fa;
    border-radius: 50%;
}

.course-placeholder {
    width: 120px !important;
    height: 120px !important;
    border: 3px solid #f8f9fa;
    border-radius: 50%;
}

.course-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
    line-height: 1.3;
}

.course-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #27ae60;
}

.price-currency {
    font-size: 1rem;
    margin-right: 2px;
}

.price-amount {
    font-size: 1.5rem;
}

.course-description {
    font-size: 0.9rem;
    line-height: 1.5;
    color: #6c757d;
}

.course-actions .btn {
    font-size: 0.8rem;
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    border: none;
    color: white;
    min-width: 80px;
    white-space: nowrap;
}

.course-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.course-actions .btn-outline-primary {
    background-color: #3498db;
    color: white;
}

.course-actions .btn-outline-primary:hover {
    background-color: #2980b9;
    color: white;
}

.course-actions .btn-outline-warning {
    background-color: #f39c12;
    color: white;
}

.course-actions .btn-outline-warning:hover {
    background-color: #e67e22;
    color: white;
}

.course-actions .btn-outline-danger {
    background-color: #e74c3c;
    color: white;
}

.course-actions .btn-outline-danger:hover {
    background-color: #c0392b;
    color: white;
}

.course-actions .btn-outline-info {
    background-color: #17a2b8;
    color: white;
}

.course-actions .btn-outline-info:hover {
    background-color: #138496;
    color: white;
}

.course-actions .btn-outline-success {
    background-color: #28a745;
    color: white;
}

.course-actions .btn-outline-success:hover {
    background-color: #1e7e34;
    color: white;
}

.card-body {
    padding: 1.5rem !important;
    border: 1px solid #dedede;
    border-radius: 8px;
}

/* Responsive Design */
@media (max-width: 1200px) {
    .course-image, .course-placeholder {
        width: 140px !important;
        height: 140px !important;
    }
}

@media (max-width: 768px) {
    .course-image, .course-placeholder {
        width: 100px !important;
        height: 100px !important;
    }

    .course-title {
        font-size: 1.1rem;
    }

    .course-price {
        font-size: 1.25rem;
    }

    .course-actions .btn {
        font-size: 0.75rem;
        padding: 0.4rem 0.5rem;
    }

    .course-actions .btn i {
        font-size: 0.8rem;
    }
}

@media (max-width: 576px) {
    .course-image, .course-placeholder {
        width: 80px !important;
        height: 80px !important;
    }

    .course-title {
        font-size: 1rem;
    }

    .course-price {
        font-size: 1.1rem;
    }

    .course-description {
        font-size: 0.8rem;
    }

    .course-actions .btn {
        font-size: 0.7rem;
        padding: 0.35rem 0.4rem;
    }

    .course-actions .btn i {
        font-size: 0.75rem;
    }

    .card-body {
        padding: 1rem !important;
    }
}

@media (max-width: 480px) {
    .course-actions .btn {
        font-size: 0.65rem;
        padding: 0.3rem 0.35rem;
    }

    .course-actions .btn i {
        font-size: 0.7rem;
    }
}

/* Delete Modal Styles */
#deleteCourseModal .modal-header {
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

#deleteCourseModal .modal-title {
    color: #dc3545;
    font-weight: 600;
}

#deleteCourseModal .modal-body {
    padding: 1.5rem;
}

#deleteCourseModal .btn-danger {
    background-color: #dc3545;
    border-color: #dc3545;
}

#deleteCourseModal .btn-danger:hover {
    background-color: #c82333;
    border-color: #bd2130;
}

#deleteCourseModal .btn-danger:disabled {
    background-color: #6c757d;
    border-color: #6c757d;
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Ani-Senso <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Course Management <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<!-- Success Message -->
<?php if(session('success')): ?>
<div class="alert alert-success alert-dismissible fade show" role="alert">
    <?php echo e(session('success')); ?>

    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
<?php endif; ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3">
                <div>
                    <h4 class="card-title mb-1">Ani-Senso Courses</h4>
                    <p class="card-title-desc mb-0">Manage your courses</p>
                </div>
                <a href="<?php echo e(route('anisenso-courses-add')); ?>" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Add New Course
                </a>
            </div>
            <div class="card-body">
                <?php if($courses->count() > 0): ?>
                    <div class="row">
                        <?php $__currentLoopData = $courses; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $course): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                        <div class="col-xl-4 col-lg-6 col-md-6 col-sm-12 mb-4">
                            <div class="card course-card h-100 shadow-sm border-0">
                                <div class="card-body p-4">
                                    <div class="text-center mb-4">
                                        <?php if($course->courseImage): ?>
                                            <img src="<?php echo e(asset($course->courseImage)); ?>" alt="<?php echo e($course->courseName); ?>" class="course-image shadow-sm">
                                        <?php else: ?>
                                            <div class="course-placeholder d-inline-flex align-items-center justify-content-center <?php echo e($course->placeholder_color); ?> shadow-sm">
                                                <span class="text-white fw-bold display-6"><?php echo e($course->first_letter); ?></span>
                                            </div>
                                        <?php endif; ?>
                                    </div>

                                    <div class="text-center">
                                        <h4 class="course-title mb-2"><?php echo e($course->courseName); ?></h4>
                                        <div class="course-price mb-3">
                                            <span class="price-currency">₱</span>
                                            <span class="price-amount"><?php echo e(number_format($course->coursePrice, 2)); ?></span>
                                        </div>

                                        <?php if($course->courseSmallDescription): ?>
                                            <p class="course-description text-muted mb-4"><?php echo e(Str::limit($course->courseSmallDescription, 100)); ?></p>
                                        <?php endif; ?>

                                        <div class="course-actions">
                                            <div class="d-flex flex-wrap gap-2 justify-content-center">
                                                <button type="button" class="btn btn-outline-primary btn-sm" onclick="viewCourse(<?php echo e($course->id); ?>)">
                                                    <i class="bx bx-book-open me-1"></i> Contents
                                                </button>
                                                <button type="button" class="btn btn-outline-info btn-sm" onclick="viewComments(<?php echo e($course->id); ?>)">
                                                    <i class="bx bx-message-square-dots me-1"></i> Comments
                                                </button>
                                                <button type="button" class="btn btn-outline-success btn-sm" onclick="viewQuestions(<?php echo e($course->id); ?>)">
                                                    <i class="bx bx-help-circle me-1"></i> Questions
                                                </button>
                                                <button type="button" class="btn btn-outline-warning btn-sm" onclick="editCourse(<?php echo e($course->id); ?>)">
                                                    <i class="bx bx-edit me-1"></i> Edit
                                                </button>
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteCourse(<?php echo e($course->id); ?>)">
                                                    <i class="bx bx-trash me-1"></i> Delete
                                                </button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bx bx-book-open display-1 text-muted"></i>
                        <h4 class="mt-3">No Courses Found</h4>
                        <p class="text-muted">Start by adding your first course.</p>
                        <a href="<?php echo e(route('anisenso-courses-add')); ?>" class="btn btn-primary">
                            <i class="bx bx-plus"></i> Add First Course
                        </a>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>



<!-- Delete Course Confirmation Modal -->
<div class="modal fade" id="deleteCourseModal" tabindex="-1" aria-labelledby="deleteCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteCourseModalLabel">
                    <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="mb-0">
                    <strong>Are you sure you want to delete this course?</strong>
                </p>
                <p class="text-muted small mt-2">
                    This action will hide the course from the list but can be restored later if needed.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i> Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bx bx-trash me-1"></i> Yes, Delete Course
                </button>
            </div>
        </div>
    </div>
</div>

<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<!-- Required datatable js -->
<script src="<?php echo e(URL::asset('/build/libs/datatables.net/js/jquery.dataTables.min.js')); ?>"></script>
<!-- Sweet Alert -->
<script src="<?php echo e(URL::asset('/build/libs/sweetalert2/sweetalert2.min.js')); ?>"></script>

<script>
$(document).ready(function() {
    console.log('Ani-Senso Course page loaded');
});

function viewCourse(courseId) {
    // Redirect to the course contents page
    window.location.href = `/anisenso-courses-contents?id=${courseId}`;
}

function editCourse(courseId) {
    // Redirect to the edit page
    window.location.href = `/anisenso-courses-edit?id=${courseId}`;
}

function deleteCourse(courseId) {
    // Show delete confirmation modal
    $('#deleteCourseModal').modal('show');
    $('#confirmDeleteBtn').data('course-id', courseId);
}

function viewComments(courseId) {
    // Redirect to the comments page
    window.location.href = `/anisenso-courses-comments?id=${courseId}`;
}

function viewQuestions(courseId) {
    // Redirect to the questions page
    window.location.href = `/anisenso-courses-questions?id=${courseId}`;
}

// Handle delete confirmation
$('#confirmDeleteBtn').on('click', function() {
    var courseId = $(this).data('course-id');
    var $modal = $('#deleteCourseModal');
    var $btn = $(this);

    // Disable button and show loading state
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

    $.ajax({
        url: `/anisenso-courses/${courseId}`,
        method: 'DELETE',
        data: {
            _token: '<?php echo e(csrf_token()); ?>'
        },
        success: function(response) {
            // Close modal
            $modal.modal('hide');

            // Show success message
            Swal.fire({
                title: 'Deleted!',
                text: 'Course has been successfully deleted.',
                icon: 'success',
                confirmButtonText: 'OK'
            }).then(() => {
                // Reload the page to reflect changes
                location.reload();
            });
        },
        error: function(xhr) {
            // Close modal
            $modal.modal('hide');

            // Show error message
            Swal.fire({
                title: 'Error!',
                text: 'Failed to delete course. Please try again.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        },
        complete: function() {
            // Reset button state
            $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Yes, Delete Course');
        }
    });
});
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/aniSensoAdmin/courses.blade.php ENDPATH**/ ?>