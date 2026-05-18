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
@endphp

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
    <div class="d-flex gap-2 flex-wrap">
        <button type="button" class="btn btn-outline-warning btn-sm" id="activityUndoBtn" title="Undo last action (Ctrl+Z) — up to 10 steps" disabled>
            <i class="bx bx-undo me-1"></i> <span id="activityUndoLabel">Undo</span> <span class="badge bg-light text-dark ms-1" id="activityUndoCount" style="display:none;font-weight:500;"></span>
        </button>
        @php $draftsCount = $schedule->drafts->count(); @endphp
        <button type="button" class="btn btn-outline-info btn-sm" id="openDraftsModalBtn" title="View activities you've moved to drafts">
            <i class="bx bx-archive me-1"></i> Drafts
            <span class="badge bg-info text-white ms-1" id="draftsBadge" @if($draftsCount === 0) style="display:none;" @endif>{{ $draftsCount }}</span>
        </button>
        <button type="button" class="btn btn-outline-success btn-sm" id="openLaborSummaryBtn" title="See the total labor expense across all activities">
            <i class="bx bx-money me-1"></i> Labor Expenses
        </button>
        <button type="button" class="btn btn-outline-dark btn-sm" id="openWorkerPresentationBtn" title="Open a printable presentation (intro, activities, monthly labor, per-worker pages, irrigation, calendar) in a new tab">
            <i class="bx bx-book-open me-1"></i> Worker Presentation
        </button>
        <button type="button" class="btn btn-outline-secondary btn-sm" id="openExportScheduleBtn" title="Open a formatted preview, download PDF, or copy as text">
            <i class="bx bx-file-blank me-1"></i> Export Schedule
        </button>
        <button type="button" class="btn btn-primary btn-sm" id="addActivityBtn">
            <i class="bx bx-plus me-1"></i> Add Activity
        </button>
    </div>
