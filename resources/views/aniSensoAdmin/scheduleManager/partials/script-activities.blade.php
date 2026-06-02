// ---------- CHANGE GROUP DATE (bulk-move every card on a single date) ----------
function _isoAddDays(iso, days) {
    const d = parseLocalDate(iso);
    if (!d) return '';
    d.setDate(d.getDate() + days);
    const y = d.getFullYear();
    const m = String(d.getMonth() + 1).padStart(2, '0');
    const day = String(d.getDate()).padStart(2, '0');
    return `${y}-${m}-${day}`;
}
function _isoDaysBetween(a, b) {
    const da = parseLocalDate(a);
    const db = parseLocalDate(b);
    if (!da || !db) return 0;
    return Math.round((db - da) / 86400000);
}

$(document).on('click', '.change-group-date-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const oldDate = ($(this).attr('data-date') || '').trim();
    if (!oldDate) return;
    const cards = $('#activitiesList .date-group[data-date="' + oldDate + '"] .activity-card[data-id]');
    $('#changeGroupDateCount').text(cards.length);
    const dateObj = parseLocalDate(oldDate);
    const pretty = dateObj
        ? `${DAY_SHORT[dateObj.getDay()]}, ${MONTH_SHORT[dateObj.getMonth()]} ${dateObj.getDate()}, ${dateObj.getFullYear()}`
        : oldDate;
    $('#changeGroupDateCurrent').text(pretty);
    $('#changeGroupDateOld').val(oldDate);
    $('#changeGroupDateNew').val(oldDate);
    $('#changeGroupDateModal').modal('show');
});

$(document).on('click', '#confirmChangeGroupDateBtn', function () {
    const oldDate = ($('#changeGroupDateOld').val() || '').trim();
    const newDate = ($('#changeGroupDateNew').val() || '').trim();
    if (!oldDate) { toastr.error('Missing source date.'); return; }
    if (!newDate) { toastr.warning('Pick a new date.'); return; }
    if (newDate === oldDate) {
        toastr.info('That is already the current date.');
        return;
    }

    const delta = _isoDaysBetween(oldDate, newDate);
    const $cards = $('#activitiesList .date-group[data-date="' + oldDate + '"] .activity-card[data-id]');
    if ($cards.length === 0) {
        toastr.warning('No activities to move.');
        $('#changeGroupDateModal').modal('hide');
        return;
    }

    // Snapshot for undo: capture the entire board (every date group) so we
    // can revert in one shot via /reorder.
    const undoSnapshot = captureBoardSnapshot();

    // Build the new items payload — only the cards in the target group need
    // their dates rewritten; sequenceOrder stays put.
    const items = [];
    $cards.each(function () {
        const id = parseInt($(this).attr('data-id'), 10);
        const oldEnd = ($(this).attr('data-target-end-date') || '').trim();
        const newEnd = oldEnd ? _isoAddDays(oldEnd, delta) : null;
        items.push({
            id: id,
            targetDate: newDate,
            targetEndDate: newEnd || null,
            sequenceOrder: parseInt($(this).attr('data-sequence-order'), 10) || 0,
        });
    });

    const $btn = $('#confirmChangeGroupDateBtn').prop('disabled', true)
        .html('<i class="bx bx-loader-alt bx-spin"></i> Moving...');

    $.ajax({
        url: URLS.activitiesReorder(),
        type: 'POST',
        data: { _token: CSRF, items: items },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            // Reflect new dates on the cards, then regroup so the new
            // date section (with its own color) renders.
            items.forEach(it => {
                const $c = $('#activitiesList [data-id="' + it.id + '"]');
                $c.attr('data-target-date', it.targetDate);
                $c.attr('data-target-end-date', it.targetEndDate || '');
            });
            reorderAndRenumberActivities();
            if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
            $('#changeGroupDateModal').modal('hide');
            toastr.success(`Moved ${items.length} ${items.length === 1 ? 'activity' : 'activities'} to ${newDate}`);

            // Undo: replay the pre-change snapshot through /reorder.
            const snap = undoSnapshot.slice();
            pushUndo(`Move group from ${oldDate} → ${newDate}`, async () => {
                const r = await $.ajax({
                    url: URLS.activitiesReorder(),
                    type: 'POST',
                    data: { _token: CSRF, items: snap },
                });
                if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                snap.forEach(it => {
                    const $c = $('#activitiesList [data-id="' + it.id + '"]');
                    $c.attr('data-target-date', it.targetDate || '');
                    $c.attr('data-target-end-date', it.targetEndDate || '');
                    $c.attr('data-sequence-order', it.sequenceOrder);
                });
                reorderAndRenumberActivities();
                if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
            });
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Move failed'),
        complete: () => $btn.prop('disabled', false)
            .html('<i class="bx bx-calendar-check me-1"></i> Move Activities')
    });
});

// ---------- DELETE WHOLE DATE GROUP (every card on a single date) ----------
$(document).on('click', '.delete-group-date-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const dateKey = ($(this).attr('data-date') || '').trim();
    if (!dateKey) return;

    const $cards = $('#activitiesList .date-group[data-date="' + dateKey + '"] .activity-card[data-id]');
    if ($cards.length === 0) {
        toastr.warning('No activities to delete in this group.');
        return;
    }

    // Snapshot every card in the group so the undo can restore by id.
    const targets = $cards.map(function () {
        return {
            id: parseInt($(this).attr('data-id'), 10),
            name: ($(this).find('h6').first().clone().children().remove().end().text() || '').trim() || ('Activity #' + $(this).attr('data-id')),
        };
    }).get();

    const dateObj = parseLocalDate(dateKey);
    const pretty = dateObj
        ? `${DAY_SHORT[dateObj.getDay()]}, ${MONTH_SHORT[dateObj.getMonth()]} ${dateObj.getDate()}, ${dateObj.getFullYear()}`
        : dateKey;

    confirmAction({
        title: 'Delete entire date group',
        message: `Delete <strong>all ${targets.length} ${targets.length === 1 ? 'activity' : 'activities'}</strong> on <strong>${escapeHtml(pretty)}</strong>?`,
        detail: 'You can immediately undo this (Ctrl+Z) — every activity is soft-deleted and can be restored together.',
        confirmText: targets.length === 1 ? 'Delete 1 Activity' : 'Delete ' + targets.length + ' Activities',
        onConfirm: () => {
            // Fire all deletes in parallel; only act on the cards that succeed.
            const deletes = targets.map(t => $.ajax({
                url: URLS.activitiesDelete(t.id),
                type: 'DELETE',
                data: { _token: CSRF },
            }).then(
                (res) => ({ id: t.id, name: t.name, ok: !!(res && res.success), msg: res && res.message }),
                (xhr) => ({ id: t.id, name: t.name, ok: false, msg: xhr.responseJSON?.message || 'delete failed' })
            ));

            Promise.all(deletes).then(results => {
                const succeeded = results.filter(r => r.ok);
                const failed = results.filter(r => !r.ok);

                succeeded.forEach(r => {
                    $('#activitiesList [data-id="' + r.id + '"]').remove();
                    bumpBadge('badge-activities', -1);
                });
                reorderAndRenumberActivities();
                if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
                if ($('#activitiesList .activity-card').length === 0) {
                    $('#activitiesList').html(`
                        <div id="activitiesEmpty">
                            <div class="text-center text-secondary py-5">
                                <i class='bx bx-task' style="font-size:2.5rem;"></i>
                                <p class="text-dark mt-2 mb-0">No activities defined yet.</p>
                                <small>Click <strong>Add Activity</strong> to define your first step.</small>
                            </div>
                        </div>
                    `);
                }

                if (succeeded.length > 0) {
                    toastr.success(`Deleted ${succeeded.length} ${succeeded.length === 1 ? 'activity' : 'activities'} on ${dateKey}`);
                }
                if (failed.length > 0) {
                    toastr.error(`${failed.length} ${failed.length === 1 ? 'activity' : 'activities'} could not be deleted — refresh and try again.`);
                }

                // Undo: restore every successfully-deleted activity in one shot.
                if (succeeded.length > 0) {
                    const idsToRestore = succeeded.map(r => r.id);
                    const label = succeeded.length === 1
                        ? "Delete '" + succeeded[0].name + "'"
                        : `Delete ${succeeded.length} activities on ${dateKey}`;
                    pushUndo(label, async () => {
                        const restores = idsToRestore.map(id => $.ajax({
                            url: URLS.activitiesRestore(id),
                            type: 'POST',
                            data: { _token: CSRF },
                        }).then(
                            (res) => (res && res.success) ? res.data : null,
                            () => null
                        ));
                        const restoredData = (await Promise.all(restores)).filter(d => d);
                        if (restoredData.length === 0) {
                            throw new Error('no activities could be restored');
                        }
                        restoredData.forEach(d => _renderCardOrReplace(d));
                    });
                }
            });
        }
    });
});

// ---------- UNDO STACK (10-step LIFO, scoped to activities) ----------
const ACTIVITY_UNDO_STACK = [];
const ACTIVITY_UNDO_MAX = 10;

function pushUndo(label, undoFn) {
    ACTIVITY_UNDO_STACK.push({ label, undoFn });
    if (ACTIVITY_UNDO_STACK.length > ACTIVITY_UNDO_MAX) {
        ACTIVITY_UNDO_STACK.shift();
    }
    refreshUndoBtn();
}

function refreshUndoBtn() {
    const n = ACTIVITY_UNDO_STACK.length;
    const $btn = $('#activityUndoBtn');
    if (n === 0) {
        $btn.prop('disabled', true).attr('title', 'Nothing to undo');
        $('#activityUndoCount').hide().text('');
        $('#activityUndoLabel').text('Undo');
    } else {
        $btn.prop('disabled', false);
        const last = ACTIVITY_UNDO_STACK[ACTIVITY_UNDO_STACK.length - 1];
        $btn.attr('title', 'Undo: ' + last.label + ' (' + n + ' available, Ctrl+Z)');
        $('#activityUndoCount').show().text(n);
        $('#activityUndoLabel').text('Undo ' + last.label);
    }
}

async function performUndo() {
    const action = ACTIVITY_UNDO_STACK.pop();
    refreshUndoBtn();
    if (!action) {
        toastr.info('Nothing to undo');
        return;
    }
    try {
        await action.undoFn();
        toastr.success('Undone: ' + action.label);
    } catch (err) {
        toastr.error('Undo failed: ' + (err && err.message ? err.message : 'unknown error'));
    }
}

$(document).on('click', '#activityUndoBtn', function (e) {
    e.preventDefault();
    if ($(this).prop('disabled')) return;
    performUndo();
});

// Ctrl+Z / Cmd+Z anywhere on the schedule setup page (but not while typing).
$(document).on('keydown', function (e) {
    if (!(e.ctrlKey || e.metaKey) || e.shiftKey) return;
    if (e.key !== 'z' && e.key !== 'Z') return;
    const tag = (e.target.tagName || '').toLowerCase();
    // contenteditable catches Quill's editor area (.ql-editor) so the
    // rich-text editor's own undo stack handles Ctrl+Z while focused.
    if (tag === 'input' || tag === 'textarea' || tag === 'select' || e.target.isContentEditable) return;
    e.preventDefault();
    performUndo();
});

refreshUndoBtn();

// Convert an Activity model JSON (as returned by /show or /save) back into a
// payload shape suitable for /update — used by the edit-undo path.
function activityToPayload(a) {
    const lotIds = a.lotIds || (a.lots || []).map(l => l.id);
    const workerIds = a.workerIds || (a.workers || []).map(w => w.id);
    const items = (a.items || []).map(it => ({
        itemType: it.itemType,
        itemId: it.itemType === 'material' ? it.materialId : it.serviceId,
        quantity: it.quantity,
        unitOfMeasure: it.unitOfMeasure || '',
        notes: it.notes || '',
    }));
    return {
        activityTitle: a.activityTitle,
        targetDate: (a.targetDate || '').slice(0, 10),
        targetEndDate: a.targetEndDate ? a.targetEndDate.slice(0, 10) : null,
        priority: a.priority,
        activityType: a.activityType || '',
        description: a.description || '',
        timeRequired: a.timeRequired,
        isDayZero: (a.isDayZero === true || a.isDayZero === 1 || a.isDayZero === '1') ? 1 : 0,
        lotIds: lotIds,
        workerIds: workerIds,
        items: items,
    };
}

// Helper: re-render a fresh card after a server response and re-sort the list.
function _renderCardOrReplace(activityData) {
    const id = activityData.id;
    const html = renderActivityCard(activityData);
    const $existing = $('#activitiesList [data-id="' + id + '"]');
    if ($existing.length) {
        $existing.replaceWith(html);
    } else {
        $('#activitiesEmpty').remove();
        $('#activitiesList').append(html);
        bumpBadge('badge-activities', 1);
    }
    reorderAndRenumberActivities();
    if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
}

function _removeCardById(id) {
    $('#activitiesList [data-id="' + id + '"]').remove();
    bumpBadge('badge-activities', -1);
    reorderAndRenumberActivities();
    if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
    if ($('#activitiesList .activity-card').length === 0) {
        $('#activitiesList').html(`
            <div id="activitiesEmpty">
                <div class="text-center text-secondary py-5">
                    <i class='bx bx-task' style="font-size:2.5rem;"></i>
                    <p class="text-dark mt-2 mb-0">No activities defined yet.</p>
                    <small>Click <strong>Add Activity</strong> to define your first step.</small>
                </div>
            </div>
        `);
    }
}

// ---------- EXPORT SCHEDULE (preview / PDF / copy) ----------
$(document).on('click', '#openExportScheduleBtn', function () {
    const url = URLS.activitiesExport();
    // Load the document view into the iframe. Cache-bust so freshly-edited
    // activities show up without a hard refresh.
    $('#exportScheduleFrame').attr('src', url + '&_=' + Date.now());
    $('#exportScheduleModal').modal('show');
});

$(document).on('click', '#downloadSchedulePdfBtn', function () {
    const iframe = document.getElementById('exportScheduleFrame');
    if (!iframe || !iframe.contentWindow) {
        toastr.error('Preview is still loading — try again in a moment.');
        return;
    }
    try {
        iframe.contentWindow.focus();
        iframe.contentWindow.print();
    } catch (err) {
        toastr.error('Could not open the print dialog: ' + err.message);
    }
});

$(document).on('click', '#copyScheduleTextBtn', function () {
    const iframe = document.getElementById('exportScheduleFrame');
    const doc = iframe && iframe.contentDocument;
    if (!doc || !doc.body) {
        toastr.error('Preview is still loading — try again in a moment.');
        return;
    }
    // Build a clean plain-text version from the rendered document.
    const lines = [];
    const title = doc.querySelector('.doc-header h1')?.textContent?.trim();
    const subtitle = doc.querySelector('.doc-header .subtitle')?.textContent?.trim();
    if (title) {
        lines.push(title);
        lines.push('='.repeat(Math.max(title.length, 4)));
    }
    if (subtitle) {
        lines.push(subtitle);
    }
    // Meta line (Status, Day Type, Spans, Generated)
    const metaText = doc.querySelector('.doc-meta')?.innerText?.replace(/\s*\n\s*/g, ' · ').trim();
    if (metaText) {
        lines.push(metaText);
    }
    lines.push('');

    // Activities, walking date-blocks in order.
    doc.querySelectorAll('.date-block').forEach(block => {
        const day = block.querySelector('.date-bar .day')?.textContent?.trim() || '';
        const date = block.querySelector('.date-bar .date')?.textContent?.trim() || '';
        const count = block.querySelector('.date-bar .count')?.textContent?.trim() || '';
        const headerLine = [day, date].filter(Boolean).join(' · ') + (count ? '  (' + count + ')' : '');
        lines.push(headerLine);
        lines.push('-'.repeat(Math.max(headerLine.length, 4)));

        block.querySelectorAll('.activity').forEach(act => {
            const t = act.querySelector('.activity-title')?.textContent?.trim() || '';
            const range = act.querySelector('.activity-range')?.textContent?.trim() || '';
            const pr = act.querySelector('.priority-pill')?.textContent?.trim() || '';
            lines.push('  • ' + t + (range ? '  ' + range : '') + (pr ? '  [' + pr + ']' : ''));

            const desc = act.querySelector('.desc-on-card');
            if (desc) {
                // Description may be rich HTML — flatten to text and indent.
                const txt = desc.innerText.trim();
                if (txt) {
                    txt.split(/\r?\n/).forEach(l => { if (l.trim()) lines.push('      ' + l.trim()); });
                }
            }

            act.querySelectorAll('.activity-line').forEach(line => {
                const label = line.querySelector('.label')?.textContent?.trim().replace(/:$/, '') || '';
                // Get all chips' text or the remaining text after the label.
                const chips = Array.from(line.querySelectorAll('.chip')).map(c => c.textContent.trim());
                let value;
                if (chips.length) {
                    value = chips.join(', ');
                } else {
                    value = line.textContent.replace(line.querySelector('.label')?.textContent || '', '').trim();
                }
                if (label || value) lines.push('      ' + (label ? label + ': ' : '') + value);
            });
            lines.push('');
        });
    });

    const text = lines.join('\n').replace(/\n{3,}/g, '\n\n').trim();

    const writeToClipboard = (txt) => {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(txt);
        }
        // Fallback for older browsers / non-secure contexts.
        const ta = document.createElement('textarea');
        ta.value = txt;
        ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
        return Promise.resolve();
    };

    writeToClipboard(text).then(() => {
        toastr.success('Schedule text copied to clipboard.');
    }).catch(err => {
        toastr.error('Copy failed: ' + (err && err.message ? err.message : err));
    });
});

// Clear the iframe when the modal closes so the next open starts fresh.
$('#exportScheduleModal').on('hidden.bs.modal', function () {
    $('#exportScheduleFrame').attr('src', 'about:blank');
});

