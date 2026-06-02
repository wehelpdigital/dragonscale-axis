// ---------- IRRIGATIONS ----------
// Catalog mirrored from AsScheduleIrrigation::TASK_TYPES / COLORS / ICONS so
// renderIrrigationRow can build the colored task-type badge without a server
// round trip after every save.
const IRR_TASK_LABELS = @json(\App\Models\AsScheduleIrrigation::TASK_TYPES);
const IRR_TASK_COLORS = @json(\App\Models\AsScheduleIrrigation::TASK_TYPE_COLORS);
const IRR_TASK_ICONS  = @json(\App\Models\AsScheduleIrrigation::TASK_TYPE_ICONS);

function irrTaskMeta(slug) {
    const key = (slug && IRR_TASK_LABELS[slug]) ? slug : 'irrigate';
    return {
        slug: key,
        label: IRR_TASK_LABELS[key],
        color: IRR_TASK_COLORS[key],
        icon:  IRR_TASK_ICONS[key],
    };
}

// Format a Y-m-d date as "MMM d, YYYY" without depending on toLocaleDateString
// quirks across browsers. Returns the raw input if it can't be parsed.
function _fmtIrrDate(iso, withYear) {
    if (!iso) return '';
    const d = parseLocalDate(iso);
    if (!d) return iso;
    const m = MONTH_SHORT[d.getMonth()];
    return withYear ? `${m} ${d.getDate()}, ${d.getFullYear()}` : `${m} ${d.getDate()}`;
}

// Resolve the array of {id, ...} entries the controller returns for the
// new pivots — Eloquent attaches them under the relation name as nested
// arrays, plus mirrors the pluck on the data payload as workerIds/lotIds.
function _irrPivotIds(i, relation) {
    if (Array.isArray(i[relation + 'Ids'])) return i[relation + 'Ids'].map(v => parseInt(v, 10));
    if (Array.isArray(i[relation])) return i[relation].map(r => parseInt(r.id, 10));
    return [];
}

function renderIrrigationRow(i) {
    const dayType = ($('.day-type-label').first().text() || 'DAS').trim();
    const meta = irrTaskMeta(i.taskType);
    const dayMode = (i.dayMode === 'date') ? 'date' : 'das';

    const priority = parseInt(i.priority, 10) || 5;
    const priorityBadge = `<span class="badge ms-1 irr-priority-pill irr-prio-${priority}" title="Priority ${priority} — lower number wins overlapping days">P${priority}</span>`;
    const taskBadge = `<span class="badge ms-1" style="background: ${meta.color}; color:#fff; font-weight:600; font-size:11px;">${meta.icon} ${escapeHtml(meta.label)}</span>`;
    const rangeBadge = (dayMode === 'date' && i.startDate && i.endDate)
        ? `<span class="badge bg-secondary text-white ms-1" style="font-weight:500; font-size:11px;"><i class="bx bx-calendar"></i> ${escapeHtml(_fmtIrrDate(i.startDate, false))} — ${escapeHtml(_fmtIrrDate(i.endDate, true))}</span>`
        : `<span class="badge bg-info text-white ms-1" style="font-weight:500; font-size:11px;"><span class="day-type-label">${escapeHtml(dayType)}</span> ${i.startDay} — ${i.endDay}</span>`;

    const lotIds    = _irrPivotIds(i, 'lot');
    const workerIds = _irrPivotIds(i, 'worker');
    const lotsArr    = Array.isArray(i.lots)    ? i.lots    : [];
    const workersArr = Array.isArray(i.workers) ? i.workers : [];

    let metaRows = '';
    if (lotsArr.length > 0) {
        metaRows += '<div class="irr-meta-row"><i class="bx bx-map-pin"></i>' +
            lotsArr.map(l => `<span class="irr-chip irr-chip-lot">${escapeHtml(l.lotName)}</span>`).join('') +
            '</div>';
    }
    if (workersArr.length > 0) {
        metaRows += '<div class="irr-meta-row"><i class="bx bx-user"></i>' +
            workersArr.map(w => `<span class="irr-chip irr-chip-worker">${escapeHtml(w.workerName)}</span>`).join('') +
            '</div>';
    }
    if (lotsArr.length === 0 && workersArr.length === 0) {
        metaRows = '<div class="irr-meta-row text-secondary"><small><i class="bx bx-info-circle me-1"></i>No lots / workers assigned</small></div>';
    }
    const descHtml = i.description
        ? `<div class="irr-description">${escapeHtml(i.description)}</div>`
        : '';

    return `<div class="irrigation-card" draggable="true"
                 data-id="${i.id}"
                 data-sort-order="${parseInt(i.sortOrder, 10) || 0}"
                 style="--irr-color: ${meta.color};">
        <div class="irr-drag-handle" title="Drag to reorder"><i class="bx bx-grid-vertical"></i></div>
        <div class="irr-card-body">
            <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
                <div class="flex-grow-1" style="min-width: 0;">
                    <h6 class="text-dark mb-1">
                        ${escapeHtml(i.irrigationTitle)}
                        ${priorityBadge}
                        ${taskBadge}
                        ${rangeBadge}
                    </h6>
                    ${metaRows}
                    ${descHtml}
                </div>
                <div class="d-flex align-items-center gap-1 flex-shrink-0">
                    <button class="btn btn-sm btn-outline-primary edit-irrigation-btn"
                            data-id="${i.id}"
                            data-title="${escapeHtml(i.irrigationTitle)}"
                            data-description="${escapeHtml(i.description || '')}"
                            data-day-mode="${escapeHtml(dayMode)}"
                            data-start-day="${i.startDay}"
                            data-end-day="${i.endDay}"
                            data-start-date="${escapeHtml(i.startDate || '')}"
                            data-end-date="${escapeHtml(i.endDate || '')}"
                            data-task-type="${escapeHtml(meta.slug)}"
                            data-priority="${priority}"
                            data-worker-id="${i.assignedWorkerId || ''}"
                            data-worker-ids="${workerIds.join(',')}"
                            data-lot-ids="${lotIds.join(',')}"
                            title="Edit"><i class="bx bx-edit-alt"></i></button>
                    <button class="btn btn-sm btn-outline-secondary duplicate-irrigation-btn"
                            data-id="${i.id}"
                            data-name="${escapeHtml(i.irrigationTitle)}"
                            title="Duplicate"><i class="bx bx-copy"></i></button>
                    <button class="btn btn-sm btn-outline-danger delete-irrigation-btn"
                            data-id="${i.id}"
                            data-name="${escapeHtml(i.irrigationTitle)}"
                            title="Delete"><i class="bx bx-trash"></i></button>
                </div>
            </div>
        </div>
    </div>`;
}

