@extends('layouts.master')

@section('title') Calendar — {{ $schedule->title }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .event-row.completed { background: #f3fbf6 !important; }
    .event-row.completed .event-title { text-decoration: line-through; opacity: .7; }
    .event-row.blocked { opacity: .55; }
    .event-pill { font-size: 11px; padding: 3px 9px; border-radius: 12px; display:inline-block; }
    .pill-activity { background:#e9efff; color:#3a4699; }
    .pill-irrigation { background:#e6f7f1; color:#0f8a5f; }
    .priority-critical { background:#9c1c1c; color:#fff; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
    .priority-high { background:#fde4e4; color:#a82929; }
    .priority-medium { background:#fff3df; color:#8a6300; }
    .priority-low { background:#e8eaee; color:#495057; }
    .day-header { background:#fafbfd; padding:8px 12px; border-left:3px solid #556ee6; border-radius:4px; margin: 14px 0 6px; }
    .filter-bar { background:#fafbfd; padding:12px; border-radius:8px; margin-bottom:14px; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('anisenso-schedule-manager.index') }}" class="text-decoration-none">Schedule Manager</a> @endslot
        @slot('title') Calendar — {{ $schedule->title }} @endslot
    @endcomponent

    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
                <div>
                    <h5 class="text-dark mb-1">Generated Calendar</h5>
                    <small class="text-secondary">
                        Generation #{{ $generation->generationNumber }} &middot;
                        Season starts {{ \Illuminate\Support\Carbon::parse($generation->seasonStartDate)->format('M j, Y') }} &middot;
                        Generated {{ $generation->generatedAt ? \Illuminate\Support\Carbon::parse($generation->generatedAt)->format('M j, Y g:i A') : '—' }}
                    </small>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ route('anisenso-schedule-manager.generate.form', ['scheduleId' => $schedule->id]) }}" class="btn btn-outline-primary btn-sm"><i class="bx bx-refresh me-1"></i> Regenerate</a>
                    <a href="{{ route('anisenso-schedule-manager.reports', ['scheduleId' => $schedule->id]) }}" class="btn btn-outline-warning btn-sm"><i class="bx bx-bar-chart-alt-2 me-1"></i> Reports</a>
                    <a href="{{ route('anisenso-schedule-manager.setup', ['id' => $schedule->id]) }}" class="btn btn-outline-secondary btn-sm"><i class="bx bx-cog me-1"></i> Back to Setup</a>
                </div>
            </div>

            <div class="filter-bar">
                <div class="row g-2 align-items-end">
                    <div class="col-md-3">
                        <label class="form-label text-dark small">Status</label>
                        <select class="form-select form-select-sm" id="filterStatus">
                            <option value="">All</option>
                            <option value="pending">Pending</option>
                            <option value="completed">Completed</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark small">Lot</label>
                        <select class="form-select form-select-sm" id="filterLot">
                            <option value="">All lots</option>
                            @foreach($schedule->lots as $lot)
                                <option value="{{ $lot->id }}">{{ $lot->lotName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label text-dark small">Event Type</label>
                        <select class="form-select form-select-sm" id="filterType">
                            <option value="">All</option>
                            <option value="activity">Activities</option>
                            <option value="irrigation">Irrigation</option>
                        </select>
                    </div>
                    <div class="col-md-3 text-end">
                        <span class="text-secondary small" id="eventCounter"></span>
                    </div>
                </div>
            </div>

            <div id="calendarLoading" class="text-center py-4 text-secondary"><i class="bx bx-loader-alt bx-spin" style="font-size:2rem;"></i><p>Loading events...</p></div>
            <div id="calendarContent" style="display:none;"></div>
            <div id="calendarEmpty" class="text-center py-4 text-secondary" style="display:none;"><i class="bx bx-calendar-x" style="font-size:2.5rem;"></i><p>No events match the current filters.</p></div>
        </div>
    </div>

    {{-- Edit event modal --}}
    <div class="modal fade" id="editEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark"><i class="bx bx-edit-alt me-2"></i>Edit Calendar Event</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="evtId">
                    <h6 class="text-dark mb-1" id="evtTitle"></h6>
                    <small class="text-secondary d-block mb-3" id="evtSubtitle"></small>
                    <div class="row">
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-dark">Scheduled Date</label>
                            <input type="date" class="form-control" id="evtScheduledDate">
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-dark">Time of Day</label>
                            <select class="form-select" id="evtTimeOfDay">
                                <option value="half">Half Day</option>
                                <option value="whole">Whole Day</option>
                            </select>
                        </div>
                        <div class="col-md-4 mb-3">
                            <label class="form-label text-dark">Priority</label>
                            <select class="form-select" id="evtPriority">
                                <option value="critical">Critical</option>
                                <option value="high">High</option>
                                <option value="medium">Medium</option>
                                <option value="low">Low</option>
                            </select>
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Assigned Workers</label>
                        <select class="form-select" id="evtWorkers" multiple size="5">
                            @foreach($schedule->workers as $w)
                                <option value="{{ $w->id }}">{{ $w->workerName }} (priority #{{ $w->priority }})</option>
                            @endforeach
                        </select>
                        <small class="text-secondary">Hold Ctrl/Cmd to select multiple.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Remarks</label>
                        <textarea class="form-control" id="evtRemarks" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="saveEventBtn"><i class="bx bx-save me-1"></i>Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    {{-- Complete event modal --}}
    <div class="modal fade" id="completeEventModal" tabindex="-1">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark"><i class="bx bx-check-circle text-success me-2"></i>Mark Activity Complete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="cmpEvtId">
                    <h6 class="text-dark mb-1" id="cmpEvtTitle"></h6>
                    <small class="text-secondary d-block mb-3" id="cmpEvtSubtitle"></small>

                    <div class="mb-3">
                        <label class="form-label text-dark">Extra Cost (PHP)</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" min="0" step="0.01" class="form-control" id="cmpExtraCost" value="0">
                        </div>
                        <small class="text-secondary">Optional. Log any unforeseen expense beyond the plan.</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Reason for Extra Cost</label>
                        <textarea class="form-control" id="cmpExtraCostDescription" rows="2" placeholder="Why was extra spending needed?"></textarea>
                    </div>
                    <div class="mb-3">
                        <label class="form-label text-dark">Remarks / Notes</label>
                        <textarea class="form-control" id="cmpRemarks" rows="3" placeholder="Any notes about this activity..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-success" id="confirmCompleteBtn"><i class="bx bx-check me-1"></i>Mark Complete</button>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3500 };

const SCHEDULE_ID = {{ $schedule->id }};
const ROOT = '{{ url('/') }}';
const CSRF = '{{ csrf_token() }}';

const Q = `?scheduleId=${SCHEDULE_ID}`;
const URLS = {
    events:           () => `${ROOT}/anisenso-schedule-manager-calendar-events${Q}`,
    eventUpdate:      (id) => `${ROOT}/anisenso-schedule-manager-calendar-event-update${Q}&id=${id}`,
    eventComplete:    (id) => `${ROOT}/anisenso-schedule-manager-calendar-event-complete${Q}&id=${id}`,
    eventUncomplete:  (id) => `${ROOT}/anisenso-schedule-manager-calendar-event-uncomplete${Q}&id=${id}`,
};

const LOTS = @json($schedule->lots->mapWithKeys(fn($l) => [$l->id => $l->lotName]));
const WORKERS = @json($schedule->workers->mapWithKeys(fn($w) => [$w->id => $w->workerName]));

let allEvents = [];

function escapeHtml(s) { return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c])); }
function fmt(date) { return new Date(date + 'T00:00:00').toLocaleDateString('en-US', { weekday:'short', month:'short', day:'numeric', year:'numeric' }); }

function loadEvents() {
    $('#calendarLoading').show();
    $('#calendarContent').hide();
    $('#calendarEmpty').hide();
    $.get(URLS.events(), function (res) {
        $('#calendarLoading').hide();
        if (!res.success) { toastr.error(res.message); return; }
        allEvents = res.data || [];
        applyFilters();
    }).fail(() => { $('#calendarLoading').hide(); toastr.error('Failed to load events.'); });
}

function applyFilters() {
    const status = $('#filterStatus').val();
    const lotId = $('#filterLot').val();
    const type = $('#filterType').val();

    let events = allEvents.slice();
    if (status) events = events.filter(e => e.status === status);
    if (lotId) events = events.filter(e => String(e.lotId) === String(lotId));
    if (type) events = events.filter(e => e.eventType === type);

    renderEvents(events);
}

function renderEvents(events) {
    $('#eventCounter').text(events.length + ' event(s)');
    if (!events.length) {
        $('#calendarEmpty').show();
        $('#calendarContent').hide();
        return;
    }

    // Determine cumulative blockers per lot.
    const byLot = {};
    allEvents.forEach(e => {
        if (!byLot[e.lotId]) byLot[e.lotId] = [];
        byLot[e.lotId].push(e);
    });
    Object.values(byLot).forEach(arr => arr.sort((a,b) => (a.scheduledDate||'').localeCompare(b.scheduledDate||'') || (a.sequenceOrder - b.sequenceOrder)));
    const firstIncompleteByLot = {};
    Object.entries(byLot).forEach(([lotId, arr]) => {
        const first = arr.find(e => e.status !== 'completed');
        if (first) firstIncompleteByLot[lotId] = String(first.id);
    });

    const byDate = {};
    events.forEach(e => {
        const d = e.scheduledDate;
        if (!byDate[d]) byDate[d] = [];
        byDate[d].push(e);
    });

    const dates = Object.keys(byDate).sort();
    let html = '';
    dates.forEach(date => {
        html += `<div class="day-header"><strong class="text-dark">${escapeHtml(fmt(date))}</strong> <small class="text-secondary">— ${byDate[date].length} event(s)</small></div>`;
        html += `<div class="table-responsive"><table class="table table-sm align-middle mb-0"><thead><tr>
            <th>Activity</th>
            <th>Lot</th>
            <th>Type</th>
            <th>Time</th>
            <th>Priority</th>
            <th>Workers</th>
            <th>Status</th>
            <th class="text-end" style="width:260px;">Actions</th>
        </tr></thead><tbody>`;
        byDate[date].forEach(e => {
            const isFirstIncomplete = firstIncompleteByLot[e.lotId] === String(e.id);
            const blocked = (e.status !== 'completed') && !isFirstIncomplete;
            const lotName = LOTS[e.lotId] || ('Lot #' + e.lotId);
            const workersTxt = (e.assignedWorkerIds || []).map(id => escapeHtml(WORKERS[id] || ('#' + id))).join(', ') || '<span class="text-secondary">—</span>';
            const pillType = e.eventType === 'activity' ? 'pill-activity' : 'pill-irrigation';
            const pillPriority = 'priority-' + (e.priority || 'medium');
            html += `<tr class="event-row ${e.status === 'completed' ? 'completed' : ''} ${blocked ? 'blocked' : ''}" data-id="${e.id}">
                <td class="text-dark"><strong class="event-title">${escapeHtml(e.eventTitle)}</strong></td>
                <td class="text-dark">${escapeHtml(lotName)}</td>
                <td><span class="event-pill ${pillType}">${e.eventType}</span></td>
                <td class="text-dark">${e.timeOfDay === 'whole' ? 'Whole' : 'Half'}</td>
                <td><span class="event-pill ${pillPriority}">${e.priority}</span></td>
                <td class="text-dark">${workersTxt}</td>
                <td>${
                    e.status === 'completed'
                        ? '<span class="badge bg-success text-white"><i class="bx bx-check"></i> Completed</span>'
                        : (blocked ? '<span class="badge bg-secondary text-white" title="Waiting for earlier events">Blocked</span>' : '<span class="badge bg-warning text-dark">Pending</span>')
                }</td>
                <td class="text-end">
                    ${e.status === 'completed'
                        ? `<button class="btn btn-sm btn-outline-secondary uncomplete-btn" data-id="${e.id}"><i class="bx bx-undo"></i> Undo</button>`
                        : `<button class="btn btn-sm btn-outline-primary edit-event-btn" data-id="${e.id}"><i class="bx bx-edit-alt"></i></button>
                           <button class="btn btn-sm btn-outline-success complete-btn" data-id="${e.id}" ${blocked ? 'disabled title="Earlier event for this lot not completed yet"' : ''}><i class="bx bx-check"></i> Complete</button>`
                    }
                </td>
            </tr>`;
            if (e.remarks) {
                html += `<tr class="${e.status === 'completed' ? 'completed' : ''}"><td colspan="8" class="text-secondary" style="padding-left:24px; font-size:12px;"><i class="bx bx-note"></i> ${escapeHtml(e.remarks)}</td></tr>`;
            }
            if (e.status === 'completed' && Number(e.extraCost) > 0) {
                html += `<tr class="completed"><td colspan="8" class="text-secondary" style="padding-left:24px; font-size:12px;"><i class="bx bx-money"></i> Extra cost: ₱ ${Number(e.extraCost).toFixed(2)} ${e.extraCostDescription ? '— ' + escapeHtml(e.extraCostDescription) : ''}</td></tr>`;
            }
        });
        html += `</tbody></table></div>`;
    });
    $('#calendarContent').html(html).show();
    $('#calendarEmpty').hide();
}

$('#filterStatus, #filterLot, #filterType').on('change', applyFilters);

// Edit
$(document).on('click', '.edit-event-btn', function () {
    const id = $(this).data('id');
    const ev = allEvents.find(e => e.id === id);
    if (!ev) return;
    $('#evtId').val(ev.id);
    $('#evtTitle').text(ev.eventTitle);
    $('#evtSubtitle').text((LOTS[ev.lotId] || '') + ' • ' + ev.eventType);
    $('#evtScheduledDate').val(ev.scheduledDate);
    $('#evtTimeOfDay').val(ev.timeOfDay);
    $('#evtPriority').val(ev.priority);
    $('#evtRemarks').val(ev.remarks || '');
    $('#evtWorkers').val((ev.assignedWorkerIds || []).map(String));
    $('#editEventModal').modal('show');
});

$('#saveEventBtn').on('click', function () {
    const id = $('#evtId').val();
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: URLS.eventUpdate(id),
        type: 'PUT',
        data: {
            _token: CSRF,
            scheduledDate: $('#evtScheduledDate').val(),
            timeOfDay: $('#evtTimeOfDay').val(),
            priority: $('#evtPriority').val(),
            assignedWorkerIds: $('#evtWorkers').val() || [],
            remarks: $('#evtRemarks').val()
        },
        success: (res) => {
            if (res.success) { toastr.success(res.message); $('#editEventModal').modal('hide'); loadEvents(); }
            else toastr.error(res.message);
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Changes')
    });
});

// Complete
$(document).on('click', '.complete-btn', function () {
    const id = $(this).data('id');
    const ev = allEvents.find(e => e.id === id);
    if (!ev) return;
    $('#cmpEvtId').val(ev.id);
    $('#cmpEvtTitle').text(ev.eventTitle);
    $('#cmpEvtSubtitle').text((LOTS[ev.lotId] || '') + ' • Scheduled for ' + fmt(ev.scheduledDate));
    $('#cmpExtraCost').val(0);
    $('#cmpExtraCostDescription').val('');
    $('#cmpRemarks').val(ev.remarks || '');
    $('#completeEventModal').modal('show');
});

$('#confirmCompleteBtn').on('click', function () {
    const id = $('#cmpEvtId').val();
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: URLS.eventComplete(id),
        type: 'POST',
        data: {
            _token: CSRF,
            extraCost: $('#cmpExtraCost').val() || 0,
            extraCostDescription: $('#cmpExtraCostDescription').val(),
            remarks: $('#cmpRemarks').val()
        },
        success: (res) => {
            if (res.success) { toastr.success(res.message); $('#completeEventModal').modal('hide'); loadEvents(); }
            else toastr.error(res.message);
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to complete'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-check me-1"></i>Mark Complete')
    });
});

// Un-complete
$(document).on('click', '.uncomplete-btn', function () {
    const id = $(this).data('id');
    if (!confirm('Revert this event back to pending?')) return;
    $.ajax({
        url: URLS.eventUncomplete(id),
        type: 'POST',
        data: { _token: CSRF },
        success: (res) => {
            if (res.success) { toastr.success(res.message); loadEvents(); }
            else toastr.error(res.message);
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed.')
    });
});

$(function () { loadEvents(); });
</script>
@endsection
