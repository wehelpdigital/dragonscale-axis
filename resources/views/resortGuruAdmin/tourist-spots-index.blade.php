@extends('layouts.master')

@section('title') Tourist Spots @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Tourist Spots @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small" style="max-width:60ch">
                The single source of truth for tourist spots across the site. Every spot row drives the
                <code>/destinations</code> carousel (when <strong>Featured order</strong> is set) and the
                typeahead search index. Each spot needs an <strong>image</strong> + a linked
                <strong>keyword page</strong> to appear in the public carousel.
            </div>
            <a href="/resort-guru-tourist-spots-create" class="btn btn-primary btn-sm">
                <i class="bx bx-plus me-1"></i>Add Tourist Spot
            </a>
        </div>

        <table id="spots-dt" class="table table-striped align-middle" style="width:100%">
            <thead>
                <tr>
                    <th style="width:64px"></th>
                    <th>Name</th>
                    <th>Location</th>
                    <th>Region</th>
                    <th>Keyword page</th>
                    <th>Featured</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th style="width:90px">Actions</th>
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
    $('#spots-dt').DataTable({
        processing: true, serverSide: true,
        ajax: '/resort-guru-tourist-spots',
        columns: [
            { data: 'thumb',          orderable: false, searchable: false },
            { data: 'name' },
            { data: 'location' },
            { data: 'region_label' },
            { data: 'keyword_link',   orderable: false, searchable: false },
            { data: 'featured_badge', orderable: true,  searchable: false },
            { data: 'status_pill',    orderable: true,  searchable: false },
            { data: 'updated_at' },
            { data: 'actions',        orderable: false, searchable: false }
        ],
        order: [[5, 'asc']],
        pageLength: 25,
    });
});

function deleteSpot(id) {
    Swal.fire({
        title: 'Delete this tourist spot?',
        text: 'It will be removed from the carousel and the typeahead search index. The linked media row stays.',
        icon: 'warning', showCancelButton: true,
        confirmButtonText: 'Delete', confirmButtonColor: '#dc3545',
    }).then(function (r) {
        if (!r.isConfirmed) return;
        $.post('/resort-guru-tourist-spots-delete', { _token: '{{ csrf_token() }}', id: id })
            .done(function () {
                $('#spots-dt').DataTable().ajax.reload();
                if (window.toastr) toastr.success('Spot deleted.');
            })
            .fail(function () {
                if (window.toastr) toastr.error('Delete failed.');
            });
    });
}
</script>
@endsection
