// ---------- LOTS ----------

// The crop catalogue, from the same const the growth stages read — so a
// crop added there appears here without a second list being edited.
const CROP_CATALOGUE = @json(collect(\App\Support\CropStages::CROPS)->map(fn ($c) => ['label' => $c['label'], 'icon' => $c['icon']]));
function trimZero(n) {
    const v = String(n ?? '0');
    return v.indexOf('.') >= 0 ? v.replace(/0+$/, '').replace(/\.$/, '') : v;
}

function renderLotRow(lot) {
    const dayType = (typeof getScheduleDayType === 'function') ? getScheduleDayType() : 'DAS';
    const d0 = (lot.dayZeroDate || '').slice(0, 10);
    let d0Badge = '';
    if (d0) {
        const dObj = parseLocalDate(d0);
        const pretty = dObj
            ? `${MONTH_SHORT[dObj.getMonth()]} ${dObj.getDate()}, ${dObj.getFullYear()}`
            : d0;
        d0Badge = `<span class="badge bg-info text-dark ms-1 day-zero-badge" style="font-size:10px;font-weight:500;" title="${escapeHtml(dayType)} Day 0 anchor">
            <i class="bx bx-target-lock"></i>
            <span class="day-type-label">${escapeHtml(dayType)}</span> 0: ${escapeHtml(pretty)}
        </span>`;
    }
    const tp = (lot.transplantDate || '').slice(0, 10);
    let tpBadge = '';
    if (tp) {
        const tObj = parseLocalDate(tp);
        const tPretty = tObj
            ? `${MONTH_SHORT[tObj.getMonth()]} ${tObj.getDate()}, ${tObj.getFullYear()}`
            : tp;
        tpBadge = `<span class="badge ms-1 transplant-badge" style="background:#0ca678;color:#fff;font-size:10px;font-weight:500;" title="DAT 0 (transplant) anchor">
            <i class="bx bx-transfer-alt"></i> DAT 0: ${escapeHtml(tPretty)}
        </span>`;
    }
    const variety = (lot.variety || '').trim();
    // Crop, variety and place — the same three the server-rendered row shows,
    // because a row that changes shape after a save reads as a bug.
    const cropInfo = CROP_CATALOGUE[(lot.crop || '').trim()] || null;
    const address = [
        lot.locBarangay ? 'Brgy. ' + String(lot.locBarangay).trim() : '',
        lot.locZone ? 'Zone ' + String(lot.locZone).trim() : '',
        (lot.locTown || '').trim(),
        (lot.locProvince || '').trim(),
    ].filter(Boolean).join(', ');
    let varietyCell = '';
    if (cropInfo) {
        varietyCell += `<span class="badge bg-primary-subtle text-primary" data-field="crop" style="font-weight:500;font-size:11px;">
               ${cropInfo.icon} ${escapeHtml(cropInfo.label)}
           </span> `;
    }
    if (variety) {
        varietyCell += `<span class="badge bg-success-subtle text-success" data-field="variety" style="font-weight:500;font-size:11px;">
               <i class="bx bx-leaf me-1"></i>${escapeHtml(variety)}
           </span>`;
    }
    if (!cropInfo && !variety) varietyCell = `<small class="text-secondary" data-field="variety">—</small>`;
    if (address) {
        varietyCell += `<small class="text-secondary d-block mt-1" data-field="location" style="font-size:11px;">
               <i class="bx bx-map-pin"></i> ${escapeHtml(address)}
           </small>`;
    }
    return `<tr data-id="${lot.id}">
        <td class="text-dark">
            <strong data-field="lotName">${escapeHtml(lot.lotName)}</strong>
            ${d0Badge}
            ${tpBadge}
        </td>
        <td class="text-dark"><span data-field="lotSize">${escapeHtml(trimZero(lot.lotSize))}</span> <small class="text-secondary" data-field="lotSizeUnit">${escapeHtml(lot.lotSizeUnit)}</small></td>
        <td>${varietyCell}</td>
        <td><small class="text-secondary" data-field="notes">${escapeHtml(lot.notes || '')}</small></td>
        <td class="text-end">
            <button class="btn btn-sm btn-outline-primary edit-lot-btn"
                    data-id="${lot.id}"
                    data-name="${escapeHtml(lot.lotName)}"
                    data-size="${lot.lotSize}"
                    data-unit="${escapeHtml(lot.lotSizeUnit)}"
                    data-variety="${escapeHtml(variety)}"
                    data-crop="${escapeHtml(lot.crop || '')}"
                    data-day-type="${escapeHtml(lot.dayType || 'DAT')}"
                    data-loc-barangay="${escapeHtml(lot.locBarangay || '')}"
                    data-loc-zone="${escapeHtml(lot.locZone || '')}"
                    data-loc-town="${escapeHtml(lot.locTown || '')}"
                    data-loc-province="${escapeHtml(lot.locProvince || '')}"
                    data-day-zero-date="${escapeHtml(d0)}"
                    data-transplant-date="${escapeHtml(tp)}"
                    data-days-to-maturity="${escapeHtml(lot.daysToMaturity ?? '')}"
                    data-tree-planted-at="${escapeHtml((lot.treePlantedAt || '').toString().slice(0, 10))}"
                    data-pin-lat="${escapeHtml(lot.pinLat ?? '')}"
                    data-pin-lng="${escapeHtml(lot.pinLng ?? '')}"
                    data-pin-label="${escapeHtml(lot.pinLabel || '')}"
                    data-notes="${escapeHtml(lot.notes || '')}"><i class="bx bx-edit-alt"></i></button>
            <button class="btn btn-sm btn-outline-danger delete-lot-btn" data-id="${lot.id}" data-name="${escapeHtml(lot.lotName)}"><i class="bx bx-trash"></i></button>
        </td>
    </tr>`;
}

