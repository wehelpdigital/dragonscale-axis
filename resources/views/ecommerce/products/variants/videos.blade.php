@extends('layouts.master')

@section('title') Variant Videos @endsection

@section('script')
<!-- Lightbox JS -->
<script src="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/js/lightbox.min.js"></script>
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

    // Initialize lightbox for videos
    lightbox.option({
        'resizeDuration': 200,
        'wrapAround': true,
        'albumLabel': 'Video %1 of %2',
        'fadeDuration': 300,
        'imageFadeDuration': 300
    });

    // Handle delete video button clicks
    $(document).on('click', '.video-overlay', function(e) {
        e.preventDefault();
        e.stopPropagation();

        const videoItem = $(this).closest('.masonry-item');
        const videoId = videoItem.data('video-id');

        // Store video ID for confirmation
        $('#deleteVideoModal').data('video-id', videoId);
        $('#deleteVideoModal').modal('show');
    });

    // Handle confirm delete button
    $('#confirmDeleteVideoBtn').on('click', function() {
        const videoId = $('#deleteVideoModal').data('video-id');
        const videoItem = $(`.masonry-item[data-video-id="${videoId}"]`);

        // Disable button and show loading
        $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

        // Send AJAX request to delete video
        $.ajax({
            url: `/ecom-products-variants-videos/${videoId}`,
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    toastr.success(response.message);

                    // Remove video from masonry with fade effect
                    videoItem.fadeOut(300, function() {
                        $(this).remove();

                        // Check if no videos left
                        if ($('.masonry-item').length === 0) {
                            location.reload(); // Reload to show no videos message
                        }
                    });

                    // Close modal
                    $('#deleteVideoModal').modal('hide');
                } else {
                    toastr.error(response.message || 'Failed to delete video');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to delete video';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                // Re-enable button
                $('#confirmDeleteVideoBtn').prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete Video');
            }
        });
    });

    // Handle Add New Video button
    $('#addVideoBtn').on('click', function() {
        $('#addVideoModal').modal('show');
    });

    // Dynamic YouTube URL validation
    $('#videoLink').on('input', function() {
        const videoLink = $(this).val().trim();
        const saveBtn = $('#saveVideoBtn');
        const feedback = $(this).next('.invalid-feedback');

        // Clear previous validation states
        $(this).removeClass('is-valid is-invalid');
        feedback.hide();

        if (videoLink === '') {
            // Empty field - neutral state
            saveBtn.prop('disabled', true);
            return;
        }

        // YouTube URL validation regex
        const youtubeRegex = /^https:\/\/(www\.)?youtube\.com\/watch\?v=[a-zA-Z0-9_-]+(&.*)?$/;

        if (youtubeRegex.test(videoLink)) {
            // Valid YouTube URL
            $(this).addClass('is-valid');
            saveBtn.prop('disabled', false);
        } else {
            // Invalid URL
            $(this).addClass('is-invalid');
            feedback.show();
            saveBtn.prop('disabled', true);
        }
    });

    // Handle Save Video button
    $('#saveVideoBtn').on('click', function() {
        const form = $('#addVideoForm');
        const formData = new FormData(form[0]);
        const videoLink = $('#videoLink').val().trim();

        // Check if field is empty
        if (videoLink === '') {
            toastr.error('Please enter a YouTube video URL.');
            return;
        }

        // Validate YouTube URL
        const youtubeRegex = /^https:\/\/(www\.)?youtube\.com\/watch\?v=[a-zA-Z0-9_-]+(&.*)?$/;

        if (!youtubeRegex.test(videoLink)) {
            toastr.error('Please enter a valid YouTube video URL.');
            return;
        }

        // Disable button and show loading
        $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

        // Send AJAX request
        $.ajax({
            url: "{{ route('ecom-products.variants.videos.upload') }}",
            method: 'POST',
            data: formData,
            processData: false,
            contentType: false,
            headers: {
                'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    toastr.success(response.message);

                    // Add new video to masonry grid
                    addVideoToGrid(response.video);

                    // Close modal and reset form
                    $('#addVideoModal').modal('hide');
                    form[0].reset();
                } else {
                    toastr.error(response.message || 'Failed to save video');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to save video';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage);
            },
            complete: function() {
                // Re-enable button
                $('#saveVideoBtn').prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Video');
            }
        });
    });

    // Function to add new video to masonry grid
    function addVideoToGrid(videoData) {
        const videoGrid = $('#videoGrid');
        const noVideosCard = $('.no-videos').closest('.card');

        // If no videos message is showing, remove it
        if (noVideosCard.length > 0) {
            noVideosCard.remove();
        }

        // Create new video item
        const newVideoItem = $(`
            <div class="masonry-item" draggable="true" data-video-id="${videoData.id}" data-video-order="${videoData.videoOrder}">
                <div class="video-thumbnail" data-video-link="${videoData.videoLink}">
                    <img src="${videoData.thumbnailUrl}" alt="Video thumbnail" class="thumbnail-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                    <div class="thumbnail-placeholder" style="display: none;">
                        <i class="bx bx-play-circle"></i>
                        <span>Click to play</span>
                    </div>
                    <div class="play-overlay">
                        <i class="bx bx-play-circle"></i>
                    </div>
                </div>
                <div class="video-overlay" title="Delete Video">
                    <i class="bx bx-x"></i>
                </div>
            </div>
        `);

        // Add to grid with fade-in effect
        newVideoItem.hide().appendTo(videoGrid).fadeIn(300);

        // Delete button handler is already set up with $(document).on('click', '.video-overlay')

        // Make video clickable for lightbox
        newVideoItem.find('.video-thumbnail').on('click', function(e) {
            if (!$(e.target).closest('.video-overlay').length) {
                const videoLink = $(this).data('video-link');
                $('#videoLightboxIframe').attr('src', videoLink);
                $('#videoLightbox').addClass('active');
            }
        });
    }

    // Handle video thumbnail clicks for lightbox
    $(document).on('click', '.video-thumbnail', function(e) {
        if (!$(e.target).closest('.video-overlay').length) {
            const videoLink = $(this).data('video-link');
            $('#videoLightboxIframe').attr('src', videoLink);
            $('#videoLightbox').addClass('active');
        }
    });

    // Handle lightbox close
    $('#videoLightboxClose').on('click', function(e) {
        e.preventDefault();
        e.stopPropagation();
        $('#videoLightbox').removeClass('active');
        $('#videoLightboxIframe').attr('src', '');
    });

    // Handle lightbox background click
    $('#videoLightbox').on('click', function(e) {
        if (e.target === this) {
            $('#videoLightbox').removeClass('active');
            $('#videoLightboxIframe').attr('src', '');
        }
    });

    // Close lightbox with Escape key
    $(document).on('keydown', function(e) {
        if (e.key === 'Escape' && $('#videoLightbox').hasClass('active')) {
            $('#videoLightbox').removeClass('active');
            $('#videoLightboxIframe').attr('src', '');
        }
    });
});
</script>
@endsection

