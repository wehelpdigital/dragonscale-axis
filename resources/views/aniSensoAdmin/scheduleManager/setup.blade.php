@extends('layouts.master')

@section('title') Setup — {{ $schedule->title }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .sm-tabs .nav-link { color: #495057; font-weight: 500; }
    .sm-tabs .nav-link.active { color: #556ee6; border-bottom: 2px solid #556ee6; background: transparent; }
    .sm-pill { border-radius: 50px; padding: 4px 12px; font-size: 11px; font-weight: 500; }
    .priority-critical { background:#9c1c1c; color:#fff; font-weight:700; text-transform:uppercase; letter-spacing:.3px; }
    .priority-high { background:#f46a6a; color:#fff; }
    .priority-medium { background:#f1b44c; color:#212529; }
    .priority-low { background:#74788d; color:#fff; }
    .activity-card { border-left: 3px solid #556ee6; }
    .item-tag { background:#eef0fb; color:#3a4699; padding:2px 8px; border-radius:8px; font-size:11px; margin-right:4px; display:inline-block; margin-bottom:3px;}
    .item-tag.service { background:#e6f7f1; color:#0f8a5f; }

    /* ---- Activities grouped by date with flat color coding ---- */
    .activity-timeline { padding: 4px 0; }
    .date-group { margin-bottom: 18px; --date-color: #556ee6; }
    .date-header {
        display: flex; align-items: center; gap: 10px;
        padding: 10px 14px;
        border-radius: 8px 8px 0 0;
        background: var(--date-color);
        color: #fff;
    }
    .date-header .date-header-day { font-weight: 600; font-size: 13px; opacity: .85; }
    .date-header .date-header-date { font-weight: 700; font-size: 16px; }
    .date-header .date-header-count {
        margin-left: auto;
        background: rgba(255,255,255,0.22);
        padding: 3px 12px;
        border-radius: 12px;
        font-size: 12px;
        font-weight: 500;
    }
    .date-header-edit-btn {
        background: rgba(255,255,255,0.15);
        color: #fff;
        border: 1px solid rgba(255,255,255,0.35);
        border-radius: 6px;
        padding: 3px 9px;
        font-size: 13px;
        cursor: pointer;
        line-height: 1;
        transition: background .12s ease;
    }
    .date-header-edit-btn:hover { background: rgba(255,255,255,0.3); }
    .date-header-delete-btn:hover { background: #f46a6a; border-color: #f46a6a; color: #fff; }
    .date-activities {
        padding: 10px;
        background: #fff;
        border: 1px solid var(--date-color);
        border-top: none;
        border-radius: 0 0 8px 8px;
    }
    /* Per-date commentary block — slots between the date-header and the
       activity cards. Tinted with the date-group color so it visually
       belongs to its date but stays subordinate to the activity cards. */
    .date-note-block {
        background: #fffaf0;
        border-left: 1px solid var(--date-color);
        border-right: 1px solid var(--date-color);
        border-bottom: 1px dashed #f0d899;
        padding: 8px 14px;
    }
    .date-note-inner {
        display: flex;
        align-items: flex-start;
        gap: 8px;
    }
    .date-note-icon {
        color: #b78103;
        font-size: 16px;
        flex-shrink: 0;
        margin-top: 1px;
    }
    .date-note-text {
        color: #5a4413;
        font-size: 13px;
        line-height: 1.55;
        flex-grow: 1;
        word-break: break-word;
    }
    /* Note-icon button states */
    .date-note-btn.has-note { background: rgba(255, 255, 255, 0.55); color: #fff; border-color: rgba(255,255,255,0.85); }
    .date-note-btn.has-note:hover { background: rgba(255, 255, 255, 0.78); }
    .activity-card {
        background: #fff;
        border: 1px solid #e6e8ec;
        border-left: 4px solid var(--date-color);
        border-radius: 6px;
        padding: 12px 14px;
        margin-bottom: 8px;
        box-shadow: 0 1px 2px rgba(0,0,0,0.03);
        transition: box-shadow .15s ease;
    }
    .activity-card:last-child { margin-bottom: 0; }
    .activity-card:hover { box-shadow: 0 2px 8px rgba(0,0,0,0.06); }
    .activity-card .step-meta { color:#74788d; font-size:12px; }
    .activity-card .step-meta i { color: var(--date-color); margin-right: 2px; }
    .activity-card-lots {
        font-size: 13px;
        color: #74788d;
        line-height: 1.6;
        margin-top: 8px;
        margin-bottom: 8px;
        display: flex;
        align-items: center;
        flex-wrap: wrap;
        gap: 6px 4px;
    }
    .activity-card-lots i.bx-map-pin {
        color: var(--date-color);
        font-size: 17px;
        vertical-align: middle;
    }
    .activity-card-lots .item-tag {
        font-size: 13px;
        padding: 5px 12px;
        border-radius: 14px;
        font-weight: 500;
        margin: 0;            /* gap on the parent handles spacing */
    }
    /* "No activities scheduled" gap markers between date groups */
    .rest-day-marker {
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 6px 14px;
        margin-bottom: 6px;
        background: #fafafa;
        border-left: 3px solid #d9dde3;
        border-radius: 4px;
        font-size: 12px;
    }
    .rest-day-marker .rest-day-icon { color: #b8bfc7; font-size: 14px; }
    .rest-day-marker .rest-day-date { font-weight: 600; color: #6b7280; }
    .rest-day-marker .rest-day-tag { color: #9aa0a6; margin-left: auto; font-style: italic; font-size: 11px; }
    /* Drag-and-drop visuals */
    .activity-card[draggable="true"] { cursor: grab; }
    .activity-card.dragging { opacity: .45; cursor: grabbing; }
    .date-activities.drop-target { background: rgba(0,0,0,0.04); outline: 2px dashed var(--date-color); outline-offset: -4px; }

    /* Flat color palette — cycled by date order (0..7) */
    .date-color-0 { --date-color: #4A90E2; } /* blue   */
    .date-color-1 { --date-color: #50C878; } /* green  */
    .date-color-2 { --date-color: #F39C12; } /* orange */
    .date-color-3 { --date-color: #9B59B6; } /* purple */
    .date-color-4 { --date-color: #1ABC9C; } /* teal   */
    .date-color-5 { --date-color: #E74C3C; } /* red    */
    .date-color-6 { --date-color: #5C6BC0; } /* indigo */
    .date-color-7 { --date-color: #16A085; } /* sea    */
    .day-pill { display:inline-block; padding:4px 10px; margin:2px; border-radius:20px; background:#f1f1f1; cursor:pointer; font-size:12px; color:#495057; }
    .day-pill.active { background:#556ee6; color:#fff; }
    /* Lot selector chips used inside Default Groupings */
    .lot-chip { display:inline-block; padding:5px 12px; margin:3px; border-radius:20px; background:#fff; border:1px solid #d3d6db; cursor:pointer; font-size:12px; color:#495057; user-select:none; transition: all .12s ease; }
    .lot-chip:hover { border-color:#556ee6; color:#556ee6; }
    .lot-chip.active { background:#556ee6; color:#fff; border-color:#556ee6; }
    .lot-chip.active:hover { background:#4458c4; border-color:#4458c4; color:#fff; }
    .lot-chip-container { padding:6px; background:#fff; border:1px dashed #d3d6db; border-radius:6px; min-height:44px; }
    /* TinyMCE-produced description rendering inside an activity card */
    .activity-description-content p { margin: 0 0 .5em; }
    .activity-description-content p:last-child { margin-bottom: 0; }
    .activity-description-content ul, .activity-description-content ol { margin: .25em 0 .5em 1.25rem; padding: 0; }
    .activity-description-content li { margin-bottom: .15em; }
    .activity-description-content h1, .activity-description-content h2,
    .activity-description-content h3, .activity-description-content h4 { margin: .5em 0 .25em; font-weight: 600; color: #212529; }
    .activity-description-content h1 { font-size: 1.05rem; }
    .activity-description-content h2 { font-size: 1rem; }
    .activity-description-content h3 { font-size: .95rem; }
    .activity-description-content h4 { font-size: .9rem; }
    .activity-description-content a { color: #556ee6; text-decoration: underline; }
    .activity-description-content table { border-collapse: collapse; }
    .activity-description-content table td, .activity-description-content table th { border: 1px solid #e6e8ec; padding: 2px 6px; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('anisenso-schedule-manager.index') }}" class="text-decoration-none">Schedule Manager</a> @endslot
        @slot('title') {{ $schedule->title }} @endslot
    @endcomponent

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="bx bx-error-circle me-2"></i>{{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif
    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bx bx-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @php
        $readinessIssues = $schedule->getReadinessIssues();
        $isReadyToGenerate = empty($readinessIssues);
    @endphp

    {{-- Header --}}
    <div class="card">
        <div class="card-body">
            <div class="d-flex justify-content-between align-items-start flex-wrap gap-3">
                <div>
                    @php
                        $statusMap = [
                            'draft' => 'bg-secondary text-white',
                            'setup' => 'bg-info text-white',
                            'generated' => 'bg-primary text-white',
                            'completed' => 'bg-success text-white',
                            'archived' => 'bg-dark text-white',
                        ];
                        $cls = $statusMap[$schedule->status] ?? 'bg-secondary text-white';
                    @endphp
                    <h4 class="text-dark mb-1">
                        <span id="scheduleHeaderTitle">{{ $schedule->title }}</span>
                        <span class="badge {{ $cls }} ms-1" style="text-transform:capitalize;">{{ $schedule->status }}</span>
                    </h4>
                    <p class="text-secondary mb-0" id="scheduleHeaderDescription">{{ $schedule->description ?: 'No description provided.' }}</p>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <a href="{{ $isReadyToGenerate ? route('anisenso-schedule-manager.generate.form', ['scheduleId' => $schedule->id]) : 'javascript:void(0);' }}"
                       class="btn btn-primary btn-sm @if(!$isReadyToGenerate) disabled @endif"
                       id="generateScheduleBtn"
                       data-ready-url="{{ route('anisenso-schedule-manager.generate.form', ['scheduleId' => $schedule->id]) }}"
                       @if(!$isReadyToGenerate) aria-disabled="true" title="Finish required setup first" @endif>
                        <i class="bx bx-calendar-plus me-1"></i> <span id="generateScheduleBtnLabel">{{ $isReadyToGenerate ? 'Generate Calendar' : 'Generate (locked)' }}</span>
                    </a>
                    @if($schedule->currentGeneration)
                        <a href="{{ route('anisenso-schedule-manager.calendar', ['scheduleId' => $schedule->id]) }}" class="btn btn-success btn-sm">
                            <i class="bx bx-calendar me-1"></i> Open Calendar
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>


    {{-- Tabs --}}
    <div class="card">
        <div class="card-body">
            <ul class="nav nav-tabs sm-tabs mb-3" role="tablist">
                <li class="nav-item"><a class="nav-link active" data-bs-toggle="tab" href="#tab-settings"><i class="bx bx-cog me-1"></i> Settings</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-lots"><i class="bx bx-map-pin me-1"></i> Lots <span class="badge bg-light text-dark ms-1" id="badge-lots">{{ $schedule->lots->count() }}</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-workers"><i class="bx bx-user me-1"></i> Workers <span class="badge bg-light text-dark ms-1" id="badge-workers">{{ $schedule->workers->count() }}</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-protocol"><i class="bx bx-file me-1"></i> Protocol</a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-materials"><i class="bx bx-package me-1"></i> Materials <span class="badge bg-light text-dark ms-1" id="badge-materials">{{ $schedule->materials->count() }}</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-services"><i class="bx bx-wrench me-1"></i> Services <span class="badge bg-light text-dark ms-1" id="badge-services">{{ $schedule->services->count() }}</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-activities"><i class="bx bx-task me-1"></i> Activities <span class="badge bg-light text-dark ms-1" id="badge-activities">{{ $schedule->activities->count() }}</span></a></li>
                <li class="nav-item"><a class="nav-link" data-bs-toggle="tab" href="#tab-irrigations"><i class="bx bx-water me-1"></i> Irrigation <span class="badge bg-light text-dark ms-1" id="badge-irrigations">{{ $schedule->irrigations->count() }}</span></a></li>
            </ul>

            <div class="tab-content">
                {{-- SETTINGS --}}
                <div class="tab-pane fade show active" id="tab-settings">
                    @include('aniSensoAdmin.scheduleManager.partials.settings', ['schedule' => $schedule])
                </div>

                {{-- LOTS --}}
                <div class="tab-pane fade" id="tab-lots">
                    @include('aniSensoAdmin.scheduleManager.partials.lots', ['schedule' => $schedule])
                </div>

                {{-- WORKERS --}}
                <div class="tab-pane fade" id="tab-workers">
                    @include('aniSensoAdmin.scheduleManager.partials.workers', ['schedule' => $schedule])
                </div>

                {{-- PROTOCOL --}}
                <div class="tab-pane fade" id="tab-protocol">
                    @include('aniSensoAdmin.scheduleManager.partials.protocol', ['schedule' => $schedule])
                </div>

                {{-- MATERIALS --}}
                <div class="tab-pane fade" id="tab-materials">
                    @include('aniSensoAdmin.scheduleManager.partials.materials', ['schedule' => $schedule])
                </div>

                {{-- SERVICES --}}
                <div class="tab-pane fade" id="tab-services">
                    @include('aniSensoAdmin.scheduleManager.partials.services', ['schedule' => $schedule])
                </div>

                {{-- ACTIVITIES --}}
                <div class="tab-pane fade" id="tab-activities">
                    @include('aniSensoAdmin.scheduleManager.partials.activities', ['schedule' => $schedule])
                </div>

                {{-- IRRIGATIONS --}}
                <div class="tab-pane fade" id="tab-irrigations">
                    @include('aniSensoAdmin.scheduleManager.partials.irrigations', ['schedule' => $schedule])
                </div>
            </div>
        </div>
    </div>

    {{-- Shared confirm modal — used by every delete handler in this page. --}}
    <div class="modal fade" id="confirmActionModal" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title text-dark">
                        <i class="bx bx-help-circle me-2" id="confirmActionIcon"></i>
                        <span id="confirmActionTitle">Confirm</span>
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p class="text-dark mb-0" id="confirmActionMessage">Are you sure?</p>
                    <small class="text-secondary d-block mt-2" id="confirmActionDetail" style="display:none;"></small>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal" id="confirmActionCancelBtn">Cancel</button>
                    <button type="button" class="btn btn-danger" id="confirmActionConfirmBtn">Delete</button>
                </div>
            </div>
        </div>
    </div>

@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<!-- TinyMCE 6 for rich-text activity description -->
<script src="https://cdn.tiny.cloud/1/lbsbsr7t63wjii3wjqcftu0e9ot0c6e6f7mle8yqp6umxmpq/tinymce/6/tinymce.min.js" referrerpolicy="origin"></script>
<script>
toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };

const SCHEDULE_ID = {{ $schedule->id }};
const ROOT = '{{ url('/') }}';
const CSRF = '{{ csrf_token() }}';

// URL helpers — flat paths, IDs in query string.
const Q = `?scheduleId=${SCHEDULE_ID}`;
const URLS = {
    scheduleUpdate:    () => `${ROOT}/anisenso-schedule-manager-update?id=${SCHEDULE_ID}`,

    lotsStore:         () => `${ROOT}/anisenso-schedule-manager-lots-store${Q}`,
    lotsUpdate:        (id) => `${ROOT}/anisenso-schedule-manager-lots-update${Q}&id=${id}`,
    lotsDelete:        (id) => `${ROOT}/anisenso-schedule-manager-lots-delete${Q}&id=${id}`,

    workersStore:      () => `${ROOT}/anisenso-schedule-manager-workers-store${Q}`,
    workersUpdate:     (id) => `${ROOT}/anisenso-schedule-manager-workers-update${Q}&id=${id}`,
    workersDelete:     (id) => `${ROOT}/anisenso-schedule-manager-workers-delete${Q}&id=${id}`,
    workersRules:      (id) => `${ROOT}/anisenso-schedule-manager-workers-rules${Q}&id=${id}`,
    workersRulesSave:  (id) => `${ROOT}/anisenso-schedule-manager-workers-rules-save${Q}&id=${id}`,

    protocolSave:      () => `${ROOT}/anisenso-schedule-manager-protocol-save${Q}`,

    materialsStore:    () => `${ROOT}/anisenso-schedule-manager-materials-store${Q}`,
    materialsUpdate:   (id) => `${ROOT}/anisenso-schedule-manager-materials-update${Q}&id=${id}`,
    materialsDelete:   (id) => `${ROOT}/anisenso-schedule-manager-materials-delete${Q}&id=${id}`,

    servicesStore:     () => `${ROOT}/anisenso-schedule-manager-services-store${Q}`,
    servicesUpdate:    (id) => `${ROOT}/anisenso-schedule-manager-services-update${Q}&id=${id}`,
    servicesDelete:    (id) => `${ROOT}/anisenso-schedule-manager-services-delete${Q}&id=${id}`,

    activitiesStore:     () => `${ROOT}/anisenso-schedule-manager-activities-store${Q}`,
    activitiesShow:      (id) => `${ROOT}/anisenso-schedule-manager-activities-show${Q}&id=${id}`,
    activitiesUpdate:    (id) => `${ROOT}/anisenso-schedule-manager-activities-update${Q}&id=${id}`,
    activitiesDelete:    (id) => `${ROOT}/anisenso-schedule-manager-activities-delete${Q}&id=${id}`,
    activitiesDuplicate: (id) => `${ROOT}/anisenso-schedule-manager-activities-duplicate${Q}&id=${id}`,
    activitiesSetDate:   (id) => `${ROOT}/anisenso-schedule-manager-activities-set-date${Q}&id=${id}`,
    activitiesReorder:   () => `${ROOT}/anisenso-schedule-manager-activities-reorder${Q}`,
    activitiesExport:    () => `${ROOT}/anisenso-schedule-manager-activities-export${Q}`,
    activitiesRestore:   (id) => `${ROOT}/anisenso-schedule-manager-activities-restore${Q}&id=${id}`,
    activitiesToDraft:   (id) => `${ROOT}/anisenso-schedule-manager-activities-to-draft${Q}&id=${id}`,
    activitiesFromDraft: (id) => `${ROOT}/anisenso-schedule-manager-activities-from-draft${Q}&id=${id}`,
    activitiesDrafts:    () => `${ROOT}/anisenso-schedule-manager-activities-drafts${Q}`,
    activitiesLabor:     () => `${ROOT}/anisenso-schedule-manager-activities-labor${Q}`,
    workerPresentation:  () => `${ROOT}/anisenso-schedule-manager-worker-presentation${Q}`,

    activityVersionsIndex:      () => `${ROOT}/anisenso-schedule-manager-activity-versions${Q}`,
    activityVersionsStore:      () => `${ROOT}/anisenso-schedule-manager-activity-versions-store${Q}`,
    activityVersionsUpdate:     (id) => `${ROOT}/anisenso-schedule-manager-activity-versions-update${Q}&id=${id}`,
    activityVersionsDelete:     (id) => `${ROOT}/anisenso-schedule-manager-activity-versions-delete${Q}&id=${id}`,
    activityVersionsSetActive:  (id) => `${ROOT}/anisenso-schedule-manager-activity-versions-set-active${Q}&id=${id}`,

    activitiesDateNoteSave:     () => `${ROOT}/anisenso-schedule-manager-activities-date-note-save${Q}`,
    activitiesDateNoteDelete:   () => `${ROOT}/anisenso-schedule-manager-activities-date-note-delete${Q}`,

    irrigationsStore:  () => `${ROOT}/anisenso-schedule-manager-irrigations-store${Q}`,
    irrigationsUpdate: (id) => `${ROOT}/anisenso-schedule-manager-irrigations-update${Q}&id=${id}`,
    irrigationsDelete: (id) => `${ROOT}/anisenso-schedule-manager-irrigations-delete${Q}&id=${id}`,
};

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}
function fmtNumber(n, d=2) { const v = Number(n ?? 0); return isNaN(v) ? '0' : v.toFixed(d); }
function fmtPeso(n) { return '₱ ' + Number(n ?? 0).toLocaleString('en-PH', { minimumFractionDigits: 2, maximumFractionDigits: 2 }); }

// --- Tab persistence (works even if some saves still reload) ---
$('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    history.replaceState(null, null, e.target.getAttribute('href'));
});
$(function () {
    if (location.hash) {
        const $tab = $('.sm-tabs a[data-bs-toggle="tab"][href="' + location.hash + '"]');
        if ($tab.length) $tab.tab('show');
    }
});

function bumpBadge(id, delta = 1) {
    const $b = $('#' + id);
    const v = parseInt($b.text(), 10) || 0;
    $b.text(Math.max(0, v + delta));
    recomputeReadiness();
}

// ---- Day 0 anchor per lot ----
// Two layered maps:
//   LOT_MANUAL_DAY_ZERO  → date manually set on each lot (fallback baseline)
//   LOT_DAY_ZERO_DATES   → effective anchor used by computeDasLabel(). Built by
//                          recomputeLotDayZero(): activity-card flags override
//                          the manual baseline so the "anchor activity" wins.
window.LOT_MANUAL_DAY_ZERO = @json($schedule->lots->mapWithKeys(fn($l) => [
    $l->id => $l->dayZeroDate ? $l->dayZeroDate->format('Y-m-d') : null,
]));
window.LOT_DAY_ZERO_DATES = Object.assign({}, window.LOT_MANUAL_DAY_ZERO);

window.MONTH_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];

window.parseLocalDate = window.parseLocalDate || function (s) {
    if (!s) return null;
    const [y, m, d] = String(s).slice(0, 10).split('-').map(n => parseInt(n, 10));
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
};

window.getScheduleDayType = window.getScheduleDayType || function () {
    return ($('.day-type-label').first().text() || 'DAS').trim();
};

// Compute the " · DAS+N" suffix for a lot/date pair, or '' when no anchor is set.
window.computeDasLabel = function (lotId, targetDate) {
    if (!targetDate) return '';
    const anchor = LOT_DAY_ZERO_DATES[lotId];
    if (!anchor) return '';
    const a = parseLocalDate(anchor);
    const b = parseLocalDate(targetDate);
    if (!a || !b) return '';
    const delta = Math.round((b - a) / 86400000);
    const sign = delta > 0 ? '+' : '';
    return ' · ' + getScheduleDayType() + sign + delta;
};

// Walk every activity card on the timeline and refresh the DAS suffix on
// each of its lot chips. Called after any lot Day 0 change.
window.refreshActivityCardDasLabels = function () {
    $('#activitiesList .activity-card[data-id]').each(function () {
        const targetDate = ($(this).attr('data-target-date') || '').trim();
        $(this).find('.activity-card-lots .item-tag[data-lot-id]').each(function () {
            const lotId = parseInt($(this).attr('data-lot-id'), 10);
            const lotName = $(this).attr('data-lot-name') || $(this).text().split(' · ')[0];
            $(this).text(lotName + computeDasLabel(lotId, targetDate));
        });
    });
};

// Rebuild LOT_DAY_ZERO_DATES from (a) every activity-card flagged as Day 0
// anchor, and (b) the manual per-lot fallback. When multiple activities anchor
// the same lot, the earliest targetDate wins (Day 0 = start of the cycle).
// LOT_DAY_ZERO_SOURCE runs in parallel so callers can tell where each anchor
// came from — 'manual' (set on the lot itself) or the activity id that
// flagged it. The activity modal uses this to hide the "Mark as Day 0"
// checkbox whenever a selected lot is already anchored elsewhere.
window.recomputeLotDayZero = function () {
    const map = Object.assign({}, window.LOT_MANUAL_DAY_ZERO || {});
    const source = {};
    Object.keys(map).forEach(lotId => {
        if (map[lotId]) source[lotId] = 'manual';
    });
    $('#activitiesList .activity-card[data-is-day-zero="1"]').each(function () {
        const activityId = parseInt($(this).attr('data-id'), 10);
        const targetDate = ($(this).attr('data-target-date') || '').trim();
        if (!targetDate) return;
        $(this).find('.activity-card-lots .item-tag[data-lot-id]').each(function () {
            const lotId = parseInt($(this).attr('data-lot-id'), 10);
            if (!lotId) return;
            const existing = map[lotId];
            if (!existing || targetDate < existing) {
                map[lotId] = targetDate;
                source[lotId] = activityId;
            }
        });
    });
    window.LOT_DAY_ZERO_DATES = map;
    window.LOT_DAY_ZERO_SOURCE = source;
    refreshActivityCardDasLabels();
};

// Seed the derived map immediately so server-rendered Day 0 anchors apply
// without waiting for the first mutation.
$(function () { recomputeLotDayZero(); });

/**
 * Shared confirm-modal helper. Replaces native confirm() so deletes use a
 * consistent in-page modal instead of the browser's blocking alert.
 *
 * Usage:
 *   confirmAction({
 *       title: 'Delete lot',
 *       message: 'Delete lot "Lot A"?',
 *       detail: 'This will mark the lot as deleted.',
 *       confirmText: 'Delete',
 *       confirmClass: 'btn-danger',
 *       icon: 'bx-trash text-danger',
 *       onConfirm: () => { ... },
 *   });
 */
window.confirmAction = function (opts) {
    const o = Object.assign({
        title: 'Confirm',
        message: 'Are you sure?',
        detail: '',
        confirmText: 'Delete',
        confirmClass: 'btn-danger',
        icon: 'bx-trash text-danger',
        onConfirm: null,
    }, opts || {});

    $('#confirmActionTitle').text(o.title);
    $('#confirmActionMessage').html(o.message);
    if (o.detail) {
        $('#confirmActionDetail').show().text(o.detail);
    } else {
        $('#confirmActionDetail').hide().text('');
    }
    $('#confirmActionIcon').attr('class', 'bx me-2 ' + o.icon);

    const $confirm = $('#confirmActionConfirmBtn');
    $confirm
        .removeClass('btn-primary btn-danger btn-warning btn-success btn-info btn-secondary')
        .addClass(o.confirmClass)
        .text(o.confirmText)
        .prop('disabled', false);

    // Detach previous click handlers so old callbacks don't fire again.
    $confirm.off('click.confirmAction').on('click.confirmAction', function () {
        if (typeof o.onConfirm === 'function') {
            try { o.onConfirm(); } catch (err) { console.error(err); }
        }
        $('#confirmActionModal').modal('hide');
    });

    $('#confirmActionModal').modal('show');
};

// --- Readiness recompute: updates the Generate button + banner live ---
const READINESS_RULES = [
    { badge: 'badge-lots',       label: 'Add at least one lot' },
    { badge: 'badge-workers',    label: 'Add at least one worker' },
    { badge: 'badge-activities', label: 'Add at least one activity' },
];

function recomputeReadiness() {
    const issues = READINESS_RULES.filter(r => (parseInt($('#' + r.badge).text(), 10) || 0) === 0);
    const $btn = $('#generateScheduleBtn');
    if (issues.length === 0) {
        $btn.removeClass('disabled').attr('href', $btn.data('ready-url')).removeAttr('aria-disabled').attr('title', '');
        $('#generateScheduleBtnLabel').text('Generate Calendar');
        $('#readinessBanner').hide();
        $('#readinessOk').show();
    } else {
        $btn.addClass('disabled').attr('href', 'javascript:void(0);').attr('aria-disabled', 'true')
            .attr('title', 'Finish setup first: ' + issues.map(r => r.label).join(', '));
        $('#generateScheduleBtnLabel').text('Generate (locked)');
        $('#readinessIssuesList').html(issues.map(r => `<li>${escapeHtml(r.label)}</li>`).join(''));
        $('#readinessBanner').show();
        $('#readinessOk').hide();
    }
}

// Guard clicks even though the disabled class blocks pointer events at the CSS level.
$(document).on('click', '#generateScheduleBtn.disabled', function (e) {
    e.preventDefault();
    toastr.warning('Finish the required setup before generating the calendar.');
});

@include('aniSensoAdmin.scheduleManager.partials.script-settings')
@include('aniSensoAdmin.scheduleManager.partials.script-lots')
@include('aniSensoAdmin.scheduleManager.partials.script-workers')
@include('aniSensoAdmin.scheduleManager.partials.script-protocol')
@include('aniSensoAdmin.scheduleManager.partials.script-materials')
@include('aniSensoAdmin.scheduleManager.partials.script-services')
@include('aniSensoAdmin.scheduleManager.partials.script-activities')
@include('aniSensoAdmin.scheduleManager.partials.script-irrigations')
</script>
@endsection
