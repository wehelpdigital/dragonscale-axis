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
                        <button class="btn btn-sm btn-primary js-mp-load" data-id="${r.id}" data-title="${esc(r.title)}"
                                title="Put this plan back on the map"><i class="bx bx-upload"></i> Open on the map</button>
                        <button class="btn btn-sm btn-outline-primary js-mp-open" data-id="${r.id}"
                                title="Look at its shapes without touching the map"><i class="bx bx-shape-polygon"></i></button>
                        <button class="btn btn-sm btn-light js-mp-rename" data-id="${r.id}" data-title="${esc(r.title)}"><i class="bx bx-pencil"></i></button>
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

// ---- and the map itself ----------------------------------------------
// The engine waits to be started, and fetches Google's script only then, so
// a season whose map is never opened costs nothing. Every showing, not only
// the first: a map built inside a hidden pane measures its container as zero
// and keeps that height until something tells it to look again.
function wakeTheMap() {
    if (!window.initCollabMap) return;
    // After the pane is actually visible — Google reads the container's size
    // the moment it builds, and Bootstrap has not finished showing it when
    // the event fires.
    requestAnimationFrame(() => window.initCollabMap());
}
$('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    if ($(e.target).attr('href') === '#tab-maps') wakeTheMap();
});
if (location.hash === '#tab-maps') $(wakeTheMap);

// ---- one map, drawn --------------------------------------------------
// What is open, and the shapes as the server last confirmed them. `at` is a
// shape's position in the stored list and is what the save is keyed on —
// names are not unique and a colour certainly is not.
let openMap = null;

const MAP_W = 200, MAP_H = 120, MAP_PAD = 10;

// Latitude and longitude to somewhere inside the box. One scale for both
// axes, so a square field is drawn square; latitude climbs northward and the
// screen's y climbs downward, so that one is flipped.
function mapFitter(objects) {
    const pts = [];
    objects.forEach(o => (o.points || []).forEach(p => {
        if (Array.isArray(p) && isFinite(p[0]) && isFinite(p[1])) pts.push(p);
    }));
    if (!pts.length) return null;

    const lat = pts.map(p => p[0]), lng = pts.map(p => p[1]);
    const minLat = Math.min(...lat), maxLat = Math.max(...lat);
    const minLng = Math.min(...lng), maxLng = Math.max(...lng);
    // A map of a single pin has no width at all; give it some so the one
    // point lands in the middle instead of dividing by zero.
    const spanLat = (maxLat - minLat) || 0.0004;
    const spanLng = (maxLng - minLng) || 0.0004;
    const k = Math.min((MAP_W - MAP_PAD * 2) / spanLng, (MAP_H - MAP_PAD * 2) / spanLat);

    return {
        x: (p) => MAP_PAD + (p[1] - minLng) * k + ((MAP_W - MAP_PAD * 2) - spanLng * k) / 2,
        y: (p) => MAP_H - MAP_PAD - (p[0] - minLat) * k - ((MAP_H - MAP_PAD * 2) - spanLat * k) / 2,
        // Roughly how wide the drawn ground is, for the line under it. A
        // degree of longitude is about 111 km at the equator and narrows with
        // latitude; near enough for "this is about 80 m across".
        metres: spanLng * 111320 * Math.cos((minLat + maxLat) / 2 * Math.PI / 180),
    };
}

