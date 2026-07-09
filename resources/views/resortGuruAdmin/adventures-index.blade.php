@extends('layouts.master')

@section('title') Adventures @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Adventures @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small" style="max-width:60ch">
                Paid experience providers — surf schools, ATV trails, dive shops, paintball arenas,
                island-hopping operators. These render in the "Memorable Adventures &amp; Activities"
                section of resort keyword pages and bid against each other for placement.
            </div>
            <a href="#" class="btn btn-primary btn-sm disabled" title="Create form is Phase 2">
                <i class="bx bx-plus me-1"></i>Add Adventure
            </a>
        </div>

        <table id="advs-dt" class="table table-striped align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Activity</th>
                    <th>Difficulty</th>
                    <th>Price</th>
                    <th>Location</th>
                    <th>Owner</th>
                    <th>Listings</th>
                    <th>Status</th>
                    <th>Updated</th>
                    <th style="width:80px">Actions</th>
                </tr>
            </thead>
        </table>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
<script>
$(function () {
    $('#advs-dt').DataTable({
        processing: true, serverSide: true,
        ajax: '/resort-guru-adventures',
        columns: [
            { data: 'name' }, { data: 'activity_type' }, { data: 'difficulty' },
            { data: 'price_range' }, { data: 'location' }, { data: 'owner' },
            { data: 'listings' }, { data: 'status_pill' }, { data: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[6, 'desc']],
        pageLength: 25,
    });
});
</script>
@endsection
