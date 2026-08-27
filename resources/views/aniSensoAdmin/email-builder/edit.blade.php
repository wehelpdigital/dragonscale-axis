@extends('layouts.master')

@section('title') Email builder @endsection

@section('css')
<style>
    .eb-add { border: 1px dashed #c9ced6; background: #fff; border-radius: .5rem; padding: .35rem .7rem;
        font-size: .82rem; font-weight: 600; color: #4b5563; cursor: pointer; }
    .eb-add:hover { border-color: #22c55e; color: #15803d; background: #f0fdf4; }
    .eb-block { border: 1px solid #e5e7eb; border-radius: .6rem; background: #fff; margin-bottom: .6rem; }
    .eb-block.eb-drag { opacity: .45; }
    .eb-block.eb-over { border-color: #22c55e; box-shadow: 0 0 0 2px rgba(34,197,94,.18); }
    .eb-head { display: flex; align-items: center; gap: .5rem; padding: .45rem .6rem;
        border-bottom: 1px solid #f1f3f5; background: #fafbfc; border-radius: .6rem .6rem 0 0; }
    .eb-grip { cursor: grab; color: #9ca3af; font-size: 1rem; line-height: 1; }
    .eb-kind { font-size: .74rem; font-weight: 700; text-transform: uppercase; color: #6b7280; }
    .eb-body { padding: .6rem; }
    .eb-actions { margin-left: auto; display: flex; gap: .2rem; }
    .eb-actions button { border: 0; background: transparent; color: #9ca3af; padding: .1rem .35rem; border-radius: .3rem; }
    .eb-actions button:hover { background: #eef1f4; color: #374151; }
    .eb-item-row { display: flex; gap: .35rem; margin-bottom: .35rem; }
    .eb-empty { border: 2px dashed #dfe3e8; border-radius: .6rem; padding: 1.5rem; text-align: center; color: #9ca3af; }
    .eb-field { border: 1px solid #dbe3ea; background: #f8fafc; border-radius: 999px; padding: .15rem .55rem;
        font-size: .74rem; font-family: ui-monospace, Menlo, Consolas, monospace; color: #334155; cursor: pointer; }
    .eb-field:hover { background: #e0f2fe; border-color: #7dd3fc; }
    .eb-note { font-size: .78rem; color: #6b7280; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Email Layouts @endslot
        @slot('title') {{ $template->templateName }} @endslot
    @endcomponent

    @if (session('success'))<div class="alert alert-success">{{ session('success') }}</div>@endif

    <form method="POST" action="{{ route('anisenso-email-builder.update', ['id' => $template->id]) }}" id="ebForm">
        @csrf
        @method('PUT')
        <input type="hidden" name="blocks" id="ebBlocks">

        <div class="row">
            <div class="col-lg-8">
                <div class="card"><div class="card-body">
                    <div class="mb-3">
                        <label class="form-label">Template name</label>
                        <input type="text" name="templateName" class="form-control" maxlength="150" required
                               value="{{ old('templateName', $template->templateName) }}">
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Subject line</label>
                        <input type="text" name="subject" class="form-control" maxlength="255" required
                               id="ebSubject" value="{{ old('subject', $template->subject) }}">
                        <div class="eb-note mt-1">Merge fields work here too — click one below while the cursor is in this box.</div>
                    </div>

                    <label class="form-label">Layout</label>
                    <p class="eb-note">Drag a block by its handle, or use the arrows. What you build is rendered to
                        the HTML that gets sent, so nobody has to write email markup by hand.</p>
                    <div id="ebCanvas"></div>

                    <div class="d-flex flex-wrap gap-2 mt-3">
                        @foreach (\App\Support\EmailBlocks::KINDS as $kind => $label)
                            <button type="button" class="eb-add" data-add="{{ $kind }}">+ {{ $label }}</button>
                        @endforeach
                    </div>
                </div></div>
            </div>

            <div class="col-lg-4">
                <div class="card"><div class="card-body">
                    <button type="submit" class="btn btn-success w-100 mb-3">Save layout</button>

                    @php
                        /* The chips this template can actually use.
                         *
                         * EmailBlocks::MERGE_FIELDS is the house list, written
                         * for the daily digest. Every other template has tags
                         * of its own — {{workerName}}, {{inviteUrl}},
                         * {{tasksTable}} — which were printed as a sentence
                         * at the bottom of this panel and had to be typed by
                         * hand, one character wrong being one email that
                         * arrives with a brace in it. They are chips now, and
                         * they lead, because they are the ones this layout
                         * is about. */
                        $ownTags = collect(preg_split('/[,\s]+/', (string) $template->availableTags))
                            ->map(fn ($t) => trim($t))
                            ->filter(fn ($t) => str_starts_with($t, '{{') && str_ends_with($t, '}}'))
                            ->unique()
                            ->values();
                        $houseTags = collect(\App\Support\EmailBlocks::MERGE_FIELDS)
                            ->reject(fn ($what, $tag) => $ownTags->contains($tag));
                    @endphp

                    <h6 class="text-dark">Merge fields</h6>
                    <p class="eb-note mb-2">Click one to drop it where the cursor is. The app fills these in when it
                        sends — it is the only one that knows whose email this is.</p>

                    @if ($ownTags->isNotEmpty())
                        <p class="eb-note mb-1"><strong>This template</strong></p>
                        <div class="d-flex flex-wrap gap-1 mb-3">
                            @foreach ($ownTags as $tag)
                                <span class="eb-field" data-field="{{ $tag }}">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                    @if ($houseTags->isNotEmpty())
                        <p class="eb-note mb-1"><strong>Everywhere</strong></p>
                        <div class="d-flex flex-wrap gap-1">
                            @foreach ($houseTags as $tag => $what)
                                <span class="eb-field" data-field="{{ $tag }}" title="{{ $what }}">{{ $tag }}</span>
                            @endforeach
                        </div>
                    @endif

                    <hr>
                    <h6 class="text-dark">The day's activities</h6>
                    <p class="eb-note mb-0">Add the <strong>The day's activities</strong> block where the list of
                        work should appear. It is the one block the layout cannot fill in itself — the app expands
                        it per person, so a worker sees only their own jobs.</p>

                </div></div>
            </div>
        </div>
    </form>

<script>
(function emailBuilder() {
    const KINDS = @json(\App\Support\EmailBlocks::KINDS);
    const canvas = document.getElementById('ebCanvas');
    const store = document.getElementById('ebBlocks');
    let blocks = @json($template->blocks ?: []);
    if (!Array.isArray(blocks)) blocks = [];

    const esc = (v) => String(v == null ? '' : v)
        .replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');

    /* The last field the cursor was in, so a merge-field chip knows where to
       land. Without this the chip would have nowhere to go the moment focus
       moves to the chip itself. */
    let lastField = document.getElementById('ebSubject');
    document.addEventListener('focusin', (e) => {
        if (e.target.matches('input[type=text], textarea')) lastField = e.target;
    });

    function fields(b) {
        switch (b.kind) {
            case 'heading':
                return `<input type="text" class="form-control" data-f="text" placeholder="Heading" value="${esc(b.text)}">`;
            case 'text':
                return `<textarea class="form-control" rows="4" data-f="text" placeholder="Write a paragraph. Blank line starts another.">${esc(b.text)}</textarea>`;
            case 'tips':
                return (b.items || ['']).map((it, n) => `<div class="eb-item-row">
                        <input type="text" class="form-control" data-f="items" data-n="${n}" placeholder="Point ${n + 1}" value="${esc(it)}">
                        <button type="button" class="btn btn-sm btn-soft-danger" data-item-del="${n}">&times;</button>
                    </div>`).join('')
                    + '<button type="button" class="btn btn-sm btn-soft-secondary" data-item-add>+ Add point</button>';
            case 'callout':
                return `<input type="text" class="form-control mb-2" data-f="title" placeholder="Box title (optional)" value="${esc(b.title)}">
                    <textarea class="form-control" rows="2" data-f="text" placeholder="What to highlight">${esc(b.text)}</textarea>`;
            case 'button':
                return `<div class="row g-2">
                        <div class="col-5"><input type="text" class="form-control" data-f="text" placeholder="Button label" value="${esc(b.text)}"></div>
                        <div class="col-7"><input type="text" class="form-control" data-f="url" placeholder="https://…" value="${esc(b.url)}"></div>
                    </div>`;
            case 'activities':
                return '<p class="eb-note mb-0">The list of today\'s and tomorrow\'s work, filled in per recipient when the email is sent.</p>';
            case 'spacer':
                return '<p class="eb-note mb-0">A gap.</p>';
        }
        return '<p class="eb-note mb-0">A line across the email.</p>';
    }

    function paint() {
        if (!blocks.length) {
            canvas.innerHTML = '<div class="eb-empty">Nothing here yet — add a block below.</div>';
            return;
        }
        canvas.innerHTML = blocks.map((b, i) => `
            <div class="eb-block" draggable="true" data-i="${i}">
                <div class="eb-head">
                    <span class="eb-grip" title="Drag to move">&#9776;</span>
                    <span class="eb-kind">${esc(KINDS[b.kind] || b.kind)}</span>
                    <span class="eb-actions">
                        <button type="button" data-move="-1" title="Move up">&uarr;</button>
                        <button type="button" data-move="1" title="Move down">&darr;</button>
                        <button type="button" data-del title="Remove">&times;</button>
                    </span>
                </div>
                <div class="eb-body">${fields(b)}</div>
            </div>`).join('');
    }

    const blockAt = (el) => {
        const wrap = el.closest('.eb-block');
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

    let dragFrom = null;
    canvas.addEventListener('dragstart', (e) => {
        const at = blockAt(e.target);
        if (!at) return;
        dragFrom = at.i;
        at.wrap.classList.add('eb-drag');
        e.dataTransfer.effectAllowed = 'move';
        try { e.dataTransfer.setData('text/plain', String(at.i)); } catch (_) {}
    });
    canvas.addEventListener('dragover', (e) => {
        e.preventDefault();
        const at = blockAt(e.target);
        canvas.querySelectorAll('.eb-over').forEach((el) => el.classList.remove('eb-over'));
        if (at && at.i !== dragFrom) at.wrap.classList.add('eb-over');
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
        canvas.querySelectorAll('.eb-drag, .eb-over').forEach((el) => el.classList.remove('eb-drag', 'eb-over'));
    });

    document.querySelectorAll('[data-add]').forEach((btn) => {
        btn.addEventListener('click', () => {
            const kind = btn.getAttribute('data-add');
            const fresh = { kind };
            if (kind === 'tips') fresh.items = [''];
            if (kind === 'button') { fresh.text = 'Open the schedule'; fresh.url = ''; }
            blocks.push(fresh);
            paint();
            canvas.lastElementChild && canvas.lastElementChild.scrollIntoView({ behavior: 'smooth', block: 'center' });
        });
    });

    /* Drop a merge field where the cursor was. mousedown, not click: by the
       time click fires the field has already lost its selection. */
    document.querySelectorAll('[data-field]').forEach((chip) => {
        chip.addEventListener('mousedown', (e) => {
            e.preventDefault();
            const tag = chip.getAttribute('data-field');
            const el = lastField;
            if (!el) return;
            const start = el.selectionStart ?? el.value.length;
            const end = el.selectionEnd ?? el.value.length;
            el.value = el.value.slice(0, start) + tag + el.value.slice(end);
            el.dispatchEvent(new Event('input', { bubbles: true }));
            el.focus();
            el.setSelectionRange(start + tag.length, start + tag.length);
        });
    });

    document.getElementById('ebForm').addEventListener('submit', () => {
        store.value = JSON.stringify(blocks);
    });

    paint();
})();
</script>
@endsection
