

<?php $__env->startSection('title'); ?> Course Topics - <?php echo e($course->courseName); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<!-- DataTables -->
<link href="<?php echo e(URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
<!-- Sweet Alert -->
<link href="<?php echo e(URL::asset('/build/libs/sweetalert2/sweetalert2.min.css')); ?>" rel="stylesheet" type="text/css" />

<style>
.topics-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chapter-section {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
}

.chapter-section:last-child {
    border-bottom: none;
}

.chapter-header {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.chapter-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #495057;
    margin: 0;
}

.topic-row {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    background: white;
    transition: all 0.2s ease;
}

.topic-row:hover {
    background: #f8f9fa;
}

.topic-row:last-child {
    border-bottom: none;
}

.topic-title {
    flex: 1;
    font-weight: 500;
    color: #495057;
}

.topic-description {
    flex: 2;
    color: #6c757d;
    font-size: 0.9rem;
}

.topic-actions {
    display: flex;
    gap: 0.5rem;
}

.topic-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
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
<?php $__env->slot('title'); ?> Course Topics <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Course Topics - <?php echo e($course->courseName); ?></h4>
                    <p class="card-title-desc">View all topics across all chapters for this course</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="<?php echo e(route('anisenso-courses.contents', ['id' => $course->id])); ?>" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Contents
                    </a>
                    <a href="<?php echo e(route('anisenso-courses')); ?>" class="btn btn-outline-secondary">
                        <i class="bx bx-book me-1"></i> All Courses
                    </a>
                </div>
            </div>
            <div class="card-body">
                <?php if($topics->count() > 0): ?>
                    <div class="topics-container">
                        <?php $__currentLoopData = $chapters; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $chapter): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <?php
                                $chapterTopics = $topics->where('chapter.id', $chapter->id);
                            ?>
                            
                            <?php if($chapterTopics->count() > 0): ?>
                                <div class="chapter-section">
                                    <div class="chapter-header">
                                        <h5 class="chapter-title"><?php echo e($chapter->chapterTitle); ?></h5>
                                    </div>
                                    
                                    <?php $__currentLoopData = $chapterTopics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $topic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                        <div class="topic-row">
                                            <div class="topic-title">
                                                <?php echo e($topic->topicTitle); ?>

                                            </div>
                                            <div class="topic-description">
                                                <?php echo e(Str::limit($topic->topicDescription, 100)); ?>

                                            </div>
                                            <div class="topic-actions">
                                                <a href="<?php echo e(route('anisenso-courses-topics-edit', ['topid' => $topic->id])); ?>" class="btn btn-outline-primary btn-sm">
                                                    <i class="bx bx-edit"></i>
                                                </a>
                                                <a href="<?php echo e(route('anisenso-courses-topics-resources', ['topid' => $topic->id])); ?>" class="btn btn-outline-info btn-sm">
                                                    <i class="bx bx-file"></i>
                                                </a>
                                                <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteTopic(<?php echo e($topic->id); ?>)">
                                                    <i class="bx bx-trash"></i>
                                                </button>
                                            </div>
                                        </div>
                                    <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                                </div>
                            <?php endif; ?>
                        <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bx bx-list-ul"></i>
                        <h4>No Topics Found</h4>
                        <p>This course doesn't have any topics yet. Start by adding chapters and topics to your course.</p>
                    </div>
                <?php endif; ?>
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
    console.log('Course all topics page loaded for: <?php echo e($course->courseName); ?>');
});

function deleteTopic(topicId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#3085d6',
        cancelButtonColor: '#d33',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/anisenso-courses-topics/${topicId}`,
                method: 'DELETE',
                data: {
                    _token: '<?php echo e(csrf_token()); ?>'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire(
                            'Deleted!',
                            'Topic has been deleted.',
                            'success'
                        ).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire(
                            'Error!',
                            response.message || 'Failed to delete topic.',
                            'error'
                        );
                    }
                },
                error: function() {
                    Swal.fire(
                        'Error!',
                        'Failed to delete topic. Please try again.',
                        'error'
                    );
                }
            });
        }
    });
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/aniSensoAdmin/course-all-topics.blade.php ENDPATH**/ ?>