</div>

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
                <span class="rest-day-date">{{ $item['carbon']->format('D, M j, Y') }}</span>
                <span class="rest-day-tag">No activities scheduled</span>
            </div>
        @else
        @php
            $dateKey = $item['date'];
            $activitiesForDate = $byDate->get($dateKey);
            $dateCarbon = ($dateKey !== '__no-date__') ? \Illuminate\Support\Carbon::parse($dateKey) : null;
            $colorIndex = $item['color'];
        @endphp
        <div class="date-group date-color-{{ $colorIndex }}" data-date="{{ $dateKey }}">
            <div class="date-header">
                @if($dateCarbon)
                    <i class="bx bx-calendar"></i>
                    <span class="date-header-day">{{ $dateCarbon->format('D') }}</span>
                    <span class="date-header-date">{{ $dateCarbon->format('M j, Y') }}</span>
                @else
                    <i class="bx bx-error-circle"></i>
                    <span class="date-header-date">No date</span>
                @endif
                <span class="date-header-count">{{ $activitiesForDate->count() }} {{ \Illuminate\Support\Str::plural('activity', $activitiesForDate->count()) }}</span>
                @if($dateKey !== '__no-date__')
                    @php $existingNote = $dateNotesByDate->get($dateKey); @endphp
                    <button type="button"
                            class="date-header-edit-btn date-note-btn {{ $existingNote ? 'has-note' : '' }}"
                            data-date="{{ $dateKey }}"
                            data-existing="{{ $existingNote ? e($existingNote->noteContent) : '' }}"
                            title="{{ $existingNote ? 'Edit the note for this date' : 'Add a note for this date' }}">
                        <i class="bx {{ $existingNote ? 'bxs-note' : 'bx-note' }}"></i>
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
                        <i class="bx bxs-note date-note-icon"></i>
                        <div class="date-note-text">{!! $noteRow ? nl2br(e($noteRow->noteContent)) : '' !!}</div>
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
                    <div class="activity-card" draggable="true" data-id="{{ $a->id }}" data-target-date="{{ $dateKey === '__no-date__' ? '' : $dateKey }}" data-target-end-date="{{ $endDateStr }}" data-lot-signature="{{ $lotSig }}" data-sequence-order="{{ (int) $a->sequenceOrder }}" data-is-day-zero="{{ $a->isDayZero ? 1 : 0 }}">
                        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                            <div class="flex-grow-1" style="min-width:0;">
                                <h6 class="text-dark mb-1">
                                    {{ $a->activityTitle }}
                                    @if($a->activityType && isset(\App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a->activityType]))
                                        <span class="badge ms-1 activity-type-badge"
                                              style="background:#e2efd4; color:#2d4d1c; font-weight:600; font-size:11px;"
                                              title="Activity type">
                                            {{ \App\Models\AsScheduleActivity::ACTIVITY_TYPES[$a->activityType] }}
                                        </span>
                                    @endif
                                    @if($a->isDayZero)
                                        <span class="badge ms-1 day-zero-badge"
                                              style="background:#ff9800; color:#fff; font-weight:600; font-size:11px;"
                                              title="This activity is the {{ $schedule->dayType }} 0 anchor — its date becomes {{ $schedule->dayType }} 0 for every lot it covers.">
                                            <i class="bx bxs-star"></i> {{ $schedule->dayType }} 0
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
                                                $dasSuffix = '';
                                                if ($lot->dayZeroDate && $a->targetDate) {
                                                    $d0 = \Illuminate\Support\Carbon::parse($lot->dayZeroDate);
                                                    $dt = \Illuminate\Support\Carbon::parse($a->targetDate);
                                                    $delta = (int) $d0->diffInDays($dt, false);
                                                    $sign = $delta > 0 ? '+' : '';
                                                    $dasSuffix = ' · ' . $schedule->dayType . $sign . $delta;
                                                }
                                            @endphp
                                            <span class="item-tag"
                                                  style="background:#eef0fb; color:#3a4699;"
                                                  data-lot-id="{{ $lot->id }}"
                                                  data-lot-name="{{ $lot->lotName }}">{{ $lot->lotName }}{{ $dasSuffix }}</span>
                                        @endforeach
                                    @else
                                        <small class="text-danger"><i class="bx bx-error-circle"></i> No lots selected — this activity will not be scheduled.</small>
                                    @endif
                                </div>
                            </div>
                            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                                <span class="sm-pill priority-{{ $a->priority }}">{{ ucfirst($a->priority) }}</span>
                                <button class="btn btn-sm btn-outline-primary edit-activity-btn" data-id="{{ $a->id }}" title="Edit"><i class="bx bx-edit-alt"></i></button>
                                <button class="btn btn-sm btn-outline-secondary duplicate-activity-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Duplicate"><i class="bx bx-copy"></i></button>
                                <button class="btn btn-sm btn-outline-info to-draft-activity-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Move to drafts (hide without deleting)"><i class="bx bx-archive-in"></i></button>
                                <button class="btn btn-sm btn-outline-danger delete-activity-btn" data-id="{{ $a->id }}" data-name="{{ $a->activityTitle }}" title="Delete"><i class="bx bx-trash"></i></button>
                            </div>
                        </div>
                        @if($a->description)
                            <div class="text-dark mt-2 mb-2 activity-description-content" style="font-size:13px;">{!! $a->description !!}</div>
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
                            {{-- Group filter (replaces the per-lot picker) --}}
                            <div class="col-md-6">
                                <label class="form-label text-dark mb-1" style="font-size:12px;">
                                    <i class="bx bxs-group"></i> Groups
                                    <small class="text-secondary fw-normal">— filter by lot group</small>
                                    <a href="javascript:void(0);" class="text-decoration-none ms-2" id="laborSelectAllGroups" style="font-size:11px;">all</a> ·
                                    <a href="javascript:void(0);" class="text-decoration-none" id="laborClearGroups" style="font-size:11px;">none</a>
                                </label>
                                <div class="lot-chip-container" id="laborGroupsContainer" style="min-height: 60px;">
                                    @foreach($schedule->defaultGroupings as $group)
                                        @php $glots = $group->lots; @endphp
                                        <span class="lot-chip" data-group-id="{{ $group->id }}" data-lot-ids="{{ $glots->pluck('id')->implode(',') }}" role="button" aria-pressed="false" title="{{ $glots->pluck('lotName')->implode(', ') ?: 'No lots' }}">
                                            {{ $group->groupName }}
                                            <small class="text-muted ms-1">{{ $glots->count() }} {{ \Illuminate\Support\Str::plural('lot', $glots->count()) }}</small>
                                        </span>
                                    @endforeach
                                    @if($schedule->defaultGroupings->count() === 0)
                                        <small class="text-secondary">No groups defined. Add groups in the Settings tab to enable group-based filtering.</small>
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

                <div class="mb-3">
                    <label class="form-label text-dark">
                        <i class="bx bx-map-pin"></i> Lots this activity applies to <span class="text-danger">*</span>
                        <span class="text-secondary fw-normal">— tap to include/exclude</span>
                    </label>
                    <div class="alert alert-warning mb-0" id="activityLotsEmpty" @if($schedule->lots->count() > 0) style="display:none;" @endif>
                        <i class="bx bx-info-circle me-1"></i> Add at least one lot first (in the <strong>Lots</strong> tab) before creating activities.
                    </div>
                    <div class="lot-chip-container" id="activityLotsContainer" @if($schedule->lots->count() === 0) style="display:none;" @endif>
                        @foreach($schedule->lots as $lot)
                            <span class="lot-chip" data-lot-id="{{ $lot->id }}" role="button" aria-pressed="false">{{ $lot->lotName }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark">Activity Type
                        <small class="text-secondary fw-normal">— what kind of work is this?</small>
                    </label>
                    <select class="form-select" id="activityType">
                        <option value="">— select a type —</option>
                        @foreach(\App\Models\AsScheduleActivity::ACTIVITY_TYPES as $typeKey => $typeLabel)
                            <option value="{{ $typeKey }}">{{ $typeLabel }}</option>
                        @endforeach
                    </select>
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
                    <textarea class="form-control" id="activityDescription" rows="6" style="font-family: ui-monospace, SFMono-Regular, Menlo, Consolas, monospace; font-size: 12px;"></textarea>
                </div>

                <hr>
                <h6 class="text-dark mb-1">
                    Materials & Services Used
                    <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                </h6>
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
                        </select>
                    </div>
                    <div class="col-md-3">
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
