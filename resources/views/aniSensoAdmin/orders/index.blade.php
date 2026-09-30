@extends('layouts.master')

@section('title') Orders @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    /* This page's styles load before Bootstrap's, so every rule is scoped
       under #orAdm (or #orModal) to win its ties. Colours come from
       Bootstrap's variables, so the admin's dark mode dresses it too. */
    #orAdm .or-tabs .nav-link { display: flex; align-items: center; gap: .4rem; }
    #orAdm .or-bar { display: flex; flex-wrap: wrap; gap: .75rem; align-items: center; margin-bottom: 1rem; }
    #orAdm .or-chips { display: flex; flex-wrap: wrap; gap: .4rem; }
    #orAdm .or-chip { border: 1px solid var(--bs-border-color); background: var(--bs-body-bg); color: var(--bs-body-color); border-radius: 999px;
        padding: .3rem .8rem; font-size: .85rem; font-weight: 500; display: inline-flex; align-items: center; gap: .4rem;
        transition: background .28s cubic-bezier(.22,1,.36,1), color .28s cubic-bezier(.22,1,.36,1), border-color .28s cubic-bezier(.22,1,.36,1); }
    #orAdm .or-chip:hover { border-color: var(--bs-primary); }
    #orAdm .or-chip.is-on { background: var(--bs-primary); border-color: var(--bs-primary); color: #fff; }
    #orAdm .or-chip .n { font-size: .72rem; padding: 0 .4rem; border-radius: 999px; background: var(--bs-secondary-bg); color: var(--bs-body-color); }
    #orAdm .or-chip.is-on .n { background: rgb(255 255 255 / .25); color: #fff; }
    #orAdm .or-chip.is-hot .n { background: var(--bs-warning); color: #000; }
    #orAdm .or-search { flex: 1 1 14rem; max-width: 22rem; margin-left: auto; }
    #orAdm .or-list { display: grid; gap: .5rem; }
    #orAdm .or-row { display: grid; grid-template-columns: minmax(0, 1fr) auto; gap: .35rem 1rem; align-items: center; text-align: left; width: 100%;
        padding: .8rem 1rem; border: 1px solid var(--bs-border-color); border-radius: .75rem; background: var(--bs-body-bg); color: var(--bs-body-color);
        animation: orIn .32s cubic-bezier(.22,1,.36,1) both; transition: border-color .28s cubic-bezier(.22,1,.36,1), box-shadow .28s cubic-bezier(.22,1,.36,1), transform .28s cubic-bezier(.22,1,.36,1); }
    #orAdm .or-row:hover { border-color: var(--bs-primary); box-shadow: 0 8px 24px -16px rgb(0 0 0 / .35); transform: translateY(-1px); }
    #orAdm .or-row .t { font-weight: 600; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #orAdm .or-row .t small { font-weight: 500; color: var(--bs-secondary-color); margin-left: .35rem; }
    #orAdm .or-row .s { color: var(--bs-secondary-color); font-size: .85rem; min-width: 0; overflow: hidden; text-overflow: ellipsis; white-space: nowrap; }
    #orAdm .or-row .m { text-align: right; font-weight: 700; white-space: nowrap; }
    #orAdm .or-row .b { display: flex; gap: .3rem; justify-content: flex-end; flex-wrap: wrap; }
    #orAdm .or-empty { text-align: center; color: var(--bs-secondary-color); padding: 2.5rem 1rem; border: 1px dashed var(--bs-border-color); border-radius: .75rem; }
    #orAdm .or-more { display: block; margin: 1rem auto 0; }
    #orAdm .or-skel { height: 4.1rem; border-radius: .75rem; background: linear-gradient(90deg, var(--bs-secondary-bg) 0%, var(--bs-tertiary-bg) 50%, var(--bs-secondary-bg) 100%);
        background-size: 200% 100%; animation: orShim 1.2s linear infinite; }
    .or-badge { display: inline-block; font-size: .72rem; font-weight: 600; padding: .15rem .5rem; border-radius: 999px; white-space: nowrap; }
    .or-badge.review { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
    .or-badge.awaiting, .or-badge.cancelled, .or-badge.skipped { background: var(--bs-secondary-bg); color: var(--bs-secondary-color); }
    .or-badge.approved, .or-badge.pass { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
    .or-badge.rejected, .or-badge.revoked, .or-badge.fail { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
    .or-badge.unsure, .or-badge.error { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
    @keyframes orIn { from { opacity: 0; transform: translateY(6px); } to { opacity: 1; transform: none; } }
    @keyframes orShim { to { background-position: -200% 0; } }

    /* One order */
    #orModal .od-facts { display: grid; grid-template-columns: repeat(auto-fill, minmax(9.5rem, 1fr)); gap: .5rem 1rem; }
    #orModal .od-facts div { min-width: 0; }
    #orModal .od-facts span { display: block; font-size: .75rem; color: var(--bs-secondary-color); }
    #orModal .od-facts b { font-weight: 600; word-break: break-word; }
    #orModal .od-sec { margin-top: 1.25rem; }
    #orModal .od-sec h6 { font-size: .75rem; text-transform: uppercase; letter-spacing: .06em; color: var(--bs-secondary-color); margin-bottom: .5rem; }
    #orModal .od-proof img { max-width: 100%; max-height: 26rem; border-radius: .6rem; border: 1px solid var(--bs-border-color); cursor: zoom-in; display: block; }
    #orModal .od-proof iframe { width: 100%; height: 26rem; border: 1px solid var(--bs-border-color); border-radius: .6rem; }
    #orModal .od-verdict { display: block; padding: .6rem .8rem; border-radius: .6rem; }
    #orModal .od-verdict.pass { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
    #orModal .od-verdict.fail { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
    #orModal .od-verdict.unsure, #orModal .od-verdict.error { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
    #orModal .od-verdict.skipped { background: var(--bs-secondary-bg); }
    #orModal .od-checks { margin-top: .5rem; display: grid; gap: .35rem; }
    #orModal .od-check { display: flex; gap: .5rem; align-items: flex-start; }
    #orModal .od-check i { flex: none; width: 1.3rem; height: 1.3rem; border-radius: 999px; display: grid; place-items: center; font-style: normal; font-size: .75rem; font-weight: 700; margin-top: .1rem; }
    #orModal .od-check i.y { background: var(--bs-success-bg-subtle); color: var(--bs-success-text-emphasis); }
    #orModal .od-check i.n { background: var(--bs-danger-bg-subtle); color: var(--bs-danger-text-emphasis); }
    #orModal .od-check i.q { background: var(--bs-warning-bg-subtle); color: var(--bs-warning-text-emphasis); }
    #orModal .od-check small { display: block; color: var(--bs-secondary-color); }
    #orModal .od-time { display: grid; gap: .4rem; border-left: 2px solid var(--bs-border-color); padding-left: .9rem; }
    #orModal .od-time div span { display: block; font-size: .75rem; color: var(--bs-secondary-color); }
    #orModal .od-reason { display: grid; grid-template-rows: 0fr; opacity: 0; transition: grid-template-rows .28s cubic-bezier(.22,1,.36,1), opacity .28s cubic-bezier(.22,1,.36,1); }
    #orModal .od-reason > div { overflow: hidden; }
    #orModal .od-reason.is-on { grid-template-rows: 1fr; opacity: 1; margin-top: 1rem; }
    #orModal .modal-footer { flex-wrap: wrap; gap: .5rem; }
    #orModal .modal-footer .btn { flex: 1 1 auto; }
    #orModal .od-zoom { position: fixed; inset: 0; z-index: 1080; background: rgb(0 0 0 / .85); display: grid; place-items: center; padding: 1rem; opacity: 0;
        pointer-events: none; transition: opacity .28s cubic-bezier(.22,1,.36,1); cursor: zoom-out; }
    #orModal .od-zoom.is-on { opacity: 1; pointer-events: auto; }
    #orModal .od-zoom img { max-width: 100%; max-height: 100%; border-radius: .5rem; }

    /* Payment settings */
    #orAdm .or-qr { width: 8rem; aspect-ratio: 1; border-radius: .6rem; border: 1px solid var(--bs-border-color); object-fit: contain; background: #fff; }
    #orAdm .or-set h5 { font-size: .95rem; margin: 0 0 .25rem; }
    #orAdm .or-set .lead-s { color: var(--bs-secondary-color); font-size: .85rem; margin-bottom: 1rem; }
    @media (max-width: 575.98px) {
        #orAdm .or-search { max-width: none; margin-left: 0; }
        #orAdm .or-row { padding: .7rem .8rem; }
    }
    @media (prefers-reduced-motion: reduce) {
        #orAdm .or-row, #orAdm .or-chip, #orModal .od-reason, #orModal .od-zoom { animation: none; transition: none; }
        #orAdm .or-skel { animation: none; }
    }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') AniSystem @endslot
        @slot('title') Orders @endslot
    @endcomponent

    @if (session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">{{ session('success') }}<button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
    @endif
    @if ($errors->any())
        <div class="alert alert-danger">{{ $errors->first() }}</div>
    @endif
    @unless ($ready)
        <div class="alert alert-warning">anee.io has not made its orders table yet. It appears once anee.io's latest release has run.</div>
    @endunless
    @unless ($linked)
        <div class="alert alert-warning">This app is not linked to anee.io (ANISYSTEM_MEDIA_TOKEN is empty), so orders can be read here but not decided.</div>
    @endunless

    <div id="orAdm">
        <ul class="nav nav-pills or-tabs mb-3" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $errors->any() ? '' : 'active' }}" data-bs-toggle="pill" data-bs-target="#orPaneList" type="button" role="tab">
                    <i class="bx bx-receipt"></i> Orders
                    @if (($counts['review'] ?? 0) > 0)<span class="badge bg-warning text-dark">{{ $counts['review'] }}</span>@endif
                </button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link {{ $errors->any() ? 'active' : '' }}" data-bs-toggle="pill" data-bs-target="#orPaneSet" type="button" role="tab">
                    <i class="bx bx-wallet"></i> Payment settings
                </button>
            </li>
        </ul>

        <div class="tab-content">
            {{-- ================= The orders ================= --}}
            <div class="tab-pane fade {{ $errors->any() ? '' : 'show active' }}" id="orPaneList" role="tabpanel">
                <div class="card"><div class="card-body">
                    <p class="text-secondary mb-3">Purchases on anee.io paid by hand. GCash plans can be approved by Anee on her own when every check on the receipt passes; everything else waits here. Approving, rejecting and revoking are carried out by anee.io, which starts or takes back the plan or credits and tells the buyer.</p>
                    <div class="or-bar">
                        <div class="or-chips" id="orChips">
                            @foreach (['review' => 'To review', 'awaiting' => 'Not paid yet', 'approved' => 'Approved', 'rejected' => 'Rejected', 'revoked' => 'Revoked', 'cancelled' => 'Cancelled', 'all' => 'All'] as $key => $label)
                                @php($n = $key === 'all' ? array_sum($counts) : ($counts[$key] ?? 0))
                                <button type="button" class="or-chip {{ $key === 'review' ? 'is-on' : '' }} {{ $key === 'review' && $n > 0 ? 'is-hot' : '' }}" data-status="{{ $key }}">
                                    {{ $label }} <span class="n">{{ $n }}</span>
                                </button>
                            @endforeach
                        </div>
                        <input type="search" class="form-control or-search" id="orSearch" placeholder="Order no., Ref No., name or email" autocomplete="off">
                    </div>
                    <div class="or-list" id="orList"></div>
                    <button type="button" class="btn btn-light or-more" id="orMore" hidden>Show more</button>
                </div></div>
            </div>

            {{-- ================= Payment settings ================= --}}
            <div class="tab-pane fade {{ $errors->any() ? 'show active' : '' }}" id="orPaneSet" role="tabpanel">
                <form method="POST" action="{{ route('anisenso-orders.settings') }}" enctype="multipart/form-data" class="or-set">
                    @csrf
                    <div class="row g-3">
                        <div class="col-xl-6">
                            <div class="card h-100"><div class="card-body">
                                <h5><i class="bx bx-mobile-alt"></i> GCash</h5>
                                <p class="lead-s">Shown to buyers in the Philippines. The name is written the way GCash itself masks it on the QR and on receipts, because Anee compares it with what the receipt shows.</p>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label" for="gcashNumber">GCash number</label>
                                        <input class="form-control" id="gcashNumber" name="gcashNumber" value="{{ old('gcashNumber', $pay['gcashNumber']) }}" inputmode="numeric" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="gcashName">Name, as GCash masks it</label>
                                        <input class="form-control" id="gcashName" name="gcashName" value="{{ old('gcashName', $pay['gcashName']) }}" required>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="gcashFee">Processing fee (₱)</label>
                                        <input class="form-control" id="gcashFee" name="gcashFee" type="number" step="0.01" min="0" max="100" value="{{ old('gcashFee', $pay['gcashFee']) }}" required>
                                        <div class="form-text">Added to every GCash order, plans and credits alike.</div>
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="reviewHours">"We check within" (hours)</label>
                                        <input class="form-control" id="reviewHours" name="reviewHours" type="number" min="1" max="168" value="{{ old('reviewHours', $pay['reviewHours']) }}" required>
                                        <div class="form-text">What a buyer is told while a person checks their payment.</div>
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check form-switch">
                                            <input type="hidden" name="aiAutoApprove" value="0">
                                            <input class="form-check-input" type="checkbox" role="switch" id="aiAutoApprove" name="aiAutoApprove" value="1" @checked(old('aiAutoApprove', $pay['aiAutoApprove']))>
                                            <label class="form-check-label" for="aiAutoApprove">Let Anee approve a GCash <b>plan</b> herself when every check on the receipt passes</label>
                                        </div>
                                        <div class="form-text">Off: every GCash plan waits here too. AI credits are never approved by her, since they can be spent at once.</div>
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label d-block">QR code</label>
                                        <div class="d-flex gap-3 align-items-start flex-wrap">
                                            <img src="{{ $qrUrl }}" alt="The GCash QR buyers see" class="or-qr" id="orQrPrev">
                                            <div class="flex-grow-1" style="min-width: 12rem">
                                                <input class="form-control" type="file" name="gcashQrFile" id="gcashQrFile" accept="image/*">
                                                <div class="form-text">{{ $pay['gcashQr'] === '' ? 'Showing the built-in QR.' : 'Showing a QR uploaded here.' }} It must be the QR of the number above.</div>
                                                @if ($pay['gcashQr'] !== '')
                                                    <div class="form-check mt-2">
                                                        <input class="form-check-input" type="checkbox" name="gcashQrReset" value="1" id="gcashQrReset">
                                                        <label class="form-check-label" for="gcashQrReset">Go back to the built-in QR</label>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div></div>
                        </div>
                        <div class="col-xl-6">
                            <div class="card h-100"><div class="card-body">
                                <h5><i class="bx bx-building-house"></i> Bank transfer</h5>
                                <p class="lead-s">Offered to buyers only once the bank, the account name and the account number are all filled in. Bank payments are always checked by a person.</p>
                                <div class="row g-3">
                                    <div class="col-sm-6">
                                        <label class="form-label" for="bankName">Bank</label>
                                        <input class="form-control" id="bankName" name="bankName" value="{{ old('bankName', $pay['bankName']) }}" placeholder="e.g. BDO, BPI, Landbank">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="bankBranch">Branch <span class="text-secondary">(optional)</span></label>
                                        <input class="form-control" id="bankBranch" name="bankBranch" value="{{ old('bankBranch', $pay['bankBranch']) }}">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="bankAccountName">Account name</label>
                                        <input class="form-control" id="bankAccountName" name="bankAccountName" value="{{ old('bankAccountName', $pay['bankAccountName']) }}">
                                    </div>
                                    <div class="col-sm-6">
                                        <label class="form-label" for="bankAccountNumber">Account number</label>
                                        <input class="form-control" id="bankAccountNumber" name="bankAccountNumber" value="{{ old('bankAccountNumber', $pay['bankAccountNumber']) }}" inputmode="numeric">
                                    </div>
                                    <div class="col-12">
                                        <label class="form-label" for="bankNote">A note under the account <span class="text-secondary">(optional)</span></label>
                                        <input class="form-control" id="bankNote" name="bankNote" value="{{ old('bankNote', $pay['bankNote']) }}" maxlength="300" placeholder="e.g. InstaPay and PESONet both work">
                                    </div>
                                </div>
                                <hr class="my-4">
                                <h5><i class="bx bx-brain"></i> More for Anee to know about receipts</h5>
                                <p class="lead-s">Anee already knows what a genuine GCash Express Send receipt looks like, the other GCash screens, InstaPay from other apps, and the usual signs of an edited picture. Anything written here is added to that, e.g. a new receipt layout GCash has started using.</p>
                                <textarea class="form-control" name="guide" rows="5" maxlength="4000">{{ old('guide', $guide) }}</textarea>
                            </div></div>
                        </div>
                    </div>
                    <div class="d-flex justify-content-end mt-3">
                        <button type="submit" class="btn btn-primary"><i class="bx bx-save"></i> Save payment settings</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- One order --}}
    <div class="modal fade" id="orModal" tabindex="-1" aria-labelledby="orTitle" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-fullscreen-sm-down">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="orTitle">Order</h5>
                        <div class="small text-secondary" id="orSub"></div>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body" id="orBody"></div>
                <div class="modal-footer" id="orFoot"></div>
            </div>
        </div>
        <div class="od-zoom" id="odZoom"><img alt="The receipt, full size"></div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
