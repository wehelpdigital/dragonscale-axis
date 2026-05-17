@extends('layouts.master')

@section('title') Reports — {{ $schedule->title }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .kpi-card { padding: 16px; border-radius: 8px; background:#fafbfd; border:1px solid #e6e8ec; }
    .kpi-label { color: #74788d; font-size: 12px; text-transform: uppercase; letter-spacing: .5px; }
    .kpi-value { color: #212529; font-size: 22px; font-weight: 600; margin-top: 4px; }
    .kpi-sub { color: #74788d; font-size: 11px; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('anisenso-schedule-manager.index') }}" class="text-decoration-none">Schedule Manager</a> @endslot
        @slot('title') Reports — {{ $schedule->title }} @endslot
    @endcomponent

    @if(!$generation)
        <div class="alert alert-info">
            <i class="bx bx-info-circle me-2"></i> No generated calendar yet. <a href="{{ route('anisenso-schedule-manager.generate.form', ['scheduleId' => $schedule->id]) }}" class="alert-link">Generate one first &rarr;</a>
        </div>
    @else
        <div class="d-flex justify-content-between flex-wrap gap-2 mb-3">
            <small class="text-secondary">
                Generation #{{ $generation->generationNumber }} • Season starts {{ \Illuminate\Support\Carbon::parse($generation->seasonStartDate)->format('M j, Y') }}
            </small>
            <div>
                <a href="{{ route('anisenso-schedule-manager.calendar', ['scheduleId' => $schedule->id]) }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-calendar me-1"></i> Open Calendar</a>
            </div>
        </div>

        <div id="reportsLoading" class="text-center py-4 text-secondary"><i class="bx bx-loader-alt bx-spin" style="font-size:2rem;"></i><p>Computing...</p></div>
        <div id="reportsContent" style="display:none;">
            <div class="row" id="kpiRow"></div>

            <div class="card mt-2">
                <div class="card-body">
                    <h5 class="text-dark mb-1">Labor Cost by Worker</h5>
                    <small class="text-secondary d-block mb-3">Half-day units = whole-day events count as 2 units. Cost = units × per-half-day rate.</small>
                    <div class="table-responsive">
                        <table class="table align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>Priority</th>
                                    <th>Worker</th>
                                    <th>Rate / Half Day</th>
                                    <th>Events Planned</th>
                                    <th>Events Completed</th>
                                    <th>Half-Day Units</th>
                                    <th>Planned Cost</th>
                                    <th>Actual Cost</th>
                                </tr>
                            </thead>
                            <tbody id="laborTbody"></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="text-dark mb-1">Materials Usage</h5>
                            <small class="text-secondary d-block mb-3">Planned vs actually used (completed events).</small>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Material</th>
                                            <th>Type</th>
                                            <th>Planned Qty</th>
                                            <th>Used Qty</th>
                                            <th>Used Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody id="materialsTbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card h-100">
                        <div class="card-body">
                            <h5 class="text-dark mb-1">Services Usage</h5>
                            <small class="text-secondary d-block mb-3">External services scheduled vs completed.</small>
                            <div class="table-responsive">
                                <table class="table align-middle mb-0">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Service</th>
                                            <th>Unit Cost</th>
                                            <th>Planned Uses</th>
                                            <th>Used</th>
                                            <th>Used Cost</th>
                                        </tr>
                                    </thead>
                                    <tbody id="servicesTbody"></tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mt-2">
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-dark mb-1">Workers Used</h5>
                            <small class="text-secondary d-block mb-3">Workers with at least one assigned event.</small>
                            <ul class="list-group list-group-flush" id="usedWorkersList"></ul>
                        </div>
                    </div>
                </div>
                <div class="col-lg-6">
                    <div class="card">
                        <div class="card-body">
                            <h5 class="text-dark mb-1">Workers Not Used</h5>
                            <small class="text-secondary d-block mb-3">Workers defined but not assigned to any event — review priority or remove if not needed.</small>
                            <ul class="list-group list-group-flush" id="unusedWorkersList"></ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection

@if($generation)
@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
const SCHEDULE_ID = {{ $schedule->id }};
const ROOT = '{{ url('/') }}';
const REPORT_URL = `${ROOT}/anisenso-schedule-manager-reports-data?scheduleId=${SCHEDULE_ID}`;

function peso(n) { return '₱ ' + Number(n || 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }
function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }

$.get(REPORT_URL, function (res) {
    $('#reportsLoading').hide();
    if (!res.success || !res.data) { toastr.error(res.message || 'Failed to load.'); return; }
    const d = res.data;

    // KPIs
    const kpis = [
        { label: 'Total Events', value: d.summary.totalEvents, sub: `${d.summary.completedEvents} completed, ${d.summary.pendingEvents} pending` },
        { label: 'Labor Cost (Planned)', value: peso(d.summary.laborCostPlanned), sub: 'sum of all assigned worker × time' },
        { label: 'Labor Cost (Actual)', value: peso(d.summary.laborCostCompleted), sub: 'only completed events' },
        { label: 'Materials Cost (Used)', value: peso(d.summary.materialsCostUsed), sub: `planned: ${peso(d.summary.materialsCostPlanned)}` },
        { label: 'Services Cost (Used)', value: peso(d.summary.servicesCostUsed), sub: `planned: ${peso(d.summary.servicesCostPlanned)}` },
        { label: 'Extra Cost Logged', value: peso(d.summary.extraCostLogged), sub: 'unforeseen expenses' },
        { label: 'Grand Total Planned', value: peso(d.summary.grandTotalPlanned), sub: 'labor + materials + services' },
        { label: 'Grand Total Actual', value: peso(d.summary.grandTotalActual), sub: 'completed + extras' },
    ];
    let kpiHtml = '';
    kpis.forEach(k => {
        kpiHtml += `<div class="col-md-3 col-sm-6 mb-3"><div class="kpi-card">
            <div class="kpi-label">${escapeHtml(k.label)}</div>
            <div class="kpi-value">${k.value}</div>
            <div class="kpi-sub">${escapeHtml(k.sub)}</div>
        </div></div>`;
    });
    $('#kpiRow').html(kpiHtml);

    // Labor table
    let lab = '';
    d.laborByWorker.forEach(w => {
        lab += `<tr>
            <td><span class="badge bg-primary text-white">#${w.priority}</span></td>
            <td class="text-dark">${escapeHtml(w.workerName)}</td>
            <td class="text-dark">${peso(w.costPerHalfDay)}</td>
            <td class="text-dark">${w.eventsAssignedAll}</td>
            <td class="text-dark">${w.eventsAssignedDone}</td>
            <td class="text-dark">${w.halfDayUnits}</td>
            <td class="text-dark">${peso(w.totalCost)}</td>
            <td class="text-dark">${peso(w.totalCostCompleted)}</td>
        </tr>`;
    });
    $('#laborTbody').html(lab || '<tr><td colspan="8" class="text-center text-secondary">No labor data.</td></tr>');

    // Materials
    let mats = '';
    d.materialUsage.forEach(m => {
        mats += `<tr>
            <td class="text-dark"><strong>${escapeHtml(m.materialName)}</strong></td>
            <td><span class="badge bg-info text-white">${escapeHtml(m.materialType)}</span></td>
            <td class="text-dark">${Number(m.plannedQty || 0).toFixed(2)} ${escapeHtml(m.unitOfMeasure)}</td>
            <td class="text-dark">${Number(m.usedQty || 0).toFixed(2)} ${escapeHtml(m.unitOfMeasure)}</td>
            <td class="text-dark">${peso(m.usedCost)}</td>
        </tr>`;
    });
    $('#materialsTbody').html(mats || '<tr><td colspan="5" class="text-center text-secondary">No material usage recorded.</td></tr>');

    // Services
    let svc = '';
    d.serviceUsage.forEach(s => {
        svc += `<tr>
            <td class="text-dark"><strong>${escapeHtml(s.serviceName)}</strong></td>
            <td class="text-dark">${peso(s.serviceCost)}</td>
            <td class="text-dark">${s.plannedTimes}</td>
            <td class="text-dark">${s.usedTimes}</td>
            <td class="text-dark">${peso(s.usedCost)}</td>
        </tr>`;
    });
    $('#servicesTbody').html(svc || '<tr><td colspan="5" class="text-center text-secondary">No service usage recorded.</td></tr>');

    // Used / unused workers
    let used = '';
    d.usedWorkers.forEach(w => { used += `<li class="list-group-item d-flex justify-content-between"><span class="text-dark">${escapeHtml(w.workerName)} <small class="text-secondary">(priority #${w.priority})</small></span><span class="badge bg-success text-white">${w.eventsAssignedAll} events</span></li>`; });
    $('#usedWorkersList').html(used || '<li class="list-group-item text-secondary text-center">None.</li>');

    let unused = '';
    d.unusedWorkers.forEach(w => { unused += `<li class="list-group-item d-flex justify-content-between"><span class="text-dark">${escapeHtml(w.workerName)} <small class="text-secondary">(priority #${w.priority})</small></span><span class="badge bg-secondary text-white">unused</span></li>`; });
    $('#unusedWorkersList').html(unused || '<li class="list-group-item text-secondary text-center">All workers are in use. 🎉</li>');

    $('#reportsContent').show();
}).fail(() => { $('#reportsLoading').hide(); toastr.error('Failed to load report.'); });
</script>
@endsection
@endif
