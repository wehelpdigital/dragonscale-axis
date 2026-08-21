@extends('layouts.master')

@section('title') Batch Search @endsection

@section('css')
    <link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
    <style>
        .batch-progress { height: 6px; border-radius: 3px; }
        .batch-name-cell { cursor: pointer; }
        .batch-name-cell:hover .rename-hint { visibility: visible; }
        .rename-hint { visibility: hidden; }
        .stat-mini { font-size: 12px; }
    </style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Lead Finder @endslot
        @slot('title') Batch Search @endslot
    @endcomponent

    <div class="row">
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle" style="background:#556ee6;">
                            <i class="bx bx-layer text-white font-size-20"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-secondary mb-1">Total Searches</p>
                        <h4 class="mb-0 text-dark">{{ number_format($totalBatches) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle" style="background:#34c38f;">
                            <i class="bx bx-check-double text-white font-size-20"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-secondary mb-1">Fully Processed</p>
                        <h4 class="mb-0 text-dark">{{ number_format($completeBatches) }}</h4>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card">
                <div class="card-body d-flex align-items-center">
                    <div class="avatar-sm me-3">
                        <span class="avatar-title rounded-circle" style="background:#50a5f1;">
                            <i class="bx bx-mail-send text-white font-size-20"></i>
                        </span>
                    </div>
                    <div>
                        <p class="text-secondary mb-1">Verified Emails</p>
                        <h4 class="mb-0 text-dark" id="totalValidEmails">&mdash;</h4>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-5">
                    <label for="filterSearch" class="form-label text-dark">Search</label>
                    <input type="text" class="form-control" id="filterSearch" autocomplete="off"
                           placeholder="Name, business type or region">
                </div>
                <div class="col-md-4">
                    <label for="filterStatus" class="form-label text-dark">Stage</label>
                    <select class="form-select" id="filterStatus">
                        <option value="">All stages</option>
                        @foreach($statuses as $value => $label)
                            <option value="{{ $value }}">{{ $label }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button type="button" class="btn btn-light w-100" id="resetFilters">
                        <i class="bx bx-reset me-1"></i>Reset
                    </button>
                </div>
            </div>

            <div class="alert alert-light border">
                <i class="bx bx-info-circle me-1 text-dark"></i>
                <span class="text-dark">A search is <strong>Complete</strong> once every grid cell is worked,
                every lead has been through the email hunt, and every address found has been checked by the
                verifier. Click a name to rename it.</span>
            </div>

            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="text-dark">Search</th>
                            <th class="text-dark">Stage</th>
                            <th class="text-dark" style="min-width:170px;">Progress</th>
                            <th class="text-dark text-center">Leads</th>
                            <th class="text-dark text-center">With Email</th>
                            <th class="text-dark text-center">Verified Good</th>
                            <th class="text-dark">Started</th>
                            <th class="text-dark text-center">Actions</th>
                        </tr>
                    </thead>
                    <tbody id="batchRows">
                        <tr><td colspan="8" class="text-center py-4 text-secondary">
                            <i class="bx bx-loader-alt bx-spin me-1"></i>Loading searches&hellip;
                        </td></tr>
                    </tbody>
                </table>
            </div>

            <nav class="mt-3">
                <ul class="pagination pagination-sm mb-0 justify-content-end" id="batchPager"></ul>
            </nav>
        </div>
    </div>

    <!-- Rename -->
    <div class="modal fade" id="renameModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark"><i class="bx bx-edit me-2"></i>Rename Search</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="renameInput" class="form-label text-dark">Name</label>
                    <input type="text" class="form-control" id="renameInput" maxlength="190">
                    <small class="text-secondary">Leave it blank to go back to the automatic name.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="confirmRename">Save</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Details -->
    <div class="modal fade" id="batchModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark" id="batchModalTitle">Search</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="batchModalBody"></div>
            </div>
        </div>
    </div>

    <!-- Delete -->
    <div class="modal fade" id="deleteModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark"><i class="bx bx-trash text-danger me-2"></i>Remove Search</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-1">Remove <strong id="deleteItemName" class="text-dark"></strong> from this list?</p>
                    <small class="text-secondary">The leads it found are kept. They are deduplicated by Google place id,
                    so deleting them would only let a later search pay to find the same businesses again.</small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmDelete">Remove</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(function () {
    toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };

    var CSRF_TOKEN = '{{ csrf_token() }}';
    var DATA_URL   = '{{ route('outreach.batches.data') }}';
    var SHOW_URL   = '{{ route('outreach.batches.show') }}';
    var RENAME_URL = '{{ route('outreach.batches.rename') }}';
    var DELETE_URL = '{{ route('outreach.batches.destroy') }}';

    var page = 1, renameTarget = null, deleteTarget = null, pollTimer = null, searchTimer = null;

    function escapeHtml(value) {
        return $('<div>').text(value === null || value === undefined ? '' : String(value)).html();
    }

    function filters() {
        return { search: $('#filterSearch').val(), status: $('#filterStatus').val(), page: page };
    }

    // Only poll while something is actually moving. A list of finished searches
    // cannot change on its own, and a timer left running would reload it forever.
    function schedulePoll(rows) {
        if (pollTimer) { clearTimeout(pollTimer); pollTimer = null; }
        var busy = rows.some(function (r) { return r.status !== 'complete' && r.status !== 'cancelled'; });
        if (busy) pollTimer = setTimeout(load, 15000);
    }

    function load() {
        $.ajax({
            url: DATA_URL, type: 'GET', data: filters(),
            success: function (res) {
                if (!res || !res.success) { toastr.error((res && res.message) || 'Could not load searches.'); return; }
                render(res.data);
            },
            error: function () { toastr.error('Could not load searches.'); }
        });
    }

    function render(paginator) {
        var rows = paginator.data || [];
        var $body = $('#batchRows').empty();

        if (!rows.length) {
            $body.append(
                '<tr><td colspan="8" class="text-center py-4">' +
                '<i class="bx bx-layer text-secondary" style="font-size:2.5rem;"></i>' +
                '<p class="text-dark mt-2 mb-1">No searches yet.</p>' +
                '<small class="text-secondary">Run one from Find Leads and it will appear here.</small>' +
                '</td></tr>'
            );
            $('#batchPager').empty();
            $('#totalValidEmails').text('0');
            return;
        }

        var totalValid = 0;

        rows.forEach(function (r) {
            totalValid += r.leadsValid;
            var bar = r.status === 'complete' ? 'bg-success' : 'bg-primary';

            $body.append(
                '<tr data-id="' + r.id + '">' +
                  '<td class="batch-name-cell" data-name="' + escapeHtml(r.name) + '" data-label="' + escapeHtml(r.displayName) + '">' +
                    '<strong class="text-dark">' + escapeHtml(r.displayName) + '</strong> ' +
                    '<i class="bx bx-edit-alt text-secondary rename-hint"></i>' +
                    '<br><small class="text-secondary">' + escapeHtml(r.businessType) + ' &middot; ' +
                    escapeHtml(r.regionLabel) + ' &middot; ' + escapeHtml(r.radiusKm) + ' km</small>' +
                  '</td>' +
                  '<td>' + r.statusBadge + '</td>' +
                  '<td>' +
                    '<div class="progress batch-progress mb-1"><div class="progress-bar ' + bar + '" style="width:' + r.progress + '%"></div></div>' +
                    '<small class="text-secondary stat-mini">' + r.progress + '% &middot; ' +
                    (r.totalCells - r.pendingCells) + '/' + r.totalCells + ' cells</small>' +
                  '</td>' +
                  '<td class="text-center text-dark">' + r.totalLeads + '</td>' +
                  '<td class="text-center text-dark">' + r.leadsWithEmail + '</td>' +
                  '<td class="text-center"><strong class="text-dark">' + r.leadsValid + '</strong>' +
                    (r.leadsVerified > r.leadsValid
                      ? '<br><small class="text-secondary stat-mini">' + (r.leadsVerified - r.leadsValid) + ' rejected</small>'
                      : '') +
                  '</td>' +
                  '<td class="text-dark">' + escapeHtml(r.startedAt || '-') + '</td>' +
                  '<td class="text-center">' +
                    '<div class="d-flex flex-wrap gap-1 justify-content-center">' +
                      '<button class="btn btn-sm btn-outline-info view-btn" title="Details"><i class="bx bx-info-circle"></i></button>' +
                      '<a href="{{ route('outreach.leads') }}?batchId=' + encodeURIComponent(r.batchId) + '" class="btn btn-sm btn-outline-primary" title="Open leads"><i class="bx bx-list-ul"></i></a>' +
                      '<button class="btn btn-sm btn-outline-danger delete-btn" data-label="' + escapeHtml(r.displayName) + '" title="Remove"><i class="bx bx-trash"></i></button>' +
                    '</div>' +
                  '</td>' +
                '</tr>'
            );
        });

        $('#totalValidEmails').text(totalValid);
        renderPager(paginator);
        schedulePoll(rows);
    }

    function renderPager(p) {
        var $p = $('#batchPager').empty();
        if (!p.last_page || p.last_page < 2) return;
        for (var i = 1; i <= p.last_page; i++) {
            $p.append('<li class="page-item ' + (i === p.current_page ? 'active' : '') + '">' +
                      '<a class="page-link" href="#" data-page="' + i + '">' + i + '</a></li>');
        }
    }

    $('#batchPager').on('click', 'a', function (e) {
        e.preventDefault(); page = parseInt($(this).data('page'), 10) || 1; load();
    });

    $('#filterStatus').on('change', function () { page = 1; load(); });
    $('#filterSearch').on('input', function () {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function () { page = 1; load(); }, 350);
    });
    $('#resetFilters').on('click', function () {
        $('#filterSearch').val(''); $('#filterStatus').val(''); page = 1; load();
    });

    // ---- rename ----
    $('#batchRows').on('click', '.batch-name-cell', function () {
        renameTarget = $(this).closest('tr').data('id');
        $('#renameInput').val($(this).data('name'));
        $('#renameModal').modal('show');
    });

    $('#confirmRename').on('click', function () {
        if (!renameTarget) return;
        var $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
        $.ajax({
            url: RENAME_URL, type: 'POST',
            data: { _token: CSRF_TOKEN, id: renameTarget, name: $('#renameInput').val() },
            success: function (res) {
                if (!res || !res.success) { toastr.error((res && res.message) || 'Could not rename.'); return; }
                $('#renameModal').modal('hide');
                toastr.success(res.message, 'Success!');
                load();
            },
            error: function (xhr) { toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not rename.'); },
            complete: function () { $btn.prop('disabled', false).html('Save'); renameTarget = null; }
        });
    });

    // ---- details ----
    $('#batchRows').on('click', '.view-btn', function () {
        var id = $(this).closest('tr').data('id');
        $('#batchModalBody').html('<div class="text-center py-4 text-secondary"><i class="bx bx-loader-alt bx-spin"></i> Loading&hellip;</div>');
        $('#batchModal').modal('show');

        $.ajax({
            url: SHOW_URL, type: 'GET', data: { id: id },
            success: function (res) {
                if (!res || !res.success) { $('#batchModalBody').html('<p class="text-dark">Could not load this search.</p>'); return; }
                var b = res.data.batch, leads = res.data.leads || [];
                $('#batchModalTitle').text(b.displayName);

                var html =
                  '<div class="row mb-3">' +
                    tile('Leads found', b.totalLeads) + tile('With an email', b.leadsWithEmail) +
                    tile('Checked', b.leadsVerified) + tile('Good to send', b.leadsValid) +
                  '</div>' +
                  '<p class="text-secondary mb-3">' + b.statusBadge + ' &middot; ' + b.progress + '% complete' +
                  (b.completedAt ? ' &middot; finished ' + escapeHtml(b.completedAt) : '') + '</p>';

                if (!leads.length) {
                    html += '<div class="text-center py-3"><p class="text-dark mb-1">No verified addresses yet.</p>' +
                            '<small class="text-secondary">They appear here once the verifier confirms them.</small></div>';
                } else {
                    html += '<h6 class="text-dark">Verified contacts <small class="text-secondary">(first 50)</small></h6>' +
                            '<div class="table-responsive"><table class="table table-sm mb-0"><thead class="table-light"><tr>' +
                            '<th class="text-dark">Business</th><th class="text-dark">Category</th>' +
                            '<th class="text-dark">Location</th><th class="text-dark">Email</th></tr></thead><tbody>';
                    leads.forEach(function (l) {
                        html += '<tr><td class="text-dark">' + escapeHtml(l.businessName) + '</td>' +
                                '<td class="text-secondary">' + escapeHtml(l.category || '-') + '</td>' +
                                '<td class="text-secondary">' + escapeHtml(l.location || '-') + '</td>' +
                                '<td><a class="text-primary" href="mailto:' + escapeHtml(l.email) + '">' + escapeHtml(l.email) + '</a></td></tr>';
                    });
                    html += '</tbody></table></div>';
                }
                $('#batchModalBody').html(html);
            },
            error: function () { $('#batchModalBody').html('<p class="text-dark">Could not load this search.</p>'); }
        });
    });

    function tile(label, value) {
        return '<div class="col-6 col-md-3"><div class="border rounded p-2 text-center">' +
               '<div class="text-secondary stat-mini">' + label + '</div>' +
               '<div class="h5 mb-0 text-dark">' + value + '</div></div></div>';
    }

    // ---- delete ----
    $('#batchRows').on('click', '.delete-btn', function () {
        deleteTarget = $(this).closest('tr').data('id');
        $('#deleteItemName').text($(this).data('label'));
        $('#deleteModal').modal('show');
    });

    $('#confirmDelete').on('click', function () {
        if (!deleteTarget) return;
        var $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Removing...');
        $.ajax({
            url: DELETE_URL, type: 'POST', data: { _token: CSRF_TOKEN, id: deleteTarget },
            success: function (res) {
                if (!res || !res.success) { toastr.error((res && res.message) || 'Could not remove.'); return; }
                $('#deleteModal').modal('hide');
                toastr.success(res.message, 'Removed');
                load();
            },
            error: function (xhr) { toastr.error((xhr.responseJSON && xhr.responseJSON.message) || 'Could not remove.'); },
            complete: function () { $btn.prop('disabled', false).html('Remove'); deleteTarget = null; }
        });
    });

    load();
});
</script>
@endsection
