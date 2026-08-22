@extends('layouts.master')

@section('title') Member Drawings @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@include('aniSensoAdmin.media.partials.styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Media @endslot
        @slot('title') Drawings @endslot
    @endcomponent

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1">What members have drawn</h4>
                    <p class="card-title-desc mb-0">
                        Every drawing lives inside a note — the notebook, a sticky on the board, or a
                        day's note — so this is all three shelves at once. Removing one takes the
                        picture out of its note and leaves the words alone.
                    </p>
                </div>
                <button type="button" class="btn btn-light btn-sm" id="reload"><i class="bx bx-refresh"></i> Refresh</button>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-5">
                    <label class="form-label mb-1" for="fSearch">Search</label>
                    <input type="text" class="form-control" id="fSearch" placeholder="Note, client, email or season…">
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
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover" id="mediaTable" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:90px">Drawing</th>
                            <th>Note</th>
                            <th>Client</th>
                            <th>Season</th>
                            <th>Shelf</th>
                            <th>Last touched</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
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
            url: "{{ route('anisenso-media-drawings.data') }}",
            data: function (d) {
                d.searchFilter = $('#fSearch').val();
                d.shelf = $('#fShelf').val();
            },
            error: function () { toastr.error('Could not load the drawings.'); }
        },
        columns: [
            { data: 'url', render: function (u, t, row) {
                return u ? '<img src="' + esc(u) + '" class="media-thumb js-open" data-url="' + esc(u) +
                           '" data-caption="' + esc(row.title) + '" alt="">'
                         : '<span class="text-secondary">—</span>';
            } },
            { data: 'title', render: function (d, t, row) {
                return '<div class="text-dark">' + esc(d) + '</div>' +
                       (row.words ? '<small class="text-secondary">' + esc(row.words) + '</small>' : '') +
                       (row.team ? ' <span class="badge bg-info">From the team board</span>' : '');
            } },
            { data: 'clientName', render: function (d, t, row) {
                return '<div class="text-dark">' + esc(d || '—') + '</div><small class="text-secondary">' + esc(row.clientEmail || '') + '</small>';
            } },
            { data: 'scheduleTitle', render: (d) => d ? esc(d) : '<span class="text-secondary">—</span>' },
            { data: 'shelf', render: (d) => '<span class="kind-chip">' + esc(SHELVES[d] || d) + '</span>' },
            { data: 'when', render: (d) => esc(d) },
            { data: null, className: 'text-end', render: function (d, t, row) {
                return '<button class="btn btn-sm btn-soft-danger js-del" data-shelf="' + esc(row.shelf) +
                       '" data-note="' + row.noteId + '" data-index="' + row.index + '"><i class="bx bx-trash"></i></button>';
            } }
        ]
    });

    let typing = null;
    $('#fSearch').on('keyup', function () { clearTimeout(typing); typing = setTimeout(() => table.ajax.reload(), 350); });
    $('#fShelf').on('change', () => table.ajax.reload());
    $('#reload').on('click', () => table.ajax.reload(null, false));

    $('#mediaTable').on('click', '.js-del', function () {
        if (!confirm('Remove this drawing from its note? The note itself stays.')) return;
        const b = $(this);
        $.ajax({
            url: '{{ url('/anisenso-media-drawings-delete') }}?shelf=' + b.data('shelf') +
                 '&noteId=' + b.data('note') + '&index=' + b.data('index'),
            type: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
            success: (res) => { res.success ? toastr.success(res.message) : toastr.error(res.message); table.ajax.reload(null, false); },
            error: () => toastr.error('Could not remove that drawing.')
        });
    });
});
</script>
@endsection
