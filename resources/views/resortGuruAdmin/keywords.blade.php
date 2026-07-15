@extends('layouts.master')

@section('title') Keywords @endsection

@section('css')
<link href="{{ URL::asset('/build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('/build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') Keywords @endslot
@endcomponent

<div class="row">
    <div class="col-12">
        <div class="card">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h4 class="card-title mb-0">Keywords / Keyphrases</h4>
                        <ul class="nav nav-pills" id="kwViewTabs">
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ $activeView === 'keywords' ? 'active' : '' }}" href="javascript:void(0);" data-view="keywords">Keywords</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link py-1 {{ $activeView === 'pages' ? 'active' : '' }}" href="javascript:void(0);" data-view="pages">SEO Pages</a>
                            </li>
                        </ul>
                    </div>
                    <div class="d-flex gap-2 kw-only {{ $activeView === 'pages' ? 'd-none' : '' }}">
                        <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#importModal">
                            <i class="bx bx-upload"></i> Import CSV
                        </button>
                        <a href="{{ route('resort-guru-keywords.create') }}" id="addKeywordBtn" class="btn btn-primary">
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

                <ul class="nav nav-tabs nav-tabs-custom mb-3" id="kwCategoryTabs" role="tablist">
                    @foreach($tabs as $key => $tab)
                        <li class="nav-item" role="presentation">
                            <a class="nav-link {{ $activeCategory === $key ? 'active' : '' }}" href="javascript:void(0);" data-category="{{ $key }}" role="tab">
                                {{ $tab['label'] }}
                                <span class="badge bg-light text-body ms-1" data-count-for="{{ $key }}">{{ number_format($tab['count']) }}</span>
                            </a>
                        </li>
                    @endforeach
                </ul>

                <div id="kwPaneKeywords" class="{{ $activeView === 'pages' ? 'd-none' : '' }}">
                    <div class="table-responsive">
                        <table id="keywordsTable" class="table table-bordered table-striped align-middle" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Keyword</th>
                                    <th>Type</th>
                                    <th>Region</th>
                                    <th>Volume</th>
                                    <th>KD</th>
                                    <th>Status</th>
                                    <th>Pages</th>
                                    <th>Listings</th>
                                    <th>Updated</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>

                <div id="kwPanePages" class="{{ $activeView === 'pages' ? '' : 'd-none' }}">
                    <p class="text-muted small mb-3" style="max-width:70ch">
                        The SEO article pages generated for each keyword. Edit opens the block builder.
                        To add or remove pages for a keyword, use the <strong>Pages</strong> button on its row in the Keywords view.
                    </p>
                    <div class="table-responsive">
                        <table id="pagesTable" class="table table-bordered table-striped align-middle" style="width:100%">
                            <thead class="table-light">
                                <tr>
                                    <th>ID</th>
                                    <th>Page</th>
                                    <th>Keyword</th>
                                    <th>Type</th>
                                    <th>Volume</th>
                                    <th>30d Views</th>
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
                <p>Upload a CSV. Required column: <code>phrase</code> (or <code>keyword</code>). Optional: <code>category</code> (e.g. <code>resort</code>, <code>food</code>, <code>destination</code>), <code>volume</code>, <code>kd</code>, <code>cluster_tag</code>, <code>intent</code>.</p>
                <p class="text-muted small mb-2" id="importCategoryHint"></p>
                <input type="hidden" name="default_category" id="importDefaultCategory" value="{{ $activeCategory }}">
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
var currentCategory = @json($activeCategory);
var currentView = @json($activeView);

