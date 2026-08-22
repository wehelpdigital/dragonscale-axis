@extends('layouts.master')

@section('title') Reels &amp; Stories @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
@include('aniSensoAdmin.media.partials.styles')
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Media @endslot
        @slot('title') Reels &amp; Stories @endslot
    @endcomponent

    <div class="card">
        <div class="card-body">
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                <div>
                    <h4 class="card-title mb-1">What members have filmed</h4>
                    <p class="card-title-desc mb-0">
                        Reels and stories are wall posts that play rather than read, which is why they
                        were only ever reachable through the member who posted one. Watch, and take
                        down what does not belong.
                    </p>
                </div>
                <button type="button" class="btn btn-light btn-sm" id="reload"><i class="bx bx-refresh"></i> Refresh</button>
            </div>

            <div class="row g-2 mb-3">
                <div class="col-md-5">
                    <label class="form-label mb-1" for="fSearch">Search</label>
                    <input type="text" class="form-control" id="fSearch" placeholder="Caption, client, email or sound…">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1" for="fFrom">From</label>
                    <input type="date" class="form-control" id="fFrom">
                </div>
                <div class="col-md-2">
                    <label class="form-label mb-1" for="fTo">To</label>
                    <input type="date" class="form-control" id="fTo">
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <div class="form-check form-switch">
                        <input class="form-check-input" type="checkbox" id="fRestricted">
                        <label class="form-check-label" for="fRestricted">Only ones already restricted</label>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table align-middle table-hover" id="mediaTable" style="width:100%">
                    <thead>
                        <tr>
                            <th style="width:90px">Frame</th>
                            <th>Caption</th>
                            <th>Client</th>
                            <th class="text-end">Length</th>
                            <th class="text-end">Reactions</th>
                            <th class="text-end">Comments</th>
                            <th>Posted</th>
                            <th></th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>

    <div class="modal fade" id="reelModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0" id="reelTitle">Reel</h5>
                        <small class="text-secondary" id="reelSub"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body text-center" id="reelBody"></div>
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

    const table = $('#mediaTable').DataTable({
        processing: true, serverSide: true, searching: false,
        order: [[6, 'desc']], pageLength: 25,
        lengthMenu: [[10, 25, 50, 100], [10, 25, 50, 100]],
        ajax: {
            url: "{{ route('anisenso-media-reels.data') }}",
            data: function (d) {
                d.searchFilter = $('#fSearch').val();
                d.from = $('#fFrom').val();
                d.to = $('#fTo').val();
                d.restricted = $('#fRestricted').is(':checked') ? 'yes' : '';
            },
            error: function () { toastr.error('Could not load the reels.'); }
        },
        columns: [
            { data: 'posterUrl', orderable: false, render: function (u) {
                return u ? '<img src="' + esc(u) + '" class="media-thumb is-tall" alt="">'
                         : '<span class="media-gone"><i class="bx bx-video"></i></span>';
            } },
            { data: 'body', name: 'p.body', render: function (d, t, row) {
                return '<div class="text-dark">' + esc((d || '').slice(0, 90) || 'No caption') + '</div>' +
                       (row.audioTitle ? '<small class="text-secondary">&#9834; ' + esc(row.audioTitle) + '</small>' : '') +
                       (row.isRestricted ? ' <span class="badge bg-warning text-dark">Restricted</span>' : '');
            } },
            { data: 'clientName', name: 'u.firstName', render: function (d, t, row) {
                return '<div class="text-dark">' + esc(d || '—') + '</div><small class="text-secondary">' + esc(row.clientEmail || '') + '</small>';
            } },
            { data: 'durationSec', name: 'p.durationSec', className: 'text-end', render: (d) => d ? d + 's' : '—' },
            { data: 'reactions', orderable: false, className: 'text-end' },
            { data: 'comments', orderable: false, className: 'text-end' },
            { data: 'created_at', name: 'p.created_at', render: (d) => esc(d) },
            { data: null, orderable: false, className: 'text-end', render: function (d, t, row) {
                return '<button class="btn btn-sm btn-outline-primary js-play me-1" data-url="' + esc(row.videoUrl || '') +
                       '" data-caption="' + esc(row.body || '') + '" data-who="' + esc(row.clientName || '') + '">Watch</button>' +
                       '<button class="btn btn-sm btn-soft-danger js-del" data-id="' + row.id + '"><i class="bx bx-trash"></i></button>';
            } }
        ]
    });

    let typing = null;
    $('#fSearch').on('keyup', function () { clearTimeout(typing); typing = setTimeout(() => table.ajax.reload(), 350); });
    $('#fFrom, #fTo, #fRestricted').on('change', () => table.ajax.reload());
    $('#reload').on('click', () => table.ajax.reload(null, false));

    $('#mediaTable').on('click', '.js-play', function () {
        const url = $(this).data('url');
        $('#reelTitle').text($(this).data('caption') || 'Reel');
        $('#reelSub').text($(this).data('who') || '');
        $('#reelBody').html(url
            ? '<video src="' + esc(url) + '" controls playsinline style="max-height:70vh;max-width:100%;border-radius:8px"></video>'
            : '<p class="text-secondary mb-0">The video file is not on the disk any more.</p>');
        $('#reelModal').modal('show');
    });
    $('#reelModal').on('hidden.bs.modal', () => $('#reelBody').empty());

    $('#mediaTable').on('click', '.js-del', function () {
        if (!confirm('Take this reel down? Its comments go with it.')) return;
        $.ajax({
            url: '{{ url('/anisenso-media-reels-delete') }}?id=' + $(this).data('id'),
            type: 'DELETE', headers: { 'X-CSRF-TOKEN': CSRF },
            success: (res) => { res.success ? toastr.success(res.message) : toastr.error(res.message); table.ajax.reload(null, false); },
            error: () => toastr.error('Could not remove that reel.')
        });
    });
});
</script>
@endsection
