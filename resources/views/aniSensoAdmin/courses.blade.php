@extends('layouts.master')

@section('title') Ani-Senso Courses @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Sweet Alert -->
<link href="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.course-card {
    transition: all 0.3s ease;
    border: none;
    background: #fff;
    border-radius: 12px;
    overflow: hidden;
}

.course-card:hover {
    transform: translateY(-8px);
    box-shadow: 0 20px 40px rgba(0,0,0,0.1);
}

.course-image {
    width: 170px !important;
    height: 170px !important;
    object-fit: cover;
    border: 8px solid #f8f9fa;
    border-radius: 50%;
}

.course-placeholder {
    width: 120px !important;
    height: 120px !important;
    border: 3px solid #f8f9fa;
    border-radius: 50%;
}

.course-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #2c3e50;
    line-height: 1.3;
}

.course-price {
    font-size: 1.5rem;
    font-weight: 700;
    color: #27ae60;
}

.price-currency {
    font-size: 1rem;
    margin-right: 2px;
}

.price-amount {
    font-size: 1.5rem;
}

.course-description {
    font-size: 0.9rem;
    line-height: 1.5;
    color: #6c757d;
}

.course-actions .btn {
    font-size: 0.8rem;
    font-weight: 500;
    padding: 0.5rem 0.75rem;
    border-radius: 8px;
    transition: all 0.2s ease;
    border: none;
    color: white;
}

.course-actions .btn:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
}

.course-actions .btn-outline-primary {
    background-color: #3498db;
    color: white;
}

.course-actions .btn-outline-primary:hover {
    background-color: #2980b9;
    color: white;
}

.course-actions .btn-outline-warning {
    background-color: #f39c12;
    color: white;
}

.course-actions .btn-outline-warning:hover {
    background-color: #e67e22;
    color: white;
}

.course-actions .btn-outline-danger {
    background-color: #e74c3c;
    color: white;
}

.course-actions .btn-outline-danger:hover {
    background-color: #c0392b;
    color: white;
}

.card-body {
    padding: 1.5rem !important;
    border: 1px solid #dedede;
    border-radius: 8px;
}

