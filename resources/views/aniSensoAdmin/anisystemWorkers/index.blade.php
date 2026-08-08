@extends('layouts.master')

@section('title') AniSystem Workers @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .sub-status-badge { text-transform: capitalize; font-size: 11px; letter-spacing: .3px; }
    .system-badge { background: #556ee6; color: #fff; font-size: 11px; }
    .worker-detail-label { font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #74788d; margin-bottom: 2px; }
    #workersTable td { vertical-align: middle; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('title') AniSystem Workers @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1 text-dark">AniSystem Workers</h4>
                            <p class="text-secondary mb-0">
                                Logins that <a href="{{ route('anisenso-clients.index') }}">AniSystem clients</a> granted to the people working their farm.
                                A worker holds no subscription of their own — access comes from the farm owner, so a worker whose owner has lapsed cannot sign in.
                            </p>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-4">
                            <input type="text" class="form-control" id="filterSearch" placeholder="Search worker or farm owner...">
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterStatus">
                                <option value="">All statuses</option>
                                <option value="active">Active</option>
                                <option value="pending">Pending invite</option>
                                <option value="revoked">Revoked</option>
                                <option value="deleted">Deleted</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterAccess">
                                <option value="">All access levels</option>
                                <option value="edit">Can edit</option>
                                <option value="view">View only</option>
                                <option value="none">No schedule access</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <select class="form-select" id="filterBoss">
                                <option value="">All farm owners</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters">Clear</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="workersTable" class="table table-hover align-middle nowrap w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>Worker</th>
                                    <th>Email</th>
                                    <th>Farm Owner</th>
                                    <th>Schedule Access</th>
                                    <th>Community</th>
                                    <th>Status</th>
                                    <th>Owner Subscription</th>
                                    <th>Invited / Accepted</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Worker details modal --}}
    <div class="modal fade" id="workerDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-id-card me-2 text-primary"></i>Worker Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="workerDetailsBody">
                    <div class="text-center py-4">
                        <i class="bx bx-loader-alt bx-spin fs-2 text-primary"></i>
                        <p class="text-secondary mb-0 mt-2">Loading...</p>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Edit access modal --}}
    <div class="modal fade" id="accessModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Worker Access</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="accessGrantId">
                    <p class="text-dark mb-3">
                        What <span id="accessWorkerName" class="fw-semibold"></span> may do in
                        <span id="accessBossName" class="fw-semibold"></span>'s farm.
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="accessLevel">Schedule access</label>
                        <select class="form-select" id="accessLevel">
                            <option value="edit">Can edit — add and change activities</option>
                            <option value="view">View only — can see, cannot change</option>
                            <option value="none">No schedule access</option>
                        </select>
                        <div class="form-text">Takes effect on the worker's next request; they do not need to sign in again.</div>
                    </div>
                    <div class="form-check form-switch mb-1">
                        <input class="form-check-input" type="checkbox" id="accessCommunity">
                        <label class="form-check-label" for="accessCommunity">Allow community access</label>
                        <div class="form-text">Lets them use the Plaza community feed, groups and messaging.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="accessSaveBtn">Save</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Confirm action modal (revoke / restore / delete) --}}
    <div class="modal fade" id="confirmActionModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="confirmActionTitle">Confirm</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-1" id="confirmActionMessage"></p>
                    <small class="text-secondary" id="confirmActionHint"></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="confirmActionBtn">Confirm</button>
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
$(document).ready(function() {
    toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };

    const STATUS_BADGES = {
        pending: 'bg-warning text-dark',
        active:  'bg-success',
        revoked: 'bg-danger',
        deleted: 'bg-secondary'
    };

    const ACCESS_BADGES = {
        edit: 'bg-success',
        view: 'bg-info',
        none: 'bg-light text-secondary'
    };

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function statusBadge(status) {
        const cls = STATUS_BADGES[status] || 'bg-secondary';
        const label = status === 'pending' ? 'Pending invite' : status;
        return '<span class="badge ' + cls + ' sub-status-badge">' + esc(label) + '</span>';
    }

    function accessBadge(level, label) {
        const cls = ACCESS_BADGES[level] || 'bg-secondary';
        return '<span class="badge ' + cls + ' sub-status-badge">' + esc(label || level) + '</span>';
    }

    // Populate the farm-owner filter with owners who actually have workers.
    $.get('{{ route('anisenso-workers.bosses') }}', function(res) {
        if (!res.success) return;
        res.data.forEach(function(b) {
            $('#filterBoss').append($('<option>').val(b.id).text(b.name || b.email));
        });
    });

    var table = $('#workersTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        order: [],
        ajax: {
            url: "{{ route('anisenso-workers.data') }}",
            type: "GET",
            data: function(d) {
                d.searchFilter = $('#filterSearch').val();
                d.status = $('#filterStatus').val();
                d.access = $('#filterAccess').val();
                d.bossId = $('#filterBoss').val();
            },
            error: function() { toastr.error('Failed to load workers', 'Error'); }
        },
        columns: [
            {
                data: 'displayName', orderable: false,
                render: function(data, type, row) {
                    let html = '<div class="fw-semibold text-dark">' + esc(data || '—') + '</div>';
                    if (!row.workerUserId) {
                        html += '<span class="badge bg-warning text-dark" style="font-size:10px;">Invite not accepted</span>';
                    } else if (row.workerAccountStatus === 'disabled') {
                        html += '<span class="badge bg-danger" style="font-size:10px;">Account disabled</span>';
                    }
                    if (row.rosterName && row.rosterName !== data) {
                        html += '<small class="d-block text-secondary">Roster: ' + esc(row.rosterName) + '</small>';
                    }
                    return html;
                }
            },
            { data: 'displayEmail', orderable: false, render: function(d) { return esc(d || '—'); } },
            {
                data: 'bossName', orderable: false,
                render: function(d, t, row) {
                    let html = '<div class="text-dark">' + esc(d || '—') + '</div>';
                    if (row.bossEmail) html += '<small class="text-secondary">' + esc(row.bossEmail) + '</small>';
                    return html;
                }
            },
            { data: 'scheduleAccess', orderable: false, searchable: false, render: function(d, t, row) { return accessBadge(d, row.accessLabel); } },
            {
                data: 'communityAccess', orderable: false, searchable: false,
                render: function(d) {
                    return d
                        ? '<span class="badge bg-success sub-status-badge">Allowed</span>'
                        : '<span class="badge bg-light text-secondary sub-status-badge">Off</span>';
                }
            },
            { data: 'effectiveStatus', orderable: false, searchable: false, render: function(d) { return statusBadge(d); } },
            {
                data: 'bossSubscribed', orderable: false, searchable: false,
                render: function(d) {
                    // The single most useful column for support: a worker locked
                    // out is almost always an owner whose subscription lapsed.
                    return d
                        ? '<span class="badge bg-success sub-status-badge">Active</span>'
                        : '<span class="badge bg-danger sub-status-badge" title="This worker cannot sign in until the owner renews">Not active</span>';
                }
            },
            {
                data: null, orderable: false, searchable: false,
                render: function(d, t, row) {
                    return '<small class="d-block text-dark">' + esc(row.invitedFormatted || '—') + '</small>' +
                           '<small class="d-block text-secondary">' + (row.acceptedFormatted ? 'accepted ' + esc(row.acceptedFormatted) : 'not accepted') + '</small>';
                }
            },
            {
                data: null, orderable: false, searchable: false,
                render: function(d, t, row) {
                    const name = esc(row.displayName || row.displayEmail || 'this worker');
                    let html = '<button type="button" class="btn btn-sm btn-outline-primary view-worker-btn me-1" data-id="' + row.id + '" title="View details"><i class="bx bx-show"></i></button>';

                    if (row.effectiveStatus !== 'deleted') {
                        html += '<button type="button" class="btn btn-sm btn-outline-secondary edit-access-btn me-1" data-id="' + row.id +
                                '" data-name="' + name + '" data-boss="' + esc(row.bossName || '—') +
                                '" data-access="' + esc(row.scheduleAccess) + '" data-community="' + (row.communityAccess ? 1 : 0) +
                                '" title="Edit access"><i class="bx bx-edit-alt"></i></button>';
                    }

                    if (row.effectiveStatus === 'active' || row.effectiveStatus === 'pending') {
                        html += '<button type="button" class="btn btn-sm btn-outline-warning worker-action-btn me-1" data-action="revoke" data-id="' + row.id + '" data-name="' + name + '" title="Revoke access"><i class="bx bx-block"></i></button>';
                    } else {
                        html += '<button type="button" class="btn btn-sm btn-outline-success worker-action-btn me-1" data-action="restore" data-id="' + row.id + '" data-name="' + name + '" title="Restore access"><i class="bx bx-undo"></i></button>';
                    }

                    if (row.effectiveStatus !== 'deleted') {
                        html += '<button type="button" class="btn btn-sm btn-outline-danger worker-action-btn" data-action="delete" data-id="' + row.id + '" data-name="' + name + '" title="Delete worker login"><i class="bx bx-trash"></i></button>';
                    }

                    return html;
                }
            }
        ]
    });

    // Filters
    let searchTimer = null;
    $('#filterSearch').on('keyup', function() {
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function() { table.ajax.reload(); }, 400);
    });
    $('#filterStatus, #filterAccess, #filterBoss').on('change', function() { table.ajax.reload(); });
    $('#clearFilters').on('click', function() {
        $('#filterSearch').val('');
        $('#filterStatus').val('');
        $('#filterAccess').val('');
        $('#filterBoss').val('');
        table.ajax.reload();
    });

    // ---- Details modal ----
    $('#workersTable').on('click', '.view-worker-btn', function() {
        const id = $(this).data('id');
        $('#workerDetailsBody').html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-2 text-primary"></i><p class="text-secondary mb-0 mt-2">Loading...</p></div>');
        $('#workerDetailsModal').modal('show');

        $.get('{{ url('/anisenso-workers') }}/' + id, function(res) {
            if (!res.success) { $('#workerDetailsBody').html('<div class="alert alert-danger mb-0">' + esc(res.message) + '</div>'); return; }
            renderWorkerDetails(res.data);
        }).fail(function(xhr) {
            $('#workerDetailsBody').html('<div class="alert alert-danger mb-0">' + esc(xhr.responseJSON?.message || 'Failed to load worker details.') + '</div>');
        });
    });

    function renderWorkerDetails(data) {
        const g = data.grant;
        const w = data.worker;
        const b = data.boss;
        let html = '';

        html += '<h6 class="text-dark mb-2"><i class="bx bx-id-card me-1"></i>Worker</h6>';
        html += '<div class="row mb-3">';
        if (w) {
            html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Name</div><div class="fw-semibold text-dark">' + esc(w.fullName) + '</div></div>';
            html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Email</div><div class="text-dark">' + esc(w.email) + '</div></div>';
            html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Phone</div><div class="text-dark">' + esc(w.phone || '—') + '</div></div>';
            html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Account</div>' + (w.accountStatus === 'disabled' ? '<span class="badge bg-danger">Disabled</span>' : '<span class="badge bg-success">Active</span>') + '</div>';
        } else {
            html += '<div class="col-md-6 mb-2"><div class="worker-detail-label">Invited email</div><div class="fw-semibold text-dark">' + esc(g.invitedEmail || '—') + '</div></div>';
            html += '<div class="col-md-6 mb-2"><div class="worker-detail-label">Account</div><span class="badge bg-warning text-dark">Invite not accepted yet</span></div>';
        }
        if (data.rosterName) {
            html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Roster entry</div><div class="text-dark">' + esc(data.rosterName) + '</div></div>';
        }
        html += '</div>';

        html += '<h6 class="text-dark mb-2"><i class="bx bx-key me-1"></i>Access</h6>';
        html += '<div class="row mb-3">';
        html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Status</div>' + statusBadge(g.effectiveStatus) + '</div>';
        html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Schedule access</div>' + accessBadge(g.scheduleAccess, g.accessLabel) + '</div>';
        html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Community</div>' + (g.communityAccess ? '<span class="badge bg-success">Allowed</span>' : '<span class="badge bg-light text-secondary">Off</span>') + '</div>';
        html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Invited</div><div class="text-dark">' + esc(g.invitedAt || '—') + '</div></div>';
        html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Accepted</div><div class="text-dark">' + esc(g.acceptedAt || '—') + '</div></div>';
        html += '</div>';

        html += '<h6 class="text-dark mb-2"><i class="bx bx-user-circle me-1"></i>Farm Owner</h6>';
        if (!b) {
            html += '<p class="text-secondary mb-3">Owner account no longer exists.</p>';
        } else {
            html += '<div class="row mb-3">';
            html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Name</div><div class="fw-semibold text-dark">' + esc(b.fullName) + ' <span class="badge system-badge ms-1">AniSystem</span></div></div>';
            html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Email</div><div class="text-dark">' + esc(b.email) + '</div></div>';
            html += '<div class="col-md-4 mb-2"><div class="worker-detail-label">Phone</div><div class="text-dark">' + esc(b.phone || '—') + '</div></div>';
            html += '</div>';
        }

        html += '<h6 class="text-dark mb-2"><i class="bx bx-calendar-check me-1"></i>Schedules In Reach</h6>';
        if (!data.schedules.length) {
            html += '<p class="text-secondary mb-0">This owner has no cropping schedules yet.</p>';
        } else {
            html += '<p class="text-secondary mb-2"><small>A grant is farm-wide, so this worker reaches every schedule the owner has.</small></p>';
            html += '<ul class="mb-0">';
            data.schedules.forEach(function(s) {
                html += '<li class="text-dark">' + esc(s.name) + '</li>';
            });
            html += '</ul>';
        }

        $('#workerDetailsBody').html(html);
    }

    // ---- Edit access ----
    $('#workersTable').on('click', '.edit-access-btn', function() {
        const $b = $(this);
        $('#accessGrantId').val($b.data('id'));
        $('#accessWorkerName').text($b.data('name'));
        $('#accessBossName').text($b.data('boss'));
        $('#accessLevel').val($b.data('access'));
        $('#accessCommunity').prop('checked', String($b.data('community')) === '1');
        $('#accessModal').modal('show');
    });

    $('#accessSaveBtn').on('click', function() {
        const $btn = $(this).prop('disabled', true);
        const id = $('#accessGrantId').val();

        $.ajax({
            url: '{{ url('/anisenso-workers') }}/' + id,
            type: 'PUT',
            data: {
                scheduleAccess: $('#accessLevel').val(),
                communityAccess: $('#accessCommunity').is(':checked') ? 1 : 0
            },
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message, 'Success');
                    $('#accessModal').modal('hide');
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(res.message || 'Could not update access.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Could not update access.', 'Error');
            },
            complete: function() { $btn.prop('disabled', false); }
        });
    });

    // ---- Revoke / Restore / Delete ----
    const ACTION_CONFIG = {
        revoke: {
            title: 'Revoke Worker Access',
            message: function(name) { return 'Revoke access for ' + name + '?'; },
            hint: 'They lose their way into this farm immediately. Reversible — you can restore it later.',
            btnClass: 'btn-warning',
            btnLabel: 'Revoke',
            method: 'PUT',
            path: '/revoke'
        },
        restore: {
            title: 'Restore Worker Access',
            message: function(name) { return 'Restore access for ' + name + '?'; },
            hint: 'A worker who never accepted their invite goes back to pending and still has to accept it.',
            btnClass: 'btn-success',
            btnLabel: 'Restore',
            method: 'PUT',
            path: '/restore'
        },
        delete: {
            title: 'Delete Worker Login',
            message: function(name) { return 'Delete the worker login for ' + name + '?'; },
            hint: 'Removes it from the owner\'s worker list. Their own AniSystem account is not touched, and you can still restore this from the Deleted filter.',
            btnClass: 'btn-danger',
            btnLabel: 'Delete',
            method: 'DELETE',
            path: ''
        }
    };

    let pendingAction = null;

    $('#workersTable').on('click', '.worker-action-btn', function() {
        const action = $(this).data('action');
        const cfg = ACTION_CONFIG[action];
        pendingAction = { action: action, id: $(this).data('id') };

        $('#confirmActionTitle').text(cfg.title);
        $('#confirmActionMessage').text(cfg.message($(this).data('name')));
        $('#confirmActionHint').text(cfg.hint);
        $('#confirmActionBtn')
            .removeClass('btn-primary btn-warning btn-success btn-danger')
            .addClass(cfg.btnClass)
            .text(cfg.btnLabel)
            .prop('disabled', false);
        $('#confirmActionModal').modal('show');
    });

    $('#confirmActionBtn').on('click', function() {
        if (!pendingAction) return;
        const cfg = ACTION_CONFIG[pendingAction.action];
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Working...');

        $.ajax({
            url: '{{ url('/anisenso-workers') }}/' + pendingAction.id + cfg.path,
            type: cfg.method,
            success: function(res) {
                if (res.success) {
                    toastr.success(res.message);
                    $('#confirmActionModal').modal('hide');
                    table.ajax.reload(null, false);
                } else {
                    toastr.error(res.message || 'Action failed.');
                }
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Action failed.');
            },
            complete: function() {
                $btn.prop('disabled', false).text(cfg.btnLabel);
                pendingAction = null;
            }
        });
    });
});
</script>
@endsection
