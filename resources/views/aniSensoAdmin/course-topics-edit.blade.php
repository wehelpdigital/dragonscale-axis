@extends('layouts.master')

@section('title') Edit Topic - {{ $topic->topicTitle }} @endsection

@section('css')
<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/lbsbsr7t63wjii3wjqcftu0e9ot0c6e6f7mle8yqp6umxmpq/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<!-- Custom styles for file upload -->
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

.form-control.is-invalid {
    border-color: #dc3545;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25);
}

.invalid-feedback {
    display: block;
    width: 100%;
    margin-top: 0.25rem;
    font-size: 0.875em;
    color: #dc3545;
}

.tox-tinymce {
    border: 1px solid #ced4da !important;
    border-radius: 0.375rem !important;
}

.tox-tinymce.is-invalid {
    border-color: #dc3545 !important;
    box-shadow: 0 0 0 0.2rem rgba(220, 53, 69, 0.25) !important;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('li_2') Courses @endslot
@slot('li_3') {{ $course->courseName }} @endslot
@slot('li_4') {{ $chapter->chapterTitle }} @endslot
@slot('title') Edit Topic @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Edit Topic</h4>
                    <p class="card-title-desc">Update topic information</p>
                    <small class="text-muted">Course: {{ $course->courseName }} | Chapter: {{ $chapter->chapterTitle }}</small>
                </div>
                <a href="{{ route('anisenso-courses-topics', ['id' => $course->id, 'chap' => $chapter->id]) }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Topics
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('anisenso-courses-topics.update', $topic->id) }}" method="POST" id="topicForm">
                    @csrf
                    @method('PUT')
                    <input type="hidden" name="courseId" value="{{ $course->id }}">
                    <input type="hidden" name="chapterId" value="{{ $chapter->id }}">

                    <!-- Topic Basic Information -->
                    <div class="form-section">
                        <h5><i class="bx bx-info-circle me-2"></i>Basic Information</h5>
                        <div class="mb-3">
                            <label for="topicTitle" class="form-label">Topic Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('topicTitle') is-invalid @enderror"
                                   id="topicTitle" name="topicTitle"
                                   value="{{ old('topicTitle', $topic->topicTitle) }}"
                                   placeholder="Enter topic title">
                            @error('topicTitle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="topicDescription" class="form-label">Topic Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('topicDescription') is-invalid @enderror"
                                      id="topicDescription" name="topicDescription" rows="3"
                                      placeholder="Enter a brief description of the topic">{{ old('topicDescription', $topic->topicDescription) }}</textarea>
                            @error('topicDescription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Topic Content -->
                    <div class="form-section">
                        <h5><i class="bx bx-text me-2"></i>Topic Content <span class="text-danger">*</span></h5>
                        <div class="mb-3">
                            <label for="topicContent" class="form-label">Content</label>
                            <textarea class="form-control @error('topicContent') is-invalid @enderror"
                                      id="topicContent" name="topicContent" rows="10"
                                      placeholder="Enter the topic content">{{ old('topicContent', $topic->topicContent) }}</textarea>
                            @error('topicContent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">You can upload images and embed YouTube videos using the toolbar above.</small>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-section">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('anisenso-courses-topics', ['id' => $course->id, 'chap' => $chapter->id]) }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Update Topic
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<script>
$(document).ready(function() {
    console.log('Edit topic page loaded for: {{ $topic->topicTitle }}');

    // Initialize TinyMCE for topic content
    tinymce.init({
        selector: '#topicContent',
        height: 400,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | image media | removeformat | help',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',

        // Image upload configuration
        images_upload_handler: function (blobInfo, progress, failure) {
            return new Promise(function(resolve, reject) {
                // Check file size before upload (10MB limit)
                var maxSize = 10 * 1024 * 1024; // 10MB in bytes
                if (blobInfo.blob().size > maxSize) {
                    reject('Image size must be less than 10MB. Current size: ' + (blobInfo.blob().size / 1024 / 1024).toFixed(2) + 'MB');
                    return;
                }

                var xhr, formData;
                xhr = new XMLHttpRequest();
                xhr.withCredentials = false;
                xhr.open('POST', '/upload-image');
                xhr.setRequestHeader('X-CSRF-TOKEN', '{{ csrf_token() }}');
                xhr.setRequestHeader('Accept', 'application/json');

                xhr.onload = function() {
                    var json;
                    if (xhr.status != 200) {
                        console.error('Upload failed with status:', xhr.status);
                        console.error('Response:', xhr.responseText);

                        // Try to parse error response
                        try {
                            var errorJson = JSON.parse(xhr.responseText);
                            if (errorJson.error) {
                                reject(errorJson.error);
                                return;
                            }
                        } catch (e) {
                            // If not JSON, use generic error
                        }

                        reject('Upload failed with status: ' + xhr.status);
                        return;
                    }

                    // Check if response is HTML (likely a redirect to login)
                    if (xhr.responseText.trim().startsWith('<!DOCTYPE html>') || xhr.responseText.includes('<html')) {
                        reject('Server returned HTML instead of JSON. Please check authentication.');
                        return;
                    }

                    try {
                        json = JSON.parse(xhr.responseText);
                    } catch (e) {
                        console.error('JSON parse error:', e);
                        console.error('Response text:', xhr.responseText);
                        reject('Invalid JSON response from server');
                        return;
                    }

                    if (!json || typeof json.location != 'string') {
                        reject('Invalid response format from server');
                        return;
                    }
                    resolve(json.location);
                };

                xhr.onerror = function() {
                    reject('Image upload failed due to a network error');
                };

                formData = new FormData();
                formData.append('file', blobInfo.blob(), blobInfo.filename());
                xhr.send(formData);
            });
        },

        // YouTube embed configuration
        media_live_embeds: true,
        media_alt_source: false,
        media_poster: false,
        media_dimensions: false
    });

    // Dynamic validation function
    function validateField(field, rules) {
        var value = field.val().trim();
        var isValid = true;
        var errorMessage = '';

        // Remove existing error
        field.removeClass('is-invalid');
        field.siblings('.invalid-feedback').remove();

        // Required validation
        if (rules.required && !value) {
            isValid = false;
            errorMessage = 'This field is required.';
        }

        // Min length validation
        if (rules.minLength && value.length < rules.minLength) {
            isValid = false;
            errorMessage = 'Minimum ' + rules.minLength + ' characters required.';
        }

        // Max length validation
        if (rules.maxLength && value.length > rules.maxLength) {
            isValid = false;
            errorMessage = 'Maximum ' + rules.maxLength + ' characters allowed.';
        }

        // Show error if invalid
        if (!isValid) {
            field.addClass('is-invalid');
            field.after('<div class="invalid-feedback">' + errorMessage + '</div>');
        }

        return isValid;
    }

    // Real-time validation on input
    $('#topicTitle').on('input blur', function() {
        validateField($(this), { required: true, maxLength: 255 });
    });

    $('#topicDescription').on('input blur', function() {
        validateField($(this), { required: true, maxLength: 1000 });
    });

    // Form submission validation
    $('#topicForm').on('submit', function(e) {
        var isValid = true;

        // Validate all fields
        isValid = validateField($('#topicTitle'), { required: true, maxLength: 255 }) && isValid;
        isValid = validateField($('#topicDescription'), { required: true, maxLength: 1000 }) && isValid;

        // Validate TinyMCE content
        var content = tinymce.get('topicContent').getContent();
        if (!content || content.trim() === '') {
            $('#topicContent').addClass('is-invalid');
            if (!$('#topicContent').siblings('.invalid-feedback').length) {
                $('#topicContent').after('<div class="invalid-feedback">Topic content is required.</div>');
            }
            isValid = false;
        } else {
            $('#topicContent').removeClass('is-invalid');
            $('#topicContent').siblings('.invalid-feedback').remove();
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
        return true;
    });
});
</script>
@endsection
