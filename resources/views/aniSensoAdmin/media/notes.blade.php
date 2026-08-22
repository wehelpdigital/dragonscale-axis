@extends('layouts.master')

@section('title') Member Notes @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@include('aniSensoAdmin.media.partials.styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Media @endslot
        @slot('title') Notes @endslot
    @endcomponent

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1">What members have written</h4>
                    <p class="card-title-desc mb-0">
                        The notebook, the board's stickies and the day notes — all three shelves, with
                        whatever is attached to each.
                    </p>
                </div>
                <button type="button" class="btn btn-light btn-sm" id="reload"><i class="bx bx-refresh"></i> Refresh</button>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-5">
                    <label class="form-label mb-1" for="fSearch">Search</label>
                    <input type="text" class="form-control" id="fSearch" placeholder="Words, client, email or season…">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1" for="fShelf">Where it lives</label>
                    <select class="form-select" id="fShelf">
                        <option value="">Anywhere</option>
                        <option value="note">The notebook</option>
                        <option value="inline">A sticky on the board</option>
                        <option value="date">A day's note</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="fMedia">
                        <label class="form-check-label" for="fMedia">Only notes with something attached</label>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover" id="mediaTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Note</th>
                            <th>Client</th>
                            <th>Season</th>
                            <th>Shelf</th>
                            <th class="text-end">Attached</th>
                            <th>Last touched</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="noteModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="noteTitle">Note</h5>
                        <small class="text-secondary" id="noteSub"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="noteBody"></div>
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
    const SHELVES = { note: 'The notebook', inline: 'Board sticky', date: "A day's note" };

    const table = $('#mediaTable').DataTable({
        processing: true, serverSide: true, searching: false, ordering: false,
        pageLength: 25, lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('anisenso-media-notes.data') }}",
            data: function (d) {
                d.searchFilter = $('#fSearch').val();
                d.shelf = $('#fShelf').val();
                d.media = $('#fMedia').is(':checked') ? 'yes' : '';
            },
            error: function () { toastr.error('Could not load the notes.'); }
        },
        columns: [
            { data: 'title', render: function (d, t, row) {
                return '<div class="text-dark">' + esc(d) + '</div>' +
                       (row.words ? '<small class="text-secondary">' + esc(row.words) + '</small>' : '');
            } },
            { data: 'clientName', render: function (d, t, row) {
                return '<div class="text-dark">' + esc(d || '—') + '</div><small class="text-secondary">' + esc(row.clientEmail || '') + '</small>';
            } },
            { data: 'scheduleTitle', render: (d) => d ? esc(d) : '<span class="text-secondary">—</span>' },
            { data: 'shelf', render: (d) => '<span class="kind-chip">' + esc(SHELVES[d] || d) + '</span>' },
            { data: 'attachments', className: 'text-end', render: (d) => d ? d : '<span class="text-secondary">—</span>' },
            { data: 'when', render: (d) => esc(d) },
            { data: null, className: 'text-end', render: function (d, t, row) {
                return '<button class="btn btn-sm btn-outline-primary js-read me-1" data-shelf="' + esc(row.shelf) + '" data-id="' + row.id + '">Read</button>' +
                       '<button class="btn btn-sm btn-soft-danger js-del" data-shelf="' + esc(row.shelf) + '" data-id="' + row.id + '"><i class="bx bx-trash"></i></button>';
            } }
        ]
    });

    let typing = null;
    $('#fSearch').on('keyup', function () { clearTimeout(typing); typing = setTimeout(() => table.ajax.reload(), 350); });
    $('#fShelf, #fMedia').on('change', () => table.ajax.reload());
    $('#reload').on('click', () => table.ajax.reload(null, false));

    $('#mediaTable').on('click', '.js-read', function () {
        const b = $(this);
        $('#noteBody').html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-3"></i></div>');
        $('#noteModal').modal('show');
        $.get('{{ url('/anisenso-media-notes-one') }}?shelf=' + b.data('shelf') + '&id=' + b.data('id'), function (res) {
            if (!res.success) { $('#noteBody').html('<p class="text-secondary mb-0">' + esc(res.message) + '</p>'); return; }
            const d = res.data;
            $('#noteTitle').text(d.title);
            $('#noteSub').text([d.clientName || d.clientEmail, d.scheduleTitle, d.when].filter(Boolean).join(' · '));
            let html = d.body ? '<p class="text-dark" style="white-space:pre-wrap">' + esc(d.body) + '</p>'
                              : '<p class="text-secondary">No words in this note.</p>';
            if (d.media.length) {
                html += '<h6 class="text-dark mt-3 mb-2">Attached</h6><div class="d-flex flex-wrap gap-2">';
                html += d.media.map(function (m) {
                    if (!m.url) return '<span class="kind-chip">' + esc(m.name || m.type) + '</span>';
                    return m.type === 'video'
                        ? '<a href="' + esc(m.url) + '" target="_blank" class="kind-chip">&#9654; ' + esc(m.name) + '</a>'
                        : '<img src="' + esc(m.url) + '" class="media-thumb is-tall js-open" data-url="' + esc(m.url) + '" data-caption="' + esc(m.name) + '" alt="">';
                }).join('');
                html += '</div>';
            }
            $('#noteBody').html(html);
        }).fail(() => $('#noteBody').html('<p class="text-secondary mb-0">Could not read that note.</p>'));
    });

    $('#mediaTable').on('click', '.js-del', function () {
        if (!confirm('Remove this note from the member app?')) return;
        const b = $(this);
        $.ajax({
            url: '{{ url('/anisenso-media-notes-delete') }}?shelf=' + b.data('shelf') + '&id=' + b.data('id'),
            type: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
            success: (res) => { res.success ? toastr.success(res.message) : toastr.error(res.message); table.ajax.reload(null, false); },
            error: () => toastr.error('Could not remove that note.')
        });
    });
});
</script>
@endsection
