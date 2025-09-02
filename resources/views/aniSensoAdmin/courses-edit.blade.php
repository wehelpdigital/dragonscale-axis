@extends('layouts.master')

@section('title') Edit Course @endsection

@section('css')
<!-- TinyMCE -->
<script src="https://cdn.tiny.cloud/1/lbsbsr7t63wjii3wjqcftu0e9ot0c6e6f7mle8yqp6umxmpq/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
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
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('li_2') Courses @endslot
@slot('title') Edit Course @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Edit Course</h4>
                    <p class="card-title-desc">Update course information</p>
                </div>
                <a href="{{ route('anisenso-courses') }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Courses
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('anisenso-courses.update', $course->id) }}" method="POST" enctype="multipart/form-data" id="courseForm">
                    @csrf
                    @method('PUT')

                    <!-- Course Basic Information -->
                    <div class="form-section">
                        <h5><i class="bx bx-info-circle me-2"></i>Basic Information</h5>
                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="courseName" class="form-label">Course Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('courseName') is-invalid @enderror"
                                           id="courseName" name="courseName" value="{{ old('courseName', $course->courseName) }}"
                                           placeholder="Enter course name">
                                    @error('courseName')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="coursePrice" class="form-label">Course Price <span class="text-danger">*</span></label>
                                    <div class="input-group">
                                        <span class="input-group-text">₱</span>
                                        <input type="number" class="form-control @error('coursePrice') is-invalid @enderror"
                                               id="coursePrice" name="coursePrice" value="{{ old('coursePrice', $course->coursePrice) }}"
                                               step="0.01" min="0" placeholder="0.00">
                                    </div>
                                    @error('coursePrice')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Course Description -->
                    <div class="form-section">
                        <h5><i class="bx bx-text me-2"></i>Course Description</h5>
                        <div class="mb-3">
                            <label for="courseSmallDescription" class="form-label">Short Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('courseSmallDescription') is-invalid @enderror"
                                      id="courseSmallDescription" name="courseSmallDescription" rows="3"
                                      placeholder="Enter a brief description of the course">{{ old('courseSmallDescription', $course->courseSmallDescription) }}</textarea>
                            @error('courseSmallDescription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="courseBigDescription" class="form-label">Detailed Description <span class="text-danger">*</span></label>
                            <textarea class="form-control @error('courseBigDescription') is-invalid @enderror"
                                      id="courseBigDescription" name="courseBigDescription" rows="6"
                                      placeholder="Enter detailed description of the course">{{ old('courseBigDescription', $course->courseBigDescription) }}</textarea>
                            @error('courseBigDescription')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Course Image -->
                    <div class="form-section">
                        <h5><i class="bx bx-image me-2"></i>Course Image</h5>
                        <div class="mb-3">
                            <label for="courseImage" class="form-label">Course Cover Image</label>
                            <div class="file-upload-wrapper">
                                <input type="file" name="courseImage" id="courseImageInput" accept="image/*">
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
                            @if($course->courseImage)
                                <div class="mt-3">
                                    <label class="form-label">Current Image:</label>
                                    <div class="text-center">
                                        <img src="{{ asset($course->courseImage) }}" alt="Current course image" style="max-width: 150px; max-height: 150px; border-radius: 5px; border: 1px solid #dee2e6;">
                                    </div>
                                </div>
                            @endif
                            @error('courseImage')
                                <div class="text-danger mt-1">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="form-section">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('anisenso-courses') }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Update Course
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
    console.log('Edit course page loaded');

    // Initialize TinyMCE for detailed description
    tinymce.init({
        selector: '#courseBigDescription',
        height: 300,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }'
    });

    // File upload handling
    $('#fileUploadBtn').on('click', function() {
        $('#courseImageInput').click();
    });

    $('#courseImageInput').on('change', function() {
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
        $('#courseImageInput').val('');
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
    $('#courseName').on('input blur', function() {
        validateField($(this), { required: true, maxLength: 255 });
    });

    $('#coursePrice').on('input blur', function() {
        validateField($(this), { required: true });
    });

    $('#courseSmallDescription').on('input blur', function() {
        validateField($(this), { required: true, maxLength: 500 });
    });

    // Form submission validation
    $('#courseForm').on('submit', function(e) {
        var isValid = true;

        // Validate all fields
        isValid = validateField($('#courseName'), { required: true, maxLength: 255 }) && isValid;
        isValid = validateField($('#coursePrice'), { required: true }) && isValid;
        isValid = validateField($('#courseSmallDescription'), { required: true, maxLength: 500 }) && isValid;

        // Validate TinyMCE content
        var bigDescription = tinymce.get('courseBigDescription').getContent();
        if (!bigDescription || bigDescription.trim() === '') {
            $('#courseBigDescription').addClass('is-invalid');
            if (!$('#courseBigDescription').siblings('.invalid-feedback').length) {
                $('#courseBigDescription').after('<div class="invalid-feedback">Detailed description is required.</div>');
            }
            isValid = false;
        } else {
            $('#courseBigDescription').removeClass('is-invalid');
            $('#courseBigDescription').siblings('.invalid-feedback').remove();
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

