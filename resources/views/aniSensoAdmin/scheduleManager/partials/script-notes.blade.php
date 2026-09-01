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
        <div class="nt-meta">${esc(r.when || '—')}</div>
        ${r.words ? `<div class="nt-words">${esc(r.words)}</div>` : ''}
        ${attHtml(r.atts)}
    </div>`;
}

// What is on a note — one chip per attachment, and each one opens.
//
// Ordered rather than left in the order files happened to be added: a map is
// what somebody is usually hunting for, so it leads, then the drawing, then a
// recording, then the photographs.
const ATT_LOOK = {
    map: ['is-map', 'bx-map-alt', 'Map'],
    drawing: ['is-draw', 'bx-pencil', 'Drawing'],
    video: ['is-video', 'bx-video', 'Video'],
    image: ['is-photo', 'bx-image', 'Photo'],
};
const ATT_ORDER = ['map', 'drawing', 'video', 'image'];

function attHtml(atts) {
    if (!Array.isArray(atts) || !atts.length) return '';

    const sorted = atts.slice().sort(
        (a, b) => ATT_ORDER.indexOf(a.type) - ATT_ORDER.indexOf(b.type));

    // Numbered only when a note has more than one of a kind, so two drawings
    // read apart without "Map 1" appearing on every map.
    const seen = {};
    const total = {};
    sorted.forEach((a) => { total[a.type] = (total[a.type] || 0) + 1; });

    const chips = sorted.map((a) => {
        const [cls, icon, label] = ATT_LOOK[a.type] || ATT_LOOK.image;
        seen[a.type] = (seen[a.type] || 0) + 1;
        const nth = total[a.type] > 1 ? ` <b>${seen[a.type]}</b>` : '';
        return `<button type="button" class="nt-att ${cls} js-nt-att"`
            + ` data-type="${esc(a.type)}" data-index="${a.index}"`
            + ` data-shelf="${esc(a.shelf)}" data-note="${a.noteId}"`
            + ` data-save="${a.saveId || 0}" data-url="${esc(a.url || '')}"`
            + ` data-name="${esc(a.name || '')}"`
            + ` title="${a.type === 'map' ? 'Open this map' : (a.type === 'drawing' ? 'Open this drawing in the pad' : (a.type === 'video' ? 'Play this recording' : 'See this photo'))}">`
            + `<i class="bx ${icon}"></i>${label}${nth}</button>`;
    });

    return `<div class="nt-atts">${chips.join('')}</div>`;
}

// Pressing one. The card behind it opens the note, so every one of these
// stops the press going further — otherwise opening a drawing would open the
// note underneath at the same time.
$(document).on('click', '.js-nt-att', function (e) {
    e.preventDefault();
    e.stopPropagation();

    const b = $(this);
    const type = b.data('type');
    const url = b.data('url');
    const name = b.data('name') || 'Attachment';

    if (type === 'drawing') {
        openDrawingFromNote(b.data('shelf'), b.data('note'), b.data('index'));
        return;
    }

    if (type === 'map') {
        // The picture on the note is the map's likeness; the map is the save
        // filed beside it. With no save there is nothing to edit, so the
        // likeness is shown instead of pretending otherwise.
        const saveId = b.data('save');
        if (saveId) { openMapFromNote(saveId, name); return; }
        if (!url) { toastr.info('This map has no saved plan behind it any more.'); return; }
        see('image', url, name + ' — the picture only; its saved plan is gone');
        return;
    }

    see(type === 'video' ? 'video' : 'image', url, name);
});

// A photo full size, or a recording playing.
function see(kind, url, title) {
    if (!url) { toastr.error('That file is not there any more.'); return; }
    $('#ntSeeTitle').text(title || 'Attachment');
    $('#ntSeeOpen').attr('href', url);
    $('#ntSeeBody').html(kind === 'video'
        ? `<video src="${esc(url)}" controls autoplay playsinline style="max-width:100%;max-height:72vh;border-radius:8px;background:#000;"></video>`
        : `<img src="${esc(url)}" alt="" style="max-width:100%;max-height:72vh;border-radius:8px;background:#fff;">`);
    new bootstrap.Modal(document.getElementById('ntSeeModal')).show();
}

// A recording keeps playing behind a closed window otherwise.
$(document).on('hidden.bs.modal', '#ntSeeModal', function () { $('#ntSeeBody').empty(); });

// A drawing opens in the pad, on its own entry, and saves back over it.
function openDrawingFromNote(shelf, noteId, index) {
    if (typeof window.openDrawCanvas !== 'function') {
        toastr.error('The drawing pad is not on this page.');
        return;
    }

    const CR = `${ROOT}/anisenso-schedule-manager-records`;
    smGet(`${CR}-drawing-one${SQ}&shelf=${encodeURIComponent(shelf)}&noteId=${noteId}&index=${index}`, function (res) {
        if (!res.success) { toastr.error(res.message); return; }
        const d = res.data;
        if (!d.editable) {
            toastr.info('This one was filed as a flat picture — the pad opens it to draw over, not to change what is under.');
        }
        window.openDrawCanvas((dataUrl, objects) => {
            $.ajax({
                url: `${CR}-drawing-save${SQ}`,
                type: 'POST',
                data: {
                    _token: CSRF, shelf, noteId, index,
                    image: dataUrl,
                    strokes: objects ? JSON.stringify(objects) : null,
                    title: d.title || '', note: d.note || '',
                },
                success: (out) => { out.success ? (toastr.success(out.message), load()) : toastr.error(out.message); },
                error: (xhr) => toastr.error(xhr.responseJSON?.message || 'That did not save.')
            });
        }, d.url || null, {
            editable: true,
            objects: d.strokes || null,
            title: d.title || 'Drawing',
            overwrite: true,
            overwriteLabel: d.title ? `\u201c${d.title}\u201d` : 'the one you opened',
            scheduleId: SCHEDULE_ID,
        });
    }).fail(() => toastr.error('Could not read that drawing.'));
}

// A map opens in the map window, on the plan this note is about.
function openMapFromNote(saveId, name) {
    if (!window.cmapOpenSave) {
        toastr.error('The map needs a Google Maps key before it can open.');
        return;
    }
    // The Maps tab owns the window; this asks it to show that plan.
    $('.sm-tabs a[href="#tab-maps"]').tab('show');
    setTimeout(() => $(`.js-mp-load[data-id="${saveId}"]`).trigger('click'), 350);
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
        $('#ntMedia').html((d.media || []).map(thumbHtml).join(''));
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
