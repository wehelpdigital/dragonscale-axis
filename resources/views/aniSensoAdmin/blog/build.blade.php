@extends('layouts.master')

@section('title') Build — {{ $post->title }} @endsection

@section('css')
<style>
    .bb-wrap { display: grid; grid-template-columns: minmax(0, 1fr) minmax(0, 26rem); gap: 1rem; align-items: start; }
    @media (max-width: 1199px) { .bb-wrap { grid-template-columns: minmax(0, 1fr); } }

    .bb-add { display: flex; flex-wrap: wrap; gap: .4rem; }
    .bb-add button {
        border: 1px dashed #c7d2fe; background: #fbfcff; color: #3a4699; border-radius: 999px;
        padding: .35rem .8rem; font-size: 12.5px; font-weight: 600;
    }
    .bb-add button:hover { background: #eef2ff; }

    .bb-block { border: 1px solid #e6e8ec; border-radius: 10px; background: #fff; margin-bottom: .6rem; }
    .bb-block.is-drag { opacity: .45; }
    .bb-head {
        display: flex; align-items: center; gap: .5rem; padding: .45rem .7rem;
        background: #f8fafd; border-bottom: 1px solid #eef1f6; border-radius: 10px 10px 0 0;
    }
    .bb-grip { cursor: grab; color: #98a4b6; }
    .bb-kind { font-size: 11.5px; font-weight: 700; color: #556ee6; text-transform: uppercase; letter-spacing: .04em; }
    .bb-body { padding: .7rem; }
    .bb-body label { font-size: 11.5px; font-weight: 600; color: #74788d; margin-bottom: .15rem; }
    .bb-body .form-control, .bb-body .form-select { font-size: 13px; }
    .bb-thumbs { display: flex; flex-wrap: wrap; gap: .35rem; margin-top: .4rem; }
    .bb-thumbs img { width: 72px; height: 54px; object-fit: cover; border-radius: 6px; border: 1px solid #e6e8ec; }
    .bb-empty { text-align: center; padding: 2.2rem 1rem; color: #98a4b6; border: 1px dashed #d3d6db; border-radius: 10px; }

    .bb-preview { position: sticky; top: 80px; }
    .bb-frame { width: 100%; height: 74vh; border: 1px solid #e6e8ec; border-radius: 12px; background: #fff; }
    .bb-link { font-size: 11.5px; word-break: break-all; color: #74788d; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') <a href="{{ route('anisenso-blog.index') }}">Technician's Blog</a> @endslot
        @slot('title') {{ $post->title }} @endslot
    @endcomponent

    <div class="bb-wrap">
        <div>
            <div class="card"><div class="card-body">
                <div class="d-flex flex-wrap justify-content-between align-items-start gap-2 mb-3">
                    <div>
                        <h4 class="card-title mb-1 text-dark">Build the article</h4>
                        <p class="text-secondary mb-0">
                            Add a piece, fill it in, drag to reorder. The preview beside this is the article as
                            anee.io will draw it — the same page and the same stylesheet, so what you see is what
                            a member gets.
                        </p>
                    </div>
                    <div class="d-flex gap-2">
                        <a href="{{ route('anisenso-blog.edit', ['id' => $post->id]) }}" class="btn btn-light btn-sm">
                            <i class="bx bx-cog"></i> Title, cover &amp; publishing
                        </a>
                        <button type="button" class="btn btn-primary btn-sm" id="bbSave">
                            <i class="bx bx-save me-1"></i> Save &amp; refresh
                        </button>
                    </div>
                </div>

                <div class="bb-add mb-3" id="bbAdd">
                    @foreach ($kinds as $key => $label)
                        <button type="button" data-kind="{{ $key }}">+ {{ $label }}</button>
                    @endforeach
                </div>

                <div id="bbBlocks"></div>
                <input type="file" id="bbFile" accept="image/*" multiple class="d-none">
            </div></div>
        </div>

        <div class="bb-preview">
            <div class="card"><div class="card-body">
                <div class="d-flex justify-content-between align-items-center mb-2">
                    <h6 class="text-dark mb-0"><i class="bx bx-devices me-1"></i>In anee.io</h6>
                    <button type="button" class="btn btn-light btn-sm" id="bbReload"><i class="bx bx-refresh"></i></button>
                </div>
                <iframe class="bb-frame" id="bbFrame" src="{{ $previewUrl }}" title="Preview"></iframe>
                <p class="bb-link mt-2 mb-1">
                    A private link — signed, and told not to be indexed. It shows drafts, so it works before you publish.
                </p>
                <div class="input-group input-group-sm">
                    <input type="text" class="form-control" id="bbUrl" value="{{ $previewUrl }}" readonly>
                    <button class="btn btn-outline-secondary" type="button" id="bbCopy"><i class="bx bx-copy"></i></button>
                    <a class="btn btn-outline-primary" href="{{ $previewUrl }}" target="_blank"><i class="bx bx-link-external"></i></a>
                </div>
            </div></div>
        </div>
    </div>
@endsection

@section('script')
<script>
const BB_TOKEN = '{{ csrf_token() }}';
const BB_ID = {{ $post->id }};
const BB_URLS = {
    blocks: '{{ url('/anisenso-blog-builder-blocks') }}?id=' + BB_ID,
    save:   '{{ url('/anisenso-blog-builder-save') }}?id=' + BB_ID,
    upload: '{{ url('/anisenso-blog-builder-upload') }}?id=' + BB_ID,
};
const BB_KINDS = @json($kinds);
let BLOCKS = [];
let uploadTarget = null;   // which block a chosen file belongs to

const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

/* Each kind draws its own few fields. Everything writes straight back into
   BLOCKS on input, so Save is only ever sending what is on screen. */
function fields(b, i) {
    const t = (k, label, ph) => `<label>${label}</label>
        <input type="text" class="form-control mb-2 bb-in" data-i="${i}" data-k="${k}" value="${esc(b[k] || '')}" placeholder="${esc(ph || '')}">`;
    const area = (k, label, rows, ph) => `<label>${label}</label>
        <textarea class="form-control mb-2 bb-in" data-i="${i}" data-k="${k}" rows="${rows}" placeholder="${esc(ph || '')}">${esc(b[k] || '')}</textarea>`;

    switch (b.type) {
        case 'heading':
            return `<div class="row g-2"><div class="col-9">${t('text', 'Heading', 'e.g. Week by week')}</div>
                <div class="col-3"><label>Size</label>
                <select class="form-select bb-in" data-i="${i}" data-k="level">
                    ${[2,3,4].map(l => `<option value="${l}" ${String(b.level||2)===String(l)?'selected':''}>H${l}</option>`).join('')}
                </select></div></div>`;
        case 'text':
            return area('html', 'Paragraphs — a blank line starts a new one', 6, 'Write the section…');
        case 'image':
            return `${b.url ? `<div class="bb-thumbs mb-2"><img src="${esc(b.url)}" alt=""></div>` : ''}
                <button type="button" class="btn btn-outline-primary btn-sm mb-2 bb-pick" data-i="${i}" data-multi="0">
                    <i class="bx bx-upload"></i> ${b.url ? 'Replace picture' : 'Choose a picture'}</button>
                ${t('caption', 'Caption', 'optional')}${t('alt', 'Describe it for a screen reader', 'optional')}`;
        case 'gallery':
            return `<div class="bb-thumbs mb-2">${(b.images || []).map(u => `<img src="${esc(u)}" alt="">`).join('') || '<span class="text-secondary small">No pictures yet.</span>'}</div>
                <button type="button" class="btn btn-outline-primary btn-sm bb-pick" data-i="${i}" data-multi="1">
                    <i class="bx bx-images"></i> Add pictures</button>
                ${(b.images || []).length ? `<button type="button" class="btn btn-light btn-sm ms-1 bb-clear" data-i="${i}">Clear</button>` : ''}`;
        case 'quote':
            return `${area('text', 'The quote', 3)}${t('label', 'Who said it', 'optional')}`;
        case 'list':
            return `<label>One item per line</label>
                <textarea class="form-control mb-2 bb-lines" data-i="${i}" data-k="items" rows="5">${esc((b.items || []).join('\n'))}</textarea>
                <div class="form-check"><input class="form-check-input bb-chk" type="checkbox" data-i="${i}" data-k="ordered" id="ord${i}" ${b.ordered ? 'checked' : ''}>
                <label class="form-check-label" for="ord${i}" style="font-size:12px;">Numbered</label></div>`;
        case 'note':
            return `<div class="row g-2"><div class="col-8">${t('title', 'Heading', 'e.g. Watch out')}</div>
                <div class="col-4"><label>Tone</label>
                <select class="form-select bb-in" data-i="${i}" data-k="kind">
                    <option value="tip" ${b.kind!=='warn'?'selected':''}>A note</option>
                    <option value="warn" ${b.kind==='warn'?'selected':''}>A warning</option>
                </select></div></div>${area('text', 'What it says', 3)}`;
        case 'button':
            return `${t('label', 'What it says', 'e.g. Read the full guide')}${t('url', 'Where it goes', 'https://…')}`;
        case 'embed':
            return `${t('url', 'Video link', 'a YouTube or Vimeo address')}
                <div class="form-text">Paste the normal watch link — it is turned into the embed the client app allows.</div>`;
        case 'divider':
            return '<p class="text-secondary small mb-0">A line across the page.</p>';
    }
    return '';
}

function draw() {
    if (!BLOCKS.length) {
        $('#bbBlocks').html('<div class="bb-empty"><i class="bx bx-layer"></i><div class="mt-1">Nothing in this article yet. Add a piece above.</div></div>');
        return;
    }
    $('#bbBlocks').html(BLOCKS.map((b, i) => `
        <div class="bb-block" draggable="true" data-i="${i}">
            <div class="bb-head">
                <i class="bx bx-menu bb-grip"></i>
                <span class="bb-kind">${esc(BB_KINDS[b.type] || b.type)}</span>
                <span class="ms-auto">
                    <button type="button" class="btn btn-sm btn-light bb-up" data-i="${i}" title="Up"><i class="bx bx-chevron-up"></i></button>
                    <button type="button" class="btn btn-sm btn-light bb-down" data-i="${i}" title="Down"><i class="bx bx-chevron-down"></i></button>
                    <button type="button" class="btn btn-sm btn-outline-danger bb-del" data-i="${i}" title="Remove"><i class="bx bx-trash"></i></button>
                </span>
            </div>
            <div class="bb-body">${fields(b, i)}</div>
        </div>`).join(''));
}

// ---- editing -----------------------------------------------------------
$(document).on('input change', '.bb-in', function () {
    BLOCKS[$(this).data('i')][$(this).data('k')] = $(this).val();
});
$(document).on('input', '.bb-lines', function () {
    BLOCKS[$(this).data('i')][$(this).data('k')] = ($(this).val() || '').split('\n').map(s => s.trim()).filter(Boolean);
});
$(document).on('change', '.bb-chk', function () {
    BLOCKS[$(this).data('i')][$(this).data('k')] = this.checked ? 1 : 0;
});
$(document).on('click', '.bb-del', function () {
    BLOCKS.splice($(this).data('i'), 1);
    draw();
});
function move(from, to) {
    if (to < 0 || to >= BLOCKS.length) return;
    BLOCKS.splice(to, 0, BLOCKS.splice(from, 1)[0]);
    draw();
}
$(document).on('click', '.bb-up', function () { move($(this).data('i'), $(this).data('i') - 1); });
$(document).on('click', '.bb-down', function () { move($(this).data('i'), $(this).data('i') + 1); });

$('#bbAdd button').on('click', function () {
    const kind = $(this).data('kind');
    const seed = { type: kind };
    if (kind === 'heading') seed.level = 2;
    if (kind === 'gallery') seed.images = [];
    if (kind === 'list') seed.items = [];
    BLOCKS.push(seed);
    draw();
    // The new piece is at the bottom, which is where the eye should go.
    const el = document.querySelector('#bbBlocks .bb-block:last-child');
    if (el) el.scrollIntoView({ behavior: 'smooth', block: 'center' });
});

// ---- dragging ----------------------------------------------------------
let dragFrom = null;
$(document).on('dragstart', '.bb-block', function (e) {
    dragFrom = $(this).data('i');
    $(this).addClass('is-drag');
    e.originalEvent.dataTransfer.effectAllowed = 'move';
});
$(document).on('dragend', '.bb-block', function () { $(this).removeClass('is-drag'); });
$(document).on('dragover', '.bb-block', function (e) { e.preventDefault(); });
$(document).on('drop', '.bb-block', function (e) {
    e.preventDefault();
    const to = $(this).data('i');
    if (dragFrom !== null && dragFrom !== to) move(dragFrom, to);
    dragFrom = null;
});

// ---- pictures ----------------------------------------------------------
$(document).on('click', '.bb-pick', function () {
    uploadTarget = { i: $(this).data('i'), multi: String($(this).data('multi')) === '1' };
    $('#bbFile').prop('multiple', uploadTarget.multi).val('').trigger('click');
});
$(document).on('click', '.bb-clear', function () {
    BLOCKS[$(this).data('i')].images = [];
    draw();
});
$('#bbFile').on('change', function () {
    const files = Array.from(this.files || []);
    if (!files.length || !uploadTarget) return;
    const target = uploadTarget;
    let left = files.length;
    files.forEach((f) => {
        const fd = new FormData();
        fd.append('_token', BB_TOKEN);
        fd.append('file', f);
        $.ajax({ url: BB_URLS.upload, type: 'POST', data: fd, processData: false, contentType: false })
            .done((res) => {
                if (!res.success) { toastr.error(res.message); return; }
                const b = BLOCKS[target.i];
                if (target.multi) { b.images = (b.images || []).concat([res.url]); }
                else { b.url = res.url; }
            })
            .fail((xhr) => toastr.error(xhr.responseJSON?.message || 'That picture did not upload.'))
            .always(() => { if (--left === 0) draw(); });
    });
});

// ---- saving ------------------------------------------------------------
function refreshPreview(url) {
    // A cache-buster, because the frame is the same address every time and a
    // browser is entitled to believe it has not changed.
    const f = document.getElementById('bbFrame');
    f.src = (url || $('#bbUrl').val()) + '&r=' + Date.now();
}
$('#bbReload').on('click', () => refreshPreview());

$('#bbSave').on('click', function () {
    const $b = $(this).prop('disabled', true).html('<i class="bx bx-loader-alt bx-spin"></i> Saving...');
    $.post(BB_URLS.save, { _token: BB_TOKEN, blocks: JSON.stringify(BLOCKS) })
        .done((res) => {
            if (!res.success) { toastr.error(res.message); return; }
            toastr.success(res.message);
            BLOCKS = res.blocks || BLOCKS;
            draw();
            refreshPreview(res.previewUrl);
        })
        .fail((xhr) => toastr.error(xhr.responseJSON?.message || 'That did not save.'))
        .always(() => $b.prop('disabled', false).html('<i class="bx bx-save me-1"></i> Save &amp; refresh'));
});

$('#bbCopy').on('click', function () {
    navigator.clipboard?.writeText($('#bbUrl').val());
    toastr.success('Link copied.');
});

// ---- open ---------------------------------------------------------------
$.get(BB_URLS.blocks, function (res) {
    BLOCKS = (res && res.blocks) || [];
    draw();
}).fail(() => { BLOCKS = []; draw(); toastr.error('Could not read the article.'); });
</script>
@endsection
