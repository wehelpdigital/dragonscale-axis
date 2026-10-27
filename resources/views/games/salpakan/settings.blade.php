@extends('layouts.master')

@section('title') {{ $gameName }} Settings @endsection

@section('css')
@include('games.salpakan._styles', ['tables' => false])
@endsection

@php
    $statusBadges = ['ok' => 'bg-success', 'partial' => 'bg-info text-white', 'running' => 'bg-primary', 'failed' => 'bg-danger', 'skipped' => 'bg-warning text-dark'];
    $statusLabels = ['ok' => 'OK', 'partial' => 'Part done', 'running' => 'Running', 'failed' => 'Failed', 'skipped' => 'Skipped'];
    $a = $analytics;
    $recent = $lastRunAt && $lastRunAt->gt(now()->subMinutes(45));
@endphp

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Games @endslot
        @slot('li_2') {{ $gameName }} @endslot
        @slot('title') Settings @endslot
    @endcomponent

    <div id="gs">
        <div class="row">
            {{-- Analytics codes --}}
            <div class="col-xl-7">
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-2">
                            <div>
                                <h4 class="card-title mb-1 text-dark"><i class="bx bx-target-lock me-1"></i>Analytics codes</h4>
                                <p class="text-secondary small mb-0">The tracking codes the game loads. Saving sends them straight to the game server, which is where they live; what you see is what the game last confirmed.</p>
                            </div>
                            <button type="button" class="btn btn-sm btn-light" id="gsReadAnalytics"><i class="bx bx-refresh me-1"></i>Read from the game</button>
                        </div>

                        <div class="small mb-3" id="gsPushStatus">
                            @if ($push['at'])
                                <span class="badge {{ ['accepted' => 'bg-success', 'rejected' => 'bg-danger', 'failed' => 'bg-danger'][$push['status']] ?? 'bg-secondary' }}">{{ ['accepted' => 'Accepted by the game', 'rejected' => 'Rejected by the game', 'failed' => 'Did not reach the game'][$push['status']] ?? 'Unknown' }}</span>
                                <span class="text-secondary ms-1">last saved {{ $push['at']->format('M j, Y g:i A') }}</span>
                                @if ($push['message'] && $push['status'] !== 'accepted')<div class="bad mt-1">{{ $push['message'] }}</div>@endif
                            @else
                                <span class="text-secondary">Not saved from AXIS yet.</span>
                            @endif
                            @if ($analyticsFetchedAt)<span class="text-secondary ms-2">· read from the game {{ $analyticsFetchedAt->diffForHumans() }}</span>@endif
                        </div>

                        <form id="gsAnalyticsForm" autocomplete="off">
                            <div class="alert alert-danger d-none" id="gsAnalyticsError"></div>
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="aEnabled" name="enabled" value="1" {{ ($a['enabled'] ?? false) ? 'checked' : '' }}>
                                <label class="form-check-label text-dark" for="aEnabled">Tracking on (off = the game loads none of these)</label>
                            </div>
                            <div class="row g-3">
                                @foreach ([
                                    'meta_pixel_id' => ['Meta Pixel ID', '123456789012345', 'Events Manager > Data sources.'],
                                    'ga4_measurement_id' => ['Google Analytics 4 measurement ID', 'G-XXXXXXXXXX', 'GA4 > Admin > Data streams.'],
                                    'gtm_container_id' => ['Google Tag Manager container ID', 'GTM-XXXXXXX', 'Optional; tags set up in GTM load through it.'],
                                    'tiktok_pixel_id' => ['TikTok Pixel ID', 'CXXXXXXXXXXXXXXXXXXX', 'TikTok Ads Manager > Events.'],
                                    'google_ads_id' => ['Google Ads conversion ID', 'AW-123456789', 'Google Ads > Conversions > Tag setup.'],
                                    'google_ads_label' => ['Google Ads conversion label', 'AbCdEfGhIjK', 'The label of the purchase conversion.'],
                                ] as $field => [$label, $placeholder, $help])
                                    <div class="col-md-6">
                                        <label class="form-label text-dark" for="a_{{ $field }}">{{ $label }}</label>
                                        <input type="text" class="form-control" id="a_{{ $field }}" name="{{ $field }}" value="{{ $a[$field] ?? '' }}" placeholder="{{ $placeholder }}" maxlength="100">
                                        <div class="invalid-feedback"></div>
                                        <div class="form-text">{{ $help }} Empty = none.</div>
                                    </div>
                                @endforeach
                            </div>

                            <h6 class="text-dark mt-4 mb-1">Server-side secrets <small class="text-secondary fw-normal">(optional)</small></h6>
                            <p class="small text-secondary">Let the game server send purchases to Google and Meta itself, which ad blockers cannot stop. Write-only: typed here, kept by the game, never stored in AXIS and never shown again. Leave blank to keep what the game has.</p>
                            <div class="row g-3">
                                @foreach ([
                                    'ga4_api_secret' => ['GA4 Measurement Protocol API secret', 'has_ga4_api_secret', 'GA4 > Admin > Data streams > Measurement Protocol API secrets.'],
                                    'meta_access_token' => ['Meta Conversions API access token', 'has_meta_access_token', 'Events Manager > Settings > Conversions API.'],
                                ] as $field => [$label, $flag, $help])
                                    <div class="col-md-6">
                                        <label class="form-label text-dark" for="a_{{ $field }}">{{ $label }}</label>
                                        <input type="password" class="form-control" id="a_{{ $field }}" name="{{ $field }}" placeholder="{{ ($a[$flag] ?? false) ? 'The game has one; type to replace it' : 'Not set' }}" autocomplete="new-password">
                                        <div class="invalid-feedback"></div>
                                        <div class="form-text">{{ $help }}</div>
                                        @if ($a[$flag] ?? false)
                                            <div class="form-check mt-1">
                                                <input class="form-check-input" type="checkbox" id="a_clear_{{ $field }}" name="clear_{{ $field }}" value="1">
                                                <label class="form-check-label small text-dark" for="a_clear_{{ $field }}">Remove it from the game</label>
                                            </div>
                                        @endif
                                    </div>
                                @endforeach
                            </div>

                            <div class="mt-4">
                                <button type="submit" class="btn btn-primary" id="gsSaveAnalytics"><i class="bx bx-save me-1"></i>Save to the game</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-xl-5">
                {{-- Game server --}}
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-3 text-dark"><i class="bx bx-server me-1"></i>Game server</h4>
                        <dl class="kv mb-0">
                            <dt>Address</dt><dd class="mono">{{ $server['url'] }}</dd>
                            <dt>Sync token</dt>
                            <dd>
                                @if ($server['tokenSet'])
                                    <span class="ok"><i class="bx bx-check-circle"></i> Set</span> <span class="sub">(SALPAKAN_SYNC_TOKEN)</span>
                                @else
                                    <span class="bad"><i class="bx bx-error-circle"></i> Not set.</span> <span class="sub">Add SALPAKAN_SYNC_TOKEN to this app's environment (the same secret the game server is given); until then nothing is synced.</span>
                                @endif
                            </dd>
                        </dl>
                    </div>
                </div>

                {{-- Acumbamail --}}
                <div class="card">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start gap-2 mb-3">
                            <h4 class="card-title mb-0 text-dark"><i class="bx bx-envelope me-1"></i>Acumbamail</h4>
                            @if (($acumbamailCounts['failed'] ?? 0) > 0)
                                <button type="button" class="btn btn-sm btn-outline-primary" id="gsRetryAcumbamail"><i class="bx bx-refresh me-1"></i>Try failed ones again</button>
                            @endif
                        </div>
                        <dl class="kv">
                            <dt>API key</dt>
                            <dd>
                                @if ($acumbamail['keySet'])
                                    <span class="ok"><i class="bx bx-check-circle"></i> Set</span> <span class="sub">(ACUMBAMAIL_API_KEY)</span>
                                @else
                                    <span class="bad"><i class="bx bx-error-circle"></i> Not set, so nobody is being added.</span> <span class="sub">Add ACUMBAMAIL_API_KEY to this app's environment; players waiting are added on the next sync after that.</span>
                                @endif
                            </dd>
                            <dt>List</dt><dd>{{ $acumbamail['listName'] }} <span class="sub">(#{{ $acumbamail['listId'] }})</span></dd>
                            <dt>Double opt-in</dt><dd>{{ $acumbamail['doubleOptin'] ? 'On: Acumbamail asks each new subscriber to confirm by email' : 'Off: players are added straight to the list' }} <span class="sub">(ACUMBAMAIL_DOUBLE_OPTIN)</span></dd>
                        </dl>
                        <p class="small text-secondary mb-2">Every player with a real email is added once. No email, a Steam placeholder address, or "no marketing" in the game: not added. Someone who opts out later is taken off the list. A refused address is tried again on later syncs, up to {{ $acumbamail['maxAttempts'] }} times.</p>
                        <div>
                            @foreach (\App\Models\GamePlayer::ACUMBAMAIL_STATUSES as $status => $label)
                                <span class="chip">{{ $label }}: <b>{{ number_format($acumbamailCounts[$status] ?? 0) }}</b></span>
                            @endforeach
                        </div>
                    </div>
                </div>

                {{-- /ecom-orders --}}
                <div class="card">
                    <div class="card-body">
                        <h4 class="card-title mb-2 text-dark"><i class="bx bx-store-alt me-1"></i>Logged in E-commerce orders</h4>
                        <p class="small text-secondary">Completed and refunded sales (not store test purchases) are logged in <a href="{{ route('ecom-orders') }}">E-commerce → Orders</a> under the store "{{ $gameName }}", order number {{ config('games.salpakan.ecom_order_prefix') }}-&lt;the game's order id&gt;, in pesos. Pending orders wait until they complete; a refund or cancellation in the store follows on the next sync (a refund is recorded as a processed refund, so the Sales Report nets it out).</p>
                        <p class="mb-2"><span class="chip">Logged: <b>{{ number_format($counts['ecom']) }}</b></span>
                            @if ($counts['ecomErrors'])<span class="chip bad">Could not be logged: <b>{{ number_format($counts['ecomErrors']) }}</b></span>@endif</p>
                        <form id="gsRateForm" class="row g-2 align-items-end">
                            <div class="col-sm-7">
                                <label class="form-label text-dark" for="rRate">Pesos per {{ $currency }}</label>
                                <input type="number" step="0.0001" min="1" max="1000" class="form-control" id="rRate" name="usdToPhpRate" value="{{ $rateIsSet ? $rate : '' }}" placeholder="{{ number_format($defaultRate, 2) }} (default)">
                                <div class="form-text">Used for orders logged from now on; ones already logged keep their rate. Blank = {{ number_format($defaultRate, 2) }} (SALPAKAN_USD_TO_PHP_RATE).</div>
                            </div>
                            <div class="col-sm-5"><button type="submit" class="btn btn-outline-primary w-100" id="gsSaveRate"><i class="bx bx-save me-1"></i>Save rate</button></div>
                        </form>
                    </div>
                </div>
            </div>
        </div>

        {{-- Sync --}}
        <div class="card">
            <div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div style="max-width: 52rem;">
                        <h4 class="card-title mb-1 text-dark"><i class="bx bx-sync me-1"></i>Sync with the game</h4>
                        @if ($recent)
                            <p class="small text-secondary mb-0"><i class="bx bx-check-circle ok"></i> Last sync <span class="text-dark">{{ $lastRunAt->diffForHumans() }}</span>, started by {{ ['scheduler' => "the app's scheduler", 'admin' => 'an admin (Sync now)', 'console' => 'the command line'][$lastRunBy] ?? $lastRunBy }}. It runs every 15 minutes (games:sync) and carries on where it left off.</p>
                        @else
                            <p class="small text-dark mb-0"><i class="bx bx-error bad"></i> No sync {{ $lastRunAt ? 'since ' . $lastRunAt->diffForHumans() : 'yet' }}. It is scheduled every 15 minutes (games:sync); if nothing runs, the scheduler is not running on the server (switch it on in Laravel Cloud), or use Sync now.</p>
                        @endif
                    </div>
                    <button type="button" class="btn btn-primary" id="gsSyncNow"><i class="bx bx-sync me-1"></i>Sync now</button>
                </div>

                <div class="row g-3 mb-3">
                    <div class="col-6 col-lg-3"><div class="stat"><i class="bx bx-group"></i><div><b>{{ number_format($counts['players']) }}</b><span>players kept</span></div></div></div>
                    <div class="col-6 col-lg-3"><div class="stat"><i class="bx bx-calendar"></i><div><b>{{ number_format($counts['activity']) }}</b><span>player-days kept</span></div></div></div>
                    <div class="col-6 col-lg-3"><div class="stat"><i class="bx bx-receipt"></i><div><b>{{ number_format($counts['orders']) }}</b><span>orders kept</span></div></div></div>
                    <div class="col-6 col-lg-3"><div class="stat"><i class="bx bx-store-alt"></i><div><b>{{ number_format($counts['ecom']) }}</b><span>logged in /ecom-orders</span></div></div></div>
                </div>

                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="table-light"><tr><th>Step</th><th>Status</th><th>Last run</th><th>Last success</th><th class="text-end">Rows</th><th>Where it got to</th><th>What it said</th></tr></thead>
                        <tbody>
                        @foreach ($types as $type => $label)
                            @php $s = $states->get($type); @endphp
                            <tr>
                                <td class="text-dark fw-semibold">{{ $label }}</td>
                                <td>@if ($s && $s->lastStatus)<span class="badge {{ $statusBadges[$s->lastStatus] ?? 'bg-secondary' }}">{{ $statusLabels[$s->lastStatus] ?? $s->lastStatus }}</span>@else<span class="text-secondary">Not run</span>@endif
                                    @if ($s && $s->failCount > 1)<div class="sub bad">{{ $s->failCount }} failures in a row</div>@endif</td>
                                <td class="text-dark">{{ $s && $s->lastRunAt ? $s->lastRunAt->format('M j, g:i A') : '—' }}</td>
                                <td class="text-dark">{{ $s && $s->lastSuccessAt ? $s->lastSuccessAt->format('M j, g:i A') : '—' }}</td>
                                <td class="text-end text-dark">{{ $s ? number_format($s->lastCount) : '—' }}</td>
                                <td class="mono">{{ $s && $s->cursorValue ? $s->cursorValue : '—' }}@if ($s && $s->resumePage)<div class="sub">resumes at page {{ $s->resumePage }}</div>@endif</td>
                                <td class="small {{ $s && $s->lastStatus === 'failed' ? 'bad' : 'text-secondary' }}" style="max-width: 28rem; white-space: normal;">{{ $s->lastMessage ?? '' }}</td>
                            </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
$(document).ready(function () {
@include('games.salpakan._script-helpers')

    const csrf = @json(csrf_token());
    const post = (url, data) => $.ajax({ url: url, type: 'POST', data: Object.assign({ _token: csrf }, data || {}) });

    $('#gsAnalyticsForm').on('submit', function (e) {
        e.preventDefault();
        const $form = $(this);
        const $btn = $('#gsSaveAnalytics');
        $form.find('.is-invalid').removeClass('is-invalid');
        $('#gsAnalyticsError').addClass('d-none');
        const data = {};
        $form.serializeArray().forEach((f) => { data[f.name] = f.value; });
        data.enabled = $('#aEnabled').is(':checked') ? 1 : 0;
        busy($btn, true, 'Saving');
        post(@json(route('games-salpakan-settings.analytics')), data)
            .done((r) => { toastr.success(r.message, 'Saved'); setTimeout(() => location.reload(), 900); })
            .fail((xhr) => {
                const errors = xhr.responseJSON?.errors || {};
                Object.keys(errors).forEach((field) => {
                    const $input = $form.find('[name="' + field + '"]');
                    $input.addClass('is-invalid').siblings('.invalid-feedback').text([].concat(errors[field])[0]);
                });
                $('#gsAnalyticsError').removeClass('d-none').text(xhr.responseJSON?.message || 'The codes could not be saved.');
                fail(xhr, 'The codes could not be saved.');
            })
            .always(() => busy($btn, false));
    });

    $('#gsReadAnalytics').on('click', function () {
        const $btn = $(this);
        busy($btn, true, 'Reading');
        post(@json(route('games-salpakan-settings.analytics-refresh')))
            .done((r) => { toastr.success(r.message, 'Done'); setTimeout(() => location.reload(), 700); })
            .fail((xhr) => fail(xhr, 'The game could not be read.'))
            .always(() => busy($btn, false));
    });

    $('#gsSyncNow').on('click', function () {
        const $btn = $(this);
        busy($btn, true, 'Syncing');
        post(@json(route('games-salpakan-settings.sync')))
            .done((r) => {
                if (r.message.indexOf('problems') === -1) { toastr.success(r.message, 'Sync'); } else { toastr.warning(r.message, 'Sync'); }
                setTimeout(() => location.reload(), 1200);
            })
            .fail((xhr) => fail(xhr, 'The sync could not run.'))
            .always(() => busy($btn, false));
    });

    $('#gsRateForm').on('submit', function (e) {
        e.preventDefault();
        const $btn = $('#gsSaveRate');
        busy($btn, true, 'Saving');
        post(@json(route('games-salpakan-settings.rate')), { usdToPhpRate: $('#rRate').val() })
            .done((r) => toastr.success(r.message, 'Saved'))
            .fail((xhr) => fail(xhr, 'The rate could not be saved.'))
            .always(() => busy($btn, false));
    });

    $('#gsRetryAcumbamail').on('click', function () {
        const $btn = $(this);
        busy($btn, true, 'Working');
        post(@json(route('games-salpakan-settings.acumbamail-retry')))
            .done((r) => { toastr.success(r.message, 'Done'); setTimeout(() => location.reload(), 900); })
            .fail((xhr) => fail(xhr, 'That did not work.'))
            .always(() => busy($btn, false));
    });
});
</script>
@endsection