$('#addLotBtn').on('click', function () {
    $('#lotModalTitle').text('Add Lot');
    $('#lotId').val('');
    $('#lotName').val('');
    $('#lotSize').val('');
    $('#lotSizeUnit').val('hectare');
    $('#lotVariety').val('');
    $('#lotCrop').val('');
    $('#lotDayType').val('DAT');
    $('#lotLocBarangay').val('');
    $('#lotLocZone').val('');
    $('#lotLocTown').val('');
    $('#lotLocProvince').val('');
    $('#lotDayZeroDate').val('');
    $('#lotTransplantDate').val('');
    $('#lotDaysToMaturity').val('');
    $('#lotTreePlantedAt').val('');
    $('#lotPinLat').val('');
    $('#lotPinLng').val('');
    $('#lotPinLabel').val('');
    $('#lotNotes').val('');
});

$(document).on('click', '.edit-lot-btn', function () {
    $('#lotModalTitle').text('Edit Lot');
    $('#lotId').val($(this).data('id'));
    $('#lotName').val($(this).data('name'));
    $('#lotSize').val($(this).data('size'));
    $('#lotSizeUnit').val($(this).data('unit'));
    $('#lotVariety').val($(this).data('variety') || '');
    $('#lotCrop').val($(this).data('crop') || '');
    $('#lotDayType').val($(this).data('day-type') || 'DAT');
    $('#lotLocBarangay').val($(this).data('loc-barangay') || '');
    $('#lotLocZone').val($(this).data('loc-zone') || '');
    $('#lotLocTown').val($(this).data('loc-town') || '');
    $('#lotLocProvince').val($(this).data('loc-province') || '');
    $('#lotDayZeroDate').val(($(this).data('day-zero-date') || '').toString().slice(0, 10));
    $('#lotTransplantDate').val(($(this).data('transplant-date') || '').toString().slice(0, 10));
    $('#lotDaysToMaturity').val($(this).data('days-to-maturity') || '');
    $('#lotTreePlantedAt').val(($(this).data('tree-planted-at') || '').toString().slice(0, 10));
    $('#lotPinLat').val($(this).data('pin-lat') ?? '');
    $('#lotPinLng').val($(this).data('pin-lng') ?? '');
    $('#lotPinLabel').val($(this).data('pin-label') || '');
    $('#lotNotes').val($(this).data('notes'));
    $('#lotModal').modal('show');
});

$(document).on('click', '#lotDayZeroDateClear', function () {
    $('#lotDayZeroDate').val('');
});

$(document).on('click', '#lotTransplantDateClear', function () {
    $('#lotTransplantDate').val('');
});

