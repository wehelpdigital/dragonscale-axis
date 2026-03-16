@extends('layouts.master')

@section('title') Website Pages @endsection

@section('css')
<!-- Toastr CSS -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Dragula CSS -->
<link href="{{ URL::asset('build/libs/dragula/dragula.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.page-card {
    background: #fff;
    border-radius: 8px;
    border: 1px solid #e9ecef;
    margin-bottom: 1rem;
    transition: all 0.2s ease;
}

.page-card:hover {
    box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
}

.page-card.gu-mirror {
    opacity: 0.9;
    box-shadow: 0 8px 24px rgba(0, 0, 0, 0.15);
}

.page-card-header {
    padding: 1rem 1.25rem;
    border-bottom: 1px solid #e9ecef;
    display: flex;
    align-items: center;
    justify-content: space-between;
}

.page-card-body {
    padding: 1rem 1.25rem;
}

.page-icon {
    width: 40px;
    height: 40px;
    border-radius: 8px;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 1.25rem;
    margin-right: 1rem;
}

.page-info {
    flex: 1;
}

.page-name {
    font-weight: 600;
    font-size: 1rem;
    color: #495057;
    margin-bottom: 0.25rem;
}

.page-slug {
    font-size: 0.85rem;
    color: #6c757d;
}

.page-meta {
    display: flex;
    gap: 1rem;
    font-size: 0.85rem;
    color: #6c757d;
}

.drag-handle {
    cursor: grab;
    padding: 0.5rem;
    color: #adb5bd;
    transition: color 0.2s ease;
}

.drag-handle:hover {
    color: #495057;
}

.drag-handle:active {
    cursor: grabbing;
}

.empty-state {
    text-align: center;
    padding: 4rem 2rem;
    color: #6c757d;
}

.empty-state i {
    font-size: 4rem;
    margin-bottom: 1.5rem;
    opacity: 0.3;
}

.empty-state h4 {
    color: #495057;
    margin-bottom: 0.5rem;
}

.system-badge {
    font-size: 0.7rem;
    padding: 0.2rem 0.5rem;
    margin-left: 0.5rem;
}

