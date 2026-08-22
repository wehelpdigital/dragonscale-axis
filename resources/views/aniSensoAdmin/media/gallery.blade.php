@extends('layouts.master')

@section('title') Member Gallery @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@include('aniSensoAdmin.media.partials.styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Media @endslot
        @slot('title') Gallery @endslot
    @endcomponent

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1">Member photos</h4>
                    <p class="card-title-desc mb-0">Every picture filed in a season's gallery, by whoever filed it.</p>
                </div>
                <button type="button" class="btn btn-light btn-sm" id="reload"><i class="bx bx-refresh"></i> Refresh</button>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label mb-1" for="fSearch">Search</label>
                    <input type="text" class="form-control" id="fSearch" placeholder="Caption, client, email or season…">
                </div>
                <div class="col-md-3">
                    <label class="form-label mb-1" for="fKind">Filed by</label>
                    <select class="form-select" id="fKind">
                        <option value="">Anyone</option>
                        <option value="personal">The owner</option>
                        <option value="team">The team</option>
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
                            <th style="width:90px">Photo</th>
                            <th>Caption</th>
                            <th>Client</th>
                            <th>Season</th>
                            <th>Album</th>
                            <th>Filed</th>
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

    const table = $('#mediaTable').DataTable({
        processing: true, serverSide: true, searching: false,
        order: [[5, 'desc']], pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('anisenso-media-gallery.data') }}",
            data: function (d) {
                d.searchFilter = $('#fSearch').val();
                d.kind = $('#fKind').val();
                d.from = $('#fFrom').val();
                d.to = $('#fTo').val();
            },
            error: function () { toastr.error('Could not load the gallery.'); }
        },
        columns: [
            { data: 'url', orderable: false, render: function (u, t, row) {
                return u ? '<img src="' + esc(u) + '" class="media-thumb js-open" data-url="' + esc(u) +
                           '" data-caption="' + esc(row.caption || row.fileName) + '" alt="">'
                         : '<span class="text-secondary">—</span>';
            } },
            { data: 'caption', name: 'g.caption', render: function (d, t, row) {
                return '<div class="text-dark">' + esc(d || '—') + '</div>' +
                       '<small class="text-secondary">' + esc(row.fileName) + '</small>' +
                       (row.isTeam ? ' <span class="badge bg-info">Team</span>' : '');
            } },
            { data: 'clientName', name: 'u.firstName', render: function (d, t, row) {
                return '<div class="text-dark">' + esc(d || '—') + '</div><small class="text-secondary">' + esc(row.clientEmail || '') + '</small>';
            } },
            { data: 'scheduleTitle', name: 's.title', render: (d) => d ? esc(d) : '<span class="text-secondary">—</span>' },
            { data: 'albumTitle', name: 'a.title', render: (d) => d ? esc(d) : '<span class="text-secondary">All photos</span>' },
            { data: 'created_at', name: 'g.created_at', render: (d) => esc(d) },
            { data: null, orderable: false, className: 'text-end', render: function (d, t, row) {
                return '<button class="btn btn-sm btn-soft-danger js-del" data-id="' + row.id + '"><i class="bx bx-trash"></i></button>';
            } }
        ]
    });

    let typing = null;
    $('#fSearch').on('keyup', function () { clearTimeout(typing); typing = setTimeout(() => table.ajax.reload(), 350); });
    $('#fKind, #fFrom, #fTo').on('change', () => table.ajax.reload());
    $('#reload').on('click', () => table.ajax.reload(null, false));

    $('#mediaTable').on('click', '.js-del', function () {
        if (!confirm('Remove this photo from the member\'s gallery?')) return;
        $.ajax({
            url: '{{ url('/anisenso-media-gallery-delete') }}?id=' + $(this).data('id'),
            type: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
            success: (res) => { res.success ? toastr.success(res.message) : toastr.error(res.message); table.ajax.reload(null, false); },
            error: () => toastr.error('Could not remove that photo.')
        });
    });
});
</script>
@endsection
