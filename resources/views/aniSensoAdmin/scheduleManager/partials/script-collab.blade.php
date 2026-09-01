// ---------- COLLAB ROOM ----------
// The whole room in one read, through the same endpoint the Member Media room
// screen uses — which takes the season's id, so there is nothing new to ask.
(function () {

const MEDIA = `${ROOT}`;
const esc = (v) => escapeHtml(v);
let started = false;

document.addEventListener('error', function (e) {
    const el = e.target;
    if (!el || el.tagName !== 'IMG' || !el.classList.contains('js-cb-img')) return;
    el.remove();
}, true);

// One message. bodyHtml, not body: the server has already escaped it and put
// Anee's faces back — the team asks her in here too, so a room carries her
// shortcodes the same way a thread does.
function msgHtml(m) {
    return `<div class="cb-msg ${m.mine ? 'is-mine' : ''}">
        <div class="min-w-0">
            <div class="cb-who">${esc(m.who)} <span class="cb-at fw-normal">${esc(m.at)}</span></div>
            ${m.body ? `<div class="cb-body">${m.bodyHtml || esc(m.body)}</div>` : ''}
            ${m.photo ? `<img class="js-cb-img js-cb-open" src="${esc(m.photo)}" alt="">` : ''}
        </div>
        <div class="text-nowrap">
            <button class="btn btn-sm btn-outline-danger js-cb-msg-del" data-id="${m.id}"><i class="bx bx-trash"></i></button>
        </div>
    </div>`;
}

function load() {
    $('#cbBody').html('<div class="cb-empty"><i class="bx bx-loader-alt bx-spin"></i> Reading the room…</div>');
    smGet(`${MEDIA}/anisenso-media-rooms-one?id=${SCHEDULE_ID}`, function (res) {
        if (!res || !res.success) { $('#cbBody').html('<div class="cb-empty">Could not read the room.</div>'); return; }
        const d = res.data;
        const chat = d.chat || [];
        const recs = d.recordings || [];
        const pages = d.pages || [];

        $('#cbBody').html(`<div class="cb-cols">
            <div class="cb-panel">
                <h6>Chat <span class="text-secondary fw-normal">${chat.length}</span></h6>
                <div id="cbChat">
                ${chat.length ? chat.map(msgHtml).join('')
                    : '<div class="cb-empty">Nothing said in here yet.</div>'}
                </div>
                <div class="cb-say">
                    <textarea id="cbSay" class="form-control" rows="1" maxlength="4000"
                              placeholder="Say something to the team…"></textarea>
                    <button type="button" class="btn btn-primary" id="cbSend"><i class="bx bx-send"></i></button>
                </div>
                <p class="cb-say-note">
                    Goes into the room itself, signed AniSystem Technician — the client and their
                    team see it in their app.
                </p>
            </div>
            <div>
                <div class="cb-panel mb-3">
                    <h6>Recordings <span class="text-secondary fw-normal">${recs.length}</span></h6>
                    ${recs.length ? recs.map(r => `
                        <div class="cb-rec">
                            ${r.poster ? `<img class="js-cb-img" src="${esc(r.poster)}" alt="">` : ''}
                            <div class="min-w-0 flex-grow-1">
                                <div class="cb-body">${esc(r.title)}</div>
                                <div class="cb-at">${esc(r.kind || '')}${r.who ? ' · ' + esc(r.who) : ''}${r.seconds ? ' · ' + r.seconds + 's' : ''} · ${esc(r.at)}</div>
                            </div>
                            <div class="text-nowrap">
                                ${r.url ? `<a class="btn btn-sm btn-outline-primary" href="${esc(r.url)}" target="_blank"><i class="bx bx-play"></i></a>` : ''}
                                <button class="btn btn-sm btn-outline-danger js-cb-rec-del" data-id="${r.id}"><i class="bx bx-trash"></i></button>
                            </div>
                        </div>`).join('')
                        : '<div class="cb-empty">Nothing recorded.</div>'}
                </div>
                <div class="cb-panel">
                    <h6>Whiteboard <span class="text-secondary fw-normal">${pages.length} ${pages.length === 1 ? 'page' : 'pages'}</span></h6>
                    ${pages.length ? `<div class="cb-pages">${pages.map(p => `<span class="cb-page">Page ${p.page} · ${esc(p.orientation || '')}</span>`).join('')}</div>`
                        : '<div class="cb-empty">The board is blank.</div>'}
                </div>
            </div>
        </div>`);
    }).fail((xhr) => {
        // The tab is not marked as read when the read failed, so coming back
        // to it asks again instead of showing this for ever.
        started = false;
        $('#cbBody').html('<div class="cb-empty">HERE</div>'.replace('HERE', escapeHtml(smWhyFailed(xhr))));
    });
}

$('#cbReload').on('click', load);

// The box grows with what is written in it; enter sends, shift+enter breaks
// the line — the same bargain every chat makes.
$(document).on('input', '#cbSay', function () {
    this.style.height = 'auto';
    this.style.height = this.scrollHeight + 'px';
});
$(document).on('keydown', '#cbSay', function (e) {
    if (e.key === 'Enter' && !e.shiftKey) { e.preventDefault(); $('#cbSend').trigger('click'); }
});

$(document).on('click', '#cbSend', function () {
    const said = $('#cbSay').val().trim();
    if (!said) return;
    const box = $('#cbSay'), btn = $(this);
    box.prop('disabled', true); btn.prop('disabled', true);

    $.ajax({
        url: `${MEDIA}/anisenso-media-rooms-message-post?id=${SCHEDULE_ID}`,
        type: 'POST',
        data: { _token: CSRF, body: said },
        success: (res) => {
            if (!res.success) { toastr.error(res.message); }
            else {
                $('#cbChat').find('.cb-empty').remove();
                $('#cbChat').append(msgHtml(res.data));
                box.val('').css('height', 'auto');
            }
            box.prop('disabled', false); btn.prop('disabled', false);
            box.trigger('focus');
        },
        error: (xhr) => {
            toastr.error(xhr.responseJSON?.message || 'That did not send.');
            box.prop('disabled', false); btn.prop('disabled', false);
        }
    });
});
$(document).on('click', '.js-cb-open', function () { window.open(this.src, '_blank'); });

function remove(url, ask, done) {
    if (!confirm(ask)) return;
    $.ajax({
        url, type: 'DELETE', data: { _token: CSRF },
        success: (res) => { res.success ? (toastr.success(res.message), done()) : toastr.error(res.message); },
        error: (xhr) => toastr.error(xhr.responseJSON?.message || 'Delete failed')
    });
}

$(document).on('click', '.js-cb-msg-del', function () {
    remove(`${MEDIA}/anisenso-media-rooms-message-delete?id=${$(this).data('id')}`, 'Remove this message from the room?', load);
});
$(document).on('click', '.js-cb-rec-del', function () {
    remove(`${MEDIA}/anisenso-media-rooms-recording-delete?id=${$(this).data('id')}`, 'Remove this recording?', load);
});

$('.sm-tabs a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
    if ($(e.target).attr('href') !== '#tab-collab' || started) return;
    started = true;
    load();
});
if (location.hash === '#tab-collab') { started = true; $(load); }

})();
