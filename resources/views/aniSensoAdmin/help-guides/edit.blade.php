@extends('layouts.master')

@section('title') Edit guide @endsection

@section('css')
<style>
    /* The builder: a column of blocks you can pick up and drop somewhere else. */
    .tb-palette { display: flex; flex-wrap: wrap; gap: .4rem; }
    .tb-add { border: 1px dashed #c9ced6; background: #fff; border-radius: .5rem; padding: .35rem .7rem;
        font-size: .82rem; font-weight: 600; color: #4b5563; cursor: pointer; }
    .tb-add:hover { border-color: #22c55e; color: #15803d; background: #f0fdf4; }
    .tb-block { border: 1px solid #e5e7eb; border-radius: .6rem; background: #fff; margin-bottom: .6rem; }
    .tb-block.tb-drag { opacity: .45; }
    .tb-block.tb-over { border-color: #22c55e; box-shadow: 0 0 0 2px rgba(34,197,94,.18); }
    .tb-head { display: flex; align-items: center; gap: .5rem; padding: .45rem .6rem;
        border-bottom: 1px solid #f1f3f5; background: #fafbfc; border-radius: .6rem .6rem 0 0; }
    .tb-grip { cursor: grab; color: #9ca3af; font-size: 1rem; line-height: 1; padding: 0 .15rem; }
    .tb-grip:active { cursor: grabbing; }
    .tb-kind { font-size: .74rem; font-weight: 700; text-transform: uppercase; color: #6b7280; letter-spacing: .02em; }
    .tb-body { padding: .6rem; }
    .tb-actions { margin-left: auto; display: flex; gap: .2rem; }
    .tb-actions button { border: 0; background: transparent; color: #9ca3af; padding: .1rem .35rem; border-radius: .3rem; }
    .tb-actions button:hover { background: #eef1f4; color: #374151; }
    .tb-item-row { display: flex; gap: .35rem; margin-bottom: .35rem; }
    .tb-empty { border: 2px dashed #dfe3e8; border-radius: .6rem; padding: 1.5rem; text-align: center; color: #9ca3af; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') How-to Guides @endslot
        @slot('title') {{ \App\Models\AsTutorialPage::moduleLabel($module) }} — {{ \App\Models\AsTutorialPage::DEVICE_LABELS[$device] }} @endslot
    @endcomponent

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif
    @if (session('error'))<div class="alert alert-danger">{{ session('error') }}</div>@endif

    <form method="POST" action="{{ route('anisenso-help-guides.update', ['module' => $module, 'device' => $device]) }}" id="tbForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="blocks" id="tbBlocks">

        <div class="row">
            <div class="col-lg-8">
                <div class="card"><div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Title</label>
                        <input type="text" name="title" class="form-control" maxlength="191" required
                               value="{{ old('title', $page->title) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Summary <span class="text-muted">(one line, under the title)</span></label>
                        <input type="text" name="summary" class="form-control" maxlength="400"
                               value="{{ old('summary', $page->summary) }}">
                    </div>

                    <label class="form-label">Content</label>
                    <p class="text-secondary small">Drag a block by its handle to move it, or use the arrows.
                        Everything is a block, which is how the app can draw a guide the same way it draws itself.</p>
                    <div id="tbCanvas"></div>

                    <div class="tb-palette mt-3">
                        @foreach (\App\Models\AsTutorialPage::BLOCK_KINDS as $kind => $label)
                            <button type="button" class="tb-add" data-add="{{ $kind }}">+ {{ $label }}</button>
                        @endforeach
                    </div>
                </div></div>
            </div>

            <div class="col-lg-4">
                <div class="card"><div class="card-body">
                    <h5 class="text-dark">This page</h5>
                    <p class="text-secondary small mb-2">
                        Read by people on <strong>{{ \App\Models\AsTutorialPage::DEVICE_LABELS[$device] }}</strong>
                        in the <strong>{{ \App\Models\AsTutorialPage::moduleLabel($module) }}</strong> module.
                    </p>
                    <div class="d-flex flex-wrap gap-2 mb-3">
                        @foreach (\App\Models\AsTutorialPage::DEVICES as $d)
                            <a href="{{ route('anisenso-help-guides.index', ['module' => $module, 'device' => $d]) }}"
                               class="btn btn-sm {{ $d === $device ? 'btn-success' : 'btn-soft-secondary' }}">
                                {{ \App\Models\AsTutorialPage::DEVICE_LABELS[$d] }}
                            </a>
                        @endforeach
                    </div>

                    <button type="submit" class="btn btn-success w-100 mb-2">Save guide</button>

                    @if ($siblings->isNotEmpty())
                        <hr>
                        <p class="text-secondary small mb-2">Start from another device's guide, then change what differs:</p>
                        @foreach ($siblings as $sib)
                            <form method="POST" action="{{ route('anisenso-help-guides.copy', ['module' => $module, 'device' => $device]) }}" class="mb-1">
                                @csrf
                                <input type="hidden" name="from" value="{{ $sib->device }}">
                                <button class="btn btn-sm btn-soft-secondary w-100" type="submit"
                                        onclick="return confirm('Replace this page with the {{ \App\Models\AsTutorialPage::DEVICE_LABELS[$sib->device] }} one?');">
                                    Copy from {{ \App\Models\AsTutorialPage::DEVICE_LABELS[$sib->device] }}
                                </button>
                            </form>
                        @endforeach
                    @endif

                    @if ($page->exists)
                        <hr>
                        <form method="POST" action="{{ route('anisenso-help-guides.destroy', ['module' => $module, 'device' => $device]) }}"
                              onsubmit="return confirm('Remove this guide? Readers on this device will see another page instead.');">
                            @csrf
                            @method('DELETE')
                            <button class="btn btn-sm btn-soft-danger w-100" type="submit">Remove this guide</button>
                        </form>
                    @endif
                </div></div>
            </div>
        </div>
    </form>

<script>
(function helpGuideBuilder() {
    const KINDS = @json(\App\Models\AsTutorialPage::BLOCK_KINDS);
    const canvas = document.getElementById('tbCanvas');
    const store = document.getElementById('tbBlocks');
    let blocks = @json($page->blocks ?: []);
    if (!Array.isArray(blocks)) blocks = [];

    const esc = (v) => String(v == null ? '' : v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    /* Each kind draws its own fields. They carry data-f so a single handler can
       write any of them back into the block it belongs to — no per-kind wiring
       to forget when a new kind is added. */
    function fields(b) {
        switch (b.kind) {
            case 'heading':
                return `<input type="text" class="form-control" data-f="text" placeholder="Section heading" value="${esc(b.text)}">`;
            case 'text':
                return `<textarea class="form-control" rows="4" data-f="text" placeholder="Write a paragraph. Leave a blank line to start another.">${esc(b.text)}</textarea>`;
            case 'steps':
            case 'tips':
                return (b.items || ['']).map((it, n) => `<div class="tb-item-row">
                        <input type="text" class="form-control" data-f="items" data-n="${n}" placeholder="${b.kind === 'steps' ? 'Step ' + (n + 1) : 'Point ' + (n + 1)}" value="${esc(it)}">
                        <button type="button" class="btn btn-sm btn-soft-danger" data-item-del="${n}">&times;</button>
                    </div>`).join('')
                    + `<button type="button" class="btn btn-sm btn-soft-secondary" data-item-add>+ Add ${b.kind === 'steps' ? 'step' : 'point'}</button>`;
            case 'callout':
                return `<div class="row g-2 mb-2">
                        <div class="col-8"><input type="text" class="form-control" data-f="title" placeholder="Callout title (optional)" value="${esc(b.title)}"></div>
                        <div class="col-4"><select class="form-select" data-f="tone">
                            ${['note', 'good', 'warn'].map((t) => `<option value="${t}"${b.tone === t ? ' selected' : ''}>${t === 'note' ? 'Note' : (t === 'good' ? 'Good to know' : 'Careful')}</option>`).join('')}
                        </select></div>
                    </div>
                    <textarea class="form-control" rows="2" data-f="text" placeholder="What the reader should know">${esc(b.text)}</textarea>`;
            case 'image':
                return `<input type="text" class="form-control mb-2" data-f="url" placeholder="https://… or /storage/…" value="${esc(b.url)}">
                    <input type="text" class="form-control" data-f="caption" placeholder="Caption (optional)" value="${esc(b.caption)}">`;
            case 'video':
                return `<input type="text" class="form-control" data-f="url" placeholder="YouTube link or id" value="${esc(b.url)}">`;
        }
        return '<p class="text-secondary small mb-0">A horizontal rule.</p>';
    }

    function paint() {
        if (!blocks.length) {
            canvas.innerHTML = '<div class="tb-empty">Nothing here yet — add a block below.</div>';
            return;
        }
        canvas.innerHTML = blocks.map((b, i) => `
            <div class="tb-block" draggable="true" data-i="${i}">
                <div class="tb-head">
                    <span class="tb-grip" title="Drag to move">&#9776;</span>
                    <span class="tb-kind">${esc(KINDS[b.kind] || b.kind)}</span>
                    <span class="tb-actions">
                        <button type="button" data-move="-1" title="Move up">&uarr;</button>
                        <button type="button" data-move="1" title="Move down">&darr;</button>
                        <button type="button" data-del title="Remove">&times;</button>
                    </span>
                </div>
                <div class="tb-body">${fields(b)}</div>
            </div>`).join('');
    }

    const blockAt = (el) => {
        const wrap = el.closest('.tb-block');
        return wrap ? { i: parseInt(wrap.getAttribute('data-i'), 10), wrap } : null;
    };

    canvas.addEventListener('input', (e) => {
        const at = blockAt(e.target);
        const f = e.target.getAttribute('data-f');
        if (!at || !f) return;
        if (f === 'items') {
            const n = parseInt(e.target.getAttribute('data-n'), 10);
            blocks[at.i].items = blocks[at.i].items || [];
            blocks[at.i].items[n] = e.target.value;
        } else {
            blocks[at.i][f] = e.target.value;
        }
    });
    canvas.addEventListener('change', (e) => {
        const at = blockAt(e.target);
        if (at && e.target.getAttribute('data-f') === 'tone') blocks[at.i].tone = e.target.value;
    });

    canvas.addEventListener('click', (e) => {
        const at = blockAt(e.target);
        if (!at) return;
        if (e.target.closest('[data-del]')) { blocks.splice(at.i, 1); paint(); return; }
        const mv = e.target.closest('[data-move]');
        if (mv) {
            const to = at.i + parseInt(mv.getAttribute('data-move'), 10);
            if (to < 0 || to >= blocks.length) return;
            const [b] = blocks.splice(at.i, 1);
            blocks.splice(to, 0, b);
            paint();
            return;
        }
        if (e.target.closest('[data-item-add]')) {
            blocks[at.i].items = (blocks[at.i].items || []).concat(['']);
            paint();
            return;
        }
        const del = e.target.closest('[data-item-del]');
        if (del) {
            const n = parseInt(del.getAttribute('data-item-del'), 10);
            blocks[at.i].items = (blocks[at.i].items || []).filter((_, k) => k !== n);
            paint();
        }
    });

    /* Dragging. The arrows above do the same job for anyone on a touchscreen,
       where HTML5 drag never fires at all. */
    let dragFrom = null;
    canvas.addEventListener('dragstart', (e) => {
        const at = blockAt(e.target);
        if (!at) return;
        dragFrom = at.i;
        at.wrap.classList.add('tb-drag');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', String(at.i)); } catch (_) {}
    });
    canvas.addEventListener('dragover', (e) => {
        e.preventDefault();
        const at = blockAt(e.target);
        canvas.querySelectorAll('.tb-over').forEach((el) => el.classList.remove('tb-over'));
        if (at && at.i !== dragFrom) at.wrap.classList.add('tb-over');
    });
    canvas.addEventListener('drop', (e) => {
        e.preventDefault();
        const at = blockAt(e.target);
        if (dragFrom === null || !at || at.i === dragFrom) return;
        const [b] = blocks.splice(dragFrom, 1);
        blocks.splice(at.i, 0, b);
        dragFrom = null;
        paint();
    });
    canvas.addEventListener('dragend', () => {
        dragFrom = null;
        canvas.querySelectorAll('.tb-drag, .tb-over').forEach((el) => el.classList.remove('tb-drag', 'tb-over'));
    });

    document.querySelectorAll('[data-add]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const kind = btn.getAttribute('data-add');
            const fresh = { kind };
            if (kind === 'steps' || kind === 'tips') fresh.items = [''];
            if (kind === 'callout') fresh.tone = 'note';
            blocks.push(fresh);
            paint();
            canvas.lastElementChild && canvas.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    document.getElementById('tbForm').addEventListener('submit', () => {
        store.value = JSON.stringify(blocks);
    });

    paint();
})();
</script>
@endsection
