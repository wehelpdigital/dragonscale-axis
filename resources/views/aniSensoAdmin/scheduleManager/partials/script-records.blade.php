// ---------- FIELD RECORDS ----------
// Six lists over five different shapes, so each section says how to ask for a
// page and how to draw one, and the shell below does the rest. Nothing here
// queries: these are the Member Media endpoints, which now take ?scheduleId=.
(function () {

const REC_PAGE = 12;
const MEDIA = '{{ url('/') }}';
const SQ = '?scheduleId={{ $schedule->id }}';

const state = { section: 'notes', start: 0, search: '', total: 0 };

const esc = (v) => escapeHtml(v);
const when = (v) => esc(v || '—');

// A file can outlive its picture — the app's own storage is rebuilt on
// deploy — and a broken-image glyph reads as a broken SCREEN. Capture phase,
// because an image's error event does not bubble.
document.addEventListener('error', function (e) {
    const el = e.target;
    if (!el || el.tagName !== 'IMG' || !el.classList.contains('js-rec-img')) return;
    const gone = document.createElement('div');
    gone.className = 'rec-gone';
    gone.title = 'The file is not on the disk any more';
    gone.innerHTML = '<i class="bx bx-image-alt"></i>';
    el.replaceWith(gone);
}, true);

function tile(url, title, meta, extra) {
    return `<div class="rec-tile">
        ${url ? `<img class="js-rec-img js-rec-open" src="${esc(url)}" data-url="${esc(url)}" data-caption="${esc(title)}" alt="">`
              : `<div class="rec-gone"><i class="bx bx-image-alt"></i></div>`}
        <div class="rec-tile-body">
            <div class="rec-title text-truncate" title="${esc(title)}">${esc(title)}</div>
            <div class="rec-meta">${esc(meta || '')}</div>
            ${extra || ''}
        </div>
    </div>`;
}

const SECTIONS = {
    notes: {
        url: (s) => `${MEDIA}/anisenso-media-notes-data${SQ}&draw=1&start=${s.start}&length=${REC_PAGE}` +
                    (s.search ? `&searchFilter=${encodeURIComponent(s.search)}` : ''),
        empty: ['bx-note', 'Nothing written on this season yet.'],
        draw: (rows) => rows.map(r => `<div class="rec-card">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="rec-title">${esc(r.title)}</div>
                    <div class="rec-meta">${esc({note: 'The notebook', inline: 'Board sticky', date: "A day's note"}[r.shelf] || r.shelf)}
                        · ${when(r.when)}${r.attachments ? ' · ' + r.attachments + ' attached' : ''}</div>
                    ${r.words ? `<div class="rec-words">${esc(r.words)}</div>` : ''}
                </div>
                <div class="text-nowrap">
                    <button class="btn btn-sm btn-outline-primary js-rec-note" data-shelf="${esc(r.shelf)}" data-id="${r.id}">Read</button>
                    <button class="btn btn-sm btn-outline-danger js-rec-del"
                            data-url="${MEDIA}/anisenso-media-notes-delete?shelf=${esc(r.shelf)}&id=${r.id}"
                            data-ask="Remove this note from the client's app?"><i class="bx bx-trash"></i></button>
                </div>
            </div>
        </div>`).join(''),
    },
    photos: {
        url: (s) => `${MEDIA}/anisenso-media-gallery-data${SQ}&draw=1&start=${s.start}&length=${REC_PAGE}` +
                    (s.search ? `&searchFilter=${encodeURIComponent(s.search)}` : ''),
        empty: ['bx-image', 'No photos in this season’s gallery.'],
        draw: (rows) => `<div class="rec-tiles">` + rows.map(r => tile(
            r.url, r.caption || r.fileName || 'Untitled',
            [r.albumTitle, r.created_at].filter(Boolean).join(' · '),
            `<button class="btn btn-sm btn-outline-danger mt-2 w-100 js-rec-del"
                     data-url="${MEDIA}/anisenso-media-gallery-delete?id=${r.id}"
                     data-ask="Remove this photo from the client's gallery?"><i class="bx bx-trash"></i> Remove</button>`
        )).join('') + `</div>`,
    },
    drawings: {
        url: (s) => `${MEDIA}/anisenso-media-drawings-data${SQ}&draw=1&start=${s.start}&length=${REC_PAGE}` +
                    (s.search ? `&searchFilter=${encodeURIComponent(s.search)}` : ''),
        empty: ['bx-pencil', 'Nothing drawn on this season yet.'],
        draw: (rows) => `<div class="rec-tiles">` + rows.map(r => tile(
            r.url, r.title, [r.team ? 'Collab board' : 'Drawing pad', r.when].filter(Boolean).join(' · '),
            `<button class="btn btn-sm btn-outline-danger mt-2 w-100 js-rec-del"
                     data-url="${MEDIA}/anisenso-media-drawings-delete?shelf=${esc(r.shelf)}&noteId=${r.noteId}&index=${r.index}"
                     data-ask="Take this drawing out of the note that holds it? The note itself stays."><i class="bx bx-trash"></i> Remove</button>`
        )).join('') + `</div>`,
    },
    maps: {
        url: (s) => `${MEDIA}/anisenso-media-maps-data${SQ}&draw=1&start=${s.start}&length=${REC_PAGE}` +
                    (s.search ? `&searchFilter=${encodeURIComponent(s.search)}` : ''),
        empty: ['bx-map-alt', 'No saved maps on this season.'],
        draw: (rows) => rows.map(r => `<div class="rec-card">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="rec-title">${esc(r.title || 'Untitled map')}</div>
                    <div class="rec-meta">${r.shapes} shape${r.shapes === 1 ? '' : 's'}
                        ${r.source ? '· ' + esc(r.source) : ''} · ${when(r.updated_at)}</div>
                </div>
                <div class="text-nowrap">
                    <button class="btn btn-sm btn-outline-danger js-rec-del"
                            data-url="${MEDIA}/anisenso-media-maps-delete?id=${r.id}"
                            data-ask="Remove this saved map?"><i class="bx bx-trash"></i></button>
                </div>
            </div>
        </div>`).join(''),
    },
    harvest: {
        // Its own endpoint, and small enough to arrive whole: a season has a
        // handful of harvest records, not a page of them.
        url: () => `${MEDIA}/anisenso-schedule-manager-harvest${SQ}`,
        whole: true,
        empty: ['bx-basket', 'Nothing recorded off the field yet.'],
        draw: (rows, extra) => (extra && extra.totalValue
                ? `<div class="rec-card" style="background:#f6f8fb;">
                       <div class="rec-title">Season value so far</div>
                       <div class="rec-meta">From the records that carry both a yield and a price</div>
                       <div class="text-dark mt-1" style="font-size:18px;font-weight:600;">${fmtPeso(extra.totalValue)}</div>
                   </div>` : '')
            + rows.map(r => `<div class="rec-card">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="rec-title">${esc(r.title)}
                        ${r.category ? `<span class="rec-chip ms-1">${esc(r.category)}</span>` : ''}</div>
                    <div class="rec-meta">${[r.lotName, r.when].filter(Boolean).map(esc).join(' · ') || '—'}</div>
                    <div class="rec-words">
                        ${r.yieldAmount !== null ? `<b>${fmtNumber(r.yieldAmount, 0)}</b> ${esc(r.yieldUnit || '')}` : ''}
                        ${r.moisturePercent !== null ? ` · ${fmtNumber(r.moisturePercent, 1)}% moisture` : ''}
                        ${r.pricePerUnit !== null ? ` · ${fmtPeso(r.pricePerUnit)}/${esc(r.yieldUnit || 'unit')}` : ''}
                        ${r.value ? ` · <b>${fmtPeso(r.value)}</b>` : ''}
                        ${r.buyer ? ` · sold to ${esc(r.buyer)}` : ''}
                    </div>
                    ${r.photos.length ? `<div class="rec-tiles mt-2">` + r.photos.map(u => tile(u, r.title, '')).join('') + `</div>` : ''}
                </div>
                <div class="text-nowrap">
                    <button class="btn btn-sm btn-outline-danger js-rec-del"
                            data-url="${MEDIA}/anisenso-schedule-manager-harvest-delete${SQ}&id=${r.id}"
                            data-ask="Remove this post-harvest record?"><i class="bx bx-trash"></i></button>
                </div>
            </div>
        </div>`).join(''),
    },
    ai: {
        url: (s) => `${MEDIA}/anisenso-ai-conversations-data${SQ}&draw=1&start=${s.start}&length=${REC_PAGE}` +
                    (s.search ? `&searchFilter=${encodeURIComponent(s.search)}` : ''),
        empty: ['bx-bot', 'The client has not asked the AI about this season.'],
        draw: (rows) => rows.map(r => `<div class="rec-card">
            <div class="d-flex justify-content-between align-items-start gap-2">
                <div class="min-w-0">
                    <div class="rec-title">${esc(r.title || 'Untitled thread')}
                        <span class="rec-chip ms-1">${r.kind === 'team' ? 'Collab Room' : 'Personal'}</span></div>
                    <div class="rec-meta">${r.messageCount} message${r.messageCount === 1 ? '' : 's'}
                        · ${fmtNumber(r.credits, 2)} credits · ${when(r.lastAt)}</div>
                </div>
                <div class="text-nowrap">
                    <button class="btn btn-sm btn-outline-primary js-rec-thread" data-kind="${esc(r.kind)}" data-id="${r.id}">Read</button>
                </div>
            </div>
        </div>`).join(''),
    },
};

function paint(html) { $('#recBody').html(html); }

function load() {
    const spec = SECTIONS[state.section];
    paint('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-3 text-secondary"></i></div>');
    $('#recPager').hide();
    $('#recSearchRow').toggle(!spec.whole);

    $.get(spec.url(state), function (res) {
        // Two shapes: the DataTables endpoints answer {data, recordsTotal},
        // ours answers {success, data:{rows}}.
        let rows, total, extra = null;
        if (Array.isArray(res.data)) {
            rows = res.data;
            total = res.recordsTotal || rows.length;
        } else if (res.success && res.data) {
            rows = res.data.rows || [];
            total = rows.length;
            extra = res.data;
        } else {
            paint('<div class="rec-empty"><i class="bx bx-error-circle"></i>Could not read these records.</div>');
            return;
        }
        state.total = total;
        $('#recSubtabs [data-count="' + state.section + '"]').text(total);

        if (!rows.length) {
            paint(`<div class="rec-empty"><i class="bx ${spec.empty[0]}"></i>${esc(spec.empty[1])}</div>`);
            return;
        }
        paint(spec.draw(rows, extra));

        if (!spec.whole && total > REC_PAGE) {
            $('#recRange').text(`${state.start + 1}–${Math.min(state.start + REC_PAGE, total)} of ${total}`);
            $('#recPrev').prop('disabled', state.start === 0);
            $('#recNext').prop('disabled', state.start + REC_PAGE >= total);
            $('#recPager').css('display', 'flex');
        }
    }).fail(() => paint('<div class="rec-empty"><i class="bx bx-error-circle"></i>Could not read these records.</div>'));
}

// The counts on the pills, so the tab says what is there before it is opened.
function loadCounts() {
    Object.keys(SECTIONS).forEach(function (key) {
        const spec = SECTIONS[key];
        const url = spec.whole ? spec.url({}) : spec.url({ start: 0, search: '' });
        $.get(url, function (res) {
            const n = Array.isArray(res.data) ? (res.recordsTotal || res.data.length)
                    : (res.data && res.data.rows ? res.data.rows.length : 0);
            $('#recSubtabs [data-count="' + key + '"]').text(n);
        }).fail(() => $('#recSubtabs [data-count="' + key + '"]').text('–'));
    });
}

$('#recSubtabs').on('click', '.nav-link', function () {
    $('#recSubtabs .nav-link').removeClass('active');
    $(this).addClass('active');
    state.section = $(this).data('section');
    state.start = 0;
    state.search = '';
    $('#recSearch').val('');
    load();
});

let recTyping = null;
$('#recSearch').on('input', function () {
    state.search = $(this).val();
    state.start = 0;
    clearTimeout(recTyping);
    recTyping = setTimeout(load, 350);
});
$('#recPrev').on('click', () => { state.start = Math.max(0, state.start - REC_PAGE); load(); });
$('#recNext').on('click', () => { state.start += REC_PAGE; load(); });
$('#recReload').on('click', () => { load(); loadCounts(); });

$(document).on('click', '.js-rec-open', function () {
    const url = $(this).data('url');
    $('#recViewerTitle').text($(this).data('caption') || 'Photo');
    $('#recViewerOpen').attr('href', url);
    $('#recViewerBody').html(`<img src="${esc(url)}" style="max-height:74vh;max-width:100%;border-radius:8px" alt="">`);
    $('#recViewer').modal('show');
});

$(document).on('click', '.js-rec-note', function () {
    const b = $(this);
    $('#recNoteBody').html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-3 text-secondary"></i></div>');
    $('#recNoteModal').modal('show');
    $.get(`${MEDIA}/anisenso-media-notes-one?shelf=${b.data('shelf')}&id=${b.data('id')}`, function (res) {
        if (!res.success) { $('#recNoteBody').html(`<p class="text-secondary mb-0">${esc(res.message)}</p>`); return; }
        const d = res.data;
        $('#recNoteTitle').text(d.title);
        $('#recNoteSub').text([d.clientName || d.clientEmail, d.when].filter(Boolean).join(' · '));
        let html = d.body ? `<div class="text-dark" style="white-space:pre-wrap">${esc(d.body)}</div>`
                          : '<p class="text-secondary">No words in this note.</p>';
        if (d.media && d.media.length) {
            html += '<h6 class="text-dark mt-3 mb-2">Attached</h6><div class="rec-tiles">';
            html += d.media.map(m => m.url
                ? (m.type === 'video'
                    ? `<a class="rec-chip" href="${esc(m.url)}" target="_blank">&#9654; ${esc(m.name || 'Video')}</a>`
                    : tile(m.url, m.name || '', ''))
                : `<span class="rec-chip">${esc(m.name || m.type)}</span>`).join('');
            html += '</div>';
        }
        $('#recNoteBody').html(html);
    }).fail(() => $('#recNoteBody').html('<p class="text-secondary mb-0">Could not read that note.</p>'));
});

$(document).on('click', '.js-rec-thread', function () {
    const b = $(this);
    $('#recThreadBody').html('<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-3 text-secondary"></i></div>');
    $('#recThreadModal').modal('show');
    $.get(`${MEDIA}/anisenso-ai-conversations?id=${b.data('id')}&kind=${b.data('kind')}`, function (res) {
        if (!res.success) { $('#recThreadBody').html(`<p class="text-secondary mb-0">${esc(res.message)}</p>`); return; }
        const d = res.data;
        $('#recThreadTitle').text(d.head.title || 'Thread');
        $('#recThreadSub').text([d.head.clientName || d.head.clientEmail, d.head.startedAt].filter(Boolean).join(' · '));
        $('#recThreadBody').html((d.turns || []).map(t => `
            <div class="rec-turn ${t.role === 'assistant' ? 'is-ai' : ''}">
                <div class="rec-bubble">${esc(t.content)}</div>
            </div>`).join('') || '<p class="text-secondary mb-0">Nothing was ever said in this thread.</p>');
    }).fail(() => $('#recThreadBody').html('<p class="text-secondary mb-0">Could not read that thread.</p>'));
});

// Every removal on this tab is the app's own kind: hidden, never destroyed.
$(document).on('click', '.js-rec-del', function () {
    const b = $(this);
    if (!confirm(b.data('ask'))) return;
    $.ajax({
        url: b.data('url'),
        type: 'DELETE',
        headers: { 'X-CSRF-TOKEN': CSRF },
        success: function (res) {
            if (res && res.success) { toastr.success(res.message || 'Removed.'); }
            else { toastr.error((res && res.message) || 'Could not remove that.'); }
            load();
            loadCounts();
        },
        error: () => toastr.error('Could not remove that.'),
    });
});

// The lists only cost anything once the tab is actually opened.
let recStarted = false;
$('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    if ($(e.target).attr('href') !== '#tab-records' || recStarted) return;
    recStarted = true;
    load();
    loadCounts();
});
if (location.hash === '#tab-records') { recStarted = true; $(function () { load(); loadCounts(); }); }

})();
