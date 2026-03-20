@extends('layouts.master')

@section('title') Blog Posts @endsection

@section('css')
<!-- DataTables -->
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.blog-thumbnail {
    width: 80px;
    height: 50px;
    object-fit: cover;
    border-radius: 6px;
}

.blog-thumbnail-placeholder {
    width: 80px;
    height: 50px;
    background: #f0f0f0;
    border-radius: 6px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #aaa;
}

.badge-style {
    border-radius: 20px !important;
    padding: 4px 12px !important;
    font-size: 11px !important;
    font-weight: 500 !important;
    text-transform: uppercase !important;
    letter-spacing: 0.5px !important;
}

.blog-title {
    max-width: 250px;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}

.bg-purple {
    background-color: #6f42c1 !important;
}

.bg-orange {
    background-color: #fd7e14 !important;
}

#toast-container {
    position: fixed !important;
    top: 20px !important;
    right: 20px !important;
    z-index: 9999 !important;
}

.featured-star {
    color: #f1b44c;
    cursor: pointer;
    font-size: 1.2rem;
    transition: all 0.2s ease;
}

.featured-star:hover {
    transform: scale(1.2);
}

.featured-star.active {
    color: #f1b44c;
}

.featured-star.inactive {
    color: #dee2e6;
}
</style>
@endsection

@section('content')

