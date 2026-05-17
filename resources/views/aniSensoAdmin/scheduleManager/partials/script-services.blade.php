// ---------- SERVICES ----------
function renderServiceRow(s) {
    return `<tr data-id="${s.id}">
        <td class="text-dark"><strong>${escapeHtml(s.serviceName)}</strong></td>
        <td class="text-dark">${fmtPeso(s.serviceCost)}</td>
        <td><small class="text-secondary">${escapeHtml((s.description || '').slice(0, 80))}</small></td>
        <td class="text-end">
            <button class="btn btn-sm btn-outline-primary edit-service-btn"
                    data-id="${s.id}"
                    data-name="${escapeHtml(s.serviceName)}"
                    data-description="${escapeHtml(s.description || '')}"
                    data-cost="${s.serviceCost}"><i class="bx bx-edit-alt"></i></button>
            <button class="btn btn-sm btn-outline-danger delete-service-btn" data-id="${s.id}" data-name="${escapeHtml(s.serviceName)}"><i class="bx bx-trash"></i></button>
        </td>
    </tr>`;
}

function addServiceOption(s) {
    $('#itemPickerId optgroup[label="Services"]').append(
        `<option value="service::${s.id}">${escapeHtml(s.serviceName)}</option>`
    );
}
function updateServiceOption(s) {
    $('#itemPickerId option[value="service::' + s.id + '"]').text(s.serviceName);
}
function removeServiceOption(id) {
    $('#itemPickerId option[value="service::' + id + '"]').remove();
}

$('#addServiceBtn').on('click', function () {
    $('#serviceModalTitle').text('Add Service');
    $('#serviceId').val('');
    $('#serviceName').val('');
    $('#serviceDescription').val('');
    $('#serviceCost').val('');
});

$(document).on('click', '.edit-service-btn', function () {
    $('#serviceModalTitle').text('Edit Service');
    $('#serviceId').val($(this).data('id'));
    $('#serviceName').val($(this).data('name'));
    $('#serviceDescription').val($(this).data('description'));
    $('#serviceCost').val($(this).data('cost'));
    $('#serviceModal').modal('show');
});

$('#saveServiceBtn').on('click', function () {
    const id = $('#serviceId').val();
    const payload = {
        _token: CSRF,
        serviceName: $('#serviceName').val(),
        description: $('#serviceDescription').val(),
        serviceCost: $('#serviceCost').val()
    };
    if (!payload.serviceName) { toastr.warning('Service name is required'); return; }
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: id ? URLS.servicesUpdate(id) : URLS.servicesStore(),
        type: id ? 'PUT' : 'POST',
        data: payload,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $('#serviceModal').modal('hide');
            const s = res.data;
            const html = renderServiceRow(s);
            if (id) {
                $('#servicesTable tr[data-id="' + id + '"]').replaceWith(html);
                updateServiceOption(s);
            } else {
                $('#servicesEmpty').remove();
                $('#servicesTable tbody').append(html);
                addServiceOption(s);
                bumpBadge('badge-services', 1);
            }
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Service')
    });
});

$(document).on('click', '.delete-service-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    confirmAction({
        title: 'Delete service',
        message: 'Delete service <strong>"' + escapeHtml(name) + '"</strong>?',
        detail: 'Activities that reference this service will keep their link but it will no longer appear in the picker.',
        confirmText: 'Delete Service',
        onConfirm: () => {
            $.ajax({
                url: URLS.servicesDelete(id),
                type: 'DELETE',
                data: { _token: CSRF },
                success: (res) => {
                    if (!res.success) { toastr.error(res.message); return; }
                    toastr.success(res.message);
                    $('#servicesTable tr[data-id="' + id + '"]').fadeOut(300, function () {
                        $(this).remove();
                        removeServiceOption(id);
                        bumpBadge('badge-services', -1);
                    });
                }
            });
        }
    });
});
