@extends('layouts.master')

@section('title') Add New Access Tag - {{ $course->courseName }} @endsection

@section('css')
<!-- Custom styles -->
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
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('li_2') Courses @endslot
@slot('li_3') {{ $course->courseName }} @endslot
@slot('li_4') Access Tags @endslot
@slot('title') Add New Access Tag @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Add New Access Tag</h4>
                    <p class="card-title-desc">Create a new access tag for "{{ $course->courseName }}"</p>
                </div>
                <a href="{{ route('anisenso-courses-tags', ['id' => $course->id]) }}" class="btn btn-secondary">
                    <i class="bx bx-arrow-back me-1"></i> Back to Access Tags
                </a>
            </div>
            <div class="card-body">
                <form action="{{ route('anisenso-courses-tags.store') }}" method="POST" id="accessTagForm">
                    @csrf
                    <input type="hidden" name="courseId" value="{{ $course->id }}">

                    <!-- Tag Information -->
                    <div class="form-section">
                        <h5><i class="bx bx-tag me-2"></i>Access Tag Information</h5>

                        <div class="row">
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="tagName" class="form-label">Tag Name <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control @error('tagName') is-invalid @enderror"
                                           id="tagName" name="tagName" value="{{ old('tagName') }}"
                                           placeholder="Enter tag name">
                                    @error('tagName')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="mb-3">
                                    <label for="expirationLength" class="form-label">Expiration Length In Days <span class="text-danger">*</span></label>
                                    <input type="number" class="form-control @error('expirationLength') is-invalid @enderror"
                                           id="expirationLength" name="expirationLength" value="{{ old('expirationLength') }}"
                                           placeholder="Enter number of days">
                                    @error('expirationLength')
                                        <div class="invalid-feedback">{{ $message }}</div>
                                    @enderror
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Form Actions -->
                    <div class="form-section">
                        <div class="d-flex justify-content-end gap-2">
                            <a href="{{ route('anisenso-courses-tags', ['id' => $course->id]) }}" class="btn btn-secondary">
                                <i class="bx bx-x me-1"></i> Cancel
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save me-1"></i> Save Access Tag
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
    console.log('Add new access tag page loaded for course: {{ $course->courseName }}');
});

// Form validation
$('#accessTagForm').on('submit', function(e) {
    let isValid = true;

    // Clear any existing validation errors
    $('.is-invalid').removeClass('is-invalid');
    $('.invalid-feedback').remove();

    // Validate tag name
    if ($('#tagName').val().trim() === '') {
        $('#tagName').addClass('is-invalid');
        $('#tagName').after('<div class="invalid-feedback">Tag name is required.</div>');
        isValid = false;
    }

    // Validate expiration length
    if ($('#expirationLength').val().trim() === '' || parseInt($('#expirationLength').val()) < 1) {
        $('#expirationLength').addClass('is-invalid');
        $('#expirationLength').after('<div class="invalid-feedback">Expiration length must be at least 1 day.</div>');
        isValid = false;
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
    console.log('Form validation passed, submitting...');
    return true;
});

// Real-time validation on input
$('#tagName').on('input blur', function() {
    if ($(this).val().trim() === '') {
        $(this).addClass('is-invalid');
        if (!$(this).siblings('.invalid-feedback').length) {
            $(this).after('<div class="invalid-feedback">Tag name is required.</div>');
        }
    } else {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
    }
});

$('#expirationLength').on('input blur', function() {
    if ($(this).val().trim() === '' || parseInt($(this).val()) < 1) {
        $(this).addClass('is-invalid');
        if (!$(this).siblings('.invalid-feedback').length) {
            $(this).after('<div class="invalid-feedback">Expiration length must be at least 1 day.</div>');
        }
    } else {
        $(this).removeClass('is-invalid');
        $(this).siblings('.invalid-feedback').remove();
    }
});
</script>
@endsection
