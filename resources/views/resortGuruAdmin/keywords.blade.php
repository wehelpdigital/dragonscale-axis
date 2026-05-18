@extends('layouts.master')

@section('title') Keywords @endsection

@section('css')
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Keywords @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <h4 class="card-title mb-0">Keywords / Keyphrases</h4>
                    <div class="d-flex gap-2">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bx bx-upload"></i> Import CSV
                        </button>
                        <a href="{{ route('resort-guru-keywords.create') }}" class="btn btn-primary">
                            <i class="bx bx-plus"></i> Add Keyword
                        </a>
                    </div>
                </div>

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

                <div class="table-responsive">
                    <table id="keywordsTable" class="table table-bordered table-striped" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Phrase</th>
                                <th>Slug</th>
                                <th>Volume</th>
                                <th>KD</th>
                                <th>Status</th>
                                <th>Pages</th>
                                <th>Updated</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Import modal -->
<div class="modal fade" id="importModal" tabindex="-1">
    <div class="modal-dialog">
        <form action="{{ route('resort-guru-keywords.import') }}" method="POST" enctype="multipart/form-data" class="modal-content">
            @csrf
            <div class="modal-header">
                <h5 class="modal-title">Import Keywords (CSV)</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>Upload a CSV. Required column: <code>phrase</code> (or <code>keyword</code>). Optional: <code>volume</code>, <code>kd</code>, <code>cluster_tag</code>, <code>intent</code>.</p>
                <input type="file" name="file" accept=".csv" class="form-control" required>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="submit" class="btn btn-primary">Upload &amp; Import</button>
            </div>
        </form>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(function () {
    var table = $('#keywordsTable').DataTable({
        processing: true,
        serverSide: true,
        order: [[3, 'desc']],
        ajax: '{{ route("resort-guru-keywords.index") }}',
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'phrase', name: 'phrase' },
            { data: 'slug', name: 'slug' },
            { data: 'search_volume_monthly', name: 'search_volume_monthly', width: '90px' },
            { data: 'keyword_difficulty', name: 'keyword_difficulty', width: '60px' },
            { data: 'status', name: 'status', width: '90px' },
            { data: 'pages_summary', name: 'pages_summary', orderable: false, searchable: false, width: '100px' },
            { data: 'updated_at', name: 'updated_at', width: '130px' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '180px' },
        ]
    });
});

function confirmDelete(id) {
    Swal.fire({
        title: 'Delete this keyword?',
        text: 'The linked SEO page will also be removed. This cannot be undone.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#f46a6a',
        confirmButtonText: 'Yes, delete it'
    }).then(function (result) {
        if (!result.isConfirmed) return;
        $.ajax({
            url: '/resort-guru-keywords-delete?id=' + id,
            type: 'DELETE',
            success: function () {
                toastr.success('Keyword deleted.');
                $('#keywordsTable').DataTable().ajax.reload();
            },
            error: function () { toastr.error('Delete failed.'); }
        });
    });
}
</script>
@endsection
