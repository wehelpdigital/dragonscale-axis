@extends('layouts.master')

@section('title') Food Pages @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Resort Guru @endslot
@slot('title') Food Pages @endslot
@endcomponent

<div class="card">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <div class="text-muted small" style="max-width:60ch">
                SEO page content for food-category keywords. Each row is a published article with
                intro, body, FAQ, and the keyword woven 4-6 times per content rules (no em-dashes,
                no marketing words, DIY-traveler voice).
            </div>
        </div>

        <table id="fp-dt" class="table table-striped align-middle" style="width:100%">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Slug</th>
                    <th>Keyword</th>
                    <th>Volume</th>
                    <th>30d views</th>
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
    $('#fp-dt').DataTable({
        processing: true, serverSide: true,
        ajax: '/resort-guru-food-pages',
        columns: [
            { data: 'title' }, { data: 'slug' }, { data: 'phrase' },
            { data: 'volume_fmt' }, { data: 'pageviews_30d' },
            { data: 'status_pill' }, { data: 'updated_at' },
            { data: 'actions', orderable: false, searchable: false }
        ],
        order: [[3, 'desc']],
        pageLength: 25,
    });
});
</script>
@endsection
