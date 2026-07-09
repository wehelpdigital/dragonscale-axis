@extends('layouts.master')

@section('title') Blog Comments @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Blog Comments @endslot
@endcomponent

<div class="row g-2 mb-3">
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Total</small>
            <h4 class="mb-0 text-primary">{{ \Illuminate\Support\Facades\DB::table('rg_blog_comments')->count() }}</h4>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Approved</small>
            <h4 class="mb-0 text-success">{{ \Illuminate\Support\Facades\DB::table('rg_blog_comments')->where('status','approved')->count() }}</h4>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">Pending</small>
            <h4 class="mb-0 text-warning">{{ \Illuminate\Support\Facades\DB::table('rg_blog_comments')->where('status','pending')->count() }}</h4>
        </div></div>
    </div>
    <div class="col-md-3 col-6">
        <div class="card mb-0"><div class="card-body py-3">
            <small class="text-muted text-uppercase" style="font-size:10px">User-submitted</small>
            <h4 class="mb-0 text-info">{{ \Illuminate\Support\Facades\DB::table('rg_blog_comments')->where('is_seeded',0)->count() }}</h4>
        </div></div>
    </div>
</div>

<div class="card">
    <div class="card-body">
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
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
$(function () {
    $('#blog-comments-dt').DataTable({
        processing: true, serverSide: true,
        ajax: '/resort-guru-blog-comments',
        order: [[6, 'desc']],
        columns: [
            { data: 'commenter_name' },
            { data: 'post' },
            { data: 'snippet', orderable: false, searchable: false },
            { data: 'rating_stars', orderable: true, searchable: false, name: 'rating' },
            { data: 'status_pill' },
            { data: 'seeded_pill', orderable: false, searchable: false },
            { data: 'created_at' },
            { data: 'actions', orderable: false, searchable: false }
        ]
    });
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