// Switch the visible input pair when the user toggles between the two
// modes inside the irrigation modal. Both sets of inputs stay in the DOM
// so already-entered values from one mode survive a flip-flip.
function setIrrigationMode(mode) {
    const isDate = mode === 'date';
    $('.irrigation-mode-das').toggle(!isDate);
    $('.irrigation-mode-date').toggle(isDate);
    $('#irrigationDayModeDas').prop('checked', !isDate);
    $('#irrigationDayModeDate').prop('checked', isDate);
}
$(document).on('change', 'input[name="irrigationDayMode"]', function () {
    setIrrigationMode($(this).val());
});

// Parse a comma-separated string of integer IDs (from data-* attributes)
// into a JS array. Empty / missing returns []. Used to pre-activate the
// lot + worker chips when the modal opens for editing.
function _parseIdList(raw) {
    if (raw === undefined || raw === null) return [];
    return String(raw).split(',')
        .map(s => s.trim())
        .filter(Boolean)
        .map(s => parseInt(s, 10))
        .filter(n => !isNaN(n));
}

// Apply an active set of IDs to a chip group. All chips are reset first
// so the modal doesn't carry stale selections from the previous open.
// Chips use `.active` + `aria-pressed="true"` to mark selection (mirrors
// the activity modal pattern so the .lot-chip CSS lights them up).
function _setIrrigationChips(containerSelector, attr, ids) {
    const idSet = new Set(ids);
    $(containerSelector + ' .lot-chip').each(function () {
        const cid = parseInt($(this).attr(attr), 10);
        const on  = idSet.has(cid);
        $(this).toggleClass('active', on).attr('aria-pressed', on ? 'true' : 'false');
    });
}

// Read the currently-selected IDs from a chip group.
function _getIrrigationChipIds(containerSelector, attr) {
    return $(containerSelector + ' .lot-chip.active').map(function () {
        return parseInt($(this).attr(attr), 10);
    }).get().filter(n => !isNaN(n));
}

// Toggle chip on click — same interaction as the activity modal.
$(document).on('click', '#irrigationLotsContainer .lot-chip', function () {
    $(this).toggleClass('active');
    $(this).attr('aria-pressed', $(this).hasClass('active') ? 'true' : 'false');
});
$(document).on('click', '#irrigationWorkersContainer .lot-chip', function () {
    $(this).toggleClass('active');
    $(this).attr('aria-pressed', $(this).hasClass('active') ? 'true' : 'false');
});

