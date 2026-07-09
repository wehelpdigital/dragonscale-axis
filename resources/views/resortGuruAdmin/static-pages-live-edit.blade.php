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
    {{-- Navigation links are RELATIVE (route(..., [], false)) on purpose:
         the admin browses this mother app on a vhost, but APP_URL is
         http://localhost. An absolute route() would point at localhost —
         a different origin — so the SameSite=lax session cookie is not
         sent and the admin lands on the login screen ("logged out"). --}}
    <a href="{{ route('resort-guru-static.index', [], false) }}" class="btn-mini">
        <i class="bx bx-arrow-back"></i> Site Pages
    </a>
    <a href="{{ route('resort-guru-static.edit', ['id' => $page->id], false) }}" class="btn-mini">
        <i class="bx bx-edit-alt"></i> Classic editor
    </a>
    <div class="title">Live Editor (Static)</div>
    <div class="muted">
        {{ $page->title ?? '' }} &middot; <code>{{ $page->slug }}</code>
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
{{-- The edit-block iframe modal was removed: editing is now native and
     in-page via the embedded builder's #blockEditorModal (no iframe). --}}

{{-- Native in-page block editor. We embed the real builder (its canvas +
     toolbar hidden via CSS) purely to reuse its #blockEditorModal + media
     picker. Editing then happens IN this already-authenticated page using
     fetch — no edit iframe, so no second page-load and no re-login. --}}
<style>
    /* Hide the embedded builder's visible UI — keep only its modals,
       which are siblings of .rg-builder and still open normally. */
    .rg-builder { display: none !important; }
</style>
@include('resortGuruAdmin.blocks.builder', ['ownerType' => 'static_page', 'ownerId' => $page->id])

<script>
(function () {
    const STATIC_PAGE_ID = {{ (int) $page->id }};
    const CSRF = '{{ csrf_token() }}';
    // Relative (same-origin) URLs — never absolute. route() builds
    // absolute URLs from APP_URL, which differs from the host the admin
    // actually browses, making the edit iframe + fetches cross-origin so
    // the SameSite=lax session cookie is dropped (forcing a re-login).
    // Passing absolute=false keeps everything same-origin.
    const URLS = {
        reorder: '{{ route('resort-guru-blocks.reorder', [], false) }}',
        save: '{{ route('resort-guru-blocks.save', [], false) }}',
        delete: '{{ route('resort-guru-blocks.delete', [], false) }}',
        editSingle: '{{ route('resort-guru-blocks.edit-single', [], false) }}'
        // No metaEdit for static pages — title / meta_title /
        // meta_description live on the classic Edit screen, not as
        // SEO-page-style eyebrow/H1/subtitle/TLDR splits.
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
    // When the native in-page block editor (#blockEditorModal, from the
    // embedded builder) closes after Save/Cancel, refresh the preview so
    // changes appear immediately.
    (function () {
        const bem = document.getElementById('blockEditorModal');
        if (bem) bem.addEventListener('hidden.bs.modal', function () { reloadPreview(); });
    })();

    // Always send the current CSRF token from Laravel's refreshed
    // XSRF-TOKEN cookie (the page-baked CSRF goes stale after re-logins).
    function liveCsrf() {
        const m = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);
        return m ? decodeURIComponent(m[1]) : null;
    }
    function ajax(url, body) {
        const fd = new FormData();
        const xsrf = liveCsrf();
        if (!xsrf) fd.append('_token', CSRF); // fallback only if cookie missing
        Object.entries(body || {}).forEach(([k, v]) => {
            if (Array.isArray(v)) v.forEach(x => fd.append(k + '[]', x));
            else if (v !== null && typeof v === 'object') fd.append(k, JSON.stringify(v));
            else fd.append(k, v);
        });
        const headers = {'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json'};
        if (xsrf) headers['X-XSRF-TOKEN'] = xsrf;
        return fetch(url, {
            method: 'POST',
            credentials: 'same-origin',
            headers: headers,
            body: fd
        }).then(function (r) {
            return r.text().then(function (t) {
                try { return JSON.parse(t); }
                catch (e) { return { ok: false, message: 'HTTP ' + r.status + (r.status === 419 ? ' (session/CSRF expired — reload the page)' : '') }; }
            });
        });
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
            owner_type: 'static_page',
            owner_id: STATIC_PAGE_ID,
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
        // Native edit, no iframe: fetch this block from the same-origin
        // list endpoint (authenticated exactly like this page), then hand
        // the block object straight to the embedded builder's editor so
        // its #blockEditorModal opens in-page. No second page-load → no
        // re-login. Errors are surfaced so an auth/redirect is visible.
        if (!window.rgBuilderOpenEditorBlock) {
            alert('The editor has not finished loading yet. Please wait a moment and try again.');
            return;
        }
        const listUrl = '/resort-guru-blocks-list?owner_type=static_page&owner_id=' + STATIC_PAGE_ID;
        fetch(listUrl, { credentials: 'same-origin', headers: { 'Accept': 'application/json' } })
            .then(function (r) {
                if (!r.ok) throw new Error('HTTP ' + r.status + ' loading the block list');
                return r.json();
            })
            .then(function (d) {
                const b = (d.blocks || []).find(function (x) { return String(x.id) === String(blockId); });
                if (b) { window.rgBuilderOpenEditorBlock(b); }
                else { alert('Block #' + blockId + ' was not found on this page.'); }
            })
            .catch(function (e) {
                alert('Could not open the editor: ' + e.message + '.\n\nIf this mentions JSON / "Unexpected token <", the admin session was not accepted for the request — tell the developer.');
            });
    }

    function openMetaEditModal(field, fieldLabel) {
        // Static pages don't have field-level meta editing (the SEO
        // page splits H1 into eyebrow + heading, subtitle, TLDR+WWWW
        // — those are SEO-specific). For static pages the title /
        // meta_title / meta_description all live on the classic
        // Edit screen.
        window.location.href = '{{ route('resort-guru-static.edit', ['id' => $page->id], false) }}';
        return;
        // Unreachable below — kept so the rest of the handler doesn't
        // throw on undefined variables if a stray edit-page-meta
        // postMessage somehow fires.
        const url = '';
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

        // Focused block editor closed (Save or Cancel) inside the modal
        // iframe → close the OUTER modal, whose hidden handler reloads the
        // preview, so the admin returns to the live page instead of being
        // dropped on the bare builder canvas behind the form.
        if (d && d.rgLiveEditBlockClose) {
            const modalEl = document.getElementById('rg-edit-block-modal');
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
