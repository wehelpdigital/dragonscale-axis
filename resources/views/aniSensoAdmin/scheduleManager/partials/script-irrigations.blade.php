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

function renderIrrigationRow(i) {
    const workerName = i.assigned_worker?.workerName || (i.assignedWorker ? i.assignedWorker.workerName : null);
    const dayType = ($('.day-type-label').first().text() || 'DAS').trim();
    const meta = irrTaskMeta(i.taskType);
    const taskBadge = `<span class="badge text-white" style="background: ${meta.color}; font-weight:600;">${meta.icon} ${escapeHtml(meta.label)}</span>`;
    return `<tr data-id="${i.id}">
        <td class="text-dark"><strong>${escapeHtml(i.irrigationTitle)}</strong></td>
        <td>${taskBadge}</td>
        <td class="text-dark"><span class="badge bg-info text-white"><span class="day-type-label">${escapeHtml(dayType)}</span> ${i.startDay} — ${i.endDay}</span></td>
        <td class="text-dark">${workerName ? escapeHtml(workerName) : '—'}</td>
        <td><small class="text-secondary">${escapeHtml((i.description || '').slice(0, 60))}</small></td>
        <td class="text-end">
            <button class="btn btn-sm btn-outline-primary edit-irrigation-btn"
                    data-id="${i.id}"
                    data-title="${escapeHtml(i.irrigationTitle)}"
                    data-description="${escapeHtml(i.description || '')}"
                    data-start-day="${i.startDay}"
                    data-end-day="${i.endDay}"
                    data-task-type="${escapeHtml(meta.slug)}"
                    data-worker-id="${i.assignedWorkerId || ''}"><i class="bx bx-edit-alt"></i></button>
            <button class="btn btn-sm btn-outline-danger delete-irrigation-btn" data-id="${i.id}" data-name="${escapeHtml(i.irrigationTitle)}"><i class="bx bx-trash"></i></button>
        </td>
    </tr>`;
}

$('#addIrrigationBtn').on('click', function () {
    $('#irrigationModalTitle').text('Add Irrigation');
    $('#irrigationId').val('');
    $('#irrigationTitle').val('');
    $('#irrigationDescription').val('');
    $('#irrigationStartDay').val(0);
    $('#irrigationEndDay').val(5);
    $('#irrigationTaskType').val('irrigate');
    $('#irrigationWorker').val('');
});

$(document).on('click', '.edit-irrigation-btn', function () {
    $('#irrigationModalTitle').text('Edit Irrigation');
    $('#irrigationId').val($(this).data('id'));
    $('#irrigationTitle').val($(this).data('title'));
    $('#irrigationDescription').val($(this).data('description'));
    $('#irrigationStartDay').val($(this).data('start-day'));
    $('#irrigationEndDay').val($(this).data('end-day'));
    $('#irrigationTaskType').val($(this).data('task-type') || 'irrigate');
    $('#irrigationWorker').val($(this).data('worker-id') || '');
    $('#irrigationModal').modal('show');
});

$('#saveIrrigationBtn').on('click', function () {
    const id = $('#irrigationId').val();
    const payload = {
        _token: CSRF,
        irrigationTitle: $('#irrigationTitle').val(),
        description: $('#irrigationDescription').val(),
        startDay: $('#irrigationStartDay').val(),
        endDay: $('#irrigationEndDay').val(),
        taskType: $('#irrigationTaskType').val() || 'irrigate',
        assignedWorkerId: $('#irrigationWorker').val() || null,
    };
    if (!payload.irrigationTitle) { toastr.warning('Title is required'); return; }
    if (parseInt(payload.endDay) < parseInt(payload.startDay)) { toastr.warning('End day must be >= Start day'); return; }
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
                $('#irrigationsTable tr[data-id="' + id + '"]').replaceWith(html);
            } else {
                $('#irrigationsEmpty').remove();
                $('#irrigationsTable tbody').append(html);
                bumpBadge('badge-irrigations', 1);
            }
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Irrigation')
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
                    $('#irrigationsTable tr[data-id="' + id + '"]').fadeOut(300, function () {
                        $(this).remove();
                        bumpBadge('badge-irrigations', -1);
                    });
                }
            });
        }
    });
});
