@extends('layouts.master')

@section('title') Restaurants @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Restaurants @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small" style="max-width:60ch">
                Master list of restaurant businesses. Each row is the source of truth for the cards
                rendered on Food Trip keyword pages and the "Restaurant Recommendations" section on
                resort keyword pages. Restaurants pay GP to appear on each keyword page they list on.
            </div>
            <a href="#" class="btn btn-primary btn-sm disabled" title="Create form is Phase 2">
                <i class="bx bx-plus me-1"></i>Add Restaurant
            </a>
        </div>

        <table id="rests-dt" class="table table-striped align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>Name</th>
                    <th>Cuisine</th>
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
    $('#rests-dt').DataTable({
        processing: true, serverSide: true,
        ajax: '/resort-guru-restaurants',
        columns: [
            { data: 'name' }, { data: 'cuisine' }, { data: 'price_range' },
            { data: 'location' }, { data: 'owner' }, { data: 'listings' },
            { data: 'status_pill' }, { data: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[5, 'desc']],
        pageLength: 25,
    });
});
</script>
@endsection
