// ---------- MAPS, DRAW, CHAT TECHNICIAN ----------
// Three shelves with the same shape: read the season's own rows, let a name
// be fixed and a row be removed, and leave what is inside them alone.
(function () {

const CR = `${ROOT}/anisenso-schedule-manager-records`;
const SQ = `?scheduleId=${SCHEDULE_ID}`;
const esc = (v) => escapeHtml(v);

// A file can outlive its picture — the client app's storage is rebuilt on
// deploy — and a broken-image glyph reads as a broken screen.
document.addEventListener('error', function (e) {
    const el = e.target;
    if (!el || el.tagName !== 'IMG' || !el.classList.contains('js-cr-img')) return;
    const gone = document.createElement('div');
    gone.className = 'dw-gone';
    gone.title = 'The file is not on the disk any more';
    gone.innerHTML = '<i class="bx bx-image-alt"></i>';
    el.replaceWith(gone);
}, true);

// Opens whichever tab is asked for once, the first time it is looked at.
function onFirstShow(hash, run) {
    let started = false;
    // `run` is handed a way to say it did not manage it, so a tab whose first
    // read dropped asks again next time it is looked at rather than showing
    // the failure for ever.
    const failed = () => { started = false; };
    $('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
        if ($(e.target).attr('href') !== hash || started) return;
        started = true;
        run(failed);
    });
    if (location.hash === hash) { started = true; $(() => run(failed)); }
}

// ---- maps -------------------------------------------------------------
function loadMaps(failed) {
    $('#mpBody').html('<div class="mp-empty"><i class="bx bx-loader-alt bx-spin"></i>Reading the maps…</div>');
    smGet(`${CR}-maps${SQ}`, function (res) {
        const rows = (res && res.data) || [];
        $('#mpBody').html(rows.length ? rows.map(r => `
            <div class="mp-card">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="mp-title">${esc(r.title)}</div>
                        <div class="mp-meta">${esc(r.source || 'map')} · ${r.shapes} ${r.shapes === 1 ? 'shape' : 'shapes'}${r.when ? ' · ' + esc(r.when) : ''}</div>
                    </div>
                    <div class="text-nowrap">
                        <button class="btn btn-sm btn-outline-primary js-mp-rename" data-id="${r.id}" data-title="${esc(r.title)}"><i class="bx bx-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger js-mp-del" data-id="${r.id}"><i class="bx bx-trash"></i></button>
                    </div>
                </div>
                ${Object.keys(r.kinds || {}).length ? `<div class="mp-kinds">${Object.keys(r.kinds).map(k => `<span class="mp-kind">${esc(k)} × ${r.kinds[k]}</span>`).join('')}</div>` : ''}
                ${(r.labels && r.labels.length) ? `<div class="mp-labels">${r.labels.map(esc).join(' · ')}</div>` : ''}
            </div>`).join('')
            : '<div class="mp-empty"><i class="bx bx-map-alt"></i>No maps saved on this season.</div>');
    }).fail((xhr) => {
        if (failed) failed();
        $('#mpBody').html('<div class="mp-empty"><i class="bx bx-error"></i>HERE</div>'.replace('HERE', escapeHtml(smWhyFailed(xhr))));
    });
}

$('#mpReload').on('click', loadMaps);

$(document).on('click', '.js-mp-rename', function () {
    const title = prompt('What should this map be called?', $(this).data('title'));
    if (title === null) return;
    $.ajax({
        url: `${CR}-map-rename${SQ}&id=${$(this).data('id')}`,
        type: 'PUT',
        data: { _token: CSRF, title },
        success: (res) => { res.success ? (toastr.success(res.message), loadMaps()) : toastr.error(res.message); },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Rename failed')
    });
});

$(document).on('click', '.js-mp-del', function () {
    if (!confirm('Remove this map from the client\'s app?')) return;
    $.ajax({
        url: `${CR}-map-delete${SQ}&id=${$(this).data('id')}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => { res.success ? (toastr.success(res.message), loadMaps()) : toastr.error(res.message); },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

onFirstShow('#tab-maps', loadMaps);

// ---- drawings ---------------------------------------------------------
function loadDrawings(failed) {
    $('#dwBody').html('<div class="dw-empty"><i class="bx bx-loader-alt bx-spin"></i>Reading the drawings…</div>');
    smGet(`${CR}-drawings${SQ}`, function (res) {
        const rows = (res && res.data) || [];
        $('#dwBody').html(rows.length ? `<div class="dw-tiles">${rows.map(r => `
            <div class="dw-tile">
                ${r.url ? `<img class="js-cr-img js-dw-open" src="${esc(r.url)}" alt="">`
                        : '<div class="dw-gone"><i class="bx bx-image-alt"></i></div>'}
                <div class="dw-body">
                    <div class="dw-title">${esc(r.noteTitle || 'Untitled note')}</div>
                    <div class="dw-meta">${r.team ? 'Team board' : 'Drawing pad'}${r.when ? ' · ' + esc(r.when) : ''}</div>
                    <div class="dw-acts">
                        <button class="btn btn-sm btn-light js-dw-note" data-shelf="${esc(r.shelf)}" data-id="${r.noteId}">Its note</button>
                        <button class="btn btn-sm btn-outline-danger js-dw-del"
                                data-shelf="${esc(r.shelf)}" data-note="${r.noteId}" data-index="${r.index}"><i class="bx bx-trash"></i></button>
                    </div>
                </div>
            </div>`).join('')}</div>`
            : '<div class="dw-empty"><i class="bx bx-pencil"></i>Nothing drawn on this season.</div>');
    }).fail((xhr) => {
        if (failed) failed();
        $('#dwBody').html('<div class="dw-empty"><i class="bx bx-error"></i>HERE</div>'.replace('HERE', escapeHtml(smWhyFailed(xhr))));
    });
}

$('#dwReload').on('click', loadDrawings);
$(document).on('click', '.js-dw-open', function () { window.open(this.src, '_blank'); });

// A drawing's name is its note's, so "its note" goes there rather than
// offering a box here that would write nowhere.
$(document).on('click', '.js-dw-note', function () {
    $('.sm-tabs a[href="#tab-notes"]').tab('show');
    setTimeout(() => $('#ntSearch').val('').trigger('input'), 400);
});

$(document).on('click', '.js-dw-del', function () {
    if (!confirm('Take this drawing out of the note that holds it?')) return;
    const b = $(this);
    $.ajax({
        url: `${CR}-drawing-delete${SQ}&shelf=${encodeURIComponent(b.data('shelf'))}&noteId=${b.data('note')}&index=${b.data('index')}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => { res.success ? (toastr.success(res.message), loadDrawings()) : toastr.error(res.message); },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

onFirstShow('#tab-draw', loadDrawings);

// ---- threads ----------------------------------------------------------
function loadThreads(failed) {
    $('#ctBody').html('<div class="ai-empty"><i class="bx bx-loader-alt bx-spin"></i>Reading the threads…</div>');
    smGet(`${CR}-ai${SQ}`, function (res) {
        const rows = (res && res.data) || [];
        $('#ctBody').html(rows.length ? rows.map(r => `
            <div class="ai-row">
                <div class="d-flex justify-content-between align-items-start gap-2">
                    <div class="min-w-0">
                        <div class="ai-row-t">${esc(r.title)}</div>
                        <div class="ai-row-m">${r.messageCount} ${r.messageCount === 1 ? 'message' : 'messages'}${r.who ? ' · ' + esc(r.who) : ''}${r.when ? ' · ' + esc(r.when) : ''}</div>
                    </div>
                    <div class="text-nowrap">
                        <span class="ai-kind ${r.kind === 'team' ? 'is-team' : ''}">${r.kind === 'team' ? 'Collab Room' : 'Their own'}</span>
                        <button class="btn btn-sm btn-outline-primary js-ct-read" data-kind="${esc(r.kind)}" data-id="${r.id}">Read</button>
                        <button class="btn btn-sm btn-light js-ct-rename" data-kind="${esc(r.kind)}" data-id="${r.id}" data-title="${esc(r.title)}"><i class="bx bx-pencil"></i></button>
                        <button class="btn btn-sm btn-outline-danger js-ct-del" data-kind="${esc(r.kind)}" data-id="${r.id}"><i class="bx bx-trash"></i></button>
                    </div>
                </div>
            </div>`).join('')
            : '<div class="ai-empty"><i class="bx bx-bot"></i>Nothing was asked about this season.</div>');
    }).fail((xhr) => {
        if (failed) failed();
        $('#ctBody').html('<div class="ai-empty"><i class="bx bx-error"></i>HERE</div>'.replace('HERE', escapeHtml(smWhyFailed(xhr))));
    });
}

$('#ctReload').on('click', loadThreads);

$(document).on('click', '.js-ct-read', function () {
    const b = $(this);
    $('#ctModalBody').html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-3 text-secondary"></i></div>');
    new bootstrap.Modal(document.getElementById('ctModal')).show();
    smGet(`${CR}-ai-one${SQ}&kind=${encodeURIComponent(b.data('kind'))}&id=${b.data('id')}`, function (res) {
        if (!res.success) { $('#ctModalBody').html(`<p class="text-secondary mb-0">${esc(res.message)}</p>`); return; }
        $('#ctModalTitle').text(res.data.title);
        const turns = res.data.turns || [];
        // bodyHtml, not body: the server has already escaped it and put
        // Anee's faces back where she wrote them. Escaping it again here
        // would show the <img> tags as words.
        $('#ctModalBody').html(turns.length ? turns.map(t => `
            <div class="ai-turn ${t.role === 'user' ? '' : 'is-bot'}">
                <div class="ai-bubble">${t.bodyHtml || esc(t.body)}</div>
            </div>`).join('')
            : '<p class="text-secondary mb-0">Nothing was said in this one.</p>');
    }).fail(() => $('#ctModalBody').html('<p class="text-secondary mb-0">Could not read that thread.</p>'));
});

$(document).on('click', '.js-ct-rename', function () {
    const b = $(this);
    const title = prompt('What should this thread be called?', b.data('title'));
    if (title === null) return;
    $.ajax({
        url: `${CR}-ai-rename${SQ}&kind=${encodeURIComponent(b.data('kind'))}&id=${b.data('id')}`,
        type: 'PUT',
        data: { _token: CSRF, title },
        success: (res) => { res.success ? (toastr.success(res.message), loadThreads()) : toastr.error(res.message); },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Rename failed')
    });
});

$(document).on('click', '.js-ct-del', function () {
    if (!confirm('Remove this thread from the client\'s app?')) return;
    const b = $(this);
    $.ajax({
        url: `${CR}-ai-delete${SQ}&kind=${encodeURIComponent(b.data('kind'))}&id=${b.data('id')}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => { res.success ? (toastr.success(res.message), loadThreads()) : toastr.error(res.message); },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

onFirstShow('#tab-chat-technician', loadThreads);

})();
