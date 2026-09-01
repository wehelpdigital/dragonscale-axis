// ---------- THE EYE, THE SEARCH, THE MIRROR ----------
// Three faces on machinery that already exists. Every row here forwards to
// the control in the Tools menu that does the work and reads its state back,
// so "hide the empty days" has one implementation and this cannot drift from
// it.
(function () {

const esc = (v) => escapeHtml(v);

// ---- what the board shows ---------------------------------------------
// Each row names the control it stands for and how to read its state. The
// Tools menu writes "on"/"off" into a small span beside each item; that span
// is the truth, and these chips are a bigger, plainer copy of it.
const VIEW_ROWS = {
    empty:    { btn: '#toggleEmptyDaysBtn',        state: '#toggleEmptyDaysState',        chip: '#vfEmptyState',    on: 'Hidden', off: 'Shown' },
    doneDays: { btn: '#toggleDoneDaysBtn',         state: '#toggleDoneDaysState',         chip: '#vfDoneDaysState', on: 'Hidden', off: 'Shown' },
    doneActs: { btn: '#toggleDoneActivitiesBtn',   state: '#toggleDoneActivitiesState',   chip: '#vfDoneActsState', on: 'Hidden', off: 'Shown' },
};

function paintViewSheet() {
    Object.values(VIEW_ROWS).forEach((r) => {
        const on = ($(r.state).text() || '').trim().toLowerCase() === 'on';
        $(r.chip).text(on ? r.on : r.off).toggleClass('is-off', on);
    });

    // The hidden row is only worth offering when something is actually
    // hidden — an option that can only ever do nothing is noise.
    const count = parseInt($('#hiddenActivityCount').text(), 10) || 0;
    $('#vfHiddenRow').toggle(count > 0);
    $('#vfHiddenSub').text(count === 1 ? 'One activity kept off the board' : count + ' activities kept off the board');
    const showing = ($('.cv-hidden-toggle-label').first().text() || '').toLowerCase().includes('hide');
    $('#vfHiddenState').text(showing ? 'Shown' : 'Hidden').toggleClass('is-off', !showing);

    // The eye says, without being opened, that the board is not showing
    // everything.
    const narrowed = Object.values(VIEW_ROWS)
        .some((r) => ($(r.state).text() || '').trim().toLowerCase() === 'on');
    $('#viewFilterBtn').toggleClass('btn-outline-secondary', !narrowed)
        .toggleClass('btn-warning', narrowed);
}

$('#viewFilterBtn').on('click', function () {
    paintViewSheet();
    new bootstrap.Modal(document.getElementById('viewFilterModal')).show();
});

$(document).on('click', '.vf-row', function () {
    const key = $(this).data('vf');
    if (key === 'fold') { $('#collapseAllDaysBtn').trigger('click'); return; }
    if (key === 'unfold') { $('#expandAllDaysBtn').trigger('click'); return; }
    if (key === 'hidden') { $('#toggleHiddenActivitiesBtn').trigger('click'); setTimeout(paintViewSheet, 60); return; }
    const row = VIEW_ROWS[key];
    if (!row) return;
    $(row.btn).trigger('click');
    // The toggles repaint their own state span synchronously, but a tick
    // costs nothing and covers the ones that wait for a render.
    setTimeout(paintViewSheet, 60);
});

// ---- search & filter ---------------------------------------------------
$('#searchToolbarBtn').on('click', function () {
    new bootstrap.Modal(document.getElementById('filtersModal')).show();
    // The box is the reason the sheet was opened nine times in ten.
    setTimeout(() => $('#activitySearchInput').trigger('focus'), 350);
});

/* How many filters are on, on the button, so a board narrowed yesterday
   cannot look like an empty season today. Counted from the controls
   themselves rather than from a tally kept beside them. */
function paintFilterCount() {
    let n = 0;
    if (($('#activitySearchInput').val() || '').trim() !== '') n++;
    n += $('.activity-type-chip.active, .activity-type-chip[aria-pressed="true"]').length;
    n += $('.activity-lot-chip.active, .activity-lot-chip[aria-pressed="true"]').length;
    $('#toolbarFilterCount').text(n).toggle(n > 0);
    $('#searchToolbarBtn').toggleClass('btn-outline-secondary', n === 0).toggleClass('btn-warning', n > 0);
}
$(document).on('input', '#activitySearchInput', paintFilterCount);
$(document).on('click', '.activity-type-chip, .activity-lot-chip, #activitySearchClear, #activityLotFilterAllBtn, #activityLotFilterClearBtn', function () {
    setTimeout(paintFilterCount, 60);
});

$('#clearAllFiltersBtn').on('click', function () {
    $('#activitySearchInput').val('').trigger('input');
    $('.activity-type-chip[aria-pressed="true"], .activity-type-chip.active').trigger('click');
    $('#activityLotFilterClearBtn').is(':visible')
        ? $('#activityLotFilterClearBtn').trigger('click')
        : $('.activity-lot-chip[aria-pressed="true"], .activity-lot-chip.active').trigger('click');
    setTimeout(paintFilterCount, 120);
});

// ---- the mirror --------------------------------------------------------
/* Built from the board that is already on the page, so it cannot disagree
   with it and costs nothing to open. Every day and every activity, including
   the ones the filters are currently hiding — that is the point of it. */
function buildMirror() {
    const days = [];
    $('#activitiesList .date-group').each(function () {
        const $g = $(this);
        const head = ($g.find('.date-header').clone().find('button, .badge').remove().end().text() || '').trim().replace(/\s+/g, ' ');
        const acts = [];
        $g.find('.activity-card[data-id]').each(function () {
            const $c = $(this);
            const title = ($c.find('.activity-card-title, .card-title, strong').first().text() || '').trim();
            const done = $c.hasClass('is-done') || $c.find('.activity-done-check:checked, input[type=checkbox]:checked').length > 0;
            const hidden = $c.hasClass('is-hidden') || $c.attr('data-hidden') === '1';
            const lots = $c.find('.activity-card-lots .item-tag').map((_, e) => $(e).text().trim()).get();
            const type = ($c.find('.sm-pill').first().text() || '').trim();
            acts.push({ title: title || '(untitled)', done, hidden, lots, type });
        });
        if (head || acts.length) days.push({ head, acts });
    });

    const total = days.reduce((a, d) => a + d.acts.length, 0);
    const done = days.reduce((a, d) => a + d.acts.filter(x => x.done).length, 0);
    $('#mirrorSub').text(`${days.length} ${days.length === 1 ? 'day' : 'days'} · ${total} ${total === 1 ? 'activity' : 'activities'} · ${done} done`);

    $('#mirrorBody').html(days.length ? days.map(d => `
        <div class="mir-day">
            <div class="mir-dayhead"><span>${esc(d.head)}</span><span>${d.acts.length} ${d.acts.length === 1 ? 'activity' : 'activities'}</span></div>
            ${d.acts.map(a => `
                <div class="mir-act ${a.done ? 'is-done' : ''} ${a.hidden ? 'is-hidden' : ''}">
                    <span class="mir-title">${esc(a.title)}</span>
                    ${a.done ? '<span class="mir-flag">done</span>' : ''}
                    ${a.hidden ? '<span class="mir-flag">hidden</span>' : ''}
                    <div class="mir-meta">${esc([a.type, a.lots.join(', ')].filter(Boolean).join(' · ')) || '&nbsp;'}</div>
                </div>`).join('') || '<div class="mir-act text-secondary">Nothing on this day.</div>'}
        </div>`).join('')
        : '<p class="text-secondary mb-0">There is no plan on this board yet.</p>');
}

$('#mirrorBtn').on('click', function () {
    buildMirror();
    new bootstrap.Modal(document.getElementById('mirrorModal')).show();
});

$('#mirrorPrint').on('click', function () {
    const w = window.open('', '_blank');
    if (!w) { toastr.warning('Allow pop-ups to print the mirror.'); return; }
    w.document.write(`<!doctype html><meta charset="utf-8"><title>${esc(document.title)}</title>
        <style>body{font:13px/1.5 system-ui,sans-serif;margin:2rem;color:#222}
        .mir-day{border:1px solid #ddd;border-radius:8px;margin-bottom:.6rem}
        .mir-dayhead{background:#f6f8fb;padding:.4rem .7rem;font-weight:700;display:flex;justify-content:space-between}
        .mir-act{padding:.35rem .7rem;border-top:1px solid #eee}
        .mir-act.is-done{color:#999;text-decoration:line-through}
        .mir-meta{font-size:11px;color:#999}
        .mir-flag{font-size:10px;background:#eee;border-radius:99px;padding:0 .4rem;margin-left:.3rem}</style>
        <h2>${esc(document.title)}</h2>${$('#mirrorBody').html()}`);
    w.document.close();
    w.focus();
    w.print();
});

// ---- refresh -----------------------------------------------------------
$('#boardRefreshBtn').on('click', function () {
    $(this).prop('disabled', true).find('i').addClass('bx-spin');
    window.location.reload();
});

$(function () { paintFilterCount(); paintViewSheet(); });

})();
