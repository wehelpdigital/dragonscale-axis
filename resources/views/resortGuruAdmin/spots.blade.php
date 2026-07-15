@extends('layouts.master')

@section('title') Spots @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs5/css/dataTables.bootstrap5.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') TouristGuidePh @endslot
@slot('title') Spots @endslot
@endcomponent

<div class="card">
    <div class="card-body">
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

        <ul class="nav nav-tabs nav-tabs-custom mb-3" id="spotsTabs" role="tablist">
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab === 'tourist-spots' ? 'active' : '' }}" href="javascript:void(0);" data-tab="tourist-spots" role="tab">
                    <i class="bx bx-map-pin me-1"></i>Tourist Spots
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab === 'restaurants' ? 'active' : '' }}" href="javascript:void(0);" data-tab="restaurants" role="tab">
                    <i class="bx bx-restaurant me-1"></i>Restaurants
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab === 'adventures' ? 'active' : '' }}" href="javascript:void(0);" data-tab="adventures" role="tab">
                    <i class="bx bx-cycling me-1"></i>Adventures
                </a>
            </li>
            <li class="nav-item" role="presentation">
                <a class="nav-link {{ $activeTab === 'fiestas' ? 'active' : '' }}" href="javascript:void(0);" data-tab="fiestas" role="tab">
                    <i class="bx bx-calendar-event me-1"></i>Fiestas
                </a>
            </li>
        </ul>

        <div id="pane-tourist-spots" class="spots-pane {{ $activeTab === 'tourist-spots' ? '' : 'd-none' }}">
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

        <div id="pane-restaurants" class="spots-pane {{ $activeTab === 'restaurants' ? '' : 'd-none' }}">
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

        <div id="pane-adventures" class="spots-pane {{ $activeTab === 'adventures' ? '' : 'd-none' }}">
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

        <div id="pane-fiestas" class="spots-pane {{ $activeTab === 'fiestas' ? '' : 'd-none' }}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <div class="text-muted small" style="max-width:60ch">
                    The fiesta calendar. Add a new fiesta with its basic metadata, then use the
                    <strong>Blocks</strong> button to assemble its content cards.
                </div>
                <a href="{{ route('resort-guru-fiestas.create') }}" class="btn btn-primary btn-sm">
                    <i class="bx bx-plus me-1"></i>Add Fiesta
                </a>
            </div>
            <table id="fiestas-dt" class="table table-striped align-middle" style="width:100%">
                <thead>
                    <tr>
                        <th>Name</th>
                        <th>Region</th>
                        <th>Province</th>
                        <th>City / Town</th>
                        <th>Month</th>
                        <th>Date Label</th>
                        <th>Status</th>
                        <th style="width:180px">Actions</th>
                    </tr>
                </thead>
            </table>
        </div>
    </div>
</div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs5/js/dataTables.bootstrap5.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
var currentSpotsTab = @json($activeTab);

$(function () {
    var initialized = {};

    // Tables are created only when their tab is first shown so DataTables
    // computes column widths against a visible container.
    var initializers = {
        'tourist-spots': function () {
            $('#spots-dt').DataTable({
                processing: true, serverSide: true,
                ajax: '/resort-guru-tourist-spots',
                columns: [
                    { data: 'thumb',          orderable: false, searchable: false },
                    { data: 'name' },
                    { data: 'location' },
                    { data: 'region_label' },
                    { data: 'keyword_link',   orderable: false, searchable: false },
                    { data: 'featured_badge', name: 'featured_order', orderable: true, searchable: false },
                    { data: 'status_pill',    name: 'status', orderable: true, searchable: false },
                    { data: 'updated_at' },
                    { data: 'actions',        orderable: false, searchable: false }
                ],
                order: [[5, 'asc']],
                pageLength: 25,
            });
        },
        'restaurants': function () {
            $('#rests-dt').DataTable({
                processing: true, serverSide: true,
                ajax: '/resort-guru-restaurants',
                columns: [
                    { data: 'name' }, { data: 'cuisine' }, { data: 'price_range' },
                    { data: 'location', orderable: false, searchable: false }, { data: 'owner', orderable: false, searchable: false }, { data: 'listings', orderable: false, searchable: false },
                    { data: 'status_pill', name: 'status', orderable: true, searchable: false }, { data: 'updated_at' },
                    { data: 'actions', orderable: false, searchable: false }
                ],
                order: [[7, 'desc']],
                pageLength: 25,
            });
        },
        'adventures': function () {
            $('#advs-dt').DataTable({
                processing: true, serverSide: true,
                ajax: '/resort-guru-adventures',
                columns: [
                    { data: 'name' }, { data: 'activity_type' }, { data: 'difficulty' },
                    { data: 'price_range' }, { data: 'location', orderable: false, searchable: false }, { data: 'owner', orderable: false, searchable: false },
                    { data: 'listings', orderable: false, searchable: false }, { data: 'status_pill', name: 'status', orderable: true, searchable: false }, { data: 'updated_at' },
                    { data: 'actions', orderable: false, searchable: false }
                ],
                order: [[8, 'desc']],
                pageLength: 25,
            });
        },
        'fiestas': function () {
            $('#fiestas-dt').DataTable({
                processing: true, serverSide: true,
                ajax: '{{ route("resort-guru-fiestas.index") }}',
                columns: [
                    { data: 'name', name: 'name' },
                    { data: 'region_label', name: 'region_cluster' },
                    { data: 'province', name: 'province' },
                    { data: 'city_or_town', name: 'city_or_town' },
                    { data: 'month_label', name: 'month' },
                    { data: 'date_label', name: 'date_label' },
                    { data: 'status_label', name: 'is_published', orderable: false, searchable: false },
                    { data: 'actions', name: 'actions', orderable: false, searchable: false }
                ],
                order: [[0, 'asc']],
                pageLength: 25,
            });
        }
    };

    function activateTab(tab) {
        currentSpotsTab = tab;
        $('#spotsTabs .nav-link').removeClass('active');
        $('#spotsTabs .nav-link[data-tab="' + tab + '"]').addClass('active');
        $('.spots-pane').addClass('d-none');
        $('#pane-' + tab).removeClass('d-none');
        if (!initialized[tab]) {
            initialized[tab] = true;
            initializers[tab]();
        }
        var url = new URL(window.location);
        if (tab === 'tourist-spots') { url.searchParams.delete('tab'); } else { url.searchParams.set('tab', tab); }
        window.history.replaceState({}, '', url);
    }

    $('#spotsTabs .nav-link').on('click', function () {
        activateTab($(this).data('tab'));
    });

    initialized[currentSpotsTab] = true;
    initializers[currentSpotsTab]();
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

window.rgDeleteFiesta = function (id, url) {
    Swal.fire({
        title: 'Delete this fiesta?',
        text: 'All content blocks attached to it will be removed.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonText: 'Yes, delete',
        confirmButtonColor: '#ef4444'
    }).then(function (r) {
        if (!r.isConfirmed) return;
        fetch(url, {
            method: 'DELETE',
            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}' }
        }).then(function (res) { return res.json(); }).then(function (j) {
            if (j.ok) {
                Swal.fire('Deleted', '', 'success');
                $('#fiestas-dt').DataTable().ajax.reload();
            } else {
                Swal.fire('Error', j.error || 'Could not delete', 'error');
            }
        });
    });
};
</script>
@endsection
