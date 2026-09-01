@extends('layouts.master')

@section('title') AniSystem Clients @endsection

@section('css')
<link href="{{ URL::asset('build/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/datatables.net-responsive-bs4/css/responsive.bootstrap4.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .sub-status-badge { text-transform: capitalize; font-size: 11px; letter-spacing: .3px; }
    .system-badge { background: #556ee6; color: #fff; font-size: 11px; }
    .client-detail-label { font-size: 11px; text-transform: uppercase; letter-spacing: .4px; color: #74788d; margin-bottom: 2px; }
    #clientsTable td { vertical-align: middle; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('title') AniSystem Clients @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1 text-dark">AniSystem Clients</h4>
                            <p class="text-secondary mb-0">Paying subscribers of the AniSystem schedule-manager SaaS. Manage their subscriptions here; payments are verified in <a href="{{ route('ecom-orders') }}">E-commerce → Orders</a>.</p>
                        </div>
                    </div>

                    {{-- Filters --}}
                    <div class="row g-2 mb-3">
                        <div class="col-md-5">
                            <input type="text" class="form-control" id="filterSearch" placeholder="Search by name or email...">
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="filterStatus">
                                <option value="">All subscription statuses</option>
                                <option value="pending">Pending</option>
                                <option value="active">Active</option>
                                <option value="suspended">Suspended</option>
                                <option value="expired">Expired</option>
                                <option value="cancelled">Cancelled</option>
                                <option value="rejected">Rejected</option>
                                <option value="none">No subscription</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="button" class="btn btn-outline-secondary w-100" id="clearFilters">Clear</button>
                        </div>
                    </div>

                    <div class="table-responsive">
                        <table id="clientsTable" class="table table-hover align-middle nowrap w-100">
                            <thead class="table-light">
                                <tr>
                                    <th>Client</th>
                                    <th>Email</th>
                                    <th>Roles</th>
                                    <th>Phone</th>
                                    <th>System</th>
                                    <th>Plan</th>
                                    <th>Status</th>
                                    <th>Starts / Expires</th>
                                    {{-- The way into the farm. Beside the
                                         subscription rather than out at the
                                         right-hand end, because this is the
                                         thing a technician opens a client row
                                         to do. --}}
                                    <th>Schedule Manager</th>
                                    <th>Order #</th>
                                    <th>AI Credits</th>
                                    <th>Registered</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Client details modal --}}
    <div class="modal fade" id="clientDetailsModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title"><i class="bx bx-user-circle me-2 text-primary"></i>Client Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="clientDetailsBody">
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

    {{-- Confirm action modal (suspend / unsuspend / cancel) --}}
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

    {{-- Adjust AI Credits --}}
    <div class="modal fade" id="creditsModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Adjust AI Credits</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="creditsClientId">
                    <p class="text-dark mb-3">
                        <span id="creditsClientName" class="fw-semibold"></span>
                        currently has <span id="creditsBalance" class="fw-semibold"></span> credits.
                    </p>
                    <div class="mb-3">
                        <label class="form-label" for="creditsDelta">Amount</label>
                        <div class="input-group">
                            <button type="button" class="btn btn-outline-secondary credits-preset" data-value="50">+50</button>
                            <button type="button" class="btn btn-outline-secondary credits-preset" data-value="100">+100</button>
                            <button type="button" class="btn btn-outline-secondary credits-preset" data-value="350">+350</button>
                            <input type="number" class="form-control" id="creditsDelta" step="1" placeholder="e.g. 100">
                        </div>
                        <div class="form-text">Positive adds credits, negative removes them.</div>
                    </div>
                    <div class="mb-1">
                        <label class="form-label" for="creditsReason">Reason</label>
                        <input type="text" class="form-control" id="creditsReason" maxlength="191"
                               placeholder="e.g. Goodwill top-up after a failed answer">
                        <div class="form-text">Shown to the client in their credit history.</div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" id="creditsSaveBtn">Apply</button>
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
        pending:   'bg-warning text-dark',
        active:    'bg-success',
        suspended: 'bg-warning text-dark',
        cancelled: 'bg-danger',
        expired:   'bg-secondary',
        rejected:  'bg-danger',
        none:      'bg-light text-secondary'
    };

    function esc(str) {
        return $('<div>').text(str == null ? '' : String(str)).html();
    }

    function statusBadge(status) {
        const cls = STATUS_BADGES[status] || 'bg-secondary';
        const label = status === 'none' ? 'No subscription' : status;
        return '<span class="badge ' + cls + ' sub-status-badge">' + esc(label) + '</span>';
    }

    // Who this account IS in AniSystem: admin (mother-site super admin),
    // client (owns schedules / subscribes), worker (works someone's farm) —
    // any combination shows all its badges.
    function rolesBadges(roles) {
        if (!roles || !roles.length) return '—';
        const cls = { admin: 'bg-primary', client: 'bg-success', worker: 'bg-info', invited: 'bg-secondary' };
        return roles.map(function (r) {
            return '<span class="badge ' + (cls[r] || 'bg-secondary') + ' me-1 text-uppercase">' + esc(r) + '</span>';
        }).join('');
    }

    var table = $('#clientsTable').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        order: [],
        ajax: {
            url: "{{ route('anisenso-clients.data') }}",
            type: "GET",
            data: function(d) {
                d.searchFilter = $('#filterSearch').val();
                d.status = $('#filterStatus').val();
            },
            error: function() { toastr.error('Failed to load clients', 'Error'); }
        },
        columns: [
            {
                data: 'clientName', name: 'anisystem_users.firstName', orderable: false,
                render: function(data, type, row) {
                    let html = '<div class="fw-semibold text-dark">' + esc(data || '—') + '</div>';
                    if (row.accountStatus === 'disabled') {
                        html += '<span class="badge bg-danger" style="font-size:10px;">Account disabled</span>';
                    }
                    return html;
                }
            },
            { data: 'email', name: 'anisystem_users.email', render: function(d) { return esc(d); } },
            { data: 'roles', orderable: false, searchable: false, render: function(d) { return rolesBadges(d); } },
            { data: 'phone', name: 'anisystem_users.phone', orderable: false, render: function(d) { return esc(d || '—'); } },
            { data: null, orderable: false, searchable: false, render: function() { return '<span class="badge system-badge">AniSystem</span>'; } },
            { data: 'subPlanName', name: 'sub.planName', orderable: false,
                render: function(d, t, row) {
                    if (!d) return '<span class="text-secondary">—</span>';
                    let html = '<div class="text-dark">' + esc(d) + '</div>';
                    if (row.priceFormatted) html += '<small class="text-secondary">' + esc(row.priceFormatted) + '</small>';
                    return html;
                }
            },
            { data: 'effectiveStatus', orderable: false, searchable: false, render: function(d) { return statusBadge(d); } },
            { data: null, orderable: false, searchable: false,
                render: function(d, t, row) {
                    if (!row.startsAtFormatted && !row.expiresAtFormatted) return '<span class="text-secondary">—</span>';
                    return '<small class="d-block text-dark">' + esc(row.startsAtFormatted || '—') + '</small>' +
                           '<small class="d-block text-secondary">to ' + esc(row.expiresAtFormatted || '—') + '</small>';
                }
            },
            /* Straight to this client's seasons, and only theirs.
               The count is on the button so an empty farm is visible before
               it is opened rather than after. */
            { data: 'scheduleCount', orderable: false, searchable: false,
                render: function(d, t, row) {
                    const n = parseInt(d, 10) || 0;
                    const url = '{{ route('anisenso-schedule-manager.index') }}?clientId=' + encodeURIComponent(row.id);
                    const tone = n > 0 ? 'btn-outline-primary' : 'btn-outline-secondary';
                    const says = n === 0 ? 'No seasons yet' : (n === 1 ? '1 season' : n + ' seasons');
                    return '<a href="' + url + '" class="btn btn-sm ' + tone + ' text-nowrap" ' +
                        'title="Open the schedule manager for this client only">' +
                        '<i class="bx bx-calendar-check me-1"></i>' + says + '</a>';
                }
            },
            { data: 'subOrderNumber', name: 'sub.orderNumber', orderable: false,
                render: function(d) {
                    if (!d) return '<span class="text-secondary">—</span>';
                    return '<a href="{{ route('ecom-orders') }}" target="_blank" title="Opens E-commerce Orders — filter by this order number">' + esc(d) + ' <i class="bx bx-link-external"></i></a>';
                }
            },
            { data: 'aiCredits', orderable: false, searchable: false,
                render: function(d, t, row) {
                    const n = Math.round((parseFloat(d) || 0) * 100) / 100;
                    const tone = n > 0 ? 'text-dark' : 'text-secondary';
                    return '<div class="d-flex align-items-center gap-1">' +
                        '<span class="fw-semibold ' + tone + '">' + n + '</span>' +
                        '<button type="button" class="btn btn-sm btn-link p-0 credits-btn" data-id="' + row.id +
                        '" data-name="' + esc(row.clientName) + '" data-balance="' + n +
                        '" title="Adjust AI Credits"><i class="bx bx-edit-alt"></i></button></div>';
                }
            },
            { data: 'registeredFormatted', name: 'anisystem_users.created_at' },
            { data: null, orderable: false, searchable: false,
                render: function(d, t, row) {
                    const name = esc(row.clientName);
                    let html = '<button type="button" class="btn btn-sm btn-outline-primary view-client-btn me-1" data-id="' + row.id + '" title="View details"><i class="bx bx-show"></i></button>';
                    if (row.effectiveStatus === 'suspended') {
                        html += '<button type="button" class="btn btn-sm btn-outline-success client-action-btn me-1" data-action="unsuspend" data-id="' + row.id + '" data-name="' + name + '" title="Unsuspend"><i class="bx bx-play-circle"></i></button>';
                    } else if (row.effectiveStatus === 'active' || row.effectiveStatus === 'pending') {
                        html += '<button type="button" class="btn btn-sm btn-outline-warning client-action-btn me-1" data-action="suspend" data-id="' + row.id + '" data-name="' + name + '" title="Suspend"><i class="bx bx-pause-circle"></i></button>';
                    }
                    if (['active', 'pending', 'suspended'].indexOf(row.effectiveStatus) !== -1) {
                        html += '<button type="button" class="btn btn-sm btn-outline-danger client-action-btn" data-action="cancel" data-id="' + row.id + '" data-name="' + name + '" title="Cancel subscription"><i class="bx bx-x-circle"></i></button>';
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
    $('#filterStatus').on('change', function() { table.ajax.reload(); });
    $('#clearFilters').on('click', function() {
        $('#filterSearch').val('');
        $('#filterStatus').val('');
        table.ajax.reload();
    });

    // ---- Details modal ----
    // ---- AI Credits ----
    $('#clientsTable').on('click', '.credits-btn', function() {
        const $b = $(this);
        $('#creditsClientId').val($b.data('id'));
        $('#creditsClientName').text($b.data('name'));
        $('#creditsBalance').text($b.data('balance'));
        $('#creditsDelta').val('');
        $('#creditsReason').val('');
        $('#creditsModal').modal('show');
    });

    $('.credits-preset').on('click', function() {
        $('#creditsDelta').val($(this).data('value')).focus();
    });

    $('#creditsSaveBtn').on('click', function() {
        const $btn = $(this).prop('disabled', true);
        const id = $('#creditsClientId').val();

        $.ajax({
            url: "{{ url('anisenso-clients') }}/" + id + "/ai-credits",
            method: 'POST',
            data: {
                _token: "{{ csrf_token() }}",
                delta: $('#creditsDelta').val(),
                reason: $('#creditsReason').val(),
            },
            success: function(res) {
                toastr.success(res.message, 'Success');
                $('#creditsModal').modal('hide');
                table.ajax.reload(null, false);
            },
            error: function(xhr) {
                toastr.error(xhr.responseJSON?.message || 'Could not adjust credits.', 'Error');
            },
            complete: function() { $btn.prop('disabled', false); },
        });
    });

    $('#clientsTable').on('click', '.view-client-btn', function() {
        const id = $(this).data('id');
        $('#clientDetailsBody').html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-2 text-primary"></i><p class="text-secondary mb-0 mt-2">Loading...</p></div>');
        $('#clientDetailsModal').modal('show');

        $.get('{{ url('/anisenso-clients') }}/' + id, function(res) {
            if (!res.success) { $('#clientDetailsBody').html('<div class="alert alert-danger mb-0">' + esc(res.message) + '</div>'); return; }
            renderClientDetails(res.data);
        }).fail(function(xhr) {
            $('#clientDetailsBody').html('<div class="alert alert-danger mb-0">' + esc(xhr.responseJSON?.message || 'Failed to load client details.') + '</div>');
        });
    });

    function renderClientDetails(data) {
        const c = data.client;
        let html = '';

        html += '<div class="row mb-3">';
        html += '<div class="col-md-4 mb-2"><div class="client-detail-label">Client</div><div class="fw-semibold text-dark">' + esc(c.fullName) + ' <span class="badge system-badge ms-1">AniSystem</span></div></div>';
        html += '<div class="col-md-4 mb-2"><div class="client-detail-label">Email</div><div class="text-dark">' + esc(c.email) + '</div></div>';
        html += '<div class="col-md-4 mb-2"><div class="client-detail-label">Phone</div><div class="text-dark">' + esc(c.phone || '—') + '</div></div>';
        html += '<div class="col-md-4 mb-2"><div class="client-detail-label">Account Status</div>' + (c.status === 'disabled' ? '<span class="badge bg-danger">Disabled</span>' : '<span class="badge bg-success">Active</span>') + '</div>';
        html += '<div class="col-md-4 mb-2"><div class="client-detail-label">Registered</div><div class="text-dark">' + esc(c.registeredAt || '—') + '</div></div>';
        html += '</div>';

        html += '<h6 class="text-dark mb-2"><i class="bx bx-receipt me-1"></i>Subscription History</h6>';
        if (!data.subscriptions.length) {
            html += '<p class="text-secondary">No subscriptions yet.</p>';
        } else {
            html += '<div class="table-responsive mb-3"><table class="table table-sm table-bordered align-middle mb-0"><thead class="table-light"><tr>' +
                '<th>Plan</th><th>Price</th><th>Status</th><th>Starts</th><th>Expires</th><th>Order #</th><th>Created</th></tr></thead><tbody>';
            data.subscriptions.forEach(function(s) {
                html += '<tr>' +
                    '<td class="text-dark">' + esc(s.planName || '—') + '</td>' +
                    '<td class="text-dark">' + esc(s.price || '—') + '</td>' +
                    '<td>' + statusBadge(s.effectiveStatus) + '</td>' +
                    '<td><small>' + esc(s.startsAt || '—') + '</small></td>' +
                    '<td><small>' + esc(s.expiresAt || '—') + '</small></td>' +
                    '<td><small>' + esc(s.orderNumber || '—') + '</small></td>' +
                    '<td><small>' + esc(s.createdAt || '—') + '</small></td>' +
                    '</tr>';
            });
            html += '</tbody></table></div>';
        }

        html += '<h6 class="text-dark mb-2"><i class="bx bx-cart me-1"></i>Linked Orders</h6>';
        if (!data.orders.length) {
            html += '<p class="text-secondary mb-0">No linked e-commerce orders.</p>';
        } else {
            html += '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0"><thead class="table-light"><tr>' +
                '<th>Order #</th><th>Order Status</th><th>Payment</th><th>Total</th><th>Date</th></tr></thead><tbody>';
            data.orders.forEach(function(o) {
                const orderCls = { pending: 'bg-warning text-dark', paid: 'bg-info', complete: 'bg-success', cancelled: 'bg-danger', refunded: 'bg-secondary' }[o.orderStatus] || 'bg-secondary';
                const payCls = { pending: 'bg-warning text-dark', verified: 'bg-success', rejected: 'bg-danger', not_required: 'bg-secondary' }[o.paymentVerificationStatus] || 'bg-secondary';
                html += '<tr>' +
                    '<td><a href="{{ route('ecom-orders') }}" target="_blank" title="Opens E-commerce Orders — filter by this order number">' + esc(o.orderNumber) + ' <i class="bx bx-link-external"></i></a></td>' +
                    '<td><span class="badge ' + orderCls + ' sub-status-badge">' + esc(o.orderStatus) + '</span></td>' +
                    '<td><span class="badge ' + payCls + ' sub-status-badge">' + esc(o.paymentVerificationStatus === 'not_required' ? 'N/A' : o.paymentVerificationStatus) + '</span></td>' +
                    '<td class="text-dark">' + esc(o.grandTotal) + '</td>' +
                    '<td><small>' + esc(o.createdAt || '—') + '</small></td>' +
                    '</tr>';
            });
            html += '</tbody></table></div>';
        }

        $('#clientDetailsBody').html(html);
    }

    // ---- Suspend / Unsuspend / Cancel ----
    const ACTION_CONFIG = {
        suspend: {
            title: 'Suspend Subscription',
            message: function(name) { return 'Suspend the current subscription of ' + name + '?'; },
            hint: 'The client will lose access until unsuspended. A notification email will be sent.',
            btnClass: 'btn-warning',
            btnLabel: 'Suspend'
        },
        unsuspend: {
            title: 'Unsuspend Subscription',
            message: function(name) { return 'Lift the suspension for ' + name + '?'; },
            hint: 'The subscription returns to active if it has not expired yet, otherwise it is marked expired.',
            btnClass: 'btn-success',
            btnLabel: 'Unsuspend'
        },
        cancel: {
            title: 'Cancel Subscription',
            message: function(name) { return 'Cancel the current subscription of ' + name + '?'; },
            hint: 'If the linked order is still unverified it will also be cancelled. A notification email will be sent. This cannot be undone.',
            btnClass: 'btn-danger',
            btnLabel: 'Cancel Subscription'
        }
    };

    let pendingAction = null;

    $('#clientsTable').on('click', '.client-action-btn', function() {
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
        const $btn = $(this);
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Working...');

        $.ajax({
            url: '{{ url('/anisenso-clients') }}/' + pendingAction.id + '/' + pendingAction.action,
            type: 'PUT',
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
                $btn.prop('disabled', false).text(pendingAction ? ACTION_CONFIG[pendingAction.action].btnLabel : 'Confirm');
                pendingAction = null;
            }
        });
    });
});
</script>
@endsection