$(function () {
    var pagesTable = null;

    var table = $('#keywordsTable').DataTable({
        processing: true,
        serverSide: true,
        order: [[4, 'desc']],
        ajax: {
            url: '{{ route("resort-guru-keywords.index") }}',
            data: function (d) { d.category = currentCategory; }
        },
        columns: [
            { data: 'id', name: 'id', width: '60px' },
            { data: 'phrase', name: 'phrase' },
            // Not searchable: any term contained in a type name ("food", "resort")
            // would otherwise match every row of that type via the global search.
            { data: 'category', name: 'category', searchable: false, width: '110px' },
            { data: 'cluster_tag', name: 'cluster_tag', width: '130px' },
            { data: 'search_volume_monthly', name: 'search_volume_monthly', width: '90px' },
            { data: 'keyword_difficulty', name: 'keyword_difficulty', width: '60px' },
            { data: 'status', name: 'status', width: '90px' },
            { data: 'pages_summary', name: 'pages_summary', orderable: false, searchable: false, width: '100px' },
            { data: 'listings_summary', name: 'listings_summary', orderable: false, searchable: false, width: '80px' },
            { data: 'updated_at', name: 'updated_at', width: '130px' },
            { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '180px' },
        ]
    });

    // Keep the tab count badges in sync with the data (deletes, imports in
    // another tab, etc.). Each view reports its own counts (keywords vs SEO
    // pages), so remember the last payload per view and re-apply on switch.
    var tabCountsByView = { keywords: null, pages: null };

    function applyBadges(counts) {
        $('#kwCategoryTabs [data-count-for]').each(function () {
            var key = $(this).data('count-for');
            var n = Object.prototype.hasOwnProperty.call(counts, key) ? counts[key] : 0;
            $(this).text(Number(n).toLocaleString());
        });
    }

    function syncBadges(e, settings, json) {
        if (!json || !json.tabCounts) return;
        var view = settings.nTable.id === 'pagesTable' ? 'pages' : 'keywords';
        tabCountsByView[view] = json.tabCounts;
        if (view === currentView) applyBadges(json.tabCounts);
    }
    table.on('xhr.dt', syncBadges);

    function initPagesTable() {
        if (pagesTable) return;
        pagesTable = $('#pagesTable').DataTable({
            processing: true,
            serverSide: true,
            order: [[4, 'desc']],
            ajax: {
                url: '{{ route("resort-guru-keywords.index") }}',
                data: function (d) { d.category = currentCategory; d.view = 'pages'; }
            },
            columns: [
                { data: 'id', name: 'p.id', width: '60px' },
                { data: 'title', name: 'title' },
                // k.-qualified names: Yajra prefixes bare names with the base
                // table (p), and these columns live on rg_keywords.
                { data: 'phrase', name: 'k.phrase' },
                { data: 'category', name: 'k.category', searchable: false, width: '110px' },
                { data: 'search_volume_monthly', name: 'k.search_volume_monthly', width: '90px' },
                { data: 'pageviews_30d', name: 'pageviews_30d', width: '90px' },
                { data: 'status_pill', name: 'status_pill', orderable: false, searchable: false, width: '80px' },
                { data: 'updated_at', name: 'p.updated_at', width: '110px' },
                { data: 'actions', name: 'actions', orderable: false, searchable: false, width: '140px' },
            ]
        });
        pagesTable.on('xhr.dt', syncBadges);
        syncTypeColumns();
    }

    function syncTypeColumns() {
        // The Type column is redundant when a single type tab is active.
        table.column(2).visible(currentCategory === 'all');
        if (pagesTable) pagesTable.column(3).visible(currentCategory === 'all');
    }

    function syncCategoryUi() {
        syncTypeColumns();
        var base = '{{ route("resort-guru-keywords.create") }}';
        $('#addKeywordBtn').attr('href', currentCategory === 'all'
            ? base
            : base + '?category=' + encodeURIComponent(currentCategory));
        $('#importDefaultCategory').val(currentCategory);
        var tabName = $('#kwCategoryTabs .nav-link[data-category="' + currentCategory + '"]').contents().first().text().trim();
        $('#importCategoryHint').text(currentCategory === 'all'
            ? 'Rows without a category column are imported as "resort".'
            : 'Rows without a category column are imported into the current tab (' + tabName + ').');
    }

    function syncViewUi() {
        $('#kwViewTabs .nav-link').removeClass('active');
        $('#kwViewTabs .nav-link[data-view="' + currentView + '"]').addClass('active');
        $('#kwPaneKeywords').toggleClass('d-none', currentView !== 'keywords');
        $('#kwPanePages').toggleClass('d-none', currentView !== 'pages');
        $('.kw-only').toggleClass('d-none', currentView !== 'keywords');
        if (currentView === 'pages') {
            initPagesTable();
            pagesTable.columns.adjust();
        } else {
            table.columns.adjust();
        }
        if (tabCountsByView[currentView]) applyBadges(tabCountsByView[currentView]);
    }

    function updateUrl() {
        var url = new URL(window.location);
        if (currentCategory === 'all') { url.searchParams.delete('category'); } else { url.searchParams.set('category', currentCategory); }
        if (currentView === 'keywords') { url.searchParams.delete('view'); } else { url.searchParams.set('view', currentView); }
        window.history.replaceState({}, '', url);
    }

    $('#kwViewTabs .nav-link').on('click', function () {
        currentView = $(this).data('view');
        syncViewUi();
        updateUrl();
    });

    $('#kwCategoryTabs .nav-link').on('click', function () {
        $('#kwCategoryTabs .nav-link').removeClass('active');
        $(this).addClass('active');
        currentCategory = $(this).data('category');
        syncCategoryUi();
        table.ajax.reload();
        if (pagesTable) pagesTable.ajax.reload();
        updateUrl();
    });

    syncCategoryUi();
    syncViewUi();
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
                // The keyword's SEO pages are cascade-deleted with it.
                if ($.fn.DataTable.isDataTable('#pagesTable')) {
                    $('#pagesTable').DataTable().ajax.reload();
                }
            },
            error: function () { toastr.error('Delete failed.'); }
        });
    });
}
</script>
@endsection
