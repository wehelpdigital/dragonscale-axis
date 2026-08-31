// ---------- NOTES ----------
// One list over three shelves, and one modal that can write to whichever
// shelf the note it opened came from.
(function () {

const NT = `${ROOT}/anisenso-schedule-manager-notes`;
const SQ = `?scheduleId=${SCHEDULE_ID}`;
let ROWS = [];
let started = false;

const esc = (v) => escapeHtml(v);

// A file can outlive its picture — the client app's storage is rebuilt on
// deploy — and a broken-image glyph reads as a broken screen.
document.addEventListener('error', function (e) {
    const el = e.target;
    if (!el || el.tagName !== 'IMG' || !el.classList.contains('js-nt-img')) return;
    el.remove();
}, true);

function card(r) {
    const shelfClass = r.shelf === 'date' ? 'is-date' : (r.shelf === 'inline' ? 'is-inline' : '');
    const heading = r.hasTitle
        ? (esc(r.title) || '<span class="text-secondary fst-italic">Untitled note</span>')
        : (r.noteDate ? 'Day note — ' + esc(r.noteDate) : 'Day note');
    return `<div class="nt-card js-nt-open" role="button" data-id="${r.id}" data-shelf="${esc(r.shelf)}">
        <div class="d-flex justify-content-between align-items-start gap-2">
            <div class="nt-title">${heading}</div>
            <span class="nt-shelf ${shelfClass}">${esc(r.shelfLabel)}</span>
        </div>
        <div class="nt-meta">
            ${esc(r.when || '—')}
            ${r.attachments ? ' · <i class="bx bx-paperclip"></i> ' + r.attachments : ''}
        </div>
        ${r.words ? `<div class="nt-words">${esc(r.words)}</div>` : ''}
    </div>`;
}

function draw() {
    const q = ($('#ntSearch').val() || '').trim().toLowerCase();
    const rows = q
        ? ROWS.filter(r => ((r.title || '') + ' ' + (r.words || '')).toLowerCase().includes(q))
        : ROWS;
    $('#ntCount').text(rows.length ? `${rows.length} of ${ROWS.length}` : '');
    $('#ntBody').html(rows.length
        ? rows.map(card).join('')
        : `<div class="nt-empty"><i class="bx bx-note"></i>Nothing written down here yet.</div>`);
}

function load() {
    $('#ntBody').html('<div class="nt-empty"><i class="bx bx-loader-alt bx-spin"></i>Reading the client\'s notes…</div>');
    smGet(`${NT}-data${SQ}`, function (res) {
        ROWS = (res && res.data) || [];
        draw();
    }).fail((xhr) => {
        // The tab is not marked as read when the read failed, so coming back
        // to it asks again instead of showing this for ever.
        started = false;
        $('#ntBody').html('<div class="nt-empty"><i class="bx bx-error"></i>HERE</div>'.replace('HERE', escapeHtml(smWhyFailed(xhr))));
    });
}

$('#ntReload').on('click', load);
$('#ntSearch').on('input', draw);

// ---- one note --------------------------------------------------------
function openNote(id, shelf) {
    smGet(`${NT}-one${SQ}&id=${id}&shelf=${encodeURIComponent(shelf)}`, function (res) {
        if (!res.success) { toastr.error(res.message); return; }
        const d = res.data;
        $('#ntId').val(d.id);
        $('#ntShelf').val(d.shelf);
        $('#ntModalTitle').text(d.shelfLabel);
        $('#ntModalSub').text(d.noteDate ? 'Pinned to ' + d.noteDate : '');
        $('#ntTitleRow').toggle(!!d.hasTitle);
        $('#ntTitle').val(d.title || '');
        // The words arrive as the client's app stored them — the rich
        // shelves keep HTML — and this box edits text, so the tags are shown
        // for what they are rather than silently eaten.
        $('#ntBodyField').val(d.body || '');
        $('#ntMedia').html((d.media || []).map(m => m.type === 'video'
            ? `<a class="rec-chip" href="${esc(m.url)}" target="_blank">&#9654; ${esc(m.name || 'Video')}</a>`
            : `<img class="js-nt-img" src="${esc(m.url)}" alt="" title="${esc(m.name || '')}" data-full="${esc(m.url)}">`
        ).join(''));
        $('#ntMediaHint').toggleClass('d-none', !(d.media || []).length);
        $('#ntDeleteBtn').show();
        new bootstrap.Modal(document.getElementById('ntModal')).show();
    }).fail(() => toastr.error('Could not open that note.'));
}

$(document).on('click', '.js-nt-open', function () {
    openNote($(this).data('id'), $(this).data('shelf'));
});

$(document).on('click', '#ntMedia img', function () {
    window.open($(this).data('full'), '_blank');
});

// A new note goes on the client's own shelf — the other two belong to the
// screens that write them.
$('#ntNewBtn').on('click', function () {
    $('#ntId').val('');
    $('#ntShelf').val('note');
    $('#ntModalTitle').text('New note');
    $('#ntModalSub').text('');
    $('#ntTitleRow').show();
    $('#ntTitle').val('');
    $('#ntBodyField').val('');
    $('#ntMedia').empty();
    $('#ntMediaHint').addClass('d-none');
    $('#ntDeleteBtn').hide();
    new bootstrap.Modal(document.getElementById('ntModal')).show();
});

$('#ntSaveBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const done = () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save note');
    const id = $('#ntId').val();
    const shelf = $('#ntShelf').val();
    const data = { _token: CSRF, shelf, title: $('#ntTitle').val(), body: $('#ntBodyField').val() };

    $.ajax({
        url: id ? `${NT}-update${SQ}&id=${id}&shelf=${encodeURIComponent(shelf)}` : `${NT}-store${SQ}`,
        type: id ? 'PUT' : 'POST',
        data,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('ntModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: done
    });
});

$('#ntDeleteBtn').on('click', function () {
    if (!confirm('Remove this note from the client\'s app?')) return;
    const id = $('#ntId').val();
    const shelf = $('#ntShelf').val();
    $.ajax({
        url: `${NT}-delete${SQ}&id=${id}&shelf=${encodeURIComponent(shelf)}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('ntModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

// Nothing is fetched until the tab is opened: this page already asks the
// remote database plenty on load.
$('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    if ($(e.target).attr('href') !== '#tab-notes' || started) return;
    started = true;
    load();
});
if (location.hash === '#tab-notes') { started = true; $(load); }

})();
