// ---------- SETTINGS ----------
const ALL_LOTS_FOR_GROUPS = @json($schedule->lots->map(fn($l) => ['id' => $l->id, 'name' => $l->lotName])->values());
let defaultGroupIdx = 0;

function addDefaultGroup(preset) {
    const idx = defaultGroupIdx++;
    const presetLotIds = (preset?.lotIds || []).map(Number);
    const startMode = preset?.startDate ? 'date' : 'stagger';
    const staggerValue = preset?.staggerDays ?? 0;
    const dateValue = preset?.startDate || '';

    const $card = $(`
        <div class="default-group-card border rounded p-2 mb-2" data-idx="${idx}" style="background:#fafbfd;">
            <div class="d-flex justify-content-between align-items-center mb-2 gap-2 flex-wrap">
                <input type="text" class="form-control form-control-sm group-name" style="max-width:240px;" placeholder="Group name" value="${escapeHtml(preset?.groupName || ('Group ' + (idx + 1)))}">
                <button type="button" class="btn btn-sm btn-outline-danger remove-default-group-btn"><i class="bx bx-trash"></i></button>
            </div>
            <div class="row g-2 align-items-center mb-2">
                <div class="col-auto">
                    <small class="text-secondary">Start mode:</small>
                </div>
                <div class="col-auto">
                    <div class="btn-group btn-group-sm start-mode-group" role="group">
                        <input type="radio" class="btn-check start-mode" name="defaultGroupMode-${idx}" id="defaultGroupModeStagger-${idx}" value="stagger" autocomplete="off" ${startMode === 'stagger' ? 'checked' : ''}>
                        <label class="btn btn-outline-primary" for="defaultGroupModeStagger-${idx}">Staggered</label>
                        <input type="radio" class="btn-check start-mode" name="defaultGroupMode-${idx}" id="defaultGroupModeDate-${idx}" value="date" autocomplete="off" ${startMode === 'date' ? 'checked' : ''}>
                        <label class="btn btn-outline-primary" for="defaultGroupModeDate-${idx}">Specific date</label>
                    </div>
                </div>
                <div class="col-auto stagger-input" ${startMode !== 'stagger' ? 'style="display:none;"' : ''}>
                    <div class="input-group input-group-sm" style="max-width:180px;">
                        <span class="input-group-text">Stagger</span>
                        <input type="number" min="0" step="1" class="form-control group-stagger" value="${staggerValue}">
                        <span class="input-group-text">days</span>
                    </div>
                </div>
                <div class="col-auto date-input" ${startMode !== 'date' ? 'style="display:none;"' : ''}>
                    <input type="date" class="form-control form-control-sm group-start-date" style="max-width:170px;" value="${escapeHtml(dateValue)}">
                </div>
            </div>
            <label class="form-label text-dark small mb-1">
                <i class="bx bx-pointer"></i> Lots in this group
                <span class="text-secondary fw-normal">— tap a lot to include/exclude it</span>
            </label>
            <div class="lot-chip-container">
                ${ALL_LOTS_FOR_GROUPS.map(l => {
                    const active = presetLotIds.includes(Number(l.id));
                    return `<span class="lot-chip ${active ? 'active' : ''}" data-lot-id="${l.id}" role="button" aria-pressed="${active}">${escapeHtml(l.name)}</span>`;
                }).join('')}
                ${ALL_LOTS_FOR_GROUPS.length === 0 ? '<small class="text-secondary">No lots yet.</small>' : ''}
            </div>
        </div>
    `);
    $('#defaultGroupsContainer').append($card);
}

// Toggle stagger vs. date inputs per group
$(document).on('change', '#defaultGroupsContainer .start-mode', function () {
    const $card = $(this).closest('.default-group-card');
    const mode = $(this).val();
    $card.find('.stagger-input').toggle(mode === 'stagger');
    $card.find('.date-input').toggle(mode === 'date');
});

$('#addDefaultGroupBtn').on('click', function () {
    if (ALL_LOTS_FOR_GROUPS.length === 0) { toastr.warning('Add at least one lot first.'); return; }
    addDefaultGroup();
});

$(document).on('click', '.remove-default-group-btn', function () {
    $(this).closest('.default-group-card').remove();
});

$(document).on('click', '#defaultGroupsContainer .lot-chip', function () {
    const $chip = $(this);
    $chip.toggleClass('active');
    $chip.attr('aria-pressed', $chip.hasClass('active') ? 'true' : 'false');
});

// Initial population from server
@php $existingDefaults = $schedule->defaultGroupings->map(fn($g) => [
    'groupName' => $g->groupName,
    'staggerDays' => $g->staggerDays,
    'startDate' => $g->startDate ? $g->startDate->format('Y-m-d') : null,
    'lotIds' => $g->lots->pluck('id'),
]); @endphp
const EXISTING_DEFAULT_GROUPS = @json($existingDefaults);

$(function () {
    EXISTING_DEFAULT_GROUPS.forEach(g => addDefaultGroup(g));
});

// Save Basic Info (title, description, dayType, defaultStaggerDays)
$('#saveSettingsBasicBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const payload = {
        _token: CSRF,
        title: $('#settingsTitle').val(),
        description: $('#settingsDescription').val(),
        dayType: $('#settingsDayType').val(),
        defaultStaggerDays: $('#settingsDefaultStaggerDays').val() || 0,
    };
    if (!payload.title) { toastr.warning('Title is required'); $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Basic Info'); return; }

    $.ajax({
        url: URLS.scheduleUpdate(),
        type: 'PUT',
        data: payload,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            // Live-update header + every dayType label across the page
            $('#scheduleHeaderTitle').text(payload.title);
            $('#scheduleHeaderDescription').text(payload.description || 'No description provided.');
            $('.day-type-label').text(payload.dayType);
            document.title = 'Setup — ' + payload.title;
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Basic Info')
    });
});

// Save Default Groupings
$('#saveDefaultGroupingsBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const groupings = [];
    let invalid = false;
    $('#defaultGroupsContainer .default-group-card').each(function () {
        const name = ($(this).find('.group-name').val() || '').trim();
        const mode = $(this).find('.start-mode:checked').val() || 'stagger';
        const staggerDays = parseInt($(this).find('.group-stagger').val(), 10) || 0;
        const startDate = ($(this).find('.group-start-date').val() || '').trim();
        const lotIds = $(this).find('.lot-chip.active').map((_, e) => parseInt($(e).data('lot-id'), 10)).get();
        if (!name) { invalid = true; return false; }
        if (mode === 'date' && !startDate) { invalid = true; return false; }
        groupings.push({
            name,
            staggerDays: mode === 'stagger' ? staggerDays : 0,
            startDate: mode === 'date' ? startDate : null,
            lotIds,
        });
    });
    if (invalid) {
        toastr.warning('Every group needs a name and (if "Specific date" is selected) a start date.');
        $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Default Groupings');
        return;
    }

    $.ajax({
        url: `${ROOT}/anisenso-schedule-manager-default-groupings-save?scheduleId=${SCHEDULE_ID}`,
        type: 'POST',
        data: { _token: CSRF, groupings },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Default Groupings')
    });
});
