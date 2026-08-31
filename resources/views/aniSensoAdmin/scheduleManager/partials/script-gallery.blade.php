// ---------- GALLERY ----------
(function () {

const GL = `${ROOT}/anisenso-schedule-manager-gallery`;
const SQ = `?scheduleId=${SCHEDULE_ID}`;
let ALBUMS = [];
let IMAGES = [];
let filter = 'all';
let started = false;

const esc = (v) => escapeHtml(v);

// A file can outlive its picture — the client app's storage is rebuilt on
// deploy — and a broken-image glyph reads as a broken screen.
document.addEventListener('error', function (e) {
    const el = e.target;
    if (!el || el.tagName !== 'IMG' || !el.classList.contains('js-gl-img')) return;
    const gone = document.createElement('div');
    gone.className = 'gl-gone';
    gone.title = 'The file is not on the disk any more';
    gone.innerHTML = '<i class="bx bx-image-alt"></i>';
    el.replaceWith(gone);
}, true);

function countIn(albumId) {
    return IMAGES.filter(i => (albumId === 'all') || (albumId === 0 ? !i.albumId : i.albumId === albumId)).length;
}

function drawAlbums() {
    const chip = (key, label, editable) => `<span class="gl-chip ${filter === key ? 'active' : ''}" data-album="${key}">
        ${esc(label)} <span class="badge">${countIn(key === 'all' ? 'all' : Number(key))}</span>
        ${editable ? `<i class="bx bx-pencil js-gl-album-edit" data-id="${key}" title="Rename"></i>` : ''}
    </span>`;
    $('#glAlbums').html(
        chip('all', 'All pictures', false)
        + chip('0', 'Not in an album', false)
        + ALBUMS.map(a => chip(String(a.id), a.title, true)).join('')
    );
}

function visible() {
    if (filter === 'all') return IMAGES;
    const id = Number(filter);
    return IMAGES.filter(i => id === 0 ? !i.albumId : i.albumId === id);
}

function draw() {
    drawAlbums();
    const rows = visible();
    $('#glBody').html(rows.length ? `<div class="gl-tiles">${rows.map(r => `
        <div class="gl-tile">
            ${r.url ? `<img class="js-gl-img js-gl-open" src="${esc(r.url)}" data-id="${r.id}" alt="">`
                    : `<div class="gl-gone"><i class="bx bx-image-alt"></i></div>`}
            <div class="gl-body">
                <div class="gl-cap">${esc(r.caption || r.name || 'Untitled')}</div>
                <div class="gl-meta">${esc(r.when || '')}${r.isTeam ? ' · team' : ''}</div>
            </div>
        </div>`).join('')}</div>`
        : `<div class="gl-empty"><i class="bx bx-image"></i>No pictures here.</div>`);
}

function load() {
    $('#glBody').html('<div class="gl-empty"><i class="bx bx-loader-alt bx-spin"></i>Reading the gallery…</div>');
    $.get(`${GL}-data${SQ}`, function (res) {
        ALBUMS = (res && res.albums) || [];
        IMAGES = (res && res.images) || [];
        draw();
    }).fail(() => $('#glBody').html('<div class="gl-empty"><i class="bx bx-error"></i>Could not read the gallery.</div>'));
}

$('#glReload').on('click', load);

$(document).on('click', '#glAlbums .gl-chip', function (e) {
    if ($(e.target).hasClass('js-gl-album-edit')) return;
    filter = $(this).data('album') + '';
    draw();
});

// ---- one picture ------------------------------------------------------
$(document).on('click', '.js-gl-open', function () {
    const r = IMAGES.find(i => i.id === Number($(this).data('id')));
    if (!r) return;
    $('#glId').val(r.id);
    $('#glModalTitle').text(r.caption || r.name || 'Picture');
    $('#glPreview').html(`<img src="${esc(r.url)}" style="max-height:46vh;max-width:100%;border-radius:8px" alt="">`);
    $('#glCaption').val(r.caption || '');
    $('#glDescription').val(r.description || '');
    $('#glOpen').attr('href', r.url || '#');
    $('#glAlbumPick').html(`<option value="0">Not in an album</option>`
        + ALBUMS.map(a => `<option value="${a.id}" ${a.id === r.albumId ? 'selected' : ''}>${esc(a.title)}</option>`).join(''));
    new bootstrap.Modal(document.getElementById('glModal')).show();
});

$('#glSaveBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const done = () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save');
    $.ajax({
        url: `${GL}-image-update${SQ}&id=${$('#glId').val()}`,
        type: 'PUT',
        data: {
            _token: CSRF,
            caption: $('#glCaption').val(),
            description: $('#glDescription').val(),
            albumId: $('#glAlbumPick').val(),
        },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('glModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: done
    });
});

$('#glDeleteBtn').on('click', function () {
    if (!confirm('Remove this picture from the client\'s gallery?')) return;
    $.ajax({
        url: `${GL}-image-delete${SQ}&id=${$('#glId').val()}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('glModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

// ---- adding pictures --------------------------------------------------
$('#glAddBtn').on('click', () => $('#glFiles').trigger('click'));
$('#glFiles').on('change', function () {
    if (!this.files || !this.files.length) return;
    const fd = new FormData();
    fd.append('_token', CSRF);
    // Into whichever album is being looked at, which is what "add pictures"
    // means while standing inside one.
    fd.append('albumId', (filter === 'all' || filter === '0') ? 0 : filter);
    Array.from(this.files).forEach(f => fd.append('files[]', f));
    const $btn = $('#glAddBtn').prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Uploading...');
    $.ajax({
        url: `${GL}-image-store${SQ}`,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Upload failed'),
        complete: () => { $btn.prop('disabled', false).html('<i class="bx bx-image-add me-1"></i> Add pictures'); $('#glFiles').val(''); }
    });
});

// ---- albums -----------------------------------------------------------
function openAlbum(a) {
    $('#glAlbumId').val(a ? a.id : '');
    $('#glAlbumModalTitle').text(a ? 'Album' : 'New album');
    $('#glAlbumTitle').val(a ? a.title : '');
    $('#glAlbumDescription').val(a ? a.description : '');
    $('#glAlbumDeleteBtn').toggle(!!a);
    new bootstrap.Modal(document.getElementById('glAlbumModal')).show();
}

$('#glNewAlbum').on('click', () => openAlbum(null));
$(document).on('click', '.js-gl-album-edit', function (e) {
    e.stopPropagation();
    openAlbum(ALBUMS.find(a => a.id === Number($(this).data('id'))));
});

$('#glAlbumSaveBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const done = () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save album');
    $.ajax({
        url: `${GL}-album-save${SQ}`,
        type: 'POST',
        data: {
            _token: CSRF,
            id: $('#glAlbumId').val() || 0,
            title: $('#glAlbumTitle').val(),
            description: $('#glAlbumDescription').val(),
        },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('glAlbumModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: done
    });
});

// An album with pictures in it is not removed by accident: the second ask is
// what happens to them.
$('#glAlbumDeleteBtn').on('click', function () {
    const id = $('#glAlbumId').val();
    if (!confirm('Remove this album?')) return;
    const go = (extra) => $.ajax({
        url: `${GL}-album-delete${SQ}&id=${id}`,
        type: 'DELETE',
        data: Object.assign({ _token: CSRF }, extra || {}),
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('glAlbumModal'))?.hide();
            filter = 'all';
            load();
        },
        error: (xhr) => {
            const msg = xhr.responseJSON?.message || 'Delete failed';
            if (xhr.status === 422 && msg.indexOf('still has pictures') > -1) {
                if (confirm('That album still has pictures. Delete them too?')) { go({ withImages: 1 }); return; }
                return;
            }
            toastr.error(msg);
        }
    });
    go();
});

$('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    if ($(e.target).attr('href') !== '#tab-gallery' || started) return;
    started = true;
    load();
});
if (location.hash === '#tab-gallery') { started = true; $(load); }

})();
