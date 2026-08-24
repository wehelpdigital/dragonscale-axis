@extends('layouts.master')

@section('title') Find Leads @endsection

@section('css')
<!-- Select2 -->
<link href="{{ URL::asset('build/libs/select2/css/select2.min.css') }}" rel="stylesheet" type="text/css" />
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<!-- SweetAlert2 -->
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />

<style>
/* Match the Bootstrap control height so Select2 lines up with the other inputs. */
.select2-container--default .select2-selection--single {
    height: 38px;
    border-color: #ced4da;
}
.select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
    color: #495057;
}
.select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
.outreach-status-chip {
    display: inline-flex;
    align-items: center;
    gap: 0.35rem;
    padding: 0.35rem 0.7rem;
    border-radius: 1rem;
    font-size: 0.8125rem;
    font-weight: 500;
}
.outreach-batch-item {
    padding: 0.75rem;
    border-bottom: 1px solid #e9ecef;
    cursor: pointer;
    transition: background 0.2s;
}
.outreach-batch-item:hover {
    background: #f8f9fa;
}
.outreach-batch-item:last-child {
    border-bottom: none;
}
.outreach-batch-item.is-selected {
    background: #eff2f7;
}
.outreach-batch-list {
    max-height: 340px;
    overflow-y: auto;
}
.outreach-leads-scroll {
    max-height: 380px;
    overflow-y: auto;
}
.outreach-batch-id {
    font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace;
    font-size: 0.75rem;
    word-break: break-all;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Lead Finder @endslot
@slot('title') Find Leads @endslot
@endcomponent

<!-- The cron is the real engine; the button below only exists so the screen moves
     before the schedule entry has been installed. -->
<div class="alert alert-info alert-dismissible fade show" role="alert">
    <i class="bx bx-info-circle me-2"></i>
    <span class="text-dark">
        Searching runs on its own once the scheduler is installed: <code>outreach:scrape-grids</code> works a few
        grid cells every two minutes, and <code>outreach:enrich-leads</code> hunts for the email addresses right
        after. Until that cron entry exists, use <strong>Process next batch now</strong> to move the queue by hand.
    </span>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>

@if(!$hasPlacesKey)
<div class="alert alert-warning" role="alert">
    <i class="bx bx-key me-2"></i>
    <span class="text-dark">No Google Places API key is saved, so a search cannot be started yet.</span>
    <a href="{{ route('outreach.settings') }}" class="alert-link ms-1">Add the key in Settings</a>
</div>
@endif

<div class="row">
    <!-- Search form -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title text-dark mb-3">
                    <i class="bx bx-search-alt me-1"></i>New Region Search
                </h4>

                <div class="mb-3">
                    <label for="businessType" class="form-label text-dark">
                        Business Type <span class="text-danger">*</span>
                    </label>
                    <input type="text" class="form-control" id="businessType" name="businessType"
                           maxlength="190" placeholder="e.g. dental clinic, resort, coffee shop"
                           list="businessTypeSuggestions">
                    <datalist id="businessTypeSuggestions">
                        <option value="dental clinic"></option>
                        <option value="veterinary clinic"></option>
                        <option value="resort"></option>
                        <option value="hotel"></option>
                        <option value="restaurant"></option>
                        <option value="coffee shop"></option>
                        <option value="gym"></option>
                        <option value="salon"></option>
                        <option value="hardware store"></option>
                        <option value="car repair shop"></option>
                    </datalist>
                    <small class="text-secondary">This is passed straight to Google Places as the keyword.</small>
                </div>

                <div class="mb-3">
                    <label for="regionLabel" class="form-label text-dark">
                        Province / Region <span class="text-danger">*</span>
                    </label>
                    <select class="form-select" id="regionLabel" name="regionLabel" data-placeholder="Choose a region">
                        <option value=""></option>
                        @foreach($regions as $region)
                            <option value="{{ $region }}">{{ $region }}</option>
                        @endforeach
                    </select>
                    <small class="text-secondary">Only regions with a stored bounding box can be tiled.</small>
                </div>

                <div class="mb-3">
                    <label for="radiusKm" class="form-label text-dark">Grid Size</label>
                    <select class="form-select" id="radiusKm" name="radiusKm">
                        @foreach($radiusOptions as $option)
                            <option value="{{ $option }}"
                                @if((float) $option === (float) $defaultRadiusKm) selected @endif>
                                {{ rtrim(rtrim(number_format($option, 1), '0'), '.') }} km
                                @if((float) $option === (float) $defaultRadiusKm) (default) @endif
                            </option>
                        @endforeach
                    </select>
                    <small class="text-secondary" id="radiusHint"></small>
                </div>

                <div class="mb-3">
                    <label for="maxLeads" class="form-label text-dark">Lead Limit</label>
                    <select class="form-select" id="maxLeads" name="maxLeads">
                        @foreach($maxLeadOptions as $option)
                            <option value="{{ $option }}" @if($option === 0) selected @endif>
                                {{ $option === 0 ? 'No limit - sweep the whole region' : 'Stop after ' . number_format($option) . ' businesses' }}
                            </option>
                        @endforeach
                    </select>
                    <small class="text-secondary">Handy for testing: a small limit runs the whole
                    pipeline end to end in a couple of minutes. The sweep stops as soon as the limit
                    is reached and the remaining cells are closed, so the batch still completes.</small>
                </div>

                <div class="alert alert-light border mb-3" id="gridEstimate">
                    <span class="text-secondary">Pick a region to see how many cells it tiles into.</span>
                </div>

                <div class="d-grid">
                    <button type="button" class="btn btn-primary" id="startSearchBtn" @if(!$hasPlacesKey) disabled @endif>
                        <i class="bx bx-play-circle me-1"></i>Start Search
                    </button>
                </div>
            </div>
        </div>

        <!-- Recent searches -->
        <div class="card">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h6 class="mb-0 text-dark"><i class="bx bx-history me-2"></i>Recent Searches</h6>
            </div>
            <div class="card-body p-0">
                <div class="outreach-batch-list" id="recentBatchList">
                    @forelse($recentBatches as $batch)
                        <div class="outreach-batch-item" data-batch-id="{{ $batch['batchId'] }}">
                            <div class="d-flex justify-content-between align-items-start gap-2">
                                <div class="flex-grow-1">
                                    <h6 class="mb-1 text-dark">{{ $batch['businessType'] }}</h6>
                                    <small class="text-secondary d-block">
                                        {{ $batch['regionLabel'] }} &middot; {{ $batch['totalCells'] }} cells
                                        &middot; {{ $batch['newLeadsCount'] }} leads
                                    </small>
                                    <small class="text-secondary">{{ $batch['startedAt'] }}</small>
                                </div>
                                @if($batch['pendingCells'] > 0)
                                    <span class="badge bg-warning text-dark">{{ $batch['pendingCells'] }} pending</span>
                                @else
                                    <span class="badge bg-success text-white">Done</span>
                                @endif
                            </div>
                        </div>
                    @empty
                        <div class="text-center py-4">
                            <i class="bx bx-map-alt text-secondary" style="font-size: 2.5rem;"></i>
                            <p class="text-dark mt-2 mb-1">No searches yet.</p>
                            <small class="text-secondary">Start one above and it will show up here.</small>
                        </div>
                    @endforelse
                </div>
            </div>
        </div>
    </div>

    <!-- Live progress -->
    <div class="col-xl-8">
        <div class="card" id="progressPanel">
            <div class="card-body">
                <div class="d-flex flex-wrap align-items-start justify-content-between gap-2 mb-3">
                    <div>
                        <h4 class="card-title text-dark mb-1">
                            <i class="bx bx-radar me-1"></i>Search Progress
                        </h4>
                        <div class="text-secondary" id="progressMeta">No batch selected yet.</div>
                        <div class="text-secondary outreach-batch-id mt-1 d-none" id="progressBatchId"></div>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="button" class="btn btn-success btn-sm" id="runBatchBtn" disabled>
                            <i class="bx bx-fast-forward me-1"></i>Process next batch now
                        </button>
                        <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshProgressBtn" disabled>
                            <i class="bx bx-refresh me-1"></i>Refresh
                        </button>
                        <button type="button" class="btn btn-outline-danger btn-sm" id="cancelBatchBtn" disabled>
                            <i class="bx bx-stop-circle me-1"></i>Cancel
                        </button>
                    </div>
                </div>

                <!-- Headline counters for the selected batch -->
                <div class="row">
                    @include('outreach::partials._stat-card', [
                        'id' => 'statCellsTotal',
                        'label' => 'Grid Cells',
                        'icon' => 'bx-grid-alt',
                        'accent' => 'primary',
                        'value' => '0',
                        'hint' => 'Tiled for this batch',
                        'hintId' => 'statCellsTotalHint',
                        'col' => 'col-xl-3 col-md-6',
                    ])
                    @include('outreach::partials._stat-card', [
                        'id' => 'statCellsDone',
                        'label' => 'Cells Finished',
                        'icon' => 'bx-check-circle',
                        'accent' => 'success',
                        'value' => '0',
                        'hint' => 'Completed, split or failed',
                        'hintId' => 'statCellsDoneHint',
                        'col' => 'col-xl-3 col-md-6',
                    ])
                    @include('outreach::partials._stat-card', [
                        'id' => 'statCellsPending',
                        'label' => 'Cells Waiting',
                        'icon' => 'bx-time-five',
                        'accent' => 'warning',
                        'value' => '0',
                        'hint' => 'Pending plus processing',
                        'hintId' => 'statCellsPendingHint',
                        'col' => 'col-xl-3 col-md-6',
                    ])
                    @include('outreach::partials._stat-card', [
                        'id' => 'statNewLeads',
                        'label' => 'New Leads',
                        'icon' => 'bx-user-plus',
                        'accent' => 'info',
                        'value' => '0',
                        'hint' => '0 raw results seen',
                        'hintId' => 'statNewLeadsHint',
                        'col' => 'col-xl-3 col-md-6',
                    ])
                </div>

                <!-- Progress bar -->
                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-baseline mb-1">
                        <span class="text-dark fw-medium" id="progressState">Idle</span>
                        <span class="text-secondary" id="progressPercentLabel">0%</span>
                    </div>
                    <div class="progress" style="height: 12px;">
                        <div class="progress-bar bg-primary progress-bar-striped progress-bar-animated"
                             id="progressBar" role="progressbar" style="width: 0%;"
                             aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                    </div>
                </div>

                <!-- Per-status grid counts -->
                <div class="d-flex flex-wrap gap-2 mb-3" id="statusChips">
                    <span class="outreach-status-chip bg-light text-dark">
                        <i class="bx bx-time-five"></i>Pending <strong id="countPending">0</strong>
                    </span>
                    <span class="outreach-status-chip bg-info text-white">
                        <i class="bx bx-loader-alt"></i>Processing <strong id="countProcessing">0</strong>
                    </span>
                    <span class="outreach-status-chip bg-success text-white">
                        <i class="bx bx-check"></i>Completed <strong id="countCompleted">0</strong>
                    </span>
                    <span class="outreach-status-chip bg-warning text-dark">
                        <i class="bx bx-git-branch"></i>Split <strong id="countSplit">0</strong>
                    </span>
                    <span class="outreach-status-chip bg-danger text-white">
                        <i class="bx bx-x"></i>Failed <strong id="countFailed">0</strong>
                    </span>
                </div>

                <div class="alert alert-danger d-none" role="alert" id="batchError">
                    <i class="bx bx-error-circle me-2"></i><span class="text-dark" id="batchErrorText"></span>
                </div>

                <!-- Newest leads -->
                <h6 class="text-dark mb-2"><i class="bx bx-list-ul me-1"></i>Newest Leads</h6>
                <div class="table-responsive outreach-leads-scroll">
                    <table class="table table-hover align-middle mb-0">
                        <thead>
                            <tr>
                                <th class="text-dark">Business</th>
                                <th class="text-dark">Location</th>
                                <th class="text-dark">Email</th>
                                <th class="text-dark">Enrichment</th>
                            </tr>
                        </thead>
                        <tbody id="recentLeadsBody">
                            <tr>
                                <td colspan="4" class="text-center py-4">
                                    <i class="bx bx-user-plus text-secondary" style="font-size: 2.5rem;"></i>
                                    <p class="text-dark mt-2 mb-1">No leads for this batch yet.</p>
                                    <small class="text-secondary">They appear as soon as the first grid cell is worked.</small>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <div class="text-end mt-3">
                    <a href="{{ route('outreach.leads') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bx bx-table me-1"></i>Open the full lead list
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<!-- Select2 -->
<script src="{{ URL::asset('build/libs/select2/js/select2.min.js') }}"></script>
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<!-- SweetAlert2 -->
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>

<script>
$(document).ready(function () {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    // A batch is only "active" while cells are pending or processing. The timer is
    // started when that is true and cleared the moment it stops being true - an
    // idle tab must never keep hitting the progress endpoint forever.
    var POLL_INTERVAL_MS = 5000;
    var pollTimer = null;
    var currentBatchId = null;
    var busy = false;
    var estimateRequest = null;

    // Last per-status counts we rendered. Kept so the buttons can be re-enabled the
    // instant an inline run finishes, without paying for another progress request.
    var lastCounts = { pending: 0, processing: 0 };

    var ENRICHMENT_BADGES = {
        pending: { css: 'bg-light text-dark', label: 'Pending' },
        processing: { css: 'bg-info text-white', label: 'Processing' },
        enriched: { css: 'bg-success text-white', label: 'Enriched' },
        failed: { css: 'bg-danger text-white', label: 'Failed' },
        skipped: { css: 'bg-warning text-dark', label: 'Skipped' }
    };

    // Everything that came out of the database goes through this before it is
    // concatenated into markup.
    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }
        return $('<div>').text(String(value)).html();
    }

    function formatInt(value) {
        return Number(value || 0).toLocaleString('en-PH');
    }

    // ==================== REGION PICKER ====================

    $('#regionLabel').select2({
        placeholder: 'Choose a region',
        allowClear: true,
        width: '100%'
    });

    // The server already rendered the list; this refresh keeps the dropdown honest
    // if GeoGridService gains a region while the page is open.
    function loadRegions() {
        $.ajax({
            url: '{{ route("outreach.scraper.regions") }}',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (!response || !response.success || !response.data || !response.data.regions) {
                    return;
                }

                var regions = response.data.regions;
                var selected = $('#regionLabel').val();
                var options = '<option value=""></option>';

                regions.forEach(function (region) {
                    options += '<option value="' + escapeHtml(region) + '">' + escapeHtml(region) + '</option>';
                });

                $('#regionLabel').html(options);

                if (selected && regions.indexOf(selected) !== -1) {
                    $('#regionLabel').val(selected);
                }

                $('#regionLabel').trigger('change.select2');
            },
            error: function () {
                // The server-rendered list is still usable, so this is not worth a toast.
            }
        });
    }

    // Asks the regions endpoint how many cells the current region+radius tiles into,
    // so the API bill is visible before the search is committed to.
    function refreshEstimate() {
        var region = $('#regionLabel').val();
        var radius = $('#radiusKm').val();

        if (!region) {
            $('#gridEstimate').html('<span class="text-secondary">Pick a region to see how many grid cells it tiles into.</span>');
            return;
        }

        if (estimateRequest) {
            estimateRequest.abort();
        }

        $('#gridEstimate').html('<span class="text-secondary"><i class="bx bx-loader-alt bx-spin me-1"></i>Measuring the region&hellip;</span>');

        estimateRequest = $.ajax({
            url: '{{ route("outreach.scraper.regions") }}',
            type: 'GET',
            dataType: 'json',
            data: { regionLabel: region, radiusKm: radius },
            success: function (response) {
                if (!response || !response.success || !response.data || !response.data.match) {
                    $('#gridEstimate').html('<span class="text-secondary">Could not measure that region.</span>');
                    return;
                }

                var match = response.data.match;

                if (!match.known) {
                    $('#gridEstimate').html(
                        '<span class="text-dark"><i class="bx bx-error me-1"></i>No bounding box is stored for ' +
                        escapeHtml(match.input) + ', so it cannot be tiled.</span>'
                    );
                    return;
                }

                var canonical = match.canonical ? match.canonical : match.input;

                var html = '<div class="text-dark fw-medium mb-1">' + escapeHtml(canonical) + '</div>' +
                    '<div class="text-secondary">About <strong class="text-dark">' + formatInt(match.estimatedCells) +
                    '</strong> grid cells of ' + escapeHtml(match.radiusKm) + ' km, covering the whole province. ' +
                    'Each cell is one Places search; a cell that comes back full splits into four smaller ones ' +
                    'until nothing is left hiding behind the 60-result cap.</div>';

                // Past the cap the grid is cut short, which would otherwise look
                // like a completed sweep that just found fewer businesses.
                if (match.overCap) {
                    html += '<div class="mt-2 text-danger"><i class="bx bx-error-circle me-1"></i>' +
                        'That is more than the ' + formatInt(match.cellCap) + '-cell limit, so the far end of ' +
                        'this region would not be queued. Raise the grid radius under Settings before starting.</div>';
                }

                $('#gridEstimate').html(html);
            },
            error: function (xhr, status) {
                if (status === 'abort') {
                    return;
                }
                $('#gridEstimate').html('<span class="text-secondary">Could not measure that region.</span>');
            },
            complete: function () {
                estimateRequest = null;
            }
        });
    }

    // Plain-language guidance per option, straight from the controller so the
    // two cannot disagree about what a given size means.
    var RADIUS_HINTS = @json($radiusHints);

    function refreshRadiusHint() {
        var key = String(parseFloat($('#radiusKm').val()));
        $('#radiusHint').text(RADIUS_HINTS[key] || '');
    }

    $('#regionLabel').on('change', refreshEstimate);
    $('#radiusKm').on('change', function () {
        refreshRadiusHint();
        refreshEstimate();
    });
    refreshRadiusHint();

    // ==================== PROGRESS RENDERING ====================

    function enrichmentBadge(status) {
        var key = String(status || 'pending').toLowerCase();
        var badge = ENRICHMENT_BADGES[key] || { css: 'bg-light text-dark', label: key };
        return '<span class="badge ' + badge.css + '">' + escapeHtml(badge.label) + '</span>';
    }

    function renderRecentLeads(leads) {
        if (!leads || !leads.length) {
            $('#recentLeadsBody').html(
                '<tr><td colspan="4" class="text-center py-4">' +
                '<i class="bx bx-user-plus text-secondary" style="font-size: 2.5rem;"></i>' +
                '<p class="text-dark mt-2 mb-1">No leads for this batch yet.</p>' +
                '<small class="text-secondary">They appear as soon as the first grid cell is worked.</small>' +
                '</td></tr>'
            );
            return;
        }

        var html = '';

        leads.forEach(function (lead) {
            var name = escapeHtml(lead.businessName);
            var category = lead.category ? escapeHtml(lead.category) : '';
            var location = lead.location ? escapeHtml(lead.location) : 'Unknown';
            var email = lead.email
                ? '<a href="mailto:' + escapeHtml(lead.email) + '" class="text-primary">' + escapeHtml(lead.email) + '</a>'
                : '<span class="text-secondary">Not found yet</span>';

            html += '<tr>' +
                '<td><strong class="text-dark">' + name + '</strong>' +
                (category ? '<br><small class="text-secondary">' + category + '</small>' : '') +
                '</td>' +
                '<td class="text-dark">' + location + '</td>' +
                '<td class="text-dark">' + email + '</td>' +
                '<td>' + enrichmentBadge(lead.enrichmentStatus) + '</td>' +
                '</tr>';
        });

        $('#recentLeadsBody').html(html);
    }

    function renderProgress(data) {
        if (!data) {
            return;
        }

        currentBatchId = data.batchId || currentBatchId;

        var counts = data.counts || {};
        var pending = Number(counts.pending || 0);
        var processing = Number(counts.processing || 0);
        var completed = Number(counts.completed || 0);
        var split = Number(counts.split || 0);
        var failed = Number(counts.failed || 0);
        var total = Number(counts.total || 0);
        var finished = data.finished === true || (pending === 0 && processing === 0);

        var meta = escapeHtml(data.businessType || 'Search') +
            ' in ' + escapeHtml(data.regionLabel || 'an unknown region') +
            ' at ' + escapeHtml(data.radiusKm) + ' km';

        if (data.startedAt) {
            meta += ' &middot; started ' + escapeHtml(data.startedAt);
        }

        $('#progressMeta').html('<span class="text-secondary">' + meta + '</span>');
        $('#progressBatchId').removeClass('d-none').text('Batch ' + (data.batchId || ''));

        $('#countPending').text(formatInt(pending));
        $('#countProcessing').text(formatInt(processing));
        $('#countCompleted').text(formatInt(completed));
        $('#countSplit').text(formatInt(split));
        $('#countFailed').text(formatInt(failed));

        $('#statCellsTotal').text(formatInt(total));
        $('#statCellsTotalHint').text('Tiled for this batch');

        $('#statCellsDone').text(formatInt(completed + split + failed));
        $('#statCellsDoneHint').text(formatInt(split) + ' split into finer cells');

        $('#statCellsPending').text(formatInt(pending + processing));
        $('#statCellsPendingHint').text(formatInt(processing) + ' running right now');

        $('#statNewLeads').text(formatInt(data.newLeadsCount));
        $('#statNewLeadsHint').text(
            formatInt(data.resultsCount) + ' raw results seen, ' + formatInt(data.leadsTotal) + ' stored'
        );

        var percent = Math.max(0, Math.min(100, Number(data.percent || 0)));
        $('#progressBar')
            .css('width', percent + '%')
            .attr('aria-valuenow', percent);
        $('#progressPercentLabel').text(percent + '%');

        if (finished) {
            $('#progressBar').removeClass('progress-bar-animated bg-primary').addClass('bg-success');
            $('#progressState').text(total > 0 ? 'Finished' : 'Nothing queued');
        } else {
            $('#progressBar').removeClass('bg-success').addClass('progress-bar-animated bg-primary');
            $('#progressState').text('Working - ' + formatInt(pending + processing) + ' cell(s) left');
        }

        if (data.lastError) {
            $('#batchErrorText').text(data.lastError);
            $('#batchError').removeClass('d-none');
        } else {
            $('#batchError').addClass('d-none');
            $('#batchErrorText').text('');
        }

        renderRecentLeads(data.recentLeads);

        lastCounts = { pending: pending, processing: processing };
        syncButtons();

        $('#recentBatchList .outreach-batch-item').removeClass('is-selected');
        $('#recentBatchList .outreach-batch-item[data-batch-id="' + currentBatchId + '"]').addClass('is-selected');

        // The whole point of the timer: it only lives while there is work left.
        if (finished) {
            stopPolling();
        } else {
            startPolling();
        }
    }

    // ==================== POLLING ====================

    // One place decides whether the action buttons are live: a request is in flight,
    // or there is simply nothing pending left to work.
    function syncButtons() {
        var hasWork = Number(lastCounts.pending || 0) > 0;

        $('#refreshProgressBtn').prop('disabled', busy || !currentBatchId);
        $('#runBatchBtn').prop('disabled', busy || !hasWork);
        $('#cancelBatchBtn').prop('disabled', busy || !hasWork);
    }

    function stopPolling() {
        if (pollTimer) {
            clearInterval(pollTimer);
            pollTimer = null;
        }
    }

    function startPolling() {
        if (pollTimer) {
            return;
        }
        pollTimer = setInterval(function () {
            fetchProgress(false);
        }, POLL_INTERVAL_MS);
    }

    function fetchProgress(announce) {
        if (!currentBatchId) {
            return;
        }

        $.ajax({
            url: '{{ route("outreach.scraper.progress") }}',
            type: 'GET',
            dataType: 'json',
            data: { batchId: currentBatchId },
            success: function (response) {
                if (!response || !response.success || !response.data) {
                    stopPolling();
                    toastr.error((response && response.message) || 'Could not load progress.', 'Error!');
                    return;
                }

                renderProgress(response.data);

                if (announce) {
                    toastr.success('Progress updated.', 'Refreshed');
                }
            },
            error: function (xhr) {
                // A dead batch would otherwise be polled every five seconds forever.
                stopPolling();
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Could not load progress.';
                toastr.error(message, 'Error!');
            }
        });
    }

    // ==================== ACTIONS ====================

    $('#startSearchBtn').on('click', function () {
        var $btn = $(this);
        var businessType = $.trim($('#businessType').val());
        var regionLabel = $('#regionLabel').val();
        var radiusKm = $('#radiusKm').val();
        var maxLeads = $('#maxLeads').val();

        if (!businessType) {
            toastr.warning('Tell us what kind of business to look for.', 'Missing business type');
            $('#businessType').trigger('focus');
            return;
        }

        if (!regionLabel) {
            toastr.warning('Choose a region to search.', 'Missing region');
            return;
        }

        busy = true;
        // Any timer still watching the previous batch is retired before the new
        // batchId is adopted.
        stopPolling();
        syncButtons();
        $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin me-1"></i>Tiling the region...');

        $.ajax({
            url: '{{ route("outreach.scraper.start") }}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                businessType: businessType,
                regionLabel: regionLabel,
                radiusKm: radiusKm,
                maxLeads: maxLeads
            },
            success: function (response) {
                if (!response || !response.success || !response.data) {
                    toastr.error((response && response.message) || 'Could not start the search.', 'Error!');
                    return;
                }

                currentBatchId = response.data.batchId;
                toastr.success(response.message, 'Search queued');
                prependBatch(response.data);
                renderProgress(response.data.progress);
            },
            error: function (xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Could not start the search.';
                toastr.error(message, 'Error!');
            },
            complete: function () {
                busy = false;
                $btn.prop('disabled', {{ $hasPlacesKey ? 'false' : 'true' }})
                    .html('<i class="bx bx-play-circle me-1"></i>Start Search');
                syncButtons();
            }
        });
    });

    // Put the fresh batch at the top of the recent list without a page reload.
    function prependBatch(data) {
        var progress = data.progress || {};
        var counts = progress.counts || {};
        var pending = Number(counts.pending || 0);

        var html = '<div class="outreach-batch-item is-selected" data-batch-id="' + escapeHtml(data.batchId) + '">' +
            '<div class="d-flex justify-content-between align-items-start gap-2">' +
            '<div class="flex-grow-1">' +
            '<h6 class="mb-1 text-dark">' + escapeHtml(data.businessType) + '</h6>' +
            '<small class="text-secondary d-block">' + escapeHtml(data.regionLabel) + ' &middot; ' +
            formatInt(counts.total) + ' cells &middot; 0 leads</small>' +
            '<small class="text-secondary">just now</small>' +
            '</div>' +
            (pending > 0
                ? '<span class="badge bg-warning text-dark">' + formatInt(pending) + ' pending</span>'
                : '<span class="badge bg-success text-white">Done</span>') +
            '</div></div>';

        var $list = $('#recentBatchList');
        $list.find('.outreach-batch-item').removeClass('is-selected');

        if ($list.find('.outreach-batch-item').length === 0) {
            $list.html(html);
        } else {
            $list.prepend(html);
        }
    }

    $('#runBatchBtn').on('click', function () {
        if (!currentBatchId || busy) {
            return;
        }

        var $btn = $(this);
        var rendered = false;

        busy = true;
        // The request works the cells itself (QUEUE_CONNECTION is sync), so it can
        // legitimately take tens of seconds. The timer would otherwise fire on top of it.
        stopPolling();
        syncButtons();
        $btn.html('<i class="bx bx-loader-alt bx-spin me-1"></i>Working cells...');

        $.ajax({
            url: '{{ route("outreach.scraper.run") }}',
            type: 'POST',
            dataType: 'json',
            data: {
                _token: '{{ csrf_token() }}',
                batchId: currentBatchId
            },
            success: function (response) {
                if (!response || !response.success || !response.data) {
                    toastr.error((response && response.message) || 'Could not run the batch.', 'Error!');
                    return;
                }

                toastr.success(response.message, 'Batch run');
                renderProgress(response.data);
                rendered = true;
            },
            error: function (xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Could not run the batch.';
                toastr.error(message, 'Error!');
            },
            complete: function () {
                busy = false;
                $btn.html('<i class="bx bx-fast-forward me-1"></i>Process next batch now');

                if (rendered) {
                    syncButtons();
                } else {
                    // Nothing was rendered, so the polling timer is still stopped -
                    // one fetch puts the panel and the timer back in a known state.
                    fetchProgress(false);
                }
            }
        });
    });

    $('#refreshProgressBtn').on('click', function () {
        fetchProgress(true);
    });

    $('#cancelBatchBtn').on('click', function () {
        if (!currentBatchId || busy) {
            return;
        }

        Swal.fire({
            title: 'Cancel the remaining cells?',
            text: 'Cells already running will finish, and every lead found so far is kept.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#f46a6a',
            cancelButtonColor: '#74788d',
            confirmButtonText: 'Yes, cancel them',
            cancelButtonText: 'Keep searching'
        }).then(function (result) {
            if (!result.isConfirmed) {
                return;
            }

            var rendered = false;

            busy = true;
            stopPolling();
            syncButtons();

            $.ajax({
                url: '{{ route("outreach.scraper.cancel") }}',
                type: 'POST',
                dataType: 'json',
                data: {
                    _token: '{{ csrf_token() }}',
                    batchId: currentBatchId
                },
                success: function (response) {
                    if (!response || !response.success) {
                        toastr.error((response && response.message) || 'Could not cancel the batch.', 'Error!');
                        return;
                    }

                    toastr.success(response.message, 'Cancelled');

                    if (response.data) {
                        renderProgress(response.data);
                        rendered = true;
                    }
                },
                error: function (xhr) {
                    var message = (xhr.responseJSON && xhr.responseJSON.message)
                        ? xhr.responseJSON.message
                        : 'Could not cancel the batch.';
                    toastr.error(message, 'Error!');
                },
                complete: function () {
                    busy = false;

                    if (rendered) {
                        syncButtons();
                    } else {
                        fetchProgress(false);
                    }
                }
            });
        });
    });

    // Clicking a past search switches the progress panel to that batch.
    $('#recentBatchList').on('click', '.outreach-batch-item', function () {
        var batchId = $(this).data('batch-id');

        if (!batchId || batchId === currentBatchId) {
            return;
        }

        stopPolling();
        currentBatchId = String(batchId);
        fetchProgress(true);
    });

    // Leaving the page must take the timer with it.
    $(window).on('beforeunload', function () {
        stopPolling();
    });

    // ==================== BOOT ====================

    loadRegions();

    @if(!empty($recentBatches))
        currentBatchId = '{{ $recentBatches[0]['batchId'] }}';
        fetchProgress(false);
    @endif
});
</script>
@endsection
