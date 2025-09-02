@extends('layouts.master')

@section('title') Variant Photos @endsection

@section('css')
<!-- Lightbox CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
<!-- Dropzone CSS -->
<link href="https://unpkg.com/dropzone@5/dist/min/dropzone.min.css" rel="stylesheet" type="text/css" />
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Masonry CSS -->
<style>
.masonry-grid {
    display: flex;
    flex-wrap: wrap;
    gap: 20px;
}

.masonry-item {
    flex: 0 0 250px;
    margin-bottom: 20px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
    cursor: move;
}

/* Drag and Drop Styles */
.masonry-item.dragging {
    opacity: 0.5;
    transform: rotate(5deg);
    z-index: 1000;
}

.masonry-item.drag-over {
    border: 2px dashed #007bff;
    background-color: rgba(0, 123, 255, 0.1);
}

.masonry-grid.dragging {
    cursor: grabbing;
}

.masonry-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.masonry-item img {
    width: 100%;
    height: auto;
    display: block;
    cursor: pointer;
}

.image-overlay {
    position: absolute;
    top: 10px;
    right: 10px;
    background: rgba(0,0,0,0.7);
    color: white;
    border-radius: 50%;
    width: 30px;
    height: 30px;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: background 0.2s ease;
}

.image-overlay:hover {
    background: rgba(220,53,69,0.9);
}

.no-images {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.no-images i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Dropzone Styling */
.dropzone {
    border: 2px dashed #0087F7;
    border-radius: 5px;
    background: white;
    min-height: 200px;
    padding: 20px;
    text-align: center;
    transition: all 0.3s ease;
}

.dropzone:hover {
    border-color: #0056b3;
    background: #f8f9fa;
}

.dropzone .dz-message {
    margin: 2em 0;
}

.dropzone .dz-message .icon {
    font-size: 3rem;
    color: #0087F7;
    margin-bottom: 15px;
}

.dropzone .dz-message h4 {
    color: #333;
    margin-bottom: 10px;
}

.dropzone .dz-message .note {
    color: #6c757d;
    font-size: 0.9rem;
}

.dropzone .dz-preview {
    margin: 10px;
}

.dropzone .dz-preview .dz-image {
    border-radius: 8px;
}

/* Toastr positioning */
#toast-container {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 9999 !important;
}

