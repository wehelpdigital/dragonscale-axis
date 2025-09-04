@extends('layouts.master')

@section('title') Course Access Tags - {{ $course->courseName }} @endsection

@section('css')
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<!-- Custom styles -->
<style>
.course-info {
    background: white;
    border-radius: 8px;
    padding: 1.5rem;
    margin-bottom: 1.5rem;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.course-info h5 {
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
@slot('title') Access Tags @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Course Access Tags</h4>
                    <p class="card-title-desc">Manage access tags for "{{ $course->courseName }}"</p>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="addNewAccessTag()">
                        <i class="bx bx-plus me-1"></i> Add New Access Tag
                    </button>
                    <a href="{{ route('anisenso-courses') }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Courses
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Success Message -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                <!-- Access Tags Section -->
                <div class="course-info">
                    <h5><i class="bx bx-tag me-2"></i>Access Tags</h5>

                    @if($tags->count() > 0)
                        <div class="table-responsive">
                            <table class="table table-bordered table-striped">
                                <thead class="table-light">
                                    <tr>
                                        <th>Tag Name</th>
                                        <th>Expiration Length</th>
                                        <th class="text-center">Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($tags as $tag)
                                        <tr data-tag-id="{{ $tag->id }}">
                                            <td>{{ $tag->tagName }}</td>
                                            <td>{{ $tag->expirationLength }}</td>
                                            <td class="text-center">
                                                <div class="d-flex gap-1 justify-content-center">
                                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="editTag({{ $tag->id }})">
                                                        <i class="bx bx-edit"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteTag({{ $tag->id }})">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @else
                        <div class="empty-state">
                            <i class="bx bx-tag"></i>
                            <h4>No Access Tags Found</h4>
                            <p>No access tags have been created for this course yet.</p>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteTagModal" tabindex="-1" aria-labelledby="deleteTagModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteTagModalLabel">
                    <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this access tag?</p>
                <p class="text-muted mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn" onclick="confirmDelete()">
                    <i class="bx bx-trash me-1"></i>Delete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Toastr JS -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
$(document).ready(function() {
    console.log('Course access tags page loaded for: {{ $course->courseName }}');

    // Configure Toastr
    toastr.options = {
        "closeButton": true,
        "debug": false,
        "newestOnTop": false,
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
});

function addNewAccessTag() {
    // Redirect to the add new access tag page
    window.location.href = `/anisenso-courses-tags-add?id={{ $course->id }}`;
}

function editTag(tagId) {
    // Redirect to the edit access tag page
    window.location.href = `/anisenso-courses-tags-edit?id=${tagId}`;
}

function deleteTag(tagId) {
    // Show confirmation modal
    $('#deleteTagModal').modal('show');
    $('#confirmDeleteBtn').data('tag-id', tagId);
}

function confirmDelete() {
    const tagId = $('#confirmDeleteBtn').data('tag-id');

    // Show loading state
    $('#confirmDeleteBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

    // Make AJAX request to delete the tag
    $.ajax({
        url: `/anisenso-courses-tags/${tagId}`,
        type: 'DELETE',
        data: {
            _token: $('meta[name="csrf-token"]').attr('content')
        },
        success: function(response) {
            if (response.success) {
                // Close modal
                $('#deleteTagModal').modal('hide');

                // Show success toastr
                toastr.success(response.message, 'Success!');

                // Remove the row from the table
                const rowToRemove = $(`tr[data-tag-id="${tagId}"]`);
                if (rowToRemove.length > 0) {
                    rowToRemove.fadeOut(400, function() {
                        $(this).remove();

                        // Check if table is empty and show empty state
                        if ($('tbody tr').length === 0) {
                            $('.table-responsive').fadeOut(400, function() {
                                $('.course-info').html(`
                                    <div class="empty-state">
                                        <i class="bx bx-tag"></i>
                                        <h4>No Access Tags Found</h4>
                                        <p>No access tags have been created for this course yet.</p>
                                    </div>
                                `);
                            });
                        }
                    });
                } else {
                    console.error('Row not found for tag ID:', tagId);
                }
            } else {
                toastr.error(response.message || 'Failed to delete access tag', 'Error!');
            }
        },
        error: function(xhr) {
            let errorMessage = 'Failed to delete access tag';
            if (xhr.responseJSON && xhr.responseJSON.message) {
                errorMessage = xhr.responseJSON.message;
            }
            toastr.error(errorMessage, 'Error!');
        },
        complete: function() {
            // Reset button state
            $('#confirmDeleteBtn').prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
        }
    });
}
</script>
@endsection