$('#addIrrigationBtn').on('click', function () {
    $('#irrigationModalTitle').text('Add Irrigation');
    $('#irrigationId').val('');
    $('#irrigationTitle').val('');
    $('#irrigationDescription').val('');
    $('#irrigationStartDay').val(0);
    $('#irrigationEndDay').val(5);
    $('#irrigationStartDate').val('');
    $('#irrigationEndDate').val('');
    $('#irrigationTaskType').val('irrigate');
    $('#irrigationPriority').val(5);
    _setIrrigationChips('#irrigationLotsContainer',    'data-lot-id',    []);
    _setIrrigationChips('#irrigationWorkersContainer', 'data-worker-id', []);
    setIrrigationMode('das');
});

$(document).on('click', '.edit-irrigation-btn', function () {
    $('#irrigationModalTitle').text('Edit Irrigation');
    $('#irrigationId').val($(this).data('id'));
    $('#irrigationTitle').val($(this).data('title'));
    $('#irrigationDescription').val($(this).data('description'));
    $('#irrigationStartDay').val($(this).data('start-day'));
    $('#irrigationEndDay').val($(this).data('end-day'));
    $('#irrigationStartDate').val($(this).data('start-date') || '');
    $('#irrigationEndDate').val($(this).data('end-date') || '');
    $('#irrigationTaskType').val($(this).data('task-type') || 'irrigate');
    $('#irrigationPriority').val(parseInt($(this).data('priority'), 10) || 5);
    _setIrrigationChips('#irrigationLotsContainer',    'data-lot-id',    _parseIdList($(this).attr('data-lot-ids')));
    _setIrrigationChips('#irrigationWorkersContainer', 'data-worker-id', _parseIdList($(this).attr('data-worker-ids')));
    setIrrigationMode($(this).data('day-mode') || 'das');
    $('#irrigationModal').modal('show');
});

$('#saveIrrigationBtn').on('click', function () {
    const id = $('#irrigationId').val();
    const dayMode = $('input[name="irrigationDayMode"]:checked').val() === 'date' ? 'date' : 'das';
    const workerIds = _getIrrigationChipIds('#irrigationWorkersContainer', 'data-worker-id');
    const lotIds    = _getIrrigationChipIds('#irrigationLotsContainer',    'data-lot-id');
    const payload = {
        _token: CSRF,
        irrigationTitle: $('#irrigationTitle').val(),
        description: $('#irrigationDescription').val(),
        dayMode: dayMode,
        // Send both modes' values; the server uses whichever matches dayMode
        // and ignores the other set. This means flipping the toggle and
        // saving doesn't wipe whatever the user previously entered in the
        // other mode — only the active mode's values land in the DB.
        startDay: $('#irrigationStartDay').val(),
        endDay: $('#irrigationEndDay').val(),
        startDate: $('#irrigationStartDate').val() || null,
        endDate: $('#irrigationEndDate').val() || null,
        taskType: $('#irrigationTaskType').val() || 'irrigate',
        priority: parseInt($('#irrigationPriority').val(), 10) || 5,
        // Legacy single-worker column — controller back-fills it from the
        // first picked worker, but include here as null so a stale value
        // never lingers on the row.
        assignedWorkerId: null,
        workerIds: workerIds,
        lotIds: lotIds,
    };
    if (!payload.irrigationTitle) { toastr.warning('Title is required'); return; }
    if (dayMode === 'das' && parseInt(payload.endDay) < parseInt(payload.startDay)) {
        toastr.warning('End day must be >= Start day');
        return;
    }
    if (dayMode === 'date') {
        if (!payload.startDate || !payload.endDate) {
            toastr.warning('Pick both start and end dates.');
            return;
        }
        if (payload.endDate < payload.startDate) {
            toastr.warning('End date must be on or after the start date.');
            return;
        }
    }
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: id ? URLS.irrigationsUpdate(id) : URLS.irrigationsStore(),
        type: id ? 'PUT' : 'POST',
        data: payload,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $('#irrigationModal').modal('hide');
            const html = renderIrrigationRow(res.data);
            if (id) {
                $('#irrigationsList .irrigation-card[data-id="' + id + '"]').replaceWith(html);
            } else {
                $('#irrigationsEmpty').remove();
                $('#irrigationsList').append(html);
                bumpBadge('badge-irrigations', 1);
            }
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Irrigation')
    });
});

