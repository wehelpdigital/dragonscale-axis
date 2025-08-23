<?php $__env->startSection('title'); ?> Course Topics - <?php echo e($chapter->chapterTitle); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<!-- DataTables -->
<link href="<?php echo e(URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css')); ?>" rel="stylesheet" type="text/css" />
<!-- Sweet Alert -->
<link href="<?php echo e(URL::asset('/build/libs/sweetalert2/sweetalert2.min.css')); ?>" rel="stylesheet" type="text/css" />
<!-- Sortable.js for drag and drop -->
<link href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css" rel="stylesheet" type="text/css" />

<style>
.topics-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.topic-row {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    background: white;
    transition: all 0.2s ease;
    cursor: move;
}

.topic-row:hover {
    background: #f8f9fa;
}

.topic-row:last-child {
    border-bottom: none;
}

.topic-row.sortable-ghost {
    opacity: 0.5;
    background: #e3f2fd;
}

.topic-row.sortable-chosen {
    background: #fff3e0;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.drag-handle {
    color: #6c757d;
    margin-right: 1rem;
    cursor: move;
    font-size: 1.2rem;
}

.topic-title {
    flex: 1;
    font-weight: 500;
    color: #495057;
}

.topic-actions {
    display: flex;
    gap: 0.5rem;
}

.topic-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
}

.topic-actions .btn-outline-info {
    color: #17a2b8;
    border-color: #17a2b8;
}

.topic-actions .btn-outline-info:hover {
    background-color: #17a2b8;
    color: white;
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
<?php $__env->slot('title'); ?> Chapter Topics <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Course Topics - <?php echo e($chapter->chapterTitle); ?></h4>
                    <p class="card-title-desc">Manage topics for this chapter</p>
                    <small class="text-muted">Course: <?php echo e($course->courseName); ?></small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="addTopic()">
                        <i class="bx bx-plus me-1"></i> Add Topic
                    </button>
                    <a href="<?php echo e(route('anisenso-courses.contents', ['id' => $course->id])); ?>" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Chapters
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Success Message -->
                <?php if(session('success')): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php echo e(session('success')); ?>

                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                <?php endif; ?>

                <?php if($topics->count() > 0): ?>
                    <div class="topics-table">
                        <div id="topics-list">
                            <?php $__currentLoopData = $topics; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $topic): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                            <div class="topic-row" data-id="<?php echo e($topic->id); ?>">
                                <div class="drag-handle">
                                    <i class="bx bx-menu"></i>
                                </div>
                                <div class="topic-title">
                                    <?php echo e($topic->topicTitle); ?>

                                </div>
                                <div class="topic-actions">
                                    <button type="button" class="btn btn-outline-info btn-sm" onclick="downloadableResources(<?php echo e($topic->id); ?>)">
                                        <i class="bx bx-download"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="editTopic(<?php echo e($topic->id); ?>)">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteTopic(<?php echo e($topic->id); ?>)">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                            <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                        </div>
                    </div>
                <?php else: ?>
                    <div class="empty-state">
                        <i class="bx bx-list-ul"></i>
                        <h4>No Topics Found</h4>
                        <p>Start by adding your first topic to this chapter.</p>
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
<!-- Sortable.js for drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
$(document).ready(function() {
    console.log('Course topics page loaded');
    console.log('Course ID:', <?php echo e($course->id); ?>);
    console.log('Chapter ID:', <?php echo e($chapter->id); ?>);

    // Initialize drag and drop functionality
    initializeSortable();
});

function initializeSortable() {
    const topicsList = document.getElementById('topics-list');
    if (topicsList) {
        new Sortable(topicsList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                updateTopicOrder();
            }
        });
    }
}

function updateTopicOrder() {
    const topics = [];
    $('#topics-list .topic-row').each(function(index) {
        topics.push({
            id: $(this).data('id'),
            order: index + 1
        });
    });

    $.ajax({
        url: '<?php echo e(route("anisenso-topics.order")); ?>',
        method: 'PUT',
        data: {
            _token: '<?php echo e(csrf_token()); ?>',
            topics: topics
        },
        success: function(response) {
            if (response.success) {
                // Show a subtle notification
                const toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });

                toast.fire({
                    icon: 'success',
                    title: 'Topic order updated'
                });
            }
        },
        error: function() {
            Swal.fire({
                title: 'Error!',
                text: 'Failed to update topic order',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}

function addTopic() {
    // Redirect to add topic page
    window.location.href = `/anisenso-courses-topics-add?id=<?php echo e($course->id); ?>&chap=<?php echo e($chapter->id); ?>`;
}

function editTopic(topicId) {
    // Redirect to edit topic page
    window.location.href = `/anisenso-courses-topics-edit?topid=${topicId}`;
}

function downloadableResources(topicId) {
    // Redirect to downloadable resources page
    window.location.href = `/anisenso-courses-topics-resources?topid=${topicId}`;
}

function deleteTopic(topicId) {
    // Show delete confirmation modal
    $('#deleteTopicModal').modal('show');
    $('#confirmDeleteBtn').data('topic-id', topicId);
}

// Handle delete confirmation
$(document).on('click', '#confirmDeleteBtn', function() {
    const topicId = $(this).data('topic-id');
    const $btn = $(this);
    const $spinner = $btn.find('.spinner-border');
    const $btnText = $btn.contents().filter(function() { return this.nodeType === 3; }).first();

    // Show loading state
    $btn.prop('disabled', true);
    $spinner.show();
    $btnText.replaceWith(' Deleting...');

    // Perform delete request
    $.ajax({
        url: `/anisenso-courses-topics/${topicId}`,
        method: 'DELETE',
        data: {
            _token: '<?php echo e(csrf_token()); ?>'
        },
        success: function(response) {
            // Hide modal
            $('#deleteTopicModal').modal('hide');

            // Remove the topic row from the table
            $(`.topic-row[data-id="${topicId}"]`).fadeOut(300, function() {
                $(this).remove();

                // Check if no topics left
                if ($('#topics-list .topic-row').length === 0) {
                    $('#topics-list').html('<div class="text-center py-4"><p class="text-muted">No topics found</p></div>');
                }
            });

            // Show success message
            Swal.fire({
                title: 'Deleted!',
                text: 'Topic has been deleted successfully.',
                icon: 'success',
                confirmButtonText: 'OK'
            });
        },
        error: function(xhr) {
            // Reset button state
            $btn.prop('disabled', false);
            $spinner.hide();
            $btnText.replaceWith(' Delete Topic');

            // Show error message
            let errorMessage = 'Failed to delete topic.';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }

            Swal.fire({
                title: 'Error!',
                text: errorMessage,
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});
</script>

<!-- Delete Topic Confirmation Modal -->
<div class="modal fade" id="deleteTopicModal" tabindex="-1" aria-labelledby="deleteTopicModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTopicModalLabel">Delete Topic</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this topic? This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <span class="spinner-border spinner-border-sm me-2" role="status" aria-hidden="true" style="display: none;"></span>
                    Delete Topic
                </button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/aniSensoAdmin/course-topics.blade.php ENDPATH**/ ?>