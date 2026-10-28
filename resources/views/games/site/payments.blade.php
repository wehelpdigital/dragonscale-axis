@extends('layouts.master')

@section('title') Website: Payments @endsection

@section('css')
@include('games.salpakan._styles', ['tables' => false])
<style>
    #gs .proof { max-height: 70vh; width: 100%; object-fit: contain; background: var(--bs-secondary-bg); border-radius: .6rem; }
    #gs .check-row { display: flex; gap: .5rem; padding: .35rem 0; border-bottom: 1px dashed var(--bs-border-color); font-size: .84rem; }
    #gs .check-row:last-child { border-bottom: 0; }
    #gs .check-row i { font-size: 1.1rem; flex: none; }
    #gs .qr-preview { max-width: 160px; border-radius: .6rem; background: #fff; padding: .4rem; }
</style>
@endsection

@php
    $badges = \App\Models\GamePayment::STATUS_BADGES;
    $labels = \App\Models\GamePayment::STATUSES;
    $waiting = ($counts['review'] ?? 0) + ($counts['approved'] ?? 0) + ($counts['checking'] ?? 0);
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Games @endslot
        @slot('li_2') Website @endslot
        @slot('title') Payments @endslot
    @endcomponent

    <div id="gs">
        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if ($errors->any())
            <div class="alert alert-danger">{{ $errors->first() }}</div>
        @endif

        <div class="row g-3 mb-3">
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-time-five"></i><div><b>{{ number_format($waiting) }}</b><span>Waiting for a person or the game</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-check-circle"></i><div><b>{{ number_format($counts['paid'] ?? 0) }}</b><span>Paid</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-money"></i><div><b>₱{{ number_format($paidTotal, 2) }}</b><span>Taken (fees included)</span></div></div></div>
            <div class="col-sm-6 col-xl-3"><div class="stat"><i class="bx bx-x-circle"></i><div><b>{{ number_format(($counts['rejected'] ?? 0) + ($counts['expired'] ?? 0)) }}</b><span>Rejected or expired</span></div></div></div>
        </div>

        <div class="row">
            <div class="col-xl-8">
                <div class="card"><div class="card-body">
                    <form class="row g-2 filters mb-3" method="GET">
                        <div class="col-sm-5">
                            <label class="form-label" for="q">Search</label>
                            <input class="form-control" id="q" name="q" value="{{ $search }}" placeholder="Player, email, item, reference">
                        </div>
                        <div class="col-sm-4">
                            <label class="form-label" for="statusFilter">Status</label>
                            <select class="form-select" id="statusFilter" name="status">
                                <option value="">All</option>
                                @foreach ($labels as $value => $label)
                                    <option value="{{ $value }}" @selected($status === $value)>{{ $label }} ({{ $counts[$value] ?? 0 }})</option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-sm-3 d-flex align-items-end"><button class="btn btn-primary w-100"><i class="bx bx-filter-alt me-1"></i>Show</button></div>
                    </form>

                    <div class="table-responsive">
                        <table class="table table-sm table-hover align-middle mb-0">
                            <thead><tr><th>When</th><th>Player</th><th>Item</th><th class="text-end">Total</th><th>AI</th><th>Status</th></tr></thead>
                            <tbody>
                                @forelse ($payments as $p)
                                    <tr class="is-clickable" data-id="{{ $p->id }}">
                                        <td><div>{{ $p->created_at?->format('M j, g:i A') }}</div><div class="sub">{{ $p->created_at?->diffForHumans() }}</div></td>
                                        <td><div class="text-dark">{{ $p->username ?: '—' }}</div><div class="sub">{{ $p->email }}</div></td>
                                        <td><div>{{ $p->itemName }}</div><div class="sub">{{ $p->itemCategory }}</div></td>
                                        <td class="text-end"><div class="text-dark">₱{{ number_format((float) $p->totalPhp, 2) }}</div><div class="sub">${{ number_format($p->priceUsdCents / 100, 2) }}</div></td>
                                        <td>
                                            @if ($p->aiStatus)
                                                <span class="badge {{ ['pass' => 'bg-success', 'fail' => 'bg-danger', 'unsure' => 'bg-warning text-dark', 'running' => 'bg-info text-white'][$p->aiStatus] ?? 'bg-secondary' }}">{{ ucfirst($p->aiStatus) }}</span>
                                                @if ($p->aiScore !== null)<div class="sub">{{ $p->aiScore }}/100</div>@endif
                                            @else
                                                <span class="text-secondary">—</span>
                                            @endif
                                        </td>
                                        <td><span class="badge {{ $badges[$p->status] ?? 'bg-secondary' }}">{{ $labels[$p->status] ?? $p->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-4">No payments{{ $status || $search ? ' match' : ' yet' }}.</td></tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">{{ $payments->links() }}</div>
                </div></div>
            </div>

            <div class="col-xl-4">
                <div class="card"><div class="card-body">
                    <h4 class="card-title text-dark mb-1"><i class="bx bx-wallet me-1"></i>Payment settings</h4>
                    <p class="small text-secondary">Where buyers send money, and what the AI may do. Saved here, used by the website's next checkout page.</p>
                    @unless ($gcashReady)
                        <div class="alert alert-warning small">GCash is not ready: set the number and the name as GCash shows it.</div>
                    @endunless
                    <form method="POST" action="{{ route('games-payments.settings') }}" enctype="multipart/form-data">
                        @csrf
                        <div class="mb-3">
                            <label class="form-label text-dark" for="gcashNumber">GCash number</label>
                            <input class="form-control" id="gcashNumber" name="gcashNumber" value="{{ old('gcashNumber', $pay['gcashNumber']) }}" placeholder="09XXXXXXXXX" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark" for="gcashName">Name, as GCash shows it</label>
                            <input class="form-control" id="gcashName" name="gcashName" value="{{ old('gcashName', $pay['gcashName']) }}" placeholder="JU*N DE* C." required>
                            <div class="form-text">Masked the way GCash prints it on receipts. The AI matches receipts against it.</div>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark" for="gcashQrFile">QR code <span class="text-secondary small">(optional)</span></label>
                            @if ($qrUrl)
                                <div class="d-flex align-items-center gap-2 mb-2"><img src="{{ $qrUrl }}" alt="" class="qr-preview"><label class="small"><input type="checkbox" name="gcashQrReset" value="1" class="form-check-input me-1">Remove</label></div>
                            @endif
                            <input type="file" class="form-control" id="gcashQrFile" name="gcashQrFile" accept="image/*">
                        </div>
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <label class="form-label text-dark" for="gcashFee">AI check fee (₱)</label>
                                <input type="number" step="0.01" min="0" max="100" class="form-control" id="gcashFee" name="gcashFee" value="{{ old('gcashFee', $pay['gcashFee']) }}" required>
                            </div>
                            <div class="col-6">
                                <label class="form-label text-dark" for="reviewHours">Reply within (hours)</label>
                                <input type="number" min="1" max="168" class="form-control" id="reviewHours" name="reviewHours" value="{{ old('reviewHours', $pay['reviewHours']) }}" required>
                            </div>
                        </div>
                        <div class="form-check form-switch mb-2">
                            <input class="form-check-input" type="checkbox" id="aiCheck" name="aiCheck" value="1" @checked(old('aiCheck', $pay['aiCheck']))>
                            <label class="form-check-label text-dark" for="aiCheck">The AI reads every receipt</label>
                        </div>
                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" id="aiAutoApprove" name="aiAutoApprove" value="1" @checked(old('aiAutoApprove', $pay['aiAutoApprove']))>
                            <label class="form-check-label text-dark" for="aiAutoApprove">The AI approves on its own when every check passes</label>
                        </div>
                        <div class="mb-3">
                            <label class="form-label text-dark" for="receiptGuide">More for the AI to know about genuine receipts</label>
                            <textarea class="form-control" id="receiptGuide" name="receiptGuide" rows="3" maxlength="4000">{{ old('receiptGuide', $pay['receiptGuide']) }}</textarea>
                        </div>
                        <button class="btn btn-primary"><i class="bx bx-save me-1"></i>Save</button>
                    </form>
                    <p class="small text-secondary mt-3 mb-0">The AI is the one set in AniSystem &gt; AI Settings. PayPal shows on the website as "coming soon".</p>
                </div></div>
            </div>
        </div>

        <div class="modal fade" id="payModal" tabindex="-1" aria-hidden="true">
            <div class="modal-dialog modal-xl modal-dialog-scrollable"><div class="modal-content">
                <div class="modal-header"><h5 class="modal-title text-dark" id="payTitle">Payment</h5><button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button></div>
                <div class="modal-body" id="payBody"><div class="text-center py-5"><i class="bx bx-loader-alt bx-spin fs-2"></i></div></div>
                <div class="modal-footer flex-wrap" id="payActions"></div>
            </div></div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    @include('games.salpakan._script-helpers')
    (function () {
        const urls = {
            show: @json(route('games-payments.show')),
            approve: @json(route('games-payments.approve')),
            reject: @json(route('games-payments.reject')),
            recheck: @json(route('games-payments.recheck')),
            deliver: @json(route('games-payments.deliver')),
        };
        const token = @json(csrf_token());
        const modal = new bootstrap.Modal(document.getElementById('payModal'));
        const peso = (n) => '₱' + Number(n || 0).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        let current = null;

        const checkIcon = (ok) => ok === true ? '<i class="bx bx-check-circle ok"></i>' : (ok === false ? '<i class="bx bx-x-circle bad"></i>' : '<i class="bx bx-help-circle text-warning"></i>');

        function render(p) {
            current = p;
            $('#payTitle').text(p.item + ' · ' + (p.player || 'player') + ' · ' + p.statusLabel);
            const ai = p.ai || {};
            const checks = (ai.checks || []).map((c) => '<div class="check-row">' + checkIcon(c.ok) + '<div><div class="text-dark">' + esc(c.label) + '</div><div class="sub">' + esc(c.detail) + '</div></div></div>').join('');
            const read = ai.read || {};
            $('#payBody').html(
                '<div class="row g-4"><div class="col-lg-6">'
                + (p.proofUrl ? '<a href="' + esc(p.proofUrl) + '" target="_blank" rel="noopener"><img src="' + esc(p.proofUrl) + '" alt="Receipt" class="proof"></a><div class="sub mt-1">Upload ' + esc(p.uploads) + ' of 3 · ' + esc(p.uploadedAt || '') + '</div>' : '<div class="text-secondary text-center py-5">No receipt yet.</div>')
                + '</div><div class="col-lg-6">'
                + '<dl class="row kv mb-2">'
                + '<dt class="col-5">Order</dt><dd class="col-7 mono">' + esc(p.orderId || '—') + '</dd>'
                + '<dt class="col-5">Player</dt><dd class="col-7">' + esc(p.player || '—') + (p.playerId ? ' <span class="sub">#' + esc(p.playerId) + '</span>' : '') + '<div class="sub">' + esc(p.email || '') + '</div></dd>'
                + '<dt class="col-5">Item</dt><dd class="col-7">' + esc(p.item) + ' <span class="sub">' + esc(p.category || '') + '</span></dd>'
                + '<dt class="col-5">Amount</dt><dd class="col-7">' + peso(p.price) + ' + ' + peso(p.fee) + ' AI fee = <b>' + peso(p.total) + '</b> <span class="sub">($' + Number(p.usd).toFixed(2) + ')</span></dd>'
                + '<dt class="col-5">Reference typed</dt><dd class="col-7 mono">' + esc(p.refNumber || '—') + '</dd>'
                + '<dt class="col-5">Opened</dt><dd class="col-7">' + esc(p.createdAt) + ' <span class="sub">link until ' + esc(p.expiresAt || '—') + '</span></dd>'
                + (p.fulfilledAt ? '<dt class="col-5">Item given</dt><dd class="col-7 ok">' + esc(p.fulfilledAt) + '</dd>' : '')
                + (p.fulfillError ? '<dt class="col-5">The game said</dt><dd class="col-7 bad">' + esc(p.fulfillError) + '</dd>' : '')
                + (p.reviewNote ? '<dt class="col-5">Note</dt><dd class="col-7">' + esc(p.reviewNote) + '</dd>' : '')
                + '</dl>'
                + (ai.verdict ? '<h6 class="text-dark mt-3">The AI: ' + esc(ai.verdict) + (read.authenticity !== undefined ? ' <span class="sub">' + esc(read.authenticity) + '/100 sure</span>' : '') + '</h6><p class="small">' + esc(ai.summary || '') + '</p>' + checks
                    + (read.notes ? '<div class="sub mt-2">“' + esc(read.notes) + '”</div>' : '') : '')
                + '<div class="mt-3"><label class="form-label text-dark small" for="payNote">Note (shown to the buyer when you reject)</label><textarea class="form-control" id="payNote" rows="2" maxlength="500"></textarea></div>'
                + '</div></div>'
            );
            const btn = (name, cls, icon, label) => '<button type="button" class="btn ' + cls + '" data-act="' + name + '"><i class="bx ' + icon + ' me-1"></i>' + label + '</button>';
            $('#payActions').html(
                (p.can.recheck ? btn('recheck', 'btn-light', 'bx-refresh', 'Read again') : '')
                + (p.can.deliver ? btn('deliver', 'btn-info', 'bx-send', 'Send to the game again') : '')
                + (p.can.reject ? btn('reject', 'btn-danger', 'bx-x', 'Reject') : '')
                + (p.can.approve ? btn('approve', 'btn-success', 'bx-check', 'Approve and give the item') : '')
                + '<button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>'
            );
        }

        function open(id) {
            $('#payBody').html('<div class="text-center py-5"><i class="bx bx-loader-alt bx-spin fs-2"></i></div>');
            $('#payActions').empty();
            modal.show();
            $.get(urls.show, { id }).done((r) => render(r.payment)).fail((x) => fail(x, 'Could not load the payment.'));
        }

        $('tr[data-id]').on('click', function () { open($(this).data('id')); });
        $(document).on('click', '[data-act]', function () {
            const act = $(this).data('act');
            const note = $('#payNote').val();
            if (act === 'reject' && !note.trim()) { toastr.warning('Say why in the note: the buyer reads it.'); $('#payNote').focus(); return; }
            if (act === 'approve' && !confirm('Approve this payment? The game gives the item at once.')) { return; }
            const $b = $(this);
            busy($b, true, '');
            $.post(urls[act], { id: current.id, note, _token: token })
                .done((r) => { toastr.success(r.message); open(current.id); setTimeout(() => location.reload(), 1600); })
                .fail((x) => { busy($b, false); fail(x, 'That did not work.'); });
        });
    })();
</script>
@endsection
