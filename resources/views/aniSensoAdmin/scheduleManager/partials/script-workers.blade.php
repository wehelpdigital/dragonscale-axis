// ---------- WORKERS ----------
const DAY_NAMES = ['Sun','Mon','Tue','Wed','Thu','Fri','Sat'];

// Per-worker cache of off-day rules so the table cell can refresh after Save Rules.
const workerOffCache = {};
@foreach($schedule->workers as $w)
workerOffCache[{{ $w->id }}] = {
    offDays: @json($w->offDays->pluck('dayOfWeek')->map(fn($d) => (int) $d)),
    offDates: @json($w->offDates->pluck('offDate')->map(fn($d) => \Illuminate\Support\Carbon::parse($d)->toDateString())),
};
@endforeach

function renderOffSummary(workerId) {
    const rules = workerOffCache[workerId] || { offDays: [], offDates: [] };
    if (!rules.offDays.length && !rules.offDates.length) {
        return '<small class="text-secondary">—</small>';
    }
    let html = '<small class="text-dark">';
    if (rules.offDays.length) {
        const days = rules.offDays.map(d => DAY_NAMES[d]).join(', ');
        html += `<span class="badge bg-light text-dark">Off: ${escapeHtml(days)}</span> `;
    }
    if (rules.offDates.length) {
        html += `<span class="badge bg-light text-dark">${rules.offDates.length} off date(s)</span>`;
    }
    html += '</small>';
    return html;
}

// Re-sort the activity modal's worker chip selector by priority asc, so a
// newly-added (or re-prioritized) worker lands in its correct slot without a
// page reload. Ties break on worker id for stability.
function sortActivityWorkerChips() {
    const $container = $('#activityWorkersContainer');
    const chips = $container.find('.lot-chip[data-worker-id]').get();
    if (chips.length < 2) return;
    chips.sort((a, b) => {
        const pa = parseInt($(a).attr('data-priority'), 10);
        const pb = parseInt($(b).attr('data-priority'), 10);
        const ra = isNaN(pa) ? 9999 : pa;
        const rb = isNaN(pb) ? 9999 : pb;
        if (ra !== rb) return ra - rb;
        return (parseInt($(a).attr('data-worker-id'), 10) || 0)
             - (parseInt($(b).attr('data-worker-id'), 10) || 0);
    });
    chips.forEach(el => $container.append(el));
}

function renderWorkerRow(w) {
    return `<tr data-id="${w.id}">
        <td><span class="badge bg-primary text-white">#${w.priority}</span></td>
        <td class="text-dark"><strong>${escapeHtml(w.workerName)}</strong></td>
        <td class="text-dark">${fmtPeso(w.costPerHalfDay)}</td>
        <td class="off-rules-cell">${renderOffSummary(w.id)}</td>
        <td><small class="text-secondary">${escapeHtml(w.notes || '')}</small></td>
        <td class="text-end">
            <button class="btn btn-sm btn-outline-info worker-rules-btn" data-id="${w.id}" data-name="${escapeHtml(w.workerName)}" title="Edit availability rules">
                <i class="bx bx-calendar-x"></i> Rules
            </button>
            <button class="btn btn-sm btn-outline-primary edit-worker-btn"
                    data-id="${w.id}"
                    data-name="${escapeHtml(w.workerName)}"
                    data-cost="${w.costPerHalfDay}"
                    data-priority="${w.priority}"
                    data-notes="${escapeHtml(w.notes || '')}"><i class="bx bx-edit-alt"></i></button>
            <button class="btn btn-sm btn-outline-danger delete-worker-btn" data-id="${w.id}" data-name="${escapeHtml(w.workerName)}"><i class="bx bx-trash"></i></button>
        </td>
    </tr>`;
}

$('#addWorkerBtn').on('click', function () {
    $('#workerModalTitle').text('Add Worker');
    $('#workerId').val('');
    $('#workerName').val('');
    $('#workerCost').val('0');
    $('#workerPriority').val(1);
    $('#workerNotes').val('');
});

$(document).on('click', '.edit-worker-btn', function () {
    $('#workerModalTitle').text('Edit Worker');
    $('#workerId').val($(this).data('id'));
    $('#workerName').val($(this).data('name'));
    $('#workerCost').val($(this).data('cost'));
    $('#workerPriority').val($(this).data('priority'));
    $('#workerNotes').val($(this).data('notes'));
    $('#workerModal').modal('show');
});

