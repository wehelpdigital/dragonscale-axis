@extends('layouts.master')

@section('title') Edit Chapter - {{ $chapter->chapterTitle }} @endsection

@section('css')
<!-- Custom styles for file upload -->
<style>
.file-upload-wrapper {
    position: relative;
    display: inline-block;
    width: 100%;
}

.file-upload-wrapper input[type=file] {
    position: absolute;
    left: -9999px;
}

.file-upload-wrapper .file-upload-btn {
    display: inline-block;
    padding: 12px 20px;
    cursor: pointer;
    background: #f8f9fa;
    border: 2px dashed #dee2e6;
    border-radius: 5px;
    text-align: center;
    transition: all 0.3s ease;
    width: 100%;
}

.file-upload-wrapper .file-upload-btn:hover {
    background: #e9ecef;
    border-color: #adb5bd;
}

.file-upload-wrapper .file-upload-btn.dragover {
    background: #d4edda;
    border-color: #28a745;
}

.file-preview {
    margin-top: 10px;
    text-align: center;
}

.file-preview img {
    max-width: 150px;
    max-height: 150px;
    border-radius: 5px;
    border: 1px solid #dee2e6;
}

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
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('li_2') Courses @endslot
@slot('li_3') Course Contents @endslot
@slot('title') Edit Chapter @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Edit Chapter</h4>
                    <p class="card-title-desc">Update chapter information for "{{ $course->courseName }}"</p>
                </div>
                <a href="{{ route('anisenso-courses.contents', ['id' => $course->id]) }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Contents
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('anisenso-courses.chapters.update', $chapter->id) }}" method="POST" enctype="multipart/form-data" id="chapterForm">
                    @csrf
                    @method('PUT')

                    <!-- Chapter Title Section -->
                    <div class="form-section">
                        <h5><i class="bx bx-edit me-2"></i>Chapter Title</h5>
                        <div class="mb-3">
                            <label for="chapterTitle" class="form-label">Chapter Title <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('chapterTitle') is-invalid @enderror"
                                   id="chapterTitle" name="chapterTitle" value="{{ old('chapterTitle', $chapter->chapterTitle) }}"
                                   placeholder="Enter chapter title">
                            @error('chapterTitle')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Chapter Description Section -->
                    <div class="form-section">
                        <h5><i class="bx bx-text me-2"></i>Chapter Description</h5>
                        <div class="mb-3">
                            <label for="chapterDescription" class="form-label">Chapter Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('chapterDescription') is-invalid @enderror"
                                      id="chapterDescription" name="chapterDescription" rows="4"
                                      placeholder="Enter chapter description">{{ old('chapterDescription', $chapter->chapterDescription) }}</textarea>
                            @error('chapterDescription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Chapter Cover Photo Section -->
                    <div class="form-section">
                        <h5><i class="bx bx-image me-2"></i>Chapter Cover Photo</h5>
                        <div class="mb-3">
                            <label for="chapterCoverPhoto" class="form-label">Chapter Cover Photo</label>
                            <div class="file-upload-wrapper">
                                <input type="file" name="chapterCoverPhoto" id="chapterCoverPhotoInput" accept="image/*">
                                <div class="file-upload-btn" id="fileUploadBtn">
                                    <div class="mb-2">
                                        <i class="display-6 text-muted bx bx-cloud-upload"></i>
                                    </div>
                                    <h5>Click to upload new image</h5>
                                    <span class="text-muted">(JPG, PNG, GIF up to 5MB)</span>
                                </div>
                            </div>
                            <div class="file-preview" id="filePreview" style="display: none;">
                                <img id="imagePreview" src="" alt="Preview">
                                <div class="mt-2">
                                    <button type="button" class="btn btn-sm btn-danger" id="removeImage">
                                        <i class="bx bx-trash"></i> Remove Image
                                    </button>
                                </div>
                            </div>
                            @if($chapter->chapterCoverPhoto)
                                <div class="mt-3">
                                    <label class="form-label">Current Cover Photo:</label>
                                    <div class="text-center">
                                        <img src="{{ asset($chapter->chapterCoverPhoto) }}" alt="Current chapter cover" style="max-width: 150px; max-height: 150px; border-radius: 5px; border: 1px solid #dee2e6;">
                                    </div>
                                </div>
                            @endif
                            @error('chapterCoverPhoto')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-section">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('anisenso-courses.contents', ['id' => $course->id]) }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Update Chapter
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
    console.log('Edit chapter page loaded for: {{ $chapter->chapterTitle }}');

    // File upload handling
    $('#fileUploadBtn').on('click', function() {
        $('#chapterCoverPhotoInput').click();
    });

    $('#chapterCoverPhotoInput').on('change', function() {
        var file = this.files[0];
        if (file) {
            // Validate file type
            if (!file.type.startsWith('image/')) {
                alert('Please select an image file.');
                this.value = '';
                return;
            }

            // Validate file size (5MB)
            if (file.size > 5 * 1024 * 1024) {
                alert('File size must be less than 5MB.');
                this.value = '';
                return;
            }

            // Show preview
            var reader = new FileReader();
            reader.onload = function(e) {
                $('#imagePreview').attr('src', e.target.result);
                $('#filePreview').show();
                $('#fileUploadBtn').hide();
            };
            reader.readAsDataURL(file);
        }
    });

    $('#removeImage').on('click', function() {
        $('#chapterCoverPhotoInput').val('');
        $('#filePreview').hide();
        $('#fileUploadBtn').show();
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
    $('#chapterTitle').on('input blur', function() {
        validateField($(this), { required: true, maxLength: 255 });
    });

    $('#chapterDescription').on('input blur', function() {
        validateField($(this), { required: true, maxLength: 1000 });
    });

    // Form submission validation
    $('#chapterForm').on('submit', function(e) {
        var isValid = true;

        // Validate all fields
        isValid = validateField($('#chapterTitle'), { required: true, maxLength: 255 }) && isValid;
        isValid = validateField($('#chapterDescription'), { required: true, maxLength: 1000 }) && isValid;

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
