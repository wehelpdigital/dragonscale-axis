@extends('layouts.master')

@section('title') Ani-Senso Courses @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Sweet Alert -->
<link href="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
/* Ensure delete/status modal appears on top of comments modal */
#deleteCommentModal, #statusChangeModal {
    z-index: 1060 !important;
}
#deleteCommentModal + .modal-backdrop,
#statusChangeModal + .modal-backdrop {
    z-index: 1059 !important;
}

/* Mention tag styling */
.mention-tag {
    background-color: #e7f3ff;
    color: #0066cc;
    padding: 1px 4px;
    border-radius: 3px;
    font-weight: 500;
}

/* Fix badge font to prevent icon font inheritance */
.unanswered-badge {
    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif !important;
    background: #dc3545 !important;
    color: white !important;
    padding: 2px 6px !important;
    border-radius: 8px !important;
    font-size: 10px !important;
    font-weight: 600 !important;
    margin-left: 4px !important;
}

.badge-style {
    border-radius: 20px !important;
    padding: 4px 12px !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
    border-width: 1px !important;
    transition: all 0.2s ease !important;
    min-width: auto !important;
    line-height: 1.2 !important;
}

.badge-style:hover {
    transform: translateY(-1px) !important;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1) !important;
}

.badge-style:active {
    transform: translateY(0) !important;
}

/* Specific color enhancements for better badge appearance */
.btn-outline-primary.badge-style {
    color: #556ee6 !important;
    border-color: #556ee6 !important;
}

.btn-outline-primary.badge-style:hover {
    background-color: #556ee6 !important;
    color: white !important;
}

.btn-outline-info.badge-style {
    color: #50a5f1 !important;
    border-color: #50a5f1 !important;
}

.btn-outline-info.badge-style:hover {
    background-color: #50a5f1 !important;
    color: white !important;
}

.btn-outline-warning.badge-style {
    color: #f1b44c !important;
    border-color: #f1b44c !important;
}

.btn-outline-warning.badge-style:hover {
    background-color: #f1b44c !important;
    color: white !important;
}

.btn-outline-secondary.badge-style {
    color: #74788d !important;
    border-color: #74788d !important;
}

.btn-outline-secondary.badge-style:hover {
    background-color: #74788d !important;
    color: white !important;
}

.btn-outline-success.badge-style {
    color: #34c38f !important;
    border-color: #34c38f !important;
}

.btn-outline-success.badge-style:hover {
    background-color: #34c38f !important;
    color: white !important;
}

.btn-outline-dark.badge-style {
    color: #495057 !important;
    border-color: #495057 !important;
}

.btn-outline-dark.badge-style:hover {
    background-color: #495057 !important;
    color: white !important;
}

.btn-outline-danger.badge-style {
    color: #f46a6a !important;
    border-color: #f46a6a !important;
}

.btn-outline-danger.badge-style:hover {
    background-color: #f46a6a !important;
    color: white !important;
}

/* Course image in table */
.course-img {
    width: 60px;
    height: 60px;
    object-fit: cover;
    border-radius: 8px;
    border: 2px solid #f8f9fa;
}

