@extends('layouts.master')

@section('title') Streamr Link status @endsection

@section('css')
@include('streamr._styles', ['tables' => false])
@endsection

@php
    $yes = '<i class="bx bx-check-circle ok"></i>';
    $no = '<i class="bx bx-x-circle bad"></i>';
    $when = fn ($at) => $at ? \App\Services\Streamr\StreamrTime::parse($at)->format('M j, Y g:i A') . ' (' . \App\Services\Streamr\StreamrTime::parse($at)->diffForHumans() . ')' : 'never';
    $call = fn ($row) => $row ? $when($row->created_at) . ' · ' . ($row->status ? 'HTTP ' . $row->status : 'not reached') . ($row->errorCode ? ' · ' . $row->errorCode : '') : 'never';
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Streamr @endslot
        @slot('title') Link status @endslot
    @endcomponent

    <div id="st">
        @if (! $config['appUrlSet'] || ! $config['tokenSet'])
            <div class="alert alert-warning">
                <b>Streamr is not linked yet.</b> Set <span class="st-mono">STREAMR_APP_URL</span> (Streamr's address) and <span class="st-mono">STREAMR_API_TOKEN</span>
                (the same long random secret as Streamr's <span class="st-mono">MOTHER_API_TOKEN</span>) in this app's environment, optionally
                <span class="st-mono">STREAMR_STORE_ID</span>, then set <span class="st-mono">MOTHER_APP_URL</span> and <span class="st-mono">MOTHER_API_TOKEN</span> on Streamr and press <b>Test link</b> on both sides.
            </div>
        @endif

        <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-3">
            <p class="text-secondary mb-0">Streamr calls this app at <span class="st-mono">/api/streamr/v1</span>; this app calls Streamr at <span class="st-mono">/api/mother</span>. Both sign every call with the shared token.</p>
            <button type="button" class="btn btn-primary" id="stPing"><i class="bx bx-transfer me-1"></i>Test link</button>
        </div>
        <div class="alert d-none" id="stPingResult" role="status"></div>

        <div class="row g-3">
            <div class="col-xl-6">
                <div class="card h-100 mb-0"><div class="card-body">
                    <h5 class="card-title text-dark mb-3"><i class="bx bx-cog me-1"></i>Configuration</h5>
                    <ul class="list-unstyled mb-0 d-grid gap-2">
                        <li>{!! $config['appUrlSet'] ? $yes : $no !!} <span class="text-dark">STREAMR_APP_URL</span>
                            <span class="sub">{{ $config['appUrlSet'] ? 'set (' . $config['appHost'] . ')' : 'not set' }}</span></li>
                        <li>{!! $config['tokenSet'] ? $yes : $no !!} <span class="text-dark">STREAMR_API_TOKEN</span>
                            <span class="sub">{{ $config['tokenSet'] ? 'set (never shown)' : 'not set: the API answers 503 and nothing is sent to Streamr' }}</span></li>
                        <li>{!! $config['store'] ? $yes : $no !!} <span class="text-dark">Store "{{ $config['storeName'] }}" in E-commerce › Stores</span>
                            <span class="sub">{{ $config['store'] ? 'found (id ' . $config['store']->id . ')' : 'not yet: made with Streamr\'s first order, or add it under E-commerce › Stores' }}</span></li>
                        @if ($config['storeIdSet'])
                            <li>{!! $config['storeIdFound'] && $config['storeIdMatches'] ? $yes : $no !!} <span class="text-dark">STREAMR_STORE_ID</span>
                                <span class="sub">{{ ! $config['storeIdFound'] ? 'no live store has that id' : ($config['storeIdMatches'] ? 'matches the Streamr store' : 'the store id does not match the Streamr store (payment settings are read from that id; orders still go to "' . $config['storeName'] . '")') }}</span></li>
                        @endif
                        <li>{!! $config['qrReadable'] ? $yes : $no !!} <span class="text-dark">GCash QR</span>
                            <span class="sub">{{ $config['qrReadable'] ? 'readable' : 'not readable: Streamr hides the QR method until one is uploaded in Payment reviews › Payment settings' }}</span></li>
                        <li>{!! $config['uploadOk'] ? $yes : $no !!} <span class="text-dark">PHP upload limits</span>
                            <span class="sub">upload_max_filesize {{ $config['uploadMax'] }}, post_max_size {{ $config['postMax'] }}{{ $config['uploadOk'] ? '' : ' (receipts need at least 9 MB)' }}</span></li>
                    </ul>
                </div></div>
            </div>

            <div class="col-xl-6">
                <div class="card h-100 mb-0"><div class="card-body">
                    <h5 class="card-title text-dark mb-3"><i class="bx bx-log-in-circle me-1"></i>Inbound (Streamr → here)</h5>
                    <dl class="row kv mb-0">
                        <dt class="col-sm-5">Last request</dt>
                        <dd class="col-sm-7">{{ $inbound['last'] ? $inbound['last']->method . ' ' . $inbound['last']->path : '—' }}<div class="sub">{{ $call($inbound['last']) }}</div></dd>
                        <dt class="col-sm-5">Requests, 24 h</dt><dd class="col-sm-7">{{ number_format($inbound['count24']) }}</dd>
                        <dt class="col-sm-5">Errors, 24 h</dt><dd class="col-sm-7">{{ number_format($inbound['errors24']) }}</dd>
                        <dt class="col-sm-5">Last GET /config</dt><dd class="col-sm-7">{{ $call($inbound['lastConfig']) }}</dd>
                    </dl>
                    <p class="sub mb-0">Times off by more than 5 minutes between the two servers make every call fail with stale_timestamp: check both clocks (NTP).</p>
                </div></div>
            </div>

            <div class="col-xl-6">
                <div class="card h-100 mb-0"><div class="card-body">
                    <h5 class="card-title text-dark mb-3"><i class="bx bx-log-out-circle me-1"></i>Outbound (here → Streamr)</h5>
                    <dl class="row kv mb-0">
                        <dt class="col-sm-5">Last order doorbell</dt><dd class="col-sm-7">{{ $call($outbound['doorbell']) }}</dd>
                        <dt class="col-sm-5">Last client action</dt><dd class="col-sm-7">{{ $call($outbound['action']) }}</dd>
                        <dt class="col-sm-5">Last support push</dt><dd class="col-sm-7">{{ $call($outbound['support']) }}</dd>
                        <dt class="col-sm-5">Last test</dt><dd class="col-sm-7">{{ $call($outbound['ping']) }}</dd>
                    </dl>
                </div></div>
            </div>

            <div class="col-xl-6">
                <div class="card h-100 mb-0"><div class="card-body">
                    <h5 class="card-title text-dark mb-3"><i class="bx bx-data me-1"></i>Registry, desk and payments</h5>
                    <dl class="row kv mb-0">
                        <dt class="col-sm-5">Clients</dt>
                        <dd class="col-sm-7">{{ number_format($registry['clients']) }}
                            <div class="sub">{{ $registry['newest'] ? 'newest push ' . $registry['newest']->diffForHumans() : 'no push yet' }}</div>
                            @if ($registry['stale'])<span class="badge bg-warning text-dark">No push for over 24 hours</span>@endif
                        </dd>
                        <dt class="col-sm-5">Desk</dt>
                        <dd class="col-sm-7">{{ number_format($desk['waiting']) }} message{{ $desk['waiting'] === 1 ? '' : 's' }} from here not picked up by Streamr for over 5 minutes</dd>
                        <dt class="col-sm-5">Payments</dt>
                        <dd class="col-sm-7"><a href="{{ route('streamr-payments.index') }}">{{ number_format($payments['pending']) }} waiting in /ecom-orders</a>
                            <div class="sub">{{ $payments['oldest'] ? 'oldest ' . $payments['oldest']->diffForHumans() : '' }}</div></dd>
                    </dl>
                </div></div>
            </div>

            <div class="col-12">
                <div class="card mb-0"><div class="card-body">
                    <h5 class="card-title text-dark mb-3"><i class="bx bx-error me-1"></i>Failed client actions</h5>
                    @if ($failedActions->isEmpty())
                        <p class="text-secondary mb-0">None.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>When</th><th>Client</th><th>Action</th><th>By</th><th>Streamr said</th><th></th></tr></thead>
                                <tbody>
                                    @foreach ($failedActions as $a)
                                        <tr>
                                            <td class="text-secondary">{{ $a->created_at ? $a->created_at->format('M j g:i A') : '' }}</td>
                                            <td class="text-dark">#{{ $a->streamrUserId }}</td>
                                            <td class="text-dark">{{ $a->label() }}</td>
                                            <td class="text-secondary">{{ $a->requestedByName }}</td>
                                            <td class="text-secondary">{{ $a->error }}{{ $a->httpStatus ? ' (HTTP ' . $a->httpStatus . ')' : '' }}</td>
                                            <td class="text-end"><button type="button" class="btn btn-sm btn-outline-warning" data-retry="{{ $a->id }}">Retry</button></td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div></div>
            </div>

            <div class="col-12">
                <div class="card"><div class="card-body">
                    <h5 class="card-title text-dark mb-3"><i class="bx bx-list-ul me-1"></i>Recent errors</h5>
                    @if ($apiErrors->isEmpty())
                        <p class="text-secondary mb-0">No errors logged.</p>
                    @else
                        <div class="table-responsive">
                            <table class="table table-sm align-middle mb-0">
                                <thead class="table-light"><tr><th>When</th><th>Way</th><th>Call</th><th>Status</th><th>Code</th><th>What</th></tr></thead>
                                <tbody>
                                    @foreach ($apiErrors as $e)
                                        <tr>
                                            <td class="text-secondary">{{ $e->created_at ? $e->created_at->format('M j g:i A') : '' }}</td>
                                            <td><span class="badge {{ $e->direction === 'in' ? 'bg-info text-white' : 'bg-primary' }}">{{ $e->direction === 'in' ? 'in' : 'out' }}</span></td>
                                            <td class="st-mono text-dark">{{ $e->method }} {{ $e->path }}</td>
                                            <td class="text-dark">{{ $e->status ?: '—' }}</td>
                                            <td class="text-dark">{{ $e->errorCode ?: '—' }}</td>
                                            <td class="text-secondary">{{ $e->note }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    @endif
                </div></div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(function () {
@include('streamr._script-helpers')

    $('#stPing').on('click', function () {
        const $b = $(this);
        busy($b, true, 'Testing…');
        $.post(@json(route('streamr-health.ping')))
            .done((r) => { $('#stPingResult').removeClass('d-none alert-danger').addClass('alert-success').text(r.message + (r.data && r.data.time ? ' Its clock: ' + r.data.time + '.' : '')); })
            .fail((x) => { $('#stPingResult').removeClass('d-none alert-success').addClass('alert-danger').text((x.responseJSON && x.responseJSON.message) || 'Streamr is not reachable.'); })
            .always(() => busy($b, false));
    });

    $('[data-retry]').on('click', function () {
        const $b = $(this);
        busy($b, true, '');
        $.post(@json(route('streamr-health.retry')) + '?' + $.param({ id: $b.data('retry') }))
            .done((r) => { toastr.success(r.message || 'Sent again.'); setTimeout(() => location.reload(), 1200); })
            .fail((x) => { busy($b, false); fail(x, 'Streamr is not reachable — try again.'); });
    });
});
</script>
@endsection
