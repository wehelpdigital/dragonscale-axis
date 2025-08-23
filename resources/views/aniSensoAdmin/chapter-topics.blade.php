@extends('layouts.master')

@section('title') Chapter Topics - {{ $chapter->chapterTitle }} @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Sweet Alert -->
<link href="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.topics-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.topic-row {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    background: white;
    transition: all 0.2s ease;
}

.topic-row:hover {
    background: #f8f9fa;
}

.topic-row:last-child {
    border-bottom: none;
}

.topic-title {
    flex: 1;
    font-weight: 500;
    color: #495057;
}

.topic-actions {
    display: flex;
    gap: 0.5rem;
}

.topic-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
    margin-left: 0.25rem;
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
@slot('title') Chapter Topics @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Chapter Topics - {{ $chapter->chapterTitle }}</h4>
                    <p class="card-title-desc">Manage topics for this chapter</p>
                    <small class="text-muted">Course: {{ $course->courseName }}</small>
                </div>
                <div class="d-flex gap-2">
                    <button type="button" class="btn btn-primary" onclick="addTopic()">
                        <i class="bx bx-plus me-1"></i> Add Topic
                    </button>
                    <a href="{{ route('anisenso-courses.contents', ['id' => $course->id]) }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Chapters
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

                <div class="topics-table">
                    <div class="empty-state">
                        <i class="bx bx-list-ul"></i>
                        <h4>No Topics Found</h4>
                        <p>Start by adding your first topic to this chapter.</p>
                        <button type="button" class="btn btn-primary" onclick="addTopic()">
                            <i class="bx bx-plus me-1"></i> Add First Topic
                        </button>
                    </div>
                </div>
            </div>
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
    console.log('Chapter topics page loaded for: {{ $chapter->chapterTitle }}');
});

function addTopic() {
    Swal.fire({
        title: 'Add Topic',
        text: 'Topic management functionality will be implemented soon!',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

function editTopic(topicId) {
    Swal.fire({
        title: 'Edit Topic',
        text: 'Topic editing functionality will be implemented soon!',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

function deleteTopic(topicId) {
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
            // Implement delete functionality here
            Swal.fire({
                title: 'Deleted!',
                text: 'Topic has been deleted.',
                icon: 'success'
            });
        }
    });
}
</script>
@endsection
