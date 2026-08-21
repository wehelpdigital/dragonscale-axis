@extends('layouts.master')

@section('title') Lead Finder Dashboard @endsection

@section('css')
<!-- Toastr -->
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />

<style>
.outreach-chart-wrap {
    position: relative;
    min-height: 200px;
}
.outreach-loader {
    position: absolute;
    top: 0;
    left: 0;
    right: 0;
    bottom: 0;
    background: rgba(255, 255, 255, 0.9);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 5;
    border-radius: 0.25rem;
}
.outreach-window-strip {
    border-left: 4px solid #74788d;
}
.outreach-window-strip.is-open { border-left-color: #34c38f; }
.outreach-window-strip.is-blocked { border-left-color: #f1b44c; }
.outreach-funnel-row + .outreach-funnel-row {
    margin-top: 0.85rem;
}
.outreach-funnel-bar {
    height: 10px;
    background: #eff2f7;
    border-radius: 5px;
    overflow: hidden;
}
.outreach-funnel-fill {
    height: 100%;
    border-radius: 5px;
    transition: width 0.4s ease;
}
/* ApexCharts positions its canvas absolutely; keep it clipped inside the card. */
.card {
    position: relative;
    z-index: 1;
}
</style>
@endsection

@section('content')
@component('components.breadcrumb')
@slot('li_1') Lead Finder @endslot
@slot('title') Dashboard @endslot
@endcomponent

<div class="d-flex flex-wrap align-items-center justify-content-between gap-2 mb-3">
    <div class="d-flex flex-wrap align-items-center gap-2">
        {!! $settings->outreach_status_badge !!}
        <span class="badge bg-light text-dark">Window {{ $settings->send_window_label }}</span>
        <span class="badge bg-light text-dark">{{ $settings->send_days_label }}</span>
    </div>
    <div class="d-flex gap-2">
        <a href="{{ route('outreach.scraper') }}" class="btn btn-primary btn-sm">
            <i class="bx bx-map-alt me-1"></i>Find Leads
        </a>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="refreshDashboard">
            <i class="bx bx-refresh me-1"></i>Refresh
        </button>
    </div>
</div>

@if(!$isConfigured)
<div class="alert alert-warning alert-dismissible fade show" role="alert">
    <i class="bx bx-error me-2"></i>
    <span class="text-dark">Lead Finder has not been configured yet. Add your Google Places key, SMTP and IMAP details before the cron can do anything.</span>
    <a href="{{ route('outreach.settings') }}" class="alert-link ms-1">Open Settings</a>
    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
</div>
@endif

<!-- Sending window strip - carries evaluateWindow()'s reason verbatim -->
<div class="card outreach-window-strip mb-4" id="windowStrip">
    <div class="card-body py-3">
        <div class="row align-items-center g-3">
            <div class="col-lg-7">
                <div class="d-flex align-items-center flex-wrap gap-2">
                    <span class="badge bg-secondary text-white px-3 py-2" id="windowBadge">
                        <i class="bx bx-loader-alt bx-spin me-1"></i>Checking
                    </span>
                    <span class="text-dark fw-medium" id="windowReason">Reading the sending window&hellip;</span>
                </div>
            </div>
            <div class="col-lg-5">
                <div class="d-flex flex-wrap justify-content-lg-end gap-2">
                    <span class="badge bg-info text-white px-3 py-2" id="windowSentToday">Sent today 0 / 0</span>
                    <span class="badge bg-light text-dark px-3 py-2" id="windowPendingGrids">0 grid cells pending</span>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Headline numbers -->
<div class="row" id="outreachStatCards">
    @include('outreach::partials._stat-card', [
        'id' => 'statTotalLeads',
        'label' => 'Total Leads Scraped',
        'icon' => 'bx-map-pin',
        'accent' => 'primary',
        'value' => '0',
        'hint' => 'Businesses found so far',
        'hintId' => 'statTotalLeadsHint',
    ])
    @include('outreach::partials._stat-card', [
        'id' => 'statEnrichedEmails',
        'label' => 'Enriched Emails Found',
        'icon' => 'bx-envelope',
        'accent' => 'info',
        'value' => '0',
        'hint' => '0% of scraped leads',
        'hintId' => 'statEnrichedHint',
    ])
    @include('outreach::partials._stat-card', [
        'id' => 'statTotalSent',
        'label' => 'Total Sent',
        'icon' => 'bx-send',
        'accent' => 'success',
        'value' => '0',
        'hint' => '0 sent today',
        'hintId' => 'statSentHint',
    ])
    @include('outreach::partials._stat-card', [
        'id' => 'statReplyRate',
        'label' => 'Reply Rate',
        'icon' => 'bx-message-rounded-dots',
        'accent' => 'warning',
        'value' => '0%',
        'hint' => '0 leads replied',
        'hintId' => 'statReplyHint',
    ])
    @include('outreach::partials._stat-card', [
        'id' => 'statBounceRate',
        'label' => 'Bounce Rate',
        'icon' => 'bx-error-circle',
        'accent' => 'danger',
        'value' => '0%',
        'hint' => 'Keep this under 3%',
        'hintId' => 'statBounceHint',
    ])
</div>

<div class="row">
    <!-- Daily sent vs replies -->
    <div class="col-xl-8">
        <div class="card">
            <div class="card-body">
                <div class="d-flex align-items-center justify-content-between mb-3">
                    <h4 class="card-title text-dark mb-0">
                        <i class="bx bx-line-chart me-1"></i>Daily Sent vs Replies
                    </h4>
                    <small class="text-secondary">Last {{ $trendDays }} days</small>
                </div>
                <div class="outreach-chart-wrap">
                    <div class="outreach-loader d-none" id="dailyChartLoader">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="outreachDailyChart" style="height: 340px;"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pipeline -->
    <div class="col-xl-4">
        <div class="card">
            <div class="card-body">
                <h4 class="card-title text-dark mb-3">
                    <i class="bx bx-filter me-1"></i>Pipeline
                </h4>
                <div class="outreach-chart-wrap">
                    <div class="outreach-loader d-none" id="pipelineChartLoader">
                        <div class="spinner-border text-primary" role="status">
                            <span class="visually-hidden">Loading...</span>
                        </div>
                    </div>
                    <div id="outreachPipelineChart" style="height: 260px;"></div>
                </div>
                <div class="mt-3" id="outreachFunnel">
                    <div class="text-center py-3">
                        <i class="bx bx-filter text-secondary" style="font-size: 2rem;"></i>
                        <p class="text-dark mt-2 mb-1">No pipeline data yet.</p>
                        <small class="text-secondary">Run a region search to start filling it.</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@section('script')
<!-- Toastr -->
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<!-- ApexCharts -->
<script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>

<script>
$(document).ready(function () {

    toastr.options = {
        closeButton: true,
        progressBar: true,
        positionClass: "toast-top-right",
        timeOut: 3000
    };

    var dailyChart = null;
    var pipelineChart = null;
    var loading = false;

    var MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

    var PIPELINE_STEPS = [
        { key: 'scraped', label: 'Scraped', color: '#556ee6' },
        { key: 'enriched', label: 'Enriched', color: '#50a5f1' },
        { key: 'contacted', label: 'Contacted', color: '#f1b44c' },
        { key: 'replied', label: 'Replied', color: '#34c38f' }
    ];

    // Anything that came out of the database is escaped before it re-enters the DOM.
    function escapeHtml(value) {
        if (value === null || value === undefined) {
            return '';
        }
        return $('<div>').text(String(value)).html();
    }

    function formatInt(value) {
        return Number(value || 0).toLocaleString('en-PH');
    }

    // Labels arrive as 'YYYY-MM-DD'; the axis only has room for 'Aug 21'.
    function shortDate(value) {
        var parts = String(value).split('-');
        if (parts.length !== 3) {
            return value;
        }
        var monthIndex = parseInt(parts[1], 10) - 1;
        return (MONTHS[monthIndex] || parts[1]) + ' ' + parseInt(parts[2], 10);
    }

    // ==================== SENDING WINDOW ====================

    function renderWindow(stats, windowState) {
        var $strip = $('#windowStrip');
        var allowed = windowState && windowState.allowed === true;

        // OutreachDecisionService writes this string and it is shown untouched - it
        // is the only place the admin learns WHY nothing is going out right now.
        var reason = (windowState && windowState.reason)
            ? String(windowState.reason)
            : 'No sending status was reported.';

        $strip.removeClass('is-open is-blocked').addClass(allowed ? 'is-open' : 'is-blocked');

        if (allowed) {
            $('#windowBadge')
                .removeClass('bg-secondary bg-warning text-dark')
                .addClass('bg-success text-white')
                .html('<i class="bx bx-check-circle me-1"></i>Sending live');
        } else {
            $('#windowBadge')
                .removeClass('bg-secondary bg-success text-white')
                .addClass('bg-warning text-dark')
                .html('<i class="bx bx-pause-circle me-1"></i>Sending paused');
        }

        $('#windowReason').text(reason);
        $('#windowSentToday').text('Sent today ' + formatInt(stats.sentToday) + ' / ' + formatInt(stats.dailyCap));

        var pending = Number(stats.pendingGrids || 0);
        $('#windowPendingGrids')
            .removeClass('bg-warning bg-light')
            .addClass(pending > 0 ? 'bg-warning' : 'bg-light')
            .text(formatInt(pending) + (pending === 1 ? ' grid cell pending' : ' grid cells pending'));
    }

    // ==================== STAT CARDS ====================

    function renderStats(stats) {
        $('#statTotalLeads').text(formatInt(stats.totalLeads));
        $('#statTotalLeadsHint').text(
            Number(stats.totalLeads) > 0 ? 'Businesses found so far' : 'Nothing scraped yet'
        );

        $('#statEnrichedEmails').text(formatInt(stats.enrichedEmails));
        var enrichPercent = Number(stats.totalLeads) > 0
            ? Math.round((stats.enrichedEmails / stats.totalLeads) * 1000) / 10
            : 0;
        $('#statEnrichedHint').text(enrichPercent + '% of scraped leads');

        $('#statTotalSent').text(formatInt(stats.totalSent));
        $('#statSentHint').text(
            formatInt(stats.sentToday) + ' sent today of a ' + formatInt(stats.dailyCap) + ' cap'
        );

        $('#statReplyRate').text(Number(stats.replyRate || 0) + '%');
        $('#statReplyHint').text(
            formatInt(stats.repliesReceived) +
            (Number(stats.repliesReceived) === 1 ? ' lead replied' : ' leads replied')
        );

        var bounceRate = Number(stats.bounceRate || 0);
        $('#statBounceRate').text(bounceRate + '%');
        $('#statBounceHint').text(
            bounceRate > 3
                ? 'Above 3% - pause and check the list quality'
                : 'Keep this under 3%'
        );
    }

    // ==================== DAILY TREND ====================

    function renderDailyChart(daily) {
        var container = document.querySelector('#outreachDailyChart');
        if (!container) {
            return;
        }

        var labels = (daily && daily.labels) ? daily.labels : [];
        var sent = (daily && daily.sent) ? daily.sent : [];
        var replies = (daily && daily.replies) ? daily.replies : [];

        if (!labels.length) {
            if (dailyChart) {
                dailyChart.destroy();
                dailyChart = null;
            }
            $('#outreachDailyChart').html(
                '<div class="d-flex align-items-center justify-content-center h-100">' +
                '<div class="text-center">' +
                '<i class="bx bx-line-chart text-secondary" style="font-size: 2.5rem;"></i>' +
                '<p class="text-dark mt-2 mb-1">No activity to chart.</p>' +
                '<small class="text-secondary">Sends and replies land here once the cron starts working.</small>' +
                '</div></div>'
            );
            return;
        }

        var hasActivity = sent.concat(replies).some(function (n) { return Number(n) > 0; });

        var options = {
            series: [
                { name: 'Sent', data: sent.map(Number) },
                { name: 'Replies', data: replies.map(Number) }
            ],
            chart: {
                type: 'line',
                height: 340,
                toolbar: { show: false },
                zoom: { enabled: false }
            },
            colors: ['#556ee6', '#34c38f'],
            stroke: { curve: 'smooth', width: [3, 3] },
            markers: { size: 0, hover: { size: 5 } },
            dataLabels: { enabled: false },
            grid: { borderColor: '#f1f1f1', strokeDashArray: 3 },
            xaxis: {
                categories: labels,
                tickAmount: 10,
                labels: {
                    rotate: -45,
                    rotateAlways: false,
                    hideOverlappingLabels: true,
                    style: { colors: '#495057', fontSize: '11px' },
                    formatter: function (value) { return shortDate(value); }
                },
                axisBorder: { color: '#e9ecef' },
                axisTicks: { color: '#e9ecef' }
            },
            yaxis: {
                min: 0,
                forceNiceScale: true,
                labels: {
                    style: { colors: '#495057' },
                    formatter: function (value) { return Math.round(value); }
                }
            },
            legend: {
                position: 'top',
                horizontalAlign: 'right',
                labels: { colors: '#495057' }
            },
            tooltip: {
                shared: true,
                intersect: false,
                y: {
                    formatter: function (value) { return Math.round(value || 0); }
                }
            }
        };

        // A flat all-zero line still gets drawn on purpose: it says "the cron is quiet",
        // which an empty-state panel would wrongly read as "nothing is configured".
        if (!hasActivity) {
            options.yaxis.max = 5;
        }

        if (dailyChart) {
            dailyChart.destroy();
            dailyChart = null;
        }
        $('#outreachDailyChart').empty();
        dailyChart = new ApexCharts(container, options);
        dailyChart.render();
    }

    // ==================== PIPELINE ====================

    function renderPipeline(pipeline) {
        var container = document.querySelector('#outreachPipelineChart');
        if (!container) {
            return;
        }

        var data = pipeline || {};
        var values = PIPELINE_STEPS.map(function (step) { return Number(data[step.key] || 0); });
        var labels = PIPELINE_STEPS.map(function (step) { return step.label; });
        var colors = PIPELINE_STEPS.map(function (step) { return step.color; });

        var options = {
            series: [{ name: 'Leads', data: values }],
            chart: {
                type: 'bar',
                height: 260,
                toolbar: { show: false }
            },
            plotOptions: {
                bar: {
                    horizontal: true,
                    borderRadius: 4,
                    distributed: true,
                    barHeight: '62%',
                    dataLabels: { position: 'top' }
                }
            },
            colors: colors,
            dataLabels: {
                enabled: true,
                textAnchor: 'start',
                offsetX: 4,
                style: { colors: ['#495057'], fontSize: '12px', fontWeight: 600 },
                formatter: function (value) { return formatInt(value); }
            },
            legend: { show: false },
            grid: { borderColor: '#f1f1f1', strokeDashArray: 3 },
            xaxis: {
                categories: labels,
                labels: {
                    style: { colors: '#495057' },
                    formatter: function (value) { return Math.round(value); }
                }
            },
            yaxis: {
                labels: { style: { colors: '#495057', fontSize: '12px' } }
            },
            tooltip: {
                y: {
                    formatter: function (value) { return formatInt(value) + ' leads'; }
                }
            }
        };

        if (pipelineChart) {
            pipelineChart.destroy();
            pipelineChart = null;
        }
        $('#outreachPipelineChart').empty();
        pipelineChart = new ApexCharts(container, options);
        pipelineChart.render();

        renderFunnel(values);
    }

    // Every step is measured against the scraped total, so the rows read as one
    // shrinking funnel rather than four unrelated ratios.
    function renderFunnel(values) {
        var base = Number(values[0] || 0);

        if (base <= 0) {
            $('#outreachFunnel').html(
                '<div class="text-center py-3">' +
                '<i class="bx bx-filter text-secondary" style="font-size: 2rem;"></i>' +
                '<p class="text-dark mt-2 mb-1">No pipeline data yet.</p>' +
                '<small class="text-secondary">Run a region search to start filling it.</small>' +
                '</div>'
            );
            return;
        }

        var html = '';

        PIPELINE_STEPS.forEach(function (step, index) {
            var value = Number(values[index] || 0);
            var percent = Math.round((value / base) * 1000) / 10;
            var width = Math.max(0, Math.min(100, percent));

            html += '<div class="outreach-funnel-row">' +
                '<div class="d-flex justify-content-between align-items-baseline mb-1">' +
                '<span class="text-dark fw-medium">' + escapeHtml(step.label) + '</span>' +
                '<span class="text-secondary small">' + formatInt(value) + ' &middot; ' + percent + '%</span>' +
                '</div>' +
                '<div class="outreach-funnel-bar">' +
                '<div class="outreach-funnel-fill" style="width: ' + width + '%; background-color: ' + step.color + ';"></div>' +
                '</div>' +
                '</div>';
        });

        $('#outreachFunnel').html(html);
    }

    // ==================== LOADING ====================

    function setLoading(state) {
        loading = state;
        $('#dailyChartLoader').toggleClass('d-none', !state);
        $('#pipelineChartLoader').toggleClass('d-none', !state);
        $('#refreshDashboard').prop('disabled', state);
    }

    function loadDashboard(announce) {
        if (loading) {
            return;
        }

        setLoading(true);

        $.ajax({
            url: '{{ route("outreach.dashboard.data") }}',
            type: 'GET',
            dataType: 'json',
            success: function (response) {
                if (!response || !response.success || !response.data) {
                    toastr.error((response && response.message) || 'Could not load the dashboard.', 'Error!');
                    return;
                }

                var data = response.data;
                renderWindow(data.stats || {}, data.window || {});
                renderStats(data.stats || {});
                renderDailyChart(data.daily || {});
                renderPipeline(data.pipeline || {});

                if (announce) {
                    toastr.success('Dashboard refreshed.', 'Updated');
                }
            },
            error: function (xhr) {
                var message = (xhr.responseJSON && xhr.responseJSON.message)
                    ? xhr.responseJSON.message
                    : 'Could not load the dashboard.';
                toastr.error(message, 'Error!');
            },
            complete: function () {
                setLoading(false);
            }
        });
    }

    $('#refreshDashboard').on('click', function () {
        loadDashboard(true);
    });

    loadDashboard(false);
});
</script>
@endsection
