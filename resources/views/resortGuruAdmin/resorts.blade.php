@extends('layouts.master')

@section('title') Properties @endsection

@section('css')
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Properties @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <h4 class="card-title mb-3">All Properties</h4>
        <div class="table-responsive">
            <table id="resortsTable" class="table table-bordered table-striped" style="width:100%">
                <thead class="table-light">
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Owner</th>
                        <th>Location</th>
                        <th>Status</th>
                        <th>Updated</th>
                        <th>Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(function () {
    $('#resortsTable').DataTable({
        processing: true, serverSide: true, order: [[5, 'desc']],
        ajax: '{{ route("resort-guru-resorts.index") }}',
        columns: [
            { data: 'id', name: 'r.id', width: '60px' },
            { data: 'name', name: 'r.name' },
            { data: 'owner_name', name: 'o.name' },
            { data: 'city', name: 'r.city', render: function(d,t,r){ return (d||'—') + ', ' + (r.province||''); } },
            { data: 'status', name: 'r.status', width: '140px' },
            { data: 'updated_at', name: 'r.updated_at', width: '140px' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '100px' },
        ]
    });
});
</script>
@endsection
