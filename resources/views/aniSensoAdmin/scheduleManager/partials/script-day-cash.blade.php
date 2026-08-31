// ---------- DAY CASH ----------
// The money on the board: what a day cost, what it brought in, grouped by the
// day it happened on because that is how it is written down in the field.
(function () {

const DC = `${ROOT}/anisenso-schedule-manager-day-cash`;
const SQ = `?scheduleId=${SCHEDULE_ID}`;
const esc = (v) => escapeHtml(v);
let ROWS = [];
let started = false;

function draw(totals) {
    const out = totals.expense || 0;
    const inn = totals.income || 0;
    $('#dcSummary').html(`
        <div class="dc-fig is-out"><b>${fmtPeso(out)}</b><span>Spent on the days</span></div>
        <div class="dc-fig is-in"><b>${fmtPeso(inn)}</b><span>Taken on the days</span></div>
        <div class="dc-fig is-net"><b>${fmtPeso(inn - out)}</b><span>The difference</span></div>`);

    if (!ROWS.length) {
        $('#dcBody').html('<div class="dc-empty"><i class="bx bx-wallet"></i>Nothing written against a day yet.</div>');
        return;
    }

    // One block per day, newest first — the order the board reads in.
    const byDay = new Map();
    ROWS.forEach((r) => {
        if (!byDay.has(r.date)) byDay.set(r.date, []);
        byDay.get(r.date).push(r);
    });

    $('#dcBody').html([...byDay.entries()].map(([date, rows]) => {
        const spent = rows.filter(r => r.kind === 'expense').reduce((a, r) => a + r.amount, 0);
        const took = rows.filter(r => r.kind === 'income').reduce((a, r) => a + r.amount, 0);
        return `<div class="dc-day">
            <div class="dc-dayhead">
                <span>${esc(date)}</span>
                <span>
                    ${spent ? `<span class="dc-amt is-out">−${fmtPeso(spent)}</span>` : ''}
                    ${took ? `<span class="dc-amt is-in ms-2">+${fmtPeso(took)}</span>` : ''}
                </span>
            </div>
            ${rows.map(r => `
                <div class="dc-row">
                    <div class="min-w-0">
                        <div class="dc-what">${esc(r.title || r.note || (r.kind === 'income' ? 'Income' : 'Expense'))}</div>
                        ${(r.title && r.note) ? `<div class="dc-note">${esc(r.note)}</div>` : ''}
                    </div>
                    <div class="text-nowrap">
                        <span class="dc-amt ${r.kind === 'income' ? 'is-in' : 'is-out'}">
                            ${r.kind === 'income' ? '+' : '−'}${fmtPeso(r.amount)}
                        </span>
                        <button class="btn btn-sm btn-light js-dc-edit ms-2" data-id="${r.id}" data-kind="${esc(r.kind)}"><i class="bx bx-pencil"></i></button>
                    </div>
                </div>`).join('')}
        </div>`;
    }).join(''));
}

function load() {
    $('#dcBody').html('<div class="dc-empty"><i class="bx bx-loader-alt bx-spin"></i>Reading the day money…</div>');
    smGet(`${DC}-data${SQ}`, function (res) {
        ROWS = (res && res.data) || [];
        draw((res && res.totals) || {});
    }).fail((xhr) => {
        started = false;
        $('#dcBody').html('<div class="dc-empty"><i class="bx bx-error"></i>HERE</div>'.replace('HERE', escapeHtml(smWhyFailed(xhr))));
    });
}

$('#dcReload').on('click', load);

function open(kind, row) {
    const income = kind === 'income';
    $('#dcId').val(row ? row.id : '');
    $('#dcKind').val(kind);
    $('#dcModalTitle').text(row ? (income ? 'Income' : 'Expense') : (income ? 'New income' : 'New expense'));
    $('#dcDate').val(row ? row.date : new Date().toISOString().slice(0, 10));
    $('#dcAmount').val(row ? row.amount : '');
    // Only an income carries a name of its own; an expense IS its note, which
    // is how the two tables are shaped.
    $('#dcTitleRow').toggle(income);
    $('#dcTitle').val(row ? row.title : '');
    $('#dcNoteLabel').text(income ? 'Note' : 'What it was for');
    $('#dcNote').val(row ? row.note : '');
    $('#dcDeleteBtn').toggle(!!row);
    new bootstrap.Modal(document.getElementById('dcModal')).show();
}

$('#dcNewExpense').on('click', () => open('expense', null));
$('#dcNewIncome').on('click', () => open('income', null));
$(document).on('click', '.js-dc-edit', function () {
    const kind = $(this).data('kind');
    open(kind, ROWS.find(r => r.id === Number($(this).data('id')) && r.kind === kind));
});

$('#dcSaveBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const done = () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save');
    if (!$('#dcDate').val() || $('#dcAmount').val() === '') { toastr.warning('A day and an amount.'); done(); return; }

    $.ajax({
        url: `${DC}-save${SQ}`,
        type: 'POST',
        data: {
            _token: CSRF,
            id: $('#dcId').val() || 0,
            kind: $('#dcKind').val(),
            date: $('#dcDate').val(),
            amount: $('#dcAmount').val(),
            title: $('#dcTitle').val(),
            note: $('#dcNote').val(),
        },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('dcModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: done
    });
});

$('#dcDeleteBtn').on('click', function () {
    if (!confirm('Take this off the client\'s board?')) return;
    $.ajax({
        url: `${DC}-delete${SQ}&id=${$('#dcId').val()}&kind=${encodeURIComponent($('#dcKind').val())}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('dcModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

// It lives inside Activities, so it loads when that drawer is opened.
$('.sm-subtabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    if ($(e.target).attr('href') !== '#sub-day-cash' || started) return;
    started = true;
    load();
});
if (location.hash === '#sub-day-cash') { started = true; $(load); }

})();
