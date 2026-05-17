// ---------- MATERIALS ----------
function materialSearchKey(m) {
    return [m.materialName, m.materialType, m.unitOfMeasure, m.description || '']
        .join(' ').toLowerCase();
}

function renderMaterialRow(m) {
    const typeLabel = m.materialType.charAt(0).toUpperCase() + m.materialType.slice(1);
    const qtyTrim = String(m.priceQuantity).replace(/\.?0+$/, '');
    return `<tr data-id="${m.id}" data-search="${escapeHtml(materialSearchKey(m))}">
        <td class="text-dark"><strong>${escapeHtml(m.materialName)}</strong></td>
        <td><span class="badge bg-info text-white">${escapeHtml(typeLabel)}</span></td>
        <td class="text-dark">${escapeHtml(m.unitOfMeasure)}</td>
        <td class="text-dark">${fmtPeso(m.priceAmount)} <small class="text-secondary">per ${escapeHtml(qtyTrim)} ${escapeHtml(m.unitOfMeasure)}</small></td>
        <td><small class="text-secondary">${escapeHtml((m.description || '').slice(0, 60))}</small></td>
        <td class="text-end">
            <button class="btn btn-sm btn-outline-primary edit-material-btn"
                    data-id="${m.id}"
                    data-name="${escapeHtml(m.materialName)}"
                    data-description="${escapeHtml(m.description || '')}"
                    data-type="${escapeHtml(m.materialType)}"
                    data-unit="${escapeHtml(m.unitOfMeasure)}"
                    data-amount="${m.priceAmount}"
                    data-quantity="${m.priceQuantity}"><i class="bx bx-edit-alt"></i></button>
            <button class="btn btn-sm btn-outline-danger delete-material-btn" data-id="${m.id}" data-name="${escapeHtml(m.materialName)}"><i class="bx bx-trash"></i></button>
        </td>
    </tr>`;
}

// --- Live search filter ---
function applyMaterialsSearch() {
    const q = ($('#materialsSearch').val() || '').toLowerCase().trim();
    const $rows = $('#materialsTable tbody tr[data-id]');
    let visible = 0;

    if (!q) {
        $rows.show();
        visible = $rows.length;
    } else {
        $rows.each(function () {
            const key = $(this).attr('data-search') || '';
            if (key.indexOf(q) !== -1) { $(this).show(); visible++; }
            else { $(this).hide(); }
        });
    }

    // Remove any prior "no results" row so we don't double-render it.
    $('#materialsNoMatch').remove();
    if (q && visible === 0 && $rows.length > 0) {
        $('#materialsTable tbody').append(
            `<tr id="materialsNoMatch"><td colspan="6" class="text-center text-secondary py-3">
                <i class="bx bx-search-alt"></i> No materials match "<strong>${escapeHtml(q)}</strong>".
            </td></tr>`
        );
    }

    // Counter helper text under the input
    if (q) {
        $('#materialsSearchCount').text(`${visible} of ${$rows.length} match`);
    } else {
        $('#materialsSearchCount').text('');
    }
}

$('#materialsSearch').on('input', applyMaterialsSearch);
$('#materialsSearchClear').on('click', function () {
    $('#materialsSearch').val('').trigger('input').focus();
});

function addMaterialOption(m) {
    $('#itemPickerId optgroup[label="Materials"]').append(
        `<option value="material::${m.id}" data-unit="${escapeHtml(m.unitOfMeasure)}">${escapeHtml(m.materialName)} (${escapeHtml(m.unitOfMeasure)})</option>`
    );
}
function updateMaterialOption(m) {
    $('#itemPickerId option[value="material::' + m.id + '"]')
        .attr('data-unit', m.unitOfMeasure)
        .text(`${m.materialName} (${m.unitOfMeasure})`);
}
function removeMaterialOption(id) {
    $('#itemPickerId option[value="material::' + id + '"]').remove();
}

$('#addMaterialBtn').on('click', function () {
    $('#materialModalTitle').text('Add Material');
    $('#materialId').val('');
    $('#materialName').val('');
    $('#materialDescription').val('');
    $('#materialType').val('granular');
    $('#materialUnit').val('kg').trigger('change');
    $('#materialAmount').val('');
    $('#materialQuantity').val(1);
});

$(document).on('click', '.edit-material-btn', function () {
    $('#materialModalTitle').text('Edit Material');
    $('#materialId').val($(this).data('id'));
    $('#materialName').val($(this).data('name'));
    $('#materialDescription').val($(this).data('description'));
    $('#materialType').val($(this).data('type'));
    $('#materialUnit').val($(this).data('unit')).trigger('change');
    $('#materialAmount').val($(this).data('amount'));
    $('#materialQuantity').val($(this).data('quantity'));
    $('#materialModal').modal('show');
});

$('#materialUnit').on('change', function () { $('#materialUnitDisplay').text($(this).val()); });

$('#saveMaterialBtn').on('click', function () {
    const id = $('#materialId').val();
    const payload = {
        _token: CSRF,
        materialName: $('#materialName').val(),
        description: $('#materialDescription').val(),
        materialType: $('#materialType').val(),
        unitOfMeasure: $('#materialUnit').val(),
        priceAmount: $('#materialAmount').val(),
        priceQuantity: $('#materialQuantity').val()
    };
    if (!payload.materialName) { toastr.warning('Material name is required'); return; }
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: id ? URLS.materialsUpdate(id) : URLS.materialsStore(),
        type: id ? 'PUT' : 'POST',
        data: payload,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $('#materialModal').modal('hide');
            const m = res.data;
            const html = renderMaterialRow(m);
            if (id) {
                $('#materialsTable tr[data-id="' + id + '"]').replaceWith(html);
                updateMaterialOption(m);
            } else {
                $('#materialsEmpty').remove();
                $('#materialsTable tbody').append(html);
                addMaterialOption(m);
                bumpBadge('badge-materials', 1);
            }
            applyMaterialsSearch();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Material')
    });
});

$(document).on('click', '.delete-material-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    confirmAction({
        title: 'Delete material',
        message: 'Delete material <strong>"' + escapeHtml(name) + '"</strong>?',
        detail: 'Activities that reference this material will keep their existing link but it will no longer appear in the picker.',
        confirmText: 'Delete Material',
        onConfirm: () => {
            $.ajax({
                url: URLS.materialsDelete(id),
                type: 'DELETE',
                data: { _token: CSRF },
                success: (res) => {
                    if (!res.success) { toastr.error(res.message); return; }
                    toastr.success(res.message);
                    $('#materialsTable tr[data-id="' + id + '"]').fadeOut(300, function () {
                        $(this).remove();
                        removeMaterialOption(id);
                        bumpBadge('badge-materials', -1);
                        applyMaterialsSearch();
                    });
                }
            });
        }
    });
});