function drawMap(objects) {
    const svg = document.getElementById('mpCanvas');
    const fit = mapFitter(objects);
    if (!fit) {
        svg.innerHTML = '<text x="100" y="60" text-anchor="middle" fill="#98a4b6">Nothing on this map has a position.</text>';
        $('#mpScale').text('');
        return;
    }

    const NS = 'http://www.w3.org/2000/svg';
    svg.innerHTML = '';
    objects.forEach((o, i) => {
        if (o.__cut) return;
        const pts = (o.points || []).filter(p => Array.isArray(p) && isFinite(p[0]) && isFinite(p[1]));
        if (!pts.length) return;
        const colour = /^#[0-9a-fA-F]{6}$/.test(o.color || '') ? o.color : '#556ee6';
        const xy = pts.map(p => [fit.x(p), fit.y(p)]);
        const kind = String(o.kind || o.type || 'shape');
        let el;

        if (kind === 'pin' || kind === 'text' || xy.length === 1) {
            el = document.createElementNS(NS, 'circle');
            el.setAttribute('cx', xy[0][0]); el.setAttribute('cy', xy[0][1]);
            el.setAttribute('r', 2.6); el.setAttribute('fill', colour);
            el.setAttribute('stroke', '#fff'); el.setAttribute('stroke-width', 1);
        } else if (kind === 'area') {
            el = document.createElementNS(NS, 'polygon');
            el.setAttribute('points', xy.map(p => p.join(',')).join(' '));
            el.setAttribute('fill', colour); el.setAttribute('fill-opacity', .22);
            el.setAttribute('stroke', colour); el.setAttribute('stroke-width', Math.max(1, +o.width || 2) / 2);
        } else {
            el = document.createElementNS(NS, 'polyline');
            el.setAttribute('points', xy.map(p => p.join(',')).join(' '));
            el.setAttribute('fill', 'none'); el.setAttribute('stroke', colour);
            el.setAttribute('stroke-width', Math.max(1, +o.width || 2) / 2);
            el.setAttribute('stroke-linejoin', 'round'); el.setAttribute('stroke-linecap', 'round');
        }
        el.setAttribute('data-at', i);
        svg.appendChild(el);

        if (o.label) {
            const mid = xy[Math.floor(xy.length / 2)];
            const t = document.createElementNS(NS, 'text');
            t.setAttribute('x', mid[0]); t.setAttribute('y', mid[1] - 4);
            t.setAttribute('text-anchor', 'middle');
            t.textContent = String(o.label).slice(0, 28);
            svg.appendChild(t);
        }
    });

    const m = fit.metres;
    $('#mpScale').text(m > 20
        ? `About ${m > 1500 ? (m / 1000).toFixed(1) + ' km' : Math.round(m) + ' m'} across. Drawn to scale, without a basemap.`
        : 'Drawn to scale, without a basemap.');
}

function drawShapeList(objects) {
    $('#mpShapes').html(objects.map((o, i) => `
        <div class="mp-shape ${o.__cut ? 'is-cut' : ''}" data-at="${i}">
            <span class="mp-what">${esc(String(o.kind || o.type || 'shape'))}</span>
            <input type="text" class="form-control form-control-sm js-mp-label"
                   value="${esc(o.label || '')}" placeholder="No name" maxlength="120">
            <input type="color" class="form-control form-control-color js-mp-colour"
                   value="${/^#[0-9a-fA-F]{6}$/.test(o.color || '') ? o.color : '#556ee6'}">
            <button class="btn btn-sm btn-outline-danger js-mp-cut" title="Take this off the map">
                <i class="bx bx-${o.__cut ? 'undo' : 'trash'}"></i>
            </button>
        </div>`).join('') || '<p class="text-secondary mb-0" style="font-size:12px;">This map has nothing on it.</p>');
}

function paintMap() { drawMap(openMap.objects); drawShapeList(openMap.objects); }

// Putting a saved plan back on the canvas above.
//
// Handed straight to the map, which already knows the order this has to
// happen in — the edits owed to the map being left are written before the
// swap, or they would be filed under the arriving map's name. Not repeated
// here.
$(document).on('click', '.js-mp-load', function () {
    const b = $(this);
    if (!window.cmapOpenSave) {
        toastr.error('The map is not on this page — it needs a Google Maps key.');
        return;
    }
    b.prop('disabled', true);
    Promise.resolve(window.cmapOpenSave({ id: b.data('id'), name: b.data('title') }))
        .catch(() => {})
        .then(() => {
            b.prop('disabled', false);
            // The shelf's own cards carry shape counts, and one of them just
            // became the canvas.
            loadMaps();
            document.getElementById('cmapWrap')?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        });
});