.action-btns {
    display: flex;
    gap: 0.5rem;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('li_2') Website @endslot
@slot('title') Pages @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <div>
                    <h4 class="card-title mb-1">Website Pages</h4>
                    <p class="card-title-desc mb-0">Manage your Ani-Senso website pages. Drag to reorder.</p>
                </div>
                <div>
                    <a href="{{ route('anisenso-website-pages.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Add New Page
                    </a>
                </div>
            </div>
            <div class="card-body">
                <!-- Success/Error Messages -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if($pages->count() > 0)
                    <div id="pages-container">
                        @foreach($pages as $page)
                        <div class="page-card" data-page-id="{{ $page->id }}">
                            <div class="page-card-header">
                                <div class="d-flex align-items-center flex-grow-1">
                                    <div class="drag-handle me-2" title="Drag to reorder">
                                        <i class="bx bx-grid-vertical fs-4"></i>
                                    </div>
                                    <div class="page-icon bg-soft-primary text-primary">
                                        <i class="bx {{ $page->pageIcon ?? 'bx-file' }}"></i>
                                    </div>
                                    <div class="page-info">
                                        <div class="page-name">
                                            {{ $page->pageName }}
                                            @if($page->isSystemPage)
                                                <span class="badge bg-info text-white system-badge">System</span>
                                            @endif
                                        </div>
                                        <div class="page-slug">
                                            <i class="bx bx-link me-1"></i>/{{ $page->pageSlug }}
                                        </div>
                                    </div>
                                </div>
                                <div class="d-flex align-items-center gap-3">
                                    <span class="badge {{ $page->status_badge }}" id="status-badge-{{ $page->id }}">
                                        {{ $page->status_label }}
                                    </span>
                                    <div class="action-btns">
                                        <button type="button" class="btn btn-soft-info btn-sm"
                                                onclick="toggleStatus({{ $page->id }})"
                                                title="{{ $page->pageStatus === 'published' ? 'Unpublish' : 'Publish' }}">
                                            <i class="bx {{ $page->pageStatus === 'published' ? 'bx-hide' : 'bx-show' }}"></i>
                                        </button>
                                        <a href="{{ route('anisenso-website-pages.edit', ['id' => $page->id]) }}"
                                           class="btn btn-soft-primary btn-sm" title="Edit Page">
                                            <i class="bx bx-edit"></i>
                                        </a>
                                        @if(!$page->isSystemPage)
                                        <button type="button" class="btn btn-soft-danger btn-sm"
                                                onclick="deletePage({{ $page->id }}, '{{ $page->pageName }}')"
                                                title="Delete Page">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                        @endif
                                    </div>
                                </div>
                            </div>
                            <div class="page-card-body">
                                <div class="page-meta">
                                    @if($page->metaTitle)
                                    <span><i class="bx bx-heading me-1"></i>{{ Str::limit($page->metaTitle, 40) }}</span>
                                    @endif
                                    <span><i class="bx bx-calendar me-1"></i>Updated: {{ $page->updated_at->format('M d, Y h:i A') }}</span>
                                </div>
                            </div>
                        </div>
                        @endforeach
                    </div>
                @else
                    <div class="empty-state">
                        <i class="bx bx-file-blank"></i>
                        <h4 class="text-dark">No Pages Found</h4>
                        <p class="text-secondary">Start by creating your first website page.</p>
                        <a href="{{ route('anisenso-website-pages.create') }}" class="btn btn-primary mt-3">
                            <i class="bx bx-plus me-1"></i> Create First Page
                        </a>
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
                <p class="text-dark">Are you sure you want to delete the page "<strong id="deletePageName"></strong>"?</p>
                <p class="text-secondary mb-0">This action cannot be undone.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">
                    <i class="bx bx-x me-1"></i>Cancel
                </button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
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
<!-- Dragula JS -->
<script src="{{ URL::asset('build/libs/dragula/dragula.min.js') }}"></script>

<script>
$(document).ready(function() {
    // Configure Toastr
    toastr.options = {
        "closeButton": true,
        "progressBar": true,
        "positionClass": "toast-top-right",
        "timeOut": 3000
    };

    // Initialize Dragula for reordering
    var container = document.getElementById('pages-container');
    if (container) {
        var drake = dragula([container], {
            moves: function(el, container, handle) {
                return handle.classList.contains('drag-handle') ||
                       handle.closest('.drag-handle') !== null;
            }
        });

        drake.on('drop', function() {
            updatePageOrder();
        });
    }
});

function updatePageOrder() {
    var order = [];
    $('#pages-container .page-card').each(function() {
        order.push($(this).data('page-id'));
    });

    $.ajax({
        url: '{{ route("anisenso-website-pages.update-order") }}',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}',
            order: order
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);
            }
        },
        error: function() {
            toastr.error('Failed to update page order');
        }
    });
}

function toggleStatus(pageId) {
    $.ajax({
        url: '/anisenso-website-pages/' + pageId + '/toggle-status',
        type: 'POST',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                toastr.success(response.message);

                // Update badge
                var badge = $('#status-badge-' + pageId);
                badge.removeClass('bg-success bg-warning text-dark');
                badge.addClass(response.statusBadge);
                badge.text(response.statusLabel);

                // Update toggle button icon
                var btn = $('[onclick="toggleStatus(' + pageId + ')"]');
                var icon = btn.find('i');
                if (response.newStatus === 'published') {
                    icon.removeClass('bx-show').addClass('bx-hide');
                    btn.attr('title', 'Unpublish');
                } else {
                    icon.removeClass('bx-hide').addClass('bx-show');
                    btn.attr('title', 'Publish');
                }
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to update status');
        }
    });
}

var pageToDelete = null;

function deletePage(pageId, pageName) {
    pageToDelete = pageId;
    $('#deletePageName').text(pageName);
    $('#deleteModal').modal('show');
}

$('#confirmDeleteBtn').on('click', function() {
    if (!pageToDelete) return;

    var $btn = $(this);
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Deleting...');

    $.ajax({
        url: '/anisenso-website-pages/' + pageToDelete,
        type: 'DELETE',
        data: {
            _token: '{{ csrf_token() }}'
        },
        success: function(response) {
            if (response.success) {
                $('#deleteModal').modal('hide');
                toastr.success(response.message);

                // Remove the page card
                $('[data-page-id="' + pageToDelete + '"]').fadeOut(400, function() {
                    $(this).remove();

                    // Check if empty
                    if ($('#pages-container .page-card').length === 0) {
                        location.reload();
                    }
                });
            }
        },
        error: function(xhr) {
            toastr.error(xhr.responseJSON?.message || 'Failed to delete page');
        },
        complete: function() {
            $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i>Delete');
            pageToDelete = null;
        }
    });
});
</script>
@endsection
