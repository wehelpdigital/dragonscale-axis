@extends('layouts.master')

@section('title') Course Topics - {{ $chapter->chapterTitle }} @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Sweet Alert -->
<link href="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Sortable.js for drag and drop -->
<link href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css" rel="stylesheet" type="text/css" />

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
    cursor: move;
}

.topic-row:hover {
    background: #f8f9fa;
}

.topic-row:last-child {
    border-bottom: none;
}

.topic-row.sortable-ghost {
    opacity: 0.5;
    background: #e3f2fd;
}

.topic-row.sortable-chosen {
    background: #fff3e0;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.drag-handle {
    color: #6c757d;
    margin-right: 1rem;
    cursor: move;
    font-size: 1.2rem;
}

.topic-title {
    flex: 1;
    font-weight: 500;
    color: #495057;
}

.topic-description {
    flex: 2;
    color: #6c757d;
    font-size: 0.9rem;
    margin-right: 1rem;
}

.topic-actions {
    display: flex;
    gap: 0.5rem;
}

.topic-actions .btn {
    padding: 0.25rem 0.5rem;
    font-size: 0.8rem;
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
@slot('li_4') {{ $chapter->chapterTitle }} @endslot
@slot('title') Course Topics @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Course Topics - {{ $chapter->chapterTitle }}</h4>
                    <p class="card-title-desc">Manage topics for this chapter</p>
                    <small class="text-muted">Course: {{ $course->courseName }}</small>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('anisenso-courses-topics-add', ['id' => $course->id, 'chap' => $chapter->id]) }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Add Topic
                    </a>
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

                @if($topics->count() > 0)
                    <div class="topics-table">
                        <div id="topics-list">
                            @foreach($topics as $topic)
                            <div class="topic-row" data-id="{{ $topic->id }}">
                                <div class="drag-handle">
                                    <i class="bx bx-menu"></i>
                                </div>
                                <div class="topic-title">
                                    {{ $topic->topicTitle }}
                                </div>
                                <div class="topic-description">
                                    {{ Str::limit($topic->topicDescription, 100) }}
                                </div>
                                <div class="topic-actions">
                                    <a href="{{ route('anisenso-courses-topics-resources', ['topid' => $topic->id]) }}" class="btn btn-outline-info btn-sm" title="Manage Resources">
                                        <i class="bx bx-folder"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="editTopic({{ $topic->id }})">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteTopic({{ $topic->id }})">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bx bx-list-ul"></i>
                        <h4>No Topics Found</h4>
                        <p>Start by adding your first topic to this chapter.</p>
                    </div>
                @endif
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
<!-- Sortable.js for drag and drop -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>

<script>
$(document).ready(function() {
    console.log('Course topics page loaded for chapter: {{ $chapter->chapterTitle }}');

    // Initialize drag and drop functionality
    initializeSortable();
});

function initializeSortable() {
    const topicsList = document.getElementById('topics-list');
    if (topicsList) {
        new Sortable(topicsList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                updateTopicOrder();
            }
        });
    }
}

function updateTopicOrder() {
    const topics = [];
    $('#topics-list .topic-row').each(function(index) {
        topics.push({
            id: $(this).data('id'),
            order: index + 1
        });
    });

    $.ajax({
        url: '{{ route("anisenso-topics.order") }}',
        method: 'PUT',
        data: {
            _token: '{{ csrf_token() }}',
            topics: topics
        },
        success: function(response) {
            if (response.success) {
                // Show a subtle notification
                const toast = Swal.mixin({
                    toast: true,
                    position: 'top-end',
                    showConfirmButton: false,
                    timer: 2000,
                    timerProgressBar: true
                });

                toast.fire({
                    icon: 'success',
                    title: 'Topic order updated'
                });
            }
        },
        error: function() {
            Swal.fire({
                title: 'Error!',
                text: 'Failed to update topic order',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}

function editTopic(topicId) {
    window.location.href = `{{ route('anisenso-courses-topics-edit') }}?topid=${topicId}`;
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
            $.ajax({
                url: `/anisenso-courses-topics/${topicId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: 'Topic has been deleted.',
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to delete topic.',
                        icon: 'error',
                        confirmButtonText: 'OK'
                    });
                }
            });
        }
    });
}
</script>
@endsection