$(document).on('click', '.js-mp-open', function () {
    const id = $(this).data('id');
    $('#mpShapes').html('<div class="text-center py-3"><i class="bx bx-loader-alt bx-spin fs-4 text-secondary"></i></div>');
    $('#mpCanvas').html('');
    new bootstrap.Modal(document.getElementById('mpModal')).show();
    smGet(`${CR}-map-one${SQ}&id=${id}`, function (res) {
        if (!res.success) { $('#mpShapes').html(`<p class="text-secondary mb-0">${esc(res.message)}</p>`); return; }
        openMap = { id, objects: res.data.objects || [] };
        $('#mpModalTitle').text(res.data.title);
        paintMap();
    }).fail(() => $('#mpShapes').html('<p class="text-secondary mb-0">Could not read that map.</p>'));
});

// Typing renames as you go; the picture keeps up so a name lands where you
// can see whether it is the right shape.
$(document).on('input', '.js-mp-label', function () {
    openMap.objects[+$(this).closest('.mp-shape').data('at')].label = this.value;
    drawMap(openMap.objects);
});
$(document).on('change', '.js-mp-colour', function () {
    openMap.objects[+$(this).closest('.mp-shape').data('at')].color = this.value;
    drawMap(openMap.objects);
});

// Cutting is a mark, not a removal, until the map is saved — so a mis-click
// on somebody else's field is one more click to undo.
$(document).on('click', '.js-mp-cut', function () {
    const at = +$(this).closest('.mp-shape').data('at');
    openMap.objects[at].__cut = !openMap.objects[at].__cut;
    paintMap();
});

$('#mpSave').on('click', function () {
    if (!openMap) return;
    const btn = $(this).prop('disabled', true);
    // `at` is the position in the STORED list, which is why nothing is
    // reordered here — the server matches on it to find the geometry the
    // console never sees.
    const send = openMap.objects
        .map((o, at) => ({ at, label: o.label || '', color: o.color || '' , cut: !!o.__cut }))
        .filter(o => !o.cut);

    $.ajax({
        url: `${CR}-map-save${SQ}&id=${openMap.id}`,
        type: 'PUT',
        data: { _token: CSRF, objects: JSON.stringify(send) },
        success: (res) => {
            btn.prop('disabled', false);
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            openMap.objects = res.data.objects || [];
            paintMap();
            loadMaps();
        },
        error: (xhr) => {
            btn.prop('disabled', false);
            toastr.error(xhr.responseJSON?.message || 'The map did not save.');
        }
    });
});

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

// Opening one keeps you on the season. The tile carries what the modal needs
// to title itself, so this costs no second read.
$(document).on('click', '.js-dw-open', function () {
    const tile = $(this).closest('.dw-tile');
    $('#dwModalImg').attr('src', this.src);
    $('#dwModalOpen').attr('href', this.src);
    $('#dwModalTitle').text(tile.find('.dw-title').text() || 'Drawing');
    $('#dwModalMeta').text(tile.find('.dw-meta').text() || '');
    // "Its note" leaves for the Notes tab, so the drawing has to get out of
    // the way first or it sits over the thing it just sent you to.
    $('#dwModalNote').off('click.dw').on('click.dw', function () {
        bootstrap.Modal.getInstance(document.getElementById('dwModal')).hide();
    });
    new bootstrap.Modal(document.getElementById('dwModal')).show();
});

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

// What the mark on an admin's line looks like in the database. The server
// puts it there; this turns it back into a label rather than leaving the
// brackets sitting in the sentence.
const ADMIN_MARK = '[technician] ';

// Which thread the modal is showing, so a reply knows where to go.
let openThread = null;

