@extends('layouts.master')

@section('title') Streamr Clients @endsection

@section('css')
@include('streamr._styles', ['tables' => true])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Streamr @endslot
        @slot('title') Clients @endslot
    @endcomponent

    <div id="st">
        @unless ($linked)
            <div class="alert alert-warning"><i class="bx bx-link me-1"></i>Streamr is not linked yet (STREAMR_APP_URL / STREAMR_API_TOKEN), so actions cannot be sent. See <a href="{{ route('streamr-health.index') }}">Link status</a>.</div>
        @endunless

        <div class="row g-3 mb-3">
            <div class="col-6 col-md-4 col-xl-2"><div class="stat"><i class="bx bx-group"></i><div><b>{{ number_format($stats['clients']) }}</b><span>Clients</span></div></div></div>
            <div class="col-6 col-md-4 col-xl-2"><div class="stat"><i class="bx bx-badge-check"></i><div><b>{{ number_format($stats['activePaid']) }}</b><span>Active paid</span></div></div></div>
            <div class="col-6 col-md-4 col-xl-2"><div class="stat"><i class="bx bx-time-five"></i><div><b>{{ number_format($stats['expiring']) }}</b><span>Expiring ≤ 7 days</span></div></div></div>
            <div class="col-6 col-md-4 col-xl-2"><div class="stat"><i class="bx bx-infinite"></i><div><b>{{ number_format($stats['lifetime']) }}</b><span>Lifetime</span></div></div></div>
            <div class="col-6 col-md-4 col-xl-2"><div class="stat"><i class="bx bx-block"></i><div><b>{{ number_format($stats['closed']) }}</b><span>Suspended/closed</span></div></div></div>
            <div class="col-6 col-md-4 col-xl-2"><div class="stat"><i class="bx bx-shield-quarter"></i><div><b>{{ number_format($stats['admins']) }}</b><span>Admins (unlimited)</span></div></div></div>
        </div>

        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h4 class="card-title mb-1 text-dark">Clients</h4>
                        <p class="text-secondary mb-0 small">Every Streamr account as Streamr last described it (it sends each change). Actions are applied by Streamr at once. Times are Philippine time.</p>
                    </div>
                    <a href="{{ route('streamr-clients.export') }}" class="btn btn-outline-success" id="stExport"><i class="bx bx-download me-1"></i>Export CSV</a>
                </div>

                @if ($stats['clients'] === 0)
                    <div class="empty mb-3">No Streamr clients yet — they appear here once Streamr is linked and sends their details.</div>
                @endif

                <div class="filters row g-2 mb-3" id="stFilters">
                    <div class="col-md-4 col-xl-3">
                        <label class="form-label" for="fSearch">Search</label>
                        <input type="text" class="form-control" id="fSearch" data-filter="searchFilter" placeholder="Name, email, store or handle">
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label" for="fPlan">Plan</label>
                        <select class="form-select" id="fPlan" data-filter="plan">
                            <option value="">Any</option>
                            @foreach (\App\Models\StreamrClient::PLANS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                            <option value="none">No plan</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label" for="fState">State</label>
                        <select class="form-select" id="fState" data-filter="state">
                            <option value="">Any</option>
                            @foreach (\App\Models\StreamrClient::STATES as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label" for="fExpiring">Expiring within</label>
                        <select class="form-select" id="fExpiring" data-filter="expiringDays">
                            <option value="">Any time</option>
                            @foreach ([7, 14, 30] as $days)
                                <option value="{{ $days }}">{{ $days }} days</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-1">
                        <label class="form-label" for="fRole">Role</label>
                        <select class="form-select" id="fRole" data-filter="role">
                            <option value="">Any</option>
                            <option value="client">Client</option>
                            <option value="admin">Admin</option>
                        </select>
                    </div>
                    <div class="col-6 col-md-4 col-xl-2">
                        <label class="form-label" for="fAccount">Account</label>
                        <select class="form-select" id="fAccount" data-filter="account">
                            <option value="">Any</option>
                            @foreach (\App\Models\StreamrClient::ACCOUNTS as $value => $label)
                                <option value="{{ $value }}">{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fRegFrom">Registered from</label>
                        <input type="date" class="form-control" id="fRegFrom" data-filter="registeredFrom">
                    </div>
                    <div class="col-6 col-md-3 col-xl-2">
                        <label class="form-label" for="fRegTo">Registered to</label>
                        <input type="date" class="form-control" id="fRegTo" data-filter="registeredTo">
                    </div>
                    <div class="col-md-3 col-xl-2 d-flex align-items-end">
                        <button type="button" class="btn btn-outline-secondary w-100" id="stClear"><i class="bx bx-x me-1"></i>Clear</button>
                    </div>
                </div>

                <div class="table-responsive">
                    <table id="stClients" class="table table-hover align-middle nowrap w-100">
                        <thead class="table-light">
                            <tr>
                                <th>Client</th>
                                <th>Email</th>
                                <th>Store</th>
                                <th>Plan</th>
                                <th>State</th>
                                <th>Starts / Expires</th>
                                <th>Credits</th>
                                <th>Usage</th>
                                <th>Last order</th>
                                <th>Registered</th>
                                <th>Last login</th>
                                <th></th>
                            </tr>
                        </thead>
                    </table>
                </div>
            </div>
        </div>
    </div>

    @include('streamr.partials.client-modal')
    @include('streamr.partials.action-modals')
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/datatables.net/js/jquery.dataTables.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive/js/dataTables.responsive.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/datatables.net-responsive-bs4/js/responsive.bootstrap4.min.js') }}"></script>
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(function () {
@include('streamr._script-helpers')

    const U = {
        data: @json(route('streamr-clients.data')),
        show: @json(route('streamr-clients.show')),
        action: @json(route('streamr-clients.action')),
        refresh: @json(route('streamr-clients.refresh')),
        retry: @json(route('streamr-health.retry')),
        exportBase: @json(route('streamr-clients.export')),
    };
    const $filters = $('#stFilters');
    let client = null, chosen = null;

    const table = $('#stClients').DataTable({
        processing: true,
        serverSide: true,
        responsive: true,
        searching: false,
        order: [[9, 'desc']],
        pageLength: 25,
        ajax: {
            url: U.data,
            data: (d) => Object.assign(d, filterValues($filters)),
            error: () => toastr.error('The clients could not be loaded.', 'Error!')
        },
        columns: [
            { data: 'firstName', name: 'firstName', render: (d, t, r) => '<a href="#" class="fw-semibold text-dark" data-open="' + esc(r.id) + '">' + esc(r.fullName) + '</a>'
                + '<div class="mt-1">' + badge(r.accountBadge, r.accountLabel) + (r.isAdmin ? ' ' + badge('bg-info text-white', 'Unlimited') : '') + '</div>' },
            { data: 'email', name: 'email', render: (d) => '<span class="text-dark">' + esc(d) + '</span>' },
            { data: 'storeName', name: 'storeName', render: (d, t, r) => (d ? '<span class="text-dark">' + esc(d) + '</span>' : dash)
                + (r.storeHandle ? '<div class="sub">' + (r.storeUrl ? '<a href="' + esc(r.storeUrl) + '" target="_blank" rel="noopener">@' + esc(r.storeHandle) + '</a>' : '@' + esc(r.storeHandle)) + '</div>' : '') },
            { data: 'planKey', name: 'planKey', render: (d, t, r) => r.planLabel ? '<span class="text-dark">' + esc(r.planLabel) + '</span>' + (r.priceFormatted ? '<div class="sub">' + esc(r.priceFormatted) + '</div>' : '') : dash },
            { data: 'subscriptionState', name: 'subscriptionState', render: (d, t, r) => badge(r.stateBadge, r.stateLabel) },
            { data: 'expiresAt', name: 'expiresAt', render: (d, t, r) => '<span class="text-dark">' + esc(r.expiresFormatted || '—') + '</span>' + (r.startsFormatted ? '<div class="sub">from ' + esc(r.startsFormatted) + '</div>' : '') },
            { data: 'availableCredits', name: 'availableCredits', render: (d, t, r) => r.isAdmin ? '<span class="text-secondary">Unlimited</span>'
                : '<span class="text-dark">' + num(d) + '</span><div class="sub">' + num(r.planCredits) + ' plan · ' + num(r.purchasedCredits) + ' bought</div>' },
            { data: 'livesCount', name: 'livesCount', render: (d, t, r) => '<span class="text-dark">' + num(d) + ' lives</span><div class="sub">' + num(r.liveMinutes) + ' min</div>' },
            { data: 'lastOrderNumber', name: 'lastOrderNumber', render: (d, t, r) => d ? '<a href="' + esc(r.lastOrderUrl) + '" class="st-mono">' + esc(d) + '</a>' : dash },
            { data: 'registeredAt', name: 'registeredAt', render: (d, t, r) => r.registeredFormatted ? '<span class="text-dark">' + esc(r.registeredFormatted) + '</span>' : dash },
            { data: 'lastLoginAt', name: 'lastLoginAt', render: (d, t, r) => r.lastLoginFormatted ? '<span class="text-dark">' + esc(r.lastLoginFormatted) + '</span>' : dash },
            { data: null, orderable: false, render: (d, t, r) => '<button type="button" class="btn btn-sm btn-outline-primary" data-open="' + esc(r.id) + '">Details</button>' }
        ]
    });

    const reload = () => {
        table.ajax.reload(null, false);
        $('#stExport').attr('href', U.exportBase + '?' + $.param(filterValues($filters)));
    };
    let timer = null;
    $('#fSearch').on('keyup', () => { clearTimeout(timer); timer = setTimeout(reload, 400); });
    $filters.find('select, input[type=date]').on('change', reload);
    $('#stClear').on('click', () => { $filters.find('input, select').val(''); reload(); });

    // ---- details
    const modal = new bootstrap.Modal(document.getElementById('stClient'));
    const actionModal = new bootstrap.Modal(document.getElementById('stAction'));
    const facts = (rows) => '<div class="facts">' + rows.filter((r) => r[1] !== null && r[1] !== undefined && r[1] !== '')
        .map((r) => '<div><span>' + esc(r[0]) + '</span><b>' + esc(r[1]) + '</b></div>').join('') + '</div>';
    const table2 = (head, rows, empty) => rows.length
        ? '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr>' + head.map((h) => '<th>' + esc(h) + '</th>').join('') + '</tr></thead><tbody>' + rows.join('') + '</tbody></table></div>'
        : '<p class="text-secondary small mb-0">' + esc(empty) + '</p>';

    function paint(c) {
        client = c;
        $('#stClientTitle').text(c.name);
        $('#stClientSub').html(esc(c.email) + ' · Streamr #' + esc(c.streamrUserId) + ' ' + badge(c.account.badge, c.account.label) + (c.isAdmin ? ' ' + badge('bg-info text-white', 'Unlimited') : ''));
        const s = c.subscription;
        $('#stClientBody').html(
            '<div class="sec mt-0"><h6>Profile</h6>' + facts([['Name', c.name], ['Email', c.email], ['Mobile', c.phone], ['Role', c.role], ['Registered', c.registeredAt], ['Last login', c.lastLoginAt], ['Last live', c.lastLiveAt]]) + '</div>'
            + '<div class="sec"><h6>Store</h6>' + facts([['Name', c.store.name], ['Handle', c.store.handle ? '@' + c.store.handle : ''], ['Custom domain', c.store.customDomain]])
                + (c.store.url ? '<a href="' + esc(c.store.url) + '" target="_blank" rel="noopener" class="small">' + esc(c.store.url) + '</a>' : '') + '</div>'
            + '<div class="sec"><h6>Subscription</h6><div class="mb-2">' + badge(s.state.badge, s.state.label) + '</div>'
                + facts([['Plan', s.planName], ['Price', s.price !== null ? peso(s.price) : ''], ['Starts', s.startsAt], ['Expires', s.expiresAt], ['Grace until', s.graceUntil]])
                + (s.queued.length ? '<div class="st-sub mt-1">Upcoming: ' + s.queued.map((q) => esc(q.plan + ' · ' + q.startsAt + ' → ' + q.expiresAt)).join('; ') + '</div>' : '') + '</div>'
            + '<div class="sec"><h6>Credits</h6>' + (c.credits.unlimited ? '<p class="text-dark mb-0">Unlimited (Streamr admin)</p>'
                : facts([['Available', num(c.credits.available)], ['Plan', num(c.credits.plan)], ['Purchased', num(c.credits.purchased)], ['Plan cycle ends', c.credits.cycleEndsAt]])) + '</div>'
            + '<div class="sec"><h6>Totals &amp; usage</h6>' + facts([['Paid', peso(c.totals.paid)], ['Orders', num(c.totals.orders)], ['Last order', c.totals.lastOrderNumber],
                ['Lives', num(c.usage.lives)], ['Live minutes', num(c.usage.liveMinutes)], ['Leads', num(c.usage.leads)], ['Products', num(c.usage.products)], ['Storage', c.usage.storage]]) + '</div>'
            + '<div class="sec"><h6>Notes (' + esc(c.notes.count) + ')</h6>' + (c.notes.recent.length
                ? c.notes.recent.map((n) => '<div class="mb-2"><div class="st-sub">' + esc([n.at, n.by].filter(Boolean).join(' · ')) + '</div><div class="text-dark" style="white-space: pre-line">' + esc(n.body) + '</div></div>').join('')
                : '<p class="text-secondary small mb-1">No notes.</p>') + '<button type="button" class="btn btn-sm btn-outline-secondary" data-action="add_note"><i class="bx bx-plus me-1"></i>Add note</button></div>'
            + '<div class="sec"><h6>Streamr orders</h6>' + table2(['Order', 'Item', 'Total', 'Order', 'Payment', 'Date'], c.orders.map((o) => '<tr><td><a class="st-mono" href="' + esc(o.url) + '">' + esc(o.number) + '</a></td><td class="text-dark">' + esc(o.item) + '</td><td class="text-dark">' + peso(o.total) + '</td><td>' + orderBadge(o.orderStatus) + '</td><td>' + paymentBadge(o.paymentStatus) + '</td><td class="text-secondary">' + esc(o.created) + '</td></tr>'), 'No Streamr orders.') + '</div>'
            + '<div class="sec"><h6>Recent actions</h6>' + table2(['Action', 'By', 'Status', 'Said', 'When', ''], c.actions.map((a) => '<tr><td class="text-dark">' + esc(a.label) + '</td><td class="text-secondary">' + esc(a.by) + '</td><td>' + badge(a.badge, words(a.status)) + '</td><td class="text-secondary">' + esc(a.error || a.message) + '</td><td class="text-secondary">' + esc(a.at) + '</td><td>' + (a.canRetry ? '<button type="button" class="btn btn-sm btn-outline-warning" data-retry="' + esc(a.id) + '">Retry</button>' : '') + '</td></tr>'), 'No actions sent yet.') + '</div>'
            + '<div class="sec"><h6>Open tickets</h6>' + table2(['Ticket', 'Subject', 'Status'], c.tickets.map((t) => '<tr><td><a class="st-mono" href="' + esc(t.url) + '">' + esc(t.number) + '</a></td><td class="text-dark">' + esc(t.subject) + '</td><td>' + badge(t.badge, t.statusLabel) + '</td></tr>'), 'No open tickets.') + '</div>'
            + '<p class="st-sub mt-3 mb-0">Snapshot from Streamr: ' + esc(c.snapshotAt || '—') + ' · stored ' + esc(c.syncedAt || '—') + '</p>'
        );
        const $open = $('#stOpenStreamr');
        if (c.links.streamr) { $open.attr('href', c.links.streamr).prop('hidden', false); } else { $open.prop('hidden', true); }
    }

    function open(id) {
        $('#stClientTitle').text('Loading…'); $('#stClientSub').text('');
        $('#stClientBody').html('<div class="skel" style="height: 12rem"></div>');
        modal.show();
        $.get(U.show, { id }).done((r) => paint(r.data)).fail((x) => { modal.hide(); fail(x, 'That client could not be opened.'); });
    }
    $('#stClients').on('click', '[data-open]', function (e) { e.preventDefault(); open($(this).data('open')); });

    // ---- actions
    const TITLES = {
        suspend_subscription: 'Suspend subscription', unsuspend_subscription: 'Unsuspend subscription', revoke_subscription: 'End plan now',
        extend_days: 'Extend days', set_expiry: 'Change end date', cancel_queued: 'Cancel upcoming period', change_plan: 'Change plan',
        adjust_credits: 'Adjust credits', update_profile: 'Edit client details', suspend_account: 'Suspend account', restore_account: 'Restore account',
        send_login_link: 'Send login link', change_email: 'Change login email', add_note: 'Add note'
    };
    const confirmText = (a, c) => {
        const plan = c.subscription.planName || 'plan';
        return ({
            suspend_subscription: 'Suspend ' + c.name + '\'s ' + plan + ' plan? Their lives stop and Streamr emails them.',
            unsuspend_subscription: 'Unsuspend ' + c.name + '\'s ' + plan + ' plan?',
            revoke_subscription: 'End ' + c.name + '\'s ' + plan + ' plan now? Their plan credits expire.',
            extend_days: 'Extend ' + c.name + '\'s ' + plan + ' plan by the days below? Streamr emails them.',
            set_expiry: 'Change when ' + c.name + '\'s ' + plan + ' plan ends (now ' + (c.subscription.expiresAt || '—') + ').',
            cancel_queued: 'Cancel one of ' + c.name + '\'s upcoming periods.',
            change_plan: 'Move ' + c.name + ' to another plan (no charge).',
            adjust_credits: 'Add or take credits for ' + c.name + '. A bucket never goes below 0.',
            update_profile: 'Edit ' + c.name + '\'s details in Streamr.',
            suspend_account: 'Suspend ' + c.name + '\'s account? They are signed out everywhere.',
            restore_account: 'Restore ' + c.name + '\'s account?',
            send_login_link: 'Email ' + c.name + ' a one-time login link?',
            change_email: 'Change ' + c.name + '\'s login email (now ' + c.email + ').',
            add_note: 'Add a note to ' + c.name + ' in Streamr.'
        })[a] || '';
    };

    $(document).on('click', '#stClient [data-action]', function () {
        if (!client) { return; }
        chosen = $(this).data('action');
        const c = client;
        const form = document.getElementById('stActionForm');
        form.reset();
        $('#stActionTitle').text(TITLES[chosen] || 'Action');
        $('#stActionConfirm').text(confirmText(chosen, c));
        $('#stActionForm [data-fields]').each(function () { $(this).prop('hidden', String($(this).data('fields')).split(' ').indexOf(chosen) === -1); });
        $('#stActionSubmit').prop('disabled', false);
        if (chosen === 'set_expiry') {
            $('#stFieldExpiresAt').val(c.subscription.expiresAtInput || '');
            if (c.subscription.isLifetime) { $('#stActionConfirm').text('A lifetime plan has no end date to change.'); $('#stActionSubmit').prop('disabled', true); }
        }
        if (chosen === 'cancel_queued') {
            const q = c.subscription.queued;
            $('#stQueuedList').html(q.length ? q.map((p, i) => '<label class="form-check"><input class="form-check-input" type="radio" name="subscription_id" value="' + esc(p.id) + '"' + (i === 0 ? ' checked' : '') + '> <span class="form-check-label text-dark">' + esc(p.plan + ' · ' + p.startsAt + ' → ' + p.expiresAt) + '</span></label>').join('')
                : '<p class="text-secondary small mb-0">No upcoming periods.</p>');
            $('#stActionSubmit').prop('disabled', !q.length);
        }
        if (chosen === 'update_profile') {
            $('#stFieldFirstName').val(c.firstName); $('#stFieldLastName').val(c.lastName); $('#stFieldPhone').val(c.phone); $('#stFieldStoreName').val(c.store.name || '');
        }
        if (chosen === 'change_plan' && c.subscription.planKey) { $('#stFieldPlan').val(c.subscription.planKey); }
        actionModal.show();
    });

    $('#stActionForm').on('submit', function (e) {
        e.preventDefault();
        if (!client || !chosen) { return; }
        if (chosen === 'update_profile') {
            const phone = $('#stFieldPhone').val().replace(/[\s-]+/g, '');
            if (phone && !/^(09|\+639)\d{9}$/.test(phone)) { toastr.warning('The mobile number looks like 09XXXXXXXXX.'); return; }
            const store = $('#stFieldStoreName').val().trim();
            if (store && store.length < 2) { toastr.warning('The store name needs at least 2 characters.'); return; }
        }
        const data = {};
        $(this).find('[data-fields]:not([hidden])').find('input, select, textarea').each(function () {
            if (this.type === 'radio' && !this.checked) { return; }
            data[this.name] = this.type === 'checkbox' ? (this.checked ? 1 : 0) : $(this).val();
        });
        const $b = $('#stActionSubmit');
        busy($b, true, 'Sending…');
        $.ajax({ url: U.action + '?' + $.param({ id: client.id, action: chosen }), method: 'POST', data })
            .done((r) => { toastr.success(r.message || 'Streamr applied it.'); actionModal.hide(); if (r.data && r.data.client) { paint(r.data.client); } table.ajax.reload(null, false); })
            .fail((x) => { fail(x, 'Streamr is not reachable — try again.'); if (x.responseJSON && x.responseJSON.data && x.responseJSON.data.client) { paint(x.responseJSON.data.client); } })
            .always(() => busy($b, false));
    });

    $('#stRefresh').on('click', function () {
        if (!client) { return; }
        const $b = $(this);
        busy($b, true, 'Refreshing…');
        $.post(U.refresh + '?' + $.param({ id: client.id }))
            .done((r) => { toastr.success(r.message || 'Refreshed.'); if (r.data && r.data.client) { paint(r.data.client); } table.ajax.reload(null, false); })
            .fail((x) => fail(x, 'Streamr is not reachable — try again.'))
            .always(() => busy($b, false));
    });

    $(document).on('click', '#stClient [data-retry]', function () {
        const $b = $(this);
        busy($b, true, '');
        $.post(U.retry + '?' + $.param({ id: $b.data('retry') }))
            .done((r) => { toastr.success(r.message || 'Sent again.'); open(client.id); })
            .fail((x) => { busy($b, false); fail(x, 'Streamr is not reachable — try again.'); });
    });

    const openId = @json($open);
    if (openId) { setTimeout(() => open(openId), 300); }
});
</script>
@endsection
