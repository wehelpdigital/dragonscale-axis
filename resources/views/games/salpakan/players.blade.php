@extends('layouts.master')

@section('title') {{ $gameName }} Players @endsection

@section('css')
@include('games.salpakan._styles', ['tables' => true])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Games @endslot
        @slot('li_2') {{ $gameName }} @endslot
        @slot('title') Players @endslot
    @endcomponent

    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bx bx-error-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif

    <div id="gs">
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-group"></i><div><b>{{ number_format($stats['players']) }}</b><span>players registered</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-joystick"></i><div><b>{{ number_format($stats['active7']) }}</b><span>played in the last 7 days</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-cart"></i><div><b>{{ number_format($stats['paying']) }}</b><span>have bought something</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-envelope"></i><div><b>{{ number_format($stats['subscribed']) }}</b><span>on the Acumbamail list</span></div></div></div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h4 class="card-title mb-1 text-dark">Players</h4>
                        <p class="text-secondary mb-0 small">Everyone registered in {{ $gameName }}, as the game server last described them (synced every 15 minutes). Times are Philippine time.</p>
                    </div>
                    <a href="{{ route('games-salpakan-players.export') }}" class="btn btn-outline-success" id="gsExport"><i class="bx bx-download me-1"></i>Export CSV</a>
                </div>

                <div class="filters row g-2 mb-3" id="gsFilters">
                    <div class="col-md-4 col-xl-3">
                        <label class="form-label" for="fSearch">Search</label>
                        <input type="text" class="form-control" id="fSearch" data-filter="searchFilter" placeholder="Username, email or player ID">
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label" for="fSignUp">Signed up with</label>
                        <select class="form-select" id="fSignUp" data-filter="signUpMethod">
                            <option value="">Any</option>
                            @foreach (\App\Models\GamePlayer::SIGN_UP_METHODS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label" for="fCountry">Country</label>
                        <select class="form-select" id="fCountry" data-filter="country">
                            <option value="">Any</option>
                            @foreach ($countries as $code => $name)
                                <option value="{{ $code }}">{{ $name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label" for="fPlatform">Last platform</label>
                        <select class="form-select" id="fPlatform" data-filter="platform">
                            <option value="">Any</option>
                            @foreach (\App\Models\GamePlayer::PLATFORMS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-3">
                        <label class="form-label" for="fInactive">Has not played for</label>
                        <select class="form-select" id="fInactive" data-filter="inactiveDays">
                            <option value="">Any time</option>
                            @foreach ([7, 14, 30, 60, 90] as $days)
                                <option value="{{ $days }}">{{ $days }}+ days</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label" for="fPurchases">Purchases</label>
                        <select class="form-select" id="fPurchases" data-filter="hasPurchases">
                            <option value="">Any</option>
                            <option value="yes">Has bought</option>
                            <option value="no">Never bought</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label" for="fMarketing">Marketing</label>
                        <select class="form-select" id="fMarketing" data-filter="marketing">
                            <option value="">Any</option>
                            <option value="reachable">Reachable by email</option>
                            @foreach (\App\Models\GamePlayer::ACUMBAMAIL_STATUSES as $value => $label)
                                <option value="{{ $value }}">Acumbamail: {{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fRegFrom">Registered from</label>
                        <input type="date" class="form-control" id="fRegFrom" data-filter="registeredFrom">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fRegTo">Registered to</label>
                        <input type="date" class="form-control" id="fRegTo" data-filter="registeredTo">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fActFrom">Last active from</label>
                        <input type="date" class="form-control" id="fActFrom" data-filter="activeFrom">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fActTo">Last active to</label>
                        <input type="date" class="form-control" id="fActTo" data-filter="activeTo">
                    </div>
                    <div class="col-md-3 col-xl-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary w-100" id="gsClear"><i class="bx bx-x me-1"></i>Clear</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="gsPlayers" class="table table-hover align-middle nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Player</th>
                                <th>Signed up</th>
                                <th>Registered</th>
                                <th>Last active</th>
                                <th>Platform</th>
                                <th>Location</th>
                                <th>Matches</th>
                                <th>Rank</th>
                                <th>Purchases</th>
                                <th>Acumbamail</th>
                                <th></th>
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
    const exportBase = @json(route('games-salpakan-players.export'));

    const table = $('#gsPlayers').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        searching: false,
        order: [[2, 'desc']],
        pageLength: 25,
        ajax: {
            url: @json(route('games-salpakan-players.data')),
            data: (d) => Object.assign(d, filterValues($filters)),
            error: () => toastr.error('The players could not be loaded.', 'Error!')
        },
        columns: [
            { data: 'username', name: 'username', render: (d, t, row) => {
                let html = '<a href="' + esc(row.showUrl) + '" class="fw-semibold text-dark">' + esc(d || 'Player #' + row.remotePlayerId) + '</a>';
                html += '<div class="sub">' + (row.email ? esc(row.email) + (row.realEmail ? '' : ' <span class="badge bg-light text-dark">no real email</span>') : 'No email') + '</div>';
                if (row.isBanned) { html += '<span class="badge bg-danger me-1">Banned</span>'; }
                if (row.adFree) { html += '<span class="badge bg-info text-white">No ads</span>'; }
                return html;
            } },
            { data: 'signUpLabel', name: 'signUpMethod', render: (d) => esc(d || '—') },
            { data: 'registeredFormatted', name: 'registeredAt', render: (d) => d ? '<span class="text-dark">' + esc(d) + '</span>' : dash },
            { data: 'lastActiveFormatted', name: 'lastActiveAt', render: (d, t, row) => d
                ? '<span class="text-dark">' + esc(d) + '</span><div class="sub">' + (row.daysInactive === 0 ? 'today' : num(row.daysInactive) + ' days ago') + '</div>'
                : '<span class="text-secondary">Never played</span>' },
            { data: 'platformLabel', name: 'lastPlatform', render: (d) => d ? esc(d) : dash },
            { data: 'location', name: 'countryCode', render: (d, t, row) => d ? '<span class="text-dark">' + esc(d) + '</span>' : dash },
            { data: 'gamesPlayed', name: 'gamesPlayed', render: (d, t, row) => '<span class="text-dark">' + num(d) + '</span><div class="sub">' + num(row.wins) + ' won' + (row.winRate !== null ? ' · ' + row.winRate + '%' : '') + '</div>' },
            { data: 'elo', name: 'elo', render: (d, t, row) => (row.rankName ? '<span class="text-dark">' + esc(row.rankName) + '</span>' : dash) + (d !== null ? '<div class="sub">Elo ' + num(d) + '</div>' : '') },
            { data: 'purchasesCount', name: 'purchasesCount', render: (d, t, row) => d > 0
                ? '<span class="text-dark">' + num(d) + '</span><div class="sub">' + esc(row.totalSpentFormatted) + '</div>' : '<span class="text-secondary">None</span>' },
            { data: 'acumbamailStatus', name: 'acumbamailStatus', render: (d, t, row) => marketingBadge(d, row.marketingLabel) + (row.marketingOptOut ? '<div class="sub">Opted out</div>' : '') },
            { data: null, orderable: false, render: (d, t, row) => '<a href="' + esc(row.showUrl) + '" class="btn btn-sm btn-outline-primary" title="Open player"><i class="bx bx-show"></i></a>' }
        ]
    });

    const reload = () => {
        table.ajax.reload();
        $('#gsExport').attr('href', exportBase + '?' + $.param(filterValues($filters)));
    };
    let timer = null;
    $('#fSearch').on('keyup', () => { clearTimeout(timer); timer = setTimeout(reload, 400); });
    $filters.find('select, input[type=date]').on('change', reload);
    $('#gsClear').on('click', () => { $filters.find('input, select').val(''); reload(); });
});
</script>
@endsection
