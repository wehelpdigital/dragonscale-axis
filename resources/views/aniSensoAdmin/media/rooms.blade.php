@extends('layouts.master')

@section('title') Collab Rooms @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@include('aniSensoAdmin.media.partials.styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Media @endslot
        @slot('title') Collab Rooms @endslot
    @endcomponent

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1">Where the teams work</h4>
                    <p class="card-title-desc mb-0">
                        A room per season: what the team said, what they recorded, what the whiteboard
                        holds, and how often they asked the technician.
                    </p>
                </div>
                <button type="button" class="btn btn-light btn-sm" id="reload"><i class="bx bx-refresh"></i> Refresh</button>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-5">
                    <label class="form-label mb-1" for="fSearch">Search</label>
                    <input type="text" class="form-control" id="fSearch" placeholder="Season, client or email…">
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="fEmpty">
                        <label class="form-check-label" for="fEmpty">Show seasons whose room is empty</label>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover" id="mediaTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Season</th>
                            <th>Client</th>
                            <th class="text-end">Chat</th>
                            <th class="text-end">Board pages</th>
                            <th class="text-end">Recordings</th>
                            <th class="text-end">AI sessions</th>
                            <th>Last said</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="roomModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="roomTitle">Room</h5>
                        <small class="text-secondary" id="roomSub"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="roomBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>

    @include('aniSensoAdmin.media.partials.viewer')
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(function () {
    const esc = (v) => $('<div>').text(v == null ? '' : v).html();
    const CSRF = "{{ csrf_token() }}";
    let openRoomId = null;

    const table = $('#mediaTable').DataTable({
        processing: true, serverSide: true, searching: false, ordering: false,
        pageLength: 25, lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('anisenso-media-rooms.data') }}",
            data: function (d) {
                d.searchFilter = $('#fSearch').val();
                d.empty = $('#fEmpty').is(':checked') ? 'show' : '';
            },
            error: function () { toastr.error('Could not load the rooms.'); }
        },
        columns: [
            { data: 'scheduleTitle', render: (d) => '<span class="text-dark">' + esc(d || 'Untitled season') + '</span>' },
            { data: 'clientName', render: function (d, t, row) {
                return '<div class="text-dark">' + esc(d || '—') + '</div><small class="text-secondary">' + esc(row.clientEmail || '') + '</small>';
            } },
            { data: 'chatCount', className: 'text-end' },
            { data: 'boardPages', className: 'text-end' },
            { data: 'recordings', className: 'text-end' },
            { data: 'aiSessions', className: 'text-end' },
            { data: 'lastChatAt', render: (d) => esc(d) },
            { data: null, className: 'text-end', render: function (d, t, row) {
                return '<button class="btn btn-sm btn-outline-primary js-open-room" data-id="' + row.id + '">Look inside</button>';
            } }
        ]
    });

    let typing = null;
    $('#fSearch').on('keyup', function () { clearTimeout(typing); typing = setTimeout(() => table.ajax.reload(), 350); });
    $('#fEmpty').on('change', () => table.ajax.reload());
    $('#reload').on('click', () => table.ajax.reload(null, false));

    function drawRoom(d) {
        let html = '';
        html += '<h6 class="text-dark mb-2"><i class="bx bx-chat me-1"></i>Team chat</h6>';
        if (!d.chat.length) {
            html += '<p class="text-secondary">Nothing said in this room yet.</p>';
        } else {
            html += d.chat.map(function (m) {
                return '<div class="room-turn"><div class="flex-grow-1">' +
                    '<div class="room-who">' + esc(m.who) + ' <span class="room-meta">· ' + esc(m.at) + '</span></div>' +
                    (m.body ? '<div class="room-body">' + esc(m.body) + '</div>' : '') +
                    (m.photo ? '<img src="' + esc(m.photo) + '" class="media-thumb is-tall js-open mt-1" data-url="' + esc(m.photo) + '" data-caption="' + esc(m.who) + '" alt="">' : '') +
                    '</div><div><button class="btn btn-sm btn-soft-danger js-del-msg" data-id="' + m.id + '"><i class="bx bx-trash"></i></button></div></div>';
            }).join('');
        }

        html += '<h6 class="text-dark mt-4 mb-2"><i class="bx bx-video me-1"></i>Recordings</h6>';
        if (!d.recordings.length) {
            html += '<p class="text-secondary">Nothing recorded.</p>';
        } else {
            html += d.recordings.map(function (r) {
                return '<div class="room-turn"><div class="flex-grow-1">' +
                    '<div class="room-who">' + esc(r.title) + ' <span class="room-meta">· ' + esc(r.kind) + ' · ' +
                    (r.seconds ? r.seconds + 's · ' : '') + esc(r.at) + (r.who ? ' · ' + esc(r.who) : '') + '</span></div>' +
                    (r.url ? '<a href="' + esc(r.url) + '" target="_blank" class="small">Open the file</a>' : '') +
                    '</div><div><button class="btn btn-sm btn-soft-danger js-del-rec" data-id="' + r.id + '"><i class="bx bx-trash"></i></button></div></div>';
            }).join('');
        }

        html += '<h6 class="text-dark mt-4 mb-2"><i class="bx bx-edit me-1"></i>Whiteboard</h6>';
        html += d.pages.length
            ? '<p class="mb-0">' + d.pages.map(p => '<span class="kind-chip">Page ' + p.page + ' · ' + esc(p.orientation) + ' · ' + esc(p.at) + '</span>').join('') + '</p>'
            : '<p class="text-secondary mb-0">The board is empty.</p>';

        $('#roomBody').html(html);
    }

    function loadRoom(id) {
        openRoomId = id;
        $('#roomBody').html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-3"></i></div>');
        $.get('{{ url('/anisenso-media-rooms-one') }}?id=' + id, function (res) {
            if (!res.success) { $('#roomBody').html('<p class="text-secondary mb-0">' + esc(res.message) + '</p>'); return; }
            $('#roomTitle').text(res.data.title || 'Room');
            $('#roomSub').text([res.data.clientName || res.data.clientEmail].filter(Boolean).join(' · '));
            drawRoom(res.data);
        }).fail(() => $('#roomBody').html('<p class="text-secondary mb-0">Could not read that room.</p>'));
    }

    $('#mediaTable').on('click', '.js-open-room', function () {
        $('#roomModal').modal('show');
        loadRoom($(this).data('id'));
    });

    function remove(url, ok) {
        $.ajax({ url: url, type: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
            success: (res) => { res.success ? toastr.success(res.message) : toastr.error(res.message); ok(); },
            error: () => toastr.error('Could not remove that.') });
    }

    $('#roomBody').on('click', '.js-del-msg', function () {
        if (!confirm('Remove this message from the team chat?')) return;
        remove('{{ url('/anisenso-media-rooms-message-delete') }}?id=' + $(this).data('id'), () => loadRoom(openRoomId));
    });
    $('#roomBody').on('click', '.js-del-rec', function () {
        if (!confirm('Remove this recording?')) return;
        remove('{{ url('/anisenso-media-rooms-recording-delete') }}?id=' + $(this).data('id'), () => loadRoom(openRoomId));
    });
});
</script>
@endsection