(() => {
    const CSRF = "{{ csrf_token() }}";
    const U = {
        list: "{{ route('anisenso-orders.data') }}",
        one: "{{ route('anisenso-orders.one') }}",
        act: "{{ route('anisenso-orders.act') }}",
    };
    const $id = (x) => document.getElementById(x);
    const esc = (s) => String(s ?? '').replace(/[&<>"']/g, (c) => ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c]));
    const aiWord = { pass: 'AI: passed', fail: 'AI: failed', unsure: 'AI: unsure', error: 'AI: could not read', skipped: 'AI: nothing to read' };
    const peso = (o, n) => (o.currency === 'PHP' ? '₱' : '$') + Number(n).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    async function api(url, opts = {}) {
        const res = await fetch(url, { method: opts.method || 'GET', headers: { Accept: 'application/json', 'Content-Type': 'application/json', 'X-CSRF-TOKEN': CSRF },
            body: opts.body ? JSON.stringify(opts.body) : undefined });
        const data = await res.json().catch(() => ({}));
        if (!res.ok || data.success === false) throw new Error(data.message || 'Something went wrong (' + res.status + ').');
        return data;
    }

    // ---- the list
    let status = 'review', q = '', page = 1, busy = false;
    const list = $id('orList'), more = $id('orMore');
    const row = (o, i) => `
        <button type="button" class="or-row" data-order="${o.id}" style="animation-delay:${Math.min(i, 12) * 30}ms">
            <div class="t">${esc(o.item)}<small>${esc(o.number)}</small></div>
            <div class="m">${esc(o.total)}</div>
            <div class="s">${esc(o.buyer)} · ${esc(o.method)} · ${esc(o.at || '')}</div>
            <div class="b">
                ${o.ai ? `<span class="or-badge ${esc(o.ai)}">${esc(aiWord[o.ai] || o.ai)}</span>` : ''}
                <span class="or-badge ${esc(o.status)}">${esc(o.byAi && o.status === 'approved' ? 'Approved by Anee' : o.statusLabel)}</span>
            </div>
        </button>`;
    async function load(reset) {
        if (busy) return;
        busy = true;
        if (reset) {
            page = 1;
            list.innerHTML = '<div class="or-skel"></div><div class="or-skel"></div><div class="or-skel"></div>';
            more.hidden = true;
        }
        try {
            const data = await api(U.list + '?' + new URLSearchParams({ status, q, page }));
            if (reset) list.innerHTML = '';
            list.insertAdjacentHTML('beforeend', data.rows.map(row).join(''));
            if (!list.children.length) {
                list.innerHTML = `<div class="or-empty">${q ? 'No order matches “' + esc(q) + '”.' : status === 'review' ? 'Nothing to review. Every payment has been decided.' : 'No orders here.'}</div>`;
            }
            more.hidden = !data.more;
            page++;
        } catch (e) {
            list.innerHTML = `<div class="or-empty">${esc(e.message)}</div>`;
        } finally {
            busy = false;
        }
    }
    $id('orChips').addEventListener('click', (e) => {
        const chip = e.target.closest('[data-status]');
        if (!chip) return;
        status = chip.dataset.status;
        document.querySelectorAll('#orChips .or-chip').forEach((c) => c.classList.toggle('is-on', c === chip));
        load(true);
    });
    let tq;
    $id('orSearch').addEventListener('input', (e) => { clearTimeout(tq); tq = setTimeout(() => { q = e.target.value.trim(); load(true); }, 350); });
    more.addEventListener('click', () => load(false));

    // ---- one order
    const modal = new bootstrap.Modal($id('orModal'));
    let current = null, pendingAct = null;
    function facts(rows) {
        return '<div class="od-facts">' + rows.filter((r) => r[1] !== null && r[1] !== undefined && r[1] !== '')
            .map((r) => `<div><span>${esc(r[0])}</span><b>${esc(r[1])}</b></div>`).join('') + '</div>';
    }
    function aiBlock(o) {
        const ai = o.ai;
        if (!ai) return `<p class="text-secondary mb-0">${o.methodKey === 'gcash' ? 'Not read yet.' : 'Anee reads GCash receipts only. This one is checked by hand.'}</p>`;
        const icon = (ok) => ok === true ? '<i class="y">✓</i>' : ok === false ? '<i class="n">✕</i>' : '<i class="q">?</i>';
        const read = ai.read || {};
        return `<div class="od-verdict ${esc(ai.verdict)}"><b>${esc(aiWord[ai.verdict] || ai.verdict)}.</b> ${esc(ai.summary || '')}</div>
            ${(ai.checks || []).length ? '<div class="od-checks">' + ai.checks.map((c) => `<div class="od-check">${icon(c.ok)}<div><b>${esc(c.label)}</b><small>${esc(c.detail)}</small></div></div>`).join('') + '</div>' : ''}
            ${read.app ? `<div class="od-sec"><h6>What Anee read</h6>${facts([
                ['App', read.app + (read.kind ? ' · ' + String(read.kind).replace(/_/g, ' ') : '')],
                ['Amount', read.amount != null ? peso(o, read.amount) : '—'],
                ['To', [read.recipientName, read.recipientNumber].filter(Boolean).join(' · ')],
                ['From', read.senderName],
                ['Ref No.', read.ref],
                ['When', read.dateTime],
                ['Sure it is genuine', read.authenticity != null ? read.authenticity + ' / 100' : ''],
                ['Her note', read.notes],
            ])}</div>` : ''}`;
    }
    function paint(o) {
        current = o;
        pendingAct = null;
        $id('orTitle').textContent = o.number;
        $id('orSub').innerHTML = `<span class="or-badge ${esc(o.status)}">${esc(o.statusLabel)}</span> · ${esc(o.item)}`;
        const proof = o.file
            ? (o.file.mime === 'application/pdf'
                ? `<div class="od-proof"><iframe src="${esc(o.file.url)}" title="The receipt (PDF)"></iframe><a href="${esc(o.file.url)}" target="_blank" rel="noopener" class="small">Open the PDF</a></div>`
                : `<div class="od-proof"><img src="${esc(o.file.url)}" alt="The receipt" id="odImg"></div>`)
            : '<p class="text-secondary mb-0">No picture or PDF: only the reference number.</p>';
        $id('orBody').innerHTML = `
            ${facts([['Buyer', o.buyer], ['Bought', o.item], ['Price', o.price], ['Fee', o.fee], ['Total to pay', o.total], ['Paid by', o.method],
                ['Ref No. typed', o.refNumber], ['Their note', o.note]])}
            <div class="od-sec"><h6>The proof</h6>${proof}</div>
            <div class="od-sec"><h6>Anee's check</h6>${aiBlock(o)}</div>
            ${o.methodKey === 'gcash' ? `<div class="od-sec"><h6>What a good receipt must show</h6>${facts([['Total sent', o.expected.total], ['To', o.expected.to], ['Paid', 'after ' + (o.timeline[0]?.at || 'the order opened')]])}</div>` : ''}
            <div class="od-sec"><h6>What happened</h6><div class="od-time">${o.timeline.map((t) => `<div><span>${esc(t.at)}</span><b>${esc(t.say)}</b></div>`).join('')}</div></div>
            <div class="od-reason" id="odReason"><div>
                <label class="form-label" for="odReasonText" id="odReasonLabel">Why?</label>
                <textarea id="odReasonText" class="form-control" rows="2" maxlength="500" placeholder="Told to the buyer, e.g. the amount did not arrive"></textarea>
            </div></div>`;
        const pending = o.status === 'review' || o.status === 'awaiting';
        const linked = @json($linked);
        $id('orFoot').innerHTML = !linked ? '<span class="text-secondary small">Not linked to anee.io: decisions are made from anee.io\'s own admin panel.</span>' : `
            ${pending && o.file && o.methodKey === 'gcash' ? '<button type="button" class="btn btn-light" data-od="recheck"><i class="bx bx-refresh"></i> Ask Anee to read it again</button>' : ''}
            ${pending ? '<button type="button" class="btn btn-outline-danger" data-od="reject">Reject</button><button type="button" class="btn btn-success" data-od="approve"><i class="bx bx-check"></i> Approve</button>' : ''}
            ${o.status === 'approved' ? '<button type="button" class="btn btn-outline-danger" data-od="revoke"><i class="bx bx-undo"></i> Revoke</button>' : ''}
            <button type="button" class="btn btn-primary" data-od="confirm" hidden>Confirm</button>`;
        $id('odImg')?.addEventListener('click', () => { $id('odZoom').querySelector('img').src = o.file.url; $id('odZoom').classList.add('is-on'); });
    }
    async function open(id) {
        $id('orTitle').textContent = 'Loading…';
        $id('orSub').textContent = '';
        $id('orBody').innerHTML = '<div class="or-skel" style="height:12rem"></div>';
        $id('orFoot').innerHTML = '';
        modal.show();
        try { paint((await api(U.one + '?id=' + id)).data); } catch (e) { toastr.error(e.message); }
    }
    list.addEventListener('click', (e) => { const r = e.target.closest('.or-row[data-order]'); if (r) open(r.dataset.order); });
    $id('odZoom').addEventListener('click', () => $id('odZoom').classList.remove('is-on'));

    $id('orFoot').addEventListener('click', async (e) => {
        const b = e.target.closest('[data-od]');
        if (!b || !current) return;
        let act = b.dataset.od;
        const c = $id('orFoot').querySelector('[data-od="confirm"]');
        // Rejecting, revoking and approving all ask once more, in place.
        if (act !== 'confirm' && act !== 'recheck') {
            pendingAct = act;
            $id('orFoot').querySelectorAll('[data-od]').forEach((x) => { if (x !== c) x.hidden = true; });
            c.hidden = false;
            if (act === 'approve') {
                c.className = 'btn btn-success';
                c.textContent = current.kind === 'plan' ? 'Yes, approve: start the plan and tell the buyer' : 'Yes, approve: add the credits and tell the buyer';
            } else {
                $id('odReasonLabel').textContent = act === 'revoke' ? 'Why is it revoked? (the buyer is told)' : 'Why is it rejected? (the buyer is told)';
                $id('odReason').classList.add('is-on');
                c.className = 'btn btn-danger';
                c.textContent = act === 'revoke' ? 'Revoke: take back what it gave' : 'Reject this payment';
                setTimeout(() => $id('odReasonText').focus(), 280);
            }
            $id('orFoot').insertAdjacentHTML('afterbegin', '<button type="button" class="btn btn-light" data-od-back>Back</button>');
            return;
        }
        if (act === 'confirm') act = pendingAct;
        b.disabled = true;
        const was = b.innerHTML;
        b.textContent = act === 'recheck' ? 'Anee is reading…' : 'Working…';
        try {
            const res = await api(U.act + '?' + new URLSearchParams({ id: current.id, action: act }), { method: 'POST', body: { reason: $id('odReasonText')?.value || '' } });
            toastr.success(res.message);
            paint((await api(U.one + '?id=' + current.id)).data);
            load(true);
        } catch (err) {
            toastr.error(err.message);
            b.disabled = false;
            b.innerHTML = was;
        }
    });
    $id('orFoot').addEventListener('click', (e) => { if (e.target.closest('[data-od-back]') && current) paint(current); });

    // Opened from a link: ?open=<id>
    const openId = new URLSearchParams(location.search).get('open');
    load(true);
    if (openId) setTimeout(() => open(openId), 300);

    // A new QR shows before it is saved.
    $id('gcashQrFile')?.addEventListener('change', (e) => {
        const f = e.target.files[0];
        if (f) $id('orQrPrev').src = URL.createObjectURL(f);
    });
})();
</script>
@endsection