@section('css')
<!-- Lightbox CSS -->
<link href="https://cdnjs.cloudflare.com/ajax/libs/lightbox2/2.11.3/css/lightbox.min.css" rel="stylesheet">
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
    flex: 0 0 350px;
    margin-bottom: 20px;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    transition: transform 0.2s ease, box-shadow 0.2s ease;
    position: relative;
    cursor: move;
}

.masonry-item:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 16px rgba(0,0,0,0.15);
}

.video-thumbnail {
    position: relative;
    width: 100%;
    height: 200px;
    background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: all 0.3s ease;
    overflow: hidden;
}

.video-thumbnail:hover {
    transform: scale(1.02);
}

.thumbnail-image {
    width: 100%;
    height: 100%;
    object-fit: cover;
    transition: transform 0.3s ease;
}

.video-thumbnail:hover .thumbnail-image {
    transform: scale(1.05);
}

.play-overlay {
    position: absolute;
    top: 50%;
    left: 50%;
    transform: translate(-50%, -50%);
    background: rgba(0, 0, 0, 0.7);
    color: white;
    width: 60px;
    height: 60px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: all 0.3s ease;
    z-index: 2;
}

.play-overlay i {
    font-size: 24px;
    margin-left: 3px; /* Slight offset to center the play icon */
}

.video-thumbnail:hover .play-overlay {
    background: rgba(220, 53, 69, 0.9);
    transform: translate(-50%, -50%) scale(1.1);
}

.thumbnail-placeholder {
    text-align: center;
    color: white;
}

.thumbnail-placeholder i {
    font-size: 48px;
    margin-bottom: 8px;
    display: block;
}

.thumbnail-placeholder span {
    font-size: 14px;
    font-weight: 500;
}

.video-overlay {
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

.video-overlay:hover {
    background: rgba(220,53,69,0.9);
}



.no-videos {
    text-align: center;
    padding: 60px 20px;
    color: #6c757d;
}

.no-videos i {
    font-size: 4rem;
    margin-bottom: 20px;
    opacity: 0.5;
}

/* Video Lightbox */
.video-lightbox {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.9);
    display: none;
    align-items: center;
    justify-content: center;
    z-index: 9999;
}

.video-lightbox.active {
    display: flex;
}

.video-lightbox-content {
    position: relative;
    max-width: 90%;
    max-height: 90%;
    width: 800px;
    height: 450px;
}

.video-lightbox iframe {
    width: 100%;
    height: 100%;
    border: none;
    border-radius: 8px;
}

.video-lightbox-close {
    position: absolute;
    top: -40px;
    right: 0;
    color: white;
    font-size: 24px;
    cursor: pointer;
    background: none;
    border: none;
    padding: 8px;
}

