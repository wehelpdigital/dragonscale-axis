@extends('layouts.master')

@section('title') SEO Pages @endsection

@section('css')
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') SEO Pages @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title mb-3">SEO Pages</h4>
                <p class="text-muted">Each keyword has one linked SEO page. Edit content, meta tags, FAQs, and the listing fallback per page.</p>
                <div class="table-responsive">
                    <table id="pagesTable" class="table table-bordered table-striped" style="width:100%">
                        <thead class="table-light">
                            <tr>
                                <th>ID</th>
                                <th>Page Title</th>
                                <th>Keyword</th>
                                <th>Slug</th>
                                <th>Status</th>
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
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(function () {
    $('#pagesTable').DataTable({
        processing: true,
        serverSide: true,
        order: [[5, 'desc']],
        ajax: '{{ route("resort-guru-pages.index") }}',
        columns: [
            { data: 'id', name: 'p.id', width: '60px' },
            { data: 'title', name: 'p.title' },
            { data: 'phrase', name: 'k.phrase' },
            { data: 'slug', name: 'k.slug' },
            { data: 'is_published', name: 'p.is_published', width: '110px' },
            { data: 'updated_at', name: 'p.updated_at', width: '140px' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '90px' },
        ]
    });
});
</script>
@endsection
