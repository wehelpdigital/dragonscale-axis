<?php $__env->startSection('title'); ?> Downloadable Resources - <?php echo e($topic->topicTitle); ?> <?php $__env->stopSection(); ?>

<?php $__env->startSection('css'); ?>
<style>
.resources-section {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.resources-section h5 {
    color: #495057;
    margin-bottom: 1rem;
    padding-bottom: 0.5rem;
    border-bottom: 2px solid #e9ecef;
}

/* Dropzone Styles */
.dropzone-container {
    padding: 0.5rem;
}

.dropzone {
    border: 2px dashed #dee2e6;
    border-radius: 12px;
    background: #f8f9fa;
    min-height: 120px;
    max-height: 300px;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    cursor: pointer;
    position: relative;
    overflow: hidden;
}

.dropzone:hover {
    border-color: #007bff;
    background: #e3f2fd;
    transform: translateY(-2px);
    box-shadow: 0 4px 12px rgba(0, 123, 255, 0.15);
}

.dropzone.dz-drag-hover {
    border-color: #28a745;
    background: #d4edda;
    transform: scale(1.02);
    box-shadow: 0 6px 20px rgba(40, 167, 69, 0.2);
}

.dz-message {
    text-align: center;
    color: #6c757d;
    padding: 1rem;
}

.dz-message i {
    font-size: 2.5rem;
    margin-bottom: 0.5rem;
    opacity: 0.7;
}

.dz-message h5 {
    font-size: 1.1rem;
    margin-bottom: 0.5rem;
    color: #495057;
}

.dz-message p {
    font-size: 0.9rem;
    margin-bottom: 0.25rem;
}

.dz-message small {
    font-size: 0.8rem;
    opacity: 0.8;
}

/* File Preview Styles */
.dz-preview {
    margin: 8px 0;
    padding: 12px;
    border-radius: 8px;
    background: white;
    border: 1px solid #e9ecef;
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: all 0.3s ease;
    position: relative;
}

/* Custom Loading Preview */
.dz-loading {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    padding: 20px;
    text-align: center;
}

.dz-loading .spinner-border {
    width: 2rem;
    height: 2rem;
    margin-bottom: 8px;
}

.dz-loading div:last-child {
    color: #6c757d;
    font-size: 0.9rem;
    font-weight: 500;
}

/* Hide default preview elements */
.dz-preview .dz-image,
.dz-preview .dz-details,
.dz-preview .dz-filename,
.dz-preview .dz-size,
.dz-preview .dz-progress,
.dz-preview .dz-success-mark,
.dz-preview .dz-error-mark {
    display: none !important;
}

.dz-preview:hover {
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}

.dz-preview .dz-image {
    width: 60px;
    height: 60px;
    border-radius: 6px;
    object-fit: cover;
}

.dz-preview .dz-details {
    padding: 8px 12px;
    flex: 1;
}

.dz-preview .dz-filename {
    font-weight: 500;
    color: #495057;
    font-size: 0.9rem;
    margin-bottom: 2px;
    word-break: break-word;
}

.dz-preview .dz-size {
    color: #6c757d;
    font-size: 0.8rem;
}

/* Progress Bar */
.dz-preview .dz-progress {
    height: 6px;
    background: #e9ecef;
    border-radius: 3px;
    overflow: hidden;
    margin-top: 8px;
    position: relative;
}

.dz-preview .dz-progress .dz-upload {
    background: linear-gradient(90deg, #007bff, #0056b3);
    height: 100%;
    transition: width 0.3s ease;
    border-radius: 3px;
}

/* Success/Error Marks */
.dz-preview .dz-success-mark,
.dz-preview .dz-error-mark {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 24px;
    height: 24px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 12px;
    animation: fadeInScale 0.3s ease;
}

.dz-preview .dz-success-mark {
    background: linear-gradient(135deg, #28a745, #20c997);
    box-shadow: 0 2px 8px rgba(40, 167, 69, 0.3);
}

.dz-preview .dz-error-mark {
    background: linear-gradient(135deg, #dc3545, #c82333);
    box-shadow: 0 2px 8px rgba(220, 53, 69, 0.3);
}

/* Animations */
@keyframes fadeInScale {
    from {
        opacity: 0;
        transform: scale(0.5);
    }
    to {
        opacity: 1;
        transform: scale(1);
    }
}

@keyframes slideInUp {
    from {
        opacity: 0;
        transform: translateY(20px);
    }
    to {
        opacity: 1;
        transform: translateY(0);
    }
}

.dz-preview {
    animation: slideInUp 0.3s ease;
}

/* Remove Button */
.dz-preview .dz-remove {
    color: #dc3545;
    font-size: 0.8rem;
    text-decoration: none;
    margin-top: 4px;
    display: inline-block;
    transition: color 0.2s ease;
}

.dz-preview .dz-remove:hover {
    color: #c82333;
    text-decoration: none;
}

/* Drag and Drop Styles */
.drag-handle {
    color: #6c757d;
    cursor: move;
    font-size: 1.2rem;
    text-align: center;
    padding: 4px;
    border-radius: 4px;
    transition: all 0.2s ease;
}

.drag-handle:hover {
    color: #495057;
    background: #f8f9fa;
}

.resource-row.sortable-ghost {
    opacity: 0.5;
    background: #e3f2fd;
}

.resource-row.sortable-chosen {
    background: #fff3e0;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

/* File link styles */
.resource-row a {
    color: #007bff;
    transition: color 0.2s ease;
}

.resource-row a:hover {
    color: #0056b3;
    text-decoration: underline !important;
}

/* File name column padding */
.resource-row td:nth-child(2) {
    padding-top: 20px;
}

/* Custom Toast Notification Styles */
.custom-toast {
    position: fixed;
    top: 20px;
    right: 20px;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
    padding: 16px 20px;
    min-width: 300px;
    max-width: 400px;
    z-index: 999999;
    transform: translateX(100%);
    transition: transform 0.3s ease;
    border-left: 4px solid #28a745;
    font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
}

.custom-toast.show {
    transform: translateX(0);
}

.custom-toast-error {
    border-left-color: #dc3545;
}

.custom-toast .toast-content {
    display: flex;
    align-items: center;
    gap: 12px;
    margin-bottom: 8px;
}

.custom-toast .toast-content i {
    font-size: 20px;
    color: #28a745;
}

.custom-toast-error .toast-content i {
    color: #dc3545;
}

.custom-toast .toast-content span {
    font-size: 14px;
    font-weight: 500;
    color: #333;
    flex: 1;
}

.custom-toast .toast-progress {
    height: 3px;
    background: #e9ecef;
    border-radius: 2px;
    overflow: hidden;
    position: relative;
}

.custom-toast .toast-progress::after {
    content: '';
    position: absolute;
    top: 0;
    left: 0;
    height: 100%;
    width: 100%;
    background: #28a745;
    animation: progress 3s linear;
}

.custom-toast-error .toast-progress::after {
    background: #dc3545;
}

@keyframes progress {
    from {
        width: 100%;
    }
    to {
        width: 0%;
    }
}
</style>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('content'); ?>
<?php $__env->startComponent('components.breadcrumb'); ?>
<?php $__env->slot('li_1'); ?> Ani-Senso <?php $__env->endSlot(); ?>
<?php $__env->slot('li_2'); ?> Courses <?php $__env->endSlot(); ?>
<?php $__env->slot('li_3'); ?> <?php echo e($course->courseName); ?> <?php $__env->endSlot(); ?>
<?php $__env->slot('li_4'); ?> <?php echo e($chapter->chapterTitle); ?> <?php $__env->endSlot(); ?>
<?php $__env->slot('title'); ?> Downloadable Resources <?php $__env->endSlot(); ?>
<?php echo $__env->renderComponent(); ?>

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Downloadable Resources</h4>
                    <p class="card-title-desc">Manage downloadable resources for "<?php echo e($topic->topicTitle); ?>"</p>
                    <small class="text-muted">Course: <?php echo e($course->courseName); ?> | Chapter: <?php echo e($chapter->chapterTitle); ?></small>
                </div>
                <a href="<?php echo e(route('anisenso-courses-topics', ['id' => $course->id, 'chap' => $chapter->id])); ?>" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Topics
                </a>
            </div>
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h5 class="mb-0"><i class="bx bx-download me-2"></i>Downloadable Resources</h5>
                    <button type="button" class="btn btn-primary" onclick="addResource()">
                        <i class="bx bx-plus me-1"></i> Add Downloadable Resource
                    </button>
                </div>

                <?php if($resources->count() > 0): ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead class="table-light">
                                <tr>
                                    <th width="50"></th>
                                    <th>File Name</th>
                                    <th width="150">Actions</th>
                                </tr>
                            </thead>
                            <tbody id="resources-list">
                                <?php $__currentLoopData = $resources; $__env->addLoop($__currentLoopData); foreach($__currentLoopData as $resource): $__env->incrementLoopIndices(); $loop = $__env->getLastLoop(); ?>
                                <tr class="resource-row" data-id="<?php echo e($resource->id); ?>">
                                    <td>
                                        <div class="drag-handle">
                                            <i class="bx bx-menu"></i>
                                        </div>
                                    </td>
                                    <td>
                                        <a href="<?php echo e(asset($resource->fileUrl)); ?>" target="_blank" class="text-decoration-none">
                                            <strong><?php echo e($resource->fileName); ?></strong>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard('<?php echo e(asset($resource->fileUrl)); ?>')">
                                                <i class="bx bx-copy"></i>
                                            </button>
                                            <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteResource(<?php echo e($resource->id); ?>)">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; $__env->popLoop(); $loop = $__env->getLastLoop(); ?>
                            </tbody>
                        </table>
                    </div>
                <?php else: ?>
                    <div class="text-center py-5">
                        <i class="bx bx-download display-1 text-muted"></i>
                        <h4 class="mt-3">No Resources Found</h4>
                        <p class="text-muted">Start by adding your first downloadable resource.</p>
                        <button type="button" class="btn btn-primary" onclick="addResource()">
                            <i class="bx bx-plus me-1"></i> Add First Resource
                        </button>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Add Resource Modal -->
<div class="modal fade" id="addResourceModal" tabindex="-1" aria-labelledby="addResourceModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addResourceModalLabel">
                    <i class="bx bx-upload me-2"></i>Add Downloadable Resource
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="dropzone-container">
                    <div id="fileDropzone" class="dropzone">
                        <div class="dz-message">
                            <i class="bx bx-cloud-upload display-4 text-muted"></i>
                            <h5 class="mt-3">Drop files here or click to upload</h5>
                            <p class="text-muted">Upload PDF, DOC, DOCX, XLS, XLSX, PPT, PPTX, ZIP, RAR files</p>
                            <small class="text-muted">Maximum file size: 50MB</small>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
<?php $__env->stopSection(); ?>

<?php $__env->startSection('script'); ?>
<!-- Sweet Alert -->
<script src="<?php echo e(URL::asset('/build/libs/sweetalert2/sweetalert2.min.js')); ?>"></script>
<!-- Dropzone.js -->
<script src="https://unpkg.com/dropzone@6.0.0-beta.2/dist/dropzone-min.js"></script>
<link rel="stylesheet" href="https://unpkg.com/dropzone@6.0.0-beta.2/dist/dropzone.css" type="text/css" />

<!-- Sortable.js for drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
$(document).ready(function() {
    console.log('Topic resources page loaded for: <?php echo e($topic->topicTitle); ?>');

    // Initialize drag and drop functionality
    initializeSortable();
});

function initializeSortable() {
    const resourcesList = document.getElementById('resources-list');
    if (resourcesList) {
        new Sortable(resourcesList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            handle: '.drag-handle',
            onEnd: function(evt) {
                updateResourceOrder();
            }
        });
    }
}

function updateResourceOrder() {
    const resources = [];
    $('#resources-list .resource-row').each(function(index) {
        resources.push({
            id: $(this).data('id'),
            order: index + 1
        });
    });

    $.ajax({
        url: '<?php echo e(route("anisenso-courses-topics-resources.order")); ?>',
        method: 'PUT',
        data: {
            _token: '<?php echo e(csrf_token()); ?>',
            resources: resources
        },
        success: function(response) {
            if (response.success) {
                showToast('Resource order updated successfully!', 'success');
            }
        },
        error: function() {
            showToast('Failed to update resource order', 'error');
        }
    });
}

let dropzone;

function addResource() {
    // Show modal
    $('#addResourceModal').modal('show');

    // Initialize dropzone after modal is shown
    $('#addResourceModal').on('shown.bs.modal', function() {
        if (!dropzone) {
            initializeDropzone();
        }
    });
}

function initializeDropzone() {
    dropzone = new Dropzone("#fileDropzone", {
        url: "<?php echo e(route('anisenso-courses-topics-resources.upload')); ?>",
        paramName: "file",
        maxFilesize: 50, // MB
        acceptedFiles: ".pdf,.doc,.docx,.xls,.xlsx,.ppt,.pptx,.zip,.rar",
        addRemoveLinks: false,
        previewTemplate: `
            <div class="dz-preview dz-file-preview">
                <div class="dz-loading">
                    <div class="spinner-border text-primary" role="status">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <div class="mt-2">Uploading...</div>
                </div>
            </div>
        `,
        dictDefaultMessage: "Drop files here or click to upload",
        dictFileTooBig: "File is too big (%sMB). Max filesize: %sMB.",
        dictInvalidFileType: "You can't upload files of this type.",
        dictResponseError: "Server responded with %s code.",
        dictCancelUpload: "Cancel",
        dictUploadCanceled: "Upload canceled.",
        dictCancelUploadConfirmation: "Are you sure you want to cancel this upload?",
        dictRemoveFile: "Remove",
        dictMaxFilesExceeded: "You can not upload any more files.",
        params: function() {
            return {
                topicId: <?php echo e($topic->id); ?>,
                _token: '<?php echo e(csrf_token()); ?>'
            };
        },
        init: function() {
                        this.on("success", function(file, response) {
                console.log('Upload success response:', response);

                if (response.success) {
                    // Show success toast
                    showToast('File uploaded successfully!', 'success');

                    // Update the table
                    updateResourcesTable(response.resource);

                    // Remove the file from dropzone after a short delay
                    setTimeout(() => {
                        this.removeFile(file);
                    }, 1500);
                } else {
                    showToast(response.message || 'Upload failed!', 'error');
                }
            });

            this.on("error", function(file, errorMessage, xhr) {
                console.error('Upload error:', errorMessage);
                console.error('XHR response:', xhr);

                let message = 'Upload failed!';
                if (xhr && xhr.responseJSON && xhr.responseJSON.message) {
                    message = xhr.responseJSON.message;
                } else if (errorMessage) {
                    message = errorMessage;
                }

                showToast(message, 'error');
            });

            this.on("complete", function(file) {
                // Clear dropzone after successful upload
                if (file.status === "success") {
                    setTimeout(() => {
                        this.removeAllFiles();
                    }, 1000);
                }
            });
        }
    });
}

function updateResourcesTable(resource) {
    const tableBody = document.querySelector('#resources-list');
    if (!tableBody) {
        // If no table exists, reload the page to show the new table
        location.reload();
        return;
    }

    const newRow = `
        <tr class="resource-row" data-id="${resource.id}">
            <td>
                <div class="drag-handle">
                    <i class="bx bx-menu"></i>
                </div>
            </td>
            <td>
                <a href="${resource.fileUrl}" target="_blank" class="text-decoration-none">
                    <strong>${resource.fileName}</strong>
                </a>
            </td>
            <td>
                <div class="d-flex gap-1">
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="copyToClipboard('${resource.fileUrl}')">
                        <i class="bx bx-copy"></i>
                    </button>
                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteResource(${resource.id})">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </td>
        </tr>
    `;

    tableBody.insertAdjacentHTML('afterbegin', newRow);
}

function showToast(message, type = 'success') {
    console.log('Showing toast:', message, type);

    // Create custom toast notification
    const toast = document.createElement('div');
    toast.className = `custom-toast custom-toast-${type}`;
    toast.innerHTML = `
        <div class="toast-content">
            <i class="bx ${type === 'success' ? 'bx-check-circle' : 'bx-x-circle'}"></i>
            <span>${message}</span>
        </div>
        <div class="toast-progress"></div>
    `;

    // Add to page
    document.body.appendChild(toast);

    // Trigger animation
    setTimeout(() => {
        toast.classList.add('show');
    }, 100);

    // Auto remove after 3 seconds
    setTimeout(() => {
        toast.classList.remove('show');
        setTimeout(() => {
            if (toast.parentNode) {
                toast.parentNode.removeChild(toast);
            }
        }, 300);
    }, 3000);
}

function copyToClipboard(url) {
    // Use modern clipboard API if available
    if (navigator.clipboard && window.isSecureContext) {
        navigator.clipboard.writeText(url).then(function() {
            showToast('URL copied to clipboard!', 'success');
        }).catch(function() {
            // Fallback to old method
            fallbackCopyToClipboard(url);
        });
    } else {
        // Fallback for older browsers
        fallbackCopyToClipboard(url);
    }
}

function fallbackCopyToClipboard(url) {
    const textArea = document.createElement('textarea');
    textArea.value = url;
    textArea.style.position = 'fixed';
    textArea.style.left = '-999999px';
    textArea.style.top = '-999999px';
    document.body.appendChild(textArea);
    textArea.focus();
    textArea.select();

    try {
        document.execCommand('copy');
        showToast('URL copied to clipboard!', 'success');
    } catch (err) {
        showToast('Failed to copy URL', 'error');
    }

    document.body.removeChild(textArea);
}

function deleteResource(resourceId) {
    Swal.fire({
        title: 'Delete Resource',
        text: 'Delete resource functionality will be implemented soon!',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}
</script>
<?php $__env->stopSection(); ?>

<?php echo $__env->make('layouts.master', array_diff_key(get_defined_vars(), ['__data' => 1, '__path' => 1]))->render(); ?><?php /**PATH C:\xampp\htdocs\btc-check\resources\views/aniSensoAdmin/course-topics-resources.blade.php ENDPATH**/ ?>