$('#saveLotBtn').on('click', function () {
    const id = $('#lotId').val();
    const dayZero = ($('#lotDayZeroDate').val() || '').trim();
    const transplant = ($('#lotTransplantDate').val() || '').trim();
    const payload = {
        _token: CSRF,
        lotName: $('#lotName').val(),
        lotSize: $('#lotSize').val(),
        lotSizeUnit: $('#lotSizeUnit').val(),
        variety: ($('#lotVariety').val() || '').trim(),
        crop: $('#lotCrop').val() || '',
        dayType: $('#lotDayType').val() || 'DAT',
        locBarangay: ($('#lotLocBarangay').val() || '').trim(),
        locZone: ($('#lotLocZone').val() || '').trim(),
        locTown: ($('#lotLocTown').val() || '').trim(),
        locProvince: ($('#lotLocProvince').val() || '').trim(),
        dayZeroDate: dayZero || null,
        transplantDate: transplant || null,
        daysToMaturity: ($('#lotDaysToMaturity').val() || '').trim() || null,
        treePlantedAt: ($('#lotTreePlantedAt').val() || '').trim() || null,
        pinLat: ($('#lotPinLat').val() || '').trim() || null,
        pinLng: ($('#lotPinLng').val() || '').trim() || null,
        pinLabel: ($('#lotPinLabel').val() || '').trim() || null,
        notes: $('#lotNotes').val()
    };
    if (!payload.lotName) { toastr.warning('Lot name is required'); return; }
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.ajax({
        url: id ? URLS.lotsUpdate(id) : URLS.lotsStore(),
        type: id ? 'PUT' : 'POST',
        data: payload,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            $('#lotModal').modal('hide');
            if (id) {
                $('#lotsTable tr[data-id="' + id + '"]').replaceWith(renderLotRow(res.data));
            } else {
                $('#lotsEmpty').remove();
                $('#lotsTable tbody').append(renderLotRow(res.data));
                bumpBadge('badge-lots', 1);
            }
            // Update the manual baselines (DAS + DAT), then re-derive the
            // effective maps so any activity-flagged anchor still overrides.
            if (typeof LOT_MANUAL_DAY_ZERO === 'object' && LOT_MANUAL_DAY_ZERO !== null) {
                LOT_MANUAL_DAY_ZERO[res.data.id] = (res.data.dayZeroDate || '').slice(0, 10) || null;
            }
            if (typeof LOT_MANUAL_TRANSPLANT === 'object' && LOT_MANUAL_TRANSPLANT !== null) {
                LOT_MANUAL_TRANSPLANT[res.data.id] = (res.data.transplantDate || '').slice(0, 10) || null;
            }
            if (typeof recomputeLotDayZero === 'function') {
                recomputeLotDayZero();
            } else if (typeof refreshActivityCardDasLabels === 'function') {
                refreshActivityCardDasLabels();
            }
            // Keep the activity-modal lot selector + name lookup in sync.
            if (typeof ACTIVITY_LOT_NAMES === 'object' && ACTIVITY_LOT_NAMES !== null) {
                ACTIVITY_LOT_NAMES[res.data.id] = res.data.lotName;
            }
            if (typeof ACTIVITY_LOT_VARIETIES === 'object' && ACTIVITY_LOT_VARIETIES !== null) {
                ACTIVITY_LOT_VARIETIES[res.data.id] = res.data.variety || null;
            }
            const $existingLotChip = $('#activityLotsContainer .lot-chip[data-lot-id="' + res.data.id + '"]');
            if ($existingLotChip.length) {
                $existingLotChip.text(res.data.lotName);
            } else {
                $('#activityLotsEmpty').hide();
                $('#activityLotsContainer').show().append(
                    `<span class="lot-chip" data-lot-id="${res.data.id}" role="button" aria-pressed="false">${escapeHtml(res.data.lotName)}</span>`
                );
            }
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i>Save Lot')
    });
});

$(document).on('click', '.delete-lot-btn', function () {
    const id = $(this).data('id');
    const name = $(this).data('name');
    confirmAction({
        title: 'Delete lot',
        message: 'Delete lot <strong>"' + escapeHtml(name) + '"</strong>?',
        detail: 'This will mark the lot as deleted. Existing data tied to it is preserved.',
        confirmText: 'Delete Lot',
        onConfirm: () => {
            $.ajax({
                url: URLS.lotsDelete(id),
                type: 'DELETE',
                data: { _token: CSRF },
                success: (res) => {
                    if (!res.success) { toastr.error(res.message); return; }
                    toastr.success(res.message);
                    $('#lotsTable tr[data-id="' + id + '"]').fadeOut(300, function () {
                        $(this).remove();
                        bumpBadge('badge-lots', -1);
                        // Drop the lot from the manual baseline, then rebuild
                        // the effective map (activity flags may still target it
                        // via stale references — recompute clears those too).
                        if (typeof LOT_MANUAL_DAY_ZERO === 'object' && LOT_MANUAL_DAY_ZERO !== null) {
                            delete LOT_MANUAL_DAY_ZERO[id];
                        }
                        if (typeof LOT_MANUAL_TRANSPLANT === 'object' && LOT_MANUAL_TRANSPLANT !== null) {
                            delete LOT_MANUAL_TRANSPLANT[id];
                        }
                        if (typeof recomputeLotDayZero === 'function') {
                            recomputeLotDayZero();
                        } else if (typeof refreshActivityCardDasLabels === 'function') {
                            refreshActivityCardDasLabels();
                        }
                        // Drop from the activity-modal lot selector + name lookup.
                        if (typeof ACTIVITY_LOT_NAMES === 'object' && ACTIVITY_LOT_NAMES !== null) {
                            delete ACTIVITY_LOT_NAMES[id];
                        }
                        if (typeof ACTIVITY_LOT_VARIETIES === 'object' && ACTIVITY_LOT_VARIETIES !== null) {
                            delete ACTIVITY_LOT_VARIETIES[id];
                        }
                        $('#activityLotsContainer .lot-chip[data-lot-id="' + id + '"]').remove();
                        if ($('#activityLotsContainer .lot-chip').length === 0) {
                            $('#activityLotsContainer').hide();
                            $('#activityLotsEmpty').show();
                        }
                    });
                },
                error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
            });
        }
    });
});
