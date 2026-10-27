@extends('layouts.master')

@section('title') {{ $gameName }} Usage History @endsection

@section('css')
@include('games.salpakan._styles', ['tables' => true])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Games @endslot
        @slot('li_2') {{ $gameName }} @endslot
        @slot('title') Usage History @endslot
    @endcomponent

    <div id="gs">
        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-joystick"></i><div><b>{{ number_format($stats['today']) }}</b><span>played today (UTC day)</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-calendar"></i><div><b>{{ number_format($stats['week']) }}</b><span>played in the last 7 days</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-group"></i><div><b>{{ number_format($stats['month']) }}</b><span>played in the last 30 days</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-trophy"></i><div><b>{{ number_format($stats['matches30']) }}</b><span>matches in the last 30 days</span></div></div></div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h4 class="card-title mb-1 text-dark">Usage history</h4>
                        <p class="text-secondary mb-0 small">One row per player per day they played. Days are the game server's (UTC); today's row keeps growing until the day is over. Who has stopped playing is under <a href="{{ route('games-salpakan-inactive.index') }}">Inactive Players</a>.</p>
                    </div>
                </div>

                <div class="filters row g-2 mb-3" id="gsFilters">
                    @if ($player)
                        <input type="hidden" data-filter="player" id="fPlayer" value="{{ $player->remotePlayerId }}">
                        <div class="col-12">
                            <span class="chip">Only {{ $player->username ?: 'Player #' . $player->remotePlayerId }}
                                <a href="{{ route('games-salpakan-usage.index') }}" class="ms-1 text-secondary" title="Show everyone"><i class="bx bx-x"></i></a></span>
                        </div>
                    @endif
                    <div class="col-md-4 col-xl-3">
                        <label class="form-label" for="fSearch">Player</label>
                        <input type="text" class="form-control" id="fSearch" data-filter="searchFilter" placeholder="Username or email">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fFrom">From</label>
                        <input type="date" class="form-control" id="fFrom" data-filter="dateFrom" value="{{ $player ? '' : now('UTC')->subDays(29)->toDateString() }}">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fTo">To</label>
                        <input type="date" class="form-control" id="fTo" data-filter="dateTo">
                    </div>
                    <div class="col-6 col-md-2 col-xl-2">
                        <label class="form-label" for="fPlatform">Platform</label>
                        <select class="form-select" id="fPlatform" data-filter="platform">
                            <option value="">Any</option>
                            @foreach (\App\Models\GamePlayer::PLATFORMS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-2 col-xl-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary w-100" id="gsClear"><i class="bx bx-x me-1"></i>Clear</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="gsUsage" class="table table-hover align-middle nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Day (UTC)</th>
                                <th>Player</th>
                                <th>Platform</th>
                                <th class="text-end">Sessions</th>
                                <th class="text-end">Matches</th>
                                <th class="text-end">Won</th>
                                <th class="text-end">Minutes</th>
                                <th>First – last seen</th>
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
    const table = $('#gsUsage').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        searching: false,
        order: [[0, 'desc']],
        pageLength: 50,
        ajax: {
            url: @json(route('games-salpakan-usage.data')),
            data: (d) => Object.assign(d, filterValues($filters)),
            error: () => toastr.error('The usage history could not be loaded.', 'Error!')
        },
        columns: [
            { data: 'dateFormatted', name: 'games_player_activity.activityDate', render: (d) => '<span class="text-dark">' + esc(d) + '</span>' },
            { data: 'playerUsername', name: 'p.username', render: (d, t, row) => {
                const name = esc(d || 'Player #' + row.remotePlayerId);
                const link = row.showUrl ? '<a href="' + esc(row.showUrl) + '" class="fw-semibold text-dark">' + name + '</a>' : '<span class="text-dark">' + name + '</span>';
                return link + (row.playerEmail ? '<div class="sub">' + esc(row.playerEmail) + '</div>' : '');
            } },
            { data: 'platformLabel', name: 'games_player_activity.platform', render: (d) => d ? esc(d) : dash },
            { data: 'sessions', name: 'games_player_activity.sessions', className: 'text-end', render: (d) => num(d) },
            { data: 'matchesPlayed', name: 'games_player_activity.matchesPlayed', className: 'text-end', render: (d) => num(d) },
            { data: 'matchesWon', name: 'games_player_activity.matchesWon', className: 'text-end', render: (d) => num(d) },
            { data: 'minutesPlayed', name: 'games_player_activity.minutesPlayed', className: 'text-end', render: (d) => num(d) },
            { data: 'firstSeenFormatted', orderable: false, render: (d, t, row) => '<span class="sub">' + esc(d || '—') + ' – ' + esc(row.lastSeenFormatted || '—') + '</span>' }
        ]
    });

    let timer = null;
    $('#fSearch').on('keyup', () => { clearTimeout(timer); timer = setTimeout(() => table.ajax.reload(), 400); });
    $filters.find('select, input[type=date]').on('change', () => table.ajax.reload());
    $('#gsClear').on('click', () => { $filters.find('input:not([type=hidden]), select').val(''); table.ajax.reload(); });
});
</script>
@endsection