$(document).on('click', '.duplicate-irrigation-btn', function () {
    const id   = $(this).data('id');
    const name = $(this).data('name');
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');
    $.ajax({
        url:  URLS.irrigationsDuplicate(id),
        type: 'POST',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message || 'Duplicate failed'); return; }
            toastr.success('Duplicated "' + name + '". Edit and save when ready.');
            const html = renderIrrigationRow(res.data);
            $('#irrigationsEmpty').remove();
            $('#irrigationsList').append(html);
            bumpBadge('badge-irrigations', 1);
            // Open the copy for immediate editing — mirrors the activities
            // duplicate flow so the user can adjust the new entry right away.
            $('.edit-irrigation-btn[data-id="' + res.data.id + '"]').first().trigger('click');
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Duplicate failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-copy"></i>')
    });
});

$(document).on('click', '.delete-irrigation-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    confirmAction({
        title: 'Delete irrigation',
        message: 'Delete irrigation <strong>"' + escapeHtml(name) + '"</strong>?',
        detail: 'The irrigation entry and its day-range schedule will be removed.',
        confirmText: 'Delete Irrigation',
        onConfirm: () => {
            $.ajax({
                url: URLS.irrigationsDelete(id),
                type: 'DELETE',
                data: { _token: CSRF },
                success: (res) => {
                    if (!res.success) { toastr.error(res.message); return; }
                    toastr.success(res.message);
                    $('#irrigationsList .irrigation-card[data-id="' + id + '"]').fadeOut(300, function () {
                        $(this).remove();
                        bumpBadge('badge-irrigations', -1);
                    });
                }
            });
        }
    });
});

// ---------- DRAG-DROP REORDER ----------
// HTML5 native drag-and-drop for the .irrigation-card list. Mirrors the
// activity-card drag pattern. After a drop, every card's sortOrder is
// renumbered 1..N and the new sequence is POSTed once so the server has
// the canonical order. No optimistic vs pessimistic UI — the DOM is
// already in the correct order, the server just persists it.

let _irrDragging = null;

$(document).on('dragstart', '#irrigationsList .irrigation-card', function (e) {
    _irrDragging = this;
    $(this).addClass('dragging');
    // Required so Firefox actually fires the dragstart event.
    if (e.originalEvent && e.originalEvent.dataTransfer) {
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        e.originalEvent.dataTransfer.setData('text/plain', $(this).data('id'));
    }
});

$(document).on('dragend', '#irrigationsList .irrigation-card', function () {
    $(this).removeClass('dragging');
    $('#irrigationsList .irrigation-card').removeClass('drag-over');
    _irrDragging = null;
});

$(document).on('dragover', '#irrigationsList .irrigation-card', function (e) {
    if (!_irrDragging || _irrDragging === this) return;
    e.preventDefault();
    if (e.originalEvent && e.originalEvent.dataTransfer) {
        e.originalEvent.dataTransfer.dropEffect = 'move';
    }
    $('#irrigationsList .irrigation-card').not(this).removeClass('drag-over');
    $(this).addClass('drag-over');
});

$(document).on('dragleave', '#irrigationsList .irrigation-card', function () {
    $(this).removeClass('drag-over');
});

$(document).on('drop', '#irrigationsList .irrigation-card', function (e) {
    e.preventDefault();
    $(this).removeClass('drag-over');
    if (!_irrDragging || _irrDragging === this) return;

    // Insert the dragged card BEFORE the drop target. Simple and matches
    // the activity-card reorder behavior — users can always drag again to
    // fine-tune position.
    $(this).before(_irrDragging);

    _persistIrrigationOrder();
});

function _persistIrrigationOrder() {
    const items = $('#irrigationsList .irrigation-card[data-id]').map(function (idx) {
        const id = parseInt($(this).data('id'), 10);
        const sortOrder = idx + 1;
        $(this).attr('data-sort-order', sortOrder);
        return { id: id, sortOrder: sortOrder };
    }).get();
    if (items.length === 0) return;

    $.ajax({
        url:  URLS.irrigationsReorder(),
        type: 'POST',
        data: { _token: CSRF, items: items },
        success: (res) => {
            if (!res || !res.success) {
                toastr.error((res && res.message) || 'Could not save the new order');
                return;
            }
            // Quiet success — drag-drop is a high-frequency action so a
            // toastr on every drop becomes noise. Only failures pop up.
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Could not save the new order')
    });
}