@media (max-width: 768px) {
    .course-image, .course-placeholder {
        width: 100px !important;
        height: 100px !important;
    }

    .course-title {
        font-size: 1.1rem;
    }

    .course-price {
        font-size: 1.25rem;
    }
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('title') Course Management @endslot
@endcomponent

<!-- Success Message -->
@if(session('success'))
<div class="alert alert-success alert-dismissible fade show" role="alert">
    {{ session('success') }}
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Ani-Senso Courses</h4>
                    <p class="card-title-desc">Manage your courses</p>
                </div>
                <a href="{{ route('anisenso-courses-add') }}" class="btn btn-primary">
                    <i class="bx bx-plus"></i> Add New Course
                </a>
            </div>
            <div class="card-body">
                @if($courses->count() > 0)
                    <div class="row">
                        @foreach($courses as $course)
                        <div class="col-xl-4 col-lg-6 col-md-6 mb-4">
                            <div class="card course-card h-100 shadow-sm border-0">
                                <div class="card-body p-4">
                                    <div class="text-center mb-4">
                                        @if($course->courseImage)
                                            <img src="{{ asset($course->courseImage) }}" alt="{{ $course->courseName }}" class="course-image shadow-sm">
                                        @else
                                            <div class="course-placeholder d-inline-flex align-items-center justify-content-center {{ $course->placeholder_color }} shadow-sm">
                                                <span class="text-white fw-bold display-6">{{ $course->first_letter }}</span>
                                            </div>
                                        @endif
                                    </div>

                                    <div class="text-center">
                                        <h4 class="course-title mb-2">{{ $course->courseName }}</h4>
                                        <div class="course-price mb-3">
                                            <span class="price-currency">₱</span>
                                            <span class="price-amount">{{ number_format($course->coursePrice, 2) }}</span>
                                        </div>

                                        @if($course->courseSmallDescription)
                                            <p class="course-description text-muted mb-4">{{ Str::limit($course->courseSmallDescription, 100) }}</p>
                                        @endif

                                        <div class="course-actions">
                                            <div class="row g-2">
                                                <div class="col-4">
                                                    <button type="button" class="btn btn-outline-primary btn-sm w-100" onclick="viewCourse({{ $course->id }})">
                                                        <i class="bx bx-book-open me-1"></i> Contents
                                                    </button>
                                                </div>
                                                <div class="col-4">
                                                    <button type="button" class="btn btn-outline-warning btn-sm w-100" onclick="editCourse({{ $course->id }})">
                                                        <i class="bx bx-edit me-1"></i> Edit
                                                    </button>
                                                </div>
                                                <div class="col-4">
                                                    <button type="button" class="btn btn-outline-danger btn-sm w-100" onclick="deleteCourse({{ $course->id }})">
                                                        <i class="bx bx-trash me-1"></i> Delete
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="text-center py-5">
                        <i class="bx bx-book-open display-1 text-muted"></i>
                        <h4 class="mt-3">No Courses Found</h4>
                        <p class="text-muted">Start by adding your first course.</p>
                        <a href="{{ route('anisenso-courses-add') }}" class="btn btn-primary">
                            <i class="bx bx-plus"></i> Add First Course
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Edit Course Modal -->
<div class="modal fade" id="editCourseModal" tabindex="-1" aria-labelledby="editCourseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="editCourseForm" method="POST" enctype="multipart/form-data">
                @csrf
                @method('PUT')
                <div class="modal-header">
                    <h5 class="modal-title" id="editCourseModalLabel">Edit Course</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editCourseName" class="form-label">Course Name</label>
                                <input type="text" class="form-control" id="editCourseName" name="courseName" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="mb-3">
                                <label for="editCoursePrice" class="form-label">Course Price</label>
                                <div class="input-group">
                                    <span class="input-group-text">₱</span>
                                    <input type="number" class="form-control" id="editCoursePrice" name="coursePrice" step="0.01" min="0" required>
                                </div>
                            </div>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label for="editCourseDescription" class="form-label">Course Description</label>
                        <textarea class="form-control" id="editCourseDescription" name="courseDescription" rows="3"></textarea>
                    </div>
                    <div class="mb-3">
                        <label for="editCourseImage" class="form-label">Course Image</label>
                        <input type="file" class="form-control" id="editCourseImage" name="courseImage" accept="image/*">
                        <small class="text-muted">Upload a new image to replace the current one (optional)</small>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Course</button>
                </div>
            </form>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Required datatable js -->
<script src="{{ URL::asset('/build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<!-- Sweet Alert -->
<script src="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
$(document).ready(function() {
    console.log('Ani-Senso Course page loaded');
});

function viewCourse(courseId) {
    // Redirect to the course contents page
    window.location.href = `/anisenso-courses-contents?id=${courseId}`;
}

function editCourse(courseId) {
    // Fetch course data and populate the edit modal
    $.ajax({
        url: `/anisenso-courses/${courseId}/edit`,
        method: 'GET',
        success: function(response) {
            $('#editCourseName').val(response.courseName);
            $('#editCoursePrice').val(response.coursePrice);
            $('#editCourseDescription').val(response.courseDescription);
            $('#editCourseForm').attr('action', `/anisenso-courses/${courseId}`);
            $('#editCourseModal').modal('show');
        },
        error: function() {
            Swal.fire({
                title: 'Error',
                text: 'Failed to load course data',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}

function deleteCourse(courseId) {
    Swal.fire({
        title: 'Are you sure?',
        text: "You won't be able to revert this!",
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#d33',
        cancelButtonColor: '#3085d6',
        confirmButtonText: 'Yes, delete it!'
    }).then((result) => {
        if (result.isConfirmed) {
            $.ajax({
                url: `/anisenso-courses/${courseId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function() {
                    Swal.fire(
                        'Deleted!',
                        'Course has been deleted.',
                        'success'
                    ).then(() => {
                        location.reload();
                    });
                },
                error: function() {
                    Swal.fire(
                        'Error!',
                        'Failed to delete course.',
                        'error'
                    );
                }
            });
        }
    });
}
</script>
@endsection