@component('components.breadcrumb')
@slot('li_1') Ani-Senso @endslot
@slot('li_2') Website @endslot
@slot('title') Blog Posts @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <!-- Success/Error Messages -->
                @if(session('success'))
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                @if(session('error'))
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                @endif

                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="card-title mb-1">Blog Posts</h4>
                        <p class="text-secondary mb-0">Manage blog posts for Ani-Senso website</p>
                    </div>
                    <a href="{{ route('anisenso-blogs.create') }}" class="btn btn-primary">
                        <i class="bx bx-plus me-1"></i> Add New Post
                    </a>
                </div>

                <!-- Filters -->
                <form method="GET" action="{{ route('anisenso-blogs') }}" class="mb-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" name="title" placeholder="Search by title..."
                                   value="{{ request('title') }}">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="category">
                                <option value="">All Categories</option>
                                @foreach($categories as $category => $color)
                                    <option value="{{ $category }}" {{ request('category') == $category ? 'selected' : '' }}>
                                        {{ $category }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status">
                                <option value="">All Statuses</option>
                                @foreach($statuses as $key => $label)
                                    <option value="{{ $key }}" {{ request('status') == $key ? 'selected' : '' }}>
                                        {{ $label }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-outline-primary w-100">
                                <i class="bx bx-filter-alt me-1"></i> Filter
                            </button>
                        </div>
                    </div>
                </form>

                <!-- Blogs Table -->
                <div class="table-responsive">
                    <table class="table table-bordered table-hover" id="blogs-table">
                        <thead class="table-light">
                            <tr>
                                <th width="100">Image</th>
                                <th>Title</th>
                                <th>Category</th>
                                <th>Status</th>
                                <th>Featured</th>
                                <th>Views</th>
                                <th>Published</th>
                                <th width="150" class="text-center">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($blogs as $blog)
                                <tr>
                                    <td>
                                        @if($blog->blogFeaturedImage)
                                            <img src="/{{ $blog->blogFeaturedImage }}" alt="{{ $blog->blogTitle }}"
                                                 class="blog-thumbnail">
                                        @else
                                            <div class="blog-thumbnail-placeholder">
                                                <i class="bx bx-image"></i>
                                            </div>
                                        @endif
                                    </td>
                                    <td>
                                        <strong class="text-dark blog-title d-block" title="{{ $blog->blogTitle }}">
                                            {{ $blog->blogTitle }}
                                        </strong>
                                        <small class="text-secondary">/blog/{{ $blog->blogSlug }}</small>
                                    </td>
                                    <td>
                                        <span class="badge badge-style {{ $blog->getCategoryBadgeClass() }}">
                                            {{ $blog->blogCategory }}
                                        </span>
                                    </td>
                                    <td>
                                        <span class="badge badge-style {{ $blog->getStatusBadgeClass() }}">
                                            {{ ucfirst($blog->blogStatus) }}
                                        </span>
                                    </td>
                                    <td class="text-center">
                                        <i class="bx bxs-star featured-star {{ $blog->isFeatured ? 'active' : 'inactive' }}"
                                           data-blog-id="{{ $blog->id }}"
                                           title="{{ $blog->isFeatured ? 'Remove from featured' : 'Mark as featured' }}"></i>
                                    </td>
                                    <td class="text-center">
                                        <span class="text-dark">{{ number_format($blog->viewCount) }}</span>
                                    </td>
                                    <td>
                                        <span class="text-dark">{{ $blog->getFormattedPublishedDate() }}</span>
                                    </td>
                                    <td class="text-center">
                                        <div class="d-flex gap-1 justify-content-center">
                                            <a href="{{ route('anisenso-blogs.edit', ['id' => $blog->id]) }}"
                                               class="btn btn-sm btn-outline-primary" title="Edit">
                                                <i class="bx bx-edit"></i>
                                            </a>
                                            <button type="button" class="btn btn-sm btn-outline-danger delete-btn"
                                                    data-blog-id="{{ $blog->id }}"
                                                    data-blog-title="{{ $blog->blogTitle }}" title="Delete">
                                                <i class="bx bx-trash"></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center py-5">
                                        <i class="bx bx-news display-4 text-secondary"></i>
                                        <p class="text-dark mt-2 mb-1">No blog posts found</p>
                                        <p class="text-secondary mb-3">Create your first blog post to get started</p>
                                        <a href="{{ route('anisenso-blogs.create') }}" class="btn btn-primary btn-sm">
                                            <i class="bx bx-plus me-1"></i> Create Post
                                        </a>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                @if($blogs->hasPages())
                    <div class="d-flex justify-content-end mt-4">
                        {{ $blogs->appends(request()->query())->links() }}
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
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark">Are you sure you want to delete this blog post?</p>
                <p class="text-secondary mb-0"><strong id="deleteBlogTitle"></strong></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">
                    <i class="bx bx-trash me-1"></i> Delete
                </button>
            </div>
        </div>
    </div>
</div>

@endsection

@section('script')
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>

<script>
toastr.options = {
    closeButton: true,
    progressBar: true,
    positionClass: "toast-top-right",
    timeOut: 3000
};

$(document).ready(function() {
    let blogToDelete = null;

    // Toggle featured status
    $('.featured-star').on('click', function() {
        const $star = $(this);
        const blogId = $star.data('blog-id');

        $.ajax({
            url: '/anisenso-blogs/' + blogId + '/toggle-featured',
            type: 'POST',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    if (response.isFeatured) {
                        $star.removeClass('inactive').addClass('active');
                    } else {
                        $star.removeClass('active').addClass('inactive');
                    }
                    toastr.success(response.message, 'Success!');
                }
            },
            error: function() {
                toastr.error('An error occurred.', 'Error!');
            }
        });
    });

    // Delete button click
    $('.delete-btn').on('click', function() {
        blogToDelete = {
            id: $(this).data('blog-id'),
            title: $(this).data('blog-title'),
            row: $(this).closest('tr')
        };
        $('#deleteBlogTitle').text(blogToDelete.title);
        $('#deleteModal').modal('show');
    });

    // Confirm delete
    $('#confirmDeleteBtn').on('click', function() {
        if (!blogToDelete) return;

        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i> Deleting...');

        $.ajax({
            url: '/anisenso-blogs/' + blogToDelete.id,
            type: 'DELETE',
            data: { _token: '{{ csrf_token() }}' },
            success: function(response) {
                if (response.success) {
                    $('#deleteModal').modal('hide');
                    toastr.success(response.message, 'Success!');
                    blogToDelete.row.fadeOut(400, function() { $(this).remove(); });
                }
            },
            error: function() {
                toastr.error('An error occurred while deleting.', 'Error!');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Delete');
                blogToDelete = null;
            }
        });
    });
});
</script>
@endsection
