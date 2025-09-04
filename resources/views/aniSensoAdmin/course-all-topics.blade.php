@extends('layouts.master')

@section('title') Course Topics - {{ $course->courseName }} @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Sweet Alert -->
<link href="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.topics-container {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chapter-section {
    border-bottom: 2px solid #e9ecef;
    margin-bottom: 2rem;
}

.chapter-section:last-child {
    border-bottom: none;
}

.chapter-header {
    background: #f8f9fa;
    padding: 1rem;
    border-radius: 8px;
    margin-bottom: 1rem;
}

.chapter-title {
    font-size: 1.25rem;
    font-weight: 600;
    color: #495057;
    margin: 0;
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

.topic-description {
    flex: 2;
    color: #6c757d;
    font-size: 0.9rem;
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
@slot('title') Course Topics @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Course Topics - {{ $course->courseName }}</h4>
                    <p class="card-title-desc">View all topics across all chapters for this course</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('anisenso-courses.contents', ['id' => $course->id]) }}" class="btn btn-secondary">
                        <i class="bx bx-arrow-back me-1"></i> Back to Contents
                    </a>
                    <a href="{{ route('anisenso-courses') }}" class="btn btn-outline-secondary">
                        <i class="bx bx-book me-1"></i> All Courses
                    </a>
                </div>
            </div>
            <div class="card-body">
                @if($chapters->count() > 0)
                    <div class="topics-container">
                        @foreach($chapters as $chapter)
                            @if($chapter->topics->count() > 0)
                                <div class="chapter-section">
                                    <div class="chapter-header">
                                        <h5 class="chapter-title">{{ $chapter->chapterTitle }}</h5>
                                    </div>
                                    <div class="topics-list">
                                        @foreach($chapter->topics as $topic)
                                            <div class="topic-row">
                                                <div class="topic-title">
                                                    {{ $topic->topicTitle }}
                                                </div>
                                                <div class="topic-description">
                                                    {{ Str::limit($topic->topicDescription, 100) }}
                                                </div>
                                                <div class="topic-actions">
                                                    <a href="{{ route('anisenso-courses-topics-edit', ['topid' => $topic->id]) }}" class="btn btn-outline-primary btn-sm">
                                                        <i class="bx bx-edit"></i>
                                                    </a>
                                                    <a href="{{ route('anisenso-courses-topics-resources', ['topid' => $topic->id]) }}" class="btn btn-outline-info btn-sm">
                                                        <i class="bx bx-file"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteTopic({{ $topic->id }})">
                                                        <i class="bx bx-trash"></i>
                                                    </button>
                                                </div>
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bx bx-list-ul"></i>
                        <h4>No Topics Found</h4>
                        <p>This course doesn't have any topics yet. Start by adding chapters and topics to your course.</p>
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

<script>
$(document).ready(function() {
    console.log('Course all topics page loaded for: {{ $course->courseName }}');
});

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
                url: `{{ route('anisenso-courses-topics.destroy', '') }}/${topicId}`,
                method: 'DELETE',
                data: {
                    _token: '{{ csrf_token() }}'
                },
                success: function(response) {
                    if (response.success) {
                        Swal.fire({
                            title: 'Deleted!',
                            text: response.message,
                            icon: 'success'
                        }).then(() => {
                            location.reload();
                        });
                    } else {
                        Swal.fire({
                            title: 'Error!',
                            text: response.message,
                            icon: 'error'
                        });
                    }
                },
                error: function() {
                    Swal.fire({
                        title: 'Error!',
                        text: 'Failed to delete topic. Please try again.',
                        icon: 'error'
                    });
                }
            });
        }
    });
}
</script>
@endsection