// bodyHtml, not body: the server has already escaped it and put Anee's faces
// back where she wrote them. Escaping it again would show the <img> tags as
// words.
function turnHtml(t) {
    const mine = (t.body || '').startsWith(ADMIN_MARK);
    let html = t.bodyHtml || esc(t.body);
    if (mine) {
        html = `<span class="ai-mine-tag">You, as the technician</span><br>`
             + html.replace(esc(ADMIN_MARK), '');
    }
    const side = t.role === 'user' ? (mine ? 'is-mine' : '') : 'is-bot';
    return `<div class="ai-turn ${side}"><div class="ai-bubble">${html}</div></div>`;
}

function sayEnabled(on) {
    $('#ctSay').prop('disabled', !on);
    $('#ctSend').prop('disabled', !on);
}

$(document).on('click', '.js-ct-read', function () {
    const b = $(this);
    openThread = { kind: String(b.data('kind')), id: b.data('id') };
    sayEnabled(false);
    $('#ctSay').val('');
    $('#ctModalBody').html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-3 text-secondary"></i></div>');
    new bootstrap.Modal(document.getElementById('ctModal')).show();
    smGet(`${CR}-ai-one${SQ}&kind=${encodeURIComponent(openThread.kind)}&id=${openThread.id}`, function (res) {
        if (!res.success) { $('#ctModalBody').html(`<p class="text-secondary mb-0">${esc(res.message)}</p>`); return; }
        $('#ctModalTitle').text(res.data.title);
        const turns = res.data.turns || [];
        $('#ctModalBody').html(turns.length ? turns.map(turnHtml).join('')
            : '<p class="text-secondary mb-0">Nothing was said in this one.</p>');
        sayEnabled(true);
    }).fail(() => $('#ctModalBody').html('<p class="text-secondary mb-0">Could not read that thread.</p>'));
});

// The box grows with what is written in it, up to the height the style caps.
$(document).on('input', '#ctSay', function () {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});

// Enter sends, shift+enter is a new line — the same bargain every chat makes.
$(document).on('keydown', '#ctSay', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $('#ctSend').trigger('click'); }
});

$(document).on('click', '#ctSend', function () {
    const said = $('#ctSay').val().trim();
    if (!said || !openThread) return;

    sayEnabled(false);
    // The question goes up straight away — waiting for the round trip to see
    // your own words back is what makes a chat feel broken.
    $('#ctModalBody').append(turnHtml({ role: 'user', body: ADMIN_MARK + said, bodyHtml: esc(said) }));
    $('#ctModalBody').append('<div class="ai-turn is-bot" id="ctWait"><div class="ai-bubble"><i class="bx bx-loader-alt bx-spin"></i> Anee is reading…</div></div>');
    $('#ctModalBody').scrollTop($('#ctModalBody')[0].scrollHeight);
    $('#ctSay').val('').css('height', 'auto');

    // Nothing is written unless she answers, so a failure takes the local
    // bubble back off and hands the words to the box they came from.
    // Pressing send again is then the whole of the retry.
    const undo = (why) => {
        $('#ctWait').remove();
        $('#ctModalBody').children('.ai-turn').last().remove();
        $('#ctSay').val(said).trigger('input');
        toastr.error(why);
        sayEnabled(true);
    };

    $.ajax({
        url: `${CR}-ai-reply${SQ}&kind=${encodeURIComponent(openThread.kind)}&id=${openThread.id}`,
        type: 'POST',
        data: { _token: CSRF, body: said },
        success: (res) => {
            if (!res.success) { undo(res.message); return; }
            $('#ctWait').remove();
            // The server's own copy of both turns replaces the local one, so
            // what is on screen is what is in the database.
            $('#ctModalBody').children('.ai-turn').last().remove();
            $('#ctModalBody').append((res.data.turns || []).map(turnHtml).join(''));
            $('#ctModalBody').scrollTop($('#ctModalBody')[0].scrollHeight);
            sayEnabled(true);
            loadThreads();
        },
        error: (xhr) => undo(xhr.responseJSON?.message || 'That did not send.')
    });
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