.course-placeholder-sm {
    width: 60px;
    height: 60px;
    border-radius: 8px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.course-placeholder-sm span {
    font-size: 1.25rem;
    font-weight: bold;
    color: white;
}

/* Comments Button Badge */
.comments-btn .badge {
    position: relative;
    top: -1px;
    font-size: 9px;
    padding: 2px 5px;
    margin-left: 4px;
    background: #dc3545 !important;
    border-radius: 10px;
}

/* Comments Modal Styles */
.comments-modal .modal-dialog { max-width: 900px; }
.comments-modal .modal-body { max-height: 70vh; overflow-y: auto; padding: 0; }
.comment-filters { background: #f8f9fa; padding: 15px; border-bottom: 1px solid #e9ecef; }
.comment-list { padding: 15px; }
.comment-item { background: white; border: 1px solid #e9ecef; border-radius: 8px; padding: 15px; margin-bottom: 12px; transition: all 0.3s ease; }
.comment-item:hover { border-color: #dee2e6; box-shadow: 0 2px 8px rgba(0,0,0,0.05); }
.comment-item.unanswered { border-left: 3px solid #dc3545; }
.comment-item.answered { border-left: 3px solid #28a745; }
.comment-header { display: flex; align-items: center; margin-bottom: 10px; }
.comment-avatar { width: 40px; height: 40px; border-radius: 50%; margin-right: 12px; }
.comment-author { font-weight: 600; color: #495057; }
.comment-time { font-size: 12px; color: #6c757d; }
.comment-source { font-size: 11px; color: #556ee6; background: #f0f4ff; padding: 2px 8px; border-radius: 4px; margin-left: auto; }
.comment-text { color: #495057; line-height: 1.6; margin-bottom: 10px; }
.comment-text img.gif-image { max-width: 200px; border-radius: 6px; margin: 8px 0; }
.comment-actions { display: flex; gap: 8px; }
.comment-replies { margin-left: 40px; margin-top: 12px; padding-left: 15px; border-left: 2px solid #e9ecef; }
.reply-item { background: #f0f8ff; border-radius: 6px; padding: 12px; margin-bottom: 8px; border-left: 3px solid #a8d4ff; transition: all 0.3s ease; }
.reply-item.admin-reply { background: #e8f5e9; border-left: 3px solid #28a745; }
/* Nested reply (reply to a reply) - lighter purple color */
.comment-replies .comment-replies .reply-item { background: #faf5ff; border-left: 3px solid #d4b8ff; }
.comment-replies .comment-replies .reply-item.admin-reply { background: #f0fff4; border-left: 3px solid #68d391; }
/* Highlight animation for new comments */
.comment-item.new-highlight, .reply-item.new-highlight { background-color: #c8e6c9 !important; }
.comment-item.delete-highlight, .reply-item.delete-highlight { background-color: #ffcdd2 !important; }
.reply-item .reply-actions { display: flex; align-items: center; font-size: 12px; }
.reply-item .reply-actions .btn-link { font-size: 12px; text-decoration: none; }
.reply-item .reply-actions .btn-link:hover { text-decoration: underline; }
.reply-item .inline-reply-form { margin-top: 10px; }

/* Reaction Buttons */
.comment-reactions { display: flex; gap: 8px; margin-left: auto; }
.reaction-btn { background: #f8f9fa; border: 1px solid #dee2e6; border-radius: 15px; padding: 2px 10px; font-size: 12px; cursor: pointer; display: flex; align-items: center; gap: 4px; transition: all 0.2s; }
.reaction-btn:hover { background: #e9ecef; }
.reaction-btn.liked { background: #e3f2fd; border-color: #2196f3; color: #1976d2; }
.reaction-btn.hearted { background: #fce4ec; border-color: #e91e63; color: #c2185b; }
.reaction-btn.user-reacted { cursor: pointer; }
.reaction-btn.user-reacted::after { content: '✓'; font-size: 9px; margin-left: 2px; }
.reaction-count { font-weight: 600; }

.reply-form { margin-top: 12px; }
.reply-input-wrapper { position: relative; }
.reply-input { padding-right: 80px; }
.reply-toolbar { display: flex; gap: 4px; margin-top: 8px; }
.reply-toolbar button { background: #f8f9fa; border: 1px solid #dee2e6; padding: 4px 10px; cursor: pointer; border-radius: 4px; font-size: 12px; transition: all 0.2s; }
.reply-toolbar button:hover { background: #e9ecef; }
.reply-picker-wrapper { position: relative; display: inline-block; }

/* Emoji Picker */
.emoji-picker-container { position: absolute; bottom: 100%; left: 0; z-index: 1050; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); width: 320px; display: none; margin-bottom: 5px; }
.emoji-picker-container.show { display: block; }

/* Clickable source */
.comment-source { font-size: 11px; color: #556ee6; background: #f0f4ff; padding: 2px 8px; border-radius: 4px; margin-left: auto; cursor: pointer; transition: all 0.2s; }
.comment-source:hover { background: #dde5ff; text-decoration: underline; }

/* Inline reply form for comments */
.inline-reply-form { margin-top: 10px; padding: 10px; background: #f8f9fa; border-radius: 6px; display: none; }
.inline-reply-form.show { display: block; }
.inline-reply-form textarea { width: 100%; min-height: 60px; padding: 10px; border: 1px solid #dee2e6; border-radius: 6px; font-size: 13px; resize: vertical; }
.inline-reply-actions { display: flex; justify-content: space-between; align-items: center; margin-top: 8px; }
.inline-reply-tools { display: flex; gap: 6px; position: relative; }
.inline-reply-tools button { background: #fff; border: 1px solid #dee2e6; padding: 4px 8px; border-radius: 4px; font-size: 12px; cursor: pointer; }
.inline-reply-tools button:hover { background: #f0f0f0; }
.emoji-tabs { display: flex; border-bottom: 1px solid #e9ecef; padding: 8px; gap: 4px; }
.emoji-tab { padding: 6px 10px; border: none; background: none; cursor: pointer; border-radius: 4px; font-size: 16px; }
.emoji-tab:hover, .emoji-tab.active { background: #f0f0f0; }
.emoji-grid { display: grid; grid-template-columns: repeat(8, 1fr); gap: 4px; padding: 12px; max-height: 200px; overflow-y: auto; }
.emoji-item { font-size: 20px; padding: 6px; cursor: pointer; border-radius: 4px; text-align: center; transition: background 0.2s; }
.emoji-item:hover { background: #f0f0f0; }

/* GIF Picker */
.gif-picker-container { position: absolute; bottom: 100%; left: 0; z-index: 1050; background: white; border-radius: 8px; box-shadow: 0 4px 20px rgba(0,0,0,0.15); width: 350px; display: none; margin-bottom: 5px; }
.gif-picker-container.show { display: block; }
.gif-search { padding: 12px; border-bottom: 1px solid #e9ecef; }
.gif-search input { width: 100%; padding: 8px 12px; border: 1px solid #e0e0e0; border-radius: 20px; font-size: 13px; }
.gif-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 8px; padding: 12px; max-height: 250px; overflow-y: auto; }
.gif-item { cursor: pointer; border-radius: 6px; overflow: hidden; transition: transform 0.2s; }
.gif-item:hover { transform: scale(1.05); }
.gif-item img { width: 100%; display: block; }

/* Pagination */
.comments-pagination { padding: 15px; border-top: 1px solid #e9ecef; background: #f8f9fa; }

/* Loading Overlay Styles */
.loading-overlay {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background-color: rgba(255, 255, 255, 0.8);
    z-index: 1000;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 0.375rem;
}

.loading-spinner {
    text-align: center;
    background: white;
    padding: 2rem;
    border-radius: 0.5rem;
    box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
}

.loading-text {
    color: #6c757d;
    font-weight: 500;
    font-size: 0.9rem;
}

/* Disable interactions during loading */
.loading-overlay.active {
    pointer-events: all;
}

.loading-overlay.active ~ * {
    pointer-events: none;
    opacity: 0.6;
}

/* @Mention Autocomplete Styles */
.mention-autocomplete {
    position: absolute;
    z-index: 1070;
    background: white;
    border-radius: 8px;
    box-shadow: 0 4px 20px rgba(0,0,0,0.25);
    min-width: 200px;
    max-width: 280px;
    display: none;
    max-height: 200px;
    overflow-y: auto;
}
.mention-autocomplete.show {
    display: block;
}
.mention-autocomplete-header {
    padding: 8px 12px;
    font-size: 11px;
    color: #6c757d;
    border-bottom: 1px solid #e9ecef;
    background: #f8f9fa;
    font-weight: 600;
    text-transform: uppercase;
}
.mention-autocomplete-item {
    padding: 8px 12px;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: background 0.15s;
}
.mention-autocomplete-item:hover,
.mention-autocomplete-item.active {
    background: #f0f4ff;
}
.mention-autocomplete-item .avatar {
    width: 28px;
    height: 28px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    color: white;
    font-size: 12px;
}
.mention-autocomplete-item .avatar.admin {
    background: #28a745;
}
.mention-autocomplete-item .avatar.student {
    background: #556ee6;
}
.mention-autocomplete-item .name {
    font-weight: 500;
    color: #495057;
    font-size: 13px;
}
.mention-autocomplete-item .type {
    font-size: 10px;
    color: #6c757d;
    margin-left: auto;
}
.mention-autocomplete-empty {
    padding: 12px;
    text-align: center;
    color: #6c757d;
    font-size: 12px;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('title') Course Management @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title">Ani-Senso Courses</h4>
                    <a href="{{ route('anisenso-courses-add') }}" class="btn btn-primary">
                        <i class="bx bx-plus"></i> Add New Course
                    </a>
                </div>

                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bx bx-check-circle me-2"></i>
                        {{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>
                        {{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                @endif

                <!-- Filters -->
                <div class="row mb-3">
                    <div class="col-md-4">
                        <form method="GET" action="{{ route('anisenso-courses') }}" class="d-flex">
                            <input type="text" name="search" class="form-control me-2" placeholder="Search by course name..." value="{{ request('search') }}">
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="bx bx-search"></i>
                            </button>
                        </form>
                    </div>
                    <div class="col-md-3">
                        <form method="GET" action="{{ route('anisenso-courses') }}" class="d-flex">
                            @if(request('search'))
                                <input type="hidden" name="search" value="{{ request('search') }}">
                            @endif
                            <select name="status" class="form-select me-2" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="active" {{ request('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ request('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                        </form>
                    </div>
                    <div class="col-md-5 text-end">
                        @if(request('search') || request('status'))
                            <a href="{{ route('anisenso-courses') }}" class="btn btn-outline-danger">
                                <i class="bx bx-x"></i> Clear Filters
                            </a>
                        @endif
                    </div>
                </div>

                <!-- Courses Table -->
                <div class="table-responsive position-relative">
                    <!-- Loading Overlay -->
                    <div id="tableLoadingOverlay" class="loading-overlay" style="display: none;">
                        <div class="loading-spinner">
                            <div class="spinner-border text-primary" role="status">
                                <span class="visually-hidden">Loading...</span>
                            </div>
                            <div class="loading-text mt-2">Loading courses...</div>
                        </div>
                    </div>

                    <table class="table table-bordered table-striped">
                        <thead class="table-light">
                            <tr>
                                <th style="width: 80px;">Image</th>
                                <th>Course Name</th>
                                <th>Description</th>
                                <th class="text-center" style="width: 100px;">Status</th>
                                <th class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($courses as $course)
                                <tr>
                                    <td class="text-center align-middle">
                                        @if($course->courseImage)
                                            <img src="{{ asset($course->courseImage) }}" alt="{{ $course->courseName }}" class="course-img">
                                        @else
                                            <div class="course-placeholder-sm d-inline-flex {{ $course->placeholder_color }}">
                                                <span>{{ $course->first_letter }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td class="align-middle">
                                        <strong class="text-dark">{{ $course->courseName }}</strong>
                                    </td>
                                    <td class="align-middle">
                                        <span class="text-secondary">{{ $course->courseSmallDescription ? Str::limit($course->courseSmallDescription, 80) : 'No description' }}</span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <span class="badge {{ $course->isActive ? 'bg-success' : 'bg-secondary' }} status-badge" data-course-id="{{ $course->id }}">
                                            {{ $course->isActive ? 'Active' : 'Inactive' }}
                                        </span>
                                    </td>
                                    <td class="text-center align-middle">
                                        <div class="d-flex flex-wrap gap-1 justify-content-center">
                                            <a href="{{ route('anisenso-courses.contents', ['id' => $course->id]) }}"
                                               class="btn btn-sm btn-outline-primary badge-style"
                                               title="Contents">
                                                <i class="bx bx-book-open me-1"></i>Contents
                                            </a>

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-info badge-style comments-btn"
                                                    title="Comments"
                                                    data-course-id="{{ $course->id }}"
                                                    data-course-name="{{ $course->courseName }}">
                                                <i class="bx bx-message-square-dots me-1"></i>Comments<span class="badge unanswered-badge" data-course-id="{{ $course->id }}" style="display: none;"></span>
                                            </button>

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-secondary badge-style tags-btn"
                                                    title="Access Tags"
                                                    data-course-id="{{ $course->id }}">
                                                <i class="bx bx-tag me-1"></i>Tags
                                            </button>

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-success badge-style students-btn"
                                                    title="View Students"
                                                    data-course-id="{{ $course->id }}"
                                                    data-course-name="{{ $course->courseName }}">
                                                <i class="bx bx-group me-1"></i>Students
                                            </button>

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-warning badge-style reviews-btn"
                                                    title="View Reviews"
                                                    data-course-id="{{ $course->id }}"
                                                    data-course-name="{{ $course->courseName }}">
                                                <i class="bx bx-star me-1"></i>Reviews
                                            </button>

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-dark badge-style audit-btn"
                                                    title="Audit Trail"
                                                    data-course-id="{{ $course->id }}"
                                                    data-course-name="{{ $course->courseName }}">
                                                <i class="bx bx-history me-1"></i>Audit
                                            </button>

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-info badge-style settings-btn"
                                                    title="Course Settings"
                                                    data-course-id="{{ $course->id }}"
                                                    data-course-name="{{ $course->courseName }}">
                                                <i class="bx bx-cog me-1"></i>Settings
                                            </button>

                                            <a href="{{ route('anisenso-courses.certificate.designer', ['id' => $course->id]) }}"
                                               class="btn btn-sm btn-outline-primary badge-style"
                                               title="Certificate Designer">
                                                <i class="bx bx-award me-1"></i>Certificate
                                            </a>

                                            <button type="button"
                                                    class="btn btn-sm {{ $course->isActive ? 'btn-outline-warning' : 'btn-outline-success' }} badge-style status-btn"
                                                    title="Toggle Status"
                                                    data-course-id="{{ $course->id }}"
                                                    data-course-name="{{ $course->courseName }}"
                                                    data-is-active="{{ $course->isActive ? '1' : '0' }}">
                                                <i class="bx {{ $course->isActive ? 'bx-pause' : 'bx-play' }} me-1"></i>Status
                                            </button>

                                            <a href="{{ route('anisenso-courses-edit', ['id' => $course->id]) }}"
                                               class="btn btn-sm btn-outline-success badge-style"
                                               title="Edit">
                                                <i class="bx bx-edit me-1"></i>Edit
                                            </a>

                                            <button type="button"
                                                    class="btn btn-sm btn-outline-danger badge-style delete-btn"
                                                    title="Delete"
                                                    data-course-id="{{ $course->id }}"
                                                    data-course-name="{{ $course->courseName }}">
                                                <i class="bx bx-trash me-1"></i>Delete
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-5">
                                        <i class="bx bx-book-open display-4"></i>
                                        <p class="mt-2 text-dark">No courses found</p>
                                        <a href="{{ route('anisenso-courses-add') }}" class="btn btn-primary btn-sm">
                                            <i class="bx bx-plus"></i> Add First Course
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if(method_exists($courses, 'hasPages') && $courses->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3">
                        <div class="text-muted">
                            Showing {{ $courses->firstItem() }} to {{ $courses->lastItem() }} of {{ $courses->total() }} courses
                        </div>
                        <div>
                            {{ $courses->appends(request()->query())->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">
                    <i class="bx bx-trash text-danger me-2"></i>Confirm Delete
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this course?</p>
                <p class="text-muted mb-0"><strong>Course:</strong> <span id="deleteCourseName" class="text-dark"></span></p>
                <p class="text-secondary small mt-2">
                    This action will hide the course from the list but can be restored later if needed.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDelete">
                    <i class="bx bx-trash me-1"></i>Delete Course
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Students Modal -->
<div class="modal fade" id="studentsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bx bx-group me-2"></i>Students - <span id="studentsCourseName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="studentsCourseId">

                <!-- Filters Row 1 -->
                <div class="row mb-2">
                    <div class="col-md-4">
                        <input type="text" class="form-control" id="studentsSearch" placeholder="Search by name, email, phone...">
                    </div>
                    <div class="col-md-2">
                        <select class="form-select" id="studentsStatusFilter">
                            <option value="">All Students</option>
                            <option value="active">Active</option>
                            <option value="expired">Expired</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <button type="button" class="btn btn-success" id="addStudentBtn">
                            <i class="bx bx-plus me-1"></i>Enroll Student
                        </button>
                    </div>
                    <div class="col-md-2 text-end">
                        <span class="badge bg-secondary fs-6" id="studentCount">0 students</span>
                    </div>
                </div>
                <!-- Filters Row 2: Expiration Date Range -->
                <div class="row mb-3">
                    <div class="col-md-2">
                        <label class="form-label small text-secondary mb-1">Expiration From</label>
                        <input type="date" class="form-control form-control-sm" id="studentsExpirationFrom">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label small text-secondary mb-1">Expiration To</label>
                        <input type="date" class="form-control form-control-sm" id="studentsExpirationTo">
                    </div>
                    <div class="col-md-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearStudentsFilters">
                            <i class="bx bx-x me-1"></i>Clear Filters
                        </button>
                    </div>
                </div>

                <!-- Students List -->
                <div id="studentsList">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success"></div>
                        <p class="mt-2 text-secondary">Loading students...</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Edit Student Modal -->
<div class="modal fade" id="editStudentModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="bx bx-edit me-2"></i>Edit Student</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="editEnrollmentId">

                <div class="mb-3">
                    <label class="form-label text-dark fw-medium">Student</label>
                    <p id="editStudentName" class="form-control-plaintext text-dark fw-bold"></p>
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark fw-medium">Current Progress</label>
                    <div class="progress" style="height: 20px;">
                        <div class="progress-bar bg-success" id="editProgressBar" role="progressbar" style="width: 0%">0%</div>
                    </div>
                    <small class="text-secondary" id="editProgressDetail">0 of 0 contents completed</small>
                </div>

                <div class="mb-3">
                    <label for="editExpirationDate" class="form-label text-dark fw-medium">Expiration Date</label>
                    <input type="date" class="form-control" id="editExpirationDate">
                    <small class="text-secondary">Leave empty for lifetime access</small>
                </div>

                <div class="mb-3">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="editIsActive" checked>
                        <label class="form-check-label text-dark" for="editIsActive">Active</label>
                    </div>
                </div>

                <!-- Change Password Section -->
                <div class="border-top pt-3 mt-3">
                    <h6 class="text-dark mb-3"><i class="bx bx-lock-alt me-1"></i>Change Password</h6>
                    <input type="hidden" id="editAccessClientId">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <label for="editNewPassword" class="form-label small text-secondary">New Password</label>
                            <input type="password" class="form-control form-control-sm" id="editNewPassword" placeholder="Enter new password">
                        </div>
                        <div class="col-md-6 mb-2">
                            <label for="editConfirmPassword" class="form-label small text-secondary">Confirm Password</label>
                            <input type="password" class="form-control form-control-sm" id="editConfirmPassword" placeholder="Confirm password">
                        </div>
                    </div>
                    <div id="passwordError" class="text-danger small mb-2" style="display: none;"></div>
                    <button type="button" class="btn btn-outline-info btn-sm me-2" id="sendPasswordEmailBtn">
                        <i class="bx bx-envelope me-1"></i>Send Password Reset Email
                    </button>
                </div>

                <div class="border-top pt-3 mt-3">
                    <button type="button" class="btn btn-outline-warning btn-sm" id="resetProgressBtn">
                        <i class="bx bx-reset me-1"></i>Reset Progress
                    </button>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveStudentBtn">
                    <i class="bx bx-save me-1"></i>Save Changes
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Enroll Student Modal -->
<div class="modal fade" id="enrollStudentModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title"><i class="bx bx-user-plus me-2"></i>Enroll Student</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Search -->
                <div class="mb-3">
                    <div class="input-group">
                        <span class="input-group-text"><i class="bx bx-search"></i></span>
                        <input type="text" class="form-control" id="enrollSearchStudent" placeholder="Search by name, email or phone...">
                    </div>
                </div>

                <!-- Clients Table -->
                <div id="enrollSearchResults" class="mb-3" style="max-height: 300px; overflow-y: auto;">
                    <div class="text-center py-4">
                        <div class="spinner-border text-success"></div>
                        <p class="mt-2 text-secondary">Loading logins...</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div id="enrollPagination" class="d-flex justify-content-between align-items-center mb-3">
                </div>

                <input type="hidden" id="selectedClientId">
                <div id="selectedStudentInfo" class="alert alert-success d-none mb-3">
                    <i class="bx bx-check-circle me-2"></i><strong>Selected:</strong> <span id="selectedStudentName"></span>
                    <button type="button" class="btn-close float-end" id="clearSelectedStudent"></button>
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark fw-medium">Expiration</label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="enrollLifetimeAccess" checked>
                        <label class="form-check-label text-dark" for="enrollLifetimeAccess">
                            <i class="bx bx-infinite me-1"></i>Lifetime Access
                        </label>
                    </div>
                    <input type="date" class="form-control" id="enrollExpirationDate" disabled>
                    <small class="text-secondary" id="enrollExpirationHint">Lifetime access - no expiration</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-success" id="confirmEnrollBtn" disabled>
                    <i class="bx bx-user-plus me-1"></i>Enroll Student
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Remove Student Confirmation Modal -->
<div class="modal fade" id="removeStudentModal" tabindex="-1" aria-hidden="true" style="z-index: 1070;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title"><i class="bx bx-user-minus me-2"></i>Remove Student</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="removeEnrollmentId">
                <div class="text-center mb-3">
                    <i class="bx bx-error-circle text-danger" style="font-size: 4rem;"></i>
                </div>
                <p class="text-dark text-center mb-2">Are you sure you want to remove</p>
                <p class="text-center"><strong class="text-danger" id="removeStudentName"></strong></p>
                <p class="text-dark text-center mb-0">from this course?</p>
                <div class="alert alert-warning mt-3 mb-0">
                    <small><i class="bx bx-info-circle me-1"></i>The student can be re-enrolled in the future.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmRemoveStudentBtn">
                    <i class="bx bx-trash me-1"></i>Remove Student
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Reset Progress Confirmation Modal -->
<div class="modal fade" id="resetProgressModal" tabindex="-1" aria-hidden="true" style="z-index: 1080;">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark"><i class="bx bx-reset me-2"></i>Reset Progress</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="text-center mb-3">
                    <i class="bx bx-error text-warning" style="font-size: 4rem;"></i>
                </div>
                <p class="text-dark text-center mb-2">Are you sure you want to reset progress for</p>
                <p class="text-center"><strong class="text-warning" id="resetProgressStudentName"></strong>?</p>
                <div class="alert alert-danger mt-3 mb-0">
                    <small><i class="bx bx-error-circle me-1"></i><strong>Warning:</strong> This will clear all completed topics and cannot be undone.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-warning" id="confirmResetProgressBtn">
                    <i class="bx bx-reset me-1"></i>Reset Progress
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Audit Trail Modal -->
<div class="modal fade" id="auditModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title">
                    <i class="bx bx-history me-2"></i>Audit Trail - <span id="auditCourseName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="auditCourseId">

                <!-- Filters -->
                <div class="row mb-3 bg-light p-3 rounded">
                    <div class="col-md-2">
                        <label class="form-label text-dark fw-medium small">Date From</label>
                        <input type="date" class="form-control form-control-sm" id="auditDateFrom">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark fw-medium small">Date To</label>
                        <input type="date" class="form-control form-control-sm" id="auditDateTo">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark fw-medium small">Entity Type</label>
                        <select class="form-select form-select-sm" id="auditEntityType">
                            <option value="">All Types</option>
                            <option value="course">Course</option>
                            <option value="chapter">Chapter</option>
                            <option value="topic">Topic</option>
                            <option value="content">Content</option>
                            <option value="student">Student</option>
                            <option value="comment">Comment</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark fw-medium small">User</label>
                        <select class="form-select form-select-sm" id="auditUserFilter">
                            <option value="">All Users</option>
                        </select>
                    </div>
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="button" class="btn btn-primary btn-sm me-2" id="applyAuditFilters">
                            <i class="bx bx-filter me-1"></i>Filter
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearAuditFilters">
                            <i class="bx bx-reset me-1"></i>Clear
                        </button>
                    </div>
                </div>

                <!-- Audit Logs -->
                <div id="auditLogsList">
                    <div class="text-center py-4">
                        <div class="spinner-border text-secondary"></div>
                        <p class="mt-2 text-secondary">Loading audit trail...</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div id="auditPagination" class="d-flex justify-content-between align-items-center mt-3">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Reviews Modal -->
<div class="modal fade" id="reviewsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-warning">
                <h5 class="modal-title text-dark">
                    <i class="bx bx-star me-2"></i>Reviews - <span id="reviewsCourseName"></span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="reviewsCourseId">

                <!-- Stats Summary -->
                <div class="row mb-3">
                    <div class="col-md-3">
                        <div class="card bg-light mb-0">
                            <div class="card-body text-center py-3">
                                <h3 class="mb-1 text-warning" id="avgRatingDisplay">0.0</h3>
                                <div id="avgStarsDisplay" class="mb-1"></div>
                                <small class="text-secondary"><span id="totalReviewsCount">0</span> reviews</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-9">
                        <div class="card bg-light mb-0">
                            <div class="card-body py-3">
                                <div class="rating-bars">
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="text-dark me-2" style="width: 50px;">5 stars</span>
                                        <div class="progress flex-grow-1" style="height: 10px;">
                                            <div class="progress-bar bg-success" id="fiveStarBar" style="width: 0%"></div>
                                        </div>
                                        <span class="text-secondary ms-2" style="width: 30px;" id="fiveStarCount">0</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="text-dark me-2" style="width: 50px;">4 stars</span>
                                        <div class="progress flex-grow-1" style="height: 10px;">
                                            <div class="progress-bar bg-primary" id="fourStarBar" style="width: 0%"></div>
                                        </div>
                                        <span class="text-secondary ms-2" style="width: 30px;" id="fourStarCount">0</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="text-dark me-2" style="width: 50px;">3 stars</span>
                                        <div class="progress flex-grow-1" style="height: 10px;">
                                            <div class="progress-bar bg-info" id="threeStarBar" style="width: 0%"></div>
                                        </div>
                                        <span class="text-secondary ms-2" style="width: 30px;" id="threeStarCount">0</span>
                                    </div>
                                    <div class="d-flex align-items-center mb-1">
                                        <span class="text-dark me-2" style="width: 50px;">2 stars</span>
                                        <div class="progress flex-grow-1" style="height: 10px;">
                                            <div class="progress-bar bg-warning" id="twoStarBar" style="width: 0%"></div>
                                        </div>
                                        <span class="text-secondary ms-2" style="width: 30px;" id="twoStarCount">0</span>
                                    </div>
                                    <div class="d-flex align-items-center">
                                        <span class="text-dark me-2" style="width: 50px;">1 star</span>
                                        <div class="progress flex-grow-1" style="height: 10px;">
                                            <div class="progress-bar bg-danger" id="oneStarBar" style="width: 0%"></div>
                                        </div>
                                        <span class="text-secondary ms-2" style="width: 30px;" id="oneStarCount">0</span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="row mb-3 bg-light p-3 rounded">
                    <div class="col-md-3">
                        <label class="form-label text-dark fw-medium small">Filter by Rating</label>
                        <select class="form-select form-select-sm" id="reviewRatingFilter">
                            <option value="">All Ratings</option>
                            <option value="5">5 Stars</option>
                            <option value="4">4 Stars</option>
                            <option value="3">3 Stars</option>
                            <option value="2">2 Stars</option>
                            <option value="1">1 Star</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark fw-medium small">Approval Status</label>
                        <select class="form-select form-select-sm" id="reviewApprovalFilter">
                            <option value="">All Reviews</option>
                            <option value="true">Approved</option>
                            <option value="false">Pending Approval</option>
                        </select>
                    </div>
                    <div class="col-md-6 d-flex align-items-end">
                        <button type="button" class="btn btn-warning btn-sm me-2" id="applyReviewFilters">
                            <i class="bx bx-filter me-1"></i>Filter
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="clearReviewFilters">
                            <i class="bx bx-reset me-1"></i>Clear
                        </button>
                    </div>
                </div>

                <!-- Reviews List -->
                <div id="reviewsList">
                    <div class="text-center py-4">
                        <div class="spinner-border text-warning"></div>
                        <p class="mt-2 text-secondary">Loading reviews...</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div id="reviewsPagination" class="d-flex justify-content-between align-items-center mt-3">
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Review Reply Modal -->
<div class="modal fade" id="reviewReplyModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1061;">
        <div class="modal-content">
            <div class="modal-header bg-success text-white">
                <h5 class="modal-title">
                    <i class="bx bx-reply me-2"></i>Reply to Review
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="replyReviewId">
                <div class="mb-3">
                    <label class="form-label text-dark">Your Reply</label>
                    <textarea class="form-control" id="reviewReplyText" rows="4"
                              placeholder="Write your reply... You can include emoji and GIF URLs"></textarea>
                    <small class="text-secondary">Tip: Paste a GIF URL ending in .gif to embed it in your reply</small>
                </div>

                <!-- Quick Emoji Picker -->
                <div class="mb-3">
                    <label class="form-label text-dark small">Quick Emoji</label>
                    <div class="d-flex flex-wrap gap-1">
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="👍">👍</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="❤️">❤️</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="🎉">🎉</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="👏">👏</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="🙏">🙏</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="😊">😊</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="🔥">🔥</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="⭐">⭐</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="💯">💯</button>
                        <button type="button" class="btn btn-outline-secondary btn-sm emoji-btn" data-emoji="✨">✨</button>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-success" id="submitReviewReply">
                    <i class="bx bx-send me-1"></i>Send Reply
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Review Confirmation Modal -->
<div class="modal fade" id="deleteReviewModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1061;">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bx bx-trash me-2"></i>Delete Review
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete this review?</p>
                <div class="bg-light p-3 rounded mb-3">
                    <div id="deleteReviewStars" class="mb-2"></div>
                    <small class="text-secondary d-block mb-1">By: <span id="deleteReviewStudent"></span></small>
                    <span id="deleteReviewText" class="text-dark"></span>
                </div>
                <p class="text-secondary small mb-0">
                    <i class="bx bx-info-circle me-1"></i>This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteReview">
                    <i class="bx bx-trash me-1"></i>Delete Review
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Review Reply Modal -->
<div class="modal fade" id="deleteReviewReplyModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1061;">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">
                    <i class="bx bx-trash me-2"></i>Delete Reply
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete this reply?</p>
                <div class="bg-light p-3 rounded mb-3">
                    <small class="text-secondary d-block mb-1">Reply preview:</small>
                    <span id="deleteReplyText" class="text-dark"></span>
                </div>
                <p class="text-secondary small mb-0">
                    <i class="bx bx-info-circle me-1"></i>This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteReviewReply">
                    <i class="bx bx-trash me-1"></i>Delete Reply
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Course Settings Modal -->
<div class="modal fade" id="settingsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-info text-white">
                <h5 class="modal-title">
                    <i class="bx bx-cog me-2"></i>Settings - <span id="settingsCourseName"></span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="settingsCourseId">

                <!-- Tabs Navigation -->
                <ul class="nav nav-tabs nav-tabs-custom" role="tablist">
                    <li class="nav-item" role="presentation">
                        <button class="nav-link active" id="courseFlowTab" data-bs-toggle="tab"
                                data-bs-target="#courseFlowPane" type="button" role="tab">
                            <i class="bx bx-git-branch me-1"></i>Course Flow
                        </button>
                    </li>
                    <!-- Future tabs can be added here -->
                    <!--
                    <li class="nav-item" role="presentation">
                        <button class="nav-link" id="certificateTab" data-bs-toggle="tab"
                                data-bs-target="#certificatePane" type="button" role="tab">
                            <i class="bx bx-award me-1"></i>Certificates
                        </button>
                    </li>
                    -->
                </ul>

                <!-- Tab Content -->
                <div class="tab-content pt-4">
                    <!-- Course Flow Tab -->
                    <div class="tab-pane fade show active" id="courseFlowPane" role="tabpanel">
                        <div class="card border mb-0">
                            <div class="card-body">
                                <h6 class="card-title text-dark mb-3">
                                    <i class="bx bx-git-branch me-2 text-info"></i>Course Flow Settings
                                </h6>
                                <p class="text-secondary small mb-4">
                                    Configure how students navigate through the course content.
                                </p>

                                <!-- Content Access Mode -->
                                <div class="mb-4">
                                    <label class="form-label text-dark fw-medium">
                                        <i class="bx bx-lock-open me-1"></i>Content Access Mode
                                    </label>
                                    <p class="text-secondary small mb-2">
                                        Control whether students can access all content freely or must follow a sequential order.
                                    </p>

                                    <div class="form-check mb-2">
                                        <input class="form-check-input" type="radio" name="contentAccessMode"
                                               id="accessModeOpen" value="open">
                                        <label class="form-check-label text-dark" for="accessModeOpen">
                                            <strong>Open Access</strong>
                                            <br>
                                            <small class="text-secondary">
                                                All topics and chapters are accessible from the start.
                                                Students can navigate freely throughout the course.
                                            </small>
                                        </label>
                                    </div>

                                    <div class="form-check">
                                        <input class="form-check-input" type="radio" name="contentAccessMode"
                                               id="accessModeSequential" value="sequential">
                                        <label class="form-check-label text-dark" for="accessModeSequential">
                                            <strong>Sequential (Linear)</strong>
                                            <br>
                                            <small class="text-secondary">
                                                Topics unlock only after the previous topic is marked as complete.
                                                Students must follow the prescribed order.
                                            </small>
                                        </label>
                                    </div>
                                </div>

                                <hr class="my-4">

                                <!-- Quiz Blocking Setting -->
                                <div class="mb-4">
                                    <label class="form-label text-dark fw-medium">
                                        <i class="bx bx-task me-1"></i>Quiz Requirements
                                    </label>
                                    <p class="text-secondary small mb-2">
                                        Configure whether quizzes must be passed before proceeding to the next chapter.
                                    </p>

                                    <div class="form-check form-switch">
                                        <input class="form-check-input" type="checkbox" role="switch"
                                               id="quizBlocksNextChapter">
                                        <label class="form-check-label text-dark" for="quizBlocksNextChapter">
                                            <strong>Require quiz pass to unlock next chapter</strong>
                                            <br>
                                            <small class="text-secondary">
                                                When enabled, students must pass the chapter quiz before they can access the next chapter.
                                            </small>
                                        </label>
                                    </div>
                                </div>

                                <!-- Current Settings Summary -->
                                <div class="alert alert-light border mb-4" id="settingsSummary">
                                    <small class="text-secondary">
                                        <i class="bx bx-info-circle me-1"></i>
                                        <strong>Current:</strong> <span id="currentSettingsSummary">Loading...</span>
                                    </small>
                                </div>

                                <!-- Save Button -->
                                <div class="text-end">
                                    <button type="button" class="btn btn-info" id="saveCourseFlowSettings">
                                        <i class="bx bx-save me-1"></i>Save Course Flow Settings
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Future tab panes can be added here -->
                    <!--
                    <div class="tab-pane fade" id="certificatePane" role="tabpanel">
                        Certificate settings content...
                    </div>
                    -->
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<!-- Delete Comment Confirmation Modal -->
<div class="modal fade" id="deleteCommentModal" tabindex="-1" aria-hidden="true" style="z-index: 1060;">
    <div class="modal-dialog modal-dialog-centered" style="z-index: 1061;">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">
                    <i class="bx bx-trash text-danger me-2"></i>Delete Comment
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete this comment?</p>
                <div class="bg-light p-3 rounded mb-3">
                    <small class="text-secondary d-block mb-1">Comment preview:</small>
                    <span id="deleteCommentText" class="text-dark"></span>
                </div>
                <p class="text-secondary small mb-0">
                    <i class="bx bx-info-circle me-1"></i>This action cannot be undone.
                </p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteComment">
                    <i class="bx bx-trash me-1"></i>Delete Comment
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Course Status Change Modal -->
<div class="modal fade" id="statusChangeModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header" id="statusChangeHeader">
                <h5 class="modal-title" id="statusChangeTitle">
                    <i class="bx bx-power-off me-2"></i>Change Course Status
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p id="statusChangeMessage"></p>
                <div class="bg-light p-3 rounded mb-3">
                    <small class="text-secondary d-block mb-1">Course:</small>
                    <strong id="statusChangeCourseName" class="text-dark"></strong>
                </div>
                <p class="text-secondary small mb-0" id="statusChangeNote"></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn" id="confirmStatusChange">
                    <i class="bx bx-check me-1"></i>Confirm
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Comments Modal -->
<div class="modal fade comments-modal" id="commentsModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-info text-white py-2">
                <h5 class="modal-title"><i class="bx bx-message-square-dots me-2"></i>Comments - <span id="commentsCourseName"></span></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <!-- Filters -->
                <div class="comment-filters">
                    <div class="row g-2 align-items-center">
                        <div class="col-md-4">
                            <input type="text" class="form-control form-control-sm" id="commentSearch" placeholder="Search comments...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="commentStatusFilter">
                                <option value="">All Status</option>
                                <option value="unanswered">Unanswered</option>
                                <option value="answered">Answered</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select form-select-sm" id="commentContentFilter">
                                <option value="">All Content</option>
                            </select>
                        </div>
                        <div class="col-md-2 text-end">
                            <button type="button" class="btn btn-sm btn-outline-secondary" onclick="loadComments()">
                                <i class="bx bx-refresh"></i>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Comments List -->
                <div class="comment-list" id="commentsList">
                    <div class="text-center py-5 text-secondary">
                        <i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i>
                        <p class="mt-2 mb-0">Loading comments...</p>
                    </div>
                </div>

                <!-- Pagination -->
                <div class="comments-pagination" id="commentsPagination" style="display: none;">
                    <div class="d-flex justify-content-between align-items-center">
                        <small class="text-secondary" id="commentsInfo"></small>
                        <nav>
                            <ul class="pagination pagination-sm mb-0" id="paginationLinks"></ul>
                        </nav>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Reply Modal -->
<div class="modal fade" id="replyModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header bg-success text-white py-2">
                <h5 class="modal-title"><i class="bx bx-reply me-2"></i>Reply to Comment</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <div class="bg-light p-3 rounded mb-3">
                        <small class="text-secondary">Replying to:</small>
                        <p class="mb-0 text-dark" id="replyingToText"></p>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label small fw-semibold">Your Reply <span class="text-danger">*</span></label>
                    <textarea class="form-control" id="replyText" rows="4" placeholder="Type your reply..." style="resize: vertical;"></textarea>
                    <div class="reply-toolbar">
                        <div class="reply-picker-wrapper">
                            <button type="button" onclick="toggleEmojiPicker('replyEmojiPicker')" title="Add Emoji">
                                <i class="bx bx-smile"></i> Emoji
                            </button>
                            <!-- Emoji Picker -->
                            <div class="emoji-picker-container" id="replyEmojiPicker">
                                <div class="emoji-tabs">
                                    <button class="emoji-tab active" data-category="smileys">😀</button>
                                    <button class="emoji-tab" data-category="gestures">👍</button>
                                    <button class="emoji-tab" data-category="hearts">❤️</button>
                                    <button class="emoji-tab" data-category="objects">⭐</button>
                                </div>
                                <div class="emoji-grid" id="replyEmojiGrid"></div>
                            </div>
                        </div>
                        <div class="reply-picker-wrapper">
                            <button type="button" onclick="toggleGifPicker('replyGifPicker')" title="Add GIF">
                                <i class="bx bx-image"></i> GIF
                            </button>
                            <!-- GIF Picker -->
                            <div class="gif-picker-container" id="replyGifPicker">
                                <div class="gif-search">
                                    <input type="text" id="replyGifSearch" placeholder="Search GIFs..." onkeyup="searchGifsDebounced(this.value, 'replyGifGrid')">
                                </div>
                                <div class="gif-grid" id="replyGifGrid"></div>
                            </div>
                        </div>
                    </div>
                    <div id="replyPreview" class="mt-2" style="display: none;"></div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-sm btn-success" id="sendReplyBtn" onclick="sendReply()">
                    <i class="bx bx-send me-1"></i>Send Reply
                </button>
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
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
// Toastr configuration
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000,
    extendedTimeOut: 1000,
    preventDuplicates: true
};

$(document).ready(function() {
    // Show loading overlay
    function showLoading() {
        $('#tableLoadingOverlay').show();
    }

    // Hide loading overlay
    function hideLoading() {
        $('#tableLoadingOverlay').hide();
    }

    // Show loading on page load for better UX
    showLoading();
    setTimeout(function() {
        hideLoading();
    }, 300);

    // Show loading on search form submission
    $('form input[name="search"]').closest('form').on('submit', function() {
        showLoading();
    });

    // Show loading on pagination links
    $(document).on('click', '.pagination a', function() {
        showLoading();
    });

    // Show loading on clear filters link
    $('a.btn-outline-danger').on('click', function() {
        showLoading();
    });

    // Load unanswered counts for all courses
    loadUnansweredCounts();

    // Comments button click - open modal
    $('.comments-btn').on('click', function() {
        currentCourseId = $(this).data('course-id');
        const courseName = $(this).data('course-name');
        $('#commentsCourseName').text(courseName);
        loadContentFilter(currentCourseId);
        loadComments();
        $('#commentsModal').modal('show');
    });

    // Filter change handlers
    $('#commentSearch').on('input', debounce(loadComments, 300));
    $('#commentStatusFilter, #commentContentFilter').on('change', loadComments);

    // Access Tags button click
    $('.tags-btn').on('click', function() {
        const courseId = $(this).data('course-id');
        window.location.href = `/anisenso-courses-tags?id=${courseId}`;
    });

    // Delete functionality
    let courseToDelete = null;

    // Show delete confirmation modal
    $('.delete-btn').on('click', function() {
        const courseId = $(this).data('course-id');
        const courseName = $(this).data('course-name');

        courseToDelete = {
            id: courseId,
            name: courseName,
            row: $(this).closest('tr')
        };

        $('#deleteCourseName').text(courseName);
        $('#deleteModal').modal('show');
    });

    // Handle delete confirmation
    $('#confirmDelete').on('click', function() {
        if (!courseToDelete) return;

        const $btn = $(this);
        const originalText = $btn.html();

        // Show loading state
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');
        showLoading();

        $.ajax({
            url: '/anisenso-courses/' + courseToDelete.id,
            type: 'DELETE',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                // Hide modal
                $('#deleteModal').modal('hide');

                // Show success toastr notification
                toastr.success('Course has been successfully deleted.', 'Success!');

                // Remove the row from the table with animation
                courseToDelete.row.fadeOut(400, function() {
                    $(this).remove();

                    // Check if table is empty
                    if ($('tbody tr').length === 0) {
                        $('tbody').html(`
                            <tr>
                                <td colspan="5" class="text-center text-muted py-5">
                                    <i class="bx bx-book-open display-4"></i>
                                    <p class="mt-2 text-dark">No courses found</p>
                                    <a href="{{ route('anisenso-courses-add') }}" class="btn btn-primary btn-sm">
                                        <i class="bx bx-plus"></i> Add First Course
                                    </a>
                                </td>
                            </tr>
                        `);
                    }
                });
            },
            error: function(xhr) {
                let errorMessage = 'An error occurred while deleting the course.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }

                // Hide modal
                $('#deleteModal').modal('hide');

                toastr.error(errorMessage, 'Error!');
            },
            complete: function() {
                // Reset button state
                $btn.prop('disabled', false).html(originalText);
                courseToDelete = null;
                hideLoading();
            }
        });
    });

    // Reset courseToDelete when modal is hidden
    $('#deleteModal').on('hidden.bs.modal', function() {
        courseToDelete = null;
    });

    // Status toggle functionality - show modal
    let statusChangeData = null;

    $('.status-btn').on('click', function() {
        const $btn = $(this);
        const courseId = $btn.data('course-id');
        const courseName = $btn.data('course-name');
        const isActive = $btn.data('is-active') === 1;

        // Store data for later
        statusChangeData = {
            courseId: courseId,
            courseName: courseName,
            isActive: isActive,
            $btn: $btn
        };

        // Update modal content
        const action = isActive ? 'Deactivate' : 'Activate';
        const message = isActive
            ? 'Are you sure you want to deactivate this course? It will be hidden from students.'
            : 'Are you sure you want to activate this course? It will be visible to students.';
        const note = isActive
            ? '<i class="bx bx-info-circle me-1"></i>Students will no longer see this course in their list.'
            : '<i class="bx bx-info-circle me-1"></i>Students will be able to access this course.';

        $('#statusChangeTitle').html(`<i class="bx bx-power-off me-2"></i>${action} Course`);
        $('#statusChangeMessage').text(message);
        $('#statusChangeCourseName').text(courseName);
        $('#statusChangeNote').html(note);

        // Update header and button colors
        const $header = $('#statusChangeHeader');
        const $confirmBtn = $('#confirmStatusChange');
        if (isActive) {
            $header.removeClass('bg-success').addClass('bg-warning');
            $confirmBtn.removeClass('btn-success').addClass('btn-warning').html('<i class="bx bx-pause me-1"></i>Deactivate');
        } else {
            $header.removeClass('bg-warning').addClass('bg-success');
            $confirmBtn.removeClass('btn-warning').addClass('btn-success').html('<i class="bx bx-play me-1"></i>Activate');
        }

        $('#statusChangeModal').modal('show');
    });

    // Handle status change confirmation
    $('#confirmStatusChange').on('click', function() {
        if (!statusChangeData) return;

        const { courseId, $btn, isActive } = statusChangeData;
        const $confirmBtn = $(this);

        const originalBtnHtml = $btn.html();
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>...');
        $confirmBtn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Processing...');

        $.ajax({
            url: '/anisenso-courses/' + courseId + '/status',
            type: 'PUT',
            data: {
                _token: '{{ csrf_token() }}'
            },
            success: function(response) {
                if (response.success) {
                    const isNowActive = response.isActive;

                    // Update badge
                    const $badge = $(`.status-badge[data-course-id="${courseId}"]`);
                    $badge.removeClass('bg-success bg-secondary')
                          .addClass(isNowActive ? 'bg-success' : 'bg-secondary')
                          .text(isNowActive ? 'Active' : 'Inactive');

                    // Update button
                    $btn.data('is-active', isNowActive ? 1 : 0)
                        .removeClass('btn-outline-warning btn-outline-success')
                        .addClass(isNowActive ? 'btn-outline-warning' : 'btn-outline-success')
                        .html(`<i class="bx ${isNowActive ? 'bx-pause' : 'bx-play'} me-1"></i>Status`);

                    $('#statusChangeModal').modal('hide');
                    toastr.success(response.message, 'Success!');
                }
            },
            error: function(xhr) {
                let errorMessage = 'Failed to update status.';
                if (xhr.responseJSON && xhr.responseJSON.message) {
                    errorMessage = xhr.responseJSON.message;
                }
                toastr.error(errorMessage, 'Error!');
                $btn.prop('disabled', false).html(originalBtnHtml);
            },
            complete: function() {
                $btn.prop('disabled', false);
                $confirmBtn.prop('disabled', false).html(isActive ? '<i class="bx bx-pause me-1"></i>Deactivate' : '<i class="bx bx-play me-1"></i>Activate');
                statusChangeData = null;
            }
        });
    });
});

// ==================== COMMENTS FUNCTIONALITY ====================
let currentCourseId = null;
let currentPage = 1;
let replyToCommentId = null;
let gifSearchTimeout = null;

// Emoji data
const emojiData = {
    smileys: ['😀', '😃', '😄', '😁', '😆', '😅', '🤣', '😂', '🙂', '😊', '😇', '🥰', '😍', '🤩', '😘', '😗', '😚', '😋', '😛', '😜', '🤪', '😝', '🤗', '🤔', '🤭', '😐', '😑', '😶', '😏', '😒', '🙄', '😬', '😮', '😯', '😲', '😳', '🥺', '😢', '😭', '😤', '😠', '😡', '🤬', '😈', '👿', '💀', '☠️', '💩', '🤡', '👹', '👺', '👻', '👽', '🤖', '😺', '😸', '😹', '😻', '😼', '😽', '🙀', '😿', '😾'],
    gestures: ['👍', '👎', '👊', '✊', '🤛', '🤜', '🤞', '✌️', '🤟', '🤘', '👌', '🤏', '👈', '👉', '👆', '👇', '☝️', '✋', '🤚', '🖐️', '🖖', '👋', '🤙', '💪', '🦾', '🙏', '🤝', '👏', '🙌', '👐', '🤲', '🤷', '🙅', '🙆', '🙋', '🤦', '🤵', '👰', '🧑‍🎓', '👨‍💻', '👩‍💼'],
    hearts: ['❤️', '🧡', '💛', '💚', '💙', '💜', '🖤', '🤍', '🤎', '💔', '❣️', '💕', '💞', '💓', '💗', '💖', '💘', '💝', '💟', '♥️', '😍', '🥰', '😘', '😻', '💑', '💏'],
    objects: ['⭐', '🌟', '✨', '💫', '🔥', '💯', '✅', '❌', '❓', '❗', '💡', '📌', '📍', '🎯', '🏆', '🥇', '🎉', '🎊', '🎁', '📚', '📖', '✏️', '📝', '💻', '📱', '⌚', '🔔', '🔕', '📢', '💬', '💭', '🗨️', '👁️‍🗨️']
};

function debounce(func, wait) {
    let timeout;
    return function executedFunction(...args) {
        const later = () => { clearTimeout(timeout); func(...args); };
        clearTimeout(timeout);
        timeout = setTimeout(later, wait);
    };
}

function loadUnansweredCounts() {
    $('.comments-btn').each(function() {
        const $btn = $(this);
        const courseId = $btn.data('course-id');
        $.get(`/api/anisenso/courses/${courseId}/comments/unanswered-count`, function(r) {
            if (r.success && r.count > 0) {
                $btn.find('.unanswered-badge').text(r.count).show();
            }
        });
    });
}

function loadContentFilter(courseId) {
    $.get(`/api/anisenso/courses/${courseId}/contents-list`, function(r) {
        if (r.success) {
            let options = '<option value="">All Content</option>';
            r.data.forEach(chapter => {
                chapter.topics?.forEach(topic => {
                    topic.contents?.forEach(content => {
                        options += `<option value="${content.id}">${chapter.chapterTitle} > ${topic.topicTitle} > ${content.contentTitle}</option>`;
                    });
                });
            });
            $('#commentContentFilter').html(options);
        }
    });
}

function loadComments(page = 1) {
    currentPage = page;
    const params = {
        search: $('#commentSearch').val(),
        status: $('#commentStatusFilter').val(),
        contentId: $('#commentContentFilter').val(),
        page: page,
        perPage: 10
    };

    $('#commentsList').html('<div class="text-center py-5"><i class="bx bx-loader-alt bx-spin" style="font-size: 2rem;"></i><p class="mt-2 text-secondary">Loading comments...</p></div>');

    $.get(`/api/anisenso/courses/${currentCourseId}/comments`, params, function(r) {
        if (r.success) {
            renderComments(r.data);
        }
    }).fail(function() {
        $('#commentsList').html('<div class="text-center py-5 text-danger"><i class="bx bx-error"></i><p class="mt-2">Failed to load comments</p></div>');
    });
}

function renderComments(data) {
    if (!data.data || data.data.length === 0) {
        $('#commentsList').html('<div class="text-center py-5"><i class="bx bx-message-square-x text-secondary" style="font-size: 3rem;"></i><p class="mt-2 text-dark">No comments found</p><small class="text-secondary">Comments from students will appear here</small></div>');
        $('#commentsPagination').hide();
        return;
    }

    let html = '';
    data.data.forEach(comment => {
        html += renderCommentItem(comment);
    });
    $('#commentsList').html(html);

    // Pagination
    if (data.last_page > 1) {
        $('#commentsInfo').text(`Showing ${data.from}-${data.to} of ${data.total} comments`);
        let paginationHtml = '';
        for (let i = 1; i <= data.last_page; i++) {
            paginationHtml += `<li class="page-item ${i === data.current_page ? 'active' : ''}"><a class="page-link" href="#" onclick="loadComments(${i}); return false;">${i}</a></li>`;
        }
        $('#paginationLinks').html(paginationHtml);
        $('#commentsPagination').show();
    } else {
        $('#commentsPagination').hide();
    }
}

function renderCommentItem(comment) {
    const statusClass = comment.isAnswered ? 'answered' : 'unanswered';
    const avatarUrl = comment.authorAvatar || generateAvatar(comment.authorName);
    const contentPath = comment.content ? `${comment.content.topic?.chapter?.chapterTitle || ''} > ${comment.content.topic?.topicTitle || ''} > ${comment.content.contentTitle}` : 'General';
    const contentId = comment.content?.id || null;

    // Reaction counts
    const likesCount = comment.likesCount || 0;
    const heartsCount = comment.heartsCount || 0;

    // Check localStorage for user reactions
    const userReactions = JSON.parse(localStorage.getItem(`anisenso_reactions_${comment.id}`) || '{}');
    const likedClass = (likesCount > 0 ? 'liked' : '') + (userReactions.like ? ' user-reacted' : '');
    const heartedClass = (heartsCount > 0 ? 'hearted' : '') + (userReactions.heart ? ' user-reacted' : '');

    let html = `
        <div class="comment-item ${statusClass}" data-comment-id="${comment.id}">
            <div class="comment-header">
                <img src="${avatarUrl}" class="comment-avatar" alt="${escapeHtml(comment.authorName)}">
                <div>
                    <div class="comment-author">${escapeHtml(comment.authorName)} ${comment.authorType === 'admin' ? '<span class="badge bg-success">Admin</span>' : ''}</div>
                    <div class="comment-time">${formatTimeAgo(comment.created_at)}</div>
                </div>
                <span class="comment-source" onclick="showContentDetails(${contentId}, '${escapeHtml(contentPath).replace(/'/g, "\\'")}')" title="Click to view details: ${escapeHtml(contentPath)}">${truncate(contentPath, 30)}</span>
            </div>
            <div class="comment-text">${formatCommentText(comment.commentText)}</div>
            <div class="comment-actions d-flex align-items-center">
                <button class="btn btn-sm btn-outline-success" onclick="toggleInlineReply(${comment.id})">
                    <i class="bx bx-reply me-1"></i>Reply
                </button>
                <button class="btn btn-sm btn-outline-danger ms-1" onclick="deleteComment(${comment.id}, '${escapeHtml(comment.commentText).replace(/'/g, "\\'")}')">
                    <i class="bx bx-trash"></i>
                </button>
                <div class="comment-reactions ms-auto">
                    <button class="reaction-btn ${likedClass}" onclick="addReaction(${comment.id}, 'like')" title="${userReactions.like ? 'You liked this' : 'Like'}">
                        <i class="bx bx-like"></i> <span class="reaction-count" id="likes-${comment.id}">${likesCount}</span>
                    </button>
                    <button class="reaction-btn ${heartedClass}" onclick="addReaction(${comment.id}, 'heart')" title="${userReactions.heart ? 'You hearted this' : 'Heart'}">
                        <i class="bx bx-heart"></i> <span class="reaction-count" id="hearts-${comment.id}">${heartsCount}</span>
                    </button>
                </div>
            </div>
            <!-- Inline Reply Form -->
            <div class="inline-reply-form" id="inline-reply-${comment.id}">
                <textarea id="inline-reply-text-${comment.id}" placeholder="Write your reply..."></textarea>
                <div class="inline-reply-actions">
                    <div class="inline-reply-tools">
                        <div class="reply-picker-wrapper">
                            <button type="button" onclick="toggleInlineEmojiPicker(${comment.id})"><i class="bx bx-smile"></i></button>
                            <div class="emoji-picker-container" id="inline-emoji-picker-${comment.id}"></div>
                        </div>
                        <div class="reply-picker-wrapper">
                            <button type="button" onclick="toggleInlineGifPicker(${comment.id})"><i class="bx bx-image"></i></button>
                            <div class="gif-picker-container" id="inline-gif-picker-${comment.id}">
                                <div class="gif-search"><input type="text" placeholder="Search GIFs..." onkeyup="searchInlineGifsDebounced(this.value, ${comment.id})"></div>
                                <div class="gif-grid" id="inline-gif-grid-${comment.id}"></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-light" onclick="toggleInlineReply(${comment.id})">Cancel</button>
                        <button type="button" class="btn btn-sm btn-success" onclick="sendInlineReplyComment(${comment.id})"><i class="bx bx-send me-1"></i>Send</button>
                    </div>
                </div>
            </div>`;

    // Render replies (depth 1 = first level replies)
    // Check both all_replies (from recursive loading) and replies (from eager loading)
    const replies = comment.all_replies || comment.replies || [];
    if (replies.length > 0) {
        html += '<div class="comment-replies">';
        replies.forEach(reply => {
            html += renderReplyItem(reply, 1);
        });
        html += '</div>';
    }

    html += '</div>';
    return html;
}

const MAX_REPLY_DEPTH = 2; // Limit to 3 levels: Comment -> Reply -> Reply to Reply

function renderReplyItem(reply, depth = 1) {
    const avatarUrl = reply.authorAvatar || generateAvatar(reply.authorName);
    const isAdmin = reply.authorType === 'admin';
    const canReply = depth < MAX_REPLY_DEPTH;

    // Reaction counts
    const likesCount = reply.likesCount || 0;
    const heartsCount = reply.heartsCount || 0;

    // Check localStorage for user reactions
    const userReactions = JSON.parse(localStorage.getItem(`anisenso_reactions_${reply.id}`) || '{}');
    const likedClass = (likesCount > 0 ? 'liked' : '') + (userReactions.like ? ' user-reacted' : '');
    const heartedClass = (heartsCount > 0 ? 'hearted' : '') + (userReactions.heart ? ' user-reacted' : '');

    let html = `
        <div class="reply-item ${isAdmin ? 'admin-reply' : ''}" data-comment-id="${reply.id}">
            <div class="d-flex align-items-start">
                <img src="${avatarUrl}" class="comment-avatar" style="width: 32px; height: 32px;" alt="${escapeHtml(reply.authorName)}">
                <div class="flex-grow-1">
                    <div class="d-flex align-items-center mb-1">
                        <span class="comment-author" style="font-size: 13px;">${escapeHtml(reply.authorName)} ${isAdmin ? '<span class="badge bg-success" style="font-size: 9px;">Admin</span>' : ''}</span>
                        <small class="text-secondary ms-2">${formatTimeAgo(reply.created_at)}</small>
                    </div>
                    <div class="comment-text" style="font-size: 13px;">${formatCommentText(reply.commentText)}</div>
                    <div class="reply-actions mt-1 d-flex align-items-center">
                        ${canReply ? `<button class="btn btn-sm btn-link text-success p-0 me-2" onclick="toggleInlineReply(${reply.id})">
                            <i class="bx bx-reply"></i> Reply
                        </button>` : ''}
                        <button class="btn btn-sm btn-link text-danger p-0 me-2" onclick="deleteComment(${reply.id}, '${escapeHtml(reply.commentText).replace(/'/g, "\\'")}')"><i class="bx bx-trash"></i></button>
                        <div class="comment-reactions ms-auto" style="font-size: 10px;">
                            <button class="reaction-btn ${likedClass}" style="padding: 1px 6px; font-size: 10px;" onclick="addReaction(${reply.id}, 'like')" title="${userReactions.like ? 'You liked this' : 'Like'}">
                                <i class="bx bx-like"></i> <span id="likes-${reply.id}">${likesCount}</span>
                            </button>
                            <button class="reaction-btn ${heartedClass}" style="padding: 1px 6px; font-size: 10px;" onclick="addReaction(${reply.id}, 'heart')" title="${userReactions.heart ? 'You hearted this' : 'Heart'}">
                                <i class="bx bx-heart"></i> <span id="hearts-${reply.id}">${heartsCount}</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>`;

    // Only show reply form if can reply
    if (canReply) {
        html += `
            <!-- Inline Reply Form for nested reply -->
            <div class="inline-reply-form" id="inline-reply-${reply.id}" style="margin-left: 40px;">
                <textarea id="inline-reply-text-${reply.id}" placeholder="Write your reply..."></textarea>
                <div class="inline-reply-actions">
                    <div class="inline-reply-tools">
                        <div class="reply-picker-wrapper">
                            <button type="button" onclick="toggleInlineEmojiPicker(${reply.id})"><i class="bx bx-smile"></i></button>
                            <div class="emoji-picker-container" id="inline-emoji-picker-${reply.id}"></div>
                        </div>
                        <div class="reply-picker-wrapper">
                            <button type="button" onclick="toggleInlineGifPicker(${reply.id})"><i class="bx bx-image"></i></button>
                            <div class="gif-picker-container" id="inline-gif-picker-${reply.id}">
                                <div class="gif-search"><input type="text" placeholder="Search GIFs..." onkeyup="searchInlineGifsDebounced(this.value, ${reply.id})"></div>
                                <div class="gif-grid" id="inline-gif-grid-${reply.id}"></div>
                            </div>
                        </div>
                    </div>
                    <div>
                        <button type="button" class="btn btn-sm btn-light" onclick="toggleInlineReply(${reply.id})">Cancel</button>
                        <button type="button" class="btn btn-sm btn-success" onclick="sendInlineReplyComment(${reply.id})"><i class="bx bx-send me-1"></i>Send</button>
                    </div>
                </div>
            </div>`;
    }

    // Nested replies with incremented depth
    // Check both all_replies (from recursive loading) and replies (from eager loading)
    const nestedReplies = reply.all_replies || reply.replies || [];
    if (nestedReplies.length > 0) {
        html += '<div class="comment-replies" style="margin-left: 32px;">';
        nestedReplies.forEach(nestedReply => {
            html += renderReplyItem(nestedReply, depth + 1);
        });
        html += '</div>';
    }

    html += '</div>';
    return html;
}

function generateAvatar(name) {
    const initial = name ? name.charAt(0).toUpperCase() : '?';
    const colors = ['#3b82f6', '#10b981', '#f59e0b', '#ef4444', '#8b5cf6', '#ec4899'];
    const color = colors[initial.charCodeAt(0) % colors.length];
    return `data:image/svg+xml,${encodeURIComponent(`<svg xmlns='http://www.w3.org/2000/svg' width='40' height='40'><rect fill='${color}' width='40' height='40'/><text x='50%' y='50%' dy='.35em' fill='white' text-anchor='middle' font-family='Arial' font-size='18' font-weight='bold'>${initial}</text></svg>`)}`;
}

function formatTimeAgo(dateStr) {
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    if (diff < 60) return 'Just now';
    if (diff < 3600) return Math.floor(diff / 60) + ' min ago';
    if (diff < 86400) return Math.floor(diff / 3600) + ' hours ago';
    if (diff < 604800) return Math.floor(diff / 86400) + ' days ago';
    return date.toLocaleDateString();
}

function formatCommentText(text) {
    if (!text) return '';
    // Convert GIF URLs to images
    text = text.replace(/\[gif:(https?:\/\/[^\]]+)\]/g, '<img src="$1" class="gif-image" alt="GIF">');
    // Highlight @mentions with bracket format: @[Full Name]
    text = text.replace(/@\[([^\]]+)\]/g, '<span class="mention-tag">@$1</span>');
    // Also support legacy @mentions without brackets (single word names)
    text = text.replace(/@([a-zA-Z0-9]+)(\s|$|,|\.)/g, '<span class="mention-tag">@$1</span>$2');
    // Convert line breaks
    text = text.replace(/\n/g, '<br>');
    return text;
}

function truncate(str, len) {
    if (!str) return '';
    return str.length > len ? str.substring(0, len) + '...' : str;
}

function escapeHtml(text) {
    if (!text) return '';
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

function openReplyModal(commentId, commentText) {
    replyToCommentId = commentId;
    $('#replyingToText').text(commentText.substring(0, 150) + (commentText.length > 150 ? '...' : ''));
    $('#replyText').val('');
    $('#replyPreview').hide().html('');
    $('#replyModal').modal('show');
    initEmojiPicker('replyEmojiGrid', 'replyText');
    loadTrendingGifs('replyGifGrid');
}

function sendReply() {
    const text = $('#replyText').val().trim();
    if (!text) {
        toastr.error('Please enter a reply');
        return;
    }

    const commentId = replyToCommentId;
    const $btn = $('#sendReplyBtn');
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Sending...');

    $.ajax({
        url: `/api/anisenso/comments/${commentId}/reply`,
        method: 'POST',
        data: { _token: '{{ csrf_token() }}', commentText: text },
        success: function(r) {
            if (r.success) {
                toastr.success('Reply sent successfully');
                $('#replyModal').modal('hide');
                $('#replyText').val('');

                // Find the parent comment and insert the new reply dynamically
                const $parentComment = $(`[data-comment-id="${commentId}"]`).first();

                // Find or create replies container
                let $repliesContainer = $parentComment.find('> .comment-replies').first();
                if ($repliesContainer.length === 0) {
                    $parentComment.append('<div class="comment-replies"></div>');
                    $repliesContainer = $parentComment.find('> .comment-replies').first();
                }

                // Calculate depth for the new reply
                const currentDepth = $parentComment.parents('.comment-replies').length;
                const newDepth = currentDepth + 1;

                // Render the new reply HTML
                const newReplyHtml = renderReplyItem(r.data, newDepth);
                const $newReply = $(newReplyHtml).hide();

                // Append and slide in with highlight effect
                $repliesContainer.append($newReply);
                $newReply.addClass('new-highlight').slideDown(300, function() {
                    setTimeout(() => {
                        $(this).removeClass('new-highlight');
                    }, 1500);
                });

                // Scroll to the new reply
                setTimeout(() => {
                    $newReply[0].scrollIntoView({ behavior: 'smooth', block: 'center' });
                }, 350);

                // Add new user to thread participants for @mention autocomplete
                addThreadParticipant(commentId, r.data.authorName);

                loadUnansweredCounts();
            } else {
                toastr.error(r.message || 'Failed to send reply');
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to send reply');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-send me-1"></i>Send Reply');
        }
    });
}

let commentToDelete = null;

function deleteComment(commentId, commentText = '') {
    commentToDelete = commentId;
    // Get comment text preview (truncate if too long)
    const preview = commentText ? (commentText.length > 100 ? commentText.substring(0, 100) + '...' : commentText) : 'This comment';
    $('#deleteCommentText').text(preview);
    $('#deleteCommentModal').modal('show');
}

function confirmDeleteComment() {
    if (!commentToDelete) return;

    const deleteId = commentToDelete;
    const $btn = $('#confirmDeleteComment');
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

    $.ajax({
        url: `/api/anisenso/comments/${deleteId}`,
        method: 'DELETE',
        headers: {
            'X-CSRF-TOKEN': '{{ csrf_token() }}'
        },
        success: function(r) {
            if (r.success) {
                $('#deleteCommentModal').modal('hide');
                toastr.success('Comment deleted');

                // Find and remove the comment with fade-out animation
                const $comment = $(`[data-comment-id="${deleteId}"]`).first();
                if ($comment.length) {
                    $comment.addClass('delete-highlight').slideUp(300, function() {
                        $(this).remove();
                    });
                }

                loadUnansweredCounts();
            } else {
                toastr.error(r.message || 'Failed to delete comment');
            }
        },
        error: function(xhr) {
            console.error('Delete error:', xhr.responseText);
            toastr.error(xhr.responseJSON?.message || 'Failed to delete comment');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete Comment');
            commentToDelete = null;
        }
    });
}

// Bind delete comment confirmation button
$(document).on('click', '#confirmDeleteComment', confirmDeleteComment);

// ==================== REACTIONS ====================
// Save user reaction to localStorage
function saveUserReaction(commentId, type) {
    const key = `anisenso_reactions_${commentId}`;
    const reactions = JSON.parse(localStorage.getItem(key) || '{}');
    reactions[type] = true;
    localStorage.setItem(key, JSON.stringify(reactions));
}

// Remove user reaction from localStorage
function removeUserReaction(commentId, type) {
    const key = `anisenso_reactions_${commentId}`;
    const reactions = JSON.parse(localStorage.getItem(key) || '{}');
    delete reactions[type];
    localStorage.setItem(key, JSON.stringify(reactions));
}

function addReaction(commentId, type) {
    $.ajax({
        url: `/api/anisenso/comments/${commentId}/reaction`,
        method: 'POST',
        headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' },
        data: { type: type },
        success: function(r) {
            if (r.success) {
                // Track user's reaction in localStorage
                if (r.data.userReacted) {
                    saveUserReaction(commentId, type);
                } else {
                    removeUserReaction(commentId, type);
                }

                // Update counts
                $(`#likes-${commentId}`).text(r.data.likesCount);
                $(`#hearts-${commentId}`).text(r.data.heartsCount);

                // Update button styling
                const $btn = $(`#${type === 'like' ? 'likes' : 'hearts'}-${commentId}`).closest('.reaction-btn');

                if (r.data.userReacted) {
                    $btn.addClass(type === 'like' ? 'liked' : 'hearted').addClass('user-reacted');
                    $btn.attr('title', `You ${type === 'like' ? 'liked' : 'hearted'} this`);
                    toastr.success(type === 'like' ? 'Liked!' : 'Hearted!');
                } else {
                    $btn.removeClass(type === 'like' ? 'liked' : 'hearted').removeClass('user-reacted');
                    $btn.attr('title', type === 'like' ? 'Like' : 'Heart');
                    toastr.info(type === 'like' ? 'Like removed' : 'Heart removed');
                }

                // Update liked/hearted class based on count
                if (r.data.likesCount > 0) {
                    $(`#likes-${commentId}`).closest('.reaction-btn').addClass('liked');
                } else {
                    $(`#likes-${commentId}`).closest('.reaction-btn').removeClass('liked');
                }
                if (r.data.heartsCount > 0) {
                    $(`#hearts-${commentId}`).closest('.reaction-btn').addClass('hearted');
                } else {
                    $(`#hearts-${commentId}`).closest('.reaction-btn').removeClass('hearted');
                }
            }
        },
        error: function() { toastr.error('Failed to update reaction'); }
    });
}

// ==================== EMOJI & GIF PICKER ====================
function toggleEmojiPicker(pickerId) {
    const $picker = $('#' + pickerId);
    $('.emoji-picker-container, .gif-picker-container').not($picker).removeClass('show');
    $picker.toggleClass('show');
}

function toggleGifPicker(pickerId) {
    const $picker = $('#' + pickerId);
    $('.emoji-picker-container, .gif-picker-container').not($picker).removeClass('show');
    $picker.toggleClass('show');
}

function initEmojiPicker(gridId, textareaId) {
    const $grid = $('#' + gridId);
    $grid.html('');
    emojiData.smileys.forEach(emoji => {
        $grid.append(`<div class="emoji-item" onclick="insertEmoji('${emoji}', '${textareaId}')">${emoji}</div>`);
    });

    // Tab switching
    $grid.closest('.emoji-picker-container').find('.emoji-tab').off('click').on('click', function() {
        $(this).siblings().removeClass('active');
        $(this).addClass('active');
        const category = $(this).data('category');
        $grid.html('');
        emojiData[category].forEach(emoji => {
            $grid.append(`<div class="emoji-item" onclick="insertEmoji('${emoji}', '${textareaId}')">${emoji}</div>`);
        });
    });
}

function insertEmoji(emoji, textareaId) {
    const $textarea = $('#' + textareaId);
    const pos = $textarea[0].selectionStart;
    const text = $textarea.val();
    $textarea.val(text.substring(0, pos) + emoji + text.substring(pos));
    $textarea.focus();
    $('.emoji-picker-container').removeClass('show');
}

function loadTrendingGifs(gridId) {
    const $grid = $('#' + gridId);
    $grid.html('<div class="text-center py-3"><i class="bx bx-loader-alt bx-spin"></i></div>');
    $.get('/api/anisenso/gifs/trending', { limit: 20 }, function(r) {
        if (r.success) {
            renderGifs(r.data, gridId);
        }
    }).fail(function() {
        $grid.html('<div class="text-center py-3 text-secondary">Failed to load GIFs</div>');
    });
}

function searchGifsDebounced(query, gridId) {
    clearTimeout(gifSearchTimeout);
    if (!query.trim()) {
        loadTrendingGifs(gridId);
        return;
    }
    gifSearchTimeout = setTimeout(() => {
        const $grid = $('#' + gridId);
        $grid.html('<div class="text-center py-3"><i class="bx bx-loader-alt bx-spin"></i></div>');
        $.get('/api/anisenso/gifs/search', { q: query, limit: 20 }, function(r) {
            if (r.success) {
                renderGifs(r.data, gridId);
            }
        });
    }, 300);
}

function renderGifs(gifs, gridId) {
    const $grid = $('#' + gridId);
    if (!gifs || gifs.length === 0) {
        $grid.html('<div class="text-center py-3 text-secondary">No GIFs found</div>');
        return;
    }
    let html = '';
    gifs.forEach(gif => {
        html += `<div class="gif-item" onclick="insertGif('${gif.url}')"><img src="${gif.preview}" alt="GIF" loading="lazy"></div>`;
    });
    $grid.html(html);
}

function insertGif(url) {
    const $textarea = $('#replyText');
    const text = $textarea.val();
    $textarea.val(text + (text ? '\n' : '') + `[gif:${url}]`);
    $('#replyPreview').html(`<img src="${url}" class="gif-image" style="max-width: 150px; border-radius: 6px;">`).show();
    $('.gif-picker-container').removeClass('show');
}

// Close pickers when clicking outside
$(document).on('click', function(e) {
    if (!$(e.target).closest('.emoji-picker-container, .gif-picker-container, .reply-toolbar button, .inline-reply-tools button, .reply-picker-wrapper button').length) {
        $('.emoji-picker-container, .gif-picker-container').removeClass('show');
    }
});

// ============ INLINE REPLY FUNCTIONS ============
function toggleInlineReply(commentId) {
    const $form = $(`#inline-reply-${commentId}`);
    $('.inline-reply-form').not($form).removeClass('show');
    $form.toggleClass('show');
    if ($form.hasClass('show')) {
        $(`#inline-reply-text-${commentId}`).focus();
    }
}

function toggleInlineEmojiPicker(commentId) {
    const $picker = $(`#inline-emoji-picker-${commentId}`);
    $('.emoji-picker-container, .gif-picker-container').not($picker).removeClass('show');

    if (!$picker.hasClass('show')) {
        initInlineEmojiPicker(commentId);
    }
    $picker.toggleClass('show');
}

function initInlineEmojiPicker(commentId) {
    const emojis = ['😀','😃','😄','😁','😆','😅','🤣','😂','🙂','😊','😇','😍','🥰','😘','😗','😋','😛','🤔','🤨','😐','😑','😶','🙄','😏','😣','😥','😮','🤐','😯','😪','😫','😴','😌','😜','🤤','😒','😓','😔','😕','🙃','🤑','😲','☹️','🙁','😖','😞','😟','😤','😢','😭','😦','😧','😨','😩','🤯','😬','😰','😱','🥵','🥶','😳','🤪','😵','😡','😠','🤬','😷','🤒','🤕','🤢','🤮','🤧','😇','🥳','🥴','🥺','🤠','🤡','🤥','🤫','🤭','👍','👎','👏','🙌','👋','🤝','✌️','👌','🤞','🤟','🤘','🤙','💪','🙏','❤️','🧡','💛','💚','💙','💜','🖤','💔','❣️','💕','💞','💓','💗','💖','💘','💝','⭐','🌟','✨','⚡','🔥','💯','👀','💬','💭','🗨️','📌','📍','✅','❌','⭕','❓','❔','❗','❕'];

    let html = '<div class="emoji-grid">';
    emojis.forEach(emoji => {
        html += `<div class="emoji-item" onclick="insertInlineEmoji('${emoji}', ${commentId})">${emoji}</div>`;
    });
    html += '</div>';
    $(`#inline-emoji-picker-${commentId}`).html(html);
}

function insertInlineEmoji(emoji, commentId) {
    const $textarea = $(`#inline-reply-text-${commentId}`);
    const pos = $textarea[0].selectionStart || $textarea.val().length;
    const text = $textarea.val();
    $textarea.val(text.substring(0, pos) + emoji + text.substring(pos));
    $textarea.focus();
    $('.emoji-picker-container').removeClass('show');
}

function toggleInlineGifPicker(commentId) {
    const $picker = $(`#inline-gif-picker-${commentId}`);
    $('.emoji-picker-container, .gif-picker-container').not($picker).removeClass('show');

    if (!$picker.hasClass('show')) {
        loadInlineTrendingGifs(commentId);
    }
    $picker.toggleClass('show');
}

let inlineGifSearchTimeout;
function searchInlineGifsDebounced(query, commentId) {
    clearTimeout(inlineGifSearchTimeout);
    if (!query.trim()) {
        loadInlineTrendingGifs(commentId);
        return;
    }
    inlineGifSearchTimeout = setTimeout(() => {
        const $grid = $(`#inline-gif-grid-${commentId}`);
        $grid.html('<div class="text-center py-2"><i class="bx bx-loader-alt bx-spin"></i></div>');
        $.get('/api/anisenso/gifs/search', { q: query, limit: 12 }, function(r) {
            if (r.success) renderInlineGifsForComment(r.data, commentId);
        });
    }, 300);
}

function loadInlineTrendingGifs(commentId) {
    const $grid = $(`#inline-gif-grid-${commentId}`);
    $grid.html('<div class="text-center py-2"><i class="bx bx-loader-alt bx-spin"></i></div>');
    $.get('/api/anisenso/gifs/trending', { limit: 12 }, function(r) {
        if (r.success) renderInlineGifsForComment(r.data, commentId);
    });
}

function renderInlineGifsForComment(gifs, commentId) {
    const $grid = $(`#inline-gif-grid-${commentId}`);
    if (!gifs || gifs.length === 0) {
        $grid.html('<div class="text-center py-2 text-secondary">No GIFs found</div>');
        return;
    }
    let html = '';
    gifs.forEach(gif => {
        html += `<div class="gif-item" onclick="insertInlineGif('${gif.url}', ${commentId})"><img src="${gif.preview}" alt="GIF" loading="lazy"></div>`;
    });
    $grid.html(html);
}

function insertInlineGif(url, commentId) {
    const $textarea = $(`#inline-reply-text-${commentId}`);
    const text = $textarea.val();
    $textarea.val(text + (text ? ' ' : '') + `[gif:${url}]`);
    $textarea.focus();
    $('.gif-picker-container').removeClass('show');
}

function sendInlineReplyComment(commentId) {
    const $textarea = $(`#inline-reply-text-${commentId}`);
    const text = $textarea.val().trim();

    if (!text) {
        toastr.error('Please enter a reply');
        return;
    }

    const $btn = $(`#inline-reply-${commentId} .btn-success`);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');

    $.ajax({
        url: `/api/anisenso/comments/${commentId}/reply`,
        method: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            commentText: text
        },
        success: function(r) {
            if (r.success) {
                toastr.success('Reply sent successfully');
                $textarea.val('');
                toggleInlineReply(commentId);

                // Find the parent comment/reply and insert the new reply dynamically
                const $parentComment = $(`[data-comment-id="${commentId}"]`).first();

                // Find or create replies container
                let $repliesContainer = $parentComment.find('> .comment-replies').first();
                if ($repliesContainer.length === 0) {
                    // Create the replies container after the inline-reply-form
                    $parentComment.append('<div class="comment-replies"></div>');
                    $repliesContainer = $parentComment.find('> .comment-replies');
                }

                // Calculate depth for the new reply
                const currentDepth = $parentComment.parents('.comment-replies').length;
                const newDepth = currentDepth + 1;

                // Render the new reply HTML
                const newReplyHtml = renderReplyItem(r.data, newDepth);
                const $newReply = $(newReplyHtml).hide();

                // Append and slide in with highlight effect
                $repliesContainer.append($newReply);
                $newReply.addClass('new-highlight').slideDown(300, function() {
                    setTimeout(() => {
                        $(this).removeClass('new-highlight');
                    }, 1500);
                });

                // Update unanswered counts locally
                loadUnansweredCounts();
            } else {
                toastr.error(r.message || 'Failed to send reply');
            }
        },
        error: function() {
            toastr.error('Failed to send reply');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-send me-1"></i>Send');
        }
    });
}

// ============ CONTENT DETAILS MODAL ============
function showContentDetails(contentId, contentPath) {
    if (!contentId) {
        toastr.info('This is a general course comment');
        return;
    }

    Swal.fire({
        title: '<i class="bx bx-file-blank text-primary"></i> Content Location',
        html: `
            <div class="text-start">
                <p class="mb-2"><strong>Path:</strong></p>
                <div class="bg-light p-3 rounded">
                    <code class="text-dark">${contentPath}</code>
                </div>
                <hr>
                <p class="mb-0 text-secondary small">
                    <i class="bx bx-info-circle me-1"></i>
                    Click below to go to the course contents page and locate this content.
                </p>
            </div>
        `,
        showCancelButton: true,
        confirmButtonText: '<i class="bx bx-link-external me-1"></i>Go to Contents',
        cancelButtonText: 'Close',
        confirmButtonColor: '#556ee6'
    }).then((result) => {
        if (result.isConfirmed) {
            window.open(`/anisenso-courses-contents?id=${currentCourseId}`, '_blank');
        }
    });
}

// ==================== @MENTION AUTOCOMPLETE ====================
// Store thread participants for autocomplete
let threadParticipantsCache = {};
let currentMentionInput = null;
let currentMentionAutocomplete = null;
let mentionSelectedIndex = -1;

/**
 * Extract all participants from the comments list
 */
function extractThreadParticipants() {
    const participants = new Map();

    // Get all comment authors from the modal
    $('#commentsList .comment-author').each(function() {
        const $author = $(this);
        const fullText = $author.text().trim();
        // Remove Admin badge text if present
        const name = fullText.replace(/Admin$/, '').trim();
        const isAdmin = fullText.includes('Admin');

        if (name && !participants.has(name)) {
            participants.set(name, {
                name: name,
                type: isAdmin ? 'Admin' : 'Student',
                initial: name.charAt(0).toUpperCase()
            });
        }
    });

    return Array.from(participants.values());
}

/**
 * Add a participant to the thread cache dynamically
 */
function addThreadParticipant(commentId, authorName) {
    if (!threadParticipantsCache[currentCourseId]) {
        threadParticipantsCache[currentCourseId] = [];
    }

    // Check if already exists
    const exists = threadParticipantsCache[currentCourseId].some(p => p.name === authorName);
    if (!exists && authorName) {
        threadParticipantsCache[currentCourseId].push({
            name: authorName,
            type: 'Admin',
            initial: authorName.charAt(0).toUpperCase()
        });
    }
}

/**
 * Initialize @mention autocomplete for a textarea
 */
function initMentionAutocomplete($textarea) {
    // Create autocomplete container if not exists
    let $autocomplete = $textarea.siblings('.mention-autocomplete');
    if ($autocomplete.length === 0) {
        $autocomplete = $('<div class="mention-autocomplete"></div>');
        $textarea.parent().css('position', 'relative').append($autocomplete);
    }

    // Handle input events for @mention trigger
    $textarea.on('input.mention', function() {
        const text = $(this).val();
        const cursorPos = this.selectionStart;

        // Find if we're typing after @
        const textBeforeCursor = text.substring(0, cursorPos);
        const atMatch = textBeforeCursor.match(/@([a-zA-Z0-9\s]*)$/);

        if (atMatch) {
            const searchTerm = atMatch[1].toLowerCase();
            showMentionSuggestions($textarea, $autocomplete, searchTerm);
        } else {
            hideMentionAutocomplete($autocomplete);
        }
    });

    // Handle keyboard navigation
    $textarea.on('keydown.mention', function(e) {
        if (!$autocomplete.hasClass('show')) return;

        const $items = $autocomplete.find('.mention-autocomplete-item');

        if (e.key === 'ArrowDown') {
            e.preventDefault();
            mentionSelectedIndex = Math.min(mentionSelectedIndex + 1, $items.length - 1);
            updateMentionSelection($autocomplete);
        } else if (e.key === 'ArrowUp') {
            e.preventDefault();
            mentionSelectedIndex = Math.max(mentionSelectedIndex - 1, 0);
            updateMentionSelection($autocomplete);
        } else if (e.key === 'Enter' && mentionSelectedIndex >= 0) {
            e.preventDefault();
            const $selected = $items.eq(mentionSelectedIndex);
            insertMention($textarea, $selected.data('name'));
            hideMentionAutocomplete($autocomplete);
        } else if (e.key === 'Escape') {
            e.preventDefault();
            hideMentionAutocomplete($autocomplete);
        }
    });

    // Handle click outside to close
    $(document).on('click.mention', function(e) {
        if (!$(e.target).closest('.mention-autocomplete, textarea').length) {
            hideMentionAutocomplete($autocomplete);
        }
    });
}

/**
 * Show mention suggestions based on search term
 */
function showMentionSuggestions($textarea, $autocomplete, searchTerm) {
    // Get participants from current thread
    const participants = extractThreadParticipants();

    // Filter by search term
    const filtered = participants.filter(p =>
        p.name.toLowerCase().includes(searchTerm)
    );

    if (filtered.length === 0) {
        $autocomplete.html('<div class="mention-autocomplete-empty">No participants found</div>').addClass('show');
        return;
    }

    // Build HTML
    let html = '<div class="mention-autocomplete-header">Thread Participants</div>';
    filtered.forEach((participant, index) => {
        const avatarClass = participant.type === 'Admin' ? 'admin' : 'student';
        html += `
            <div class="mention-autocomplete-item" data-name="${escapeHtml(participant.name)}" data-index="${index}">
                <div class="avatar ${avatarClass}">${participant.initial}</div>
                <span class="name">${escapeHtml(participant.name)}</span>
                <span class="type">${participant.type}</span>
            </div>
        `;
    });

    $autocomplete.html(html).addClass('show');
    mentionSelectedIndex = -1;

    // Handle click on item
    $autocomplete.find('.mention-autocomplete-item').on('click', function() {
        insertMention($textarea, $(this).data('name'));
        hideMentionAutocomplete($autocomplete);
    });

    // Handle hover
    $autocomplete.find('.mention-autocomplete-item').on('mouseenter', function() {
        mentionSelectedIndex = $(this).data('index');
        updateMentionSelection($autocomplete);
    });
}

/**
 * Update visual selection in autocomplete
 */
function updateMentionSelection($autocomplete) {
    $autocomplete.find('.mention-autocomplete-item').removeClass('active');
    if (mentionSelectedIndex >= 0) {
        $autocomplete.find('.mention-autocomplete-item').eq(mentionSelectedIndex).addClass('active');
    }
}

/**
 * Insert mention at cursor position
 */
function insertMention($textarea, name) {
    const text = $textarea.val();
    const cursorPos = $textarea[0].selectionStart;

    // Find the @ symbol position
    const textBeforeCursor = text.substring(0, cursorPos);
    const atIndex = textBeforeCursor.lastIndexOf('@');

    if (atIndex !== -1) {
        // Replace @partial with @[Full Name]
        const before = text.substring(0, atIndex);
        const after = text.substring(cursorPos);
        const mention = `@[${name}] `;

        $textarea.val(before + mention + after);

        // Set cursor position after mention
        const newPos = atIndex + mention.length;
        $textarea[0].setSelectionRange(newPos, newPos);
        $textarea.focus();
    }
}

/**
 * Hide mention autocomplete
 */
function hideMentionAutocomplete($autocomplete) {
    $autocomplete.removeClass('show');
    mentionSelectedIndex = -1;
}

// Initialize mention autocomplete when inline reply forms are shown
$(document).on('focus', '.inline-reply-form textarea, #replyText', function() {
    initMentionAutocomplete($(this));
});

// Also handle dynamically created textareas
$(document).on('click', '.inline-reply-form textarea', function() {
    if (!$(this).data('mention-initialized')) {
        initMentionAutocomplete($(this));
        $(this).data('mention-initialized', true);
    }
});

// ============================================
// STUDENTS MODULE
// ============================================

let currentStudentsCourseId = null;

// Open Students Modal
$('.students-btn').on('click', function() {
    currentStudentsCourseId = $(this).data('course-id');
    $('#studentsCourseId').val(currentStudentsCourseId);
    $('#studentsCourseName').text($(this).data('course-name'));
    $('#studentsSearch').val('');
    $('#studentsStatusFilter').val('');
    $('#studentsExpirationFrom').val('');
    $('#studentsExpirationTo').val('');
    $('#studentsModal').modal('show');
    loadStudents();
});

// Load Students
function loadStudents() {
    const courseId = $('#studentsCourseId').val();
    const search = $('#studentsSearch').val();
    const status = $('#studentsStatusFilter').val();
    const expirationFrom = $('#studentsExpirationFrom').val();
    const expirationTo = $('#studentsExpirationTo').val();

    $('#studentsList').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-success"></div>
            <p class="mt-2 text-secondary">Loading students...</p>
        </div>
    `);

    $.ajax({
        url: `/anisenso-courses/${courseId}/students`,
        type: 'GET',
        data: {
            search: search,
            status: status,
            expirationFrom: expirationFrom,
            expirationTo: expirationTo
        },
        success: function(response) {
            if (response.success) {
                renderStudentsList(response.students, response.totalTopics);
                $('#studentCount').text(response.totalStudents + ' students');
            }
        },
        error: function() {
            $('#studentsList').html('<div class="alert alert-danger">Failed to load students</div>');
        }
    });
}

// Date filter change handlers
$('#studentsExpirationFrom, #studentsExpirationTo').on('change', function() {
    loadStudents();
});

// Clear filters button
$('#clearStudentsFilters').on('click', function() {
    $('#studentsSearch').val('');
    $('#studentsStatusFilter').val('');
    $('#studentsExpirationFrom').val('');
    $('#studentsExpirationTo').val('');
    loadStudents();
});

// Render Students List
function renderStudentsList(students, totalTopics) {
    if (students.length === 0) {
        $('#studentsList').html(`
            <div class="text-center py-4">
                <i class="bx bx-user-x text-secondary" style="font-size: 3rem;"></i>
                <p class="text-dark mt-2">No students enrolled yet</p>
                <small class="text-secondary">Click "Enroll Student" to add the first student</small>
            </div>
        `);
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead class="table-light">
                    <tr>
                        <th class="text-dark">Student</th>
                        <th class="text-dark">Contact</th>
                        <th class="text-dark text-center" style="min-width: 200px;">Progress</th>
                        <th class="text-dark">Expiration</th>
                        <th class="text-dark text-center">Actions</th>
                    </tr>
                </thead>
                <tbody>
    `;

    students.forEach(function(student) {
        const progressClass = student.progressPercent >= 100 ? 'bg-success' :
                             student.progressPercent >= 50 ? 'bg-info' : 'bg-warning';
        const expirationClass = student.isExpired ? 'text-danger' :
                               (student.daysRemaining !== null && student.daysRemaining < 7) ? 'text-warning' : 'text-success';

        html += `
            <tr>
                <td>
                    <strong class="text-dark">${escapeHtml(student.fullName)}</strong>
                    <br><small class="text-secondary">Enrolled: ${student.enrollmentDate}</small>
                </td>
                <td>
                    <small class="text-dark">${escapeHtml(student.email)}</small>
                    <br><small class="text-secondary">${escapeHtml(student.phone)}</small>
                </td>
                <td class="text-center">
                    <div class="progress" style="height: 20px; border-radius: 10px;">
                        <div class="progress-bar ${progressClass}" role="progressbar"
                             style="width: ${student.progressPercent}%; border-radius: 10px;"
                             aria-valuenow="${student.progressPercent}" aria-valuemin="0" aria-valuemax="100">
                            <strong>${student.progressPercent}%</strong>
                        </div>
                    </div>
                    <small class="text-secondary mt-1 d-block">${student.completedTopics} of ${totalTopics} topics completed</small>
                </td>
                <td>
                    <span class="${expirationClass}">${student.formattedExpiration}</span>
                </td>
                <td class="text-center">
                    <button class="btn btn-sm btn-outline-primary edit-student-btn"
                            data-id="${student.enrollmentId}"
                            data-name="${escapeHtml(student.fullName)}"
                            data-progress="${student.progressPercent}"
                            title="Edit">
                        <i class="bx bx-edit"></i>
                    </button>
                    <button class="btn btn-sm btn-outline-danger remove-student-btn"
                            data-id="${student.enrollmentId}"
                            data-name="${escapeHtml(student.fullName)}"
                            title="Remove">
                        <i class="bx bx-trash"></i>
                    </button>
                </td>
            </tr>
        `;
    });

    html += '</tbody></table></div>';
    $('#studentsList').html(html);
}

// Edit Student
$(document).on('click', '.edit-student-btn', function() {
    const enrollmentId = $(this).data('id');

    $.ajax({
        url: `/anisenso-courses/enrollments/${enrollmentId}`,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const enrollment = response.enrollment;
                const client = response.client;

                $('#editEnrollmentId').val(enrollment.id);
                $('#editAccessClientId').val(enrollment.accessClientId);
                $('#editStudentName').text(client.fullName);
                $('#editExpirationDate').val(enrollment.expirationDate || '');
                $('#editIsActive').prop('checked', enrollment.isActive);
                $('#editProgressBar').css('width', enrollment.progressPercent + '%').text(enrollment.progressPercent + '%');
                $('#editProgressDetail').text(`${enrollment.completedTopics} of ${enrollment.totalTopics} topics completed`);

                // Clear password fields
                $('#editNewPassword').val('');
                $('#editConfirmPassword').val('');
                $('#passwordError').hide().text('');

                $('#editStudentModal').modal('show');
            }
        },
        error: function() {
            toastr.error('Failed to load student details');
        }
    });
});

// Save Student Changes
$('#saveStudentBtn').on('click', function() {
    const enrollmentId = $('#editEnrollmentId').val();
    const accessClientId = $('#editAccessClientId').val();
    const newPassword = $('#editNewPassword').val();
    const confirmPassword = $('#editConfirmPassword').val();
    const $btn = $(this);

    // Validate password if provided
    if (newPassword || confirmPassword) {
        if (newPassword !== confirmPassword) {
            $('#passwordError').text('Passwords do not match').show();
            return;
        }
        if (newPassword.length < 6) {
            $('#passwordError').text('Password must be at least 6 characters').show();
            return;
        }
    }
    $('#passwordError').hide();

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

    $.ajax({
        url: `/anisenso-courses/enrollments/${enrollmentId}`,
        type: 'PUT',
        data: {
            _token: '{{ csrf_token() }}',
            expirationDate: $('#editExpirationDate').val() || null,
            isActive: $('#editIsActive').is(':checked') ? 1 : 0,
            accessClientId: accessClientId,
            newPassword: newPassword || null
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#editStudentModal').modal('hide');
                loadStudents();
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to save changes');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Changes');
        }
    });
});

// Send Password Reset Email
$('#sendPasswordEmailBtn').on('click', function() {
    const accessClientId = $('#editAccessClientId').val();
    const studentName = $('#editStudentName').text();
    const $btn = $(this);

    if (!accessClientId) {
        toastr.error('No student selected');
        return;
    }

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Sending...');

    $.ajax({
        url: `/anisenso-courses/students/${accessClientId}/send-password-reset`,
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                toastr.success(`Password reset email queued for ${studentName}`);
            } else {
                toastr.warning(response.message || 'Email feature coming soon');
            }
        },
        error: function(xhr) {
            toastr.info('Password reset email feature coming soon');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-envelope me-1"></i>Send Password Reset Email');
        }
    });
});

// Remove Student - Show Confirmation Modal
$(document).on('click', '.remove-student-btn', function() {
    const enrollmentId = $(this).data('id');
    const studentName = $(this).data('name');

    $('#removeEnrollmentId').val(enrollmentId);
    $('#removeStudentName').text(studentName);
    $('#removeStudentModal').modal('show');
});

// Confirm Remove Student
$('#confirmRemoveStudentBtn').on('click', function() {
    const enrollmentId = $('#removeEnrollmentId').val();
    const $btn = $(this);

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Removing...');

    $.ajax({
        url: `/anisenso-courses/enrollments/${enrollmentId}`,
        type: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                $('#removeStudentModal').modal('hide');
                toastr.success(response.message);
                loadStudents();
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to remove student');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Remove Student');
        }
    });
});

// Reset Progress - Show Confirmation Modal
$('#resetProgressBtn').on('click', function() {
    const studentName = $('#editStudentName').text();
    $('#resetProgressStudentName').text(studentName);
    $('#resetProgressModal').modal('show');
});

// Confirm Reset Progress
$('#confirmResetProgressBtn').on('click', function() {
    const enrollmentId = $('#editEnrollmentId').val();
    const $btn = $(this);

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Resetting...');

    $.ajax({
        url: `/anisenso-courses/enrollments/${enrollmentId}/reset-progress`,
        type: 'POST',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                $('#resetProgressModal').modal('hide');
                toastr.success(response.message);
                $('#editProgressBar').css('width', '0%').text('0%');
                $('#editProgressDetail').text('0 of ' + $('#editProgressDetail').text().split(' of ')[1]);
                loadStudents();
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to reset progress');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-reset me-1"></i>Reset Progress');
        }
    });
});

// Search filter with debounce
let studentsSearchTimeout;
$('#studentsSearch').on('keyup', function() {
    clearTimeout(studentsSearchTimeout);
    studentsSearchTimeout = setTimeout(loadStudents, 300);
});
$('#studentsStatusFilter').on('change', loadStudents);

// Add Student Button - Open Enroll Modal
$('#addStudentBtn').on('click', function() {
    $('#enrollSearchStudent').val('');
    $('#selectedClientId').val('');
    $('#selectedStudentInfo').addClass('d-none');
    $('#enrollLifetimeAccess').prop('checked', true);
    $('#enrollExpirationDate').val('').prop('disabled', true);
    $('#enrollExpirationHint').text('Lifetime access - no expiration');
    $('#confirmEnrollBtn').prop('disabled', true);
    $('#enrollStudentModal').modal('show');
    loadAvailableLogins(1, '');
});

// Toggle Lifetime Access checkbox
$('#enrollLifetimeAccess').on('change', function() {
    if ($(this).is(':checked')) {
        $('#enrollExpirationDate').val('').prop('disabled', true);
        $('#enrollExpirationHint').text('Lifetime access - no expiration');
    } else {
        $('#enrollExpirationDate').prop('disabled', false);
        $('#enrollExpirationHint').text('Select an expiration date');
    }
});

// Load available logins for enrollment (store-specific logins)
let enrollCurrentPage = 1;
let enrollSearchTerm = '';

function loadAvailableLogins(page = 1, search = '') {
    enrollCurrentPage = page;
    enrollSearchTerm = search;

    $('#enrollSearchResults').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-success"></div>
            <p class="mt-2 text-secondary">Loading logins...</p>
        </div>
    `);

    $.ajax({
        url: `/anisenso-courses/${currentStudentsCourseId}/students/search`,
        type: 'GET',
        data: { search: search, page: page, per_page: 10 },
        success: function(response) {
            if (response.success && response.data && response.data.length > 0) {
                let html = `
                    <table class="table table-hover table-sm mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="text-dark">Name</th>
                                <th class="text-dark">Contact</th>
                                <th class="text-dark">Store</th>
                                <th class="text-center">Select</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                response.data.forEach(function(client) {
                    const selectedId = $('#selectedClientId').val();
                    const isSelected = selectedId == client.id ? 'table-success' : '';
                    html += `
                        <tr class="${isSelected}">
                            <td>
                                <strong class="text-dark">${escapeHtml(client.fullName)}</strong>
                            </td>
                            <td>
                                <small class="text-dark">${escapeHtml(client.email || 'N/A')}</small>
                                <br><small class="text-secondary">${escapeHtml(client.phone || 'N/A')}</small>
                            </td>
                            <td><span class="badge bg-info text-white">${escapeHtml(client.store || 'N/A')}</span></td>
                            <td class="text-center">
                                <button type="button" class="btn btn-sm ${selectedId == client.id ? 'btn-success' : 'btn-outline-success'} select-client-btn"
                                        data-id="${client.id}"
                                        data-name="${escapeHtml(client.fullName)}"
                                        data-email="${escapeHtml(client.email || '')}">
                                    <i class="bx ${selectedId == client.id ? 'bx-check' : 'bx-plus'}"></i>
                                </button>
                            </td>
                        </tr>
                    `;
                });
                html += '</tbody></table>';
                $('#enrollSearchResults').html(html);

                // Render pagination
                renderEnrollPagination(response.current_page, response.last_page, response.total);
            } else {
                $('#enrollSearchResults').html(`
                    <div class="text-center py-4">
                        <i class="bx bx-user-x text-secondary" style="font-size: 2rem;"></i>
                        <p class="text-dark mt-2">No available logins found</p>
                        <small class="text-secondary">All logins may already be enrolled in this course</small>
                    </div>
                `);
                $('#enrollPagination').html('');
            }
        },
        error: function() {
            $('#enrollSearchResults').html('<div class="alert alert-danger">Failed to load logins</div>');
            $('#enrollPagination').html('');
        }
    });
}

// Render enrollment pagination
function renderEnrollPagination(currentPage, totalPages, total) {
    if (totalPages <= 1) {
        $('#enrollPagination').html(`<small class="text-secondary">${total} login(s) available</small>`);
        return;
    }

    let html = `<small class="text-secondary">Page ${currentPage} of ${totalPages} (${total} total)</small>`;
    html += '<div class="btn-group btn-group-sm">';

    if (currentPage > 1) {
        html += `<button type="button" class="btn btn-outline-secondary enroll-page-btn" data-page="${currentPage - 1}"><i class="bx bx-chevron-left"></i></button>`;
    }
    if (currentPage < totalPages) {
        html += `<button type="button" class="btn btn-outline-secondary enroll-page-btn" data-page="${currentPage + 1}"><i class="bx bx-chevron-right"></i></button>`;
    }

    html += '</div>';
    $('#enrollPagination').html(html);
}

// Pagination click
$(document).on('click', '.enroll-page-btn', function() {
    const page = $(this).data('page');
    loadAvailableLogins(page, enrollSearchTerm);
});

// Search Available Students for Enrollment
let enrollSearchTimeout;
$('#enrollSearchStudent').on('keyup', function() {
    clearTimeout(enrollSearchTimeout);
    const search = $(this).val();

    enrollSearchTimeout = setTimeout(function() {
        loadAvailableLogins(1, search);
    }, 300);
});

// Select Client for Enrollment
$(document).on('click', '.select-client-btn', function() {
    const clientId = $(this).data('id');
    const clientName = $(this).data('name');

    $('#selectedClientId').val(clientId);
    $('#selectedStudentName').text(clientName);
    $('#selectedStudentInfo').removeClass('d-none');
    $('#confirmEnrollBtn').prop('disabled', false);

    // Refresh table to show selection
    loadAvailableLogins(enrollCurrentPage, enrollSearchTerm);
});

// Clear selected student
$('#clearSelectedStudent').on('click', function() {
    $('#selectedClientId').val('');
    $('#selectedStudentInfo').addClass('d-none');
    $('#confirmEnrollBtn').prop('disabled', true);
    loadAvailableLogins(enrollCurrentPage, enrollSearchTerm);
});

// Confirm Enrollment
$('#confirmEnrollBtn').on('click', function() {
    const clientId = $('#selectedClientId').val();
    const isLifetime = $('#enrollLifetimeAccess').is(':checked');
    const expirationDate = isLifetime ? null : $('#enrollExpirationDate').val();
    const $btn = $(this);

    if (!clientId) {
        toastr.warning('Please select a student first');
        return;
    }

    // Validate expiration date if not lifetime
    if (!isLifetime && !expirationDate) {
        toastr.warning('Please select an expiration date or enable lifetime access');
        return;
    }

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Enrolling...');

    $.ajax({
        url: '/anisenso-courses/students/enroll',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            courseId: currentStudentsCourseId,
            accessClientId: clientId,
            expirationDate: expirationDate
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
                $('#enrollStudentModal').modal('hide');
                loadStudents();
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to enroll student');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-user-plus me-1"></i>Enroll Student');
        }
    });
});

// ============================================
// AUDIT TRAIL MODULE
// ============================================

let currentAuditCourseId = null;

// Open Audit Modal
$('.audit-btn').on('click', function() {
    currentAuditCourseId = $(this).data('course-id');
    $('#auditCourseId').val(currentAuditCourseId);
    $('#auditCourseName').text($(this).data('course-name'));
    $('#auditDateFrom').val('');
    $('#auditDateTo').val('');
    $('#auditEntityType').val('');
    $('#auditUserFilter').val('');
    $('#auditModal').modal('show');
    loadAuditLogs();
    loadAuditUsers(currentAuditCourseId);
});

// Load Audit Logs
function loadAuditLogs(page = 1) {
    const courseId = $('#auditCourseId').val();

    $('#auditLogsList').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-secondary"></div>
            <p class="mt-2 text-secondary">Loading audit trail...</p>
        </div>
    `);

    $.ajax({
        url: `/anisenso-courses/${courseId}/audit`,
        type: 'GET',
        data: {
            dateFrom: $('#auditDateFrom').val(),
            dateTo: $('#auditDateTo').val(),
            entityType: $('#auditEntityType').val(),
            userId: $('#auditUserFilter').val(),
            page: page
        },
        success: function(response) {
            if (response.success) {
                renderAuditLogs(response.logs, response.pagination);
            }
        },
        error: function() {
            $('#auditLogsList').html('<div class="alert alert-danger">Failed to load audit logs</div>');
        }
    });
}

// Render Audit Logs
function renderAuditLogs(logs, pagination) {
    if (logs.length === 0) {
        $('#auditLogsList').html(`
            <div class="text-center py-4">
                <i class="bx bx-history text-secondary" style="font-size: 3rem;"></i>
                <p class="text-dark mt-2">No audit logs found</p>
                <small class="text-secondary">Activity will appear here as changes are made to the course</small>
            </div>
        `);
        $('#auditPagination').empty();
        return;
    }

    let html = `
        <div class="table-responsive">
            <table class="table table-sm table-hover">
                <thead class="table-light">
                    <tr>
                        <th class="text-dark" style="width: 160px;">Date/Time</th>
                        <th class="text-dark" style="width: 120px;">User</th>
                        <th class="text-dark" style="width: 150px;">Action</th>
                        <th class="text-dark">Details</th>
                    </tr>
                </thead>
                <tbody>
    `;

    logs.forEach(function(log) {
        const entityBadge = getEntityBadge(log.entityType);

        html += `
            <tr>
                <td class="text-dark" style="white-space: nowrap;">
                    <small>${log.createdAt}</small>
                    <br><small class="text-secondary">${log.ipAddress || ''}</small>
                </td>
                <td class="text-dark"><small>${escapeHtml(log.userName)}</small></td>
                <td>
                    ${entityBadge}
                    <br><small class="text-dark">${log.actionTypeLabel}</small>
                </td>
                <td>
                    ${log.entityName ? `<strong class="text-dark">${escapeHtml(log.entityName)}</strong><br>` : ''}
                    ${log.fieldChanged ? `<small class="text-secondary">Field: ${escapeHtml(log.fieldChanged)}</small><br>` : ''}
                    ${log.previousValue ? `<small class="text-danger"><i class="bx bx-minus"></i> ${escapeHtml(log.previousValue)}</small><br>` : ''}
                    ${log.newValue ? `<small class="text-success"><i class="bx bx-plus"></i> ${escapeHtml(log.newValue)}</small><br>` : ''}
                    ${log.description ? `<small class="text-secondary fst-italic">${escapeHtml(log.description)}</small>` : ''}
                </td>
            </tr>
        `;
    });

    html += '</tbody></table></div>';
    $('#auditLogsList').html(html);

    // Render pagination
    renderAuditPagination(pagination);
}

function getEntityBadge(entityType) {
    const badges = {
        'course': '<span class="badge bg-primary">Course</span>',
        'chapter': '<span class="badge bg-info text-white">Chapter</span>',
        'topic': '<span class="badge bg-success">Topic</span>',
        'content': '<span class="badge bg-warning text-dark">Content</span>',
        'questionnaire': '<span class="badge bg-purple text-white">Quiz</span>',
        'question': '<span class="badge bg-pink text-white">Question</span>',
        'student': '<span class="badge bg-secondary">Student</span>',
        'enrollment': '<span class="badge bg-secondary">Enrollment</span>',
        'comment': '<span class="badge bg-dark">Comment</span>'
    };
    return badges[entityType] || `<span class="badge bg-light text-dark">${entityType}</span>`;
}

function renderAuditPagination(pagination) {
    if (!pagination || pagination.lastPage <= 1) {
        $('#auditPagination').empty();
        return;
    }

    let html = `
        <div>
            <small class="text-secondary">
                Showing ${pagination.from || 0} to ${pagination.to || 0} of ${pagination.total} entries
            </small>
        </div>
        <nav>
            <ul class="pagination pagination-sm mb-0">
    `;

    // Previous
    if (pagination.currentPage > 1) {
        html += `<li class="page-item"><a class="page-link audit-page-link" href="#" data-page="${pagination.currentPage - 1}">&laquo;</a></li>`;
    }

    // Page numbers
    for (let i = 1; i <= pagination.lastPage; i++) {
        if (i === 1 || i === pagination.lastPage || (i >= pagination.currentPage - 2 && i <= pagination.currentPage + 2)) {
            html += `
                <li class="page-item ${i === pagination.currentPage ? 'active' : ''}">
                    <a class="page-link audit-page-link" href="#" data-page="${i}">${i}</a>
                </li>
            `;
        } else if (i === pagination.currentPage - 3 || i === pagination.currentPage + 3) {
            html += `<li class="page-item disabled"><span class="page-link">...</span></li>`;
        }
    }

    // Next
    if (pagination.currentPage < pagination.lastPage) {
        html += `<li class="page-item"><a class="page-link audit-page-link" href="#" data-page="${pagination.currentPage + 1}">&raquo;</a></li>`;
    }

    html += '</ul></nav>';
    $('#auditPagination').html(html);
}

$(document).on('click', '.audit-page-link', function(e) {
    e.preventDefault();
    loadAuditLogs($(this).data('page'));
});

// Load users for filter
function loadAuditUsers(courseId) {
    $.ajax({
        url: `/anisenso-courses/${courseId}/audit/users`,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                let options = '<option value="">All Users</option>';
                response.users.forEach(function(user) {
                    options += `<option value="${user.id}">${escapeHtml(user.name)}</option>`;
                });
                $('#auditUserFilter').html(options);
            }
        }
    });
}

// Apply filters
$('#applyAuditFilters').on('click', function() {
    loadAuditLogs();
});

// Clear filters
$('#clearAuditFilters').on('click', function() {
    $('#auditDateFrom').val('');
    $('#auditDateTo').val('');
    $('#auditEntityType').val('');
    $('#auditUserFilter').val('');
    loadAuditLogs();
});

// ==========================================
// REVIEWS FUNCTIONALITY
// ==========================================

let currentReviewsCourseId = null;
let currentReviewsPage = 1;
let reviewToDelete = null;
let replyToDelete = null;
let reviewsData = {}; // Store reviews data for safe access

// Open Reviews Modal
$('.reviews-btn').on('click', function() {
    currentReviewsCourseId = $(this).data('course-id');
    $('#reviewsCourseId').val(currentReviewsCourseId);
    $('#reviewsCourseName').text($(this).data('course-name'));

    // Reset filters
    $('#reviewRatingFilter').val('');
    $('#reviewApprovalFilter').val('');
    currentReviewsPage = 1;

    loadReviews();
    $('#reviewsModal').modal('show');
});

// Load Reviews
function loadReviews(page = 1) {
    currentReviewsPage = page;
    const courseId = $('#reviewsCourseId').val();
    const rating = $('#reviewRatingFilter').val();
    const approved = $('#reviewApprovalFilter').val();

    $('#reviewsList').html(`
        <div class="text-center py-4">
            <div class="spinner-border text-warning"></div>
            <p class="mt-2 text-secondary">Loading reviews...</p>
        </div>
    `);

    $.ajax({
        url: `/anisenso-courses/${courseId}/reviews`,
        type: 'GET',
        data: { page, rating, approved, perPage: 10 },
        success: function(response) {
            if (response.success) {
                updateReviewStats(response.stats);
                renderReviewsList(response.reviews);
                renderReviewsPagination(response.pagination);
            } else {
                $('#reviewsList').html(`
                    <div class="alert alert-warning">
                        <i class="bx bx-error me-2"></i>${response.message || 'Failed to load reviews'}
                    </div>
                `);
            }
        },
        error: function(xhr) {
            $('#reviewsList').html(`
                <div class="alert alert-danger">
                    <i class="bx bx-error me-2"></i>Error loading reviews
                </div>
            `);
        }
    });
}

// Update Review Stats
function updateReviewStats(stats) {
    const total = stats.totalReviews || 0;
    const avg = stats.averageRating || 0;

    $('#avgRatingDisplay').text(avg.toFixed(1));
    $('#totalReviewsCount').text(total);

    // Generate stars for average
    let starsHtml = '';
    for (let i = 1; i <= 5; i++) {
        if (i <= Math.floor(avg)) {
            starsHtml += '<i class="bx bxs-star text-warning"></i>';
        } else if (i - 0.5 <= avg) {
            starsHtml += '<i class="bx bxs-star-half text-warning"></i>';
        } else {
            starsHtml += '<i class="bx bx-star text-muted"></i>';
        }
    }
    $('#avgStarsDisplay').html(starsHtml);

    // Update rating bars
    if (total > 0) {
        $('#fiveStarBar').css('width', (stats.fiveStar / total * 100) + '%');
        $('#fourStarBar').css('width', (stats.fourStar / total * 100) + '%');
        $('#threeStarBar').css('width', (stats.threeStar / total * 100) + '%');
        $('#twoStarBar').css('width', (stats.twoStar / total * 100) + '%');
        $('#oneStarBar').css('width', (stats.oneStar / total * 100) + '%');
    } else {
        $('#fiveStarBar, #fourStarBar, #threeStarBar, #twoStarBar, #oneStarBar').css('width', '0%');
    }

    $('#fiveStarCount').text(stats.fiveStar || 0);
    $('#fourStarCount').text(stats.fourStar || 0);
    $('#threeStarCount').text(stats.threeStar || 0);
    $('#twoStarCount').text(stats.twoStar || 0);
    $('#oneStarCount').text(stats.oneStar || 0);
}

// Render Reviews List
function renderReviewsList(reviews) {
    if (!reviews || reviews.length === 0) {
        reviewsData = {};
        $('#reviewsList').html(`
            <div class="text-center py-5">
                <i class="bx bx-star text-secondary" style="font-size: 3rem;"></i>
                <p class="mt-2 text-dark mb-1">No reviews yet</p>
                <small class="text-secondary">Reviews from enrolled students will appear here</small>
            </div>
        `);
        return;
    }

    // Store reviews data for safe access
    reviewsData = {};
    reviews.forEach(function(review) {
        reviewsData[review.id] = {
            studentName: review.studentName,
            starsHtml: review.starsHtml,
            reviewText: review.reviewText || ''
        };
        // Also store replies
        if (review.replies) {
            review.replies.forEach(function(reply) {
                reviewsData['reply_' + reply.id] = {
                    replyText: reply.replyText || ''
                };
            });
        }
    });

    let html = '';
    reviews.forEach(function(review) {
        const approvedBadge = review.isApproved
            ? '<span class="badge bg-success ms-2">Approved</span>'
            : '<span class="badge bg-warning text-dark ms-2">Pending</span>';
        const featuredBadge = review.isFeatured
            ? '<span class="badge bg-info text-white ms-1">Featured</span>'
            : '';

        // Replies HTML
        let repliesHtml = '';
        if (review.replies && review.replies.length > 0) {
            repliesHtml = '<div class="mt-3 ps-4 border-start border-3 border-success">';
            review.replies.forEach(function(reply) {
                repliesHtml += `
                    <div class="mb-2 bg-light p-2 rounded">
                        <div class="d-flex justify-content-between align-items-start">
                            <div>
                                <strong class="text-dark"><i class="bx bx-user-circle me-1"></i>${escapeHtml(reply.userName)}</strong>
                                <small class="text-secondary ms-2">${reply.timeAgo}</small>
                            </div>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-reply-btn"
                                    data-reply-id="${reply.id}">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                        <div class="mt-1 text-dark">${reply.parsedReplyText}</div>
                    </div>
                `;
            });
            repliesHtml += '</div>';
        }

        html += `
            <div class="card mb-3 border">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-start">
                        <div>
                            <div class="mb-1">
                                ${review.starsHtml}
                                ${approvedBadge}
                                ${featuredBadge}
                            </div>
                            <h6 class="text-dark mb-1">${escapeHtml(review.studentName)}</h6>
                            <small class="text-secondary">${review.studentEmail} • ${review.timeAgo}</small>
                        </div>
                        <div class="btn-group">
                            <button type="button" class="btn btn-sm ${review.isApproved ? 'btn-success' : 'btn-outline-success'} toggle-approval-btn"
                                    data-review-id="${review.id}"
                                    title="${review.isApproved ? 'Click to unapprove' : 'Click to approve'}">
                                <i class="bx ${review.isApproved ? 'bx-check-circle' : 'bx-circle'}"></i>
                            </button>
                            <button type="button" class="btn btn-sm ${review.isFeatured ? 'btn-info' : 'btn-outline-info'} toggle-featured-btn"
                                    data-review-id="${review.id}"
                                    title="${review.isFeatured ? 'Remove from featured' : 'Mark as featured'}">
                                <i class="bx bx-star"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-success reply-review-btn"
                                    data-review-id="${review.id}"
                                    title="Reply">
                                <i class="bx bx-reply"></i>
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger delete-review-btn"
                                    data-review-id="${review.id}"
                                    title="Delete">
                                <i class="bx bx-trash"></i>
                            </button>
                        </div>
                    </div>

                    ${review.reviewTitle ? `<h6 class="mt-3 mb-2 text-dark">${escapeHtml(review.reviewTitle)}</h6>` : ''}
                    <p class="mt-2 mb-0 text-dark">${escapeHtml(review.reviewText || '')}</p>

                    ${repliesHtml}
                </div>
            </div>
        `;
    });

    $('#reviewsList').html(html);
}

// Render Reviews Pagination
function renderReviewsPagination(pagination) {
    if (!pagination || pagination.lastPage <= 1) {
        $('#reviewsPagination').html('');
        return;
    }

    let html = `
        <small class="text-secondary">
            Showing ${pagination.from || 0} to ${pagination.to || 0} of ${pagination.total || 0} reviews
        </small>
        <nav>
            <ul class="pagination pagination-sm mb-0">
    `;

    // Previous button
    html += `
        <li class="page-item ${pagination.currentPage === 1 ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadReviews(${pagination.currentPage - 1}); return false;">
                <i class="bx bx-chevron-left"></i>
            </a>
        </li>
    `;

    // Page numbers
    for (let i = 1; i <= pagination.lastPage; i++) {
        if (
            i === 1 ||
            i === pagination.lastPage ||
            (i >= pagination.currentPage - 1 && i <= pagination.currentPage + 1)
        ) {
            html += `
                <li class="page-item ${i === pagination.currentPage ? 'active' : ''}">
                    <a class="page-link" href="#" onclick="loadReviews(${i}); return false;">${i}</a>
                </li>
            `;
        } else if (
            i === pagination.currentPage - 2 ||
            i === pagination.currentPage + 2
        ) {
            html += '<li class="page-item disabled"><span class="page-link">...</span></li>';
        }
    }

    // Next button
    html += `
        <li class="page-item ${pagination.currentPage === pagination.lastPage ? 'disabled' : ''}">
            <a class="page-link" href="#" onclick="loadReviews(${pagination.currentPage + 1}); return false;">
                <i class="bx bx-chevron-right"></i>
            </a>
        </li>
    `;

    html += '</ul></nav>';
    $('#reviewsPagination').html(html);
}

// Apply review filters
$('#applyReviewFilters').on('click', function() {
    loadReviews(1);
});

// Clear review filters
$('#clearReviewFilters').on('click', function() {
    $('#reviewRatingFilter').val('');
    $('#reviewApprovalFilter').val('');
    loadReviews(1);
});

// Toggle Approval Status
$(document).on('click', '.toggle-approval-btn', function() {
    const $btn = $(this);
    const reviewId = $btn.data('review-id');

    $btn.prop('disabled', true);

    $.ajax({
        url: `/anisenso-courses/reviews/${reviewId}/approval`,
        type: 'PUT',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message, 'Success!');
                loadReviews(currentReviewsPage);
            } else {
                toastr.error(response.message || 'Failed to update', 'Error!');
            }
        },
        error: function(xhr) {
            toastr.error('Error updating review', 'Error!');
        },
        complete: function() {
            $btn.prop('disabled', false);
        }
    });
});

// Toggle Featured Status
$(document).on('click', '.toggle-featured-btn', function() {
    const $btn = $(this);
    const reviewId = $btn.data('review-id');

    $btn.prop('disabled', true);

    $.ajax({
        url: `/anisenso-courses/reviews/${reviewId}/featured`,
        type: 'PUT',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message, 'Success!');
                loadReviews(currentReviewsPage);
            } else {
                toastr.error(response.message || 'Failed to update', 'Error!');
            }
        },
        error: function(xhr) {
            toastr.error('Error updating review', 'Error!');
        },
        complete: function() {
            $btn.prop('disabled', false);
        }
    });
});

// Open Reply Modal
$(document).on('click', '.reply-review-btn', function() {
    const reviewId = $(this).data('review-id');
    $('#replyReviewId').val(reviewId);
    $('#reviewReplyText').val('');
    $('#reviewReplyModal').modal('show');
});

// Emoji picker for reply
$(document).on('click', '.emoji-btn', function() {
    const emoji = $(this).data('emoji');
    const $textarea = $('#reviewReplyText');
    const currentText = $textarea.val();
    const cursorPos = $textarea[0].selectionStart;
    const textBefore = currentText.substring(0, cursorPos);
    const textAfter = currentText.substring(cursorPos);
    $textarea.val(textBefore + emoji + textAfter);
    $textarea.focus();
    $textarea[0].setSelectionRange(cursorPos + emoji.length, cursorPos + emoji.length);
});

// Submit Reply
$('#submitReviewReply').on('click', function() {
    const $btn = $(this);
    const reviewId = $('#replyReviewId').val();
    const replyText = $('#reviewReplyText').val().trim();

    if (!replyText) {
        toastr.warning('Please enter a reply', 'Warning!');
        return;
    }

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Sending...');

    $.ajax({
        url: `/anisenso-courses/reviews/${reviewId}/reply`,
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            replyText: replyText
        },
        success: function(response) {
            if (response.success) {
                toastr.success('Reply added successfully', 'Success!');
                $('#reviewReplyModal').modal('hide');
                loadReviews(currentReviewsPage);
            } else {
                toastr.error(response.message || 'Failed to add reply', 'Error!');
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error adding reply', 'Error!');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-send me-1"></i>Send Reply');
        }
    });
});

// Open Delete Review Modal
$(document).on('click', '.delete-review-btn', function() {
    const reviewId = $(this).data('review-id');
    const reviewData = reviewsData[reviewId] || {};

    reviewToDelete = {
        id: reviewId,
        student: reviewData.studentName || 'Unknown',
        stars: reviewData.starsHtml || '',
        text: (reviewData.reviewText || '').substring(0, 100)
    };

    $('#deleteReviewStars').html(reviewToDelete.stars);
    $('#deleteReviewStudent').text(reviewToDelete.student);
    $('#deleteReviewText').text(reviewToDelete.text ? reviewToDelete.text + '...' : 'No text');
    $('#deleteReviewModal').modal('show');
});

// Confirm Delete Review
$('#confirmDeleteReview').on('click', function() {
    if (!reviewToDelete) return;

    const $btn = $(this);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

    $.ajax({
        url: `/anisenso-courses/reviews/${reviewToDelete.id}`,
        type: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                toastr.success('Review deleted successfully', 'Success!');
                $('#deleteReviewModal').modal('hide');
                loadReviews(currentReviewsPage);
            } else {
                toastr.error(response.message || 'Failed to delete review', 'Error!');
            }
        },
        error: function(xhr) {
            toastr.error('Error deleting review', 'Error!');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete Review');
            reviewToDelete = null;
        }
    });
});

// Open Delete Reply Modal
$(document).on('click', '.delete-reply-btn', function() {
    const replyId = $(this).data('reply-id');
    const replyData = reviewsData['reply_' + replyId] || {};

    replyToDelete = {
        id: replyId,
        text: (replyData.replyText || '').substring(0, 100)
    };

    $('#deleteReplyText').text(replyToDelete.text ? replyToDelete.text + '...' : 'No text');
    $('#deleteReviewReplyModal').modal('show');
});

// Confirm Delete Reply
$('#confirmDeleteReviewReply').on('click', function() {
    if (!replyToDelete) return;

    const $btn = $(this);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

    $.ajax({
        url: `/anisenso-courses/review-replies/${replyToDelete.id}`,
        type: 'DELETE',
        data: { _token: '{{ csrf_token() }}' },
        success: function(response) {
            if (response.success) {
                toastr.success('Reply deleted successfully', 'Success!');
                $('#deleteReviewReplyModal').modal('hide');
                loadReviews(currentReviewsPage);
            } else {
                toastr.error(response.message || 'Failed to delete reply', 'Error!');
            }
        },
        error: function(xhr) {
            toastr.error('Error deleting reply', 'Error!');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete Reply');
            replyToDelete = null;
        }
    });
});

// ==========================================
// SETTINGS FUNCTIONALITY
// ==========================================

let currentSettingsCourseId = null;

// Open Settings Modal
$('.settings-btn').on('click', function() {
    currentSettingsCourseId = $(this).data('course-id');
    $('#settingsCourseId').val(currentSettingsCourseId);
    $('#settingsCourseName').text($(this).data('course-name'));

    // Reset form to loading state
    $('#currentSettingsSummary').text('Loading...');
    $('input[name="contentAccessMode"]').prop('checked', false);
    $('#quizBlocksNextChapter').prop('checked', false);

    loadCourseSettings();
    $('#settingsModal').modal('show');
});

// Load Course Settings
function loadCourseSettings() {
    const courseId = $('#settingsCourseId').val();

    $.ajax({
        url: `/anisenso-courses/${courseId}/settings`,
        type: 'GET',
        success: function(response) {
            if (response.success) {
                const settings = response.settings;

                // Set content access mode
                if (settings.contentAccessMode === 'open') {
                    $('#accessModeOpen').prop('checked', true);
                } else {
                    $('#accessModeSequential').prop('checked', true);
                }

                // Set quiz blocking
                $('#quizBlocksNextChapter').prop('checked', settings.quizBlocksNextChapter);

                // Update summary
                updateSettingsSummary(settings);
            } else {
                toastr.error(response.message || 'Failed to load settings', 'Error!');
            }
        },
        error: function(xhr) {
            toastr.error('Error loading settings', 'Error!');
            $('#currentSettingsSummary').text('Error loading settings');
        }
    });
}

// Update settings summary display
function updateSettingsSummary(settings) {
    let summary = [];

    if (settings.contentAccessMode === 'open') {
        summary.push('Open Access (all content accessible)');
    } else {
        summary.push('Sequential Access (linear progression)');
    }

    if (settings.quizBlocksNextChapter) {
        summary.push('Quiz pass required for next chapter');
    } else {
        summary.push('Quiz not required');
    }

    $('#currentSettingsSummary').text(summary.join(' | '));
}

// Update summary on form changes
$('input[name="contentAccessMode"], #quizBlocksNextChapter').on('change', function() {
    const accessMode = $('input[name="contentAccessMode"]:checked').val() || 'open';
    const quizBlocks = $('#quizBlocksNextChapter').is(':checked');

    updateSettingsSummary({
        contentAccessMode: accessMode,
        quizBlocksNextChapter: quizBlocks
    });
});

// Save Course Flow Settings
$('#saveCourseFlowSettings').on('click', function() {
    const $btn = $(this);
    const courseId = $('#settingsCourseId').val();

    const contentAccessMode = $('input[name="contentAccessMode"]:checked').val();
    const quizBlocksNextChapter = $('#quizBlocksNextChapter').is(':checked');

    if (!contentAccessMode) {
        toastr.warning('Please select a content access mode', 'Warning!');
        return;
    }

    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Saving...');

    $.ajax({
        url: `/anisenso-courses/${courseId}/settings/course-flow`,
        type: 'PUT',
        data: {
            _token: '{{ csrf_token() }}',
            contentAccessMode: contentAccessMode,
            quizBlocksNextChapter: quizBlocksNextChapter ? 1 : 0
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message, 'Success!');
                updateSettingsSummary(response.settings);
            } else {
                toastr.error(response.message || 'Failed to save settings', 'Error!');
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Error saving settings', 'Error!');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Course Flow Settings');
        }
    });
});
</script>
@endsection
