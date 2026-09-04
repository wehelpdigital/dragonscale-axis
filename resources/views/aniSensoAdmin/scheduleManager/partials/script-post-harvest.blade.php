// ---------- POST-HARVEST ----------
(function () {

const PH = `${ROOT}/anisenso-schedule-manager-harvest`;
const SQ = `?scheduleId=${SCHEDULE_ID}`;
let started = false;

const esc = (v) => escapeHtml(v);
const num = (v) => (v === null || v === undefined || v === '') ? null : Number(v);

document.addEventListener('error', function (e) {
    const el = e.target;
    if (!el || el.tagName !== 'IMG' || !el.classList.contains('js-ph-img')) return;
    el.remove();
}, true);

function card(r) {
    const figs = [];
    if (r.yieldAmount !== null) figs.push(`<span class="ph-fig"><i class="bx bx-bar-chart-alt-2"></i> ${fmtNumber(r.yieldAmount)} ${esc(r.yieldUnit || '')}</span>`);
    if (r.moisturePercent !== null) figs.push(`<span class="ph-fig"><i class="bx bx-droplet"></i> ${fmtNumber(r.moisturePercent, 1)}%</span>`);
    if (r.pricePerUnit !== null) figs.push(`<span class="ph-fig"><i class="bx bx-purchase-tag"></i> ${fmtPeso(r.pricePerUnit)}</span>`);
    if (r.value) figs.push(`<span class="ph-fig"><i class="bx bx-wallet"></i> ${fmtPeso(r.value)}</span>`);
    if (r.buyer) figs.push(`<span class="ph-fig"><i class="bx bx-user"></i> ${esc(r.buyer)}</span>`);

    return `<div class="ph-card js-ph-open" role="button" data-id="${r.id}">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="min-w-0">
                <div class="ph-title">${esc(r.title)}</div>
                <div class="ph-meta">${esc(r.category || '')}${r.lotName ? ' · ' + esc(r.lotName) : ''}${r.when ? ' · ' + esc(r.when) : ''}</div>
            </div>
        </div>
        ${figs.length ? `<div class="ph-figs">${figs.join('')}</div>` : ''}
        ${(r.photos && r.photos.length) ? `<div class="ph-shots">${r.photos.map(u => `<img class="js-ph-img" src="${esc(u)}" alt="">`).join('')}</div>` : ''}
    </div>`;
}

function load() {
    $('#phBody').html('<div class="ph-empty"><i class="bx bx-loader-alt bx-spin"></i>Reading the records…</div>');
    smGet(`${PH}${SQ}`, function (res) {
        const d = (res && res.data) || {};
        const rows = d.rows || [];
        $('#phTotal').html(d.totalValue ? `Everything sold so far: <b>${fmtPeso(d.totalValue)}</b>` : '');
        $('#phBody').html(rows.length
            ? rows.map(card).join('')
            : `<div class="ph-empty"><i class="bx bx-basket"></i>Nothing recorded off this season yet.</div>`);
    }).fail((xhr) => {
        // The tab is not marked as read when the read failed, so coming back
        // to it asks again instead of showing this for ever.
        started = false;
        $('#phBody').html('<div class="ph-empty"><i class="bx bx-error"></i>HERE</div>'.replace('HERE', escapeHtml(smWhyFailed(xhr))));
    });
}

$('#phReload').on('click', load);

function fill(d) {
    $('#phId').val(d.id || '');
    $('#phTitle').val(d.title || '');
    $('#phCategory').val(d.category || 'yield');
    $('#phDate').val(d.observationDate || '');
    $('#phLot').val(String(d.lotId || 0));
    $('#phBuyer').val(d.buyer || '');
    $('#phYield').val(d.yieldAmount ?? '');
    $('#phUnit').val(d.yieldUnit || '');
    $('#phMoisture').val(d.moisturePercent ?? '');
    $('#phPrice').val(d.pricePerUnit ?? '');
    $('#phNotes').val(d.notes || '');
    $('#phExtrasHint').toggleClass('d-none', !d.id);
    $('#phDeleteBtn').toggle(!!d.id);
    $('#phModalTitle').text(d.id ? 'Observation record' : 'New observation record');
}

$(document).on('click', '.js-ph-open', function (e) {
    if (e.target.tagName === 'IMG') { window.open(e.target.src, '_blank'); return; }
    const id = $(this).data('id');
    smGet(`${PH}-one${SQ}&id=${id}`, function (res) {
        if (!res.success) { toastr.error(res.message); return; }
        fill(res.data);
        new bootstrap.Modal(document.getElementById('phModal')).show();
    }).fail(() => toastr.error('Could not open that record.'));
});

$('#phNewBtn').on('click', function () {
    fill({ category: 'yield' });
    new bootstrap.Modal(document.getElementById('phModal')).show();
});

$('#phSaveBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const done = () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save record');
    if (!($('#phTitle').val() || '').trim()) { toastr.warning('A record needs a title.'); done(); return; }

    $.ajax({
        url: `${PH}-save${SQ}`,
        type: 'POST',
        data: {
            _token: CSRF,
            id: $('#phId').val() || 0,
            title: $('#phTitle').val(),
            category: $('#phCategory').val(),
            observationDate: $('#phDate').val() || null,
            lotId: Number($('#phLot').val()) || null,
            yieldAmount: num($('#phYield').val()),
            yieldUnit: $('#phUnit').val() || null,
            moisturePercent: num($('#phMoisture').val()),
            pricePerUnit: num($('#phPrice').val()),
            buyer: $('#phBuyer').val() || null,
            notes: $('#phNotes').val() || null,
        },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('phModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: done
    });
});

$('#phDeleteBtn').on('click', function () {
    if (!confirm('Remove this record from the client\'s app?')) return;
    $.ajax({
        url: `${PH}-delete${SQ}&id=${$('#phId').val()}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('phModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

$('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    if ($(e.target).attr('href') !== '#tab-post-harvest' || started) return;
    started = true;
    load();
});
if (location.hash === '#tab-post-harvest') { started = true; $(load); }

})();