$('#saveWorkerBtn').on('click', function () {
    const id = $('#workerId').val();
    const payload = {
        _token: CSRF,
        workerName: $('#workerName').val(),
        costPerHalfDay: $('#workerCost').val() === '' ? 0 : $('#workerCost').val(),
        priority: $('#workerPriority').val(),
        notes: $('#workerNotes').val()
    };
    if (!payload.workerName) { toastr.warning('Worker name is required'); return; }
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: id ? URLS.workersUpdate(id) : URLS.workersStore(),
        type: id ? 'PUT' : 'POST',
        data: payload,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $('#workerModal').modal('hide');
            const w = res.data;
            if (!workerOffCache[w.id]) workerOffCache[w.id] = { offDays: [], offDates: [] };
            const html = renderWorkerRow(w);
            if (id) {
                $('#workersTable tr[data-id="' + id + '"]').replaceWith(html);
            } else {
                $('#workersEmpty').remove();
                $('#workersTable tbody').append(html);
                bumpBadge('badge-workers', 1);
            }
            // Keep the activity-modal worker selector in sync. Without this,
            // newly-added workers wouldn't appear as a selectable chip until
            // a full page reload.
            if (typeof ACTIVITY_WORKER_NAMES === 'object' && ACTIVITY_WORKER_NAMES !== null) {
                ACTIVITY_WORKER_NAMES[w.id] = w.workerName;
            }
            const chipBody = `${escapeHtml(w.workerName)} <small class="text-muted ms-1">#${w.priority}</small>`;
            const $existingChip = $('#activityWorkersContainer .lot-chip[data-worker-id="' + w.id + '"]');
            if ($existingChip.length) {
                // Edit: rewrite the chip body + priority attr but keep selection state.
                $existingChip.html(chipBody).attr('data-priority', w.priority);
            } else {
                $('#activityWorkersEmpty').hide();
                $('#activityWorkersContainer').show().append(
                    `<span class="lot-chip" data-worker-id="${w.id}" data-priority="${w.priority}" role="button" aria-pressed="false">${chipBody}</span>`
                );
            }
            sortActivityWorkerChips();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Worker')
    });
});

$(document).on('click', '.delete-worker-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    confirmAction({
        title: 'Delete worker',
        message: 'Delete worker <strong>"' + escapeHtml(name) + '"</strong>?',
        detail: 'Existing activity assignments and availability rules for this worker will be hidden.',
        confirmText: 'Delete Worker',
        onConfirm: () => {
    $.ajax({
        url: URLS.workersDelete(id),
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $('#workersTable tr[data-id="' + id + '"]').fadeOut(300, function () {
                $(this).remove();
                delete workerOffCache[id];
                bumpBadge('badge-workers', -1);
            });
            // Drop the worker from the activity-modal selector + name lookup.
            if (typeof ACTIVITY_WORKER_NAMES === 'object' && ACTIVITY_WORKER_NAMES !== null) {
                delete ACTIVITY_WORKER_NAMES[id];
            }
            $('#activityWorkersContainer .lot-chip[data-worker-id="' + id + '"]').remove();
            if ($('#activityWorkersContainer .lot-chip').length === 0) {
                $('#activityWorkersContainer').hide();
                $('#activityWorkersEmpty').show();
            }
        }
    });
        }
    });
});

// Rules modal
$(document).on('click', '.worker-rules-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    $('#rulesWorkerId').val(id);
    $('#rulesWorkerName').text(name);
    $('#ruleDayPills .day-pill').removeClass('active');
    $('#offDatesList').empty();

    $.get(URLS.workersRules(id), function (res) {
        if (!res.success) { toastr.error(res.message); return; }
        (res.data.offDays || []).forEach(d => {
            $('#ruleDayPills .day-pill[data-day="' + d + '"]').addClass('active');
        });
        (res.data.offDates || []).forEach(od => {
            appendOffDatePill(od.offDate);
        });
        $('#workerRulesModal').modal('show');
    });
});

$('#ruleDayPills').on('click', '.day-pill', function () { $(this).toggleClass('active'); });

function appendOffDatePill(d) {
    if (!d) return;
    const dateStr = (typeof d === 'string') ? d.slice(0,10) : d;
    $('#offDatesList').append(`
        <span class="badge bg-light text-dark p-2" data-date="${dateStr}">
            ${dateStr}
            <a href="javascript:void(0);" class="text-danger ms-2 remove-off-date">&times;</a>
        </span>
    `);
}

$('#addOffDateBtn').on('click', function () {
    const v = $('#newOffDate').val();
    if (!v) { toastr.warning('Pick a date first'); return; }
    if ($('#offDatesList span[data-date="' + v + '"]').length) { toastr.info('Already added'); return; }
    appendOffDatePill(v);
    $('#newOffDate').val('');
});

$(document).on('click', '.remove-off-date', function () { $(this).closest('span').remove(); });

$('#saveWorkerRulesBtn').on('click', function () {
    const id = parseInt($('#rulesWorkerId').val(), 10);
    const offDays = $('#ruleDayPills .day-pill.active').map((_, e) => parseInt($(e).data('day'), 10)).get();
    const offDates = $('#offDatesList span').map((_, e) => $(e).data('date')).get();
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: URLS.workersRulesSave(id),
        type: 'POST',
        data: { _token: CSRF, offDays, offDates },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $('#workerRulesModal').modal('hide');
            // Refresh just the "Off Rules" cell of that row, no reload.
            workerOffCache[id] = { offDays, offDates };
            $('#workersTable tr[data-id="' + id + '"] .off-rules-cell').html(renderOffSummary(id));
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Rules')
    });
});
