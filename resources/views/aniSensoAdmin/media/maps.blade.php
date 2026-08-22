@extends('layouts.master')

@section('title') Member Maps @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@include('aniSensoAdmin.media.partials.styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Media @endslot
        @slot('title') Maps @endslot
    @endcomponent

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1">Saved maps</h4>
                    <p class="card-title-desc mb-0">
                        Every map a member has kept — from the Maps module or from a Collab Room —
                        and what each one has drawn on it.
                    </p>
                </div>
                <button type="button" class="btn btn-light btn-sm" id="reload"><i class="bx bx-refresh"></i> Refresh</button>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label mb-1" for="fSearch">Search</label>
                    <input type="text" class="form-control" id="fSearch" placeholder="Title, client, email or season…">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1" for="fSource">Saved from</label>
                    <select class="form-select" id="fSource">
                        <option value="">Anywhere</option>
                        <option value="module">The Maps module</option>
                        <option value="collab">A Collab Room</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1" for="fFrom">From</label>
                    <input type="date" class="form-control" id="fFrom">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1" for="fTo">To</label>
                    <input type="date" class="form-control" id="fTo">
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover" id="mediaTable" style="width:100%">
                    <thead>
                        <tr>
                            <th>Map</th>
                            <th>Client</th>
                            <th>Season</th>
                            <th>Saved from</th>
                            <th class="text-end">Shapes</th>
                            <th>Last touched</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="mapModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="mapTitle">Map</h5>
                        <small class="text-secondary" id="mapSub"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="mapBody"></div>
                <div class="modal-footer"><button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button></div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script>
$(function () {
    const esc = (v) => $('<div>').text(v == null ? '' : v).html();
    const CSRF = "{{ csrf_token() }}";

    const table = $('#mediaTable').DataTable({
        processing: true, serverSide: true, searching: false,
        order: [[5, 'desc']], pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('anisenso-media-maps.data') }}",
            data: function (d) {
                d.searchFilter = $('#fSearch').val();
                d.source = $('#fSource').val();
                d.from = $('#fFrom').val();
                d.to = $('#fTo').val();
            },
            error: function () { toastr.error('Could not load the maps.'); }
        },
        columns: [
            { data: 'title', name: 'm.title', render: (d) => '<span class="text-dark">' + esc(d || 'Untitled map') + '</span>' },
            { data: 'clientName', name: 'u.firstName', render: function (d, t, row) {
                return '<div class="text-dark">' + esc(d || '—') + '</div><small class="text-secondary">' + esc(row.clientEmail || '') + '</small>';
            } },
            { data: 'scheduleTitle', name: 's.title', render: (d) => d ? esc(d) : '<span class="text-secondary">—</span>' },
            { data: 'source', name: 'm.source', render: (d) => '<span class="kind-chip">' + esc(d || 'module') + '</span>' },
            { data: 'shapes', orderable: false, className: 'text-end', render: (d) => Number(d || 0).toLocaleString() },
            { data: 'updated_at', name: 'm.updated_at', render: (d) => esc(d) },
            { data: null, orderable: false, className: 'text-end', render: function (d, t, row) {
                return '<button class="btn btn-sm btn-outline-primary js-view me-1" data-id="' + row.id + '">Look</button>' +
                       '<button class="btn btn-sm btn-soft-danger js-del" data-id="' + row.id + '"><i class="bx bx-trash"></i></button>';
            } }
        ]
    });

    let typing = null;
    $('#fSearch').on('keyup', function () { clearTimeout(typing); typing = setTimeout(() => table.ajax.reload(), 350); });
    $('#fSource, #fFrom, #fTo').on('change', () => table.ajax.reload());
    $('#reload').on('click', () => table.ajax.reload(null, false));

    $('#mediaTable').on('click', '.js-view', function () {
        $('#mapBody').html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-3"></i></div>');
        $('#mapModal').modal('show');
        $.get('{{ url('/anisenso-media-maps-one') }}?id=' + $(this).data('id'), function (res) {
            if (!res.success) { $('#mapBody').html('<p class="text-secondary mb-0">' + esc(res.message) + '</p>'); return; }
            const d = res.data;
            $('#mapTitle').text(d.title || 'Untitled map');
            $('#mapSub').text([d.clientName || d.clientEmail, d.scheduleTitle, d.when].filter(Boolean).join(' · '));
            let html = '<p class="mb-2"><strong>' + d.shapes + '</strong> ' + (d.shapes === 1 ? 'shape' : 'shapes') +
                       ' saved from <span class="kind-chip">' + esc(d.source || 'module') + '</span></p>';
            const kinds = Object.keys(d.kinds || {});
            if (kinds.length) {
                html += '<p class="mb-2">' + kinds.map(k => '<span class="kind-chip">' + esc(k) + ' × ' + d.kinds[k] + '</span>').join('') + '</p>';
            }
            if ((d.labels || []).length) {
                html += '<h6 class="text-dark mt-3 mb-1">What is written on it</h6><p class="mb-0">' +
                        d.labels.map(l => '<span class="kind-chip">' + esc(l) + '</span>').join('') + '</p>';
            }
            $('#mapBody').html(html);
        }).fail(() => $('#mapBody').html('<p class="text-secondary mb-0">Could not read that map.</p>'));
    });

    $('#mediaTable').on('click', '.js-del', function () {
        if (!confirm('Remove this saved map from the member\'s shelf?')) return;
        $.ajax({
            url: '{{ url('/anisenso-media-maps-delete') }}?id=' + $(this).data('id'),
            type: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
            success: (res) => { res.success ? toastr.success(res.message) : toastr.error(res.message); table.ajax.reload(null, false); },
            error: () => toastr.error('Could not remove that map.')
        });
    });
});
</script>
@endsection
