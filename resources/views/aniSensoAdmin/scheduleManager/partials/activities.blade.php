@php
    // Resolve the version tab list + which one is active. Done here so the
    // partial stays self-contained and the controller doesn't need to inject
    // anything new beyond $schedule.
    $activityVersions = $schedule->versions;
    $activeVersion = $activityVersions->firstWhere('isActive', true) ?? $activityVersions->firstWhere('isOriginal', true) ?? $activityVersions->first();

    // Pre-index date notes by Y-m-d so each date-header can show its note in
    // O(1) without re-querying. Empty collection when nothing exists, so the
    // ->get() / ?? '' chain in the loop stays simple.
    $dateNotesByDate = $schedule->dateNotes->keyBy(function ($n) {
        return $n->noteDate->format('Y-m-d');
    });

    // Same trick for progress markers — keyed by the date AFTER which the
    // marker line sits. Each entry exposes id + noteContent so the timeline
    // can render a horizontal line with the optional note inline, and the
    // date-header bookmark button can flag dates that already have one.
    $markersByDate = $schedule->progressMarkers->keyBy(function ($m) {
        return $m->markerDate->format('Y-m-d');
    });
@endphp

<style>
    /* Brief highlight when "Today & Tomorrow" jumps to a day. */
    @keyframes ttPulse { 0%, 100% { background: transparent; } 25% { background: rgba(85,110,230,.14); } }
    #activitiesList .date-group.tt-highlight { animation: ttPulse 2.2s ease; border-radius: .5rem; }
    @media (prefers-reduced-motion: reduce) { #activitiesList .date-group.tt-highlight { animation: none; } }

    /* ---- Per-day accordion (collapse/expand date groups) ----
       State persists per schedule in localStorage (collapsedDates:<id>) so
       it survives refreshes, including the live-sync auto-refresh. */
    .date-collapse-btn .bx { transition: transform .15s ease; display: inline-block; }
    .date-group.is-collapsed .date-collapse-btn .bx { transform: rotate(-90deg); }
    .date-group.is-collapsed .date-activities,
    .date-group.is-collapsed .date-note-block { display: none; }
    .date-group.is-collapsed .date-header { border-radius: 8px; }
    /* The date text doubles as a toggle target (bigger hit area than the chevron). */
    .date-header .date-header-day,
    .date-header .date-header-date { cursor: pointer; user-select: none; }
    /* While search/type/lot filters are active, matches must be visible even
       inside collapsed days — filtering temporarily overrides the accordion. */
    #activitiesList.is-filtering .date-group.is-collapsed .date-activities { display: block; }
    #activitiesList.is-filtering .date-group.is-collapsed .date-note-block { display: block; }

    /* ---- Drag the resume-here marker / date note onto another day ---- */
    .progress-marker-bookmark[draggable="true"] { cursor: grab; }
    .progress-marker-bookmark[draggable="true"]:active { cursor: grabbing; }
    .progress-marker.marker-dragging { opacity: .45; }
    .date-note-icon[draggable="true"] { cursor: grab; }
    .date-note-icon[draggable="true"]:active { cursor: grabbing; }
    .date-note-block.note-dragging { opacity: .45; }
    .date-group.marker-drop-target .date-header,
    .date-group.note-drop-target .date-header,
    .rest-day-marker.marker-drop-target {
        outline: 2px dashed #f4a82a;
        outline-offset: 2px;
        border-radius: 8px;
    }

    /* ---- Hide/show days with no activities (mirrors the client app) ----
       Animated collapse: rest-day rows squeeze shut instead of snapping.
       State persists per schedule in localStorage (hideEmptyDays:<id>). */
    #activitiesList .rest-day-marker {
        transition: opacity .28s cubic-bezier(.22,1,.36,1), max-height .28s cubic-bezier(.22,1,.36,1),
                    margin .28s cubic-bezier(.22,1,.36,1), padding .28s cubic-bezier(.22,1,.36,1),
                    border-width .28s cubic-bezier(.22,1,.36,1);
        max-height: 9rem; overflow: hidden;
    }
    #activitiesList.hide-empty-days .rest-day-marker {
        opacity: 0; max-height: 0; margin-top: 0; margin-bottom: 0;
        padding-top: 0; padding-bottom: 0; border-width: 0; pointer-events: none;
    }
    @media (prefers-reduced-motion: reduce) { #activitiesList .rest-day-marker { transition: none; } }

    /* ---- Tools menu (mirrors the client app's Tools hamburger) ----
       The board grew more controls than a single row can hold, so the ones
       that are read rather than pressed live behind one button, in the same
       order the client app lists them. The buttons keep their ids: the
       handlers bind by id and never knew where they were drawn. */
    .sm-tools-menu { min-width: 274px; padding: .3rem 0; }
    .sm-tools-menu .dropdown-item {
        display: flex; align-items: center; gap: .55rem;
        font-size: 13px; padding: .42rem .9rem;
    }
    .sm-tools-menu .dropdown-item .bx { font-size: 16px; color: #6b7a90; }
    .sm-tools-menu .dropdown-item.active,
    .sm-tools-menu .dropdown-item.active .bx { background: #eef2ff; color: #2c3e8c; }
    .sm-tools-menu .tools-state { margin-left: auto; font-size: 10.5px; font-weight: 600; color: #98a4b6; }
    .sm-tools-menu .dropdown-item.active .tools-state { color: #2c3e8c; }
    .sm-tools-menu .dropdown-header {
        font-size: 10.5px; text-transform: uppercase; letter-spacing: .04em;
        color: #8a94a6; padding: .5rem .9rem .25rem;
    }

    /* ---- Notice / Growth / Weather panels ----
       Read-only readings of data that already exists. Styled here rather than
       in the theme because nothing else in this admin looks like them. */
    .notice-row { display:flex; gap:.7rem; padding:.7rem .2rem; border-bottom:1px solid #eef0f4; }
    .notice-row:last-child { border-bottom:0; }
    .notice-dot { width:9px; height:9px; border-radius:50%; margin-top:.42rem; flex:0 0 auto; background:#f4a82a; }
    .notice-row.is-blocking .notice-dot { background:#f46a6a; }
    .notice-label { font-weight:600; color:#343a40; font-size:13.5px; }
    .notice-detail { font-size:12.5px; color:#74788d; margin-top:.15rem; }
    .notice-where { font-size:10.5px; text-transform:uppercase; letter-spacing:.04em; color:#98a4b6; margin-top:.25rem; }

    .gs-lot { border:1px solid #e6e8ec; border-radius:10px; padding:.85rem 1rem; margin-bottom:.7rem; }
    .gs-head { display:flex; align-items:center; gap:.6rem; }
    .gs-emoji { font-size:22px; line-height:1; }
    .gs-lotname { font-weight:600; color:#343a40; }
    .gs-day { display:block; font-size:12px; color:#74788d; }
    .gs-now { font-weight:600; color:#2c3e8c; margin-top:.55rem; }
    .gs-what { font-size:13px; color:#495057; margin-top:.15rem; }
    .gs-needs { font-size:13px; color:#495057; margin-top:.45rem; background:#f6f8fb; border-radius:8px; padding:.5rem .65rem; }
    .gs-needs b { display:block; font-size:11px; text-transform:uppercase; letter-spacing:.04em; color:#8a94a6; margin-bottom:.15rem; }
    .gs-bar { height:6px; border-radius:99px; background:#eef1f6; margin-top:.6rem; overflow:hidden; }
    .gs-bar span { display:block; height:100%; background:#34c38f; border-radius:99px;
                   transition:width .28s cubic-bezier(.22,1,.36,1); }
    .gs-next { font-size:12px; color:#74788d; margin-top:.4rem; }
    .gs-steps { margin-top:.65rem; border-top:1px dashed #e6e8ec; padding-top:.55rem; }
    .gs-step { display:flex; align-items:center; gap:.5rem; font-size:12.5px; color:#98a4b6; padding:.12rem 0; }
    .gs-step .gs-sdot { width:7px; height:7px; border-radius:50%; background:#dfe3e9; flex:0 0 auto; }
    .gs-step.is-past { color:#74788d; }
    .gs-step.is-past .gs-sdot { background:#c3cbd6; }
    .gs-step.is-now { color:#2c3e8c; font-weight:600; }
    .gs-step.is-now .gs-sdot { background:#556ee6; box-shadow:0 0 0 3px rgba(85,110,230,.18); }
    .gs-when { margin-left:auto; font-size:11px; color:#98a4b6; }

    .wx-place { border:1px solid #e6e8ec; border-radius:10px; padding:.85rem 1rem; margin-bottom:.7rem; }
    .wx-days { display:flex; gap:.5rem; overflow-x:auto; padding-top:.6rem; }
    .wx-day { flex:0 0 auto; min-width:86px; text-align:center; border-radius:10px; padding:.55rem .4rem; background:#f6f8fb; }
    .wx-day.is-today { background:#eef2ff; }
    .wx-dow { font-size:11px; font-weight:600; color:#74788d; text-transform:uppercase; letter-spacing:.03em; }
    .wx-emoji { font-size:22px; line-height:1.4; }
    .wx-temp { font-size:12.5px; color:#343a40; font-weight:600; }
    .wx-pop { font-size:11px; color:#556ee6; }
    .wx-text { font-size:10.5px; color:#98a4b6; }

    .share-social { display:flex; gap:.5rem; flex-wrap:wrap; }
    .share-social .btn { flex:1 1 130px; }

    /* ---- Putting finished work away (mirrors the client app) ----
       Two separate questions: hide the cards that are ticked, and hide whole
       days where nothing is left to do. Both are per-schedule localStorage,
       like every other view switch on this tab. */
    #activitiesList.hide-done-activities .activity-card.is-done { display: none; }
    #activitiesList.hide-done-days .date-group.is-all-done { display: none; }

    /* ---- Done checkmark (mirrors the client app's big checkbox) ----
       Same isDone column the farmer app writes, so both stay in step. */
    .done-check {
        width: 26px; height: 26px; border-radius: 50%; flex: 0 0 auto; padding: 0;
        border: 2px solid #b9c2cf; background: #fff; color: transparent;
        display: inline-flex; align-items: center; justify-content: center;
        cursor: pointer; transition: background .18s ease, border-color .18s ease, color .18s ease;
    }
    .done-check:hover { border-color: #198754; color: rgba(25,135,84,.55); }
    .done-check.is-checked { background: #198754; border-color: #198754; color: #fff; }
    .done-check .bx { font-size: 17px; }
    .activity-card.is-done { opacity: .78; }
    .activity-card.is-done > .d-flex h6 { text-decoration: line-through; text-decoration-thickness: 1.5px; }
    .activity-card.is-done { cursor: default; }

    /* ---- Task / Irrigation / Service mode tabs (mirrors the client app) ----
       A segmented control at the top of the Add/Edit Activity modal. Each mode
       reveals its own field: Task→type select, Irrigation→water task,
       Service→price; and hides the ones that don't apply. */
    .activity-mode-tabs { display: flex; gap: 6px; padding: 5px; background: #eef1f6; border-radius: 12px; margin-bottom: 1.1rem; }
    .activity-mode-tab { flex: 1; display: inline-flex; align-items: center; justify-content: center; gap: 8px;
        padding: 9px 12px; border: none; background: transparent; border-radius: 9px;
        font-size: 14px; font-weight: 600; color: #5b6472; cursor: pointer;
        transition: background .2s ease, color .2s ease, box-shadow .2s ease; }
    .activity-mode-tab.is-active { background: #fff; color: #1f2937; box-shadow: 0 1px 3px rgba(0,0,0,.1); }
    .activity-mode-tab:hover:not(.is-active) { color: #2d4d1c; }
    .activity-mode-tab .bx { font-size: 18px; }
    .activity-mode-tab:active { transform: scale(.98); }
    @keyframes modeFieldIn { from { opacity: 0; transform: translateY(-6px); } to { opacity: 1; transform: none; } }
    .mode-field-in { animation: modeFieldIn .28s cubic-bezier(.22,1,.36,1); }
    @media (prefers-reduced-motion: reduce) { .mode-field-in { animation: none; } }

    /* Water-task + service badges on activity cards (mirrors the client app). */
    .water-task-badge { display: inline-flex; align-items: center; gap: 4px; background: var(--wt, #2f8fd8);
        color: #fff; font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 6px; }
    .service-badge { display: inline-flex; align-items: center; gap: 4px; background: #6d5bd0;
        color: #fff; font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 6px; }
    .service-badge .item-tag-price { background: rgba(255,255,255,.22); padding: 0 5px; border-radius: 4px; }
</style>

{{-- Activity versions sub-tabs — every version is a branch of the schedule.
     The currently-active version is the one feeding $schedule->activities,
     so calendar, export, presentation, and labor summary all follow it
     automatically. Switching tabs flips isActive server-side and reloads. --}}
<div class="version-tabs d-flex align-items-center flex-wrap gap-1 mb-3 pb-2"
     style="border-bottom: 1px solid #e6e8ec;">
    <small class="text-secondary me-2 d-flex align-items-center" style="font-weight:600;">
        <i class="bx bx-git-branch me-1" style="font-size:14px;"></i> Version:
    </small>
    @foreach($activityVersions as $v)
        <button type="button"
                class="btn btn-sm version-tab-btn {{ $v->isActive ? 'btn-primary' : 'btn-outline-secondary' }}"
                data-version-id="{{ $v->id }}"
                data-version-name="{{ $v->versionName }}"
                data-version-description="{{ $v->description }}"
                data-is-original="{{ $v->isOriginal ? 1 : 0 }}"
                data-is-active="{{ $v->isActive ? 1 : 0 }}"
                title="{{ $v->isActive ? 'This is the currently-active version' : 'Switch to this version (reloads the page)' }}">
            @if($v->isOriginal)
                <i class="bx bxs-bookmark-star" style="color:#f4a82a;"></i>
            @else
                <i class="bx bx-git-branch"></i>
            @endif
            {{ $v->versionName }}
        </button>
    @endforeach
    <div class="dropdown ms-2">
        <button class="btn btn-sm btn-outline-success dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Version actions">
            <i class="bx bx-plus"></i> Version
        </button>
        <ul class="dropdown-menu dropdown-menu-end">
            <li><h6 class="dropdown-header"><i class="bx bx-git-branch"></i> Create</h6></li>
            <li><button class="dropdown-item" type="button" id="newVersionBtn">
                <i class="bx bx-copy-alt me-1"></i> Fork from current version&hellip;
                <small class="d-block text-secondary ms-4">Clone all activities into a new branch</small>
            </button></li>
            <li><button class="dropdown-item" type="button" id="newEmptyVersionBtn">
                <i class="bx bx-file-blank me-1"></i> Create empty version&hellip;
                <small class="d-block text-secondary ms-4">Start with no activities</small>
            </button></li>
            <li><hr class="dropdown-divider"></li>
            <li><h6 class="dropdown-header"><i class="bx bx-edit"></i> Current version</h6></li>
            <li><button class="dropdown-item" type="button" id="renameVersionBtn">
                <i class="bx bx-edit-alt me-1"></i> Rename / edit notes&hellip;
            </button></li>
            <li><button class="dropdown-item text-danger" type="button" id="deleteVersionBtn">
                <i class="bx bx-trash me-1"></i> Delete this version
            </button></li>
        </ul>
    </div>
    {{-- The version-wide Protocol Introduction (formerly the "View Note"
         button) has moved to the Protocol tab so it's reachable
         independent of the Activities tab. The edit modal still lives in
         the Activities partial because it's wired into Quill there;
         the new trigger button on the Protocol tab opens that same modal
         via the .global-note-trigger-btn class. --}}
    @if($activeVersion && $activeVersion->description)
        <small class="text-secondary ms-3 d-flex align-items-center" style="font-size:12px;font-style:italic;max-width:480px;">
            <i class="bx bx-info-circle me-1"></i>
            <span class="text-truncate" title="{{ $activeVersion->description }}">{{ $activeVersion->description }}</span>
        </small>
    @endif
</div>

<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="text-dark mb-1">Activities @if($activeVersion)<small class="text-secondary" style="font-size:13px;font-weight:500;">— {{ $activeVersion->versionName }}</small>@endif</h5>
        <small class="text-secondary">Manually-designed farm tasks. Each activity has a specific calendar date and the lots it applies to.</small>
    </div>
    <div class="d-flex gap-2 flex-wrap align-items-center">
        <button type="button" class="btn btn-outline-warning btn-sm" id="activityUndoBtn" title="Undo last action (Ctrl+Z) — up to 10 steps" disabled>
            <i class="bx bx-undo me-1"></i> <span id="activityUndoLabel">Undo</span> <span class="badge bg-light text-dark ms-1" id="activityUndoCount" style="display:none;font-weight:500;"></span>
        </button>
        <button type="button" class="btn btn-outline-warning btn-sm" id="activityRedoBtn" title="Redo (Ctrl+Shift+Z)" disabled>
            <i class="bx bx-redo me-1"></i> <span id="activityRedoLabel">Redo</span> <span class="badge bg-light text-dark ms-1" id="activityRedoCount" style="display:none;font-weight:500;"></span>
        </button>

        {{-- Tools — the same hamburger the client app carries, holding the
             same things in the same order. The buttons kept their ids when
             they moved in here: every handler binds by id, and none of them
             ever knew where the button was drawn. --}}
        @php $draftsCount = $schedule->drafts->count(); @endphp
        <div class="dropdown">
            <button type="button" class="btn btn-outline-secondary btn-sm dropdown-toggle" id="activityToolsBtn"
                    data-bs-toggle="dropdown" data-bs-auto-close="outside" aria-expanded="false"
                    title="Everything else this board can do">
                <i class="bx bx-menu me-1"></i> Tools
                <span class="badge bg-danger ms-1" id="activityToolsAlert" style="display:none;font-weight:600;">0</span>
            </button>
            <ul class="dropdown-menu dropdown-menu-end sm-tools-menu">
                <li><h6 class="dropdown-header">What the board shows</h6></li>
                <li><button class="dropdown-item" type="button" id="toggleDoneActivitiesBtn"
                            title="Put the ticked activities away — the client's own board is untouched">
                    <i class="bx bx-check-circle"></i> <span id="toggleDoneActivitiesLabel">Hide completed activities</span>
                    <span class="tools-state" id="toggleDoneActivitiesState">off</span>
                </button></li>
                <li><button class="dropdown-item" type="button" id="toggleDoneDaysBtn"
                            title="Hide whole days where every activity is already done">
                    <i class="bx bx-calendar-check"></i> <span id="toggleDoneDaysLabel">Hide finished days</span>
                    <span class="tools-state" id="toggleDoneDaysState">off</span>
                </button></li>
                <li><button class="dropdown-item" type="button" id="toggleEmptyDaysBtn"
                            title="Hide or show the &quot;No activities scheduled&quot; days">
                    <i class="bx bx-moon"></i> <span id="toggleEmptyDaysLabel">Hide empty days</span>
                    <span class="tools-state" id="toggleEmptyDaysState">off</span>
                </button></li>
                <li><button class="dropdown-item" type="button" id="toggleHiddenActivitiesBtn" style="display:none;"
                            title="Activities the farmer has hidden from their own board">
                    <i class="bx bx-hide"></i> <span class="cv-hidden-toggle-label">Show hidden</span>
                    <span class="tools-state">(<span id="hiddenActivityCount">0</span>)</span>
                </button></li>
                <li><button class="dropdown-item" type="button" id="activityFocusSearchBtn">
                    <i class="bx bx-search"></i> Search &amp; filters
                    <span class="tools-state" id="activityFilterState" style="display:none;">on</span>
                </button></li>

                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Move around</h6></li>
                <li><button class="dropdown-item" type="button" id="todayTomorrowBtn">
                    <i class="bx bx-calendar-event"></i> Today &amp; tomorrow
                </button></li>
                <li><button class="dropdown-item" type="button" id="collapseAllDaysBtn">
                    <i class="bx bx-chevrons-up"></i> Collapse all days
                </button></li>
                <li><button class="dropdown-item" type="button" id="expandAllDaysBtn">
                    <i class="bx bx-chevrons-down"></i> Expand all days
                </button></li>

                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Read the season</h6></li>
                <li><button class="dropdown-item" type="button" id="scheduleNoticeBtn"
                            title="What is still missing from this plan">
                    <i class="bx bx-bell"></i> Notice
                    <span class="tools-state" id="scheduleNoticeState"></span>
                </button></li>
                <li><button class="dropdown-item" type="button" id="openDraftsModalBtn"
                            title="Activities moved to drafts">
                    <i class="bx bx-archive"></i> Drafts
                    <span class="tools-state"><span class="badge bg-info text-white" id="draftsBadge" @if($draftsCount === 0) style="display:none;" @endif>{{ $draftsCount }}</span></span>
                </button></li>

                <li><hr class="dropdown-divider"></li>
                <li><h6 class="dropdown-header">Hand it on</h6></li>
                <li><button class="dropdown-item" type="button" id="quickShareBtn"
                            title="The client's own share link for this plan">
                    <i class="bx bx-share-alt"></i> Share this plan
                </button></li>
                <li><button class="dropdown-item js-tools-forward" type="button" data-forward="#openExportScheduleBtn">
                    <i class="bx bx-file-blank"></i> Export schedule
                </button></li>
                <li><button class="dropdown-item js-tools-forward" type="button" data-forward="#openWorkerPresentationBtn">
                    <i class="bx bx-book-open"></i> Worker presentation
                </button></li>
                <li><button class="dropdown-item js-tools-forward" type="button" data-forward="#openCardViewerBtn">
                    <i class="bx bx-slideshow"></i> Card viewer
                </button></li>
                <li><button class="dropdown-item js-tools-forward" type="button" data-forward="#openLaborSummaryBtn">
                    <i class="bx bx-money"></i> Labor expenses
                </button></li>
                <li><a class="dropdown-item" href="{{ route('anisenso-schedule-manager.calendar', ['scheduleId' => $schedule->id]) }}">
                    <i class="bx bx-calendar"></i> Calendar
                </a></li>
                <li><a class="dropdown-item" href="{{ route('anisenso-schedule-manager.reports', ['scheduleId' => $schedule->id]) }}">
                    <i class="bx bx-bar-chart-alt-2"></i> Reports
                </a></li>
            </ul>
        </div>

        {{-- What the board shows. An eye, because the question is about
             seeing rather than about the plan. --}}
        <button type="button" class="btn btn-outline-secondary btn-sm" id="viewFilterBtn"
                title="What the board shows" aria-label="What the board shows">
            <i class="bx bx-show"></i>
        </button>
        {{-- Every filter behind one button, with a count of the ones that
             are on, so a board narrowed yesterday cannot look like an empty
             season today. --}}
        <button type="button" class="btn btn-outline-secondary btn-sm position-relative" id="searchToolbarBtn"
                title="Search &amp; filter the board">
            <i class="bx bx-search me-1"></i> Search
            <span id="toolbarFilterCount"
                  class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-primary"
                  style="display:none; font-size:9.5px;">0</span>
        </button>
        {{-- The mirror: the whole plan held up to be read, and nothing else.
             Every day, every activity, the hidden ones included and the done
             ones marked — with no button on any of it. The board you work on
             has to be re-filtered and un-hidden to answer "is that done?",
             and then put back; this answers it without touching the board. --}}
        <button type="button" class="btn btn-outline-secondary btn-sm" id="mirrorBtn"
                title="Mirror — read the whole plan">
            <i class="bx bx-book-open me-1"></i> Mirror
        </button>
        {{-- A long session accumulates what a long session accumulates.
             Rebuilding the page hands it back for the cost of one read. --}}
        <button type="button" class="btn btn-outline-secondary btn-sm" id="boardRefreshBtn"
                title="Refresh — rebuild this board">
            <i class="bx bx-refresh"></i>
        </button>
        <button type="button" class="btn btn-primary btn-sm" id="addActivityBtn">
            <i class="bx bx-plus me-1"></i> Add Activity
        </button>
    </div>
</div>

<div class="activity-timeline" id="activitiesList">
    @php
        // Eager-load workers + lots once for the server-rendered cards.
        $schedule->load(['activities.workers', 'activities.lots']);
        // Sort by date, then manual sequenceOrder (drag-to-reorder), then by
        // lot signature so same-lot activities cluster when no manual order
        // is set, then by id for stability.
        $sortedActivities = $schedule->activities->sortBy(function ($a) {
            $date = $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate)->format('Y-m-d') : 'ZZZZ-12-31';
            $seq = str_pad((string) (int) $a->sequenceOrder, 10, '0', STR_PAD_LEFT);
            $lotSig = $a->lots->pluck('id')->sort()->values()->implode(',');
            return $date . '|' . $seq . '|' . $lotSig . '|' . str_pad((string) $a->id, 10, '0', STR_PAD_LEFT);
        })->values();
        // Group by Y-m-d so we can render a single date header per group.
        $byDate = $sortedActivities->groupBy(function ($a) {
            return $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate)->format('Y-m-d') : '__no-date__';
        });

        // Build a unified chronological timeline that interleaves date-groups
        // with "no activities scheduled" rest-day markers. A day counts as
        // covered if it falls inside ANY activity's [start, end] range — so a
        // multi-day activity does not produce a rest marker for its inner
        // days, only days with truly no work do.
        $coveredDays = [];
        $firstDate = null;
        $lastDate = null;
        foreach ($sortedActivities as $a) {
            if (!$a->targetDate) continue;
            $s = \Illuminate\Support\Carbon::parse($a->targetDate);
            $e = $a->targetEndDate ? \Illuminate\Support\Carbon::parse($a->targetEndDate) : $s->copy();
            for ($d = $s->copy(); $d->lte($e); $d->addDay()) {
                $coveredDays[$d->format('Y-m-d')] = true;
            }
            if (!$firstDate || $s->lt($firstDate)) $firstDate = $s->copy();
            if (!$lastDate || $e->gt($lastDate)) $lastDate = $e->copy();
        }
        $timeline = [];
        $colorCursor = 0;
        if ($firstDate && $lastDate) {
            for ($d = $firstDate->copy(); $d->lte($lastDate); $d->addDay()) {
                $key = $d->format('Y-m-d');
                if (isset($byDate[$key])) {
                    $timeline[] = ['type' => 'group', 'date' => $key, 'color' => $colorCursor, 'carbon' => $d->copy()];
                    $colorCursor = ($colorCursor + 1) % 8;
                } elseif (!isset($coveredDays[$key])) {
                    $timeline[] = ['type' => 'rest', 'date' => $key, 'carbon' => $d->copy()];
                }
            }
        }
        if (isset($byDate['__no-date__'])) {
            $timeline[] = ['type' => 'group', 'date' => '__no-date__', 'color' => 0, 'carbon' => null];
        }

        // Splice progress markers into the timeline immediately AFTER their
        // markerDate row. The marker line then visually separates "what you
        // already worked through" from "where you'll pick up next." If the
        // markerDate isn't in the timeline at all (e.g. marker dropped on a
        // date that no longer has activities AND falls outside the covered
        // range), we still surface it on its own row so the user can find
        // and edit / clear it.
        if ($markersByDate->count()) {
            $expanded = [];
            $seenMarkerDates = [];
            foreach ($timeline as $row) {
                $expanded[] = $row;
                $rowDate = $row['date'];
                if ($rowDate !== '__no-date__' && isset($markersByDate[$rowDate])) {
                    $expanded[] = [
                        'type' => 'marker',
                        'date' => $rowDate,
                        'carbon' => $row['carbon'] ?? \Illuminate\Support\Carbon::parse($rowDate),
                        'marker' => $markersByDate[$rowDate],
                    ];
                    $seenMarkerDates[$rowDate] = true;
                }
            }
            // Orphans — markers whose date wasn't on the timeline. Append at the end.
            foreach ($markersByDate as $dateKey => $marker) {
                if (!isset($seenMarkerDates[$dateKey])) {
                    $expanded[] = [
                        'type' => 'marker',
                        'date' => $dateKey,
                        'carbon' => \Illuminate\Support\Carbon::parse($dateKey),
                        'marker' => $marker,
                    ];
                }
            }
            $timeline = $expanded;
        }
    @endphp
    @if($sortedActivities->count() === 0)
        <div id="activitiesEmpty">
            <div class="text-center text-secondary py-5">
                <i class="bx bx-task" style="font-size:2.5rem;"></i>
                <p class="text-dark mt-2 mb-0">No activities defined yet.</p>
                <small>Click <strong>Add Activity</strong> to define your first step.</small>
            </div>
        </div>
    @else
    @foreach($timeline as $item)
        @if($item['type'] === 'rest')
            <div class="rest-day-marker" data-date="{{ $item['date'] }}">
                <i class="bx bx-moon rest-day-icon"></i>
                <div class="rest-day-text">
                    <span class="rest-day-date">{{ $item['carbon']->format('l, F j, Y') }}</span>
                    <span class="rest-day-tag">No activities scheduled</span>
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-primary rest-day-add-btn"
                        data-date="{{ $item['date'] }}"
                        title="Add a new activity to this date">
                    <i class="bx bx-plus"></i> Add Activity
                </button>
            </div>
        @elseif($item['type'] === 'marker')
            @php $marker = $item['marker']; @endphp
            <div class="progress-marker {{ $marker->noteContent ? 'has-note' : '' }}"
                 data-marker-id="{{ $marker->id }}"
                 data-date="{{ $item['date'] }}"
                 data-note="{{ e($marker->noteContent) }}">
                <div class="progress-marker-line">
                    <span class="progress-marker-bookmark" draggable="true"
                          title="Drag onto another day to move this marker">
                        <i class="bx bxs-bookmark"></i>
                        <span class="progress-marker-label">Resume here</span>
                        <span class="progress-marker-date">— {{ $item['carbon']->format('M j, Y') }}</span>
                    </span>
                    <div class="progress-marker-actions">
                        <button type="button"
                                class="btn btn-sm btn-outline-secondary progress-marker-edit-btn"
                                data-marker-id="{{ $marker->id }}"
                                data-date="{{ $item['date'] }}"
                                data-note="{{ e($marker->noteContent) }}"
                                title="Edit the note on this resume-here marker">
                            <i class="bx bx-edit-alt"></i>
                        </button>
                        <button type="button"
                                class="btn btn-sm btn-outline-danger progress-marker-delete-btn"
                                data-marker-id="{{ $marker->id }}"
                                data-date="{{ $item['date'] }}"
                                title="Remove this resume-here marker">
                            <i class="bx bx-trash"></i>
                        </button>
                    </div>
                </div>
                @if($marker->noteContent)
                    <div class="progress-marker-note">
                        <i class="bx bxs-note progress-marker-note-icon"></i>
                        <div class="progress-marker-note-text">{!! nl2br(e($marker->noteContent)) !!}</div>
                    </div>
                @endif
            </div>
        @else
        @php
            $dateKey = $item['date'];
            $activitiesForDate = $byDate->get($dateKey);
            $dateCarbon = ($dateKey !== '__no-date__') ? \Illuminate\Support\Carbon::parse($dateKey) : null;
            $colorIndex = $item['color'];
        @endphp
        @php
            // Find the latest targetEndDate across activities in this group.
            // When any activity extends past its start date, the header
            // shows "→ Jun 15" so the user sees at a glance that some
            // activities here aren't single-day. The card-level range badge
            // still shows the per-activity range; this header badge is the
            // group-level summary.
            $latestEndCarbon = null;
            if ($dateCarbon) {
                foreach ($activitiesForDate as $_act) {
                    $_e = $_act->targetEndDate ? \Illuminate\Support\Carbon::parse($_act->targetEndDate) : null;
                    if ($_e && $_e->greaterThan($dateCarbon)) {
                        if (!$latestEndCarbon || $_e->greaterThan($latestEndCarbon)) {
                            $latestEndCarbon = $_e->copy();
                        }
                    }
                }
            }
            $groupSpanDays = $latestEndCarbon ? ($dateCarbon->diffInDays($latestEndCarbon) + 1) : 0;
            // When every activity on this date is hidden, the date-group CSS
            // collapses to display:none — without a substitute the date would
            // disappear from the timeline entirely. Render a rest-day-marker
            // twin (.rest-day-substitute) so the user sees "No activities
            // scheduled" instead of a gap, mirroring genuinely empty dates.
            $allHidden = $dateCarbon
                && $activitiesForDate->isNotEmpty()
                && $activitiesForDate->every(fn ($_a) => (int) ($_a->isHidden ?? 0) === 1);
        @endphp
        @if($allHidden)
            <div class="rest-day-marker rest-day-substitute" data-date="{{ $dateKey }}">
                <i class="bx bx-moon rest-day-icon"></i>
                <div class="rest-day-text">
                    <span class="rest-day-date">{{ $dateCarbon->format('l, F j, Y') }}</span>
                    <span class="rest-day-tag">No activities scheduled</span>
                </div>
                <button type="button"
                        class="btn btn-sm btn-outline-primary rest-day-add-btn"
                        data-date="{{ $dateKey }}"
                        title="Add a new activity to this date">
                    <i class="bx bx-plus"></i> Add Activity
                </button>
            </div>
        @endif
        <div class="date-group date-color-{{ $colorIndex }}" data-date="{{ $dateKey }}">
            <div class="date-header">
                <button type="button"
                        class="date-header-edit-btn date-collapse-btn"
                        title="Collapse / expand this day's activities">
                    <i class="bx bx-chevron-down"></i>
                </button>
                @if($dateCarbon)
                    <i class="bx bx-calendar"></i>
                    <span class="date-header-day">{{ $dateCarbon->format('D') }}</span>
                    <span class="date-header-date">{{ $dateCarbon->format('M j, Y') }}</span>
                    @if($latestEndCarbon)
                        <span class="date-header-range" title="At least one activity in this group extends through {{ $latestEndCarbon->format('M j, Y') }} ({{ $groupSpanDays }} days total)">
                            <i class="bx bx-right-arrow-alt"></i>
                            {{ $latestEndCarbon->format('M j') }}@if($latestEndCarbon->year !== $dateCarbon->year), {{ $latestEndCarbon->year }}@endif
                            <span class="date-header-range-days">({{ $groupSpanDays }}d)</span>
                        </span>
                    @endif
                @else
                    <i class="bx bx-error-circle"></i>
                    <span class="date-header-date">No date</span>
                @endif
                <span class="date-header-count">{{ $activitiesForDate->count() }} {{ \Illuminate\Support\Str::plural('activity', $activitiesForDate->count()) }}</span>
                @if($dateKey !== '__no-date__')
                    {{-- Quick-add: open the Activity modal with this date pre-filled,
                         same handler the rest-day "Add Activity" button uses. --}}
                    <button type="button"
                            class="date-header-edit-btn group-add-activity-btn"
                            data-date="{{ $dateKey }}"
                            title="Add a new activity to this date">
                        <i class="bx bx-plus"></i>
                    </button>
                    @php $existingNote = $dateNotesByDate->get($dateKey); @endphp
                    <button type="button"
                            class="date-header-edit-btn date-note-btn {{ $existingNote ? 'has-note' : '' }}"
                            data-date="{{ $dateKey }}"
                            data-existing="{{ $existingNote ? e($existingNote->noteContent) : '' }}"
                            title="{{ $existingNote ? 'Edit the note for this date' : 'Add a note for this date' }}">
                        <i class="bx {{ $existingNote ? 'bxs-note' : 'bx-note' }}"></i>
                    </button>
                    @php $existingMarker = $markersByDate->get($dateKey); @endphp
                    <button type="button"
                            class="date-header-edit-btn date-marker-btn {{ $existingMarker ? 'has-marker' : '' }}"
                            data-date="{{ $dateKey }}"
                            data-marker-id="{{ $existingMarker?->id }}"
                            data-existing="{{ $existingMarker ? e($existingMarker->noteContent) : '' }}"
                            title="{{ $existingMarker ? 'Edit the resume-here marker on this date' : 'Drop a "resume here" marker after this date' }}">
                        <i class="bx {{ $existingMarker ? 'bxs-bookmark' : 'bx-bookmark' }}"></i>
                    </button>
                    <button type="button"
                            class="date-header-edit-btn change-group-date-btn"
                            data-date="{{ $dateKey }}"
                            title="Change date for all activities in this group">
                        <i class="bx bx-calendar-edit"></i>
                    </button>
                    <button type="button"
                            class="date-header-edit-btn date-header-delete-btn delete-group-date-btn"
                            data-date="{{ $dateKey }}"
                            title="Delete every activity in this group">
                        <i class="bx bx-trash"></i>
                    </button>
                @endif
            </div>
            @if($dateKey !== '__no-date__')
                @php $noteRow = $dateNotesByDate->get($dateKey); @endphp
                <div class="date-note-block" data-date="{{ $dateKey }}" @if(!$noteRow) style="display:none;" @endif>
                    <div class="date-note-inner">
                        <i class="bx bxs-note date-note-icon" draggable="true"
                           title="Drag onto another day to move this note"></i>
                        <div class="date-note-text">{!! $noteRow ? ($noteRow->noteContent !== strip_tags($noteRow->noteContent) ? $noteRow->noteContent : nl2br(e($noteRow->noteContent))) : '' !!}</div>
                    </div>
                </div>
            @endif
            <div class="date-activities">
                @foreach($activitiesForDate as $a)
                    @php
                        $activityLots = $a->lots;
                        $lotSig = $activityLots->pluck('id')->sort()->values()->implode(',');
                        $endDateCarbon = $a->targetEndDate ? \Illuminate\Support\Carbon::parse($a->targetEndDate) : null;
                        $startDateCarbon = $a->targetDate ? \Illuminate\Support\Carbon::parse($a->targetDate) : null;
                        $isRange = $endDateCarbon && $startDateCarbon && $endDateCarbon->greaterThan($startDateCarbon);
                        $rangeDays = $isRange ? ($startDateCarbon->diffInDays($endDateCarbon) + 1) : 0;
                        $endDateStr = $endDateCarbon ? $endDateCarbon->format('Y-m-d') : '';
                    @endphp
                    <div class="activity-card{{ $a->isHidden ? ' is-hidden' : '' }}{{ $a->isDone ? ' is-done' : '' }}" draggable="{{ $a->isDone ? 'false' : 'true' }}" data-id="{{ $a->id }}" data-target-date="{{ $dateKey === '__no-date__' ? '' : $dateKey }}" data-target-end-date="{{ $endDateStr }}" data-lot-signature="{{ $lotSig }}" data-sequence-order="{{ (int) $a->sequenceOrder }}" data-is-day-zero="{{ $a->isDayZero ? 1 : 0 }}" data-is-transplant="{{ $a->isTransplant ? 1 : 0 }}" data-activity-type="{{ $a->activityType ?: '' }}" data-is-hidden="{{ $a->isHidden ? 1 : 0 }}" data-is-done="{{ $a->isDone ? 1 : 0 }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                            <div class="flex-grow-1" style="min-width:0;">
                                <h6 class="text-dark mb-1">
                                    {{ $a->activityTitle }}
                                    @if($a->activityType === 'irrigation')
                                        @php $wtm = $a->waterTaskMeta(); @endphp
                                        <span class="badge ms-1 water-task-badge" style="--wt:{{ $wtm['color'] }}" title="Water task">
                                            <i class="bx bxs-droplet"></i> {{ $wtm['label'] }}
                                        </span>
                                    @elseif($a->activityType === 'service')
                                        <span class="badge ms-1 service-badge" title="Hired service">
                                            <i class="bx bx-wrench"></i> Service
                                            @if($a->servicePrice !== null)<span class="item-tag-price">₱{{ number_format((float) $a->servicePrice, 2) }}</span>@endif
                                        </span>
                                    @elseif($a->activityType && isset(\App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a->activityType]))
                                        <span class="badge ms-1 activity-type-badge"
                                              style="background:#e2efd4; color:#2d4d1c; font-weight:600; font-size:11px;"
                                              title="Activity type">
                                            {{ \App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a->activityType] }}
                                        </span>
                                    @endif
                                    @if($a->isDayZero)
                                        <span class="badge ms-1 day-zero-badge"
                                              style="background:#ff9800; color:#3d2600; font-weight:600; font-size:11px;"
                                              title="This activity is the {{ $schedule->dayType }} 0 anchor — its date becomes {{ $schedule->dayType }} 0 for every lot it covers.">
                                            <i class="bx bxs-star"></i> {{ $schedule->dayType }} 0
                                        </span>
                                    @endif
                                    @if($a->isTransplant)
                                        <span class="badge ms-1 transplant-badge"
                                              style="background:#0ca678; color:#fff; font-weight:600; font-size:11px;"
                                              title="Transplant — its date becomes DAT 0 for every lot it covers. Later activities on those lots count in DAT.">
                                            <i class="bx bx-transfer-alt"></i> &rarr; DAT 0
                                        </span>
                                    @endif
                                    @if($isRange)
                                        <span class="badge bg-light text-dark ms-1" style="font-weight:500;font-size:11px;" title="Multi-day range">
                                            <i class="bx bx-right-arrow-alt"></i> {{ $endDateCarbon->format('M j') }}
                                            <span class="text-secondary">({{ $rangeDays }}d)</span>
                                        </span>
                                    @endif
                                </h6>
                                <div class="activity-card-lots">
                                    @if($activityLots->count())
                                        <i class="bx bx-map-pin"></i>
                                        @foreach($activityLots as $lot)
                                            @php
                                                // Phase-aware day-number suffix from the lot's MANUAL anchors
                                                // (first paint). On load, recomputeLotDayZero() re-derives this
                                                // client-side, folding in activity-flagged DAS 0 / DAT 0 anchors.
                                                // Once the date reaches the lot's transplant date it counts in DAT.
                                                $dasSuffix = '';
                                                if ($a->targetDate) {
                                                    $dt = \Illuminate\Support\Carbon::parse($a->targetDate);
                                                    if ($lot->transplantDate) {
                                                        $tp = \Illuminate\Support\Carbon::parse($lot->transplantDate);
                                                        if ($dt->gte($tp)) {
                                                            $delta = (int) $tp->diffInDays($dt, false);
                                                            // At the transplant pivot (DAT 0), also show the DAS it converts from.
                                                            if ($delta === 0 && $lot->dayZeroDate) {
                                                                $d0 = \Illuminate\Support\Carbon::parse($lot->dayZeroDate);
                                                                $dasDelta = (int) $d0->diffInDays($dt, false);
                                                                $dasSuffix = ' · ' . $schedule->dayType . ($dasDelta > 0 ? '+' : '') . $dasDelta . ' → DAT0';
                                                            } else {
                                                                $dasSuffix = ' · DAT' . ($delta > 0 ? '+' : '') . $delta;
                                                            }
                                                        }
                                                    }
                                                    if ($dasSuffix === '' && $lot->dayZeroDate) {
                                                        $d0 = \Illuminate\Support\Carbon::parse($lot->dayZeroDate);
                                                        $delta = (int) $d0->diffInDays($dt, false);
                                                        $dasSuffix = ' · ' . $schedule->dayType . ($delta > 0 ? '+' : '') . $delta;
                                                    }
                                                }
                                            @endphp
                                            <span class="item-tag"
                                                  style="background:#eef0fb; color:#3a4699;"
                                                  data-lot-id="{{ $lot->id }}"
                                                  data-lot-name="{{ $lot->lotName }}">{{ $lot->lotName }}@if(!empty($lot->variety)) <small style="opacity:.85;">· {{ $lot->variety }}</small>@endif{{ $dasSuffix }}</span>
                                        @endforeach
                                    @else
                                        {{-- N/A activity: explicit chip explaining the
                                             activity isn't tied to a specific lot. --}}
                                        <span class="item-tag activity-na-tag" title="Activity applies generally — not tied to any specific lot">
                                            <i class="bx bx-globe"></i> N/A — Not lot-specific
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                {{-- Done checkmark — same isDone flag as the client app's
                                     big checkbox. Done cards dim + strike through and
                                     can't be dragged; admin editing stays allowed. --}}
                                <button type="button" class="done-check{{ $a->isDone ? ' is-checked' : '' }}"
                                        data-id="{{ $a->id }}"
                                        aria-pressed="{{ $a->isDone ? 'true' : 'false' }}"
                                        title="{{ $a->isDone ? 'Mark as not done' : 'Mark this activity as done' }}">
                                    <i class="bx bx-check"></i>
                                </button>
                                <span class="sm-pill priority-{{ $a->priority }}">{{ ucfirst($a->priority) }}</span>
                                {{-- Visibility switch — toggles isHidden. When OFF
                                     (switch unchecked), the activity is excluded
                                     from the worker presentation, card viewer,
                                     and export. The card itself stays on the
                                     timeline (dimmed via .is-hidden) so the user
                                     knows the activity exists. --}}
                                <label class="hide-activity-switch form-check form-switch m-0" title="Toggle visibility in worker presentation, card viewer, and export">
                                    <input type="checkbox" class="form-check-input hide-activity-toggle"
                                           data-id="{{ $a->id }}"
                                           {{ $a->isHidden ? '' : 'checked' }}
                                           aria-label="Show or hide this activity in presentations">
                                </label>
                                @if($a->isHidden)
                                    <span class="badge bg-secondary text-white hide-activity-tag" style="font-weight:500;font-size:11px;">
                                        <i class="bx bx-hide"></i> Hidden
                                    </span>
                                @endif
                                <button class="btn btn-sm btn-outline-primary edit-activity-btn" data-id="{{ $a->id }}" title="Edit"><i class="bx bx-edit-alt"></i></button>
                                <button class="btn btn-sm btn-outline-secondary duplicate-activity-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Duplicate"><i class="bx bx-copy"></i></button>
                                <button class="btn btn-sm btn-outline-info to-draft-activity-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Move to drafts (hide without deleting)"><i class="bx bx-archive-in"></i></button>
                                <button class="btn btn-sm btn-outline-danger delete-activity-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Delete"><i class="bx bx-trash"></i></button>
                            </div>
                        </div>
                        @if($a->description)
                            <div class="text-dark mt-2 mb-2 activity-description-content" style="font-size:13px;">{!! $a->description !!}</div>
                        @endif
                        @if($a->imagePath)
                            <div class="activity-card-image">
                                <img src="{{ $a->imageUrl() }}" alt="Activity image" loading="lazy">
                            </div>
                        @endif
                        <div class="step-meta mt-1">
                            @php
                                $timeLabel = ['half' => 'Half day', 'whole' => 'Whole day', 'n/a' => 'N/A'][$a->timeRequired] ?? ucfirst($a->timeRequired);
                            @endphp
                            <i class="bx bx-time"></i> {{ $timeLabel }}
                        </div>
                        <div class="mt-2">
                            @if($a->workers->count())
                                <small class="text-secondary me-1"><i class="bx bx-user"></i> Workers:</small>
                                @foreach($a->workers as $worker)
                                    <span class="item-tag" style="background:#fef3e8; color:#a66200;">{{ $worker->workerName }}</span>
                                @endforeach
                            @else
                                <small class="text-secondary"><i class="bx bx-user-x"></i> No workers assigned</small>
                            @endif
                        </div>
                        @if($a->items->count())
                            <div class="mt-2">
                                @foreach($a->items as $it)
                                    @php
                                        $qtyTrim = rtrim(rtrim((string) $it->quantity, '0'), '.');
                                        $unit = $it->unitOfMeasure ?: ($it->material->unitOfMeasure ?? '');
                                    @endphp
                                    @if($it->itemType === 'material' && $it->material)
                                        <span class="item-tag">{{ $it->material->materialName }} ×{{ $qtyTrim }}@if($unit) {{ $unit }}@endif</span>
                                    @elseif($it->itemType === 'service' && $it->service)
                                        <span class="item-tag service">{{ $it->service->serviceName }}@if($qtyTrim !== '1' || $unit) ×{{ $qtyTrim }}@if($unit) {{ $unit }}@endif @endif</span>
                                    @elseif($it->itemName)
                                        {{-- Free-form item created in the client app (name + price). --}}
                                        @php $priceTxt = $it->unitPrice !== null ? ' @ ₱'.number_format((float) $it->unitPrice, 2) : ''; @endphp
                                        <span class="item-tag">{{ $it->itemName }}@if($it->quantity !== null) ×{{ $qtyTrim }}@if($unit) {{ $unit }}@endif @endif{{ $priceTxt }}</span>
                                    @endif
                                @endforeach
                            </div>
                        @else
                            <div class="mt-2"><small class="text-secondary"><i class="bx bx-minus-circle"></i> No materials or services</small></div>
                        @endif
                    </div>
                @endforeach
            </div>
        </div>
        @endif
    @endforeach
    @endif
</div>

{{-- Global activity note modal: edit/clear the version-wide commentary
     that renders at the top of the activity timeline + on presentation
     + on export schedule. --}}
<div class="modal fade" id="globalActivityNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark">
                    <i class="bxs-message-detail bx me-2"></i>
                    <span id="globalActivityNoteModalTitle">Note for this version</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-2">
                    A free-form note that applies to the whole activity timeline of the
                    <strong id="globalActivityNoteVersionName" class="text-primary">—</strong> version.
                </p>
                <small class="text-secondary d-block mb-2">
                    This note renders at the top of the Activities section in the Worker Presentation
                    and Export Schedule. Each version (fork) carries its own copy.
                    Supports formatting — headings, bold, lists, links, tables.
                </small>
                {{-- Quill rich-text editor for the version-wide note. --}}
                <div class="sm-quill-wrap" id="globalActivityNoteWrap">
                    <div class="sm-quill-host" id="globalActivityNoteContent"></div>
                </div>
                <input type="hidden" id="globalActivityNoteVersionId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="globalActivityNoteClearBtn" style="display:none;">
                    <i class="bx bx-trash me-1"></i> Clear Note
                </button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="globalActivityNoteSaveBtn">
                    <i class="bx bx-save me-1"></i> Save Note
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Date-note modal: add/edit/clear the per-date commentary. The note is
     scoped to the active version, so each fork can carry its own notes. --}}
<div class="modal fade" id="dateNoteModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark">
                    <i class="bx bxs-note me-2"></i>
                    <span id="dateNoteModalTitle">Note for this date</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-2">
                    Add a note explaining what will happen on
                    <strong id="dateNoteModalDate" class="text-primary">—</strong>.
                </p>
                <small class="text-secondary d-block mb-2">
                    This note will appear in the Worker Presentation and the Export Schedule
                    under this date's heading. Line breaks are preserved.
                </small>
                <textarea class="form-control" id="dateNoteContent" rows="6" maxlength="20000"
                          placeholder="e.g. Heavy spraying day — confirm tank mix the day before. All workers report at 6am."></textarea>
                <input type="hidden" id="dateNoteDate">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="dateNoteClearBtn" style="display:none;">
                    <i class="bx bx-trash me-1"></i> Clear Note
                </button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="dateNoteSaveBtn">
                    <i class="bx bx-save me-1"></i> Save Note
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Progress-marker modal: drop / edit / clear a "resume here" bookmark
     on a specific date. The marker renders as a horizontal line in the
     timeline so the user can find where they left off yesterday. --}}
<div class="modal fade" id="progressMarkerModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark">
                    <i class="bx bxs-bookmark me-2"></i>
                    <span id="progressMarkerModalTitle">Resume-here marker</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-2">
                    Drop a horizontal line after
                    <strong id="progressMarkerModalDate" class="text-primary">—</strong>
                    so you can find where you stopped working yesterday.
                </p>
                <small class="text-secondary d-block mb-2">
                    Optional note — write a short reminder for what to pick up next ("continue with weeding Apartado 2", "review fertilizer plan", etc.). Line breaks are preserved.
                </small>
                <textarea class="form-control" id="progressMarkerNote" rows="5" maxlength="5000"
                          placeholder="e.g. Continue here tomorrow — weeding Apartado 2 not yet planned."></textarea>
                <input type="hidden" id="progressMarkerDate">
                <input type="hidden" id="progressMarkerId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-danger me-auto" id="progressMarkerClearBtn" style="display:none;">
                    <i class="bx bx-trash me-1"></i> Remove Marker
                </button>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="progressMarkerSaveBtn">
                    <i class="bx bx-save me-1"></i> Save Marker
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Change-group-date modal: bulk-move every activity in a date section --}}
<div class="modal fade" id="changeGroupDateModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-calendar-edit me-2"></i>Move all activities to a new date</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-2">
                    Move all <strong id="changeGroupDateCount">0</strong> activities currently on
                    <strong id="changeGroupDateCurrent" class="text-primary">—</strong>
                    to a new date.
                </p>
                <div class="mb-2">
                    <label class="form-label text-dark">New date <span class="text-danger">*</span></label>
                    <input type="date" class="form-control" id="changeGroupDateNew">
                </div>
                <small class="text-secondary">
                    <i class="bx bx-info-circle me-1"></i>
                    Multi-day activities keep their duration — only the start date shifts. Worker assignments and items are untouched.
                </small>
                <input type="hidden" id="changeGroupDateOld">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="confirmChangeGroupDateBtn">
                    <i class="bx bx-calendar-check me-1"></i> Move Activities
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Export options — chosen before the preview renders, mirroring the
     Worker Presentation options modal. The picks become query params on the
     export URL; the controller does the filtering so every derived figure
     (totals, spans, date groups) reflects the same filtered set. --}}
<div class="modal fade" id="exportScheduleOptionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-file-blank me-2"></i>Export Schedule Options</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="expActivitiesOnly">
                    <label class="form-check-label text-dark" for="expActivitiesOnly">
                        <strong>Show only the activities</strong>
                        <div class="text-secondary" style="font-size: 12.5px;">
                            Drop the critical rules, protocol introduction, attachments, summary,
                            lots, workers, and irrigation sections — leaving just the activity timeline.
                        </div>
                    </label>
                </div>

                <hr class="my-3">

                <div class="mb-3">
                    <label class="form-label text-dark mb-1" style="font-weight:600;font-size:13px;">
                        <i class="bx bx-calendar-event me-1"></i> Start from date
                    </label>
                    <div class="input-group">
                        <input type="date" class="form-control" id="expStartDate">
                        <button type="button" class="btn btn-outline-secondary" id="expStartDateClear"
                                title="Clear — start from the schedule's first activity">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                    <small class="text-secondary d-block mt-1" style="font-size: 12.5px;">
                        Empty = start at the schedule's first activity. A multi-day activity that began
                        earlier but is still running on this date is kept, so in-progress work isn't lost.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark mb-1" style="font-weight:600;font-size:13px;">
                        <i class="bx bx-trending-up me-1"></i>
                        Up to <span class="day-type-label">{{ $schedule->dayType }}</span>
                    </label>
                    <div class="input-group">
                        <input type="number" step="1" class="form-control" id="expDasMax"
                               placeholder="e.g. 45 — leave empty for the whole season">
                        <button type="button" class="btn btn-outline-secondary" id="expDasMaxClear"
                                title="Clear — run to the end of the schedule">
                            <i class="bx bx-x"></i>
                        </button>
                    </div>
                    <small class="text-secondary d-block mt-1" style="font-size: 12.5px;">
                        Ends the document at this day number. An activity is measured by its
                        <strong>earliest <span class="day-type-label">{{ $schedule->dayType }}</span></strong>
                        across the lots being shown — the same rule the Labor Summary uses.
                        Activities with no Day 0 anchor have no
                        <span class="day-type-label">{{ $schedule->dayType }}</span> to compare, so they
                        drop out when this is set (that includes general <strong>N/A</strong> activities).
                    </small>
                </div>

                <hr class="my-3">

                <div class="mb-1">
                    <label class="form-label text-dark mb-2" style="font-weight:600;font-size:13px;">
                        <i class="bx bx-hide me-1"></i> Hide from the document
                    </label>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="expHideWorkers">
                        <label class="form-check-label text-dark" for="expHideWorkers">
                            <strong>Workers</strong>
                            <div class="text-secondary" style="font-size: 12.5px;">
                                Drop worker names from every activity, plus the Workers roster and headcount.
                            </div>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="expHideNotes">
                        <label class="form-check-label text-dark" for="expHideNotes">
                            <strong>Notes</strong>
                            <div class="text-secondary" style="font-size: 12.5px;">
                                Drop the per-date notes and the version-wide note. Dates that carried
                                only a note disappear with them.
                            </div>
                        </label>
                    </div>
                    <div class="form-check mb-2">
                        <input class="form-check-input" type="checkbox" id="expHideCriticality">
                        <label class="form-check-label text-dark" for="expHideCriticality">
                            <strong>Criticality</strong>
                            <div class="text-secondary" style="font-size: 12.5px;">
                                Drop the Critical / High / Medium / Low pill from each activity.
                                The <em>Critical Rules</em> section is separate — untick
                                <strong>Show only the activities</strong> above to keep it.
                            </div>
                        </label>
                    </div>
                </div>

                <hr class="my-3">

                <div class="mb-1">
                    <label class="form-label text-dark mb-1" style="font-weight:600;font-size:13px;">
                        <i class="bx bx-map-pin me-1"></i> Lots to include
                    </label>
                    <small class="text-secondary d-block mb-2" style="font-size: 12.5px;">
                        All lots are included by default — <strong>uncheck</strong> any you don't want.
                    </small>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" class="btn btn-link btn-sm p-0" id="expLotsSelectAllBtn">Select all</button>
                        <span class="text-secondary">·</span>
                        <button type="button" class="btn btn-link btn-sm p-0" id="expLotsClearBtn">Clear</button>
                    </div>
                    <div class="d-flex flex-wrap gap-2 p-2 rounded" id="expLotsList"
                         style="border:1px solid #e6e8ec; background:#fafbfc; max-height: 180px; overflow-y: auto;">
                        @foreach($schedule->lots as $lot)
                            <div class="form-check form-check-inline m-0" style="min-width: 32%;">
                                <input class="form-check-input exp-lot-pick" type="checkbox"
                                       id="expLot{{ $lot->id }}" value="{{ $lot->id }}" checked>
                                <label class="form-check-label text-dark" for="expLot{{ $lot->id }}" style="font-size: 13px;">
                                    {{ $lot->lotName }}@if(!empty($lot->variety))<small class="text-secondary"> · {{ $lot->variety }}</small>@endif
                                </label>
                            </div>
                        @endforeach
                        @if($schedule->lots->count() === 0)
                            <small class="text-secondary"><i class="bx bx-info-circle me-1"></i>No lots defined on this schedule.</small>
                        @endif
                    </div>
                    <div class="form-check mt-2">
                        <input class="form-check-input" type="checkbox" id="expIncludeNa" checked>
                        <label class="form-check-label text-dark" for="expIncludeNa" style="font-size: 13px;">
                            Include general activities
                            <span class="text-secondary">— those marked <strong>N/A</strong>, which aren't tied to any lot</span>
                        </label>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="expGenerateBtn">
                    <i class="bx bx-show me-1"></i> Generate Preview
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Export schedule modal: preview + download PDF + copy to clipboard --}}
<div class="modal fade" id="exportScheduleModal" tabindex="-1" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-fullscreen-lg-down" style="max-width: 1000px;">
        <div class="modal-content" style="height: 90vh;">
            <div class="modal-header">
                <h5 class="modal-title text-dark me-4"><i class="bx bx-file-blank me-2"></i>Schedule Preview — {{ $schedule->title }}</h5>
                <div class="d-flex gap-2 align-items-center flex-shrink-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="copyScheduleTextBtn" title="Copy plain-text version to clipboard">
                        <i class="bx bx-copy me-1"></i> Copy as Text
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="downloadSchedulePdfBtn" title="Use browser's Save as PDF dialog">
                        <i class="bx bx-download me-1"></i> Download PDF
                    </button>
                    <button type="button" class="btn-close ms-1" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body p-0" style="overflow: hidden; background: #e9ecef;">
                <iframe id="exportScheduleFrame" src="about:blank" style="width:100%; height:100%; border:0; background:#fff;"></iframe>
            </div>
            <div class="modal-footer py-2">
                <small class="text-secondary me-auto"><i class="bx bx-info-circle me-1"></i> "Download PDF" opens your browser's print dialog — pick "Save as PDF" as the destination.</small>
                <button type="button" class="btn btn-light btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Labor expenses summary modal --}}
<div class="modal fade" id="laborSummaryModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl" style="max-width: 1320px;">
        <div class="modal-content" style="height: 90vh;">
            <div class="modal-header">
                <h5 class="modal-title text-dark me-4"><i class="bx bx-money me-2"></i>Labor Expense Summary</h5>
                <div class="d-flex gap-2 align-items-center flex-shrink-0">
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="laborCopyBtn" title="Copy a plain-text version of the current summary">
                        <i class="bx bx-copy me-1"></i> Copy
                    </button>
                    <button type="button" class="btn btn-outline-secondary btn-sm" id="laborPrintBtn" title="Open the browser's print dialog">
                        <i class="bx bx-printer me-1"></i> Print
                    </button>
                    <button type="button" class="btn btn-primary btn-sm" id="laborPdfBtn" title="Open the print dialog and choose 'Save as PDF'">
                        <i class="bx bx-download me-1"></i> Export PDF
                    </button>
                    <button type="button" class="btn-close ms-1" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
            </div>
            <div class="modal-body" style="overflow-y: auto;">
                <div class="alert alert-info py-2 mb-3" style="font-size:12.5px;">
                    <strong><i class="bx bx-calculator me-1"></i> Cost formula</strong> &mdash;
                    <code>cost = rate &times; units &times; days</code>
                    where <code>units</code> is <strong>2</strong> for whole-day, <strong>1</strong> for half-day, <strong>0</strong> for N/A,
                    and <code>days</code> is the number of calendar days the activity spans (single-day = 1).
                    Drafted activities are excluded.
                </div>

                {{-- Filter panel --}}
                <div class="card mb-3" style="background: #f8f9fa; border: 1px solid #e6e8ec;">
                    <div class="card-body p-3">
                        <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                            <h6 class="text-dark mb-0"><i class="bx bx-filter-alt me-1"></i> Filters</h6>
                            <div class="d-flex gap-2 flex-wrap">
                                <button type="button" class="btn btn-light btn-sm" id="laborResetFiltersBtn"><i class="bx bx-reset me-1"></i> Reset</button>
                                <button type="button" class="btn btn-success btn-sm" id="laborApplyFiltersBtn"><i class="bx bx-check me-1"></i> Apply Filters</button>
                            </div>
                        </div>

                        <div class="row g-3">
                            {{-- Lot filter. It used to be lot GROUPS, with a
                                 line pointing at the Settings tab to define
                                 one — and that screen went with the concept.
                                 A group only ever resolved to its lots on the
                                 server, and the endpoint takes lot ids
                                 directly, so this asks the question itself. --}}
                            <div class="col-md-6">
                                <label class="form-label text-dark mb-1" style="font-size:12px;">
                                    <i class="bx bx-map-pin"></i> Lots
                                    <small class="text-secondary fw-normal">— filter by lot</small>
                                    <a href="javascript:void(0);" class="text-decoration-none ms-2" id="laborSelectAllLots" style="font-size:11px;">all</a> ·
                                    <a href="javascript:void(0);" class="text-decoration-none" id="laborClearLots" style="font-size:11px;">none</a>
                                </label>
                                <div class="lot-chip-container" id="laborLotsContainer" style="min-height: 60px;">
                                    @foreach($schedule->lots as $lot)
                                        <span class="lot-chip" data-lot-id="{{ $lot->id }}" role="button" aria-pressed="false" title="{{ $lot->crop ?: '' }}">
                                            {{ $lot->lotName }}
                                        </span>
                                    @endforeach
                                    @if($schedule->lots->count() === 0)
                                        <small class="text-secondary">No lots yet. Add them in the <strong>Lots</strong> tab.</small>
                                    @endif
                                </div>
                            </div>

                            {{-- Worker filter --}}
                            <div class="col-md-6">
                                <label class="form-label text-dark mb-1" style="font-size:12px;">
                                    <i class="bx bx-user"></i> Workers
                                    <small class="text-secondary fw-normal">— all / one / many</small>
                                    <a href="javascript:void(0);" class="text-decoration-none ms-2" id="laborSelectAllWorkers" style="font-size:11px;">all</a> ·
                                    <a href="javascript:void(0);" class="text-decoration-none" id="laborClearWorkers" style="font-size:11px;">none</a>
                                </label>
                                <div class="lot-chip-container" id="laborWorkersContainer" style="min-height: 60px; max-height: 130px; overflow-y: auto;">
                                    @foreach($schedule->workers as $worker)
                                        <span class="lot-chip" data-worker-id="{{ $worker->id }}" data-priority="{{ (int) $worker->priority }}" role="button" aria-pressed="false">
                                            {{ $worker->workerName }}
                                            <small class="text-muted ms-1">#{{ $worker->priority }} · ₱{{ number_format((float) $worker->costPerHalfDay, 2) }}/half</small>
                                        </span>
                                    @endforeach
                                    @if($schedule->workers->count() === 0)
                                        <small class="text-secondary">No workers defined.</small>
                                    @endif
                                </div>
                            </div>

                            {{-- DAS range filter --}}
                            <div class="col-md-12">
                                <div class="d-flex gap-3 align-items-center flex-wrap">
                                    <label class="form-label text-dark mb-0" style="font-size:12px; white-space: nowrap;">
                                        <i class="bx bx-calendar"></i> {{ $schedule->dayType }} Range
                                    </label>
                                    <div class="d-flex gap-1 align-items-center">
                                        <input type="number" class="form-control form-control-sm" id="laborDasMin" placeholder="min" style="width: 95px; text-align:center;">
                                        <span class="text-secondary">to</span>
                                        <input type="number" class="form-control form-control-sm" id="laborDasMax" placeholder="max" style="width: 95px; text-align:center;">
                                    </div>
                                    <small class="text-secondary" style="font-size:11px;">
                                        Empty = no filter. Each activity uses its earliest <strong>{{ $schedule->dayType }}</strong> across its lots.
                                    </small>
                                </div>
                            </div>

                            {{-- Calendar date range filter — restricts total to
                                 work-days that fall inside the picked window.
                                 Multi-day activities are pro-rated: only the
                                 days within the window count toward the cost. --}}
                            <div class="col-md-12">
                                <div class="d-flex gap-3 align-items-center flex-wrap">
                                    <label class="form-label text-dark mb-0" style="font-size:12px; white-space: nowrap;">
                                        <i class="bx bx-calendar-event"></i> Date Range
                                    </label>
                                    <div class="d-flex gap-1 align-items-center">
                                        <input type="date" class="form-control form-control-sm" id="laborStartDate" style="width: 145px;">
                                        <span class="text-secondary">to</span>
                                        <input type="date" class="form-control form-control-sm" id="laborEndDate" style="width: 145px;">
                                        <button type="button" class="btn btn-link btn-sm p-0 ms-1" id="laborDateClearBtn" title="Clear date range" style="font-size:11px;">Clear</button>
                                    </div>
                                    <small class="text-secondary" style="font-size:11px;">
                                        Empty = no filter. Multi-day activities are pro-rated to the days inside this window.
                                    </small>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="laborSummaryBody">
                    <div class="text-center py-4 text-secondary">
                        <i class="bx bx-loader-alt bx-spin" style="font-size: 1.5rem;"></i>
                        <p class="text-dark mt-2 mb-0">Calculating&hellip;</p>
                    </div>
                </div>
            </div>
            <div class="modal-footer py-2">
                <small class="text-secondary me-auto" id="laborFilterCountHint"></small>
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Drafts modal: list every drafted activity and click any to restore --}}
{{-- Worker-presentation options modal: pick which optional sections to include before opening the report. --}}
<div class="modal fade" id="workerPresentationOptionsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-book-open me-2"></i>Worker Presentation Options</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p class="text-dark mb-3">Pick which optional sections to include in the presentation:</p>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="optShowDesc" checked>
                    <label class="form-check-label text-dark" for="optShowDesc">
                        <strong>Activity descriptions</strong>
                        <div class="text-secondary" style="font-size: 12.5px;">
                            Show the full description under each activity card (rich text). Adds depth but lengthens the document.
                        </div>
                    </label>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="optShowIrrigation" checked>
                    <label class="form-check-label text-dark" for="optShowIrrigation">
                        <strong>Irrigation schedules</strong>
                        <div class="text-secondary" style="font-size: 12.5px;">
                            Show every irrigation cycle as <em>{{ $schedule->dayType }}</em> ranges with their per-group calendar dates and assigned worker.
                        </div>
                    </label>
                </div>

                <div class="form-check mb-2">
                    <input class="form-check-input" type="checkbox" id="optShowCalendar" checked>
                    <label class="form-check-label text-dark" for="optShowCalendar">
                        <strong>Calendar view</strong>
                        <div class="text-secondary" style="font-size: 12.5px;">
                            Show the month-by-month calendar with activity + irrigation bands across days. Adds landscape pages at the end.
                        </div>
                    </label>
                </div>

                <hr class="my-3">

                <div class="form-check mb-3">
                    <input class="form-check-input" type="checkbox" id="optLaborOnly">
                    <label class="form-check-label text-dark" for="optLaborOnly">
                        <strong>Show only the worker labor section</strong>
                        <div class="text-secondary" style="font-size: 12.5px;">
                            Hide intro tables (groups/workers/lots), the activities timeline, irrigation, and calendar.
                            Only the per-worker pages and the monthly labor counts remain.
                        </div>
                    </label>
                </div>

                <div class="mb-1">
                    <label class="form-label text-dark mb-1" style="font-weight:600;font-size:13px;">
                        <i class="bx bx-user-check me-1"></i> Workers to include
                    </label>
                    <small class="text-secondary d-block mb-2" style="font-size: 12.5px;">
                        All workers are included by default — <strong>uncheck</strong> any you don't want in the labor section.
                    </small>
                    <div class="d-flex gap-2 mb-2">
                        <button type="button" class="btn btn-link btn-sm p-0" id="wpWorkersSelectAllBtn">Select all</button>
                        <span class="text-secondary">·</span>
                        <button type="button" class="btn btn-link btn-sm p-0" id="wpWorkersClearBtn">Clear</button>
                    </div>
                    <div class="d-flex flex-wrap gap-2 p-2 rounded" id="wpWorkersList" style="border:1px solid #e6e8ec; background:#fafbfc; max-height: 180px; overflow-y: auto;">
                        @foreach($schedule->workers as $w)
                            <div class="form-check form-check-inline m-0" style="min-width: 32%;">
                                <input class="form-check-input wp-worker-pick" type="checkbox" id="wpWorker{{ $w->id }}" value="{{ $w->id }}" checked>
                                <label class="form-check-label text-dark" for="wpWorker{{ $w->id }}" style="font-size: 13px;">{{ $w->workerName }}</label>
                            </div>
                        @endforeach
                        @if($schedule->workers->count() === 0)
                            <small class="text-secondary"><i class="bx bx-info-circle me-1"></i>No workers defined on this schedule.</small>
                        @endif
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="presentGenerateBtn">
                    <i class="bx bx-book-open me-1"></i> Generate Presentation
                </button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="draftsModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-archive me-2"></i>Drafted Activities</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body" style="max-height: 65vh; overflow-y: auto;">
                <p class="text-secondary mb-3" style="font-size:13px;">
                    <i class="bx bx-info-circle me-1"></i>
                    These activities are temporarily hidden from the timeline. Click any draft to restore it back into the schedule.
                </p>
                <div id="draftsListContainer">
                    <div class="text-center py-4 text-secondary">
                        <i class="bx bx-loader-alt bx-spin" style="font-size: 1.5rem;"></i>
                        <p class="text-dark mt-2 mb-0">Loading drafts…</p>
                    </div>
                </div>
                <div id="draftsEmpty" style="display:none;" class="text-center py-4">
                    <i class="bx bx-archive text-secondary" style="font-size: 2.5rem;"></i>
                    <p class="text-dark mt-2 mb-1">No drafted activities.</p>
                    <small class="text-secondary">Click the <i class="bx bx-archive-in"></i> button on any activity card to move it here.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Activity modal --}}
<div class="modal fade" id="activityModal" tabindex="-1" data-bs-focus="false">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-task me-2"></i><span id="activityModalTitle">Add Activity</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="activityId">

                {{-- Task / Irrigation / Service mode tabs. Each saves as an
                     activity of the matching type; the mode drives which
                     type-specific field shows (type select / water task /
                     service price). Mirrors the client app. --}}
                <div class="activity-mode-tabs" id="activityModeTabs" role="tablist">
                    <button type="button" class="activity-mode-tab is-active" data-mode="task" aria-selected="true">
                        <i class="bx bx-task"></i> Task
                    </button>
                    <button type="button" class="activity-mode-tab" data-mode="irrigation" aria-selected="false">
                        <i class="bx bxs-droplet"></i> Irrigation
                    </button>
                    <button type="button" class="activity-mode-tab" data-mode="service" aria-selected="false">
                        <i class="bx bx-wrench"></i> Service
                    </button>
                    {{-- Beside the three kinds, not underneath one of them:
                         who is on the job is asked the same way as what the
                         job is, and it does not change the activity's type. --}}
                    <button type="button" class="activity-mode-tab" data-mode="payroll" aria-selected="false">
                        <i class="bx bx-group"></i> Worker checklist
                    </button>
                    {{-- The day's errands: things that are nobody's task and
                         nobody's wage but still have to happen, some of which
                         cost or bring in money once they do. --}}
                    <button type="button" class="activity-mode-tab" data-mode="reminders" aria-selected="false">
                        <i class="bx bx-list-check"></i> Reminder checklist
                    </button>
                </div>

                <div class="row">
                    <div class="col-md-12 mb-3">
                        <label class="form-label text-dark">Activity Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="activityTitle" placeholder="e.g. Basal Fertilizer">
                    </div>
                </div>
                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="activityTargetDate">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">
                            End Date
                            <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                        </label>
                        <div class="input-group">
                            <input type="date" class="form-control" id="activityTargetEndDate">
                            <button type="button" class="btn btn-outline-secondary" id="activityTargetEndDateClear" title="Clear end date (single-day activity)">
                                <i class="bx bx-x"></i>
                            </button>
                        </div>
                        <small class="text-secondary">Leave empty for a single-day activity. Set for a multi-day range (e.g. land preparation over 5 days).</small>
                    </div>
                </div>

                {{-- Day-number entry — the DAS/DAP twin of the date row above.
                     Farm work is planned as "basal fertilizer at DAS 21", not
                     as a calendar date, so this lets the user type the day
                     number and have the date fill itself in. Two-way: editing
                     a date refreshes the day numbers, editing a day number
                     rewrites the date.

                     The date inputs above remain the only thing submitted —
                     these fields are purely a lens over them, so nothing
                     downstream (calendar, export, presentation) changes.

                     Rendered hidden; JS reveals it only when a selected lot
                     carries a Day 0 anchor, since without an anchor a day
                     number has nothing to count from. --}}
                {{-- NOTE: the frame-name spans below use class
                     `activity-day-frame-name` (NOT `day-type-label`). JS flips
                     their text to "DAT" when the DAT frame is active, and
                     getScheduleDayType() reads the FIRST `.day-type-label` on the
                     page — so reusing that class here would make it report "DAT"
                     and corrupt every base-type label. Keep them separate. --}}
                <div class="mb-3 p-3 rounded" id="activityDasRow"
                     style="display:none; background:#eef4fb; border:1px solid #c9dcf0;">
                    <div class="d-flex align-items-center gap-2 mb-2 flex-wrap">
                        <label class="form-label text-dark mb-0 fw-semibold" style="font-size:13px;">
                            <i class="bx bx-trending-up"></i>
                            Set by <span class="activity-day-frame-name">{{ $schedule->dayType }}</span> day number
                        </label>
                        <span class="badge bg-light text-secondary" style="font-weight:500;">Optional</span>
                        {{-- Base ⇄ DAT frame toggle — shown only when the chosen
                             reference lot has BOTH a sowing and a transplant
                             anchor, so the user can count from either. --}}
                        <div class="btn-group btn-group-sm ms-auto" role="group" id="activityDayFrameToggle" style="display:none;">
                            <input type="radio" class="btn-check" name="activityDayFrame" id="activityDayFrameBase" value="base" checked>
                            <label class="btn btn-outline-primary" for="activityDayFrameBase"><span class="day-type-label">{{ $schedule->dayType }}</span></label>
                            <input type="radio" class="btn-check" name="activityDayFrame" id="activityDayFrameDat" value="dat">
                            <label class="btn btn-outline-success" for="activityDayFrameDat">DAT</label>
                        </div>
                    </div>
                    <div class="row g-2 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label text-dark mb-1" style="font-size:12px;">Relative to lot</label>
                            <select class="form-select form-select-sm" id="activityDasRefLot"></select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-dark mb-1" style="font-size:12px;">
                                Start <span class="activity-day-frame-name">{{ $schedule->dayType }}</span>
                            </label>
                            <input type="number" step="1" class="form-control form-control-sm"
                                   id="activityStartDas" placeholder="e.g. 21">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label text-dark mb-1" style="font-size:12px;">
                                End <span class="activity-day-frame-name">{{ $schedule->dayType }}</span>
                                <span class="text-secondary fw-normal">— optional</span>
                            </label>
                            <input type="number" step="1" class="form-control form-control-sm"
                                   id="activityEndDas" placeholder="e.g. 25">
                        </div>
                    </div>
                    <small class="text-secondary d-block mt-2" id="activityDasAnchorNote"></small>
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark">
                        <i class="bx bx-map-pin"></i> Lots this activity applies to
                        <span class="text-secondary fw-normal">— tap to include/exclude, or pick <strong>N/A</strong> for general activities</span>
                    </label>
                    <div class="alert alert-warning mb-0" id="activityLotsEmpty" @if($schedule->lots->count() > 0) style="display:none;" @endif>
                        <i class="bx bx-info-circle me-1"></i> Add at least one lot first (in the <strong>Lots</strong> tab) before creating activities.
                    </div>
                    <div class="lot-chip-container" id="activityLotsContainer" @if($schedule->lots->count() === 0) style="display:none;" @endif>
                        {{-- N/A pseudo-chip: clicking it deselects every real lot;
                             clicking any real lot deselects N/A. Server stores
                             "N/A activity" as an activity with zero lot pivots. --}}
                        <span class="lot-chip lot-chip-na" data-lot-na="1" role="button" aria-pressed="false" title="The activity applies generally, not to any specific lot">
                            <i class="bx bx-globe"></i> N/A — Not lot-specific
                        </span>
                        @foreach($schedule->lots as $lot)
                            <span class="lot-chip" data-lot-id="{{ $lot->id }}" role="button" aria-pressed="false">{{ $lot->lotName }}</span>
                        @endforeach
                    </div>
                </div>

                {{-- Task mode: pick a work type. Irrigation & Service are
                     driven by their own tabs, so they're excluded here. --}}
                <div class="mb-3" id="activityTypeWrap">
                    <label class="form-label text-dark">Activity Type
                        <small class="text-secondary fw-normal">— what kind of work is this?</small>
                    </label>
                    <select class="form-select" id="activityType">
                        <option value="">— select a type —</option>
                        @foreach(\App\Models\AsScheduleActivity::ACTIVITY_TYPES as $typeKey => $typeLabel)
                            @if(!in_array($typeKey, ['irrigation', 'service'], true))
                                <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                            @endif
                        @endforeach
                    </select>
                </div>

                {{-- Irrigation mode: which water task this is. --}}
                <div class="mb-3" id="activityWaterTaskWrap" style="display:none;">
                    <label class="form-label text-dark">Water Task
                        <small class="text-secondary fw-normal">— what happens to the water?</small>
                    </label>
                    <select class="form-select" id="activityWaterTask">
                        @foreach(\App\Models\AsScheduleActivity::WATER_TASKS as $wtKey => $wtLabel)
                            <option value="{{ $wtKey }}">{{ $wtLabel }}</option>
                        @endforeach
                    </select>
                </div>

                {{-- Service mode: the cost of the hired service. --}}
                <div class="mb-3" id="activityServicePriceWrap" style="display:none;">
                    <label class="form-label text-dark">Service Price (₱)
                        <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                    </label>
                    <input type="number" class="form-control" id="activityServicePrice" min="0" step="any" placeholder="0.00" inputmode="decimal">
                    <small class="text-secondary">The cost of this hired service for the lot(s) it applies to.</small>
                </div>

                <div class="row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">Priority <span class="text-danger">*</span></label>
                        <select class="form-select" id="activityPriority">
                            <option value="critical">Critical</option>
                            <option value="high">High</option>
                            <option value="medium" selected>Medium</option>
                            <option value="low">Low</option>
                        </select>
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">Time Required <span class="text-danger">*</span></label>
                        <select class="form-select" id="activityTimeRequired">
                            <option value="half">Half Day</option>
                            <option value="whole">Whole Day</option>
                            <option value="n/a">N/A</option>
                        </select>
                        <small class="text-secondary">Pick <strong>N/A</strong> if this activity doesn't take measurable worker time.</small>
                    </div>
                </div>

                <div class="mb-3 p-3 rounded" style="background:#fff8e1; border:1px solid #ffe0a8;">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="activityIsDayZero">
                        <label class="form-check-label text-dark fw-semibold" for="activityIsDayZero">
                            <i class="bx bxs-star text-warning"></i>
                            Mark this activity as <span class="day-type-label">{{ $schedule->dayType }}</span> 0
                            <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                        </label>
                    </div>
                    <small class="text-secondary d-block mt-1" style="margin-left:1.5rem;">
                        When checked, this activity's <strong>Start Date</strong> becomes
                        <strong><span class="day-type-label">{{ $schedule->dayType }}</span> 0</strong>
                        for every lot it covers. Other activities on those lots will be labeled relative to this anchor
                        (e.g. <span class="day-type-label">{{ $schedule->dayType }}</span>+7,
                        <span class="day-type-label">{{ $schedule->dayType }}</span>+14).
                        If more than one activity on the same lot is marked, the <strong>earliest date</strong> wins.
                    </small>
                </div>

                {{-- Transplant / DAT 0 anchor — for transplanted rice. Mutually
                     exclusive with "Mark as {{ $schedule->dayType }} 0" (sowing
                     and transplanting are different events); the JS keeps only
                     one ticked. --}}
                <div class="mb-3 p-3 rounded" style="background:#e6f7f1; border:1px solid #b3e6d5;">
                    <div class="form-check">
                        <input type="checkbox" class="form-check-input" id="activityIsTransplant">
                        <label class="form-check-label text-dark fw-semibold" for="activityIsTransplant">
                            <i class="bx bx-transfer-alt text-success"></i>
                            Mark this activity as the <strong>transplant</strong> — convert to <strong>DAT 0</strong>
                            <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                        </label>
                    </div>
                    <small class="text-secondary d-block mt-1" style="margin-left:1.5rem;">
                        For transplanted rice: seedbed activities count in
                        <strong><span class="day-type-label">{{ $schedule->dayType }}</span></strong> (from sowing).
                        Mark the transplanting activity here — its <strong>Start Date</strong> becomes
                        <strong>DAT 0</strong> for every lot it covers, and later activities on those lots
                        switch to <strong>DAT</strong> day numbers (DAT 14, DAT 30, …).
                        If more than one activity on the same lot is marked, the <strong>earliest date</strong> wins.
                    </small>
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark">
                        <i class="bx bx-user"></i> Workers assigned to this activity
                        <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                        <span class="text-secondary fw-normal d-block mt-1" style="font-size:12px;">Tap to include/exclude. Leave empty if no manual labor is needed.</span>
                    </label>
                    <div class="alert alert-warning mb-0" id="activityWorkersEmpty" @if($schedule->workers->count() > 0) style="display:none;" @endif>
                        <i class="bx bx-info-circle me-1"></i> No workers defined yet. Add them in the <strong>Workers</strong> tab.
                    </div>
                    <div class="lot-chip-container" id="activityWorkersContainer" @if($schedule->workers->count() === 0) style="display:none;" @endif>
                        @foreach($schedule->workers as $worker)
                            <span class="lot-chip" data-worker-id="{{ $worker->id }}" data-priority="{{ (int) $worker->priority }}" role="button" aria-pressed="false">
                                {{ $worker->workerName }}
                                <small class="text-muted ms-1">#{{ $worker->priority }}</small>
                            </span>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <label class="form-label text-dark mb-1">Description</label>
                        <button type="button" class="btn btn-sm btn-link p-0 text-decoration-none" id="toggleDescriptionMode">
                            <i class="bx bx-code-alt"></i> <span id="toggleDescriptionModeLabel">Edit HTML source</span>
                        </button>
                    </div>
                    <small class="text-secondary d-block mb-1">Supports formatting — headings, bold, lists, links, etc. Toggle to <strong>Edit HTML source</strong> to paste or hand-edit raw markup.</small>
                    {{-- Quill rich-text editor (replaces TinyMCE). The mount
                         div holds the WYSIWYG. The sibling textarea is the
                         HTML source-edit view, toggled by #toggleDescriptionMode. --}}
                    <div class="sm-quill-wrap" id="activityDescriptionWrap">
                        <div class="sm-quill-host" id="activityDescription"></div>
                        <textarea class="form-control sm-quill-html-source" id="activityDescriptionSource" rows="12"></textarea>
                    </div>
                </div>

                {{-- Activity reference image. Single optional image per
                     activity, shown on the activity card, in the card
                     viewer, worker presentation, and export schedule.
                     Upload happens immediately via /activities-image-upload;
                     the resolved relative path is stashed in the hidden
                     input and persisted on activity save. --}}
                <div class="mb-3">
                    <label class="form-label text-dark">
                        <i class="bx bx-image"></i> Activity Image
                        <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                    </label>
                    <small class="text-secondary d-block mb-2">
                        Upload one reference image (JPG / PNG / WebP / GIF, max 8 MB). Renders
                        on the activity card, card viewer, worker presentation, and export schedule.
                    </small>
                    <div id="activityImageWrap" class="activity-image-wrap" style="display:none;">
                        <img id="activityImagePreview" src="" alt="Activity image preview" class="activity-image-preview">
                        <div class="mt-2 d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-sm btn-outline-primary" id="activityImageReplaceBtn">
                                <i class="bx bx-refresh me-1"></i> Replace Image
                            </button>
                            <button type="button" class="btn btn-sm btn-outline-danger" id="activityImageRemoveBtn">
                                <i class="bx bx-trash me-1"></i> Remove Image
                            </button>
                        </div>
                    </div>
                    <div id="activityImageEmpty">
                        <button type="button" class="btn btn-outline-primary" id="activityImageUploadBtn">
                            <i class="bx bx-cloud-upload me-1"></i> Upload Image
                        </button>
                    </div>
                    <input type="file" id="activityImageFileInput" accept="image/jpeg,image/png,image/webp,image/gif" style="display:none;">
                    <input type="hidden" id="activityImagePath" value="">
                </div>

                <div id="activityItemsSection">
                <hr>
                <h6 class="text-dark mb-1">
                    <span id="activityItemsSectionLabel">Materials & Services Used</span>
                    <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                </h6>
                {{-- Reminder checklist. One line per errand: what it is, and
                     — only if it involves money — whether ticking it costs or
                     earns, and how much. A line with money on it becomes an
                     expense or an income on the day it is ticked, which is
                     where the day's cash comes from. --}}
                <div id="activityRemindersPane" style="display:none;" class="mb-3">
                    <label class="form-label text-dark">Reminders
                        <small class="text-secondary fw-normal">— ticked on the day, as they happen</small>
                    </label>
                    <div id="reminderRows"></div>
                    <button type="button" class="btn btn-outline-primary btn-sm mt-2" id="addReminderRow">
                        <i class="bx bx-plus"></i> Add a reminder
                    </button>
                    <small class="text-secondary d-block mt-2">
                        A line with money attached only counts on the day it is ticked.
                    </small>
                </div>

                <small class="text-secondary d-block mb-2">
                    Pick any inputs this activity consumes. <strong>Leave empty</strong> if the activity uses no materials and no paid services
                    (e.g. a manual task with nothing to apply, or work done by your own crew).
                </small>
                <div class="row g-2 align-items-end mb-2">
                    <div class="col-md-3">
                        <label class="form-label text-dark">Type</label>
                        <select class="form-select" id="itemPickerType">
                            <option value="material">Material</option>
                            <option value="service">Service</option>
                            {{-- The way the farmer app writes every item now:
                                 a name and a price, no catalogue involved. --}}
                            <option value="custom">Something else</option>
                        </select>
                    </div>
                    <div class="col-md-3" id="itemPickerCustomWrap" style="display:none;">
                        <label class="form-label text-dark">What it is</label>
                        <input type="text" class="form-control" id="itemPickerName" maxlength="255" placeholder="e.g. Urea 46-0-0">
                    </div>
                    <div class="col-md-2" id="itemPickerPriceWrap" style="display:none;">
                        <label class="form-label text-dark">Price</label>
                        <input type="number" min="0" step="0.01" class="form-control" id="itemPickerPrice" placeholder="0.00">
                    </div>
                    <div class="col-md-3" id="itemPickerPickWrap">
                        <label class="form-label text-dark">Pick</label>
                        <select class="form-select" id="itemPickerId">
                            <optgroup label="Materials">
                                @foreach($schedule->materials as $m)
                                    <option value="material::{{ $m->id }}" data-unit="{{ $m->unitOfMeasure }}">{{ $m->materialName }} ({{ $m->unitOfMeasure }})</option>
                                @endforeach
                            </optgroup>
                            <optgroup label="Services">
                                @foreach($schedule->services as $s)
                                    <option value="service::{{ $s->id }}">{{ $s->serviceName }}</option>
                                @endforeach
                            </optgroup>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark">Qty</label>
                        <input type="number" min="0" step="0.0001" class="form-control" id="itemPickerQty" value="1">
                    </div>
                    <div class="col-md-2">
                        <label class="form-label text-dark">Unit</label>
                        <select class="form-select" id="itemPickerUnit">
                            <option value="">—</option>
                            <option value="kg">kg</option>
                            <option value="g">g</option>
                            <option value="l">L</option>
                            <option value="ml">ml</option>
                            <option value="bottle">bottle</option>
                            <option value="sachet">sachet</option>
                            <option value="pack">pack</option>
                            <option value="piece">piece</option>
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="button" class="btn btn-outline-primary w-100" id="addItemBtn"><i class="bx bx-plus"></i> Add</button>
                    </div>
                </div>
                <small class="text-secondary d-block mb-2">Picking a material auto-fills its unit — you can override (e.g. grams instead of kg).</small>
                <div id="itemsContainer" class="mt-2"></div>
                <div id="itemsContainerEmpty" class="mt-2 p-3 text-center text-secondary rounded" style="background:#f8f9fa; border:1px dashed #d3d6db;">
                    <i class="bx bx-package"></i>
                    <small class="d-block mt-1">No materials or services added. That's fine — this activity will be marked as needing none.</small>
                </div>
                </div>{{-- /activityItemsSection --}}
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveActivityBtn"><i class="bx bx-save me-1"></i>Save Activity</button>
            </div>
        </div>
    </div>
</div>

{{-- New version modal: fork from current (or create empty) + name + notes.
     The "sourceVersionId" hidden field is populated by the trigger button
     so a single modal handles both fork and empty-create flows. --}}
<div class="modal fade" id="newVersionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark">
                    <i class="bx bx-git-branch me-2"></i>
                    <span id="newVersionModalTitle">Fork new version</span>
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="alert alert-info py-2 mb-3" style="font-size:12.5px;">
                    <i class="bx bx-info-circle me-1"></i>
                    <span id="newVersionSourceHint">All activities from the current version will be deep-cloned into the new branch. Edits in the new version will not affect the source.</span>
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark">Version name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="newVersionName"
                           maxlength="120"
                           placeholder="e.g. Budget Cut V1, Labor Shortage Plan, Drought Adjusted">
                    <small class="text-secondary">A short label that will appear as a tab.</small>
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark">Notes (optional)</label>
                    <textarea class="form-control" id="newVersionDescription" rows="3"
                              maxlength="5000"
                              placeholder="Why are you creating this version? e.g. 'Reduced labor on spraying tasks to handle budget freeze'"></textarea>
                </div>

                <div class="form-check">
                    <input type="checkbox" class="form-check-input" id="newVersionSetActive" checked>
                    <label class="form-check-label text-dark" for="newVersionSetActive">
                        Switch to this version after creating
                    </label>
                </div>

                <input type="hidden" id="newVersionSourceId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveNewVersionBtn">
                    <i class="bx bx-git-branch me-1"></i> <span id="saveNewVersionBtnLabel">Create Version</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- Rename / edit-notes modal for the currently-active version. --}}
<div class="modal fade" id="renameVersionModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-edit-alt me-2"></i>Rename version</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label class="form-label text-dark">Version name <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="renameVersionName" maxlength="120">
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Notes (optional)</label>
                    <textarea class="form-control" id="renameVersionDescription" rows="3" maxlength="5000"></textarea>
                </div>
                <input type="hidden" id="renameVersionId">
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveRenameVersionBtn">
                    <i class="bx bx-save me-1"></i> Save Changes
                </button>
            </div>
        </div>
    </div>
</div>


{{-- ============================ NOTICE ============================
     The client's own readiness bell, read from this side. Same checks, same
     wording, so a farmer asking "what is it complaining about?" and the admin
     answering are looking at one list. --}}
<div class="modal fade" id="scheduleNoticeModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0"><i class="bx bx-bell me-1"></i> Notice</h5>
                    <small class="text-secondary">What is still missing from this plan</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="scheduleNoticeBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light btn-sm" id="scheduleNoticeReload"><i class="bx bx-refresh"></i> Check again</button>
                <button type="button" class="btn btn-primary btn-sm" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================ SHARE ============================
     The client's own public link — the same address their Quick Share sheet
     hands out. A plan that has never been shared has no link yet; minting one
     is a deliberate press, not something opening this panel does. --}}
<div class="modal fade" id="quickShareModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title mb-0"><i class="bx bx-share-alt me-1"></i> Share this plan</h5>
                    <small class="text-secondary">Anyone with the link can read it — no login</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="quickShareHas" style="display:none;">
                    <div class="input-group mb-3">
                        <input type="text" class="form-control" id="quickShareLink" readonly>
                        <button class="btn btn-outline-secondary" type="button" id="quickShareCopy" title="Copy the link">
                            <i class="bx bx-copy"></i>
                        </button>
                    </div>
                    <div class="share-social">
                        <a class="btn btn-outline-primary btn-sm" id="quickShareFb" target="_blank" rel="noopener"><i class="bx bxl-facebook me-1"></i> Facebook</a>
                        <a class="btn btn-outline-success btn-sm" id="quickShareWa" target="_blank" rel="noopener"><i class="bx bxl-whatsapp me-1"></i> WhatsApp</a>
                        <a class="btn btn-outline-secondary btn-sm" id="quickShareEmail"><i class="bx bx-envelope me-1"></i> Email</a>
                    </div>
                </div>
                <div id="quickShareNone" style="display:none;">
                    <p class="text-secondary mb-3">
                        This plan has never been shared, so it has no link yet. Creating one makes it
                        readable by anyone who is given the address.
                    </p>
                    <button type="button" class="btn btn-primary btn-sm" id="quickShareCreate">
                        <i class="bx bx-link me-1"></i> Create a share link
                    </button>
                </div>
                <div id="quickShareLoading" class="text-center py-3"><i class="bx bx-loader-alt bx-spin fs-4 text-secondary"></i></div>
            </div>
        </div>
    </div>
</div>

{{-- ============================ WHAT THE BOARD SHOWS ============================
     Every row forwards to the control that already does the work and reads
     its state back, so there is one implementation of each answer and this is
     a face on it. --}}
<div class="modal fade" id="viewFilterModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-show me-1"></i>What the board shows</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <button type="button" class="vf-row" data-vf="empty">
                    <span class="vf-ico"><i class="bx bx-moon"></i></span>
                    <span class="vf-txt"><b>Empty dates</b><i>Days with nothing scheduled</i></span>
                    <span class="vf-state" id="vfEmptyState">Shown</span>
                </button>
                <button type="button" class="vf-row" data-vf="doneDays">
                    <span class="vf-ico"><i class="bx bx-calendar-check"></i></span>
                    <span class="vf-txt"><b>Finished days</b><i>Days where every activity is done</i></span>
                    <span class="vf-state" id="vfDoneDaysState">Shown</span>
                </button>
                <button type="button" class="vf-row" data-vf="doneActs">
                    <span class="vf-ico"><i class="bx bx-check-circle"></i></span>
                    <span class="vf-txt"><b>Completed activities</b><i>The ones already ticked</i></span>
                    <span class="vf-state" id="vfDoneActsState">Shown</span>
                </button>
                {{-- Only worth offering when something is actually hidden. --}}
                <button type="button" class="vf-row" data-vf="hidden" id="vfHiddenRow" style="display:none;">
                    <span class="vf-ico"><i class="bx bx-hide"></i></span>
                    <span class="vf-txt"><b>Hidden activities</b><i id="vfHiddenSub">Kept off the farmer's own board</i></span>
                    <span class="vf-state" id="vfHiddenState">Hidden</span>
                </button>
                <button type="button" class="vf-row" data-vf="fold">
                    <span class="vf-ico"><i class="bx bx-chevrons-up"></i></span>
                    <span class="vf-txt"><b>Fold every day</b><i>Shut the whole board</i></span>
                    <span class="vf-go">Fold</span>
                </button>
                <button type="button" class="vf-row" data-vf="unfold">
                    <span class="vf-ico"><i class="bx bx-chevrons-down"></i></span>
                    <span class="vf-txt"><b>Open every day</b><i>Show every card again</i></span>
                    <span class="vf-go">Open</span>
                </button>
            </div>
        </div>
    </div>
</div>

{{-- ============================ SEARCH & FILTER ============================
     The search box and both rows of chips, which used to sit on the board
     whether or not anybody was filtering. --}}
<div class="modal fade" id="filtersModal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-search me-1"></i>Search &amp; filter</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
{{-- Dynamic search bar: filters activity cards (and hides empty date groups)
     as you type. Matches across title, type, lots, workers, and items. --}}
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
    <div class="input-group" style="max-width: 480px;">
        <span class="input-group-text bg-white" style="border-right: 0;">
            <i class="bx bx-search text-secondary"></i>
        </span>
        <input type="search" class="form-control" id="activitySearchInput"
               placeholder="Search by title, type, lot, worker, material…"
               autocomplete="off" style="border-left: 0;">
        <button type="button" class="btn btn-outline-secondary" id="activitySearchClear" title="Clear search">
            <i class="bx bx-x"></i>
        </button>
    </div>
    <small class="text-secondary" id="activitySearchHint" style="display:none;">
        Showing <strong id="activitySearchCount">0</strong> matching activities.
    </small>
    {{-- The show-hidden pill now lives in the Tools menu, beside the other
         questions about what the board is showing. --}}
</div>

{{-- Activity type filter — toggle chips. Tap one (or more) to narrow the
     timeline to that type. Empty selection = show all. Combines with the
     text search above via AND so both filters apply together. --}}
<div class="d-flex align-items-center gap-2 mb-3 flex-wrap" id="activityTypeFilterRow">
    <small class="text-secondary me-1" style="white-space:nowrap;">
        <i class="bx bx-filter-alt"></i> Filter by type:
    </small>
    @foreach(\App\Models\AsScheduleActivity::ACTIVITY_TYPES as $typeKey => $typeLabel)
        <span class="lot-chip activity-type-chip"
              data-type="{{ $typeKey }}"
              role="button"
              aria-pressed="false"
              style="font-size:11.5px; padding:3px 10px;">
            {{ $typeLabel }}
        </span>
    @endforeach
    <button type="button" class="btn btn-link btn-sm p-0 ms-1"
            id="activityTypeFilterClearBtn"
            style="font-size:11.5px; display:none;">
        Clear types
    </button>
</div>

{{-- Lot visibility filter — toggle chips, multi-select. Tapping a lot hides
     its activities from THIS tab only: nothing is deleted and the per-card
     visibility switch (isHidden) is untouched, so the calendar, card viewer,
     presentation, and export are unaffected.

     An activity only disappears once EVERY lot it covers is hidden. Hiding
     "Lot A" therefore never removes a card that also covers a still-visible
     "Lot B" — otherwise you'd lose sight of Lot B's work. Activities with no
     lots at all are governed by the separate N/A chip.

     "Hide all" + un-picking one chip is the focus-on-a-single-lot workflow.
     Combines with the search + type filters via AND. --}}
@if($schedule->lots->count() > 0)
    <div class="d-flex align-items-center gap-2 mb-3 flex-wrap" id="activityLotFilterRow">
        <small class="text-secondary me-1" style="white-space:nowrap;">
            <i class="bx bx-hide"></i> Hide lots:
        </small>
        @foreach($schedule->lots as $lot)
            <span class="lot-chip activity-lot-chip"
                  data-lot-id="{{ $lot->id }}"
                  role="button"
                  aria-pressed="false"
                  title="Hide {{ $lot->lotName }} — cards covering another visible lot stay put"
                  style="font-size:11.5px; padding:3px 10px;">
                {{ $lot->lotName }}@if(!empty($lot->variety))<small style="opacity:.75;"> · {{ $lot->variety }}</small>@endif
            </span>
        @endforeach
        <span class="lot-chip lot-chip-na activity-lot-chip"
              data-lot-id="__na__"
              role="button"
              aria-pressed="false"
              title="Hide activities that aren't tied to any specific lot"
              style="font-size:11.5px; padding:3px 10px;">
            <i class="bx bx-globe"></i> N/A
        </span>
        <button type="button" class="btn btn-link btn-sm p-0 ms-1"
                id="activityLotFilterAllBtn"
                style="font-size:11.5px;"
                title="Hide every lot — then tap one to view it on its own">
            Hide all
        </button>
        <button type="button" class="btn btn-link btn-sm p-0 ms-1"
                id="activityLotFilterClearBtn"
                style="font-size:11.5px; display:none;">
            Show all lots
        </button>
    </div>
@endif
            </div>
            <div class="modal-footer justify-content-between">
                <button type="button" class="btn btn-light btn-sm" id="clearAllFiltersBtn">Clear every filter</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">See the board</button>
            </div>
        </div>
    </div>
</div>

{{-- ============================ MIRROR ============================
     Read-only, built from the board that is already on the page — so it
     cannot disagree with it, and it costs nothing to open. --}}
<div class="modal fade" id="mirrorModal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <div>
                    <h5 class="modal-title text-dark mb-0"><i class="bx bx-book-open me-1"></i>Mirror</h5>
                    <small class="text-secondary" id="mirrorSub">The whole plan, as it stands.</small>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="mirrorBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" id="mirrorPrint"><i class="bx bx-printer me-1"></i>Print</button>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
