{{-- Drag-drop content block builder.
     Required variables: $ownerType, $ownerId
     Optional: $allowed (array of block types) --}}
@php
    $allowed = $allowed ?? ['heading','rich_text','image','gallery','video','faq','cta','two_column','listing_slot','quote','divider','custom_html'];
    $labels = [
        'heading'=>'Heading','rich_text'=>'Rich Text','image'=>'Image','gallery'=>'Gallery',
        'video'=>'Video','faq'=>'FAQ','cta'=>'Call to Action','two_column'=>'Two Columns',
        'listing_slot'=>'Listing Slot','quote'=>'Quote','divider'=>'Divider','custom_html'=>'Custom HTML',
    ];
    $icons = [
        'heading'=>'bx-heading','rich_text'=>'bx-text','image'=>'bx-image','gallery'=>'bx-images',
        'video'=>'bx-video','faq'=>'bx-help-circle','cta'=>'bx-mouse-alt','two_column'=>'bx-columns',
        'listing_slot'=>'bx-trophy','quote'=>'bx-quote-alt-left','divider'=>'bx-minus','custom_html'=>'bx-code-alt',
    ];
@endphp

<link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.snow.css">
<style>
    .rg-builder { background: #f8f9fc; padding: 16px; border-radius: 6px; }
    .rg-builder-toolbar { display: flex; flex-wrap: wrap; gap: 6px; padding: 12px; background: white; border-radius: 6px; margin-bottom: 14px; box-shadow: 0 1px 2px rgba(0,0,0,.04); }
    .rg-builder-toolbar .btn-add { display: inline-flex; align-items: center; gap: 4px; padding: 6px 10px; background: #eef2ff; border: 1px solid #c7d2fe; color: #4338ca; border-radius: 4px; font-size: 12px; cursor: pointer; transition: all .15s; }
    .rg-builder-toolbar .btn-add:hover { background: #c7d2fe; }
    .rg-builder-canvas { min-height: 200px; }
    .rg-builder-canvas.empty { display: flex; align-items: center; justify-content: center; min-height: 220px; background: white; border: 2px dashed #d1d5db; border-radius: 6px; color: #9ca3af; flex-direction: column; gap: 6px; }
    .rg-block { background: white; border-radius: 6px; box-shadow: 0 1px 2px rgba(0,0,0,.05); margin-bottom: 10px; display: flex; align-items: stretch; overflow: hidden; }
    .rg-block-handle { background: #f3f4f6; padding: 18px 8px; cursor: grab; color: #9ca3af; display: flex; align-items: center; user-select: none; }
    .rg-block-handle:hover { background: #e5e7eb; color: #6b7280; }
    .rg-block-preview { flex: 1; padding: 14px 18px; min-width: 0; }
    .rg-block-preview .rg-block-typelabel { display: inline-block; font-size: 10px; text-transform: uppercase; letter-spacing: .05em; color: #6366f1; background: #eef2ff; padding: 2px 6px; border-radius: 3px; margin-bottom: 6px; }
    .rg-block-preview .rg-preview-body { color: #374151; font-size: 13px; line-height: 1.5; max-height: 120px; overflow: hidden; }
    .rg-block-preview .rg-preview-body img { max-width: 100px; max-height: 80px; border-radius: 3px; }
    .rg-block-actions { display: flex; flex-direction: column; gap: 4px; padding: 14px 10px; border-left: 1px solid #f3f4f6; }
    .rg-block-actions button { width: 30px; height: 30px; border: none; background: #f3f4f6; border-radius: 4px; cursor: pointer; color: #6b7280; }
    .rg-block-actions button:hover { background: #e5e7eb; color: #1f2937; }
    .rg-block-actions button.delete:hover { background: #fee2e2; color: #b91c1c; }
    .rg-block.sortable-ghost { opacity: .4; }
    .rg-block.sortable-chosen { box-shadow: 0 4px 12px rgba(99,102,241,.25); }
    #blockEditorBody .ql-editor { min-height: 200px; font-size: 14px; }
    #blockEditorBody label { font-weight: 600; font-size: 13px; margin-bottom: 4px; }
    #blockEditorBody .form-help { font-size: 11px; color: #6b7280; margin-top: -4px; margin-bottom: 8px; }
    .rg-faq-row, .rg-gallery-row { display: flex; gap: 8px; margin-bottom: 6px; }
    .rg-faq-row input, .rg-faq-row textarea { flex: 1; }
    .rg-image-preview { display: block; max-width: 200px; max-height: 140px; margin-top: 6px; border-radius: 4px; }
</style>

<div class="rg-builder" data-owner-type="{{ $ownerType }}" data-owner-id="{{ $ownerId }}">
    <div class="rg-builder-toolbar">
        <strong style="margin-right: 8px; font-size: 13px; align-self: center;">Add block:</strong>
        @foreach($allowed as $type)
            <button type="button" class="btn-add" data-add-type="{{ $type }}">
                <i class="bx {{ $icons[$type] ?? 'bx-plus' }}"></i> {{ $labels[$type] ?? ucfirst($type) }}
            </button>
        @endforeach
    </div>
    <div id="rgBlocksCanvas" class="rg-builder-canvas empty">
        <i class="bx bx-loader bx-spin" style="font-size: 28px;"></i>
        <div>Loading blocks...</div>
    </div>
</div>

{{-- Block editor modal --}}
<div class="modal fade" id="blockEditorModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="blockEditorTitle">Edit Block</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="blockEditorBody"></div>
            <div class="modal-footer">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-primary" id="blockEditorSave"><i class="bx bx-save me-1"></i>Save Block</button>
            </div>
        </div>
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/quill@1.3.7/dist/quill.min.js"></script>
<script>
(function () {
    const cfg = {
        ownerType: @json($ownerType),
        ownerId: @json($ownerId),
        csrf: @json(csrf_token()),
        listingsAllowed: @json(in_array('listing_slot', $allowed, true)),
        urls: {
            list: '/resort-guru-blocks-list',
            save: '/resort-guru-blocks-save',
            delete: '/resort-guru-blocks-delete',
            reorder: '/resort-guru-blocks-reorder',
            upload: '/resort-guru-blocks-upload-media',
        },
    };

    const state = { blocks: [], current: null, quill: null };
    const escapeHtml = (s) => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    const labels = @json($labels);

    function defaultPayload(type) {
        const defaults = {
            heading: { level: 'h2', text: 'New heading' },
            rich_text: { html: '<p>Write your content here...</p>' },
            image: { src: '', alt: '', caption: '', align: 'center' },
            gallery: { images: [], columns: 3 },
            video: { src: '', youtube_id: '', caption: '' },
            faq: { items: [{ question: '', answer: '' }] },
            cta: { headline: '', text: '', button_text: 'Click here', button_url: '#', style: 'primary' },
            two_column: { left_html: '<p>Left column</p>', right_html: '<p>Right column</p>' },
            listing_slot: { slot_label: 'Featured Resorts', fallback_html: '<p>We are still finalizing partners for this destination. <a href="/register">Sign up</a> to list here.</p>' },
            quote: { text: 'A great quote.', author: '' },
            divider: { style: 'line' },
            custom_html: { html: '<!-- custom html -->' },
        };
        return defaults[type] || {};
    }

    function previewHtml(block) {
        const p = block.payload || {};
        switch (block.type) {
            case 'heading':
                return `<${p.level || 'h2'} style="margin:0;font-size:${p.level==='h3'?'18px':'22px'};">${escapeHtml(p.text)}</${p.level||'h2'}>`;
            case 'rich_text':
                return p.html ? p.html : '<em>Empty rich text</em>';
            case 'image':
                return p.src ? `<img src="${p.src}" alt="${escapeHtml(p.alt)}">${p.caption ? '<div style="font-size:11px;color:#6b7280">'+escapeHtml(p.caption)+'</div>' : ''}` : '<em>No image yet</em>';
            case 'gallery':
                return (p.images && p.images.length) ? p.images.slice(0,4).map(i => `<img src="${i.src}" alt="" style="display:inline-block;margin-right:4px">`).join('') + (p.images.length>4?` <em>+${p.images.length-4} more</em>`:'') : '<em>No images yet</em>';
            case 'video':
                return p.youtube_id ? `<em>YouTube video: ${escapeHtml(p.youtube_id)}</em>` : (p.src ? `<em>Video file: ${escapeHtml(p.src)}</em>` : '<em>No video yet</em>');
            case 'faq':
                return (p.items||[]).map(f => `<strong>${escapeHtml(f.question)}</strong><div style="font-size:12px;color:#6b7280">${escapeHtml(f.answer).slice(0,120)}</div>`).join('<br>') || '<em>No FAQs yet</em>';
            case 'cta':
                return `<strong>${escapeHtml(p.headline||'')}</strong><div style="font-size:12px;color:#6b7280">${escapeHtml(p.text||'')}</div><span style="display:inline-block;margin-top:6px;padding:4px 10px;background:#6366f1;color:white;border-radius:3px;font-size:11px">${escapeHtml(p.button_text)}</span>`;
            case 'two_column':
                return `<div style="display:grid;grid-template-columns:1fr 1fr;gap:8px;font-size:12px"><div>${p.left_html||'<em>Empty</em>'}</div><div>${p.right_html||'<em>Empty</em>'}</div></div>`;
            case 'listing_slot':
                return `<strong>Listing slot: ${escapeHtml(p.slot_label||'Featured')}</strong><div style="font-size:11px;color:#6b7280;margin-top:4px">Fallback HTML if no bids: ${escapeHtml((p.fallback_html||'').slice(0,80))}...</div>`;
            case 'quote':
                return `<em>"${escapeHtml(p.text)}"</em>${p.author ? ' &mdash; '+escapeHtml(p.author):''}`;
            case 'divider':
                return `<hr style="margin:6px 0">`;
            case 'custom_html':
                return `<code style="font-size:11px;color:#6b7280">${escapeHtml((p.html||'').slice(0,100))}...</code>`;
            default:
                return `<em>Unknown block type: ${escapeHtml(block.type)}</em>`;
        }
    }

    function blockEl(block) {
        const div = document.createElement('div');
        div.className = 'rg-block';
        div.dataset.id = block.id;
        div.innerHTML = `
            <div class="rg-block-handle" title="Drag to reorder"><i class="bx bx-grid-vertical"></i></div>
            <div class="rg-block-preview">
                <span class="rg-block-typelabel">${escapeHtml(labels[block.type] || block.type)}</span>
                <div class="rg-preview-body">${previewHtml(block)}</div>
            </div>
            <div class="rg-block-actions">
                <button type="button" class="edit" title="Edit"><i class="bx bx-edit"></i></button>
                <button type="button" class="delete" title="Delete"><i class="bx bx-trash"></i></button>
            </div>
        `;
        div.querySelector('.edit').addEventListener('click', () => openEditor(block));
        div.querySelector('.delete').addEventListener('click', () => deleteBlock(block));
        return div;
    }

    function render() {
        const canvas = document.getElementById('rgBlocksCanvas');
        if (!state.blocks.length) {
            canvas.classList.add('empty');
            canvas.innerHTML = '<i class="bx bx-layer" style="font-size:36px;color:#cbd5e1"></i><div>No blocks yet. Use the toolbar above to add one.</div>';
            return;
        }
        canvas.classList.remove('empty');
        canvas.innerHTML = '';
        state.blocks.forEach(b => canvas.appendChild(blockEl(b)));
    }

    function fetchBlocks() {
        const url = `${cfg.urls.list}?owner_type=${encodeURIComponent(cfg.ownerType)}&owner_id=${encodeURIComponent(cfg.ownerId)}`;
        fetch(url, { credentials: 'same-origin' })
            .then(r => r.json())
            .then(d => { state.blocks = d.blocks || []; render(); initSortable(); });
    }

    function initSortable() {
        const canvas = document.getElementById('rgBlocksCanvas');
        if (canvas._sortable || canvas.classList.contains('empty')) return;
        canvas._sortable = new Sortable(canvas, {
            handle: '.rg-block-handle',
            animation: 150,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            onEnd: () => {
                const ids = Array.from(canvas.querySelectorAll('.rg-block')).map(el => el.dataset.id);
                fetch(cfg.urls.reorder, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
                    credentials: 'same-origin',
                    body: JSON.stringify({ ids }),
                });
                state.blocks.sort((a,b) => ids.indexOf(String(a.id)) - ids.indexOf(String(b.id)));
            },
        });
    }

    function deleteBlock(block) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({ title: 'Delete this block?', icon: 'warning', showCancelButton: true, confirmButtonText: 'Delete', confirmButtonColor: '#dc2626' })
                .then(r => { if (r.isConfirmed) doDelete(block); });
        } else if (confirm('Delete this block?')) {
            doDelete(block);
        }
    }
    function doDelete(block) {
        fetch(cfg.urls.delete, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
            credentials: 'same-origin',
            body: JSON.stringify({ id: block.id }),
        }).then(r => r.json()).then(() => {
            state.blocks = state.blocks.filter(b => b.id !== block.id);
            render();
            if (typeof toastr !== 'undefined') toastr.success('Block deleted');
        });
    }

    function editorForm(block) {
        const p = block.payload || {};
        switch (block.type) {
            case 'heading':
                return `
                    <div class="mb-3"><label>Level</label>
                        <select class="form-select" data-field="level">
                            <option value="h2" ${p.level==='h2'?'selected':''}>H2 (section heading)</option>
                            <option value="h3" ${p.level==='h3'?'selected':''}>H3 (sub heading)</option>
                        </select>
                    </div>
                    <div class="mb-3"><label>Text</label>
                        <input type="text" class="form-control" data-field="text" value="${escapeHtml(p.text)}">
                    </div>`;
            case 'rich_text':
                return `<div class="mb-2"><label>Content</label>
                    <div id="rgQuillEditor" style="background:white">${p.html || ''}</div>
                </div>`;
            case 'image':
                return `
                    <div class="mb-3"><label>Image</label>
                        <input type="file" class="form-control" accept="image/*" data-upload-target="src">
                        <input type="hidden" data-field="src" value="${escapeHtml(p.src||'')}">
                        ${p.src ? `<img src="${p.src}" class="rg-image-preview">` : ''}
                    </div>
                    <div class="mb-3"><label>Alt text (for accessibility + SEO)</label>
                        <input type="text" class="form-control" data-field="alt" value="${escapeHtml(p.alt||'')}">
                    </div>
                    <div class="mb-3"><label>Caption (optional)</label>
                        <input type="text" class="form-control" data-field="caption" value="${escapeHtml(p.caption||'')}">
                    </div>
                    <div class="mb-3"><label>Alignment</label>
                        <select class="form-select" data-field="align">
                            <option value="left" ${p.align==='left'?'selected':''}>Left</option>
                            <option value="center" ${p.align==='center'?'selected':''}>Center</option>
                            <option value="right" ${p.align==='right'?'selected':''}>Right</option>
                        </select>
                    </div>`;
            case 'gallery':
                return `
                    <div class="mb-3"><label>Add images (multiple)</label>
                        <input type="file" class="form-control" accept="image/*" multiple data-upload-target="gallery">
                    </div>
                    <div class="mb-3"><label>Columns</label>
                        <select class="form-select" data-field="columns">
                            <option value="2" ${p.columns==2?'selected':''}>2</option>
                            <option value="3" ${p.columns==3?'selected':''}>3</option>
                            <option value="4" ${p.columns==4?'selected':''}>4</option>
                        </select>
                    </div>
                    <div id="rgGalleryList">${(p.images||[]).map((i, idx) => `<div class="rg-gallery-row" data-idx="${idx}"><img src="${i.src}" style="width:80px;height:60px;object-fit:cover;border-radius:3px"><input type="text" class="form-control form-control-sm" placeholder="Alt text" data-gallery-alt value="${escapeHtml(i.alt||'')}"><button type="button" class="btn btn-sm btn-outline-danger" data-gallery-remove>&times;</button></div>`).join('')}</div>
                    <input type="hidden" data-field="images" value='${JSON.stringify(p.images||[])}'>`;
            case 'video':
                return `
                    <div class="mb-3"><label>YouTube video ID</label>
                        <input type="text" class="form-control" data-field="youtube_id" value="${escapeHtml(p.youtube_id||'')}" placeholder="e.g. dQw4w9WgXcQ">
                        <div class="form-help">From a URL like https://www.youtube.com/watch?v=<strong>dQw4w9WgXcQ</strong></div>
                    </div>
                    <div class="mb-3"><label>OR upload a video file</label>
                        <input type="file" class="form-control" accept="video/*" data-upload-target="src">
                        <input type="hidden" data-field="src" value="${escapeHtml(p.src||'')}">
                        ${p.src ? `<div class="mt-2"><small>Current: ${escapeHtml(p.src)}</small></div>` : ''}
                    </div>
                    <div class="mb-3"><label>Caption (optional)</label>
                        <input type="text" class="form-control" data-field="caption" value="${escapeHtml(p.caption||'')}">
                    </div>`;
            case 'faq':
                return `
                    <div id="rgFaqList">${(p.items||[]).map((f, idx) => `<div class="rg-faq-row" data-idx="${idx}"><input type="text" class="form-control form-control-sm" placeholder="Question" data-faq-q value="${escapeHtml(f.question||'')}"><input type="text" class="form-control form-control-sm" placeholder="Answer" data-faq-a value="${escapeHtml(f.answer||'')}"><button type="button" class="btn btn-sm btn-outline-danger" data-faq-remove>&times;</button></div>`).join('')}</div>
                    <button type="button" class="btn btn-sm btn-outline-primary mt-2" id="rgFaqAdd"><i class="bx bx-plus"></i> Add FAQ</button>
                    <input type="hidden" data-field="items" value='${JSON.stringify(p.items||[])}'>`;
            case 'cta':
                return `
                    <div class="mb-3"><label>Headline</label>
                        <input type="text" class="form-control" data-field="headline" value="${escapeHtml(p.headline||'')}">
                    </div>
                    <div class="mb-3"><label>Body text</label>
                        <textarea class="form-control" rows="2" data-field="text">${escapeHtml(p.text||'')}</textarea>
                    </div>
                    <div class="row">
                        <div class="col-6 mb-3"><label>Button text</label>
                            <input type="text" class="form-control" data-field="button_text" value="${escapeHtml(p.button_text||'')}">
                        </div>
                        <div class="col-6 mb-3"><label>Button URL</label>
                            <input type="text" class="form-control" data-field="button_url" value="${escapeHtml(p.button_url||'')}">
                        </div>
                    </div>
                    <div class="mb-3"><label>Style</label>
                        <select class="form-select" data-field="style">
                            <option value="primary" ${p.style==='primary'?'selected':''}>Primary</option>
                            <option value="secondary" ${p.style==='secondary'?'selected':''}>Secondary</option>
                            <option value="outline" ${p.style==='outline'?'selected':''}>Outline</option>
                        </select>
                    </div>`;
            case 'two_column':
                return `
                    <div class="mb-3"><label>Left column</label>
                        <div id="rgQuillLeft" style="background:white">${p.left_html || ''}</div>
                        <input type="hidden" data-field="left_html">
                    </div>
                    <div class="mb-3"><label>Right column</label>
                        <div id="rgQuillRight" style="background:white">${p.right_html || ''}</div>
                        <input type="hidden" data-field="right_html">
                    </div>`;
            case 'listing_slot':
                return `
                    <div class="alert alert-info"><i class="bx bx-info-circle me-1"></i>This block renders paid resort listings sorted by bid. Owners bid GP to climb here.</div>
                    <div class="mb-3"><label>Section label (shown above listings)</label>
                        <input type="text" class="form-control" data-field="slot_label" value="${escapeHtml(p.slot_label||'Featured Resorts')}">
                    </div>
                    <div class="mb-3"><label>Fallback HTML (shown when no paid listings exist)</label>
                        <textarea class="form-control" rows="4" data-field="fallback_html">${escapeHtml(p.fallback_html||'')}</textarea>
                    </div>`;
            case 'quote':
                return `
                    <div class="mb-3"><label>Quote</label>
                        <textarea class="form-control" rows="3" data-field="text">${escapeHtml(p.text||'')}</textarea>
                    </div>
                    <div class="mb-3"><label>Author (optional)</label>
                        <input type="text" class="form-control" data-field="author" value="${escapeHtml(p.author||'')}">
                    </div>`;
            case 'divider':
                return `<div class="mb-3"><label>Style</label>
                    <select class="form-select" data-field="style">
                        <option value="line" ${p.style==='line'?'selected':''}>Plain line</option>
                        <option value="dots" ${p.style==='dots'?'selected':''}>Dots</option>
                        <option value="thick" ${p.style==='thick'?'selected':''}>Thick line</option>
                    </select>
                </div>`;
            case 'custom_html':
                return `<div class="mb-3"><label>Raw HTML</label>
                    <textarea class="form-control" rows="8" data-field="html" style="font-family:monospace;font-size:12px">${escapeHtml(p.html||'')}</textarea>
                    <div class="form-help">Use sparingly. Raw HTML is rendered as-is on the public site.</div>
                </div>`;
            default:
                return '<p>Unknown block type.</p>';
        }
    }

    function openEditor(block) {
        state.current = JSON.parse(JSON.stringify(block));
        document.getElementById('blockEditorTitle').textContent = (block.id ? 'Edit ' : 'Add ') + (labels[block.type] || block.type);
        document.getElementById('blockEditorBody').innerHTML = editorForm(state.current);
        const modal = bootstrap.Modal.getOrCreateInstance(document.getElementById('blockEditorModal'));
        modal.show();
        wireEditor(state.current);
    }

    function wireEditor(block) {
        // Rich-text Quill init
        if (block.type === 'rich_text') {
            state.quill = new Quill('#rgQuillEditor', {
                theme: 'snow',
                modules: { toolbar: [
                    [{header: [2,3,4,false]}], ['bold','italic','underline','strike'],
                    [{list:'ordered'},{list:'bullet'}], ['link','blockquote'], ['clean']
                ]},
            });
        }
        if (block.type === 'two_column') {
            state.quillLeft = new Quill('#rgQuillLeft', { theme: 'snow', modules: { toolbar: [['bold','italic','link'],[{list:'bullet'}]] } });
            state.quillRight = new Quill('#rgQuillRight', { theme: 'snow', modules: { toolbar: [['bold','italic','link'],[{list:'bullet'}]] } });
        }
        // Image / video uploads
        document.querySelectorAll('[data-upload-target]').forEach(input => {
            input.addEventListener('change', (e) => handleUpload(e, input.dataset.uploadTarget));
        });
        // FAQ + Gallery dynamic
        if (block.type === 'faq') {
            document.getElementById('rgFaqAdd').addEventListener('click', () => addFaqRow());
            document.getElementById('rgFaqList').addEventListener('click', (e) => { if (e.target.dataset.faqRemove !== undefined) e.target.closest('.rg-faq-row').remove(); });
        }
        if (block.type === 'gallery') {
            document.getElementById('rgGalleryList').addEventListener('click', (e) => { if (e.target.dataset.galleryRemove !== undefined) { e.target.closest('.rg-gallery-row').remove(); } });
        }
    }

    function addFaqRow(question = '', answer = '') {
        const list = document.getElementById('rgFaqList');
        const idx = list.children.length;
        const row = document.createElement('div');
        row.className = 'rg-faq-row';
        row.dataset.idx = idx;
        row.innerHTML = `<input type="text" class="form-control form-control-sm" placeholder="Question" data-faq-q value="${escapeHtml(question)}"><input type="text" class="form-control form-control-sm" placeholder="Answer" data-faq-a value="${escapeHtml(answer)}"><button type="button" class="btn btn-sm btn-outline-danger" data-faq-remove>&times;</button>`;
        list.appendChild(row);
    }

    function handleUpload(e, target) {
        const files = Array.from(e.target.files || []);
        if (!files.length) return;
        if (target === 'gallery') {
            const list = document.getElementById('rgGalleryList');
            files.forEach(file => {
                const fd = new FormData();
                fd.append('file', file);
                fd.append('_token', cfg.csrf);
                fetch(cfg.urls.upload, { method: 'POST', credentials: 'same-origin', body: fd })
                    .then(r => r.json())
                    .then(d => {
                        if (!d.ok) return;
                        const idx = list.children.length;
                        const row = document.createElement('div');
                        row.className = 'rg-gallery-row';
                        row.dataset.idx = idx;
                        row.dataset.src = d.url;
                        row.innerHTML = `<img src="${d.url}" style="width:80px;height:60px;object-fit:cover;border-radius:3px"><input type="text" class="form-control form-control-sm" placeholder="Alt text" data-gallery-alt><button type="button" class="btn btn-sm btn-outline-danger" data-gallery-remove>&times;</button>`;
                        list.appendChild(row);
                    });
            });
        } else {
            const fd = new FormData();
            fd.append('file', files[0]);
            fd.append('_token', cfg.csrf);
            fetch(cfg.urls.upload, { method: 'POST', credentials: 'same-origin', body: fd })
                .then(r => r.json())
                .then(d => {
                    if (!d.ok) return;
                    const hiddenInput = document.querySelector(`[data-field="${target}"]`);
                    if (hiddenInput) hiddenInput.value = d.url;
                    if (typeof toastr !== 'undefined') toastr.success('Uploaded');
                    // show preview
                    if (d.kind === 'image') {
                        const existing = e.target.parentElement.querySelector('.rg-image-preview');
                        if (existing) existing.src = d.url;
                        else {
                            const img = document.createElement('img');
                            img.src = d.url; img.className = 'rg-image-preview';
                            e.target.parentElement.appendChild(img);
                        }
                    }
                });
        }
    }

    function readEditor(block) {
        const payload = {};
        document.querySelectorAll('[data-field]').forEach(input => {
            payload[input.dataset.field] = input.value;
        });
        if (block.type === 'rich_text' && state.quill) {
            payload.html = state.quill.root.innerHTML;
        }
        if (block.type === 'two_column') {
            payload.left_html = state.quillLeft.root.innerHTML;
            payload.right_html = state.quillRight.root.innerHTML;
        }
        if (block.type === 'faq') {
            const items = [];
            document.querySelectorAll('#rgFaqList .rg-faq-row').forEach(row => {
                const q = row.querySelector('[data-faq-q]').value.trim();
                const a = row.querySelector('[data-faq-a]').value.trim();
                if (q || a) items.push({ question: q, answer: a });
            });
            payload.items = items;
        }
        if (block.type === 'gallery') {
            const images = [];
            document.querySelectorAll('#rgGalleryList .rg-gallery-row').forEach(row => {
                const src = row.dataset.src || row.querySelector('img')?.src || '';
                const alt = row.querySelector('[data-gallery-alt]')?.value?.trim() || '';
                if (src) images.push({ src, alt });
            });
            payload.images = images;
            payload.columns = parseInt(payload.columns || '3', 10);
        }
        return payload;
    }

    function saveCurrent() {
        if (!state.current) return;
        const payload = readEditor(state.current);
        const body = {
            owner_type: cfg.ownerType,
            owner_id: cfg.ownerId,
            block_type: state.current.type,
            payload: payload,
        };
        if (state.current.id) body.id = state.current.id;
        fetch(cfg.urls.save, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': cfg.csrf },
            credentials: 'same-origin',
            body: JSON.stringify(body),
        })
        .then(r => r.json())
        .then(d => {
            if (!d.ok) { if (typeof toastr !== 'undefined') toastr.error(d.message || 'Save failed'); return; }
            const idx = state.blocks.findIndex(b => b.id === d.block.id);
            if (idx >= 0) state.blocks[idx] = d.block;
            else state.blocks.push(d.block);
            state.blocks.sort((a,b) => a.sort_order - b.sort_order);
            render();
            initSortable();
            bootstrap.Modal.getInstance(document.getElementById('blockEditorModal')).hide();
            if (typeof toastr !== 'undefined') toastr.success('Block saved');
        });
    }

    function bindToolbar() {
        document.querySelectorAll('[data-add-type]').forEach(btn => {
            btn.addEventListener('click', () => {
                const type = btn.dataset.addType;
                openEditor({ id: 0, type, payload: defaultPayload(type) });
            });
        });
        document.getElementById('blockEditorSave').addEventListener('click', saveCurrent);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', () => { bindToolbar(); fetchBlocks(); });
    } else {
        bindToolbar(); fetchBlocks();
    }
})();
</script>