.video-lightbox-close:hover {
    color: #ddd;
}

/* Form validation styles */
.form-control.is-valid {
    border-color: #198754;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 8 8'%3e%3cpath fill='%23198754' d='M2.3 6.73L.6 4.53c-.4-1.04.46-1.4 1.1-.8l1.1 1.4 3.4-3.8c.6-.63 1.6-.27 1.2.7l-4 4.6c-.43.5-.8.4-1.1.1z'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
}

.form-control.is-invalid {
    border-color: #dc3545;
    padding-right: calc(1.5em + 0.75rem);
    background-image: url("data:image/svg+xml,%3csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 12 12' width='12' height='12' fill='none' stroke='%23dc3545'%3e%3ccircle cx='6' cy='6' r='4.5'/%3e%3cpath d='m5.8 4.6 2.4 2.4m0-2.4L5.8 7'/%3e%3c/svg%3e");
    background-repeat: no-repeat;
    background-position: right calc(0.375em + 0.1875rem) center;
    background-size: calc(0.75em + 0.375rem) calc(0.75em + 0.375rem);
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
@slot('title') Variant Videos @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <div>
                        <h4 class="card-title">Variant Videos</h4>
                        <p class="card-title-desc">Manage videos for: <strong>{{ $variant->ecomVariantName }}</strong> ({{ $product->productName }})</p>
                    </div>
                    <a href="{{ route('ecom-products.variants', ['id' => $product->id]) }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back"></i> Back to Variants
                    </a>
                </div>

                <!-- Add New Video Button -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Variant Videos</h5>
                            <button type="button" class="btn btn-primary" id="addVideoBtn">
                                <i class="bx bx-plus"></i> Add New Video
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Videos Content -->
                <div class="row">
                    <div class="col-12">


                        @if($videos->count() > 0)
                            <div class="masonry-grid" id="videoGrid">
                                @foreach($videos as $video)
                                    <div class="masonry-item" draggable="true" data-video-id="{{ $video->id }}" data-video-order="{{ $video->videoOrder }}">
                                        <div class="video-thumbnail" data-video-link="{{ $video->videoLink }}">
                                            <img src="{{ $video->thumbnail_url }}" alt="Video thumbnail" class="thumbnail-image" onerror="this.style.display='none'; this.nextElementSibling.style.display='flex';">
                                            <div class="thumbnail-placeholder" style="display: none;">
                                                <i class="bx bx-play-circle"></i>
                                                <span>Click to play</span>
                                            </div>
                                            <div class="play-overlay">
                                                <i class="bx bx-play-circle"></i>
                                            </div>
                                        </div>
                                        <div class="video-overlay" title="Delete Video">
                                            <i class="bx bx-x"></i>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <div class="card border">
                                <div class="card-body no-videos">
                                    <i class="bx bx-video"></i>
                                    <h5>No Videos Found</h5>
                                    <p class="text-muted">No videos have been added for this variant yet.</p>
                                    <p class="text-muted">Click "Add New Video" to add the first video.</p>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- Video Lightbox -->
<div class="video-lightbox" id="videoLightbox">
    <div class="video-lightbox-content">
        <button type="button" class="video-lightbox-close" id="videoLightboxClose">
            <i class="bx bx-x"></i>
        </button>
        <iframe id="videoLightboxIframe" src="" frameborder="0" allowfullscreen></iframe>
    </div>
</div>

<!-- Delete Video Confirmation Modal -->
<div class="modal fade" id="deleteVideoModal" tabindex="-1" aria-labelledby="deleteVideoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteVideoModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this video?</p>
                <p class="text-muted mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteVideoBtn">
                    <i class="bx bx-trash me-1"></i>Delete Video
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Add Video Modal -->
<div class="modal fade" id="addVideoModal" tabindex="-1" aria-labelledby="addVideoModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addVideoModalLabel">Add New Video</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="addVideoForm">
                    @csrf
                    <input type="hidden" name="variantId" value="{{ $variant->id }}">

                                        <div class="mb-3">
                        <label for="videoLink" class="form-label">YouTube Video URL</label>
                        <input type="url" class="form-control" id="videoLink" name="videoLink"
                               placeholder="https://www.youtube.com/watch?v=VIDEO_ID" required>
                        <div class="form-text">Enter a valid YouTube video URL (e.g., https://www.youtube.com/watch?v=HeE-NS7o9aU&list=RDghhH6pAi2Gk&index=8)</div>
                        <div class="invalid-feedback">Please enter a valid YouTube video URL in the format: https://www.youtube.com/watch?v=VIDEO_ID</div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveVideoBtn" disabled>
                    <i class="bx bx-save me-1"></i>Save Video
                </button>
            </div>
        </div>
    </div>
</div>

@endsection
