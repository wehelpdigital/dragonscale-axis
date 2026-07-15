@extends('layouts.master')

@section('title') Authors @endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') Authors @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small">
                Authors are the bylines that appear on keyword landing pages. Use this list to add or edit
                contributors and assign which destination clusters they cover.
            </div>
            <a href="/resort-guru-authors-create" class="btn btn-primary btn-sm">
                <i class="bx bx-plus me-1"></i>Add Author
            </a>
        </div>

        <table id="authors-dt" class="table table-striped" style="width:100%">
            <thead>
                <tr>
                    <th style="width:60px"></th>
                    <th>Name</th>
                    <th>Role</th>
                    <th>Home Base</th>
                    <th>Covers</th>
                    <th>Pages</th>
                    <th>Status</th>
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
    $('#authors-dt').DataTable({
        processing: true, serverSide: true,
        ajax: '/resort-guru-authors',
        columns: [
            { data: 'avatar', orderable: false, searchable: false },
            { data: 'name' },
            { data: 'role' },
            { data: 'home_base' },
            { data: 'covers_clusters' },
            { data: 'pages_count', searchable: false },
            { data: 'status_pill' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[5, 'desc']],
    });
});
function deleteAuthor(id) {
    Swal.fire({
        title: 'Delete this author?',
        text: 'Pages assigned to this author will have their byline cleared (the content stays).',
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Delete', confirmButtonColor: '#dc3545',
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post('/resort-guru-authors-delete', { _token: '{{ csrf_token() }}', id: id })
            .done(function () { $('#authors-dt').DataTable().ajax.reload(); });
    });
}
</script>
@endsection
