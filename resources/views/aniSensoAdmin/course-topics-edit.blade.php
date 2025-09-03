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
                    <p class="card-title-desc">Update the topic information</p>
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
                                   id="topicTitle" name="topicTitle" value="{{ old('topicTitle', $topic->topicTitle) }}"
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
                            <small class="text-muted">You can upload images and embed YouTube videos using the toolbar above.</small>
                            @error('topicContent')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    <!-- Form Actions -->
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
    // Initialize TinyMCE
    tinymce.init({
        selector: '#topicContent',
        height: 400,
        plugins: [
            'advlist', 'autolink', 'lists', 'link', 'image', 'charmap', 'preview',
            'anchor', 'searchreplace', 'visualblocks', 'code', 'fullscreen',
            'insertdatetime', 'media', 'table', 'help', 'wordcount'
        ],
        toolbar: 'undo redo | formatselect | bold italic backcolor | alignleft aligncenter alignright alignjustify | bullist numlist outdent indent | removeformat | help',
        content_style: 'body { font-family: -apple-system, BlinkMacSystemFont, San Francisco, Segoe UI, Roboto, Helvetica Neue, sans-serif; font-size: 14px; }',
        setup: function(editor) {
            // Add custom validation
            editor.on('change', function() {
                if (editor.getContent().trim() === '') {
                    editor.getElement().classList.add('is-invalid');
                } else {
                    editor.getElement().classList.remove('is-invalid');
                }
            });
        }
    });

    // Form validation
    $('#topicForm').on('submit', function(e) {
        let isValid = true;

        // Validate title
        if ($('#topicTitle').val().trim() === '') {
            $('#topicTitle').addClass('is-invalid');
            isValid = false;
        } else {
            $('#topicTitle').removeClass('is-invalid');
        }

        // Validate description
        if ($('#topicDescription').val().trim() === '') {
            $('#topicDescription').addClass('is-invalid');
            isValid = false;
        } else {
            $('#topicDescription').removeClass('is-invalid');
        }

        // Validate content
        if (tinymce.get('topicContent').getContent().trim() === '') {
            tinymce.get('topicContent').getElement().classList.add('is-invalid');
            isValid = false;
        } else {
            tinymce.get('topicContent').getElement().classList.remove('is-invalid');
        }

        if (!isValid) {
            e.preventDefault();
            Swal.fire({
                title: 'Validation Error',
                text: 'Please fill in all required fields.',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
});
</script>
@endsection
