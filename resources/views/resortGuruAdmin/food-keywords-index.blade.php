@extends('layouts.master')

@section('title') Food Keywords @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Food Keywords @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small" style="max-width:60ch">
                Restaurant-search keywords (category=<code>food</code>). These render on <code>/food-trip</code>
                and at each keyword's own slug. Restaurant listings stack against these pages — a separate
                bid pool from resort keywords.
            </div>
            <a href="#" class="btn btn-primary btn-sm disabled" title="Create form is Phase 2">
                <i class="bx bx-plus me-1"></i>Add Food Keyword
            </a>
        </div>

        <table id="fk-dt" class="table table-striped align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>Phrase</th>
                    <th>Slug</th>
                    <th>Volume</th>
                    <th>Region</th>
                    <th>Pages</th>
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
    $('#fk-dt').DataTable({
        processing: true, serverSide: true,
        ajax: '/resort-guru-food-keywords',
        columns: [
            { data: 'phrase' }, { data: 'slug' }, { data: 'volume_fmt' },
            { data: 'cluster_tag' }, { data: 'pages_summary' }, { data: 'listings_count' },
            { data: 'status' }, { data: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[2, 'desc']],
        pageLength: 25,
    });
});
</script>
@endsection
