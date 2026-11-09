@extends('layouts.master')

@section('title') Streamr Payment reviews @endsection

@section('css')
@include('streamr._styles', ['tables' => false])
<style>
    #st .pay-list { display: grid; gap: .5rem; }
    #st .pay-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .35rem 1rem; align-items: center; width: 100%; text-align: left;
        padding: .8rem 1rem; border: 1px solid var(--bs-border-color); border-radius: .75rem; background: var(--bs-body-bg); color: var(--bs-body-color);
        transition: border-color .25s ease, box-shadow .25s ease; }
    #st .pay-row:hover { border-color: var(--bs-primary); box-shadow: 0 8px 24px -16px rgb(0 0 0 / .35); }
    #st .pay-row .t { font-weight: 600; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; color: var(--bs-emphasis-color); }
    #st .pay-row .t small { font-weight: 500; color: var(--bs-secondary-color); margin-left: .35rem; }
    #st .pay-row .s { color: var(--bs-secondary-color); font-size: .84rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #st .pay-row .m { text-align: right; font-weight: 700; white-space: nowrap; color: var(--bs-emphasis-color); }
    #st .pay-row .b { display: flex; gap: .3rem; justify-content: flex-end; flex-wrap: wrap; align-items: center; }
    @media (max-width: 575.98px) { #st .pay-row { grid-template-columns: 1fr; } #st .pay-row .m, #st .pay-row .b { text-align: left; justify-content: flex-start; } }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Streamr @endslot
        @slot('title') Payment reviews @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert"><i class="bx bx-check-circle me-2"></i>{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif
    @if (session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert"><i class="bx bx-error-circle me-2"></i>{{ session('error') }}<button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger"><i class="bx bx-error-circle me-2"></i>{{ $errors->first() }}</div>
    @endif

    <div id="st">
        <ul class="nav nav-pills mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $tab === 'reviews' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#stPaneReviews" type="button" role="tab">
                    <i class="bx bx-receipt me-1"></i>Reviews
                    @if (($counts['review'] ?? 0) > 0)<span class="badge bg-danger ms-1" id="stReviewCount">{{ $counts['review'] }}</span>@endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $tab === 'settings' ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#stPaneSettings" type="button" role="tab">
                    <i class="bx bx-wallet me-1"></i>Payment settings
                </button>
            </li>
        </ul>

        <div class="tab-content">
            <div class="tab-pane fade {{ $tab === 'reviews' ? 'show active' : '' }}" id="stPaneReviews" role="tabpanel">
                <div class="card"><div class="card-body">
                    <p class="text-secondary mb-3">
                        Streamr's plan and credit purchases as they are in <a href="{{ url('/ecom-orders') }}">/ecom-orders</a> (store <b class="text-dark">{{ $storeName }}</b>).
                        Streamr's AI reads every GCash QR and Maya receipt; what it could not pass waits here. <b class="text-dark">Verify</b> and <b class="text-dark">Reject</b>
                        are the same as Payments › Verify/Reject in /ecom-orders, and Streamr applies the plan or credits (or tells the buyer) right after.
                    </p>
                    <div class="bar">
                        <div class="chips" id="stChips">
                            @foreach (\App\Http\Controllers\Streamr\StreamrPaymentsController::CHIPS as $key => $label)
                                <button type="button" class="chip {{ $key === 'review' ? 'is-on' : '' }} {{ $key === 'review' && ($counts[$key] ?? 0) > 0 ? 'is-hot' : '' }}" data-status="{{ $key }}">
                                    {{ $label }} <span class="n" data-count="{{ $key }}">{{ $counts[$key] ?? 0 }}</span>
                                </button>
                            @endforeach
                        </div>
                        <input type="search" class="form-control search" id="stSearch" placeholder="Order no., reference, name or email" autocomplete="off" aria-label="Search Streamr orders">
                    </div>
                    <div class="pay-list" id="stList"><div class="skel"></div><div class="skel"></div><div class="skel"></div></div>
                    <div class="text-center mt-3"><button type="button" class="btn btn-light" id="stMore" hidden>Show more</button></div>
                </div></div>
            </div>

            <div class="tab-pane fade {{ $tab === 'settings' ? 'show active' : '' }}" id="stPaneSettings" role="tabpanel">
                @include('streamr.partials.settings-tab')
            </div>
        </div>
    </div>

    @include('streamr.partials.review-modal')
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(function () {
@include('streamr._script-helpers')

    const U = {
        list: @json(route('streamr-payments.data')),
        one: @json(route('streamr-payments.one')),
        verifyBase: @json(url('/ecom-order-payments')),
    };
    const $list = $('#stList');
    const $more = $('#stMore');
    let status = 'review', q = '', page = 1, loading = false, current = null, pendingAct = null;

    const row = (o) => '<button type="button" class="pay-row" data-number="' + esc(o.number) + '">'
        + '<div class="t">' + esc(o.item) + '<small>' + esc(o.number) + '</small></div>'
        + '<div class="m">' + peso(o.total) + '</div>'
        + '<div class="s">' + esc(o.client) + (o.email && o.email !== o.client ? ' · ' + esc(o.email) : '') + (o.storeHandle ? ' · @' + esc(o.storeHandle) : '')
        + ' · ' + esc(o.method) + ' · ' + esc(o.age) + '</div>'
        + '<div class="b">' + aiChip(o.ai) + (o.autoApproved ? badge('bg-info text-white', 'Auto-approved') : '') + badge(o.status.badge, o.status.label)
        + ' <span class="btn btn-sm btn-outline-primary">Review</span></div>'
        + '</button>';

    function paintCounts(counts) {
        if (!counts) { return; }
        Object.keys(counts).forEach((k) => $('[data-count="' + k + '"]').text(counts[k]));
        $('#stChips [data-status="review"]').toggleClass('is-hot', (counts.review || 0) > 0);
        const $badge = $('#stReviewCount');
        if ((counts.review || 0) > 0) { $badge.length ? $badge.text(counts.review) : null; } else { $badge.remove(); }
    }

    function load(reset) {
        if (loading) { return; }
        loading = true;
        if (reset) { page = 1; $list.html('<div class="skel"></div><div class="skel"></div><div class="skel"></div>'); $more.prop('hidden', true); }
        $.get(U.list, { status, q, page }).done((r) => {
            const d = r.data || {};
            if (reset) { $list.empty(); }
            $list.append((d.rows || []).map(row).join(''));
            if (!$list.children().length) {
                $list.html('<div class="empty">' + (q ? 'No Streamr order matches “' + esc(q) + '”.' : (status === 'review' ? 'Nothing to review. Every Streamr payment has been decided.' : 'No Streamr orders here yet.')) + '</div>');
            }
            $more.prop('hidden', !d.more);
            paintCounts(d.counts);
            page++;
        }).fail((x) => { $list.html('<div class="empty">' + esc((x.responseJSON && x.responseJSON.message) || 'The orders could not be loaded.') + '</div>'); })
          .always(() => { loading = false; });
    }

    $('#stChips').on('click', '[data-status]', function () {
        status = $(this).data('status');
        $('#stChips .chip').removeClass('is-on');
        $(this).addClass('is-on');
        load(true);
    });
    let tq;
    $('#stSearch').on('input', function () { clearTimeout(tq); const v = this.value.trim(); tq = setTimeout(() => { q = v; load(true); }, 350); });
    $more.on('click', () => load(false));
    $list.on('click', '.pay-row', function () { open($(this).data('number')); });

    // ---- one order
    const modal = new bootstrap.Modal(document.getElementById('stReview'));
    const icon = (ok) => ok === true ? '<i class="y">✓</i>' : (ok === false ? '<i class="n">✕</i>' : '<i class="q">?</i>');
    const facts = (rows) => '<div class="facts">' + rows.filter((r) => r[1] !== null && r[1] !== undefined && r[1] !== '')
        .map((r) => '<div><span>' + esc(r[0]) + '</span><b>' + esc(r[1]) + '</b></div>').join('') + '</div>';

    function aiBlock(ai, title) {
        if (!ai) { return '<p class="text-secondary small mb-0">' + esc(title || 'No AI reading for this receipt.') + '</p>'; }
        return '<div class="verdict ' + esc(ai.verdict) + '"><b>' + esc((title ? title + ': ' : '') + ai.word) + (ai.score !== null ? ' · ' + esc(ai.score) + '/100' : '') + '.</b> ' + esc(ai.summary) + '</div>'
            + (ai.checks.length ? '<div class="checks">' + ai.checks.map((c) => '<div class="check">' + icon(c.ok) + '<div><b class="text-dark">' + esc(c.label) + '</b>' + (c.hard ? '' : ' <span class="st-sub">(soft)</span>') + '<small>' + esc(c.detail) + '</small></div></div>').join('') + '</div>' : '')
            + '<div class="st-sub mt-1">' + esc([ai.provider, ai.model, ai.checkedAt].filter(Boolean).join(' · ')) + '</div>';
    }

    function expected(o) {
        const e = o.expected, rows = [['Amount to receive', peso(e.amount)]];
        if (o.methodKey === 'manual_gcash') { rows.push(['GCash', [e.gcash.number, e.gcash.account_name_masked].filter(Boolean).join(' · ') || 'not set']); }
        if (o.methodKey === 'manual_maya') { rows.push(['Maya', [e.maya.number, e.maya.account_name_masked].filter(Boolean).join(' · ') || 'not set']); }
        if (o.methodKey === 'manual_bank') { rows.push(['Bank', [e.bank.bank_name, e.bank.account_name, e.bank.account_number, e.bank.branch].filter(Boolean).join(' · ') || 'not set']); }
        rows.push(['Paid', 'after ' + o.created]);
        return facts(rows);
    }

    function proofs(o) {
        if (!o.proofs.length) { return '<p class="text-secondary mb-0">No proof was sent.</p>'; }
        return o.proofs.map((p) => {
            let media = '<p class="text-secondary small mb-1">Reference number only — no receipt picture.' + (p.reference ? ' Ref: <span class="st-mono">' + esc(p.reference) + '</span>' : '') + '</p>';
            if (p.url && p.kind === 'pdf') {
                media = '<div class="proof"><iframe src="' + esc(p.url) + '" title="Receipt ' + esc(p.n) + ' (PDF)"></iframe><a href="' + esc(p.url) + '" target="_blank" rel="noopener" class="small">Open PDF</a></div>';
            } else if (p.url) {
                media = '<div class="proof"><img src="' + esc(p.url) + '" alt="Receipt ' + esc(p.n) + '" data-zoom="' + esc(p.url) + '" loading="lazy"></div>';
            }
            return '<div class="sec"><h6>Proof ' + esc(p.n) + ' of 3' + (p.paymentNumber ? ' · ' + esc(p.paymentNumber) : '') + ' · sent ' + esc(p.submitted) + '</h6>'
                + '<div class="row g-3"><div class="col-lg-6">' + media + '</div><div class="col-lg-6">' + aiBlock(p.ai, null)
                + p.rechecks.map((r) => '<div class="mt-2">' + aiBlock(r, 'Re-check') + '</div>').join('') + '</div></div></div>';
        }).join('');
    }

    function payments(o) {
        return '<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr><th>Payment</th><th class="text-end">Sent</th><th class="text-end">Verified</th><th>Status</th><th>Decided</th><th></th></tr></thead><tbody>'
            + o.payments.map((p) => '<tr><td class="st-mono text-dark">' + esc(p.number) + '</td><td class="text-end text-dark">' + peso(p.amountSent) + '</td><td class="text-end text-dark">' + (p.amountVerified !== null ? peso(p.amountVerified) : dash) + '</td>'
                + '<td>' + badge(p.badge, p.statusLabel) + '</td><td class="text-secondary">' + esc([p.verifiedBy, p.verifiedAt].filter(Boolean).join(' · ') || '—') + '</td>'
                + '<td>' + (p.invoiceUrl ? '<a href="' + esc(p.invoiceUrl) + '" target="_blank" rel="noopener">Invoice</a>' : '') + '</td></tr>'
                + (p.notes ? '<tr><td colspan="6"><pre class="notes">' + esc(p.notes) + '</pre></td></tr>' : '')).join('')
            + '</tbody></table></div>';
    }

    function paint(o) {
        current = o; pendingAct = null;
        $('#stReviewTitle').text(o.number);
        $('#stReviewSub').html(badge(o.status.badge, o.status.label) + ' ' + aiChip(o.ai) + (o.autoApproved ? ' ' + badge('bg-info text-white', 'Auto-approved by ' + (o.decidedBy === 'streamr_admin' ? 'a Streamr admin' : 'Streamr AI')) : '') + ' · ' + esc(o.item));
        const c = o.client;
        $('#stReviewBody').html(
            facts([['Order', o.number], ['Created', o.created], ['Bought', o.item + ' (' + o.kind + ')'], ['Total', peso(o.total)], ['Paid by', o.method],
                ['Reference typed', o.facts.reference], ['Payer', o.facts.payer], ['Sender phone', o.facts.senderPhone], ['Buyer note', o.facts.buyerNote],
                ['Client', c.name], ['Email', c.email], ['Store', c.storeHandle ? '@' + c.storeHandle : c.storeName]])
            + (c.clientsUrl ? '<div class="small mt-1"><a href="' + esc(c.clientsUrl) + '">Open the client in Streamr › Clients</a></div>' : '')
            + '<div class="sec"><h6>Expected</h6>' + expected(o) + '</div>'
            + proofs(o)
            + '<div class="sec"><h6>Payments</h6>' + payments(o) + '</div>'
            + '<div class="sec"><h6>Timeline</h6><div class="timeline">' + o.timeline.map((t) => '<div><span>' + esc(t.at) + ' · ' + esc(t.who) + '</span><b class="text-dark">' + esc(t.what) + '</b>' + (t.description ? '<div class="st-sub">' + esc(t.description) + '</div>' : '') + '</div>').join('') + '</div></div>'
            + '<div class="sec" id="stDecide" hidden><div id="stDecideVerify" hidden><label class="form-label text-dark" for="stAmount">Amount received (₱)</label><input type="number" min="0" step="0.01" class="form-control" id="stAmount"></div>'
            + '<div id="stDecideReject" hidden><label class="form-label text-dark" for="stReason">Why is it rejected? (the buyer sees this)</label><textarea class="form-control" id="stReason" rows="2" maxlength="1000" placeholder="e.g. The amount did not arrive in our GCash."></textarea></div></div>'
            + '<p class="st-sub mt-3 mb-0">The same order is in /ecom-orders (search ' + esc(o.number) + ').' + (o.links.streamr ? ' <a href="' + esc(o.links.streamr) + '" target="_blank" rel="noopener">Open in Streamr</a>' : '') + '</p>'
        );
        footer();
    }

    function footer() {
        const o = current;
        let html = '';
        if (o.can.verify) { html += '<button type="button" class="btn btn-success" data-act="verify"><i class="bx bx-check me-1"></i>Verify payment</button>'; }
        if (o.can.reject) { html += '<button type="button" class="btn btn-outline-danger" data-act="reject"><i class="bx bx-x me-1"></i>Reject</button>'; }
        html += '<button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>';
        $('#stReviewFoot').html(html);
    }

    function open(number) {
        $('#stReviewTitle').text('Loading…'); $('#stReviewSub').text('');
        $('#stReviewBody').html('<div class="skel" style="height: 12rem"></div>'); $('#stReviewFoot').empty();
        modal.show();
        $.get(U.one, { number }).done((r) => paint(r.data)).fail((x) => { modal.hide(); fail(x, 'That order could not be opened.'); });
    }

    // The second step always names the amount that will be recorded, also after it is edited.
    $('#stReviewBody').on('input', '#stAmount', function () {
        if (pendingAct !== 'verify') { return; }
        $('#stReviewFoot [data-act="confirm"]').html('<i class="bx bx-check-double me-1"></i>Confirm verify ' + esc(this.value === '' ? '' : peso(this.value)));
    });

    $('#stReviewBody').on('click', '[data-zoom]', function () { $('#stZoom img').attr('src', $(this).data('zoom')); $('#stZoom').addClass('is-on'); });
    $('#stZoom').on('click', function () { $(this).removeClass('is-on'); });

    // Verify and Reject ask once more, in place; both go to /ecom-orders' own endpoint.
    $('#stReviewFoot').on('click', '[data-act]', function () {
        const act = $(this).data('act');
        if (act === 'verify' || act === 'reject') {
            pendingAct = act;
            $('#stDecide').prop('hidden', false);
            $('#stDecideVerify').prop('hidden', act !== 'verify');
            $('#stDecideReject').prop('hidden', act !== 'reject');
            if (act === 'verify') { $('#stAmount').val(current.can.amount !== null ? Number(current.can.amount).toFixed(2) : ''); }
            $('#stReviewFoot').html('<button type="button" class="btn btn-light" data-act="back">Back</button>'
                + (act === 'verify'
                    ? '<button type="button" class="btn btn-success" data-act="confirm"><i class="bx bx-check-double me-1"></i>Confirm verify ' + esc(peso(current.can.amount)) + '</button>'
                    : '<button type="button" class="btn btn-danger" data-act="confirm"><i class="bx bx-x me-1"></i>Reject this payment</button>'));
            setTimeout(() => (act === 'verify' ? $('#stAmount') : $('#stReason')).trigger('focus'), 150);
            return;
        }
        if (act === 'back') { $('#stDecide').prop('hidden', true); pendingAct = null; footer(); return; }
        if (act !== 'confirm' || !pendingAct) { return; }

        const data = { action: pendingAct };
        if (pendingAct === 'verify') {
            const amount = $('#stAmount').val();
            if (amount === '' || Number(amount) < 0) { toastr.warning('Type the amount received.'); return; }
            data.amountVerified = amount;
        } else {
            const reason = $('#stReason').val().trim();
            if (!reason) { toastr.warning('Write the reason: the buyer sees it.'); $('#stReason').trigger('focus'); return; }
            data.notes = reason;
        }
        const $b = $(this);
        busy($b, true, 'Working…');
        $.ajax({ url: U.verifyBase + '/' + encodeURIComponent(current.can.paymentId) + '/verify', method: 'PUT', data })
            .done((r) => {
                toastr.success((r && r.message ? r.message + '. ' : '') + 'Streamr is applying it now.');
                $.get(U.one, { number: current.number }).done((x) => paint(x.data));
                load(true);
            })
            .fail((x) => { busy($b, false); fail(x, 'That did not work.'); });
    });

    // Opened from a link: ?open=STR-...
    load(true);
    const openNumber = @json($open);
    if (openNumber) { setTimeout(() => open(openNumber), 300); }

    // The counts stay fresh while the page is in view.
    setInterval(() => {
        if (document.visibilityState !== 'visible') { return; }
        $.ajax({ url: U.list, data: { status: 'review', page: 1, q: '' }, _background: true }).done((r) => paintCounts((r.data || {}).counts));
    }, 60000);
});
</script>
@endsection
