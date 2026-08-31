// ---------- DOCUMENTS (the client's Documentation entries) ----------
(function () {

const DE = `${ROOT}/anisenso-schedule-manager-doc-entries`;
const SQ = `?scheduleId=${SCHEDULE_ID}`;
const esc = (v) => escapeHtml(v);
let TAGS = [];
let ENTRIES = [];
let KEEP = [];
let started = false;

function typeClass(t) {
    return t === 'critical_rule' ? 'is-rule' : (t === 'custom' ? 'is-custom' : '');
}

function drawTags() {
    $('#deTags').html(TAGS.length
        ? TAGS.map(t => `<span class="de-tag">${esc(t.name)}
            <i class="bx bx-pencil js-de-tag-edit" role="button" data-id="${t.id}" title="Rename"></i>
            <i class="bx bx-x js-de-tag-del" role="button" data-id="${t.id}" title="Remove"></i></span>`).join('')
        : '<small class="text-secondary">No tags of their own yet.</small>');
}

function draw() {
    drawTags();
    $('#deBody').html(ENTRIES.length ? ENTRIES.map(e => `
        <div class="de-card">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="de-title">${esc(e.title || 'Untitled document')}</div>
                    <div class="de-meta">${esc(e.when || '')}</div>
                </div>
                <div class="text-nowrap">
                    <span class="de-type ${typeClass(e.type)}">${esc(e.typeLabel)}</span>
                    <button class="btn btn-sm btn-outline-primary js-de-edit" data-id="${e.id}"><i class="bx bx-pencil"></i></button>
                </div>
            </div>
            ${e.content ? `<div class="de-words">${esc(stripTags(e.content)).slice(0, 260)}</div>` : ''}
            ${e.files.length ? `<div class="de-files">${e.files.map(f => f.isImage && f.url
                ? `<a href="${esc(f.url)}" target="_blank"><img src="${esc(f.url)}" alt=""></a>`
                : `<a class="de-file" href="${esc(f.url || '#')}" target="_blank"><i class="bx bx-paperclip"></i> ${esc(f.name)}</a>`
            ).join('')}</div>` : ''}
        </div>`).join('')
        : '<div class="de-empty"><i class="bx bx-file-blank"></i>No documents on this season yet.</div>');
}

function stripTags(html) {
    const d = document.createElement('div');
    d.innerHTML = html;
    return d.textContent || '';
}

function load() {
    $('#deBody').html('<div class="de-empty"><i class="bx bx-loader-alt bx-spin"></i>Reading the documents…</div>');
    smGet(`${DE}-data${SQ}`, function (res) {
        TAGS = (res && res.tags) || [];
        ENTRIES = (res && res.entries) || [];
        draw();
    }).fail((xhr) => {
        // The tab is not marked as read when the read failed, so coming back
        // to it asks again instead of showing this for ever.
        started = false;
        $('#deBody').html('<div class="de-empty"><i class="bx bx-error"></i>HERE</div>'.replace('HERE', escapeHtml(smWhyFailed(xhr))));
    });
}

$('#deReload').on('click', load);

// ---- one document -----------------------------------------------------
function syncTagRow() {
    const custom = $('#deType').val() === 'custom';
    $('#deTagRow').toggle(custom);
    $('#deTagId').html(TAGS.map(t => `<option value="${t.id}">${esc(t.name)}</option>`).join('')
        || '<option value="">Make a tag first</option>');
}
$('#deType').on('change', syncTagRow);

function drawKeep() {
    $('#deKeep').html(KEEP.map(f => `<span class="de-file">
        <i class="bx bx-paperclip"></i> ${esc(f.name)}
        <i class="bx bx-x js-de-file-drop" role="button" data-path="${esc(f.path)}" title="Take this one off"></i>
    </span>`).join(''));
}

function openEntry(e) {
    $('#deId').val(e ? e.id : '');
    $('#deModalTitle').text(e ? 'Document' : 'New document');
    $('#deType').val(e ? e.type : 'miscellaneous');
    KEEP = e ? e.files.slice() : [];
    drawKeep();
    syncTagRow();
    if (e && e.tagId) $('#deTagId').val(String(e.tagId));
    $('#deTitle').val(e ? e.title : '');
    $('#deContent').val(e ? e.content : '');
    $('#deFiles').val('');
    $('#deDeleteBtn').toggle(!!e);
    new bootstrap.Modal(document.getElementById('deModal')).show();
}

$('#deNewBtn').on('click', () => openEntry(null));
$(document).on('click', '.js-de-edit', function () {
    openEntry(ENTRIES.find(e => e.id === Number($(this).data('id'))));
});
$(document).on('click', '.js-de-file-drop', function () {
    const p = $(this).data('path');
    KEEP = KEEP.filter(f => f.path !== p);
    drawKeep();
});

$('#deSaveBtn').on('click', function () {
    const $btn = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    const done = () => $btn.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save document');

    const fd = new FormData();
    fd.append('_token', CSRF);
    fd.append('id', $('#deId').val() || 0);
    fd.append('type', $('#deType').val());
    if ($('#deType').val() === 'custom') fd.append('tagId', $('#deTagId').val() || '');
    fd.append('title', $('#deTitle').val());
    fd.append('content', $('#deContent').val());
    KEEP.forEach(f => fd.append('keepPaths[]', f.path));
    Array.from($('#deFiles')[0].files || []).forEach(f => fd.append('files[]', f));

    $.ajax({
        url: `${DE}-save${SQ}`,
        type: 'POST',
        data: fd,
        processData: false,
        contentType: false,
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('deModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed'),
        complete: done
    });
});

$('#deDeleteBtn').on('click', function () {
    if (!confirm('Remove this document from the client\'s app?')) return;
    $.ajax({
        url: `${DE}-delete${SQ}&id=${$('#deId').val()}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            bootstrap.Modal.getInstance(document.getElementById('deModal'))?.hide();
            load();
        },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

// ---- tags -------------------------------------------------------------
function saveTag(id, name) {
    $.ajax({
        url: `${DE}-tag-save${SQ}`,
        type: 'POST',
        data: { _token: CSRF, id: id || 0, name },
        success: (res) => { res.success ? (toastr.success(res.message), load()) : toastr.error(res.message); },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Save failed')
    });
}

$('#deNewTag').on('click', function () {
    const name = prompt('What is the tag called?');
    if (name === null || !name.trim()) return;
    saveTag(0, name.trim());
});

$(document).on('click', '.js-de-tag-edit', function () {
    const t = TAGS.find(x => x.id === Number($(this).data('id')));
    const name = prompt('What should this tag be called?', t ? t.name : '');
    if (name === null || !name.trim()) return;
    saveTag(t.id, name.trim());
});

$(document).on('click', '.js-de-tag-del', function () {
    if (!confirm('Remove this tag?')) return;
    $.ajax({
        url: `${DE}-tag-delete${SQ}&id=${$(this).data('id')}`,
        type: 'DELETE',
        data: { _token: CSRF },
        success: (res) => { res.success ? (toastr.success(res.message), load()) : toastr.error(res.message); },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
});

$('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    if ($(e.target).attr('href') !== '#tab-protocol-doc' || started) return;
    started = true;
    load();
});
if (location.hash === '#tab-protocol-doc') { started = true; $(load); }

})();