// ---------- ACTIVITIES ----------
function getScheduleDayType() {
    return ($('.day-type-label').first().text() || 'DAS').trim();
}

// Clear button for the optional end-date input.
$(document).on('click', '#activityTargetEndDateClear', function () {
    $('#activityTargetEndDate').val('');
});

// Decide whether the "Mark as Day 0" checkbox should be visible right now.
// Rule: if every selected lot is either un-anchored or anchored by THIS
// activity itself, show the toggle. As soon as a selected lot is anchored
// elsewhere (another activity or a manual lot date), hide the toggle so the
// user doesn't accidentally create a conflicting anchor.
function shouldShowDayZeroToggle() {
    const currentActivityId = parseInt($('#activityId').val(), 10);
    const hasCurrentId = !isNaN(currentActivityId) && currentActivityId > 0;
    const selectedLotIds = getActivityLotIds();
    if (selectedLotIds.length === 0) return true;
    const sources = window.LOT_DAY_ZERO_SOURCE || {};
    for (const lotId of selectedLotIds) {
        const src = sources[lotId];
        if (!src) continue;
        if (hasCurrentId && src === currentActivityId) continue;
        return false;
    }
    return true;
}

function refreshDayZeroToggleVisibility() {
    const $checkbox = $('#activityIsDayZero');
    if ($checkbox.length === 0) return;
    const $wrapper = $checkbox.closest('.mb-3');
    if (shouldShowDayZeroToggle()) {
        $wrapper.show();
    } else {
        // Force-uncheck while hidden so a stale tick doesn't get submitted.
        $checkbox.prop('checked', false);
        $wrapper.hide();
    }
}

// Lot chips inside the Activity modal — toggle selection with mutual
// exclusion against the special "N/A" chip. Picking N/A clears real
// lots (the activity isn't tied to any specific lot); picking a real
// lot clears N/A. Day 0 toggle is re-evaluated for the new lot set.
$(document).on('click', '#activityLotsContainer .lot-chip', function () {
    const $chip = $(this);
    const isNa  = $chip.hasClass('lot-chip-na');

    if (isNa) {
        // Toggle N/A; if turning it ON, deactivate every real lot chip.
        const willActivate = !$chip.hasClass('active');
        if (willActivate) {
            $('#activityLotsContainer .lot-chip:not(.lot-chip-na)')
                .removeClass('active').attr('aria-pressed', 'false');
        }
        $chip.toggleClass('active', willActivate)
             .attr('aria-pressed', willActivate ? 'true' : 'false');
    } else {
        // Real lot toggle — kill N/A if it was active.
        $('#activityLotsContainer .lot-chip-na')
            .removeClass('active').attr('aria-pressed', 'false');
        $chip.toggleClass('active');
        $chip.attr('aria-pressed', $chip.hasClass('active') ? 'true' : 'false');
    }
    refreshDayZeroToggleVisibility();
});

function setActivityLots(lotIds) {
    const ids = (lotIds || []).map(Number);
    // Empty array → mark N/A active (activity applies generally).
    const useNa = ids.length === 0;
    $('#activityLotsContainer .lot-chip').each(function () {
        const $c = $(this);
        if ($c.hasClass('lot-chip-na')) {
            $c.toggleClass('active', useNa).attr('aria-pressed', useNa ? 'true' : 'false');
        } else {
            const isActive = ids.includes(parseInt($c.data('lot-id'), 10));
            $c.toggleClass('active', isActive).attr('aria-pressed', isActive ? 'true' : 'false');
        }
    });
}

function getActivityLotIds() {
    // N/A active → return empty array; server stores zero lot pivots.
    if ($('#activityLotsContainer .lot-chip-na.active').length > 0) return [];
    return $('#activityLotsContainer .lot-chip.active:not(.lot-chip-na)')
        .map((_, e) => parseInt($(e).data('lot-id'), 10))
        .get();
}

// Worker chips inside the Activity modal — toggle selection.
$(document).on('click', '#activityWorkersContainer .lot-chip', function () {
    const $chip = $(this);
    $chip.toggleClass('active');
    $chip.attr('aria-pressed', $chip.hasClass('active') ? 'true' : 'false');
});

function setActivityWorkers(workerIds) {
    const ids = (workerIds || []).map(Number);
    $('#activityWorkersContainer .lot-chip').each(function () {
        const isActive = ids.includes(parseInt($(this).data('worker-id'), 10));
        $(this).toggleClass('active', isActive).attr('aria-pressed', isActive ? 'true' : 'false');
    });
}

function getActivityWorkerIds() {
    return $('#activityWorkersContainer .lot-chip.active')
        .map((_, e) => parseInt($(e).data('worker-id'), 10))
        .get();
}

// Worker name lookup for timeline rendering.
const ACTIVITY_WORKER_NAMES = @json($schedule->workers->mapWithKeys(fn($w) => [$w->id => $w->workerName]));

// Activity type slug → label, mirrors AsScheduleActivity::ACTIVITY_TYPES.
const ACTIVITY_TYPE_LABELS = @json(\App\Models\AsScheduleActivity::ACTIVITY_TYPES);

// ---- Quill wiring for the Activity Description ----
// Replaced TinyMCE (cloud build was capping editor loads and locking the
// editor to read-only). Quill is MIT-licensed, self-hosted from a CDN,
// and has no usage cap.
const ACTIVITY_DESC_EDITOR = 'activityDescription';
const ACTIVITY_DESC_SOURCE = 'activityDescriptionSource';
const ACTIVITY_DESC_WRAP   = 'activityDescriptionWrap';
// 'visual' = Quill WYSIWYG, 'html' = plain <textarea> showing raw HTML.
let descriptionMode = 'visual';
let activityDescQuill = null;

// Shared Quill toolbar config — same set of formatting controls the
// TinyMCE setup offered.
const SM_QUILL_TOOLBAR = [
    [{ header: [1, 2, 3, 4, false] }],
    ['bold', 'italic', 'underline', 'strike'],
    [{ list: 'ordered' }, { list: 'bullet' }],
    [{ indent: '-1' }, { indent: '+1' }],
    ['blockquote', 'code-block'],
    ['link'],
    ['clean'],
];

function initActivityDescriptionEditor() {
    if (typeof Quill === 'undefined') return;
    if (activityDescQuill) return; // already initialized
    activityDescQuill = new Quill('#' + ACTIVITY_DESC_EDITOR, {
        theme: 'snow',
        placeholder: 'Describe this activity…',
        modules: { toolbar: SM_QUILL_TOOLBAR },
    });
}

function destroyActivityDescriptionEditor() {
    if (!activityDescQuill) return;
    // Quill has no public destroy method — strip the toolbar + container
    // that Quill injected so a fresh init on next modal-open builds cleanly.
    const $wrap = $('#' + ACTIVITY_DESC_WRAP);
    $wrap.find('.ql-toolbar').remove();
    const $host = $('#' + ACTIVITY_DESC_EDITOR);
    $host.empty().removeClass('ql-container ql-snow').removeAttr('style');
    activityDescQuill = null;
}

function getActivityDescriptionContent() {
    // In HTML mode the textarea is the source of truth.
    if (descriptionMode === 'html') {
        return $('#' + ACTIVITY_DESC_SOURCE).val() || '';
    }
    if (activityDescQuill) {
        const html = activityDescQuill.root.innerHTML;
        // Quill leaves "<p><br></p>" behind when the editor is empty —
        // normalize that to an empty string so downstream "is the note
        // blank?" checks behave.
        return html === '<p><br></p>' ? '' : html;
    }
    return '';
}

function setActivityDescriptionContent(html) {
    if (descriptionMode === 'html') {
        $('#' + ACTIVITY_DESC_SOURCE).val(html || '');
        return;
    }
    if (activityDescQuill) {
        // dangerouslyPasteHTML preserves arbitrary markup (headings,
        // styles, tables) instead of round-tripping through Quill's
        // Delta format, which would strip unknown tags.
        activityDescQuill.clipboard.dangerouslyPasteHTML(html || '');
    } else {
        // Editor not yet built — stash the HTML so it can be flushed
        // after the modal's shown.bs.modal handler initializes Quill.
        $('#' + ACTIVITY_DESC_EDITOR).data('pending-content', html || '');
    }
}

function setDescriptionMode(mode) {
    if (mode === descriptionMode) return;
    const $wrap = $('#' + ACTIVITY_DESC_WRAP);
    if (mode === 'html') {
        // Visual → HTML: pull current HTML out of Quill, hide the WYSIWYG,
        // surface the bare textarea so the user can hand-edit markup.
        const html = getActivityDescriptionContent();
        descriptionMode = 'html';
        $('#' + ACTIVITY_DESC_SOURCE).val(html);
        $wrap.addClass('is-html-mode');
        $('#toggleDescriptionModeLabel').text('Back to visual editor');
        $('#toggleDescriptionMode i').removeClass('bx-code-alt').addClass('bx-text');
    } else {
        // HTML → Visual: capture whatever raw markup the user typed and
        // push it back into Quill.
        const html = $('#' + ACTIVITY_DESC_SOURCE).val() || '';
        descriptionMode = 'visual';
        $wrap.removeClass('is-html-mode');
        if (activityDescQuill) {
            activityDescQuill.clipboard.dangerouslyPasteHTML(html);
        }
        $('#toggleDescriptionModeLabel').text('Edit HTML source');
        $('#toggleDescriptionMode i').removeClass('bx-text').addClass('bx-code-alt');
    }
}

$(document).on('click', '#toggleDescriptionMode', function (e) {
    e.preventDefault();
    setDescriptionMode(descriptionMode === 'visual' ? 'html' : 'visual');
});

// Reset back to the visual editor whenever the modal closes — keeps the
// next open consistent and predictable.
$('#activityModal').on('hidden.bs.modal', function () {
    destroyActivityDescriptionEditor();
    descriptionMode = 'visual';
    $('#' + ACTIVITY_DESC_WRAP).removeClass('is-html-mode');
    $('#' + ACTIVITY_DESC_SOURCE).val('');
    $('#toggleDescriptionModeLabel').text('Edit HTML source');
    $('#toggleDescriptionMode i').removeClass('bx-text').addClass('bx-code-alt');
});

// Init when the modal is first shown — Quill needs a visible mount point
// to size the toolbar/editor correctly. After init, flush any pending
// content stashed by the edit-button handler (which fires *before*
// shown.bs.modal).
$('#activityModal').on('shown.bs.modal', function () {
    initActivityDescriptionEditor();
    const pending = $('#' + ACTIVITY_DESC_EDITOR).data('pending-content');
    if (pending !== undefined) {
        setActivityDescriptionContent(pending);
        $('#' + ACTIVITY_DESC_EDITOR).removeData('pending-content');
    }
});

// Lot lookup so render can show lot names without a server round-trip.
const ACTIVITY_LOT_NAMES = @json($schedule->lots->mapWithKeys(fn($l) => [$l->id => $l->lotName]));
// Parallel variety map — null when the lot has no variety. Used by the
// activity-card render to show the crop variety alongside the lot name.
const ACTIVITY_LOT_VARIETIES = @json($schedule->lots->mapWithKeys(fn($l) => [$l->id => $l->variety]));

const MONTH_SHORT = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
const DAY_SHORT = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

function parseLocalDate(s) {
    // Treat "YYYY-MM-DD" as a local date (no TZ swing).
    if (!s) return null;
    const [y, m, d] = String(s).slice(0, 10).split('-').map(n => parseInt(n, 10));
    if (!y || !m || !d) return null;
    return new Date(y, m - 1, d);
}

function timeRequiredLabel(value) {
    if (value === 'whole') return 'Whole day';
    if (value === 'n/a') return 'N/A';
    return 'Half day';
}

function renderActivityCard(a) {
    const priorityCls = 'priority-' + (a.priority || 'medium');
    const priorityLabel = (a.priority || 'medium');
    const priorityCap = priorityLabel.charAt(0).toUpperCase() + priorityLabel.slice(1);
    const timeLabel = timeRequiredLabel(a.timeRequired);
    const targetDateStr = (a.targetDate || '').slice(0, 10);

    let itemsBlock = '';
    if ((a.items || []).length) {
        let inner = '';
        a.items.forEach(it => {
            const qtyTrim = String(it.quantity).replace(/\.?0+$/, '');
            // Prefer per-item unit; fall back to the material's own unit when blank.
            const unit = it.unitOfMeasure || (it.material && it.material.unitOfMeasure) || '';
            const unitDisplay = unit ? ' ' + escapeHtml(unit) : '';
            if (it.itemType === 'material' && it.material) {
                inner += `<span class="item-tag">${escapeHtml(it.material.materialName)} ×${escapeHtml(qtyTrim)}${unitDisplay}</span>`;
            } else if (it.itemType === 'service' && it.service) {
                const showQty = qtyTrim !== '1' || unit;
                const qtyChunk = showQty ? ` ×${escapeHtml(qtyTrim)}${unitDisplay}` : '';
                inner += `<span class="item-tag service">${escapeHtml(it.service.serviceName)}${qtyChunk}</span>`;
            }
        });
        itemsBlock = `<div class="mt-2">${inner}</div>`;
    } else {
        itemsBlock = `<div class="mt-2"><small class="text-secondary"><i class="bx bx-minus-circle"></i> No materials or services</small></div>`;
    }

    // Lot tags from server-provided lotIds OR a.lots[] fallback (rendered in header now)
    const lotIds = a.lotIds || ((a.lots || []).map(l => l.id || l));
    const lotSig = lotIds.slice().map(Number).sort((x, y) => x - y).join(',');
    let lotsHeaderBlock;
    if (lotIds && lotIds.length) {
        const tags = lotIds.map(id => {
            const name = ACTIVITY_LOT_NAMES[id] || ('Lot #' + id);
            const variety = (ACTIVITY_LOT_VARIETIES && ACTIVITY_LOT_VARIETIES[id]) || '';
            const varietyChunk = variety
                ? ` <small style="opacity:.85;">· ${escapeHtml(variety)}</small>`
                : '';
            const dasSuffix = (typeof computeDasLabel === 'function') ? computeDasLabel(id, targetDateStr) : '';
            return `<span class="item-tag" style="background:#eef0fb; color:#3a4699;" data-lot-id="${id}" data-lot-name="${escapeHtml(name)}">${escapeHtml(name)}${varietyChunk}${escapeHtml(dasSuffix)}</span>`;
        }).join('');
        lotsHeaderBlock = `<div class="activity-card-lots"><i class="bx bx-map-pin"></i>${tags}</div>`;
    } else {
        lotsHeaderBlock = `<div class="activity-card-lots"><span class="item-tag activity-na-tag" title="Activity applies generally — not tied to any specific lot"><i class="bx bx-globe"></i> N/A — Not lot-specific</span></div>`;
    }

    // Worker tags from server-provided workerIds OR a.workers[] fallback
    const workerIds = a.workerIds || ((a.workers || []).map(w => w.id || w));
    let workersBlock;
    if (workerIds && workerIds.length) {
        const tags = workerIds.map(id => {
            const name = ACTIVITY_WORKER_NAMES[id] || ('Worker #' + id);
            return `<span class="item-tag" style="background:#fef3e8; color:#a66200;">${escapeHtml(name)}</span>`;
        }).join('');
        workersBlock = `<div class="mt-2"><small class="text-secondary me-1"><i class="bx bx-user"></i> Workers:</small>${tags}</div>`;
    } else {
        workersBlock = `<div class="mt-2"><small class="text-secondary"><i class="bx bx-user-x"></i> No workers assigned</small></div>`;
    }

    // Description is rich HTML — keep it intact rather than truncating mid-tag.
    const descHtml = a.description || '';

    const seqOrder = (a.sequenceOrder !== undefined && a.sequenceOrder !== null) ? a.sequenceOrder : 0;
    const targetEndDateStr = (a.targetEndDate || '').slice(0, 10);
    const endDateObj = parseLocalDate(targetEndDateStr);
    const startDateObj = parseLocalDate(targetDateStr);
    let rangeBadge = '';
    if (endDateObj && startDateObj && endDateObj > startDateObj) {
        const days = Math.round((endDateObj - startDateObj) / 86400000) + 1;
        const endLabel = `${MONTH_SHORT[endDateObj.getMonth()]} ${endDateObj.getDate()}`;
        rangeBadge = `<span class="badge bg-light text-dark ms-1" style="font-weight:500;font-size:11px;" title="Multi-day range"><i class="bx bx-right-arrow-alt"></i> ${escapeHtml(endLabel)} <span class="text-secondary">(${days}d)</span></span>`;
    }
    const isDayZeroFlag = (a.isDayZero === true || a.isDayZero === 1 || a.isDayZero === '1') ? 1 : 0;
    const dayType = (typeof getScheduleDayType === 'function') ? getScheduleDayType() : 'DAS';
    const dayZeroBadge = isDayZeroFlag
        ? `<span class="badge ms-1 day-zero-badge" style="background:#ff9800; color:#fff; font-weight:600; font-size:11px;" title="This activity is the ${escapeHtml(dayType)} 0 anchor — its date becomes ${escapeHtml(dayType)} 0 for every lot it covers."><i class="bx bxs-star"></i> ${escapeHtml(dayType)} 0</span>`
        : '';
    const typeLabel = a.activityType && ACTIVITY_TYPE_LABELS[a.activityType] ? ACTIVITY_TYPE_LABELS[a.activityType] : '';
    const typeBadge = typeLabel
        ? `<span class="badge ms-1 activity-type-badge" style="background:#e2efd4; color:#2d4d1c; font-weight:600; font-size:11px;" title="Activity type">${escapeHtml(typeLabel)}</span>`
        : '';
    return `<div class="activity-card" draggable="true" data-id="${a.id}" data-target-date="${escapeHtml(targetDateStr)}" data-target-end-date="${escapeHtml(targetEndDateStr)}" data-lot-signature="${escapeHtml(lotSig)}" data-sequence-order="${seqOrder}" data-is-day-zero="${isDayZeroFlag}" data-activity-type="${escapeHtml(a.activityType || '')}">
        <div class="d-flex justify-content-between align-items-start gap-2 flex-wrap">
            <div class="flex-grow-1" style="min-width:0;">
                <h6 class="text-dark mb-1">${escapeHtml(a.activityTitle)}${typeBadge}${dayZeroBadge}${rangeBadge}</h6>
                ${lotsHeaderBlock}
            </div>
            <div class="d-flex align-items-center gap-2 flex-shrink-0">
                <span class="sm-pill ${priorityCls}">${escapeHtml(priorityCap)}</span>
                <button class="btn btn-sm btn-outline-primary edit-activity-btn" data-id="${a.id}" title="Edit"><i class="bx bx-edit-alt"></i></button>
                <button class="btn btn-sm btn-outline-secondary duplicate-activity-btn" data-id="${a.id}" data-name="${escapeHtml(a.activityTitle)}" title="Duplicate"><i class="bx bx-copy"></i></button>
                <button class="btn btn-sm btn-outline-info to-draft-activity-btn" data-id="${a.id}" data-name="${escapeHtml(a.activityTitle)}" title="Move to drafts (hide without deleting)"><i class="bx bx-archive-in"></i></button>
                <button class="btn btn-sm btn-outline-danger delete-activity-btn" data-id="${a.id}" data-name="${escapeHtml(a.activityTitle)}" title="Delete"><i class="bx bx-trash"></i></button>
            </div>
        </div>
        ${descHtml ? `<div class="text-dark mt-2 mb-2 activity-description-content" style="font-size:13px;">${descHtml}</div>` : ''}
        <div class="step-meta mt-1">
            <i class="bx bx-time"></i> ${escapeHtml(timeLabel)}
        </div>
        ${workersBlock}
        ${itemsBlock}
    </div>`;
}

