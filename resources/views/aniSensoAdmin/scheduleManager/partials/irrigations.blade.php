<div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
    <div>
        <h5 class="text-dark mb-1">Irrigation Schedules</h5>
        <small class="text-secondary">Define irrigation windows by DAS/DAP/DAT day ranges (e.g. Irrigation 1 from DAS 0 to 5).</small>
    </div>
    <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#irrigationModal" id="addIrrigationBtn">
        <i class="bx bx-plus me-1"></i> Add Irrigation
    </button>
</div>

@php
    $taskTypeCatalog = \App\Models\AsScheduleIrrigation::TASK_TYPES;
    // Pull pivot sets once so the row loop can render lot + worker chips
    // without an N+1.
    $schedule->load(['irrigations.workers', 'irrigations.lots']);
@endphp

{{-- Card list — mirrors the activity-card visual language so the two
     tabs feel cohesive. Each card's left border is colored by task type
     (irrigate=blue, drain=brown, etc.) and the whole card is drag-drop
     reorderable. The hidden #irrigationsTable table is still emitted so
     legacy JS that targets `#irrigationsTable tbody` keeps working — its
     <tbody> is the actual card container. --}}
<div class="irrigation-card-list" id="irrigationsList">
    @forelse($schedule->irrigations as $i)
        @php
            $meta = \App\Models\AsScheduleIrrigation::taskTypeMeta($i->taskType);
            $isDateMode = ($i->dayMode === 'date' && $i->startDate && $i->endDate);
        @endphp
        @php $irrPriority = (int) ($i->priority ?? 5); @endphp
        <div class="irrigation-card" draggable="true"
             data-id="{{ $i->id }}"
             data-sort-order="{{ (int) $i->sortOrder }}"
             style="--irr-color: {{ $meta['color'] }};">
            <div class="irr-drag-handle" title="Drag to reorder"><i class="bx bx-grid-vertical"></i></div>
            <div class="irr-card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                    <div class="flex-grow-1" style="min-width: 0;">
                        <h6 class="text-dark mb-1">
                            {{ $i->irrigationTitle }}
                            <span class="badge ms-1 irr-priority-pill irr-prio-{{ $irrPriority }}" title="Priority {{ $irrPriority }} — lower number wins overlapping days">
                                P{{ $irrPriority }}
                            </span>
                            <span class="badge ms-1" style="background: {{ $meta['color'] }}; color:#fff; font-weight:600; font-size:11px;">
                                {{ $meta['icon'] }} {{ $meta['label'] }}
                            </span>
                            @if($isDateMode)
                                <span class="badge bg-secondary text-white ms-1" style="font-weight:500; font-size:11px;">
                                    <i class="bx bx-calendar"></i>
                                    {{ $i->startDate->format('M j') }} — {{ $i->endDate->format('M j, Y') }}
                                </span>
                            @else
                                <span class="badge bg-info text-white ms-1" style="font-weight:500; font-size:11px;">
                                    <span class="day-type-label">{{ $schedule->dayType }}</span>
                                    {{ $i->startDay }} — {{ $i->endDay }}
                                </span>
                            @endif
                        </h6>
                        @if($i->lots->count() > 0)
                            <div class="irr-meta-row">
                                <i class="bx bx-map-pin"></i>
                                @foreach($i->lots as $lt)
                                    <span class="irr-chip irr-chip-lot">{{ $lt->lotName }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($i->workers->count() > 0)
                            <div class="irr-meta-row">
                                <i class="bx bx-user"></i>
                                @foreach($i->workers as $wk)
                                    <span class="irr-chip irr-chip-worker">{{ $wk->workerName }}</span>
                                @endforeach
                            </div>
                        @endif
                        @if($i->lots->count() === 0 && $i->workers->count() === 0)
                            <div class="irr-meta-row text-secondary"><small><i class="bx bx-info-circle me-1"></i>No lots / workers assigned</small></div>
                        @endif
                        @if($i->description)
                            <div class="irr-description">{{ $i->description }}</div>
                        @endif
                    </div>
                    <div class="d-flex align-items-center gap-1 flex-shrink-0">
                        <button class="btn btn-sm btn-outline-primary edit-irrigation-btn"
                                data-id="{{ $i->id }}"
                                data-title="{{ $i->irrigationTitle }}"
                                data-description="{{ $i->description }}"
                                data-day-mode="{{ $i->dayMode ?: 'das' }}"
                                data-start-day="{{ $i->startDay }}"
                                data-end-day="{{ $i->endDay }}"
                                data-start-date="{{ $i->startDate ? $i->startDate->format('Y-m-d') : '' }}"
                                data-end-date="{{ $i->endDate ? $i->endDate->format('Y-m-d') : '' }}"
                                data-task-type="{{ $i->taskType ?: 'irrigate' }}"
                                data-priority="{{ $irrPriority }}"
                                data-worker-id="{{ $i->assignedWorkerId }}"
                                data-worker-ids="{{ $i->workers->pluck('id')->implode(',') }}"
                                data-lot-ids="{{ $i->lots->pluck('id')->implode(',') }}"
                                title="Edit"><i class="bx bx-edit-alt"></i></button>
                        <button class="btn btn-sm btn-outline-secondary duplicate-irrigation-btn"
                                data-id="{{ $i->id }}"
                                data-name="{{ $i->irrigationTitle }}"
                                title="Duplicate"><i class="bx bx-copy"></i></button>
                        <button class="btn btn-sm btn-outline-danger delete-irrigation-btn"
                                data-id="{{ $i->id }}"
                                data-name="{{ $i->irrigationTitle }}"
                                title="Delete"><i class="bx bx-trash"></i></button>
                    </div>
                </div>
            </div>
        </div>
    @empty
        <div id="irrigationsEmpty" class="text-center text-secondary py-5" style="border:1px dashed #d9dde3; border-radius:8px;">
            <i class="bx bx-water" style="font-size:2.5rem;"></i>
            <p class="text-dark mt-2 mb-0">No irrigation schedules yet.</p>
            <small>Click <strong>Add Irrigation</strong> to define your first cycle.</small>
        </div>
    @endforelse
</div>

{{-- Hidden legacy table shell — its <tbody> is the selector the existing
     row-add JS targets. We re-point that selector at #irrigationsList so
     this <table> is no longer required, but we leave it here as a
     no-display backstop to avoid breaking any older JS path. --}}
<table id="irrigationsTable" style="display:none;"><tbody></tbody></table>

{{-- Irrigation modal --}}
<div class="modal fade" id="irrigationModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title text-dark"><i class="bx bx-water me-2"></i><span id="irrigationModalTitle">Add Irrigation</span></h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" id="irrigationId">
                <div class="mb-3">
                    <label class="form-label text-dark">Title <span class="text-danger">*</span></label>
                    <input type="text" class="form-control" id="irrigationTitle" placeholder="e.g. Irrigation 1">
                </div>
                <div class="row">
                    <div class="col-md-7 mb-3">
                        <label class="form-label text-dark">Task Type <span class="text-danger">*</span></label>
                        <select class="form-select" id="irrigationTaskType">
                            @foreach(\App\Models\AsScheduleIrrigation::TASK_TYPES as $slug => $label)
                                @php $tMeta = \App\Models\AsScheduleIrrigation::taskTypeMeta($slug); @endphp
                                <option value="{{ $slug }}" {{ $slug === 'irrigate' ? 'selected' : '' }}>
                                    {{ $tMeta['icon'] }} {{ $label }}
                                </option>
                            @endforeach
                        </select>
                        <small class="text-secondary">
                            <strong>Irrigate</strong> = fill · <strong>Maintain</strong> = hold ·
                            <strong>Overflow</strong> = flush · <strong>Drain</strong> = stop
                        </small>
                    </div>
                    <div class="col-md-5 mb-3">
                        <label class="form-label text-dark">Priority <span class="text-danger">*</span></label>
                        <select class="form-select" id="irrigationPriority">
                            <option value="1">1 — Critical</option>
                            <option value="2">2 — High</option>
                            <option value="3">3 — Medium</option>
                            <option value="4">4 — Low</option>
                            <option value="5" selected>5 — Lowest (default)</option>
                        </select>
                        <small class="text-secondary">
                            Lower number wins on overlapping days. e.g. a priority-1 "Drain" on
                            DAS 5 carves out a priority-5 "Irrigate" running DAS 1–10, splitting
                            it into DAS 1–4 and DAS 6–10 on the calendar.
                        </small>
                    </div>
                </div>
                <div class="mb-3">
                    <label class="form-label text-dark">Description</label>
                    <textarea class="form-control" id="irrigationDescription" rows="2"></textarea>
                </div>
                {{-- Range mode toggle: DAS (relative day numbers, anchored
                     to each group's Day 0) vs Date (absolute calendar dates).
                     Only the selected mode's inputs are visible / required;
                     the other mode's values are preserved but ignored. --}}
                <div class="mb-2">
                    <label class="form-label text-dark mb-1">Range mode <span class="text-danger">*</span></label>
                    <div class="btn-group w-100" role="group" aria-label="Irrigation range mode">
                        <input type="radio" class="btn-check" name="irrigationDayMode" id="irrigationDayModeDas" value="das" checked>
                        <label class="btn btn-outline-primary" for="irrigationDayModeDas">
                            <i class="bx bx-trending-up me-1"></i> DAS / DAT Day Range
                        </label>
                        <input type="radio" class="btn-check" name="irrigationDayMode" id="irrigationDayModeDate" value="date">
                        <label class="btn btn-outline-primary" for="irrigationDayModeDate">
                            <i class="bx bx-calendar me-1"></i> Specific Date Range
                        </label>
                    </div>
                </div>

                <div class="irrigation-mode-das row">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">Start Day (<span class="day-type-label">{{ $schedule->dayType }}</span>) <span class="text-danger">*</span></label>
                        <input type="number" step="1" class="form-control" id="irrigationStartDay" value="0">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">End Day (<span class="day-type-label">{{ $schedule->dayType }}</span>) <span class="text-danger">*</span></label>
                        <input type="number" step="1" class="form-control" id="irrigationEndDay" value="5">
                    </div>
                </div>
                <div class="irrigation-mode-date row" style="display:none;">
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">Start Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="irrigationStartDate">
                    </div>
                    <div class="col-md-6 mb-3">
                        <label class="form-label text-dark">End Date <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="irrigationEndDate">
                    </div>
                </div>
                <small class="text-secondary d-block mb-3">
                    <strong>DAS / DAT:</strong> day numbers relative to each lot group's Day 0 anchor (cycle repeats per group).
                    <strong>Date:</strong> fixed calendar dates, applies to every group on those exact days.
                    Day type set in <strong>Settings</strong>.
                </small>
                <div class="mb-3">
                    <label class="form-label text-dark">
                        <i class="bx bx-map-pin"></i> Lots this irrigation pertains to
                        <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                        <span class="text-secondary fw-normal d-block mt-1" style="font-size:12px;">
                            Tap to include/exclude. Leave empty if it applies to every lot.
                        </span>
                    </label>
                    <div class="alert alert-warning mb-0" id="irrigationLotsEmpty" @if($schedule->lots->count() > 0) style="display:none;" @endif>
                        <i class="bx bx-info-circle me-1"></i> No lots defined yet. Add them in the <strong>Lots</strong> tab.
                    </div>
                    <div class="lot-chip-container" id="irrigationLotsContainer" @if($schedule->lots->count() === 0) style="display:none;" @endif>
                        @foreach($schedule->lots as $lt)
                            <span class="lot-chip" data-lot-id="{{ $lt->id }}" role="button" aria-pressed="false">{{ $lt->lotName }}</span>
                        @endforeach
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label text-dark">
                        <i class="bx bx-user"></i> Workers assigned to this irrigation
                        <span class="badge bg-light text-secondary ms-1" style="font-weight:500;">Optional</span>
                        <span class="text-secondary fw-normal d-block mt-1" style="font-size:12px;">
                            Tap to include/exclude. Pick every worker who will handle this irrigation.
                        </span>
                    </label>
                    <div class="alert alert-warning mb-0" id="irrigationWorkersEmpty" @if($schedule->workers->count() > 0) style="display:none;" @endif>
                        <i class="bx bx-info-circle me-1"></i> No workers defined yet. Add them in the <strong>Workers</strong> tab.
                    </div>
                    <div class="lot-chip-container" id="irrigationWorkersContainer" @if($schedule->workers->count() === 0) style="display:none;" @endif>
                        @foreach($schedule->workers as $w)
                            <span class="lot-chip" data-worker-id="{{ $w->id }}" data-priority="{{ (int) $w->priority }}" role="button" aria-pressed="false">
                                {{ $w->workerName }}
                                <small class="text-muted ms-1">#{{ $w->priority }}</small>
                            </span>
                        @endforeach
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="saveIrrigationBtn"><i class="bx bx-save me-1"></i>Save Irrigation</button>
            </div>
        </div>
    </div>
</div>
