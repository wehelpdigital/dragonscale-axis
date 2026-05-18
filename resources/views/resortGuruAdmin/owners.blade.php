@extends('layouts.master')

@section('title') Clients @endsection

@section('css')
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Clients @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h4 class="card-title mb-0">Clients</h4>
                    <a href="{{ route('resort-guru-owners.create') }}" class="btn btn-success btn-sm">
                        <i class="bx bx-plus me-1"></i> Add Client
                    </a>
                </div>
                <div class="table-responsive">
                    <table id="ownersTable" class="table table-bordered table-striped" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Name</th>
                                <th>Email</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>Last Login</th>
                                <th>Joined</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(function () {
    $('#ownersTable').DataTable({
        processing: true,
        serverSide: true,
        order: [[6, 'desc']],
        ajax: '{{ route("resort-guru-owners.index") }}',
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'name', name: 'name' },
            { data: 'email', name: 'email' },
            { data: 'phone', name: 'phone' },
            { data: 'status', name: 'status', width: '110px' },
            { data: 'last_login_at', name: 'last_login_at', width: '140px' },
            { data: 'created_at', name: 'created_at', width: '110px' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '90px' },
        ]
    });
});
</script>
@endsection
