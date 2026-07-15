@extends('layouts.master')

@section('title') Blog @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') Blog @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="card-title mb-0">Blog</h4>
            <a href="{{ route('resort-guru-blog.create') }}" class="btn btn-primary"><i class="bx bx-plus"></i> New Post</a>
        </div>
        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        @endif

        <ul class="nav nav-tabs nav-tabs-custom mb-3" id="blogTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab === 'posts' ? 'active' : '' }}" href="javascript:void(0);" data-tab="posts" role="tab">
                    <i class="bx bx-news me-1"></i>Posts
                    <span class="badge bg-light text-body ms-1">{{ number_format($posts->total()) }}</span>
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab === 'comments' ? 'active' : '' }}" href="javascript:void(0);" data-tab="comments" role="tab">
                    <i class="bx bx-comment-detail me-1"></i>Comments
                    @if($commentStats)
                        <span class="badge {{ $commentStats['pending'] > 0 ? 'bg-warning text-dark' : 'bg-light text-body' }} ms-1">
                            {{ number_format($commentStats['pending'] > 0 ? $commentStats['pending'] : $commentStats['total']) }}{{ $commentStats['pending'] > 0 ? ' pending' : '' }}
                        </span>
                    @endif
                </a>
            </li>
        </ul>

        <div id="blogPanePosts" class="{{ $activeTab === 'posts' ? '' : 'd-none' }}">
            <div class="table-responsive">
                <table class="table">
                    <thead><tr><th>ID</th><th>Title</th><th>Slug</th><th>Status</th><th>Published</th><th>Actions</th></tr></thead>
                    <tbody>
                        @forelse($posts as $p)
                            <tr>
                                <td>{{ $p->id }}</td>
                                <td>{{ $p->title }}</td>
                                <td><code>{{ $p->slug }}</code></td>
                                <td><span class="badge bg-{{ $p->status === 'published' ? 'success' : 'secondary' }}">{{ $p->status }}</span></td>
                                <td>{{ $p->published_at ? \Carbon\Carbon::parse($p->published_at)->format('Y-m-d') : '—' }}</td>
                                <td>
                                    <a href="{{ route('resort-guru-blog.edit', ['id' => $p->id]) }}" class="btn btn-sm btn-primary"><i class="bx bx-edit"></i></a>
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="text-center text-muted py-4">No posts yet.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            {{ $posts->links() }}
        </div>

        <div id="blogPaneComments" class="{{ $activeTab === 'comments' ? '' : 'd-none' }}">
            @if($commentStats)
                <div class="row g-2 mb-3">
                    <div class="col-md-3 col-6">
                        <div class="card mb-0 border"><div class="card-body py-3">
                            <small class="text-muted text-uppercase" style="font-size:10px">Total</small>
                            <h4 class="mb-0 text-primary">{{ number_format($commentStats['total']) }}</h4>
                        </div></div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card mb-0 border"><div class="card-body py-3">
                            <small class="text-muted text-uppercase" style="font-size:10px">Approved</small>
                            <h4 class="mb-0 text-success">{{ number_format($commentStats['approved']) }}</h4>
                        </div></div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card mb-0 border"><div class="card-body py-3">
                            <small class="text-muted text-uppercase" style="font-size:10px">Pending</small>
                            <h4 class="mb-0 text-warning">{{ number_format($commentStats['pending']) }}</h4>
                        </div></div>
                    </div>
                    <div class="col-md-3 col-6">
                        <div class="card mb-0 border"><div class="card-body py-3">
                            <small class="text-muted text-uppercase" style="font-size:10px">User-submitted</small>
                            <h4 class="mb-0 text-info">{{ number_format($commentStats['user']) }}</h4>
                        </div></div>
                    </div>
                </div>

                <table id="blog-comments-dt" class="table table-striped" style="width:100%">
                    <thead>
                        <tr>
                            <th>Commenter</th>
                            <th>Post</th>
                            <th>Comment</th>
                            <th>Rating</th>
                            <th>Status</th>
                            <th>Source</th>
                            <th>Date</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                </table>
            @else
                <p class="text-muted">The comments table has not been migrated yet.</p>
            @endif
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
var currentBlogTab = @json($activeTab);
var hasComments = @json((bool) $commentStats);

$(function () {
    var commentsInitialized = false;

    function initCommentsTable() {
        if (commentsInitialized || !hasComments) return;
        commentsInitialized = true;
        $('#blog-comments-dt').DataTable({
            processing: true, serverSide: true,
            ajax: '/resort-guru-blog-comments',
            order: [[6, 'desc']],
            columns: [
                { data: 'commenter_name' },
                { data: 'post', orderable: false, searchable: false },
                { data: 'snippet', orderable: false, searchable: false },
                { data: 'rating_stars', orderable: true, searchable: false, name: 'rating' },
                { data: 'status_pill', name: 'status', orderable: true, searchable: false },
                { data: 'seeded_pill', orderable: false, searchable: false },
                { data: 'created_at' },
                { data: 'actions', orderable: false, searchable: false }
            ]
        });
    }

    function activateTab(tab) {
        currentBlogTab = tab;
        $('#blogTabs .nav-link').removeClass('active');
        $('#blogTabs .nav-link[data-tab="' + tab + '"]').addClass('active');
        $('#blogPanePosts').toggleClass('d-none', tab !== 'posts');
        $('#blogPaneComments').toggleClass('d-none', tab !== 'comments');
        if (tab === 'comments') initCommentsTable();
        var url = new URL(window.location);
        if (tab === 'posts') { url.searchParams.delete('tab'); } else { url.searchParams.set('tab', tab); }
        window.history.replaceState({}, '', url);
    }

    $('#blogTabs .nav-link').on('click', function () {
        activateTab($(this).data('tab'));
    });

    if (currentBlogTab === 'comments') initCommentsTable();
});

function setStatus(id, status) {
    $.post('/resort-guru-blog-comments-status', { _token: '{{ csrf_token() }}', id: id, status: status })
        .done(function () { $('#blog-comments-dt').DataTable().ajax.reload(); });
}
function approveComment(id) { setStatus(id, 'approved'); }
function spamComment(id) { setStatus(id, 'spam'); }
function deleteComment(id) {
    Swal.fire({
        title: 'Delete this comment?', icon: 'warning',
        showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc3545'
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post('/resort-guru-blog-comments-delete', { _token: '{{ csrf_token() }}', id: id })
            .done(function () { $('#blog-comments-dt').DataTable().ajax.reload(); });
    });
}
</script>
@endsection
