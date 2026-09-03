// ---------- INVENTORY ----------
(function () {

const IV = `${ROOT}/anisenso-schedule-manager-inventory`;
const SQ = `?scheduleId=${SCHEDULE_ID}`;
let ITEMS = [];
let MOVES = [];
let started = false;

const esc = (v) => escapeHtml(v);
const qty = (n) => {
    const v = Number(n ?? 0);
    return (Math.round(v * 1000) / 1000).toString();
};

/* The unit vocabulary, from the one list both apps read. Spoken here rather
   than rebuilt, so a row written by a farmer reads the same on this screen. */
const IV_UNITS = @json(\App\Support\InventoryUnits::UNITS);
const IV_KINDS = @json(\App\Support\InventoryUnits::KINDS);

const unitSays = (key, singular) => {
    const u = IV_UNITS[key];
    if (!u) return String(key || '');   // from before this list existed
    const word = singular ? u.one : u.many;
    return u.of ? `${word} (${u.of})` : word;
};
const says = (n, unit) => `${qty(n)} ${unitSays(unit, Math.abs(Number(n) || 0) === 1)}`;

/* Which units a kind is actually bought in. A unit already chosen stays
   offered even if the kind moved on, so opening the list to look is never a
   way to lose an answer. */
function fillUnits(sel, kind, want) {
    const $sel = $(sel);
    if (!$sel.length) return;
    const keys = (IV_KINDS[kind] && IV_KINDS[kind].units) ? IV_KINDS[kind].units.slice() : Object.keys(IV_UNITS);
    if (want && keys.indexOf(want) === -1 && IV_UNITS[want]) keys.unshift(want);
    $sel.html(keys.map(k => `<option value="${k}">${esc(unitSays(k, false))}</option>`).join(''));
    $sel.val(want && keys.indexOf(want) !== -1 ? want : keys[0]);
}

function drawItems() {
    $('#ivBody').html(ITEMS.length ? `<div class="iv-grid">${ITEMS.map(i => `
        <div class="iv-item ${i.isLow ? 'is-low' : ''}">
            <div class="iv-name">${esc(i.icon)} ${esc(i.name)}</div>
            <div class="iv-kind">${esc(i.kindLabel)}${i.unitPrice != null ? ` · ₱${qty(i.unitPrice)} per ${esc(unitSays(i.unit, true))}` : ''}</div>
            <div class="iv-have">${qty(i.onHand)} <small>${esc(i.unitLabel)}</small></div>
            ${i.isLow ? `<div class="iv-low"><i class="bx bx-error"></i> at or below ${esc(i.lowSays || '')}</div>` : ''}
            <div class="iv-acts">
                <button class="btn btn-sm btn-outline-primary js-iv-move" data-id="${i.id}"><i class="bx bx-transfer"></i> Move</button>
                <button class="btn btn-sm btn-light js-iv-edit" data-id="${i.id}"><i class="bx bx-pencil"></i></button>
            </div>
        </div>`).join('')}</div>`
        : `<div class="iv-empty"><i class="bx bx-package"></i>Nothing on the shelf yet.</div>`);
}

function drawMoves() {
    $('#ivMoves').html(MOVES.length ? MOVES.map(m => `
        <div class="iv-move">
            <div class="min-w-0">
                ${m.reason === 'created'
                ? '<span class="iv-delta text-secondary">·</span>'
                : `<span class="iv-delta ${m.delta >= 0 ? 'is-in' : 'is-out'}">${m.delta >= 0 ? '+' : ''}${qty(m.delta)} ${esc(m.unit)}</span>`}
                <span class="text-dark ms-1">${esc(m.itemName)}</span>
                <span class="text-secondary ms-1">· ${esc(m.reasonLabel)}${m.happenedOn ? ' · ' + esc(m.happenedOn) : ''}</span>
                ${m.note ? `<div class="text-secondary" style="font-size:11.5px">${esc(m.note)}</div>` : ''}
            </div>
            <div class="text-nowrap">
                ${m.fromActivity
                    ? '<span class="text-secondary" style="font-size:11px" title="Untick the activity to take this back">from an activity</span>'
                    : `<button class="btn btn-sm btn-outline-danger js-iv-move-del" data-id="${m.id}"><i class="bx bx-trash"></i></button>`}
            </div>
        </div>`).join('')
        : '<p class="text-secondary mb-0 py-2" style="font-size:12.5px">Nothing has moved yet.</p>');
}

function load() {
    $('#ivBody').html('<div class="iv-empty"><i class="bx bx-loader-alt bx-spin"></i>Reading the shelf…</div>');
    smGet(`${IV}-data${SQ}`, function (res) {
        ITEMS = (res && res.items) || [];
        MOVES = (res && res.moves) || [];
        drawItems();
        drawMoves();
    }).fail((xhr) => {
        // The tab is not marked as read when the read failed, so coming back
        // to it asks again instead of showing this for ever.
        started = false;
        $('#ivBody').html('<div class="iv-empty"><i class="bx bx-error"></i>HERE</div>'.replace('HERE', escapeHtml(smWhyFailed(xhr))));
    });
}

$('#ivReload').on('click', load);

// ---- items ------------------------------------------------------------
function openItem(i) {
    $('#ivItemId').val(i ? i.id : '');
    $('#ivItemModalTitle').text(i ? 'Item' : 'New item');
    $('#ivName').val(i ? i.name : '');
    $('#ivKind').val(i ? i.kind : 'granular');
    $('#ivUnit').data('touched', i ? 1 : 0);
    fillUnits('#ivUnit', i ? i.kind : 'granular', i ? i.unit : null);
    sayUnit();
    $('#ivLowAt').val(i && i.lowAt !== null ? i.lowAt : '');
    $('#ivUnitPrice').val(i && i.unitPrice !== null && i.unitPrice !== undefined ? i.unitPrice : '');
    $('#ivNote').val(i ? i.note : '');
    $('#ivOpening').val('');
    // An opening count only makes sense the first time: after that the shelf
    // moves by movements.
    $('#ivOpeningRow').toggle(!i);
    $('#ivItemDeleteBtn').toggle(!!i);
    new bootstrap.Modal(document.getElementById('ivItemModal')).show();
}

/* The unit is echoed beside every box a number goes into, so nobody has to
   scroll back up to remember whether the 12 they are typing is bags or kilos. */
function sayUnit() {
    const u = $('#ivUnit').val();
    const many = unitSays(u, false);
    $('#ivLowUnit').text(many);
    $('#ivPriceUnit').text('per ' + unitSays(u, true));
    $('#ivOpeningUnit').text(many);
    const first = (IV_KINDS[$('#ivKind').val()] || {}).units;
    $('#ivUnitHint').text(first && first.length ? `Usually ${unitSays(first[0], false)}.` : '');
}
$('#ivKind').on('change', function () {
    /* Keep an answer somebody actually gave, and only that.
     *
     * Carrying the current value across every kind change looks like the same
     * kindness and is not: on a fresh item nobody chose "bags (50 kg)", it was
     * the granular default, so switching to Fuel offered bags of diesel. The
     * value is kept only once it has been touched — which is also true of an
     * item being edited, where openItem marks it. */
    var $u = $('#ivUnit');
    fillUnits($u, $(this).val(), $u.data('touched') ? $u.val() : null);
    sayUnit();
});
$('#ivUnit').on('change', function () { $(this).data('touched', 1); sayUnit(); });

$('#ivNewItem').on('click', () => openItem(null));
$(document).on('click', '.js-iv-edit', function () {
    openItem(ITEMS.find(i => i.id === Number($(this).data('id'))));
});

$('#ivItemSaveBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const done = () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save item');
    if (!($('#ivName').val() || '').trim()) { toastr.warning('An item needs a name.'); done(); return; }

    $.ajax({
        url: `${IV}-item-save${SQ}`,
        type: 'POST',
        data: {
            _token: CSRF,
            id: $('#ivItemId').val() || 0,
            name: $('#ivName').val(),
            kind: $('#ivKind').val(),
            unit: $('#ivUnit').val(),
            lowAt: $('#ivLowAt').val() || null,
            unitPrice: $('#ivUnitPrice').val() || null,
            note: $('#ivNote').val() || null,
            opening: $('#ivOpening').val() || 0,
        },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('ivItemModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: done
    });
});

$('#ivItemDeleteBtn').on('click', function () {
    if (!confirm('Take this item off the client\'s inventory?')) return;
    $.ajax({
        url: `${IV}-item-delete${SQ}&id=${$('#ivItemId').val()}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('ivItemModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

// ---- movements --------------------------------------------------------
$(document).on('click', '.js-iv-move', function () {
    const i = ITEMS.find(x => x.id === Number($(this).data('id')));
    if (!i) return;
    $('#ivMoveItemId').val(i.id);
    $('#ivMoveWhat').text(`${i.name} — ${i.says} on hand.`);
    $('#ivQtyUnit').text(i.unitLabel);
    $('#ivDirection').val('in');
    $('#ivQty').val('');
    $('#ivAfter').text('');
    $('#ivReason').val('');
    $('#ivOn').val('');
    $('#ivMoveNote').val('');
    new bootstrap.Modal(document.getElementById('ivMoveModal')).show();
});

/* What the shelf will read once this is recorded. */
function sayAfter() {
    const i = ITEMS.find(x => x.id === Number($('#ivMoveItemId').val()));
    const n = Number($('#ivQty').val() || 0);
    if (!i || !(n > 0)) { $('#ivAfter').text(''); return; }
    const out = $('#ivDirection').val() === 'out';
    $('#ivAfter').text(`After this: ${says(i.onHand + (out ? -n : n), i.unit)}.`);
}
$('#ivQty, #ivDirection').on('input change', sayAfter);

$('#ivMoveSaveBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const done = () => $btn.prop('disabled', false).html('<i class="bx bx-transfer me-1"></i> Record it');
    if (!Number($('#ivQty').val())) { toastr.warning('How much?'); done(); return; }

    $.ajax({
        url: `${IV}-move${SQ}`,
        type: 'POST',
        data: {
            _token: CSRF,
            itemId: $('#ivMoveItemId').val(),
            qty: $('#ivQty').val(),
            direction: $('#ivDirection').val(),
            reason: $('#ivReason').val() || null,
            on: $('#ivOn').val() || null,
            note: $('#ivMoveNote').val() || null,
        },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('ivMoveModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: done
    });
});

$(document).on('click', '.js-iv-move-del', function () {
    if (!confirm('Take this movement back?')) return;
    $.ajax({
        url: `${IV}-move-delete${SQ}&id=${$(this).data('id')}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

$('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    if ($(e.target).attr('href') !== '#tab-inventory' || started) return;
    started = true;
    load();
});
if (location.hash === '#tab-inventory') { started = true; $(load); }

})();