// Rebuild the date-grouped, color-coded list from whatever .activity-card
// elements are currently in the DOM. Called after every save/delete/duplicate.
function reorderAndRenumberActivities() {
    const $list = $('#activitiesList');
    // Collect every card regardless of which group it currently sits in.
    const cards = $list.find('.activity-card[data-id]').get();
    if (cards.length === 0) return;

    // Group by date (Y-m-d). Cards with no date use a sentinel key sorted last.
    const groups = {};
    cards.forEach(el => {
        const key = ($(el).attr('data-target-date') || '').trim() || '__no-date__';
        if (!groups[key]) groups[key] = [];
        groups[key].push(el);
    });

    // Within each date group: honor manual sequenceOrder first (drag-to-
    // reorder), fall back to lot signature so same-lot activities cluster
    // when no manual order has been set, then id for stability.
    Object.values(groups).forEach(arr => arr.sort((a, b) => {
        const seqA = parseInt($(a).attr('data-sequence-order'), 10) || 0;
        const seqB = parseInt($(b).attr('data-sequence-order'), 10) || 0;
        if (seqA !== seqB) return seqA - seqB;
        const sa = $(a).attr('data-lot-signature') || '';
        const sb = $(b).attr('data-lot-signature') || '';
        if (sa !== sb) return sa.localeCompare(sb);
        return parseInt($(a).attr('data-id'), 10) - parseInt($(b).attr('data-id'), 10);
    }));

    // Build covered-days set + span so we can interleave rest markers.
    const coveredDays = new Set();
    let firstDate = null;
    let lastDate = null;
    cards.forEach(el => {
        const startStr = ($(el).attr('data-target-date') || '').trim();
        if (!startStr) return;
        const endStr = ($(el).attr('data-target-end-date') || '').trim() || startStr;
        const start = parseLocalDate(startStr);
        const end = parseLocalDate(endStr);
        if (!start || !end) return;
        const cur = new Date(start.getTime());
        while (cur <= end) {
            const y = cur.getFullYear();
            const m = String(cur.getMonth() + 1).padStart(2, '0');
            const d = String(cur.getDate()).padStart(2, '0');
            coveredDays.add(`${y}-${m}-${d}`);
            cur.setDate(cur.getDate() + 1);
        }
        if (!firstDate || start < firstDate) firstDate = new Date(start.getTime());
        if (!lastDate || end > lastDate) lastDate = new Date(end.getTime());
    });

    // Construct the unified timeline: every dated day in [firstDate, lastDate]
    // produces either a group (real activities) or a rest marker (truly empty
    // day); covered-but-empty days are skipped because a multi-day activity
    // already accounts for them. __no-date__ is appended at the bottom.
    const timeline = [];
    let colorCursor = 0;
    if (firstDate && lastDate) {
        const cur = new Date(firstDate.getTime());
        while (cur <= lastDate) {
            const y = cur.getFullYear();
            const m = String(cur.getMonth() + 1).padStart(2, '0');
            const d = String(cur.getDate()).padStart(2, '0');
            const key = `${y}-${m}-${d}`;
            if (groups[key]) {
                timeline.push({ type: 'group', key, color: colorCursor, dateObj: new Date(cur.getTime()) });
                colorCursor = (colorCursor + 1) % 8;
            } else if (!coveredDays.has(key)) {
                timeline.push({ type: 'rest', key, dateObj: new Date(cur.getTime()) });
            }
            cur.setDate(cur.getDate() + 1);
        }
    }
    if (groups['__no-date__']) {
        timeline.push({ type: 'group', key: '__no-date__', color: 0, dateObj: null });
    }

    // Snapshot per-date notes BEFORE the wipe so we can re-inject them into
    // the rebuilt headers + inline blocks. Without this snapshot, every save
    // would silently drop the note rendering (the DB row stays intact, but
    // the rebuilt DOM has no note button/block since it's built from the
    // activity cards only).
    const notesByDate = {};
    $list.find('.date-note-btn').each(function () {
        const key = ($(this).attr('data-date') || '').trim();
        if (!key) return;
        notesByDate[key] = $(this).attr('data-existing') || '';
    });

    // Wipe and rebuild the list interleaving groups + rest markers.
    $list.empty();
    timeline.forEach(item => {
        if (item.type === 'rest') {
            const d = item.dateObj;
            // Full weekday name + full month so the marker reads at a
            // glance, matching the server-rendered partial format.
            const dayNames = ['Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday'];
            const monthNames = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
            const pretty = `${dayNames[d.getDay()]}, ${monthNames[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
            $list.append(`
                <div class="rest-day-marker" data-date="${escapeHtml(item.key)}">
                    <i class="bx bx-moon rest-day-icon"></i>
                    <div class="rest-day-text">
                        <span class="rest-day-date">${escapeHtml(pretty)}</span>
                        <span class="rest-day-tag">No activities scheduled</span>
                    </div>
                    <button type="button"
                            class="btn btn-sm btn-outline-primary rest-day-add-btn"
                            data-date="${escapeHtml(item.key)}"
                            title="Add a new activity to this date">
                        <i class="bx bx-plus"></i> Add Activity
                    </button>
                </div>
            `);
            return;
        }
        const key = item.key;
        const cardsForDate = groups[key];
        const dateObj = key !== '__no-date__' ? parseLocalDate(key) : null;

        // Compute the latest end-date across activities in this group so
        // the header can show a range badge when at least one activity is
        // multi-day. Mirrors the server-rendered partial.
        let latestEndObj = null;
        if (dateObj) {
            cardsForDate.forEach(el => {
                const endStr = ($(el).attr('data-target-end-date') || '').trim();
                if (!endStr) return;
                const end = parseLocalDate(endStr);
                if (!end) return;
                if (end > dateObj && (!latestEndObj || end > latestEndObj)) {
                    latestEndObj = end;
                }
            });
        }
        let rangeBadgeHtml = '';
        if (latestEndObj) {
            const spanDays = Math.round((latestEndObj - dateObj) / 86400000) + 1;
            const showYear = latestEndObj.getFullYear() !== dateObj.getFullYear();
            const endLabel = `${MONTH_SHORT[latestEndObj.getMonth()]} ${latestEndObj.getDate()}${showYear ? ', ' + latestEndObj.getFullYear() : ''}`;
            rangeBadgeHtml = `
                <span class="date-header-range" title="At least one activity in this group extends through ${endLabel} (${spanDays} days total)">
                    <i class="bx bx-right-arrow-alt"></i>
                    ${endLabel}
                    <span class="date-header-range-days">(${spanDays}d)</span>
                </span>`;
        }

        let headerHtml;
        if (dateObj) {
            headerHtml = `
                <i class="bx bx-calendar"></i>
                <span class="date-header-day">${DAY_SHORT[dateObj.getDay()]}</span>
                <span class="date-header-date">${MONTH_SHORT[dateObj.getMonth()]} ${dateObj.getDate()}, ${dateObj.getFullYear()}</span>${rangeBadgeHtml}`;
        } else {
            headerHtml = `<i class="bx bx-error-circle"></i><span class="date-header-date">No date</span>`;
        }
        const count = cardsForDate.length;
        // Re-inject the date note from the pre-empty() snapshot. Mirrors the
        // server-rendered output in the activities partial so the icon state
        // (outline vs solid) and the inline block stay in sync.
        const noteContent = (key !== '__no-date__') ? (notesByDate[key] || '') : '';
        const hasNote     = noteContent !== '';
        const noteBtn = (key !== '__no-date__')
            ? `<button type="button"
                       class="date-header-edit-btn date-note-btn${hasNote ? ' has-note' : ''}"
                       data-date="${escapeHtml(key)}"
                       data-existing="${escapeHtml(noteContent)}"
                       title="${hasNote ? 'Edit the note for this date' : 'Add a note for this date'}">
                       <i class="bx ${hasNote ? 'bxs-note' : 'bx-note'}"></i>
               </button>`
            : '';
        const editBtn = (key !== '__no-date__')
            ? `<button type="button" class="date-header-edit-btn change-group-date-btn" data-date="${escapeHtml(key)}" title="Change date for all activities in this group"><i class="bx bx-calendar-edit"></i></button>`
              + `<button type="button" class="date-header-edit-btn date-header-delete-btn delete-group-date-btn" data-date="${escapeHtml(key)}" title="Delete every activity in this group"><i class="bx bx-trash"></i></button>`
            : '';
        const noteBlockHtml = (key !== '__no-date__')
            ? `<div class="date-note-block" data-date="${escapeHtml(key)}"${hasNote ? '' : ' style="display:none;"'}>
                   <div class="date-note-inner">
                       <i class="bx bxs-note date-note-icon"></i>
                       <div class="date-note-text">${hasNote ? escapeHtml(noteContent).replace(/\n/g, '<br>') : ''}</div>
                   </div>
               </div>`
            : '';
        const $group = $(`
            <div class="date-group date-color-${item.color}" data-date="${key === '__no-date__' ? '' : escapeHtml(key)}">
                <div class="date-header">
                    ${headerHtml}
                    <span class="date-header-count">${count} ${count === 1 ? 'activity' : 'activities'}</span>
                    ${noteBtn}
                    ${editBtn}
                </div>
                ${noteBlockHtml}
                <div class="date-activities"></div>
            </div>
        `);
        cardsForDate.forEach(el => $group.find('.date-activities').append(el));
        $list.append($group);
    });
}

function refreshItemsEmptyState() {
    const hasItems = $('#itemsContainer span').length > 0;
    $('#itemsContainerEmpty').toggle(!hasItems);
}

function resetActivityModal() {
    $('#activityId').val('');
    $('#activityTitle').val('');
    $('#activityTargetDate').val('');
    $('#activityTargetEndDate').val('');
    $('#activityPriority').val('medium');
    $('#activityType').val('');
    $('#activityTimeRequired').val('half');
    $('#activityIsDayZero').prop('checked', false);
    setActivityDescriptionContent('');
    setActivityLots([]);
    setActivityWorkers([]);
    $('#itemsContainer').empty();
    refreshItemsEmptyState();
}

$('#addActivityBtn').on('click', function () {
    $('#activityModalTitle').text('Add Activity');
    resetActivityModal();
    $('#activityModal').removeData('before-snapshot');
    refreshDayZeroToggleVisibility();
    $('#activityModal').modal('show');
});

// Rest-day quick-add: clicking the "+ Add Activity" on a no-activity
// day opens the same modal as the toolbar button, but with the target
// date pre-filled to the clicked day so the user doesn't have to type
// it. Mirrors the toolbar handler exactly otherwise.
$(document).on('click', '.rest-day-add-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const dateKey = ($(this).data('date') || '').trim();
    $('#activityModalTitle').text('Add Activity');
    resetActivityModal();
    if (dateKey) {
        $('#activityTargetDate').val(dateKey);
    }
    $('#activityModal').removeData('before-snapshot');
    refreshDayZeroToggleVisibility();
    $('#activityModal').modal('show');
});

$(document).on('click', '.edit-activity-btn', function () {
    const id = $(this).data('id');
    $.get(URLS.activitiesShow(id), function (res) {
        if (!res.success) { toastr.error(res.message); return; }
        $('#activityModalTitle').text('Edit Activity');
        resetActivityModal();
        const a = res.data;
        // Stash the pre-edit snapshot so the save handler can push an undo entry
        // that re-writes the original values.
        $('#activityModal').data('before-snapshot', JSON.parse(JSON.stringify(a)));
        $('#activityId').val(a.id);
        $('#activityTitle').val(a.activityTitle);
        // targetDate / targetEndDate are serialized as Y-m-d (per the model cast)
        $('#activityTargetDate').val((a.targetDate || '').slice(0, 10));
        $('#activityTargetEndDate').val((a.targetEndDate || '').slice(0, 10));
        $('#activityPriority').val(a.priority);
        $('#activityType').val(a.activityType || '');
        $('#activityTimeRequired').val(a.timeRequired);
        $('#activityIsDayZero').prop('checked', a.isDayZero === true || a.isDayZero === 1 || a.isDayZero === '1');
        setActivityLots(a.lotIds || (a.lots || []).map(l => l.id));
        setActivityWorkers(a.workerIds || (a.workers || []).map(w => w.id));
        // Defer setContent until the modal is shown (Quill may not exist yet
        // on the very first open). Modal's shown.bs.modal handler below catches it.
        $('#activityDescription').data('pending-content', a.description || '');
        setActivityDescriptionContent(a.description || '');
        (a.items || []).forEach(it => {
            const itemUnit = it.unitOfMeasure || (it.material ? it.material.unitOfMeasure : '');
            if (it.itemType === 'material' && it.material) {
                appendItemTag('material', it.materialId, it.material.materialName, it.quantity, itemUnit);
            } else if (it.itemType === 'service' && it.service) {
                appendItemTag('service', it.serviceId, it.service.serviceName, it.quantity, it.unitOfMeasure || '');
            }
        });
        refreshDayZeroToggleVisibility();
        $('#activityModal').modal('show');
    });
});

function appendItemTag(type, id, label, qty, unit) {
    const cls = type === 'material' ? 'item-tag' : 'item-tag service';
    const unitSafe = unit || '';
    const unitDisplay = unitSafe ? ' ' + escapeHtml(unitSafe) : '';
    $('#itemsContainer').append(`
        <span class="${cls} me-2 mb-2" style="font-size:13px; padding:6px 12px;" data-type="${type}" data-id="${id}" data-qty="${qty}" data-unit="${escapeHtml(unitSafe)}">
            <strong>${escapeHtml(label)}</strong> × ${qty}${unitDisplay}
            <a href="javascript:void(0);" class="text-danger ms-2 remove-item-tag" title="Remove">&times;</a>
        </span>
    `);
    refreshItemsEmptyState();
}

$(document).on('click', '.remove-item-tag', function () {
    $(this).closest('span').remove();
    refreshItemsEmptyState();
});

$('#itemPickerType').on('change', function () {
    const t = $(this).val();
    $('#itemPickerId optgroup').hide();
    $('#itemPickerId optgroup[label="' + (t === 'material' ? 'Materials' : 'Services') + '"]').show();
    $('#itemPickerId').val($('#itemPickerId optgroup[label="' + (t === 'material' ? 'Materials' : 'Services') + '"] option:first').val()).trigger('change');
});

// Picking a material auto-fills the unit select with that material's unit.
$('#itemPickerId').on('change', function () {
    const $opt = $(this).find('option:selected');
    const unit = $opt.data('unit') || '';
    if (unit) {
        $('#itemPickerUnit').val(unit);
    } else {
        $('#itemPickerUnit').val('');
    }
});

$('#addItemBtn').on('click', function () {
    const v = $('#itemPickerId').val();
    if (!v) { toastr.warning('Pick a material or service'); return; }
    const [type, id] = v.split('::');
    const $opt = $('#itemPickerId').find('option:selected');
    // For materials, store just the name (without the "(unit)" suffix that the option label has).
    const baseLabel = ($opt.text() || '').replace(/\s*\([^)]*\)\s*$/, '').trim();
    const qty = parseFloat($('#itemPickerQty').val()) || 1;
    const unit = ($('#itemPickerUnit').val() || '').trim();

    if ($('#itemsContainer span[data-type="' + type + '"][data-id="' + id + '"]').length) {
        toastr.info('Already added — remove and re-add to update quantity/unit.');
        return;
    }
    appendItemTag(type, id, baseLabel, qty, unit);
});

$('#saveActivityBtn').on('click', function () {
    const id = $('#activityId').val();
    const items = [];
    $('#itemsContainer span').each(function () {
        items.push({
            itemType: $(this).data('type'),
            itemId: $(this).data('id'),
            quantity: $(this).data('qty'),
            unitOfMeasure: $(this).data('unit') || ''
        });
    });
    const endDateVal = ($('#activityTargetEndDate').val() || '').trim();
    const startDateVal = ($('#activityTargetDate').val() || '').trim();
    if (endDateVal && startDateVal && endDateVal < startDateVal) {
        toastr.warning('End date must be on or after the start date.');
        return;
    }
    const payload = {
        _token: CSRF,
        activityTitle: $('#activityTitle').val(),
        targetDate: startDateVal,
        targetEndDate: endDateVal || null,
        priority: $('#activityPriority').val(),
        activityType: $('#activityType').val() || '',
        description: getActivityDescriptionContent(),
        timeRequired: $('#activityTimeRequired').val(),
        isDayZero: $('#activityIsDayZero').is(':checked') ? 1 : 0,
        lotIds: getActivityLotIds(),
        workerIds: getActivityWorkerIds(),
        items: items
    };
    if (!payload.activityTitle) { toastr.warning('Activity title is required'); return; }
    if (!payload.targetDate) { toastr.warning('Pick a target date'); return; }
    // Empty payload.lotIds is allowed — it means the user picked N/A
    // ("not lot-specific") on the lot selector. The server stores the
    // activity with zero lot pivots and the card renders an N/A badge.
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: id ? URLS.activitiesUpdate(id) : URLS.activitiesStore(),
        type: id ? 'PUT' : 'POST',
        data: payload,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $('#activityModal').modal('hide');
            const html = renderActivityCard(res.data);
            const savedTitle = res.data.activityTitle || payload.activityTitle || 'activity';

            if (id) {
                $('#activitiesList [data-id="' + id + '"]').replaceWith(html);
                // Undo for edit: re-save with the pre-edit snapshot.
                const before = $('#activityModal').data('before-snapshot');
                if (before) {
                    pushUndo("Edit '" + savedTitle + "'", async () => {
                        const restorePayload = activityToPayload(before);
                        restorePayload._token = CSRF;
                        const r = await $.ajax({
                            url: URLS.activitiesUpdate(before.id),
                            type: 'PUT',
                            data: restorePayload,
                        });
                        if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                        _renderCardOrReplace(r.data);
                    });
                }
            } else {
                $('#activitiesEmpty').remove();
                $('#activitiesList').append(html);
                bumpBadge('badge-activities', 1);
                // Undo for create: delete the newly-created activity.
                const newId = res.data.id;
                pushUndo("Add '" + savedTitle + "'", async () => {
                    const r = await $.ajax({
                        url: URLS.activitiesDelete(newId),
                        type: 'DELETE',
                        data: { _token: CSRF },
                    });
                    if (!r || !r.success) throw new Error((r && r.message) || 'delete failed');
                    _removeCardById(newId);
                });
            }
            reorderAndRenumberActivities();
            if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Activity')
    });
});

// ---------- Drag-and-drop: reorder within a date OR move between dates ----------
let dragSourceCard = null;
let dragOrigin = null;     // { date, parent, nextSibling } — snapshot for rollback
let dragBoardSnapshot = null; // full state of every card before the drag, for undo

function captureBoardSnapshot() {
    const snap = [];
    $('#activitiesList .activity-card[data-id]').each(function () {
        snap.push({
            id: parseInt($(this).attr('data-id'), 10),
            targetDate: ($(this).attr('data-target-date') || '').trim(),
            targetEndDate: ($(this).attr('data-target-end-date') || '').trim() || null,
            sequenceOrder: parseInt($(this).attr('data-sequence-order'), 10) || 0,
        });
    });
    return snap;
}

$(document).on('dragstart', '.activity-card', function (e) {
    dragSourceCard = this;
    dragOrigin = {
        date: ($(this).attr('data-target-date') || '').trim(),
        endDate: ($(this).attr('data-target-end-date') || '').trim(),
        parent: this.parentNode,
        nextSibling: this.nextElementSibling,
    };
    // Snapshot every card so a successful drop can push an undo that restores
    // the exact arrangement.
    dragBoardSnapshot = captureBoardSnapshot();
    $(this).addClass('dragging');
    if (e.originalEvent && e.originalEvent.dataTransfer) {
        e.originalEvent.dataTransfer.effectAllowed = 'move';
        // Some browsers require any data to be set for the drag to fire.
        try { e.originalEvent.dataTransfer.setData('text/plain', String($(this).data('id'))); } catch (_) {}
    }
});

$(document).on('dragend', '.activity-card', function () {
    $(this).removeClass('dragging');
    $('.date-activities.drop-target').removeClass('drop-target');
    dragSourceCard = null;
    dragOrigin = null;
});

// Live in-position reorder during dragover. We figure out which card the
// cursor is hovering above (by Y midpoint) and insert the dragged card there.
function dragoverPosition(container, cursorY) {
    const cards = Array.from(container.querySelectorAll('.activity-card[data-id]'))
        .filter(c => c !== dragSourceCard);
    for (const card of cards) {
        const rect = card.getBoundingClientRect();
        const mid = rect.top + rect.height / 2;
        if (cursorY < mid) return card;
    }
    return null;
}

$(document).on('dragover', '.date-activities', function (e) {
    e.preventDefault();
    if (!dragSourceCard) return;
    if (e.originalEvent && e.originalEvent.dataTransfer) {
        e.originalEvent.dataTransfer.dropEffect = 'move';
    }
    const insertBefore = dragoverPosition(this, e.originalEvent.clientY);
    if (insertBefore) {
        // Avoid jitter: only insert when the position would actually change.
        if (insertBefore.previousElementSibling !== dragSourceCard) {
            insertBefore.parentNode.insertBefore(dragSourceCard, insertBefore);
        }
    } else if (this.lastElementChild !== dragSourceCard) {
        this.appendChild(dragSourceCard);
    }
});

$(document).on('dragenter', '.date-activities', function (e) {
    e.preventDefault();
    $(this).addClass('drop-target');
});

$(document).on('dragleave', '.date-activities', function (e) {
    if (e.target === this) $(this).removeClass('drop-target');
});

$(document).on('drop', '.date-activities', function (e) {
    e.preventDefault();
    $(this).removeClass('drop-target');
    if (!dragSourceCard) return;

    const $card = $(dragSourceCard);
    const $targetGroup = $(this).closest('.date-group');
    const newDate = ($targetGroup.attr('data-date') || '').trim();
    const oldDate = (dragOrigin && dragOrigin.date) || '';

    if (!newDate) {
        // No-date pseudo-group — revert and nudge the user.
        toastr.warning('Cannot drop on "No date". Edit the activity instead.');
        if (dragOrigin && dragOrigin.parent) {
            if (dragOrigin.nextSibling) {
                dragOrigin.parent.insertBefore(dragSourceCard, dragOrigin.nextSibling);
            } else {
                dragOrigin.parent.appendChild(dragSourceCard);
            }
        }
        return;
    }

    // Compute the new end date if this is a multi-day range — preserve duration
    // by shifting the end by the same number of days as the start moved.
    function daysBetween(a, b) {
        const da = parseLocalDate(a);
        const db = parseLocalDate(b);
        if (!da || !db) return null;
        return Math.round((db - da) / 86400000);
    }
    function addDaysIso(iso, days) {
        const d = parseLocalDate(iso);
        if (!d) return '';
        d.setDate(d.getDate() + days);
        const y = d.getFullYear();
        const m = String(d.getMonth() + 1).padStart(2, '0');
        const day = String(d.getDate()).padStart(2, '0');
        return `${y}-${m}-${day}`;
    }
    let newEndDate = '';
    if (dragOrigin.endDate && dragOrigin.date) {
        const delta = daysBetween(dragOrigin.date, newDate);
        if (delta !== null) {
            newEndDate = addDaysIso(dragOrigin.endDate, delta);
        }
    }

    // Commit the date attrs on the card (was tracked by visual position only).
    $card.attr('data-target-date', newDate);
    $card.attr('data-target-end-date', newEndDate);

    // Build the full ordered list for the destination container — and if the
    // date changed, for the source container too so its remaining cards keep
    // tight, predictable sequence numbers.
    const containers = new Set();
    containers.add(this);
    if (dragOrigin && dragOrigin.parent && dragOrigin.parent !== this) {
        containers.add(dragOrigin.parent);
    }

    const items = [];
    containers.forEach(container => {
        const containerDate = ($(container).closest('.date-group').attr('data-date') || '').trim();
        if (!containerDate) return;
        $(container).children('.activity-card[data-id]').each(function (idx) {
            items.push({
                id: parseInt($(this).attr('data-id'), 10),
                targetDate: containerDate,
                targetEndDate: ($(this).attr('data-target-end-date') || '').trim() || null,
                sequenceOrder: idx * 10,
            });
            // Reflect on the DOM so the sort key stays consistent right away.
            $(this).attr('data-sequence-order', idx * 10);
        });
    });

    // Refresh headers/colors/counts immediately so the UI feels snappy.
    reorderAndRenumberActivities();

    // Capture snapshot now, before async, so the undo entry has a stable copy.
    const snapshotForUndo = dragBoardSnapshot;
    const draggedId = parseInt($card.attr('data-id'), 10);

    $.ajax({
        url: URLS.activitiesReorder(),
        type: 'POST',
        data: { _token: CSRF, items: items },
        success: (res) => {
            if (!res.success) {
                toastr.error(res.message);
                return;
            }
            if (oldDate && oldDate !== newDate) {
                toastr.success('Moved to ' + newDate);
            } else {
                toastr.success('Order saved');
            }
            // A Day 0 activity may have shifted dates — re-derive the lot map.
            if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
            // Push undo: restore the pre-drag board state via reorder.
            if (snapshotForUndo && snapshotForUndo.length) {
                const undoSnapshot = snapshotForUndo.slice();
                const label = (oldDate && oldDate !== newDate)
                    ? 'Move activity to ' + newDate
                    : 'Reorder activities';
                pushUndo(label, async () => {
                    const r = await $.ajax({
                        url: URLS.activitiesReorder(),
                        type: 'POST',
                        data: { _token: CSRF, items: undoSnapshot },
                    });
                    if (!r || !r.success) throw new Error((r && r.message) || 'reorder failed');
                    // Reflect on DOM so the timeline regroups correctly.
                    undoSnapshot.forEach(it => {
                        const $c = $('#activitiesList [data-id="' + it.id + '"]');
                        $c.attr('data-target-date', it.targetDate || '');
                        $c.attr('data-target-end-date', it.targetEndDate || '');
                        $c.attr('data-sequence-order', it.sequenceOrder);
                    });
                    reorderAndRenumberActivities();
                    if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
                });
            }
        },
        error: (xhr) => {
            toastr.error(xhr.responseJSON?.message || 'Save failed — refresh to see saved order.');
        }
    });
    dragBoardSnapshot = null;
});

// Duplicate handler — create a server-side copy, render & open it for editing.
$(document).on('click', '.duplicate-activity-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const $btn = $(this).prop('disabled', true);
    $.ajax({
        url: URLS.activitiesDuplicate(id),
        type: 'POST',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success('Duplicated "' + name + '". Edit and save when ready.');
            const html = renderActivityCard(res.data);
            $('#activitiesEmpty').remove();
            $('#activitiesList').append(html);
            bumpBadge('badge-activities', 1);
            reorderAndRenumberActivities();
            if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
            // Open the new copy for editing right away.
            $('.edit-activity-btn[data-id="' + res.data.id + '"]').first().trigger('click');
            // Undo for duplicate: delete the copy.
            const copyId = res.data.id;
            pushUndo("Duplicate '" + name + "'", async () => {
                const r = await $.ajax({
                    url: URLS.activitiesDelete(copyId),
                    type: 'DELETE',
                    data: { _token: CSRF },
                });
                if (!r || !r.success) throw new Error((r && r.message) || 'delete failed');
                _removeCardById(copyId);
            });
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Duplicate failed'),
        complete: () => $btn.prop('disabled', false)
    });
});

$(document).on('click', '.delete-activity-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    confirmAction({
        title: 'Delete activity',
        message: 'Delete activity <strong>"' + escapeHtml(name) + '"</strong>?',
        detail: 'You can immediately undo this (Ctrl+Z) — the activity is soft-deleted and can be restored.',
        confirmText: 'Delete Activity',
        onConfirm: () => {
    $.ajax({
        url: URLS.activitiesDelete(id),
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $('#activitiesList [data-id="' + id + '"]').fadeOut(300, function () {
                $(this).remove();
                bumpBadge('badge-activities', -1);
                reorderAndRenumberActivities();
                if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
                if ($('#activitiesList .activity-card').length === 0) {
                    $('#activitiesList').html(`
                        <div id="activitiesEmpty">
                            <div class="text-center text-secondary py-5">
                                <i class='bx bx-task' style="font-size:2.5rem;"></i>
                                <p class="text-dark mt-2 mb-0">No activities defined yet.</p>
                                <small>Click <strong>Add Activity</strong> to define your first step.</small>
                            </div>
                        </div>
                    `);
                }
            });
            // Undo for delete: restore (soft-undelete) and re-render.
            pushUndo("Delete '" + name + "'", async () => {
                const r = await $.ajax({
                    url: URLS.activitiesRestore(id),
                    type: 'POST',
                    data: { _token: CSRF },
                });
                if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                _renderCardOrReplace(r.data);
            });
        }
    });
        }
    });
});

// ---------- DRAFTS ----------
// Bump the small badge on the "Drafts" button. Hides itself at zero.
function bumpDraftsBadge(delta) {
    const $badge = $('#draftsBadge');
    const current = parseInt($badge.text(), 10) || 0;
    const next = Math.max(0, current + delta);
    $badge.text(next);
    if (next === 0) {
        $badge.hide();
    } else {
        $badge.show();
    }
}

// Render a single draft entry inside the Drafts modal. Lean payload — no
// items/workers — just enough to identify what the user is restoring.
function renderDraftRow(d) {
    const lots = (d.lots || []).map(l => escapeHtml(l.lotName)).join(', ') || '—';
    const targetDate = d.targetDate ? (() => {
        const dt = parseLocalDate(d.targetDate);
        return dt ? `${DAY_SHORT[dt.getDay()]}, ${MONTH_SHORT[dt.getMonth()]} ${dt.getDate()}, ${dt.getFullYear()}` : d.targetDate;
    })() : 'No date';
    const priorityCls = 'priority-' + (d.priority || 'medium');
    const priorityLabel = (d.priority || 'medium');
    const priorityCap = priorityLabel.charAt(0).toUpperCase() + priorityLabel.slice(1);
    return `<div class="card mb-2 draft-row" data-id="${d.id}" data-name="${escapeHtml(d.activityTitle)}" style="border-left: 3px solid #50a5f1; cursor: default;">
        <div class="card-body p-3">
            <div class="d-flex justify-content-between align-items-start gap-3 flex-wrap">
                <div class="flex-grow-1" style="min-width:0;">
                    <h6 class="text-dark mb-1">${escapeHtml(d.activityTitle)}</h6>
                    <div class="text-secondary" style="font-size:12px;">
                        <i class="bx bx-calendar"></i> ${escapeHtml(targetDate)}
                        <span class="ms-2"><i class="bx bx-map-pin"></i> ${lots}</span>
                    </div>
                    ${d.updatedAt ? `<small class="text-secondary"><i class="bx bx-time-five"></i> Drafted ${escapeHtml(d.updatedAt)}</small>` : ''}
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <span class="sm-pill ${priorityCls}">${escapeHtml(priorityCap)}</span>
                    <button type="button" class="btn btn-sm btn-success restore-draft-btn" data-id="${d.id}" data-name="${escapeHtml(d.activityTitle)}" title="Restore this draft back to the activities panel">
                        <i class="bx bx-archive-out me-1"></i> Restore
                    </button>
                    <button type="button" class="btn btn-sm btn-outline-danger delete-draft-btn" data-id="${d.id}" data-name="${escapeHtml(d.activityTitle)}" title="Delete this draft permanently (can be undone via Ctrl+Z)">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
            </div>
        </div>
    </div>`;
}

function renderDraftsList(drafts) {
    const $container = $('#draftsListContainer');
    if (!drafts || drafts.length === 0) {
        $container.empty().hide();
        $('#draftsEmpty').show();
        return;
    }
    $('#draftsEmpty').hide();
    $container.show().html(drafts.map(renderDraftRow).join(''));
}

// Open the Drafts modal and fetch the current list. Refresh every open so
// the modal reflects the latest server state even across multiple tabs.
$(document).on('click', '#openDraftsModalBtn', function () {
    $('#draftsListContainer').show().html(`
        <div class="text-center py-4 text-secondary">
            <i class="bx bx-loader-alt bx-spin" style="font-size: 1.5rem;"></i>
            <p class="text-dark mt-2 mb-0">Loading drafts…</p>
        </div>
    `);
    $('#draftsEmpty').hide();
    $('#draftsModal').modal('show');

    $.get(URLS.activitiesDrafts(), function (res) {
        if (!res.success) {
            toastr.error(res.message || 'Could not load drafts');
            renderDraftsList([]);
            return;
        }
        // Sync the badge with the server's authoritative count.
        const n = (res.data || []).length;
        $('#draftsBadge').text(n);
        if (n === 0) $('#draftsBadge').hide(); else $('#draftsBadge').show();
        renderDraftsList(res.data || []);
    }).fail(function (xhr) {
        toastr.error(xhr.responseJSON?.message || 'Could not load drafts');
        renderDraftsList([]);
    });
});

// Move an active activity into the drafts bin. Fades the card out of the
// timeline, bumps both badges, and pushes a one-click undo onto the stack.
$(document).on('click', '.to-draft-activity-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const $btn = $(this).prop('disabled', true);
    $.ajax({
        url: URLS.activitiesToDraft(id),
        type: 'POST',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(`"${name}" moved to drafts`);
            $('#activitiesList [data-id="' + id + '"]').fadeOut(250, function () {
                $(this).remove();
                bumpBadge('badge-activities', -1);
                bumpDraftsBadge(1);
                reorderAndRenumberActivities();
                if (typeof recomputeLotDayZero === 'function') recomputeLotDayZero();
                if ($('#activitiesList .activity-card').length === 0) {
                    $('#activitiesList').html(`
                        <div id="activitiesEmpty">
                            <div class="text-center text-secondary py-5">
                                <i class='bx bx-task' style="font-size:2.5rem;"></i>
                                <p class="text-dark mt-2 mb-0">No activities defined yet.</p>
                                <small>Click <strong>Add Activity</strong> to define your first step.</small>
                            </div>
                        </div>
                    `);
                }
            });
            // Undo: pull straight back out of drafts (no confirm dialog).
            // _renderCardOrReplace bumps badge-activities internally; we only
            // need to drop the drafts badge here.
            pushUndo("Move '" + name + "' to drafts", async () => {
                const r = await $.ajax({
                    url: URLS.activitiesFromDraft(id),
                    type: 'POST',
                    data: { _token: CSRF },
                });
                if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                _renderCardOrReplace(r.data);
                bumpDraftsBadge(-1);
            });
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Move to drafts failed'),
        complete: () => $btn.prop('disabled', false)
    });
});

// Restore a draft back to the activity panel. The modal stays open so the
// user can restore several drafts in a row without re-opening it.
$(document).on('click', '.restore-draft-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    const $row = $(this).closest('.draft-row');
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i>');
    $.ajax({
        url: URLS.activitiesFromDraft(id),
        type: 'POST',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(`"${name}" restored`);
            // _renderCardOrReplace handles the activities badge bump for us.
            _renderCardOrReplace(res.data);
            bumpDraftsBadge(-1);
            $row.fadeOut(250, function () {
                $(this).remove();
                if ($('#draftsListContainer .draft-row').length === 0) {
                    $('#draftsListContainer').hide();
                    $('#draftsEmpty').show();
                }
            });
            // Undo: shove it right back into drafts and rip the card out again.
            pushUndo("Restore '" + name + "' from drafts", async () => {
                const r = await $.ajax({
                    url: URLS.activitiesToDraft(id),
                    type: 'POST',
                    data: { _token: CSRF },
                });
                if (!r || !r.success) throw new Error((r && r.message) || 'undo failed');
                _removeCardById(id);
                bumpDraftsBadge(1);
            });
        },
        error: (xhr) => {
            toastr.error(xhr.responseJSON?.message || 'Restore failed');
            $btn.prop('disabled', false).html('<i class="bx bx-archive-out me-1"></i> Restore');
        }
    });
});

// Permanently delete a drafted activity. The existing activitiesDelete
// endpoint soft-deletes (deleteStatus = 0) without caring whether the row
// is a draft or active, so we reuse it. Undo goes back via the existing
// restore endpoint which preserves isDraft = 1, so the entry returns to
// the drafts list rather than the active timeline.
$(document).on('click', '.delete-draft-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    confirmAction({
        title: 'Delete drafted activity',
        message: 'Permanently delete drafted activity <strong>"' + escapeHtml(name) + '"</strong>?',
        detail: 'You can immediately undo this (Ctrl+Z) — the draft is soft-deleted and can be restored back into the drafts list.',
        confirmText: 'Delete Draft',
        onConfirm: () => {
            const $row = $('#draftsListContainer .draft-row[data-id="' + id + '"]');
            $.ajax({
                url: URLS.activitiesDelete(id),
                type: 'DELETE',
                data: { _token: CSRF },
                success: (res) => {
                    if (!res.success) { toastr.error(res.message); return; }
                    toastr.success(`Draft "${name}" deleted`);
                    $row.fadeOut(250, function () {
                        $(this).remove();
                        bumpDraftsBadge(-1);
                        if ($('#draftsListContainer .draft-row').length === 0) {
                            $('#draftsListContainer').hide();
                            $('#draftsEmpty').show();
                        }
                    });
                    // Undo: restore brings it back as a draft (isDraft is preserved).
                    pushUndo("Delete draft '" + name + "'", async () => {
                        const r = await $.ajax({
                            url: URLS.activitiesRestore(id),
                            type: 'POST',
                            data: { _token: CSRF },
                        });
                        if (!r || !r.success) throw new Error((r && r.message) || 'restore failed');
                        bumpDraftsBadge(1);
                        // If the drafts modal is open, re-append the restored
                        // row so the user sees it return without re-opening.
                        if ($('#draftsModal').hasClass('show')) {
                            $('#draftsEmpty').hide();
                            $('#draftsListContainer').show().append(renderDraftRow({
                                id: r.data.id,
                                activityTitle: r.data.activityTitle,
                                targetDate: r.data.targetDate,
                                lots: r.data.lots || [],
                                priority: r.data.priority,
                                updatedAt: r.data.updated_at || null,
                            }));
                        }
                    });
                },
                error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed'),
            });
        }
    });
});

// ---------- LABOR EXPENSE SUMMARY ----------
function timeRequiredShortLabel(value) {
    if (value === 'whole') return 'Whole';
    if (value === 'n/a')   return 'N/A';
    return 'Half';
}

function renderLaborSummary(d) {
    if (!d || d.totalActivities === 0) {
        return `
            <div class="text-center py-4">
                <i class="bx bx-money text-secondary" style="font-size: 2.5rem;"></i>
                <p class="text-dark mt-2 mb-1">No activities yet.</p>
                <small class="text-secondary">Once you add activities with workers assigned, this is where you'll see the running labor cost.</small>
            </div>`;
    }

    const t = d.totals || { halfDays: 0, wholeDays: 0, naCount: 0, totalAssignments: 0 };
    const dayType = d.dayType || 'DAS';
    const phases = d.phases || {};
    const pre = phases.preDayZero || { count: 0, cost: 0, halfDays: 0, wholeDays: 0, naCount: 0 };
    const main = phases.cropping  || { count: 0, cost: 0, halfDays: 0, wholeDays: 0, naCount: 0 };
    const una = phases.unanchored || { count: 0, cost: 0, halfDays: 0, wholeDays: 0, naCount: 0 };
    const showUnanchored = una.count > 0;
    const pctPre = d.grandTotal > 0 ? Math.round((pre.cost / d.grandTotal) * 100) : 0;
    const pctMain = d.grandTotal > 0 ? Math.round((main.cost / d.grandTotal) * 100) : 0;

    // ----- Hero card with the grand total + the phase split.
    const phaseCardCss = 'background: rgba(255,255,255,0.14); border-radius: 6px; padding: 10px 14px; min-width: 200px; backdrop-filter: blur(2px);';
    const hero = `
        <div class="card border-0 mb-3" style="background: linear-gradient(135deg, #0f8a5f 0%, #1abc9c 100%); color: #fff;">
            <div class="card-body p-3">
                <div class="d-flex justify-content-between align-items-end flex-wrap gap-2 mb-3">
                    <div>
                        <div style="font-size: 12px; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.5px;">Total labor expense so far</div>
                        <h2 class="mb-0 mt-1" style="color: #fff; font-weight: 700;">${fmtPeso(d.grandTotal)}</h2>
                    </div>
                    <div class="text-end" style="font-size: 12px; line-height: 1.5; opacity: 0.95;">
                        ${d.totalActivities} ${d.totalActivities === 1 ? 'activity' : 'activities'} ·
                        ${t.totalAssignments} ${t.totalAssignments === 1 ? 'worker assignment' : 'worker assignments'}<br>
                        ${t.halfDays} half-day · ${t.wholeDays} whole-day · ${t.naCount} N/A
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap">
                    <div style="${phaseCardCss}">
                        <div style="font-size: 10.5px; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.6px;">
                            <i class="bx bx-shovel"></i> Land Preparation
                            <span class="ms-1" style="opacity:0.75;">(${escapeHtml(dayType)} &lt; 0)</span>
                        </div>
                        <div style="font-size: 18pt; font-weight: 700; line-height: 1.1; margin-top: 2px;">${fmtPeso(pre.cost)}</div>
                        <div style="font-size: 11px; opacity: 0.9;">
                            ${pre.count} ${pre.count === 1 ? 'activity' : 'activities'}
                            ${pre.halfDays || pre.wholeDays || pre.naCount ? `· ${pre.halfDays}H / ${pre.wholeDays}W / ${pre.naCount}N` : ''}
                            ${d.grandTotal > 0 ? `<span class="ms-1" style="opacity:0.85;">· ${pctPre}%</span>` : ''}
                        </div>
                    </div>
                    <div style="${phaseCardCss}">
                        <div style="font-size: 10.5px; opacity: 0.85; text-transform: uppercase; letter-spacing: 0.6px;">
                            <i class="bx bx-leaf"></i> Main Cropping
                            <span class="ms-1" style="opacity:0.75;">(${escapeHtml(dayType)} 0 onwards)</span>
                        </div>
                        <div style="font-size: 18pt; font-weight: 700; line-height: 1.1; margin-top: 2px;">${fmtPeso(main.cost)}</div>
                        <div style="font-size: 11px; opacity: 0.9;">
                            ${main.count} ${main.count === 1 ? 'activity' : 'activities'}
                            ${main.halfDays || main.wholeDays || main.naCount ? `· ${main.halfDays}H / ${main.wholeDays}W / ${main.naCount}N` : ''}
                            ${d.grandTotal > 0 ? `<span class="ms-1" style="opacity:0.85;">· ${pctMain}%</span>` : ''}
                        </div>
                    </div>
                    ${showUnanchored ? `
                    <div style="${phaseCardCss} background: rgba(0,0,0,0.18);">
                        <div style="font-size: 10.5px; opacity: 0.9; text-transform: uppercase; letter-spacing: 0.6px;">
                            <i class="bx bx-error-circle"></i> Unanchored
                            <span class="ms-1" style="opacity:0.75;">(no ${escapeHtml(dayType)} 0)</span>
                        </div>
                        <div style="font-size: 18pt; font-weight: 700; line-height: 1.1; margin-top: 2px;">${fmtPeso(una.cost)}</div>
                        <div style="font-size: 11px; opacity: 0.9;">
                            ${una.count} ${una.count === 1 ? 'activity' : 'activities'}
                            ${una.halfDays || una.wholeDays || una.naCount ? `· ${una.halfDays}H / ${una.wholeDays}W / ${una.naCount}N` : ''}
                        </div>
                    </div>` : ''}
                </div>
            </div>
        </div>`;

    // ----- Per-worker breakdown table with phase split columns.
    const showUnaCol = una.count > 0;
    let workerRows;
    if ((d.perWorker || []).length === 0) {
        workerRows = `<tr><td colspan="${showUnaCol ? 7 : 6}" class="text-center text-secondary py-3">No workers have been assigned to any activity yet.</td></tr>`;
    } else {
        workerRows = d.perWorker.map(w => {
            const unaCell = showUnaCol
                ? `<td class="text-end text-secondary" title="Activities with no ${dayType} 0 set">${fmtPeso(w.unanchoredTotal || 0)}</td>`
                : '';
            return `
            <tr>
                <td>
                    <strong class="text-dark">${escapeHtml(w.name)}</strong>
                    <small class="text-secondary ms-1">#${w.priority}</small>
                </td>
                <td class="text-end text-dark">${fmtPeso(w.costPerHalfDay)}</td>
                <td class="text-center text-dark">
                    <span class="badge bg-light text-dark" title="Half-day activities">${w.halfDays} H</span>
                    <span class="badge bg-light text-dark ms-1" title="Whole-day activities (counted 2x)">${w.wholeDays} W</span>
                    ${w.naCount > 0 ? `<span class="badge bg-light text-secondary ms-1" title="N/A activities (no labor billed)">${w.naCount} N/A</span>` : ''}
                </td>
                <td class="text-end" style="color:#a05a00;" title="Earned during land preparation (${dayType} < 0)">${fmtPeso(w.preDayZeroTotal || 0)}</td>
                <td class="text-end" style="color:#0f6f4d;" title="Earned during main cropping season (${dayType} 0 onwards)">${fmtPeso(w.croppingTotal || 0)}</td>
                ${unaCell}
                <td class="text-end text-dark"><strong>${fmtPeso(w.total)}</strong></td>
            </tr>`;
        }).join('');
    }

    const workerTable = `
        <h6 class="text-dark mb-2"><i class="bx bx-user me-1"></i> By Worker</h6>
        <div class="table-responsive mb-3">
            <table class="table table-sm align-middle mb-0">
                <thead style="background: #f8f9fa;">
                    <tr>
                        <th class="text-secondary">Worker</th>
                        <th class="text-secondary text-end">Half-day rate</th>
                        <th class="text-secondary text-center">Assignments</th>
                        <th class="text-end" style="background:#fff8e1; color:#a05a00; font-size:11px;" title="Earned during land preparation (${dayType} < 0)">Land Prep</th>
                        <th class="text-end" style="background:#e8f5ee; color:#0f6f4d; font-size:11px;" title="Earned during main cropping season (${dayType} 0 onwards)">Cropping</th>
                        ${showUnaCol ? `<th class="text-secondary text-end" title="Activities with no ${dayType} 0 set">Unanchored</th>` : ''}
                        <th class="text-secondary text-end">Earned</th>
                    </tr>
                </thead>
                <tbody>${workerRows}</tbody>
            </table>
        </div>`;

    // ----- Per-activity breakdown, split into phase sections with subtotals.
    const renderActivityRow = (a) => {
        const dateObj = a.targetDate ? parseLocalDate(a.targetDate) : null;
        const endDateObj = a.targetEndDate ? parseLocalDate(a.targetEndDate) : null;
        let pretty;
        if (dateObj && endDateObj && endDateObj > dateObj) {
            pretty = `${MONTH_SHORT[dateObj.getMonth()]} ${dateObj.getDate()} → ${MONTH_SHORT[endDateObj.getMonth()]} ${endDateObj.getDate()}, ${endDateObj.getFullYear()}`;
        } else if (dateObj) {
            pretty = `${MONTH_SHORT[dateObj.getMonth()]} ${dateObj.getDate()}, ${dateObj.getFullYear()}`;
        } else {
            pretty = 'No date';
        }
        const trClass = a.cost === 0 ? 'text-secondary' : '';
        const days = a.rangeDays || 1;
        const units = (a.unitsPerDay !== undefined && a.unitsPerDay !== null) ? a.unitsPerDay : 1;
        const rateSum = a.workerRateSum || 0;
        const daysBadge = days > 1
            ? `<span class="badge bg-warning text-dark ms-1" title="Multi-day range — bills every day">${days}d</span>`
            : '';
        const dasLbl = (a.das === null || a.das === undefined)
            ? '<span class="badge bg-light text-secondary" title="No Day 0 anchor set for any covered lot">—</span>'
            : `<span class="badge bg-light text-dark">${escapeHtml(dayType)}${a.das >= 0 ? '+' : ''}${a.das}</span>`;
        const formula = `${fmtPeso(rateSum)} × ${units} × ${days}`;
        return `
            <tr class="${trClass}">
                <td class="text-dark"><strong>${escapeHtml(a.activityTitle)}</strong></td>
                <td>${escapeHtml(pretty)}${daysBadge}</td>
                <td>${dasLbl}</td>
                <td><span class="badge bg-light text-dark">${timeRequiredShortLabel(a.timeRequired)}</span></td>
                <td class="text-center">${a.workerCount}</td>
                <td class="text-end text-secondary" style="font-size:11px;" title="Σ(rates) × units/day × days = cost">${escapeHtml(formula)}</td>
                <td class="text-end"><strong>${fmtPeso(a.cost)}</strong></td>
            </tr>`;
    };

    const renderActivitySection = (sectionItems, label, icon, accentColor, subtotal) => {
        if (!sectionItems.length) return '';
        const rows = sectionItems.map(renderActivityRow).join('');
        return `
            <div class="mt-3" style="border-left: 3px solid ${accentColor}; padding-left: 12px;">
                <div class="d-flex justify-content-between align-items-center mb-2 flex-wrap gap-2">
                    <h6 class="text-dark mb-0" style="color: ${accentColor} !important;">
                        <i class="bx ${icon} me-1"></i> ${escapeHtml(label)}
                        <span class="badge ms-1" style="background: ${accentColor}1a; color: ${accentColor}; font-weight: 500;">${sectionItems.length} ${sectionItems.length === 1 ? 'activity' : 'activities'}</span>
                    </h6>
                    <div class="text-dark" style="font-size: 13px;">
                        Subtotal: <strong style="color: ${accentColor};">${fmtPeso(subtotal)}</strong>
                    </div>
                </div>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead style="background: #f8f9fa;">
                            <tr>
                                <th class="text-secondary">Activity</th>
                                <th class="text-secondary">Date</th>
                                <th class="text-secondary">${escapeHtml(dayType)}</th>
                                <th class="text-secondary">Time</th>
                                <th class="text-secondary text-center">Workers</th>
                                <th class="text-secondary text-end" title="Σ(half-day rates) × units/day × days">Formula</th>
                                <th class="text-secondary text-end">Cost</th>
                            </tr>
                        </thead>
                        <tbody>${rows}</tbody>
                    </table>
                </div>
            </div>`;
    };

    const preItems = (d.perActivity || []).filter(a => a.phase === 'preDayZero');
    const cropItems = (d.perActivity || []).filter(a => a.phase === 'cropping');
    const unaItems = (d.perActivity || []).filter(a => a.phase === 'unanchored');

    const activityTable = (d.perActivity || []).length ? `
        <details class="mt-3" open>
            <summary class="text-dark fw-semibold" style="cursor: pointer; font-size: 14px;">
                <i class="bx bx-task me-1"></i> By Activity (${d.perActivity.length})
                <small class="text-secondary fw-normal ms-1">— grouped by phase</small>
            </summary>
            ${renderActivitySection(preItems, `Land Preparation · ${dayType} < 0`, 'bx-shovel', '#a05a00', pre.cost)}
            ${renderActivitySection(cropItems, `Main Cropping · ${dayType} 0 onwards`, 'bx-leaf', '#0f6f4d', main.cost)}
            ${renderActivitySection(unaItems, `Unanchored · no ${dayType} 0 set`, 'bx-error-circle', '#74788d', una.cost)}
        </details>` : '';

    return hero + workerTable + activityTable;
}

// Latest labor-summary payload — held so Print / Copy / PDF can run without refetching.
let LATEST_LABOR_DATA = null;

function getLaborFilterPayload() {
    const groupIds = $('#laborGroupsContainer .lot-chip.active')
        .map((_, e) => parseInt($(e).data('group-id'), 10)).get();
    const workerIds = $('#laborWorkersContainer .lot-chip.active')
        .map((_, e) => parseInt($(e).data('worker-id'), 10)).get();
    const dasMinRaw = ($('#laborDasMin').val() || '').trim();
    const dasMaxRaw = ($('#laborDasMax').val() || '').trim();
    const startDateRaw = ($('#laborStartDate').val() || '').trim();
    const endDateRaw   = ($('#laborEndDate').val()   || '').trim();
    const payload = {};
    if (groupIds.length) payload.groupIds = groupIds;
    if (workerIds.length) payload.workerIds = workerIds;
    if (dasMinRaw !== '' && !isNaN(parseInt(dasMinRaw, 10))) payload.dasMin = parseInt(dasMinRaw, 10);
    if (dasMaxRaw !== '' && !isNaN(parseInt(dasMaxRaw, 10))) payload.dasMax = parseInt(dasMaxRaw, 10);
    if (startDateRaw !== '') payload.startDate = startDateRaw;
    if (endDateRaw   !== '') payload.endDate   = endDateRaw;
    return payload;
}

function updateLaborFilterHint() {
    const filters = getLaborFilterPayload();
    const parts = [];
    if (filters.groupIds) parts.push(`${filters.groupIds.length} ${filters.groupIds.length === 1 ? 'group' : 'groups'}`);
    if (filters.workerIds) parts.push(`${filters.workerIds.length} ${filters.workerIds.length === 1 ? 'worker' : 'workers'}`);
    if (filters.dasMin !== undefined || filters.dasMax !== undefined) {
        const lo = filters.dasMin !== undefined ? filters.dasMin : '−∞';
        const hi = filters.dasMax !== undefined ? filters.dasMax : '+∞';
        parts.push(`DAS [${lo}, ${hi}]`);
    }
    if (filters.startDate || filters.endDate) {
        const lo = filters.startDate || '—';
        const hi = filters.endDate   || '—';
        parts.push(`Date [${lo}, ${hi}]`);
    }
    $('#laborFilterCountHint').text(parts.length ? `Filters active: ${parts.join(' · ')}` : '');
}

function reloadLaborSummary() {
    const filters = getLaborFilterPayload();
    $('#laborSummaryBody').html(`
        <div class="text-center py-4 text-secondary">
            <i class="bx bx-loader-alt bx-spin" style="font-size: 1.5rem;"></i>
            <p class="text-dark mt-2 mb-0">Calculating&hellip;</p>
        </div>
    `);
    updateLaborFilterHint();
    $.get(URLS.activitiesLabor(), filters, function (res) {
        if (!res.success) {
            LATEST_LABOR_DATA = null;
            $('#laborSummaryBody').html(
                `<div class="alert alert-danger mb-0">${escapeHtml(res.message || 'Could not load labor summary')}</div>`
            );
            return;
        }
        LATEST_LABOR_DATA = res.data;
        $('#laborSummaryBody').html(renderLaborSummary(res.data));
    }).fail(function (xhr) {
        LATEST_LABOR_DATA = null;
        $('#laborSummaryBody').html(
            `<div class="alert alert-danger mb-0">${escapeHtml(xhr.responseJSON?.message || 'Could not load labor summary')}</div>`
        );
    });
}

// Open the full Worker Presentation in a new tab — it renders the printable
// report (intro, activities, monthly labor, per-worker pages, irrigation,
// and calendar) and has its own "Save as PDF / Print" button at the top.
// Open the worker-presentation options modal first, then open the report
// in a new tab with each toggle's choice as a query param.
$(document).on('click', '#openCardViewerBtn', function () {
    // Card Viewer is a self-contained slide deck — no options modal,
    // just opens the active version's per-day slides in a new tab.
    window.open(URLS.cardViewer(), '_blank');
});

$(document).on('click', '#openWorkerPresentationBtn', function () {
    // Reset the worker filter every open so it starts in the opt-out
    // baseline ("everyone included, uncheck to exclude"). Without this
    // re-check the modal would remember an earlier unchecked state and
    // surprise the user.
    $('#wpWorkersList .wp-worker-pick').prop('checked', true);
    $('#workerPresentationOptionsModal').modal('show');
});
// Quick controls for the workers checkbox list inside the options modal.
$(document).on('click', '#wpWorkersSelectAllBtn', function () {
    $('#wpWorkersList .wp-worker-pick').prop('checked', true);
});
$(document).on('click', '#wpWorkersClearBtn', function () {
    $('#wpWorkersList .wp-worker-pick').prop('checked', false);
});
// When "labor only" is checked, gray out section toggles that wouldn't
// render anyway — purely informational, the server still honors them but
// the visual cue helps the user understand the interaction.
$(document).on('change', '#optLaborOnly', function () {
    const off = $(this).is(':checked');
    $('#optShowDesc, #optShowIrrigation, #optShowCalendar')
        .closest('.form-check').css('opacity', off ? 0.45 : 1);
});

$(document).on('click', '#presentGenerateBtn', function () {
    const flags = {
        showDesc:       $('#optShowDesc').is(':checked')       ? 1 : 0,
        showIrrigation: $('#optShowIrrigation').is(':checked') ? 1 : 0,
        showCalendar:   $('#optShowCalendar').is(':checked')   ? 1 : 0,
        laborOnly:      $('#optLaborOnly').is(':checked')      ? 1 : 0,
    };
    // Worker filter is opt-out: all checkboxes start checked, the user
    // unchecks anyone they want to exclude. Send only the checked IDs.
    // If the user manages to uncheck EVERYONE we still pass at least one
    // sentinel id (0 = "no real worker") so the server filters down to
    // zero workers instead of falling back to "include everyone" — that
    // ambiguity is exactly the bug the user reported (unchecking Ariel
    // had no effect because empty-array meant "show all").
    const $allWorkers = $('#wpWorkersList .wp-worker-pick');
    const $checked   = $allWorkers.filter(':checked');
    let workerIds = $checked.map(function () { return parseInt($(this).val(), 10); }).get();
    const userMadeASelection = $allWorkers.length > 0; // empty schedule has no boxes
    if (userMadeASelection && workerIds.length === 0) {
        // Sentinel -1: a negative id can't match any real worker, AND it
        // survives the server's array_filter() (which strips 0/null/'').
        workerIds = [-1];
    }
    const flagQs    = Object.entries(flags).map(([k, v]) => `&${k}=${v}`).join('');
    const workerQs  = workerIds.map(id => `&workerIds[]=${id}`).join('');
    $('#workerPresentationOptionsModal').modal('hide');
    window.open(URLS.workerPresentation() + flagQs + workerQs, '_blank');
});

$(document).on('click', '#openLaborSummaryBtn', function () {
    // Reset filters every open so the user always sees the unfiltered grand
    // total first; they can then narrow down from there.
    $('#laborGroupsContainer .lot-chip').removeClass('active').attr('aria-pressed', 'false');
    $('#laborWorkersContainer .lot-chip').removeClass('active').attr('aria-pressed', 'false');
    $('#laborDasMin').val('');
    $('#laborDasMax').val('');
    $('#laborStartDate').val('');
    $('#laborEndDate').val('');
    $('#laborSummaryModal').modal('show');
    reloadLaborSummary();
});

// ---- Filter chip handlers ----
$(document).on('click', '#laborGroupsContainer .lot-chip', function () {
    const $c = $(this);
    $c.toggleClass('active');
    $c.attr('aria-pressed', $c.hasClass('active') ? 'true' : 'false');
    updateLaborFilterHint();
});
$(document).on('click', '#laborWorkersContainer .lot-chip', function () {
    const $c = $(this);
    $c.toggleClass('active');
    $c.attr('aria-pressed', $c.hasClass('active') ? 'true' : 'false');
    updateLaborFilterHint();
});
$(document).on('click', '#laborSelectAllGroups', function () {
    $('#laborGroupsContainer .lot-chip').addClass('active').attr('aria-pressed', 'true');
    updateLaborFilterHint();
});
$(document).on('click', '#laborClearGroups', function () {
    $('#laborGroupsContainer .lot-chip').removeClass('active').attr('aria-pressed', 'false');
    updateLaborFilterHint();
});
$(document).on('click', '#laborSelectAllWorkers', function () {
    $('#laborWorkersContainer .lot-chip').addClass('active').attr('aria-pressed', 'true');
    updateLaborFilterHint();
});
$(document).on('click', '#laborClearWorkers', function () {
    $('#laborWorkersContainer .lot-chip').removeClass('active').attr('aria-pressed', 'false');
    updateLaborFilterHint();
});
$(document).on('input', '#laborDasMin, #laborDasMax', updateLaborFilterHint);
// Date inputs fire 'change' (not 'input') reliably across browsers; refresh
// the hint and reload immediately so the user gets feedback right away.
$(document).on('change', '#laborStartDate, #laborEndDate', function () {
    updateLaborFilterHint();
    reloadLaborSummary();
});
$(document).on('click', '#laborDateClearBtn', function () {
    $('#laborStartDate').val('');
    $('#laborEndDate').val('');
    updateLaborFilterHint();
    reloadLaborSummary();
});

$(document).on('click', '#laborApplyFiltersBtn', function () {
    reloadLaborSummary();
});
$(document).on('click', '#laborResetFiltersBtn', function () {
    $('#laborGroupsContainer .lot-chip').removeClass('active').attr('aria-pressed', 'false');
    $('#laborWorkersContainer .lot-chip').removeClass('active').attr('aria-pressed', 'false');
    $('#laborDasMin').val('');
    $('#laborDasMax').val('');
    $('#laborStartDate').val('');
    $('#laborEndDate').val('');
    updateLaborFilterHint();
    reloadLaborSummary();
});

// ---- Print + PDF + Copy ----
function buildLaborPrintHtml(d) {
    if (!d) return '<html><body><p>No data.</p></body></html>';
    const t = d.totals || {};
    const filters = d.filters || {};
    const dayType = d.dayType || 'DAS';
    const phases = d.phases || {};
    const pre = phases.preDayZero || { count: 0, cost: 0 };
    const main = phases.cropping  || { count: 0, cost: 0 };
    const una = phases.unanchored || { count: 0, cost: 0 };
    const showUnaCol = una.count > 0;
    const generatedAt = new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });

    const filterChips = [];
    if (filters.groupIds && filters.groupIds.length) {
        filterChips.push(`<span class="f-chip">Groups: ${filters.groupIds.length}</span>`);
    } else if (filters.lotIds && filters.lotIds.length) {
        filterChips.push(`<span class="f-chip">Lots: ${filters.lotIds.length}</span>`);
    }
    if (filters.workerIds && filters.workerIds.length) {
        filterChips.push(`<span class="f-chip">Workers: ${filters.workerIds.length}</span>`);
    }
    if (filters.dasMin !== null || filters.dasMax !== null) {
        const lo = filters.dasMin !== null ? filters.dasMin : '−∞';
        const hi = filters.dasMax !== null ? filters.dasMax : '+∞';
        filterChips.push(`<span class="f-chip">${escapeHtml(d.dayType || 'DAS')} [${lo}, ${hi}]</span>`);
    }
    const filterStrip = filterChips.length
        ? `<div class="filter-strip">Filters applied: ${filterChips.join(' ')}</div>`
        : '<div class="filter-strip muted">No filters applied — all activities included.</div>';

    const workerRows = (d.perWorker || []).map(w => {
        const unaCell = showUnaCol ? `<td class="num">${fmtPeso(w.unanchoredTotal || 0)}</td>` : '';
        return `
        <tr>
            <td>${escapeHtml(w.name)} <span class="muted">#${w.priority}</span></td>
            <td class="num">${fmtPeso(w.costPerHalfDay)}</td>
            <td class="num">${w.halfDays}</td>
            <td class="num">${w.wholeDays}</td>
            <td class="num">${w.naCount}</td>
            <td class="num phase-pre">${fmtPeso(w.preDayZeroTotal || 0)}</td>
            <td class="num phase-crop">${fmtPeso(w.croppingTotal || 0)}</td>
            ${unaCell}
            <td class="num"><strong>${fmtPeso(w.total)}</strong></td>
        </tr>`;
    }).join('') || `<tr><td colspan="${showUnaCol ? 9 : 8}" class="muted center">No workers contributed to the cost under current filters.</td></tr>`;

    const renderPrintRow = (a) => {
        const dateStr = a.targetEndDate && a.targetEndDate !== a.targetDate
            ? `${a.targetDate} → ${a.targetEndDate}`
            : (a.targetDate || 'No date');
        const dasLbl = (a.das === null || a.das === undefined) ? '—' : (a.das >= 0 ? '+' : '') + a.das;
        const tLbl = timeRequiredShortLabel(a.timeRequired);
        const days = a.rangeDays || 1;
        const units = (a.unitsPerDay !== undefined && a.unitsPerDay !== null) ? a.unitsPerDay : 1;
        const rateSum = a.workerRateSum || 0;
        const formula = `${fmtPeso(rateSum)} × ${units} × ${days}`;
        return `<tr>
            <td>${escapeHtml(a.activityTitle)}</td>
            <td>${escapeHtml(dateStr)}${days > 1 ? ` <span class="muted">(${days}d)</span>` : ''}</td>
            <td class="center">${escapeHtml(dayType)}${escapeHtml(dasLbl)}</td>
            <td class="center">${tLbl}</td>
            <td class="num">${a.workerCount}</td>
            <td class="num muted" style="font-size:8.5pt;">${escapeHtml(formula)}</td>
            <td class="num"><strong>${fmtPeso(a.cost)}</strong></td>
        </tr>`;
    };
    const preActs   = (d.perActivity || []).filter(a => a.phase === 'preDayZero');
    const cropActs  = (d.perActivity || []).filter(a => a.phase === 'cropping');
    const unaActs   = (d.perActivity || []).filter(a => a.phase === 'unanchored');
    const activityTableHead = `<thead><tr><th style="width:24%;">Activity</th><th style="width:14%;">Date</th><th class="center" style="width:9%;">${escapeHtml(dayType)}</th><th class="center" style="width:9%;">Time</th><th class="num" style="width:8%;">Workers</th><th class="num" style="width:20%;" title="Σ(rates) × units × days">Formula</th><th class="num" style="width:16%;">Cost</th></tr></thead>`;
    const renderPrintSection = (items, label, subtotal, accentColor) => {
        if (items.length === 0) return '';
        return `
            <div class="phase-block" style="border-left: 4px solid ${accentColor}; padding-left: 8px; margin-top: 14px;">
                <div style="display:flex; justify-content:space-between; align-items:baseline;">
                    <h3 style="margin: 0 0 4px; font-size: 11pt; color: ${accentColor};">${escapeHtml(label)} <span class="muted" style="font-weight:400;">(${items.length})</span></h3>
                    <div style="font-size:10pt;">Subtotal: <strong style="color:${accentColor};">${fmtPeso(subtotal)}</strong></div>
                </div>
                <table>${activityTableHead}<tbody>${items.map(renderPrintRow).join('')}</tbody></table>
            </div>`;
    };
    const phaseSections = (d.perActivity || []).length === 0
        ? `<table>${activityTableHead}<tbody><tr><td colspan="7" class="muted center">No activities matched the current filters.</td></tr></tbody></table>`
        : (renderPrintSection(preActs, `Land Preparation · ${dayType} < 0`, pre.cost, '#a05a00') +
           renderPrintSection(cropActs, `Main Cropping · ${dayType} 0 onwards`, main.cost, '#0f6f4d') +
           renderPrintSection(unaActs, `Unanchored · no ${dayType} 0 set`, una.cost, '#74788d'));

    return `<!DOCTYPE html>
<html><head><meta charset="UTF-8"><title>Labor Summary — ${escapeHtml(d.scheduleTitle || '')}</title>
<style>
    @page { size: A4; margin: 18mm 14mm 20mm; }
    * { box-sizing: border-box; }
    body { margin: 0; padding: 0; font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, "Helvetica Neue", Arial, sans-serif; color: #1a1f2b; font-size: 10.5pt; word-break: break-word; overflow-wrap: anywhere; }
    h1 { margin: 0 0 4px; font-size: 18pt; letter-spacing: -0.3px; }
    h2 { margin: 18px 0 8px; font-size: 12pt; text-transform: uppercase; letter-spacing: .5px; border-bottom: 1px solid #d9dde3; padding-bottom: 4px; }
    .meta { color: #6b7280; font-size: 9.5pt; margin-bottom: 12px; }
    .hero { background: #f1f3f7; border-left: 5px solid #0f8a5f; padding: 14px 18px; border-radius: 4px; margin: 8px 0 14px; }
    .hero .label { font-size: 9pt; color: #6b7280; text-transform: uppercase; letter-spacing: .5px; }
    .hero .grand { font-size: 22pt; font-weight: 700; color: #0f8a5f; margin-top: 2px; }
    .hero .sub { font-size: 9.5pt; color: #4a5160; margin-top: 4px; }
    .filter-strip { background: #fff8e1; border: 1px solid #ffe0a8; padding: 6px 10px; border-radius: 4px; font-size: 9.5pt; margin-bottom: 12px; }
    .filter-strip.muted { background: #f8f9fa; border-color: #e6e8ec; color: #6b7280; }
    .f-chip { display: inline-block; background: #fff; border: 1px solid #d9dde3; padding: 1px 8px; border-radius: 10px; margin-right: 4px; font-size: 9pt; }
    .phase-row { display: flex; gap: 8px; margin: 8px 0 14px; }
    .phase-card { flex: 1; border-left: 4px solid #ccc; padding: 8px 10px; background: #fafafa; border-radius: 3px; }
    .phase-card .lbl { font-size: 8.5pt; text-transform: uppercase; letter-spacing: 0.4px; color: #6b7280; }
    .phase-card .amt { font-size: 14pt; font-weight: 700; margin-top: 2px; }
    .phase-card .sub { font-size: 8.5pt; color: #6b7280; }
    .phase-card.pre  { border-color: #a05a00; }
    .phase-card.pre  .amt { color: #a05a00; }
    .phase-card.crop { border-color: #0f6f4d; }
    .phase-card.crop .amt { color: #0f6f4d; }
    .phase-card.una  { border-color: #74788d; }
    .phase-card.una  .amt { color: #74788d; }
    td.phase-pre  { background: #fff8e1; color: #a05a00; }
    td.phase-crop { background: #e8f5ee; color: #0f6f4d; }
    table { width: 100%; border-collapse: collapse; margin-bottom: 8px; table-layout: fixed; }
    th, td { text-align: left; padding: 5px 8px; border-bottom: 1px solid #ecedf0; word-break: break-word; vertical-align: top; font-size: 10pt; }
    th { color: #6b7280; font-size: 9pt; font-weight: 600; text-transform: uppercase; letter-spacing: .3px; }
    .num { text-align: right; }
    .center { text-align: center; }
    .muted { color: #9aa0a6; }
    tfoot td { border-top: 2px solid #1a1f2b; border-bottom: none; font-weight: 700; padding-top: 8px; }
    footer { margin-top: 18px; font-size: 8.5pt; color: #9aa0a6; text-align: center; border-top: 1px solid #ecedf0; padding-top: 8px; }
</style></head>
<body>
    <h1>Labor Expense Summary</h1>
    <div class="meta">${escapeHtml(d.scheduleTitle || '')} · Generated ${escapeHtml(generatedAt)}</div>
    <div class="hero">
        <div class="label">Total Labor Expense</div>
        <div class="grand">${fmtPeso(d.grandTotal)}</div>
        <div class="sub">
            ${d.totalActivities} ${d.totalActivities === 1 ? 'activity' : 'activities'} ·
            ${t.totalAssignments || 0} ${t.totalAssignments === 1 ? 'worker assignment' : 'worker assignments'} ·
            ${t.halfDays || 0} half-day · ${t.wholeDays || 0} whole-day · ${t.naCount || 0} N/A
        </div>
    </div>
    <div class="phase-row">
        <div class="phase-card pre">
            <div class="lbl">Land Preparation (${escapeHtml(dayType)} &lt; 0)</div>
            <div class="amt">${fmtPeso(pre.cost)}</div>
            <div class="sub">${pre.count} ${pre.count === 1 ? 'activity' : 'activities'} · ${pre.halfDays || 0}H / ${pre.wholeDays || 0}W / ${pre.naCount || 0}N</div>
        </div>
        <div class="phase-card crop">
            <div class="lbl">Main Cropping (${escapeHtml(dayType)} 0 onwards)</div>
            <div class="amt">${fmtPeso(main.cost)}</div>
            <div class="sub">${main.count} ${main.count === 1 ? 'activity' : 'activities'} · ${main.halfDays || 0}H / ${main.wholeDays || 0}W / ${main.naCount || 0}N</div>
        </div>
        ${showUnaCol ? `
        <div class="phase-card una">
            <div class="lbl">Unanchored (no ${escapeHtml(dayType)} 0)</div>
            <div class="amt">${fmtPeso(una.cost)}</div>
            <div class="sub">${una.count} ${una.count === 1 ? 'activity' : 'activities'}</div>
        </div>` : ''}
    </div>
    ${filterStrip}
    <h2>By Worker</h2>
    <table>
        <thead><tr><th style="width:22%;">Worker</th><th class="num" style="width:11%;">Rate</th><th class="num" style="width:7%;">H</th><th class="num" style="width:7%;">W</th><th class="num" style="width:7%;">N</th><th class="num phase-pre" style="width:13%;">Land Prep</th><th class="num phase-crop" style="width:13%;">Cropping</th>${showUnaCol ? '<th class="num" style="width:10%;">Unanch.</th>' : ''}<th class="num" style="width:${showUnaCol ? '10%' : '20%'};">Total</th></tr></thead>
        <tbody>${workerRows}</tbody>
    </table>
    <h2>By Activity</h2>
    ${phaseSections}
    <footer>${escapeHtml(d.scheduleTitle || '')} — Labor summary · ${escapeHtml(generatedAt)}</footer>
</body></html>`;
}

function openLaborPrintWindow() {
    if (!LATEST_LABOR_DATA) { toastr.warning('Wait for the summary to finish loading.'); return null; }
    const html = buildLaborPrintHtml(LATEST_LABOR_DATA);
    // Use a hidden iframe so the print dialog doesn't tear the user's tab away.
    let iframe = document.getElementById('laborPrintFrame');
    if (!iframe) {
        iframe = document.createElement('iframe');
        iframe.id = 'laborPrintFrame';
        iframe.style.position = 'fixed';
        iframe.style.left = '-9999px';
        iframe.style.width = '0';
        iframe.style.height = '0';
        iframe.setAttribute('aria-hidden', 'true');
        document.body.appendChild(iframe);
    }
    const doc = iframe.contentWindow.document;
    doc.open();
    doc.write(html);
    doc.close();
    return iframe;
}

function triggerLaborPrint() {
    const iframe = openLaborPrintWindow();
    if (!iframe) return;
    // Give the iframe a tick to lay out before firing print.
    setTimeout(() => {
        try {
            iframe.contentWindow.focus();
            iframe.contentWindow.print();
        } catch (err) {
            toastr.error('Print failed: ' + err.message);
        }
    }, 120);
}

$(document).on('click', '#laborPrintBtn, #laborPdfBtn', function () {
    triggerLaborPrint();
});

function buildLaborPlainText(d) {
    if (!d) return '';
    const lines = [];
    const t = d.totals || {};
    const phases = d.phases || {};
    const pre = phases.preDayZero || { count: 0, cost: 0 };
    const main = phases.cropping  || { count: 0, cost: 0 };
    const una = phases.unanchored || { count: 0, cost: 0 };
    const dayType = d.dayType || 'DAS';
    const generatedAt = new Date().toLocaleString('en-PH', { dateStyle: 'medium', timeStyle: 'short' });
    lines.push(`LABOR EXPENSE SUMMARY — ${d.scheduleTitle || ''}`);
    lines.push('='.repeat(50));
    lines.push(`Generated: ${generatedAt}`);
    lines.push('');
    lines.push(`TOTAL: ${fmtPeso(d.grandTotal)}`);
    lines.push(`  Land Preparation (${dayType} < 0):     ${fmtPeso(pre.cost)}  (${pre.count} ${pre.count === 1 ? 'activity' : 'activities'})`);
    lines.push(`  Main Cropping     (${dayType} 0 onwards): ${fmtPeso(main.cost)}  (${main.count} ${main.count === 1 ? 'activity' : 'activities'})`);
    if (una.count > 0) {
        lines.push(`  Unanchored        (no ${dayType} 0):     ${fmtPeso(una.cost)}  (${una.count} ${una.count === 1 ? 'activity' : 'activities'})`);
    }
    lines.push(`Activities: ${d.totalActivities} · Worker assignments: ${t.totalAssignments || 0}`);
    lines.push(`${t.halfDays || 0} half-day · ${t.wholeDays || 0} whole-day · ${t.naCount || 0} N/A`);
    lines.push('');

    const f = d.filters || {};
    const fParts = [];
    if (f.groupIds && f.groupIds.length) fParts.push(`Groups: ${f.groupIds.length}`);
    else if (f.lotIds && f.lotIds.length) fParts.push(`Lots: ${f.lotIds.length}`);
    if (f.workerIds && f.workerIds.length) fParts.push(`Workers: ${f.workerIds.length}`);
    if (f.dasMin !== null || f.dasMax !== null) {
        const lo = f.dasMin !== null ? f.dasMin : '−∞';
        const hi = f.dasMax !== null ? f.dasMax : '+∞';
        fParts.push(`${d.dayType || 'DAS'} [${lo}, ${hi}]`);
    }
    lines.push(`Filters: ${fParts.length ? fParts.join(' · ') : '(none)'}`);
    lines.push('Formula:  cost = Σ(worker half-day rates) × units/day × days  (units: whole=2, half=1, n/a=0)');
    lines.push('');

    lines.push('BY WORKER');
    lines.push('-'.repeat(64));
    lines.push('Worker'.padEnd(24) + 'Rate'.padStart(10) + 'LandPrep'.padStart(12) + 'Cropping'.padStart(12) + 'Total'.padStart(12));
    (d.perWorker || []).forEach(w => {
        const name = `${w.name} #${w.priority}`.slice(0, 22).padEnd(24);
        const rate = fmtPeso(w.costPerHalfDay).padStart(10);
        const prePart = fmtPeso(w.preDayZeroTotal || 0).padStart(12);
        const cropPart = fmtPeso(w.croppingTotal || 0).padStart(12);
        const total = fmtPeso(w.total).padStart(12);
        lines.push(name + rate + prePart + cropPart + total);
        if (una.count > 0 && (w.unanchoredTotal || 0) > 0) {
            lines.push(' '.repeat(24) + ('(unanchored: ' + fmtPeso(w.unanchoredTotal) + ')').padStart(40));
        }
    });
    if ((d.perWorker || []).length === 0) lines.push('(none)');
    lines.push('');

    const writeActivitySection = (items, label, subtotal) => {
        if (items.length === 0) return;
        lines.push(`${label}  (subtotal: ${fmtPeso(subtotal)})`);
        lines.push('-'.repeat(50));
        items.forEach(a => {
            const date = a.targetEndDate && a.targetEndDate !== a.targetDate
                ? `${a.targetDate} → ${a.targetEndDate} (${a.rangeDays}d)`
                : (a.targetDate || 'No date');
            const das = (a.das === null || a.das === undefined) ? '—' : (a.das >= 0 ? '+' : '') + a.das;
            const timeLbl = timeRequiredShortLabel(a.timeRequired);
            const days = a.rangeDays || 1;
            const units = (a.unitsPerDay !== undefined && a.unitsPerDay !== null) ? a.unitsPerDay : 1;
            const rateSum = a.workerRateSum || 0;
            const formula = `${fmtPeso(rateSum)} × ${units} × ${days}`;
            lines.push(`• ${a.activityTitle}`);
            lines.push(`    ${date}  ${dayType}${das}  [${timeLbl}]  ${a.workerCount} worker(s)`);
            lines.push(`    ${formula} = ${fmtPeso(a.cost)}`);
        });
        lines.push('');
    };
    const allActivities = d.perActivity || [];
    writeActivitySection(allActivities.filter(a => a.phase === 'preDayZero'), `BY ACTIVITY — LAND PREPARATION (${dayType} < 0)`, pre.cost);
    writeActivitySection(allActivities.filter(a => a.phase === 'cropping'), `BY ACTIVITY — MAIN CROPPING (${dayType} 0 onwards)`, main.cost);
    writeActivitySection(allActivities.filter(a => a.phase === 'unanchored'), `BY ACTIVITY — UNANCHORED (no ${dayType} 0)`, una.cost);
    if (allActivities.length === 0) lines.push('BY ACTIVITY: (none)');

    return lines.join('\n');
}

$(document).on('click', '#laborCopyBtn', function () {
    if (!LATEST_LABOR_DATA) { toastr.warning('Wait for the summary to finish loading.'); return; }
    const text = buildLaborPlainText(LATEST_LABOR_DATA);
    const writeToClipboard = (txt) => {
        if (navigator.clipboard && navigator.clipboard.writeText) {
            return navigator.clipboard.writeText(txt);
        }
        const ta = document.createElement('textarea');
        ta.value = txt;
        ta.style.position = 'fixed'; ta.style.opacity = '0';
        document.body.appendChild(ta);
        ta.select();
        document.execCommand('copy');
        ta.remove();
        return Promise.resolve();
    };
    writeToClipboard(text)
        .then(() => toastr.success('Labor summary copied to clipboard.'))
        .catch(err => toastr.error('Copy failed: ' + (err && err.message ? err.message : err)));
});

// ---------- DYNAMIC ACTIVITY SEARCH ----------
// Filters activity cards by text content (title, type pill, lot/worker chips,
// material/service chips — anything the user can see on the card). Hides
// date groups and rest-day markers when no card inside them matches. Runs
// purely client-side, no server round-trip.
function applyActivityFilter() {
    const raw = ($('#activitySearchInput').val() || '').trim().toLowerCase();
    const $list = $('#activitiesList');
    const $cards = $list.find('.activity-card[data-id]');

    // Collect the currently-active activity-type chips. Empty array = no
    // type filter (show all types).
    const activeTypes = $('#activityTypeFilterRow .activity-type-chip.active')
        .map(function () { return String($(this).data('type') || ''); })
        .get()
        .filter(Boolean);
    const hasTypeFilter = activeTypes.length > 0;

    // Toggle the "Clear types" link visibility based on whether anything
    // is selected.
    $('#activityTypeFilterClearBtn').toggle(hasTypeFilter);

    // No filters at all → show everything and exit early.
    if (!raw && !hasTypeFilter) {
        $cards.show().removeClass('search-hidden');
        $list.find('.date-group, .rest-day-marker').show();
        $('#activitySearchHint').hide();
        $('#activitySearchCount').text('0');
        return;
    }

    // Treat the whole query as a single LIKE %x% substring pattern. Internal
    // whitespace is collapsed on both sides so multi-space input and line-
    // broken HTML don't cause false negatives — typing "apartado pagbababad"
    // matches the phrase as a substring, NOT as two separate AND constraints.
    const needle = raw.replace(/\s+/g, ' ');
    let visible = 0;
    $cards.each(function () {
        const $card = $(this);
        const text = $card.text().toLowerCase().replace(/\s+/g, ' ');
        const cardType = String($card.attr('data-activity-type') || '');
        const matchesSearch = !needle || text.includes(needle);
        const matchesType   = !hasTypeFilter || activeTypes.indexOf(cardType) !== -1;
        if (matchesSearch && matchesType) {
            $card.show().removeClass('search-hidden');
            visible++;
        } else {
            $card.hide().addClass('search-hidden');
        }
    });

    // Hide any date-group whose every card is hidden. Use the deterministic
    // .search-hidden class instead of jQuery's :visible — :visible requires
    // the WHOLE ancestor chain to be visible, so if a date-group is hidden
    // from a previous keystroke, its just-shown children would still report
    // as not-visible and the group would stay collapsed.
    $list.find('.date-group').each(function () {
        const hasVisible = $(this).find('.activity-card[data-id]:not(.search-hidden)').length > 0;
        $(this).toggle(hasVisible);
    });
    $list.find('.rest-day-marker').hide();

    $('#activitySearchHint').show();
    $('#activitySearchCount').text(visible);
}

// Type-chip toggle handler — click to add/remove from the active set, then
// re-apply the filter so the timeline updates instantly.
$(document).on('click', '#activityTypeFilterRow .activity-type-chip', function () {
    const $chip = $(this);
    $chip.toggleClass('active');
    $chip.attr('aria-pressed', $chip.hasClass('active') ? 'true' : 'false');
    applyActivityFilter();
});

// "Clear types" button — deactivate every chip and reapply.
$(document).on('click', '#activityTypeFilterClearBtn', function () {
    $('#activityTypeFilterRow .activity-type-chip')
        .removeClass('active')
        .attr('aria-pressed', 'false');
    applyActivityFilter();
});

// Debounce the search slightly so we don't recompute on every keystroke.
let activitySearchTimer = null;
$(document).on('input', '#activitySearchInput', function () {
    clearTimeout(activitySearchTimer);
    activitySearchTimer = setTimeout(applyActivityFilter, 80);
});
$(document).on('click', '#activitySearchClear', function () {
    $('#activitySearchInput').val('');
    applyActivityFilter();
});
// Re-apply the current search after any DOM mutation that rebuilds cards
// (save/edit/duplicate/drag/etc. all call reorderAndRenumberActivities()).
// Wrap the function so the search filter sticks across rebuilds.
const _origReorderAndRenumber = reorderAndRenumberActivities;
reorderAndRenumberActivities = function () {
    _origReorderAndRenumber.apply(this, arguments);
    // Re-apply filter after a DOM rebuild if EITHER the search box has
    // text OR at least one type chip is active — otherwise the rebuilt
    // cards would all show regardless of the user's current filter state.
    const hasSearch = ($('#activitySearchInput').val() || '').trim() !== '';
    const hasTypePick = $('#activityTypeFilterRow .activity-type-chip.active').length > 0;
    if (hasSearch || hasTypePick) {
        applyActivityFilter();
    }
};

// ---------- ACTIVITY VERSIONS (sub-tabs / forks) ----------
//
// A "version" is an isolated copy of the schedule's activities — a branch
// the user can fork and tweak without disturbing the source. The full set
// of activity rows + items + lot pivots + worker pivots get duplicated on
// the server side; switching versions just flips an isActive flag and
// reloads (server-side render rebuilds the timeline against the new set).
//
// The activeVersion is the one feeding $schedule->activities, so calendar
// generation, worker presentation, export, and labor summary all follow
// the active tab automatically.

// Switch to a different version. Cheap server flip + page reload; no
// client-side diff is needed because the timeline is server-rendered and
// every consumer pulls from the same active-version query.
$(document).on('click', '.version-tab-btn', function () {
    const $btn = $(this);
    if (parseInt($btn.data('is-active'), 10) === 1) {
        return; // already active — nothing to do
    }
    const id = $btn.data('version-id');
    const name = $btn.data('version-name');
    $btn.prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Switching...');

    $.ajax({
        url: URLS.activityVersionsSetActive(id),
        type: 'POST',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) {
                toastr.error(res.message || 'Failed to switch version');
                return;
            }
            toastr.success('Switched to "' + name + '". Reloading...');
            // Preserve the activities tab so the user lands back in the same panel.
            if (!location.hash) {
                location.hash = '#tab-activities';
            }
            setTimeout(() => location.reload(), 350);
        },
        error: (xhr) => {
            toastr.error(xhr.responseJSON?.message || 'Failed to switch version');
            $btn.prop('disabled', false);
        }
    });
});

// "Fork from current" opens the new-version modal with the active version
// pre-selected as the source. The hidden sourceId tells the server to
// deep-clone every activity from that version into the new branch.
$(document).on('click', '#newVersionBtn', function () {
    const $active = $('.version-tab-btn[data-is-active="1"]').first();
    const sourceId = $active.length ? $active.data('version-id') : '';
    const sourceName = $active.length ? $active.data('version-name') : 'current';
    $('#newVersionModalTitle').text('Fork new version');
    $('#newVersionSourceId').val(sourceId);
    $('#newVersionSourceHint').html(
        'All activities from <strong>' + escapeHtml(sourceName) + '</strong> will be deep-cloned into the new branch. ' +
        'Edits in the new version will not affect the source.'
    );
    $('#newVersionName').val('');
    $('#newVersionDescription').val('');
    $('#newVersionSetActive').prop('checked', true);
    $('#saveNewVersionBtnLabel').text('Fork Version');
    $('#newVersionModal').modal('show');
    setTimeout(() => $('#newVersionName').trigger('focus'), 200);
});

// Same modal, but no source — creates a blank version with zero activities.
$(document).on('click', '#newEmptyVersionBtn', function () {
    $('#newVersionModalTitle').text('Create empty version');
    $('#newVersionSourceId').val('');
    $('#newVersionSourceHint').html(
        'The new version will start with <strong>no activities</strong>. You can build it up from scratch or duplicate cards into it later.'
    );
    $('#newVersionName').val('');
    $('#newVersionDescription').val('');
    $('#newVersionSetActive').prop('checked', true);
    $('#saveNewVersionBtnLabel').text('Create Version');
    $('#newVersionModal').modal('show');
    setTimeout(() => $('#newVersionName').trigger('focus'), 200);
});

$(document).on('click', '#saveNewVersionBtn', function () {
    const versionName = ($('#newVersionName').val() || '').trim();
    if (!versionName) {
        toastr.warning('Give the version a name.');
        $('#newVersionName').trigger('focus');
        return;
    }
    const payload = {
        _token:          CSRF,
        versionName:     versionName,
        description:     $('#newVersionDescription').val() || '',
        sourceVersionId: $('#newVersionSourceId').val() || '',
        setActive:       $('#newVersionSetActive').is(':checked') ? 1 : 0,
    };
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

    $.ajax({
        url: URLS.activityVersionsStore(),
        type: 'POST',
        data: payload,
        success: (res) => {
            if (!res.success) {
                toastr.error(res.message || 'Failed to create version');
                return;
            }
            toastr.success('Version "' + versionName + '" created. Reloading...');
            $('#newVersionModal').modal('hide');
            if (!location.hash) location.hash = '#tab-activities';
            setTimeout(() => location.reload(), 400);
        },
        error: (xhr) => {
            const msg = xhr.responseJSON?.message || 'Failed to create version';
            const errs = xhr.responseJSON?.errors;
            if (errs) {
                const first = Object.values(errs)[0];
                toastr.error(Array.isArray(first) ? first[0] : msg);
            } else {
                toastr.error(msg);
            }
        },
        complete: () => {
            $btn.prop('disabled', false).html('<i class="bx bx-git-branch me-1"></i> <span id="saveNewVersionBtnLabel">' + $('#saveNewVersionBtnLabel').text() + '</span>');
        }
    });
});

$(document).on('click', '#renameVersionBtn', function () {
    const $active = $('.version-tab-btn[data-is-active="1"]').first();
    if (!$active.length) {
        toastr.warning('No active version to rename.');
        return;
    }
    $('#renameVersionId').val($active.data('version-id'));
    $('#renameVersionName').val($active.data('version-name') || '');
    $('#renameVersionDescription').val($active.data('version-description') || '');
    $('#renameVersionModal').modal('show');
});

$(document).on('click', '#saveRenameVersionBtn', function () {
    const id = $('#renameVersionId').val();
    const versionName = ($('#renameVersionName').val() || '').trim();
    if (!id || !versionName) {
        toastr.warning('Give the version a name.');
        return;
    }
    const payload = {
        _token:      CSRF,
        versionName: versionName,
        description: $('#renameVersionDescription').val() || '',
    };
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');

    $.ajax({
        url: URLS.activityVersionsUpdate(id),
        type: 'PUT',
        data: payload,
        success: (res) => {
            if (!res.success) {
                toastr.error(res.message || 'Failed to rename version');
                return;
            }
            toastr.success('Version renamed. Reloading...');
            $('#renameVersionModal').modal('hide');
            if (!location.hash) location.hash = '#tab-activities';
            setTimeout(() => location.reload(), 300);
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to rename version'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Changes')
    });
});

// ---------- GLOBAL ACTIVITY NOTE (one note per version, above the timeline) ----------
//
// Single free-form note attached to the active version. Renders above the
// whole activity list, in the worker presentation, and in the export
// schedule. Forks inherit the source's note as a copied value.

// The Protocol Introduction (formerly the global activity note) is
// edited from the Protocol tab — this helper keeps the trigger button +
// the inline preview in sync after every save / clear without a full
// page reload. The trigger lives on .global-note-trigger-btn so we can
// support multiple buttons sharing the same modal.
function _refreshGlobalNoteTrigger(content) {
    const $btn = $('.global-note-trigger-btn').first();
    if (!$btn.length) return;

    // Rich-text editors leave "<p><br></p>" / "<p>&nbsp;</p>" placeholders
    // behind when the user clears all content, so look past tags + &nbsp;
    // to decide whether the note is really empty.
    const raw      = String(content || '');
    const stripped = raw.replace(/<[^>]+>/g, '').replace(/&nbsp;/gi, ' ').trim();
    const isEmpty  = stripped === '';

    $btn.attr('data-existing', isEmpty ? '' : raw);
    $btn.toggleClass('btn-primary', !isEmpty);
    $btn.toggleClass('btn-outline-secondary', isEmpty);
    $btn.html(
        (isEmpty
            ? '<i class="bx bx-message-square-add me-1"></i> Add Introduction'
            : '<i class="bxs-edit-alt bx me-1"></i> Edit Introduction')
    );

    // Inline preview block on the Protocol tab. When the note has
    // content, replace the empty-state placeholder with the rendered
    // HTML; when it's empty, restore the placeholder.
    const $section = $btn.closest('.card-body');
    let $preview = $section.find('#protocolIntroPreview');
    let $empty   = $section.find('.protocol-empty-state');
    if (isEmpty) {
        if ($preview.length) $preview.remove();
        if (!$empty.length) {
            $section.append(
                `<div class="protocol-empty-state text-center py-4">
                    <i class="bx bxs-message-detail" style="font-size: 2rem; color: #b8c0d3;"></i>
                    <p class="text-dark mt-2 mb-1">No introduction written yet.</p>
                    <small class="text-secondary">Click <strong>Add Introduction</strong> to write the protocol context.</small>
                </div>`
            );
        }
    } else {
        if ($empty.length) $empty.remove();
        if (!$preview.length) {
            $section.append('<div class="protocol-intro-preview" id="protocolIntroPreview"></div>');
            $preview = $section.find('#protocolIntroPreview');
        }
        $preview.html(raw);
    }
}

// Quill wiring for the global note modal. Mirrors the activity-
// description editor setup so the toolbar/behavior is consistent across
// the rich-text fields in the schedule manager.
const GLOBAL_NOTE_EDITOR = 'globalActivityNoteContent';
const GLOBAL_NOTE_WRAP   = 'globalActivityNoteWrap';
let globalNoteQuill = null;

function initGlobalNoteEditor(initialHtml) {
    if (typeof Quill === 'undefined') return;
    if (globalNoteQuill) {
        // Re-seed instead of rebuilding when the editor is still alive
        // from a prior open (the hidden.bs.modal handler usually clears it).
        globalNoteQuill.clipboard.dangerouslyPasteHTML(initialHtml || '');
        return;
    }
    globalNoteQuill = new Quill('#' + GLOBAL_NOTE_EDITOR, {
        theme: 'snow',
        placeholder: 'Write a note for this version…',
        modules: { toolbar: SM_QUILL_TOOLBAR },
    });
    if (initialHtml) {
        globalNoteQuill.clipboard.dangerouslyPasteHTML(initialHtml);
    }
    globalNoteQuill.focus();
}

function destroyGlobalNoteEditor() {
    if (!globalNoteQuill) return;
    const $wrap = $('#' + GLOBAL_NOTE_WRAP);
    $wrap.find('.ql-toolbar').remove();
    const $host = $('#' + GLOBAL_NOTE_EDITOR);
    $host.empty().removeClass('ql-container ql-snow').removeAttr('style');
    globalNoteQuill = null;
}

function getGlobalNoteContent() {
    if (globalNoteQuill) {
        const html = globalNoteQuill.root.innerHTML;
        return html === '<p><br></p>' ? '' : html;
    }
    return '';
}

// Class-based trigger so multiple buttons can open the same modal
// (currently the only trigger lives on the Protocol tab, but using
// .global-note-trigger-btn keeps the contract loose enough to add more
// triggers without rewiring). The button carries the active version's
// id + current content via data- attributes.
$(document).on('click', '.global-note-trigger-btn', function () {
    const $btn = $(this);
    const versionId   = $btn.data('version-id');
    const existing    = $btn.attr('data-existing') || '';
    const versionName = $('.version-tab-btn[data-version-id="' + versionId + '"]').data('version-name') || 'this version';

    $('#globalActivityNoteVersionId').val(versionId);
    $('#globalActivityNoteVersionName').text(versionName);
    // Quill is initialized after shown.bs.modal fires (the editor needs
    // a visible mount point to size correctly). The existing markup is
    // passed in via initGlobalNoteEditor(existing) at that point.
    $('#globalActivityNoteModalTitle').text(existing ? 'Edit Protocol Introduction' : 'Add Protocol Introduction');
    $('#globalActivityNoteClearBtn').toggle(!!existing);
    $('#globalActivityNoteModal').modal('show');
});

// Init Quill only after the modal is fully visible (animation done).
// Initializing earlier inside a hidden/animating modal would build a
// zero-height editor that looks broken until the user focuses it.
$('#globalActivityNoteModal').on('shown.bs.modal', function () {
    const existing = $('.global-note-trigger-btn').first().attr('data-existing') || '';
    initGlobalNoteEditor(existing);
});
$('#globalActivityNoteModal').on('hidden.bs.modal', function () {
    destroyGlobalNoteEditor();
});

$(document).on('click', '#globalActivityNoteSaveBtn', function () {
    const id      = $('#globalActivityNoteVersionId').val();
    const content = getGlobalNoteContent();
    if (!id) return;

    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: URLS.activityVersionsGlobalNote(id),
        type: 'POST',
        data: { _token: CSRF, globalActivityNote: content },
        success: (res) => {
            if (!res.success) { toastr.error(res.message || 'Failed to save note'); return; }
            // Render with whatever Quill produced. _refreshGlobalNoteTrigger
            // recognizes empty content (including Quill's "<p><br></p>" /
            // "<p>&nbsp;</p>" placeholders) and falls back to the "+ Add note" CTA.
            _refreshGlobalNoteTrigger(content);
            const wasCleared = String(content || '').replace(/<[^>]+>/g, '').replace(/&nbsp;/gi, ' ').trim() === '';
            toastr.success(wasCleared ? 'Global note cleared.' : 'Global note saved.');
            $('#globalActivityNoteModal').modal('hide');
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to save note'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Note')
    });
});

$(document).on('click', '#globalActivityNoteClearBtn', function () {
    const id = $('#globalActivityNoteVersionId').val();
    if (!id) return;

    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Clearing...');
    $.ajax({
        url: URLS.activityVersionsGlobalNote(id),
        type: 'POST',
        data: { _token: CSRF, globalActivityNote: '' },
        success: (res) => {
            if (!res.success) { toastr.error(res.message || 'Failed to clear note'); return; }
            _refreshGlobalNoteTrigger('');
            toastr.success('Global note cleared.');
            $('#globalActivityNoteModal').modal('hide');
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to clear note'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Clear Note')
    });
});

// ---------- DATE NOTES (per-date commentary on the timeline) ----------
//
// Each date-group can carry an optional note that explains what happens on
// that day in more detail than the activity titles convey. The note is
// scoped to the active version (forks get their own copies) and renders in
// the Worker Presentation + Export Schedule too.

function _prettyDateLabel(iso) {
    const d = parseLocalDate(iso);
    if (!d) return iso;
    return `${DAY_SHORT[d.getDay()]}, ${MONTH_SHORT[d.getMonth()]} ${d.getDate()}, ${d.getFullYear()}`;
}

// Refresh the note row + the date-header icon state without a full reload.
// Pass null/empty for `content` to render the empty state.
function _refreshDateNoteUI(dateKey, content) {
    const $btn  = $('.date-note-btn[data-date="' + dateKey + '"]');
    const $row  = $('.date-note-block[data-date="' + dateKey + '"]');
    const safe  = String(content || '').trim();

    if (safe === '') {
        $btn.removeClass('has-note')
            .attr('data-existing', '')
            .attr('title', 'Add a note for this date')
            .find('i').removeClass('bxs-note').addClass('bx-note');
        $row.hide().find('.date-note-text').empty();
        return;
    }

    // nl2br + HTML-escape so user-supplied content can't inject markup.
    const html = escapeHtml(safe).replace(/\n/g, '<br>');
    $btn.addClass('has-note')
        .attr('data-existing', safe)
        .attr('title', 'Edit the note for this date')
        .find('i').removeClass('bx-note').addClass('bxs-note');
    $row.show().find('.date-note-text').html(html);
}

// Clicking the note icon on a date header opens the modal pre-populated
// with whatever note is currently stored (if any).
$(document).on('click', '.date-note-btn', function (e) {
    e.preventDefault();
    e.stopPropagation();
    const dateKey  = ($(this).attr('data-date') || '').trim();
    const existing = $(this).attr('data-existing') || '';
    if (!dateKey) return;

    $('#dateNoteDate').val(dateKey);
    $('#dateNoteModalDate').text(_prettyDateLabel(dateKey));
    $('#dateNoteContent').val(existing);
    $('#dateNoteModalTitle').text(existing ? 'Edit note for this date' : 'Add note for this date');
    $('#dateNoteClearBtn').toggle(!!existing);
    $('#dateNoteModal').modal('show');
    setTimeout(() => $('#dateNoteContent').trigger('focus'), 200);
});

$(document).on('click', '#dateNoteSaveBtn', function () {
    const dateKey = $('#dateNoteDate').val();
    const content = $('#dateNoteContent').val();
    if (!dateKey) return;

    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: URLS.activitiesDateNoteSave(),
        type: 'POST',
        data: {
            _token:      CSRF,
            noteDate:    dateKey,
            noteContent: content,
        },
        success: (res) => {
            if (!res.success) { toastr.error(res.message || 'Failed to save note'); return; }
            const saved = (content || '').trim();
            _refreshDateNoteUI(dateKey, saved);
            toastr.success(saved === '' ? 'Note cleared.' : 'Note saved.');
            $('#dateNoteModal').modal('hide');
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to save note'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save Note')
    });
});

$(document).on('click', '#dateNoteClearBtn', function () {
    const dateKey = $('#dateNoteDate').val();
    if (!dateKey) return;

    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Clearing...');
    $.ajax({
        url: URLS.activitiesDateNoteDelete(),
        type: 'DELETE',
        data: { _token: CSRF, noteDate: dateKey },
        success: (res) => {
            if (!res.success) { toastr.error(res.message || 'Failed to clear note'); return; }
            _refreshDateNoteUI(dateKey, '');
            toastr.success('Note cleared.');
            $('#dateNoteModal').modal('hide');
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to clear note'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-trash me-1"></i> Clear Note')
    });
});

$(document).on('click', '#deleteVersionBtn', function () {
    const $active = $('.version-tab-btn[data-is-active="1"]').first();
    if (!$active.length) {
        toastr.warning('No active version to delete.');
        return;
    }
    const isOriginal = parseInt($active.data('is-original'), 10) === 1;
    if (isOriginal) {
        toastr.error('The Original version is the baseline and cannot be deleted.');
        return;
    }
    const id = $active.data('version-id');
    const name = $active.data('version-name');

    confirmAction({
        title: 'Delete version',
        message: 'Delete the entire <strong>"' + escapeHtml(name) + '"</strong> version?',
        detail: 'Every activity inside this version will be soft-deleted with it. The Original version will become active again. This cannot be undone from the activity-level Undo stack.',
        confirmText: 'Delete Version',
        onConfirm: () => {
            $.ajax({
                url: URLS.activityVersionsDelete(id),
                type: 'DELETE',
                data: { _token: CSRF },
                success: (res) => {
                    if (!res.success) {
                        toastr.error(res.message || 'Failed to delete version');
                        return;
                    }
                    toastr.success('Version "' + name + '" deleted. Reloading...');
                    if (!location.hash) location.hash = '#tab-activities';
                    setTimeout(() => location.reload(), 350);
                },
                error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Failed to delete version')
            });
        }
    });
});
