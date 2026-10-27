@extends('layouts.master')

@section('title') {{ $player->username ?: 'Player #' . $player->remotePlayerId }} @endsection

@section('css')
@include('games.salpakan._styles', ['tables' => false])
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Games @endslot
        @slot('li_2') {{ $gameName }} @endslot
        @slot('li_2_link') {{ route('games-salpakan-players.index') }} @endslot
        @slot('title') {{ $player->username ?: 'Player #' . $player->remotePlayerId }} @endslot
    @endcomponent

    <div id="gs">
        <div class="mb-3">
            <a href="{{ route('games-salpakan-players.index') }}" class="btn btn-sm btn-light"><i class="bx bx-arrow-back me-1"></i>All players</a>
        </div>

        <div class="row">
            {{-- Who they are --}}
            <div class="col-xl-4">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex align-items-center gap-3 mb-3">
                            <div class="avatar-md"><span class="avatar-title rounded-circle bg-primary-subtle text-primary fs-3"><i class="bx bx-user"></i></span></div>
                            <div class="min-w-0">
                                <h5 class="mb-1 text-dark text-truncate">{{ $player->username ?: 'Player #' . $player->remotePlayerId }}</h5>
                                <div class="sub">Player #{{ $player->remotePlayerId }} in {{ $gameName }}</div>
                                <div class="mt-1">
                                    @if ($player->isBanned)<span class="badge bg-danger">Banned</span>@endif
                                    @if ($player->adFree)<span class="badge bg-info text-white">No ads</span>@endif
                                    @if ($player->marketingOptOut)<span class="badge bg-warning text-dark">Opted out of marketing</span>@endif
                                </div>
                            </div>
                        </div>
                        <dl class="kv mb-0">
                            <dt>Email</dt>
                            <dd>
                                @if ($player->email)
                                    {{ $player->email }}
                                    @unless ($player->hasRealEmail())<span class="badge bg-light text-dark ms-1">not a real address</span>@endunless
                                @else
                                    <span class="text-secondary">None</span>
                                @endif
                            </dd>
                            <dt>Signed up with</dt><dd>{{ $player->signUpLabel() ?: '—' }}</dd>
                            <dt>Registered</dt><dd>{{ $player->registeredAt ? $player->registeredAt->format('M j, Y g:i A') : '—' }}</dd>
                            <dt>Last active</dt>
                            <dd>
                                @if ($player->lastActiveAt)
                                    {{ $player->lastActiveAt->format('M j, Y g:i A') }}
                                    <span class="sub">({{ $player->daysInactive() === 0 ? 'today' : $player->daysInactive() . ' days ago' }})</span>
                                @else
                                    <span class="text-secondary">Never played</span>
                                @endif
                            </dd>
                            <dt>Platform last used</dt><dd>{{ $player->lastPlatform ? $player->platformLabel() : '—' }}</dd>
                            <dt>Location</dt><dd>{{ $player->location() ?: '—' }}</dd>
                            <dt>Credits &amp; XP</dt><dd>{{ number_format($player->credits) }} credits · {{ number_format($player->xp) }} XP</dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-dark mb-3"><i class="bx bx-trophy me-1"></i>Rank &amp; record</h5>
                        <dl class="kv mb-0">
                            <dt>Rank</dt><dd>{{ $player->rankName ?: '—' }}@if ($player->rankTier !== null) <span class="sub">(tier {{ $player->rankTier }})</span>@endif</dd>
                            <dt>Elo</dt><dd>{{ $player->elo !== null ? number_format($player->elo) : '—' }}</dd>
                            <dt>Matches</dt>
                            <dd>{{ number_format($player->gamesPlayed) }} played: {{ number_format($player->wins) }} won, {{ number_format($player->losses) }} lost, {{ number_format($player->draws) }} drawn
                                @if ($player->winRate() !== null)<span class="sub">({{ $player->winRate() }}% won)</span>@endif</dd>
                        </dl>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-dark mb-3"><i class="bx bx-envelope me-1"></i>Acumbamail</h5>
                        <p class="mb-1">
                            <span class="badge {{ ['pending' => 'bg-light text-dark', 'subscribed' => 'bg-success', 'skipped' => 'bg-secondary', 'failed' => 'bg-danger', 'unsubscribed' => 'bg-warning text-dark'][$player->acumbamailStatus] ?? 'bg-secondary' }}">{{ $player->acumbamailLabel() }}</span>
                            @if ($player->acumbamailSubscribedAt)<span class="sub ms-1">added {{ $player->acumbamailSubscribedAt->format('M j, Y g:i A') }}@if ($player->acumbamailEmail) as {{ $player->acumbamailEmail }}@endif</span>@endif
                        </p>
                        @if ($player->acumbamailUnsubscribedAt)<p class="sub mb-1">Taken off {{ $player->acumbamailUnsubscribedAt->format('M j, Y g:i A') }}</p>@endif
                        @if ($player->acumbamailMessage)<p class="small text-dark mb-0">{{ $player->acumbamailMessage }}</p>@endif
                    </div>
                </div>
            </div>

            {{-- What they did --}}
            <div class="col-xl-8">
                <div class="row g-3 mb-3">
                    <div class="col-6 col-lg-3"><div class="stat"><i class="bx bx-calendar"></i><div><b>{{ number_format($totals['days']) }}</b><span>days played</span></div></div></div>
                    <div class="col-6 col-lg-3"><div class="stat"><i class="bx bx-joystick"></i><div><b>{{ number_format($totals['matches']) }}</b><span>matches</span></div></div></div>
                    <div class="col-6 col-lg-3"><div class="stat"><i class="bx bx-time-five"></i><div><b>{{ number_format($totals['minutes'] / 60, 1) }}</b><span>hours played</span></div></div></div>
                    <div class="col-6 col-lg-3"><div class="stat"><i class="bx bx-cart"></i><div><b>{{ \App\Models\GameOrder::money($player->totalSpentCents, $currency) }}</b><span>{{ $player->purchasesCount }} {{ \Illuminate\Support\Str::plural('purchase', $player->purchasesCount) }}</span></div></div></div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <div>
                                <h5 class="card-title text-dark mb-1">Activity by day</h5>
                                <p class="sub mb-0">Matches and minutes on each day played (the game server's days, UTC). Up to the last 365 days played.</p>
                            </div>
                            <a href="{{ route('games-salpakan-usage.index', ['player' => $player->remotePlayerId]) }}" class="btn btn-sm btn-outline-primary text-nowrap"><i class="bx bx-history me-1"></i>In Usage History</a>
                        </div>
                        @if ($chart->isEmpty())
                            <div class="text-center py-4">
                                <i class="bx bx-joystick text-secondary" style="font-size: 2.5rem;"></i>
                                <p class="text-dark mt-2 mb-1">No play recorded yet.</p>
                                <small class="text-secondary">Days played appear here after the next sync.</small>
                            </div>
                        @else
                            <div id="gsPlayerChart" class="chart-box"></div>
                        @endif
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title text-dark mb-3">Days played</h5>
                        <div class="table-responsive" style="max-height: 26rem;">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light"><tr><th>Day (UTC)</th><th>Platform</th><th class="text-end">Sessions</th><th class="text-end">Matches</th><th class="text-end">Won</th><th class="text-end">Minutes</th><th>First – last seen</th></tr></thead>
                                <tbody>
                                @forelse ($activity as $day)
                                    <tr>
                                        <td class="text-dark">{{ $day->activityDate->format('D, M j, Y') }}</td>
                                        <td class="text-dark">{{ \App\Models\GamePlayer::PLATFORMS[$day->platform] ?? ($day->platform ?: '—') }}</td>
                                        <td class="text-end text-dark">{{ number_format($day->sessions) }}</td>
                                        <td class="text-end text-dark">{{ number_format($day->matchesPlayed) }}</td>
                                        <td class="text-end text-dark">{{ number_format($day->matchesWon) }}</td>
                                        <td class="text-end text-dark">{{ number_format($day->minutesPlayed) }}</td>
                                        <td class="sub">{{ $day->firstSeenAt ? $day->firstSeenAt->format('g:i A') : '—' }} – {{ $day->lastSeenAt ? $day->lastSeenAt->format('g:i A') : '—' }}</td>
                                    </tr>
                                @empty
                                    <tr><td colspan="7" class="text-center text-secondary py-3">No days played yet.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <h5 class="card-title text-dark mb-0">Orders</h5>
                            <a href="{{ route('games-salpakan-sales.index', ['player' => $player->remotePlayerId]) }}" class="btn btn-sm btn-outline-primary"><i class="bx bx-cart me-1"></i>In Sales &amp; Orders</a>
                        </div>
                        <div class="table-responsive">
                            <table class="table table-sm table-hover mb-0">
                                <thead class="table-light"><tr><th>Ordered</th><th>Item</th><th>Store</th><th class="text-end">Price</th><th>Status</th><th>/ecom-orders</th></tr></thead>
                                <tbody>
                                @forelse ($orders as $order)
                                    <tr>
                                        <td class="text-dark">{{ $order->orderedAt ? $order->orderedAt->format('M j, Y g:i A') : '—' }}</td>
                                        <td><span class="text-dark">{{ $order->itemName ?: $order->productId }}</span><div class="sub">{{ $order->kindLabel() }}@if ($order->itemCategory) · {{ $order->itemCategory }}@endif</div></td>
                                        <td class="text-dark">{{ $order->storeLabel() }}</td>
                                        <td class="text-end text-dark">{{ $order->formattedPrice() }}</td>
                                        <td>
                                            <span class="badge {{ \App\Models\GameOrder::STATUS_BADGES[$order->orderStatus] ?? 'bg-secondary' }}">{{ $order->statusLabel() }}</span>
                                            @if ($order->isTestPurchase)<span class="badge bg-light text-dark">Test</span>@endif
                                        </td>
                                        <td>
                                            @if ($order->ecomOrderId)
                                                <a href="{{ route('ecom-orders') }}" target="_blank" title="Order {{ config('games.salpakan.ecom_order_prefix') }}-{{ $order->remoteOrderId }}">₱{{ number_format((float) $order->ecomPhpAmount, 2) }} <i class="bx bx-link-external"></i></a>
                                            @elseif ($order->ecomError)
                                                <span class="bad small" title="{{ $order->ecomError }}">Not logged</span>
                                            @else
                                                <span class="text-secondary">—</span>
                                            @endif
                                        </td>
                                    </tr>
                                @empty
                                    <tr><td colspan="6" class="text-center text-secondary py-3">No orders.</td></tr>
                                @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
<script>
$(document).ready(function () {
    const days = @json($chart);
    const el = document.querySelector('#gsPlayerChart');
    if (!el || !days.length) { return; }

    new ApexCharts(el, {
        chart: { height: 320, type: 'line', toolbar: { show: false }, zoom: { enabled: false } },
        series: [
            { name: 'Matches', type: 'column', data: days.map((d) => d.matches) },
            { name: 'Won', type: 'column', data: days.map((d) => d.won) },
            { name: 'Minutes', type: 'line', data: days.map((d) => d.minutes) }
        ],
        labels: days.map((d) => d.date),
        xaxis: { type: 'category', tickAmount: Math.min(12, days.length), labels: { rotate: -45 } },
        stroke: { width: [0, 0, 3], curve: 'smooth' },
        plotOptions: { bar: { columnWidth: '60%', borderRadius: 2 } },
        yaxis: [
            { seriesName: 'Matches', title: { text: 'Matches' }, min: 0, forceNiceScale: true },
            { seriesName: 'Matches', show: false },
            { seriesName: 'Minutes', opposite: true, title: { text: 'Minutes' }, min: 0 }
        ],
        tooltip: { shared: true, intersect: false },
        colors: ['#556ee6', '#34c38f', '#f1b44c'],
        legend: { position: 'top' }
    }).render();
});
</script>
@endsection
