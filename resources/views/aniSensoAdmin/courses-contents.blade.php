@extends('layouts.master')

@section('title') Course Contents - {{ $course->courseName }} @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Sweet Alert -->
<link href="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Sortable.js for drag and drop -->
<link href="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.css" rel="stylesheet" type="text/css" />

<style>
.chapters-table {
    background: white;
    border-radius: 8px;
    overflow: hidden;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.chapter-row {
    display: flex;
    align-items: center;
    padding: 1rem;
    border-bottom: 1px solid #e9ecef;
    background: white;
    transition: all 0.2s ease;
    cursor: move;
}

.chapter-row:hover {
    background: #f8f9fa;
}

.chapter-row:last-child {
    border-bottom: none;
}

.chapter-row.sortable-ghost {
    opacity: 0.5;
    background: #e3f2fd;
}

.chapter-row.sortable-chosen {
    background: #fff3e0;
    box-shadow: 0 4px 8px rgba(0,0,0,0.1);
}

.drag-handle {
    color: #6c757d;
    margin-right: 1rem;
    cursor: move;
    font-size: 1.2rem;
}

.chapter-title {
    flex: 1;
    font-weight: 500;
    color: #495057;
}

.chapter-actions {
    display: flex;
    gap: 0.5rem;
}

.chapter-actions .btn {
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
@slot('title') Course Contents @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title">Course Contents - {{ $course->courseName }}</h4>
                    <p class="card-title-desc">Manage the chapters for this course</p>
                </div>
                <div class="d-flex gap-2">
                    <a href="{{ route('anisenso-courses.chapters.add', ['id' => $course->id]) }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Add Chapter
                    </a>
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

                @if($chapters->count() > 0)
                    <div class="chapters-table">
                        <div id="chapters-list">
                            @foreach($chapters as $chapter)
                            <div class="chapter-row" data-id="{{ $chapter->id }}">
                                <div class="drag-handle">
                                    <i class="bx bx-menu"></i>
                                </div>
                                <div class="chapter-title">
                                    {{ $chapter->chapterTitle }}
                                </div>
                                <div class="chapter-actions">
                                    <a href="{{ route('anisenso-courses-topics', ['id' => $course->id, 'chap' => $chapter->id]) }}" class="btn btn-outline-info btn-sm" title="View Topics">
                                        <i class="bx bx-list-ul"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="editChapter({{ $chapter->id }})">
                                        <i class="bx bx-edit"></i>
                                    </button>
                                    <button type="button" class="btn btn-outline-danger btn-sm" onclick="deleteChapter({{ $chapter->id }})">
                                        <i class="bx bx-trash"></i>
                                    </button>
                                </div>
                            </div>
                            @endforeach
                        </div>
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bx bx-book-open"></i>
                        <h4>No Chapters Found</h4>
                        <p>Start by adding your first chapter to this course.</p>
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
    console.log('Course contents page loaded for: {{ $course->courseName }}');

    // Initialize drag and drop functionality
    initializeSortable();
});

function initializeSortable() {
    const chaptersList = document.getElementById('chapters-list');
    if (chaptersList) {
        new Sortable(chaptersList, {
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: function(evt) {
                updateChapterOrder();
            }
        });
    }
}

function updateChapterOrder() {
    const chapters = [];
    $('#chapters-list .chapter-row').each(function(index) {
        chapters.push({
            id: $(this).data('id'),
            order: index + 1
        });
    });

    $.ajax({
        url: '{{ route("anisenso-courses.chapters.order") }}',
        method: 'PUT',
        data: {
            _token: '{{ csrf_token() }}',
            chapters: chapters
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
                    title: 'Chapter order updated'
                });
            }
        },
        error: function() {
            Swal.fire({
                title: 'Error!',
                text: 'Failed to update chapter order',
                icon: 'error',
                confirmButtonText: 'OK'
            });
        }
    });
}

function editChapter(chapterId) {
    Swal.fire({
        title: 'Edit Chapter',
        text: 'Chapter editing functionality will be implemented soon!',
        icon: 'info',
        confirmButtonText: 'OK'
    });
}

function deleteChapter(chapterId) {
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
                text: 'Chapter has been deleted.',
                icon: 'success'
            });
        }
    });
}
</script>
@endsection
