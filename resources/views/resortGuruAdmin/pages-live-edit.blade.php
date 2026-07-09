@extends('layouts.master')

@section('title') Live Editor: {{ $keyword->phrase ?? $page->title }} @endsection

@section('content')
{{-- Live Editor: full-bleed iframe of the published frontend page
     with admin chrome injected by rg-live-edit.js. Listens for
     postMessage events from the iframe and dispatches AJAX calls
     to the existing block CRUD endpoints. --}}

<style>
    #rg-live-toolbar {
        position: sticky;
        top: 0;
        z-index: 1050;
        background: #1e293b;
        color: #fff;
        padding: 8px 14px;
        display: flex;
        align-items: center;
        gap: 12px;
        margin: -1.5rem -1.5rem 1rem -1.5rem;
        flex-wrap: wrap;
    }
    #rg-live-toolbar .title { font-weight: 700; }
    #rg-live-toolbar .muted { color: #94a3b8; font-size: 12px; }
    #rg-live-toolbar .btn-mini {
        background: #334155;
        color: #fff;
        border: 1px solid #475569;
        border-radius: 4px;
        padding: 4px 10px;
        font-size: 12px;
        text-decoration: none;
    }
    #rg-live-toolbar .btn-mini:hover { background: #475569; color: #fff; }
    #rg-live-iframe-wrap {
        position: relative;
        margin: -1rem -1.5rem -1.5rem -1.5rem;
        background: #f1f5f9;
    }
    #rg-live-iframe {
        width: 100%;
        height: calc(100vh - 130px);
        border: 0;
        background: #fff;
        display: block;
    }
    #rg-live-spinner {
        position: absolute;
        top: 50%; left: 50%;
        transform: translate(-50%, -50%);
        background: rgba(255,255,255,0.9);
        padding: 24px 32px;
        border-radius: 12px;
        font-weight: 600;
        z-index: 5;
        display: none;
    }
    #rg-live-spinner.show { display: block; }

    .block-type-tile {
        cursor: pointer;
        padding: 14px 12px;
        border: 2px solid #e2e8f0;
        border-radius: 10px;
        text-align: center;
        background: #fff;
        transition: border-color .15s, background .15s, transform .1s;
    }
    .block-type-tile:hover {
        border-color: #6366f1;
        background: #eef2ff;
    }
    .block-type-tile:active { transform: translateY(1px); }
    .block-type-tile .name {
        font-weight: 700;
        font-size: 13px;
        text-transform: capitalize;
    }
    .block-type-tile .hint { font-size: 11px; color: #64748b; }
</style>

<div id="rg-live-toolbar">
    {{-- Relative (same-origin) so it never bounces the admin to login via
         the APP_URL/vhost origin mismatch. --}}
    <a href="{{ route('resort-guru-pages.edit', ['id' => $page->id], false) }}" class="btn-mini">
        <i class="bx bx-arrow-back"></i> Classic editor
    </a>
    <div class="title">Live Editor</div>
    <div class="muted">
        {{ $keyword->phrase ?? '' }} &middot; <code>{{ $page->slug }}</code>
    </div>
    <div class="ms-auto d-flex gap-2">
        <button type="button" id="rg-live-reload" class="btn-mini">
            <i class="bx bx-refresh"></i> Reload preview
        </button>
        <a href="{{ \App\Support\RgFrontend::urlFor($page->slug) }}" target="_blank" class="btn-mini">
            <i class="bx bx-link-external"></i> View public
        </a>
    </div>
</div>

<div id="rg-live-iframe-wrap">
    <div id="rg-live-spinner">Reloading preview…</div>
    <iframe id="rg-live-iframe" src="{{ $previewUrl }}"></iframe>
</div>

{{-- Add block modal --}}
<div class="modal fade" id="rg-add-block-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add a block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p class="text-muted small mb-3">New blocks append to the end of the page. After saving, drag them into the position you want using the type badge as a handle.</p>
                <div class="row g-2" id="rg-add-tiles">
                    {{-- populated by JS --}}
                </div>
            </div>
        </div>
    </div>
</div>

{{-- Edit page-metadata modal — iframe pointing at the focused
     pages-meta-edit-single view, scoped to one field (H1, eyebrow,
     subtitle, WWWW). On save the inner view posts a
     rgLiveEditMetaSaved message and we close + reload the preview. --}}
<div class="modal fade" id="rg-edit-meta-modal" tabindex="-1">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit page metadata</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="rg-edit-meta-iframe" src="about:blank"
                        style="width: 100%; height: 70vh; border: 0;"></iframe>
            </div>
            <div class="modal-footer">
                <small class="text-muted me-auto">Click Save inside the editor — the preview reloads automatically.</small>
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

{{-- Edit block modal — iframe pointing at the classic editor, scrolled
     to the block being edited. Saves there propagate to the DB; on
     close, the live-edit iframe reloads to reflect the change. --}}
<div class="modal fade" id="rg-edit-block-modal" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Edit block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-0">
                <iframe id="rg-edit-block-iframe" src="about:blank"
                        style="width: 100%; height: 70vh; border: 0;"></iframe>
            </div>
            <div class="modal-footer">
                <small class="text-muted me-auto">Save inside the editor, then close this dialog. The preview will reload automatically.</small>
                <button type="button" class="btn btn-primary" data-bs-dismiss="modal">Done editing</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const PAGE_ID = {{ (int) $page->id }};
    const CSRF = '{{ csrf_token() }}';
    // RELATIVE (same-origin) URLs — an absolute route() points at APP_URL
    // (http://localhost), a different origin from the vhost the admin
    // actually browses, so the SameSite=lax session cookie is dropped and
    // requests 419 / bounce to login. absolute=false keeps them same-origin.
    const URLS = {
        reorder: '{{ route('resort-guru-blocks.reorder', [], false) }}',
        save: '{{ route('resort-guru-blocks.save', [], false) }}',
        delete: '{{ route('resort-guru-blocks.delete', [], false) }}',
        editSingle: '{{ route('resort-guru-blocks.edit-single', [], false) }}',
        metaEdit: '{{ route('resort-guru-pages.meta-edit', [], false) }}'
    };

    // Block types the admin can drop in. Each ships with a minimal
    // default payload so the new block has something visible to anchor
    // its existence; the admin opens it to fill in real content.
    const BLOCK_TYPES = [
        ['section_header', 'Section Header', 'Bold H2 anchor', {heading: 'New section'}],
        ['text_section',   'Text Section',   'Heading + body paragraphs', {heading: 'New text section', body: 'Write here.'}],
        ['rich_text',      'Rich Text',      'Free HTML block', {html: '<p>New content.</p>'}],
        ['heading',        'Heading',        'Single H2 / H3', {text: 'New heading', level: 'h2'}],
        ['image',          'Image',          'Single image with caption', {src: '', alt: '', caption: ''}],
        ['gallery',        'Gallery',        'Image grid', {items: []}],
        ['hero_slider',    'Hero Slider',    'Carousel of full-width images', {slides: []}],
        ['quick_facts',    'Quick Facts',    'Compact KPI tiles', {items: []}],
        ['facts_list',     'Facts List',     'Bullet of labelled facts', {items: []}],
        ['attractions',    'Attractions',    'Tourist spot cards', {items: []}],
        ['how_to_get_to',  'How to Get To',  'Transport options block', {items: []}],
        ['foods_to_try',   'Foods to Try',   'Dish cards', {heading: 'Foods to try', items: []}],
        ['place_history',  'Place History',  'Origin story card', {heading: 'A short history', body: ''}],
        ['local_tip',      'Local Tip',      'Single highlighted tip', {text: 'Local tip goes here.'}],
        ['pros_cons',      'Pros / Cons',    'Best for / Skip if', {best_for: [], skip_if: []}],
        ['faq',            'FAQ',            'Q&A accordion', {items: []}],
        ['tag_pills',      'Tag Pills',      'Hashtag chips', {tags: []}],
        ['data_table',     'Data Table',     'Comparison table', {rows: []}],
        ['cta',            'Call to Action', 'Big bold CTA', {headline: 'Take action', text: '', button_text: 'Continue', button_url: '#'}],
        ['author',         'Author',         'Byline card', {author_id: null}],
        ['external_guides','External Guides','Outbound link list', {items: []}],
        ['map_embed',      'Map Embed',      'Embedded Google Map', {embed_url: ''}],
        ['related_blogs',  'Related Blogs',  'Blog post cards', {items: []}],
        ['listing_slot',   'Listing Slot',   'Paid resort listings band', {}],
        ['custom_html',    'Custom HTML',    'Raw HTML escape hatch', {html: ''}],
    ];

    // Populate the add-block modal tiles once.
    (function buildTiles() {
        const wrap = document.getElementById('rg-add-tiles');
        wrap.innerHTML = BLOCK_TYPES.map(t => (
            `<div class="col-sm-6 col-md-4">
                <div class="block-type-tile" data-type="${t[0]}" data-payload='${JSON.stringify(t[3])}'>
                    <div class="name">${t[1]}</div>
                    <div class="hint">${t[2]}</div>
                </div>
            </div>`
        )).join('');
        wrap.addEventListener('click', e => {
            const tile = e.target.closest('.block-type-tile');
            if (!tile) return;
            addBlock(tile.dataset.type, JSON.parse(tile.dataset.payload));
        });
    })();

    const iframe = document.getElementById('rg-live-iframe');
    const spinner = document.getElementById('rg-live-spinner');
    let pendingAfterBlockId = null;

    function reloadPreview() {
        spinner.classList.add('show');
        iframe.src = iframe.src;
    }
    iframe.addEventListener('load', () => spinner.classList.remove('show'));
    document.getElementById('rg-live-reload').addEventListener('click', reloadPreview);

    function ajax(url, body) {
        const fd = new FormData();
        fd.append('_token', CSRF);
        Object.entries(body || {}).forEach(([k, v]) => {
            if (Array.isArray(v)) v.forEach(x => fd.append(k + '[]', x));
            else if (v !== null && typeof v === 'object') fd.append(k, JSON.stringify(v));
            else fd.append(k, v);
        });
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'},
            body: fd
        }).then(r => r.json());
    }

    function reorder(order) {
        ajax(URLS.reorder, {ids: order}).then(res => {
            if (res && res.ok) reloadPreview();
            else alert('Reorder failed: ' + (res && res.message ? res.message : 'unknown'));
        });
    }

    function deleteBlock(blockId) {
        if (!confirm('Delete this block? This cannot be undone.')) return;
        ajax(URLS.delete, {id: blockId}).then(res => {
            if (res && res.ok) reloadPreview();
            else alert('Delete failed: ' + (res && res.message ? res.message : 'unknown'));
        });
    }

    function openAddPicker(afterBlockId) {
        pendingAfterBlockId = afterBlockId || null;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('rg-add-block-modal'));
        modal.show();
    }

    function addBlock(blockType, payload) {
        ajax(URLS.save, {
            owner_type: 'seo_page',
            owner_id: PAGE_ID,
            block_type: blockType,
            payload: payload
        }).then(res => {
            bootstrap.Modal.getOrCreateInstance(document.getElementById('rg-add-block-modal')).hide();
            if (res && res.ok) reloadPreview();
            else alert('Add failed: ' + (res && res.message ? res.message : 'unknown'));
        });
    }

    function moveOne(blockId, dir) {
        // We rebuild the order from the iframe's current block sequence
        // and swap the target with its neighbor. The iframe's DOM is
        // the source of truth for current order — we read it via
        // postMessage by asking the iframe to re-emit "ready" with the
        // ordered list. For v1, simpler: post a synthetic reorder
        // built from the iframe's current visible blocks.
        try {
            const doc = iframe.contentDocument;
            const ids = Array.from(doc.querySelectorAll('.rg-live-block'))
                .map(b => parseInt(b.dataset.rgBlockId, 10));
            const i = ids.indexOf(blockId);
            if (i < 0) return;
            const j = dir === 'up' ? i - 1 : i + 1;
            if (j < 0 || j >= ids.length) return;
            [ids[i], ids[j]] = [ids[j], ids[i]];
            reorder(ids);
        } catch (e) {
            console.warn('moveOne failed', e);
        }
    }

    function openEditModal(blockId) {
        // Focused single-block editor — loads RgBlocksController@editSingle,
        // which renders the block builder partial filtered to this one
        // block (no add-toolbar, no other blocks). The minimal layout
        // strips the sidebar / breadcrumb so it feels like a focused
        // edit panel, not a whole admin page.
        const url = URLS.editSingle + '?id=' + blockId;
        document.getElementById('rg-edit-block-iframe').src = url;
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('rg-edit-block-modal'));
        document.getElementById('rg-edit-block-modal').addEventListener('hidden.bs.modal', reloadPreview, {once: true});
        modal.show();
    }

    function openMetaEditModal(field, fieldLabel) {
        // Focused single-field metadata editor (H1, eyebrow, subtitle,
        // tldr+WWWW). Loads RgSeoPagesController@editMetaSingle which
        // renders the relevant form field + Save button. On save the
        // inner view posts rgLiveEditMetaSaved → we close + reload here.
        const url = URLS.metaEdit + '?id=' + PAGE_ID + '&field=' + encodeURIComponent(field);
        document.getElementById('rg-edit-meta-iframe').src = url;
        const modalEl = document.getElementById('rg-edit-meta-modal');
        const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
        modalEl.addEventListener('hidden.bs.modal', reloadPreview, {once: true});
        modal.show();
    }

    window.addEventListener('message', (e) => {
        const d = e.data;
        // Save-confirmation from the inner meta-edit iframe — close
        // the modal and the modal's hidden.bs.modal listener fires
        // reloadPreview() so the live preview iframe shows the new
        // H1 / eyebrow / subtitle / WWWW immediately.
        if (d && d.rgLiveEditMetaSaved) {
            const modalEl = document.getElementById('rg-edit-meta-modal');
            const inst = bootstrap.Modal.getInstance(modalEl);
            if (inst) inst.hide();
            return;
        }
        if (d && d.rgLiveEditMetaClose) {
            const modalEl = document.getElementById('rg-edit-meta-modal');
            const inst = bootstrap.Modal.getInstance(modalEl);
            if (inst) inst.hide();
            return;
        }

        if (!d || !d.rgLiveEdit) return;
        switch (d.action) {
            case 'ready':
                console.log('Live-edit iframe ready, blocks:', d.blockCount, 'meta:', d.metaCount);
                break;
            case 'edit':
                openEditModal(d.blockId);
                break;
            case 'edit-page-meta':
                openMetaEditModal(d.field, d.fieldLabel);
                break;
            case 'delete':
                deleteBlock(d.blockId);
                break;
            case 'reorder':
                reorder(d.order);
                break;
            case 'move-up':
                moveOne(d.blockId, 'up');
                break;
            case 'move-down':
                moveOne(d.blockId, 'down');
                break;
            case 'add':
                openAddPicker(d.afterBlockId);
                break;
        }
    });
})();
</script>
@endsection