.toast-top-right {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 9999 !important;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') E-commerce @endslot
@slot('li_2') Products @endslot
@slot('li_3') Variants @endslot
@slot('title') Variant Photos @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Variant Photos</h4>
                        <p class="card-title-desc">Manage photos for: <strong>{{ $variant->ecomVariantName }}</strong> ({{ $product->productName }})</p>
                    </div>
                    <a href="{{ route('ecom-products.variants', ['id' => $product->id]) }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back to Variants
                    </a>
                </div>

                <!-- Add New Image Button -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Variant Images</h5>
                            <button type="button" class="btn btn-primary" id="addImageBtn">
                                <i class="bx bx-plus"></i> Add New Product Image
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Photos Content -->
                <div class="row">
                    <div class="col-12">
                                                @if($images->count() > 0)
                            <div class="masonry-grid" id="imageGrid">
                                @foreach($images as $image)
                                    <div class="masonry-item" draggable="true" data-image-id="{{ $image->id }}" data-image-order="{{ $image->imageOrder }}">
                                        <a href="{{ $image->imageLink }}" data-lightbox="variant-images" data-title="Variant Image">
                                            <img src="{{ $image->imageLink }}" alt="Variant Image" loading="lazy">
                                        </a>
                                        <div class="image-overlay" title="Delete Image">
                                            <i class="bx bx-x"></i>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="card border">
                                <div class="card-body no-images">
                                    <i class="bx bx-image"></i>
                                    <h5>No Images Found</h5>
                                    <p class="text-muted">No images have been uploaded for this variant yet.</p>
                                    <p class="text-muted">Click "Add New Product Image" to upload the first image.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Upload Image Modal -->
<div class="modal fade" id="uploadImageModal" tabindex="-1" aria-labelledby="uploadImageModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="uploadImageModalLabel">Upload New Product Image</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form action="{{ route('ecom-products.variants.photos.upload') }}" class="dropzone" id="imageDropzone">
                    @csrf
                    <input type="hidden" name="variantId" value="{{ $variant->id }}">
                    <div class="dz-message">
                        <div class="icon">
                            <i class="bx bx-cloud-upload"></i>
                        </div>
                        <h4>Drop files here or click to upload</h4>
                        <span class="note">(Maximum file size: 10MB. Supported formats: JPEG, PNG, JPG, GIF, WEBP)</span>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Image Confirmation Modal -->
<div class="modal fade" id="deleteImageModal" tabindex="-1" aria-labelledby="deleteImageModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteImageModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="text-center">
                    <i class="bx bx-trash text-danger" style="font-size: 3rem;"></i>
                    <h5 class="mt-3">Delete Image</h5>
                    <p class="text-muted">Are you sure you want to delete this image?</p>
                    <p class="text-muted"><small>This action cannot be undone.</small></p>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bx bx-trash me-1"></i>Delete Image
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Lightbox JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
<!-- Dropzone JS -->
<script src="https://unpkg.com/dropzone@5/dist/min/dropzone.min.js"></script>
<!-- Toastr JS -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Configure Toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "preventDuplicates": false,
        "onclick": null,
        "showDuration": "300",
        "hideDuration": "1000",
        "timeOut": "5000",
        "extendedTimeOut": "1000",
        "showEasing": "swing",
        "hideEasing": "linear",
        "showMethod": "fadeIn",
        "hideMethod": "fadeOut"
    };

    // Initialize lightbox
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'albumLabel': 'Image %1 of %2',
        'fadeDuration': 300,
        'imageFadeDuration': 300
    });

        // Initialize Dropzone
    Dropzone.autoDiscover = false;

    // Check if Dropzone is already attached to the element
    const dropzoneElement = document.getElementById("imageDropzone");
    if (dropzoneElement && dropzoneElement.dropzone) {
        dropzoneElement.dropzone.destroy();
    }

    const myDropzone = new Dropzone("#imageDropzone", {
        url: "{{ route('ecom-products.variants.photos.upload') }}",
        paramName: "image",
        maxFilesize: 10, // MB
        acceptedFiles: "image/jpeg,image/png,image/jpg,image/gif,image/webp",
        addRemoveLinks: true,
        dictDefaultMessage: "Drop files here or click to upload",
        dictFileTooBig: "File is too big. Maximum file size is 10MB.",
        dictInvalidFileType: "You can't upload files of this type.",
        dictResponseError: "Server responded with an error.",
        dictCancelUpload: "Cancel",
        dictUploadCanceled: "Upload canceled.",
        dictCancelUploadConfirmation: "Are you sure you want to cancel this upload?",
        dictRemoveFile: "Remove file",
        dictMaxFilesExceeded: "You can not upload any more files.",
        headers: {
            'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
        },
        init: function() {
                        this.on("success", function(file, response) {
                console.log('Upload response:', response);
                if (response.success) {
                    // Show success message
                    toastr.success(response.message);

                    // Add new image to masonry grid
                    addImageToGrid(response.image);

                    // Remove the file from dropzone
                    this.removeFile(file);

                    // Clear dropzone
                    this.removeAllFiles();
                } else {
                    toastr.error(response.message || 'Upload failed');
                }
            });

            this.on("error", function(file, errorMessage) {
                toastr.error('Upload failed: ' + errorMessage);
            });

            this.on("addedfile", function(file) {
                // Validate file type
                if (!file.type.match(/image.*/)) {
                    toastr.error('Please upload only image files');
                    this.removeFile(file);
                    return false;
                }
            });
        }
    });



        // Handle delete image button clicks
    $('.image-overlay').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const imageItem = $(this).closest('.masonry-item');
        const imageId = imageItem.data('image-id');

        // Store the image item for deletion
        window.imageToDelete = imageItem;

        // Show delete confirmation modal
        $('#deleteImageModal').modal('show');
    });

    // Handle delete confirmation
    $('#confirmDeleteBtn').on('click', function() {
        const imageItem = window.imageToDelete;
        const imageId = imageItem.data('image-id');

        // Disable button and show loading
        $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        // Send delete request
        $.ajax({
            url: `/ecom-products-variants-photos/${imageId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Remove from DOM with animation
                    imageItem.fadeOut(300, function() {
                        $(this).remove();

                        // Check if no images left
                        if ($('.masonry-item').length === 0) {
                            location.reload(); // Reload to show no images message
                        }
                    });

                    // Show success message
                    toastr.success(response.message);

                    // Close modal
                    $('#deleteImageModal').modal('hide');
                } else {
                    toastr.error(response.message || 'Failed to delete image');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to delete image';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                // Re-enable button
                $('#confirmDeleteBtn').prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete Image');
            }
        });
    });

    // Handle Add New Product Image button
    $('#addImageBtn').on('click', function() {
        $('#uploadImageModal').modal('show');
    });

    // Handle modal close - clear dropzone
    $('#uploadImageModal').on('hidden.bs.modal', function() {
        myDropzone.removeAllFiles();
    });

    // Drag and Drop functionality
    const imageGrid = document.getElementById('imageGrid');
    let draggedElement = null;
    let originalOrder = [];

    // Initialize drag and drop
    function initDragAndDrop() {
        const items = document.querySelectorAll('.masonry-item');

        items.forEach(item => {
            item.addEventListener('dragstart', handleDragStart);
            item.addEventListener('dragend', handleDragEnd);
            item.addEventListener('dragover', handleDragOver);
            item.addEventListener('dragenter', handleDragEnter);
            item.addEventListener('dragleave', handleDragLeave);
            item.addEventListener('drop', handleDrop);
        });
    }

    function handleDragStart(e) {
        draggedElement = this;
        this.classList.add('dragging');
        imageGrid.classList.add('dragging');

        // Store original order
        originalOrder = Array.from(document.querySelectorAll('.masonry-item')).map(item => ({
            id: item.dataset.imageId,
            order: parseInt(item.dataset.imageOrder)
        }));

        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/html', this.outerHTML);
    }

    function handleDragEnd(e) {
        this.classList.remove('dragging');
        imageGrid.classList.remove('dragging');
        draggedElement = null;
    }

    function handleDragOver(e) {
        e.preventDefault();
        e.dataTransfer.dropEffect = 'move';
    }

    function handleDragEnter(e) {
        e.preventDefault();
        this.classList.add('drag-over');
    }

    function handleDragLeave(e) {
        this.classList.remove('drag-over');
    }

    function handleDrop(e) {
        e.preventDefault();
        this.classList.remove('drag-over');

        if (draggedElement !== this) {
            const allItems = Array.from(document.querySelectorAll('.masonry-item'));
            const draggedIndex = allItems.indexOf(draggedElement);
            const droppedIndex = allItems.indexOf(this);

            // Reorder DOM elements
            if (draggedIndex < droppedIndex) {
                this.parentNode.insertBefore(draggedElement, this.nextSibling);
            } else {
                this.parentNode.insertBefore(draggedElement, this);
            }

            // Update order in database
            updateImageOrder();
        }
    }

    function updateImageOrder() {
        const items = Array.from(document.querySelectorAll('.masonry-item'));
        const newOrder = items.map((item, index) => ({
            id: parseInt(item.dataset.imageId),
            order: index + 1
        }));

        // Update data attributes
        items.forEach((item, index) => {
            item.dataset.imageOrder = index + 1;
        });

        // Send to server
        $.ajax({
            url: "{{ route('ecom-products.variants.photos.reorder') }}",
            method: 'PATCH',
            data: {
                variantId: {{ $variant->id }},
                imageOrders: newOrder,
                _token: $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    toastr.success(response.message);
                } else {
                    toastr.error(response.message || 'Failed to update image order');
                    // Revert to original order
                    revertToOriginalOrder();
                }
            },
            error: function() {
                toastr.error('Failed to update image order');
                // Revert to original order
                revertToOriginalOrder();
            }
        });
    }

    function revertToOriginalOrder() {
        const items = Array.from(document.querySelectorAll('.masonry-item'));
        const sortedItems = originalOrder.map(order =>
            items.find(item => parseInt(item.dataset.imageId) === order.id)
        );

        sortedItems.forEach(item => {
            if (item) {
                imageGrid.appendChild(item);
            }
        });
    }

    // Initialize drag and drop when page loads
    initDragAndDrop();

    // Re-initialize drag and drop after new images are added
    function addImageToGrid(imageData) {
        const masonryGrid = $('.masonry-grid');
        const noImagesCard = $('.no-images').closest('.card');

        // If no images message is showing, remove it
        if (noImagesCard.length > 0) {
            noImagesCard.remove();
        }

        // Create new image item
        const newImageItem = $(`
            <div class="masonry-item" draggable="true" data-image-id="${imageData.id}" data-image-order="${imageData.imageOrder}">
                <a href="${imageData.imageLink}" data-lightbox="variant-images" data-title="Variant Image">
                    <img src="${imageData.imageLink}" alt="Variant Image" loading="lazy">
                </a>
                <div class="image-overlay" title="Delete Image">
                    <i class="bx bx-x"></i>
                </div>
            </div>
        `);

        // Add to grid with fade-in effect
        newImageItem.hide().appendTo(masonryGrid).fadeIn(300);

        // Reinitialize lightbox for new image
        lightbox.init();

        // Re-initialize drag and drop for new item
        initDragAndDrop();

                // Add click handler for delete button
        newImageItem.find('.image-overlay').on('click', function(e) {
            e.preventDefault();
            e.stopPropagation();

            const imageItem = $(this).closest('.masonry-item');
            const imageId = imageItem.data('image-id');

            // Store the image item for deletion
            window.imageToDelete = imageItem;

            // Show delete confirmation modal
            $('#deleteImageModal').modal('show');
        });
    }
});
</script>
@endsection
