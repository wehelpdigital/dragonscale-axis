@extends('layouts.master')

@section('title') Generate Calendar — {{ $schedule->title }} @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .group-card { background:#fafbfd; border:1px solid #e6e8ec; border-radius:8px; padding:14px; margin-bottom:12px; }
    .lot-chip { display:inline-block; padding:4px 10px; margin:3px; border-radius:20px; background:#fff; border:1px solid #d3d6db; cursor:pointer; font-size:12px; color:#495057; }
    .lot-chip.active { background:#556ee6; color:#fff; border-color:#556ee6; }
    .day-pill { display:inline-block; padding:4px 10px; margin:2px; border-radius:20px; background:#f1f1f1; cursor:pointer; font-size:12px; color:#495057; }
    .day-pill.active { background:#556ee6; color:#fff; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') <a href="{{ route('anisenso-schedule-manager.index') }}" class="text-decoration-none">Schedule Manager</a> @endslot
        @slot('title') Generate — {{ $schedule->title }} @endslot
    @endcomponent

    @if(session('error'))<div class="alert alert-danger"><i class="bx bx-error-circle me-2"></i>{{ session('error') }}</div>@endif
    @if(session('info'))<div class="alert alert-info"><i class="bx bx-info-circle me-2"></i>{{ session('info') }}</div>@endif

    @if(!empty($issues))
        <div class="alert alert-warning">
            <strong><i class="bx bx-error me-1"></i> Cannot generate yet.</strong>
            Please set up: {{ implode(', ', $issues) }}.
            <a href="{{ route('anisenso-schedule-manager.setup', ['id' => $schedule->id]) }}" class="alert-link">Go to setup &rarr;</a>
        </div>
    @endif

    @php $current = $schedule->currentGeneration; @endphp

    <form method="POST" action="{{ $current ? route('anisenso-schedule-manager.generate.regenerate', ['scheduleId' => $schedule->id]) : route('anisenso-schedule-manager.generate.run', ['scheduleId' => $schedule->id]) }}" id="generateForm">
        @csrf

        <div class="row">
            <div class="col-lg-7">
                <div class="card">
                    <div class="card-body">
                        <h5 class="text-dark mb-1">{{ $current ? 'Regenerate Calendar' : 'Generate Calendar' }}</h5>
                        <small class="text-secondary d-block mb-3">
                            @if($current)
                                A previous calendar exists (gen #{{ $current->generationNumber }}). Regenerating will preserve completed activities and replan everything else with the latest setup data.
                            @else
                                Configure the inputs below and we'll generate your full cropping calendar respecting worker availability and your off-day rules.
                            @endif
                        </small>

                        <div class="mb-3">
                            <label class="form-label text-dark">Season Start Date <span class="text-danger">*</span></label>
                            <input type="date" name="seasonStartDate" class="form-control @error('seasonStartDate') is-invalid @enderror"
                                   value="{{ old('seasonStartDate', $current?->seasonStartDate?->format('Y-m-d') ?? now('Asia/Manila')->toDateString()) }}" required>
                            <small class="text-secondary">Day 0 (basal / planting day). All DAP/DAS/DAT offsets are computed from this date plus the group's stagger.</small>
                            @error('seasonStartDate')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="mb-3">
                            <label class="form-label text-dark">Notes</label>
                            <textarea name="notes" class="form-control" rows="2" placeholder="Optional notes about this run">{{ old('notes', $current?->notes) }}</textarea>
                        </div>

                        <hr>

                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h6 class="text-dark mb-0">Groupings of Lots</h6>
                            <button type="button" class="btn btn-sm btn-outline-primary" id="addGroupBtn"><i class="bx bx-plus"></i> Add Group</button>
                        </div>
                        <small class="text-secondary d-block mb-2">Group lots that should share the same schedule. Use the stagger days to offset a group's start (e.g. 7 days after the season start).</small>
                        <div id="groupsContainer"></div>
                        @error('groupings')<div class="text-danger small">{{ $message }}</div>@enderror
                        @error('groupings.*.lotIds')<div class="text-danger small">Each group needs at least one lot.</div>@enderror
                    </div>
                </div>
            </div>

            <div class="col-lg-5">
                <div class="card">
                    <div class="card-body">
                        <h6 class="text-dark mb-2">Global Unavailable Dates</h6>
                        <small class="text-secondary d-block mb-2">Specific calendar dates where no work happens (holidays, festivals, etc.).</small>
                        <div class="row g-2 align-items-end mb-2">
                            <div class="col-7"><input type="date" class="form-control" id="newGenOffDate"></div>
                            <div class="col-5"><button type="button" class="btn btn-outline-primary w-100" id="addGenOffDateBtn"><i class="bx bx-plus"></i> Add</button></div>
                        </div>
                        <div id="genOffDatesList" class="d-flex flex-wrap gap-2 mb-3"></div>

                        <hr>
                        <h6 class="text-dark mb-2">Global Off Days of Week</h6>
                        <small class="text-secondary d-block mb-2">Tap a day to mark it unavailable for the whole season.</small>
                        <div id="genOffDays">
                            @php $dayNames = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat']; @endphp
                            @foreach($dayNames as $i => $d)
                                <span class="day-pill" data-day="{{ $i }}">{{ $d }}</span>
                            @endforeach
                        </div>

                        <hr>
                        <div class="d-flex justify-content-end">
                            <a href="{{ route('anisenso-schedule-manager.setup', ['id' => $schedule->id]) }}" class="btn btn-light me-2">Back</a>
                            <button type="submit" class="btn btn-primary" id="generateSubmitBtn" @if(!empty($issues)) disabled @endif>
                                <i class="bx bx-magic-wand me-1"></i> {{ $current ? 'Regenerate' : 'Generate' }}
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
toastr.options = { closeButton: true, progressBar: true, positionClass: "toast-top-right", timeOut: 3000 };

const ALL_LOTS = @json($schedule->lots->map(fn($l) => ['id' => $l->id, 'name' => $l->lotName])->values());
const SCHEDULE_DEFAULT_STAGGER = {{ (int) ($schedule->defaultStaggerDays ?? 0) }};
const EXISTING = @json([
    'groupings' => $schedule->currentGeneration?->groupings?->map(fn($g) => [
        'name' => $g->groupName,
        'staggerDays' => $g->staggerDays,
        'startDate' => $g->startDate ? $g->startDate->format('Y-m-d') : null,
        'lotIds' => $g->lots->pluck('id'),
    ]) ?? [],
    'offDates' => $schedule->currentGeneration?->offDates?->map(fn($d) => \Illuminate\Support\Carbon::parse($d->offDate)->toDateString()) ?? [],
    'offDays'  => $schedule->currentGeneration?->offDays?->pluck('dayOfWeek') ?? [],
]);
// Schedule-level defaults — used to pre-populate the form when there's no prior generation.
const SCHEDULE_DEFAULTS = @json([
    'groupings' => $schedule->defaultGroupings->map(fn($g) => [
        'name' => $g->groupName,
        'staggerDays' => $g->staggerDays,
        'startDate' => $g->startDate ? $g->startDate->format('Y-m-d') : null,
        'lotIds' => $g->lots->pluck('id'),
    ])->values(),
]);

let groupIdx = 0;

function escapeHtml(s) {
    return String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
}

function addGroup(preset) {
    const idx = groupIdx++;
    const startMode = preset?.startDate ? 'date' : 'stagger';
    const staggerValue = preset?.staggerDays ?? 0;
    const dateValue = preset?.startDate || '';

    const $card = $(`
        <div class="group-card" data-idx="${idx}">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h6 class="text-dark mb-0">Group ${idx + 1}</h6>
                <button type="button" class="btn btn-sm btn-outline-danger remove-group-btn" data-idx="${idx}"><i class="bx bx-trash"></i></button>
            </div>
            <div class="row mb-2">
                <div class="col-md-12">
                    <label class="form-label text-dark small">Name <span class="text-danger">*</span></label>
                    <input type="text" name="groupings[${idx}][name]" class="form-control form-control-sm" value="${escapeHtml(preset?.name || ('Group ' + (idx + 1)))}" required>
                </div>
            </div>
            <div class="row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <small class="text-secondary">Start:</small>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm" role="group">
                        <input type="radio" class="btn-check gen-start-mode" name="genGroupMode-${idx}" id="genGroupModeStagger-${idx}" value="stagger" autocomplete="off" ${startMode === 'stagger' ? 'checked' : ''}>
                        <label class="btn btn-outline-primary" for="genGroupModeStagger-${idx}">Staggered</label>
                        <input type="radio" class="btn-check gen-start-mode" name="genGroupMode-${idx}" id="genGroupModeDate-${idx}" value="date" autocomplete="off" ${startMode === 'date' ? 'checked' : ''}>
                        <label class="btn btn-outline-primary" for="genGroupModeDate-${idx}">Specific date</label>
                    </div>
                </div>
                <div class="col-auto stagger-input" ${startMode !== 'stagger' ? 'style="display:none;"' : ''}>
                    <div class="input-group input-group-sm" style="max-width:200px;">
                        <span class="input-group-text">+</span>
                        <input type="number" min="0" step="1" name="groupings[${idx}][staggerDays]" class="form-control form-control-sm group-stagger" value="${staggerValue}">
                        <span class="input-group-text">days from season start</span>
                    </div>
                </div>
                <div class="col-auto date-input" ${startMode !== 'date' ? 'style="display:none;"' : ''}>
                    <input type="date" name="groupings[${idx}][startDate]" class="form-control form-control-sm group-start-date" style="max-width:180px;" value="${escapeHtml(dateValue)}">
                </div>
            </div>
            <label class="form-label text-dark small">Lots in this group <span class="text-danger">*</span></label>
            <div class="lot-chip-container" data-idx="${idx}"></div>
        </div>
    `);
    $('#groupsContainer').append($card);

    const $lotContainer = $card.find('.lot-chip-container');
    const presetLotIds = (preset?.lotIds || []).map(Number);
    ALL_LOTS.forEach(lot => {
        const isActive = presetLotIds.includes(Number(lot.id));
        const chip = $(`<span class="lot-chip ${isActive ? 'active' : ''}" data-lot-id="${lot.id}">${escapeHtml(lot.name)}</span>`);
        if (isActive) {
            chip.append(`<input type="hidden" name="groupings[${idx}][lotIds][]" value="${lot.id}">`);
        }
        $lotContainer.append(chip);
    });
}

$(document).on('click', '.lot-chip', function () {
    const $chip = $(this);
    const groupIdx = $chip.closest('.lot-chip-container').data('idx');
    const lotId = $chip.data('lot-id');
    $chip.toggleClass('active');
    if ($chip.hasClass('active')) {
        $chip.append(`<input type="hidden" name="groupings[${groupIdx}][lotIds][]" value="${lotId}">`);
    } else {
        $chip.find('input').remove();
    }
});

// Toggle stagger vs. specific-date per group. Also disable the unused input so
// it doesn't get submitted with a stale value.
$(document).on('change', '#groupsContainer .gen-start-mode', function () {
    const $card = $(this).closest('.group-card');
    const mode = $(this).val();
    $card.find('.stagger-input').toggle(mode === 'stagger');
    $card.find('.date-input').toggle(mode === 'date');
    $card.find('.group-stagger').prop('disabled', mode !== 'stagger');
    $card.find('.group-start-date').prop('disabled', mode !== 'date');
});

$('#addGroupBtn').on('click', () => addGroup());

$(document).on('click', '.remove-group-btn', function () {
    $(this).closest('.group-card').remove();
});

// Off dates
function appendOffDatePill(d) {
    if (!d) return;
    const dateStr = (typeof d === 'string') ? d.slice(0,10) : d;
    if ($('#genOffDatesList span[data-date="' + dateStr + '"]').length) return;
    $('#genOffDatesList').append(`
        <span class="badge bg-light text-dark p-2" data-date="${dateStr}">
            ${dateStr}
            <a href="javascript:void(0);" class="text-danger ms-2 remove-gen-off-date">&times;</a>
            <input type="hidden" name="offDates[]" value="${dateStr}">
        </span>
    `);
}

$('#addGenOffDateBtn').on('click', function () {
    const v = $('#newGenOffDate').val();
    if (!v) { toastr.warning('Pick a date first'); return; }
    appendOffDatePill(v);
    $('#newGenOffDate').val('');
});

$(document).on('click', '.remove-gen-off-date', function () { $(this).closest('span').remove(); });

// Off days
$('#genOffDays').on('click', '.day-pill', function () {
    $(this).toggleClass('active');
    $(this).find('input').remove();
    if ($(this).hasClass('active')) {
        $(this).append(`<input type="hidden" name="offDays[]" value="${$(this).data('day')}">`);
    }
});

// Validate submission
$('#generateForm').on('submit', function (e) {
    if ($('#groupsContainer .group-card').length === 0) {
        e.preventDefault();
        toastr.error('Add at least one group of lots.');
        return false;
    }
    let problem = null;
    $('#groupsContainer .group-card').each(function () {
        if ($(this).find('.lot-chip.active').length === 0) {
            problem = 'Each group must contain at least one lot.';
            return false;
        }
        const mode = $(this).find('.gen-start-mode:checked').val() || 'stagger';
        if (mode === 'date' && !($(this).find('.group-start-date').val() || '').trim()) {
            problem = 'Pick a start date for every group set to "Specific date".';
            return false;
        }
    });
    if (problem) {
        e.preventDefault();
        toastr.error(problem);
        return false;
    }
    // Make sure the inactive input isn't submitted (e.g. an empty date with a stagger group).
    $('#groupsContainer .group-card').each(function () {
        const mode = $(this).find('.gen-start-mode:checked').val() || 'stagger';
        $(this).find('.group-stagger').prop('disabled', mode !== 'stagger');
        $(this).find('.group-start-date').prop('disabled', mode !== 'date');
    });
    $('#generateSubmitBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Generating...');
});

// Init
$(function () {
    if (EXISTING.groupings && EXISTING.groupings.length) {
        // Prior generation exists — replay its config.
        EXISTING.groupings.forEach(g => addGroup(g));
    } else if (SCHEDULE_DEFAULTS.groupings && SCHEDULE_DEFAULTS.groupings.length) {
        // Schedule-level defaults defined in Settings — use them.
        SCHEDULE_DEFAULTS.groupings.forEach(g => addGroup(g));
    } else if (ALL_LOTS.length) {
        // Fallback: one group containing every lot, using the schedule's default stagger.
        addGroup({ name: 'All Lots', staggerDays: SCHEDULE_DEFAULT_STAGGER, lotIds: ALL_LOTS.map(l => l.id) });
    }
    (EXISTING.offDates || []).forEach(d => appendOffDatePill(d));
    (EXISTING.offDays  || []).forEach(d => {
        const $p = $('#genOffDays .day-pill[data-day="' + d + '"]');
        $p.addClass('active').append(`<input type="hidden" name="offDays[]" value="${d}">`);
    });
});
</script>
@endsection
