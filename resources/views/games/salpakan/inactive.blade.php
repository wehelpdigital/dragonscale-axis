@extends('layouts.master')

@section('title') {{ $gameName }} Inactive Players @endsection

@section('css')
@include('games.salpakan._styles', ['tables' => true])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Games @endslot
        @slot('li_2') {{ $gameName }} @endslot
        @slot('title') Inactive Players @endslot
    @endcomponent

    <div id="gs">
        <p class="text-secondary small">Players who have not played for a while, to write to later. "Reachable" means a real email address (not a Steam placeholder) and not opted out of marketing in the game. Someone who registered and never played counts from the day they signed up.</p>

        <div class="row g-3 mb-3" id="gsSegments">
            @foreach ($thresholds as $threshold)
                <div class="col-sm-6 col-xl-3">
                    <div class="stat segment {{ $threshold === $days ? 'is-on' : '' }}" data-days="{{ $threshold }}" role="button" tabindex="0">
                        <i class="bx bx-user-x"></i>
                        <div>
                            <b>{{ number_format($counts[$threshold]['reachable']) }} <small class="text-secondary fs-6">of {{ number_format($counts[$threshold]['all']) }}</small></b>
                            <span>reachable, not played for {{ $threshold }}+ days</span>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h4 class="card-title mb-1 text-dark">Not played for <span id="gsDaysLabel">{{ $days }}</span>+ days</h4>
                        <p class="text-secondary mb-0 small">Longest gone first. The export takes the same players as the table.</p>
                    </div>
                    <a href="{{ route('games-salpakan-inactive.export', ['days' => $days]) }}" class="btn btn-outline-success" id="gsExport"><i class="bx bx-download me-1"></i>Export CSV</a>
                </div>

                <div class="filters row g-2 mb-3" id="gsFilters">
                    <input type="hidden" data-filter="days" id="fDays" value="{{ $days }}">
                    <div class="col-md-4 col-xl-3">
                        <label class="form-label" for="fSearch">Search</label>
                        <input type="text" class="form-control" id="fSearch" data-filter="searchFilter" placeholder="Username or email">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fCountry">Country</label>
                        <select class="form-select" id="fCountry" data-filter="country">
                            <option value="">Any</option>
                            @foreach ($countries as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fPurchases">Purchases</label>
                        <select class="form-select" id="fPurchases" data-filter="hasPurchases">
                            <option value="">Any</option>
                            <option value="yes">Has bought</option>
                            <option value="no">Never bought</option>
                        </select>
                    </div>
                    <div class="col-md-2 col-xl-3 d-flex align-items-end">
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="fReachable" data-filter="reachable" checked>
                            <label class="form-check-label text-dark" for="fReachable">Reachable by email only</label>
                        </div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="gsInactive" class="table table-hover align-middle nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Player</th>
                                <th>Email</th>
                                <th>Last played</th>
                                <th class="text-end">Days gone</th>
                                <th>Platform</th>
                                <th>Location</th>
                                <th class="text-end">Matches</th>
                                <th class="text-end">Purchases</th>
                                <th>Acumbamail</th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function () {
@include('games.salpakan._script-helpers')

    const $filters = $('#gsFilters');
    const exportBase = @json(route('games-salpakan-inactive.export'));

    const table = $('#gsInactive').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        searching: false,
        ordering: false,
        pageLength: 50,
        ajax: {
            url: @json(route('games-salpakan-inactive.data')),
            data: (d) => Object.assign(d, filterValues($filters)),
            error: () => toastr.error('The inactive players could not be loaded.', 'Error!')
        },
        columns: [
            { data: 'username', render: (d, t, row) => '<a href="' + esc(row.showUrl) + '" class="fw-semibold text-dark">' + esc(d || 'Player #' + row.remotePlayerId) + '</a>' },
            { data: 'email', render: (d, t, row) => d ? '<span class="text-dark">' + esc(d) + '</span>' + (row.realEmail ? '' : ' <span class="badge bg-light text-dark">not real</span>') + (row.marketingOptOut ? ' <span class="badge bg-warning text-dark">opted out</span>' : '') : dash },
            { data: 'lastSeenFormatted', render: (d, t, row) => (d ? '<span class="text-dark">' + esc(d) + '</span>' : dash) + (row.neverPlayed ? '<div class="sub">signed up, never played</div>' : '') },
            { data: 'daysInactive', className: 'text-end', render: (d) => d === null ? dash : '<span class="text-dark">' + num(d) + '</span>' },
            { data: 'platformLabel', render: (d) => d ? esc(d) : dash },
            { data: 'location', render: (d) => d ? esc(d) : dash },
            { data: 'gamesPlayed', className: 'text-end', render: (d) => num(d) },
            { data: 'purchasesCount', className: 'text-end', render: (d) => num(d) },
            { data: 'acumbamailStatus', render: (d, t, row) => marketingBadge(d, row.marketingLabel) }
        ]
    });

    const reload = () => {
        table.ajax.reload();
        $('#gsExport').attr('href', exportBase + '?' + $.param(filterValues($filters)));
    };
    $('#gsSegments').on('click keypress', '.segment', function () {
        $('#gsSegments .segment').removeClass('is-on');
        $(this).addClass('is-on');
        $('#fDays').val($(this).data('days'));
        $('#gsDaysLabel').text($(this).data('days'));
        reload();
    });
    let timer = null;
    $('#fSearch').on('keyup', () => { clearTimeout(timer); timer = setTimeout(reload, 400); });
    $filters.find('select, input[type=checkbox]').on('change', reload);
    $('#gsExport').attr('href', exportBase + '?' + $.param(filterValues($filters)));
});
</script>
@endsection
