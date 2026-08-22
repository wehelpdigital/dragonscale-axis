@extends('layouts.master')

@section('title') Community — AI Answers @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .aiadraft-card { border:1px solid #eef0f2; }
    .aiadraft-card.is-posting { opacity:.55; pointer-events:none; }
    .aiadraft-card.is-new { animation: aiaIn .32s cubic-bezier(.22,1,.36,1); }
    @keyframes aiaIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
    @media (prefers-reduced-motion: reduce) { .aiadraft-card.is-new { animation:none; } }

    .aiadraft-q { background:#f8f9fa; border-radius:.5rem; padding:.75rem 1rem; }
    .aia-badge-model { font-size:.7rem; }

    /* ---- The answer, read rather than glimpsed ----
       A 150px box on a six-paragraph answer meant scrolling inside a scroll,
       so the field now grows to what it holds. Two heights: a tall default
       that shows most answers whole, and a full one for the long ones. Both
       ease, so a row opening does not jump the page under the cursor. */
    .aiadraft-a textarea.form-control {
        min-height:260px; max-height:60vh; font-size:.925rem; line-height:1.6;
        transition:max-height .28s cubic-bezier(.22,1,.36,1);
    }
    .aiadraft-card.is-tall .aiadraft-a textarea.form-control { max-height:none; }
    @media (prefers-reduced-motion: reduce) { .aiadraft-a textarea.form-control { transition:none; } }

    /* ---- Collapsed rows ----
       Fifteen answers at full height is a page you cannot navigate. A folded
       row keeps its question and hides its answer, and the fold animates. */
    .aiadraft-fold { display:grid; grid-template-rows:1fr; transition:grid-template-rows .28s cubic-bezier(.22,1,.36,1); }
    .aiadraft-card.is-folded .aiadraft-fold { grid-template-rows:0fr; }
    .aiadraft-fold > div { overflow:hidden; min-height:0; }
    .aiadraft-card.is-folded .aia-chevron { transform:rotate(-90deg); }
    .aia-chevron { transition:transform .28s cubic-bezier(.22,1,.36,1); }
    @media (prefers-reduced-motion: reduce) {
        .aiadraft-fold, .aia-chevron { transition:none; }
    }

    .aia-head { cursor:pointer; }
    .aia-progress { font-size:.85rem; }
    .aia-lines { font-size:.72rem; color:#98a4b6; }
</style>
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('title') AI Answers @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex flex-wrap justify-content-between align-items-center mb-3 gap-2">
                        <div>
                            <h4 class="card-title mb-1 text-dark">AI Answers for Community Questions</h4>
                            <p class="text-secondary mb-0">
                                Generate {{ $assistantName }} answers for group questions that haven't been answered yet.
                                Review and edit each one, then post it to the community as the {{ $assistantName }}.
                            </p>
                        </div>
                        <div class="d-flex gap-2 flex-wrap">
                            <button type="button" class="btn btn-primary" id="btnGenerate" {{ $aiUsable ? '' : 'disabled' }}>
                                <i class="bx bx-bot"></i> Answer new questions
                                <span class="badge bg-light text-dark ms-1" id="unansweredBadge">{{ $unansweredCount }}</span>
                            </button>
                            <button type="button" class="btn btn-outline-danger" id="btnStop" style="display:none;">
                                <i class="bx bx-stop-circle"></i> Stop
                            </button>
                            <button type="button" class="btn btn-success" id="btnPostAll" @if($drafts->isEmpty()) style="display:none;" @endif>
                                <i class="bx bx-send"></i> Post all (<span id="postAllCount">{{ $drafts->count() }}</span>)
                            </button>
                            <button type="button" class="btn btn-light" id="btnFoldAll" title="Fold or open every answer">
                                <i class="bx bx-collapse-vertical"></i> Fold all
                            </button>
                        </div>
                    </div>

                    {{-- Where a run says what it is doing. Answers arrive one at
                         a time and each is drawn as it lands, so this line is
                         the only thing that has to be watched. --}}
                    <div class="alert alert-info align-items-center gap-2 aia-progress" id="genProgress" style="display:none;">
                        <i class="bx bx-loader-alt bx-spin"></i>
                        <span id="genProgressText">Asking the AI…</span>
                    </div>

                    @unless($aiUsable)
                        <div class="alert alert-warning mb-3">
                            <i class="bx bx-error-circle me-1"></i>
                            The AniSenso AI isn't configured yet. Set a provider, model and API key in
                            <a href="{{ route('anisenso-ai-settings.index') }}">AI Settings</a> before generating answers.
                        </div>
                    @endunless

                    <div id="draftsWrap">
                        @foreach($drafts as $draft)
                            @include('aniSensoAdmin.community.partials.ai-answer-card', [
                                'draft' => $draft,
                                'assistantName' => $assistantName,
                            ])
                        @endforeach
                    </div>

                    <div class="text-center text-secondary py-5" id="emptyState" @if($drafts->isNotEmpty()) style="display:none;" @endif>
                        <i class="bx bx-message-rounded-check" style="font-size:2.5rem;"></i>
                        <p class="mb-0 mt-2">No answers waiting for review.</p>
                        <p class="small">Click <strong>Answer new questions</strong> to generate answers for unanswered community questions.</p>
                    </div>

                    <div id="recentWrap" @if($recentPosted->isEmpty()) style="display:none;" @endif>
                        <hr class="my-4">
                        <h6 class="text-secondary mb-2">Recently posted</h6>
                        <ul class="list-unstyled mb-0" id="recentList">
                            @foreach($recentPosted as $p)
                                <li class="small text-secondary mb-1">
                                    <i class="bx bx-check-circle text-success"></i>
                                    {{ $p->questionTitle ?: 'Question' }}
                                    <span class="text-muted">— in {{ optional($p->post)->group->name ?? 'a group' }}, {{ $p->postedAt?->format('M j, Y g:i A') }}</span>
                                    @if($p->post)
                                        <a href="{{ rtrim(config('anisystem.url'), '/') }}/app/community/groups/{{ $p->post->groupId }}#post-{{ $p->postId }}"
                                           target="_blank" rel="noopener" class="ms-1">view in the community</a>
                                    @endif
                                </li>
                            @endforeach
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    const ASSISTANT = @json($assistantName);
    const jsonHeaders = { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' };
    const esc = (v) => String(v ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));

    async function api(url, method, body) {
        const res = await fetch(url, { method, headers: jsonHeaders, body: body ? JSON.stringify(body) : undefined });
        return res.json();
    }

    /* ---------------- the answer field ----------------
     * Grows to what it holds, so a long answer is read rather than scrolled
     * inside a box. The CSS caps it; this only sets the natural height. */
    function fitAnswer(ta) {
        if (!ta) return;
        ta.style.height = 'auto';
        ta.style.height = (ta.scrollHeight + 2) + 'px';
        const card = ta.closest('.aiadraft-card');
        const lines = card?.querySelector('.aia-lines');
        if (lines) {
            const words = ta.value.trim() ? ta.value.trim().split(/\s+/).length : 0;
            lines.textContent = words + (words === 1 ? ' word' : ' words');
        }
    }
    function fitAll(scope) { (scope || document).querySelectorAll('.draft-answer').forEach(fitAnswer); }

    document.addEventListener('input', (e) => {
        if (e.target.classList?.contains('draft-answer')) fitAnswer(e.target);
    });

    /* Folding: the head folds the row, the chevron beside the answer lets one
     * long answer off its height cap without touching the others. */
    document.addEventListener('click', (e) => {
        const head = e.target.closest('.aia-head');
        if (head && !e.target.closest('button')) {
            head.closest('.aiadraft-card')?.classList.toggle('is-folded');
            return;
        }
        const tallBtn = e.target.closest('.btn-tall');
        if (tallBtn) {
            const card = tallBtn.closest('.aiadraft-card');
            card.classList.toggle('is-tall');
            tallBtn.innerHTML = card.classList.contains('is-tall')
                ? '<i class="bx bx-collapse-vertical"></i> Shrink'
                : '<i class="bx bx-expand-vertical"></i> Expand';
            if (card.classList.contains('is-tall')) fitAnswer(card.querySelector('.draft-answer'));
        }
    });

    document.getElementById('btnFoldAll')?.addEventListener('click', function () {
        const cards = [...document.querySelectorAll('.aiadraft-card')];
        const foldingUp = cards.some(c => !c.classList.contains('is-folded'));
        cards.forEach(c => c.classList.toggle('is-folded', foldingUp));
        this.innerHTML = foldingUp
            ? '<i class="bx bx-expand-vertical"></i> Open all'
            : '<i class="bx bx-collapse-vertical"></i> Fold all';
    });

    /* ---------------- generation, one question at a time ----------------
     * Fifteen answers used to be fifteen provider round-trips inside a single
     * request: a spinner for minutes, often a timeout, and an answer you only
     * saw by reloading the page. Each answer is now its own request, drawn the
     * moment it lands, and the run can be stopped between them. */
    const btnGenerate = document.getElementById('btnGenerate');
    const btnStop = document.getElementById('btnStop');
    const progress = document.getElementById('genProgress');
    const progressText = document.getElementById('genProgressText');
    let stopped = false;

    function cardHtml(d) {
        return `
        <div class="card aiadraft-card mb-3 is-new" data-draft="${d.id}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2 aia-head">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bx bx-chevron-down aia-chevron text-secondary" style="font-size:1.25rem;"></i>
                        <div>
                            <div class="fw-semibold text-dark">${esc(d.questionTitle)}</div>
                            <div class="text-secondary small">
                                in ${esc(d.groupName)}${d.askedBy ? ' · asked by ' + esc(d.askedBy) : ''}
                                ${d.model ? ' · <span class="badge bg-light text-dark aia-badge-model">' + esc(d.model) + '</span>' : ''}
                            </div>
                        </div>
                    </div>
                    <button type="button" class="btn btn-sm btn-soft-danger btn-dismiss" data-id="${d.id}" title="Discard">
                        <i class="bx bx-trash"></i>
                    </button>
                </div>
                <div class="aiadraft-fold"><div>
                    ${d.questionBody ? `<div class="aiadraft-q text-secondary small mb-3">${esc(d.questionBody).replace(/\n/g, '<br>')}</div>` : ''}
                    <div class="d-flex justify-content-between align-items-center mb-1">
                        <label class="form-label small text-secondary mb-0">${esc(ASSISTANT)}'s answer (editable)</label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="aia-lines"></span>
                            <button type="button" class="btn btn-sm btn-light btn-tall"><i class="bx bx-expand-vertical"></i> Expand</button>
                        </div>
                    </div>
                    <div class="aiadraft-a mb-2">
                        <textarea class="form-control draft-answer">${esc(d.answerBody)}</textarea>
                    </div>
                    <div class="d-flex gap-2 justify-content-end align-items-center">
                        ${d.postUrl ? `<a href="${esc(d.postUrl)}" target="_blank" rel="noopener" class="small me-auto">see the question in the community</a>` : ''}
                        <button type="button" class="btn btn-sm btn-soft-secondary btn-save" data-id="${d.id}">
                            <i class="bx bx-save"></i> Save edits
                        </button>
                        <button type="button" class="btn btn-sm btn-success btn-post" data-id="${d.id}">
                            <i class="bx bx-send"></i> Post to community
                        </button>
                    </div>
                </div></div>
            </div>
        </div>`;
    }

    function addCard(d) {
        document.getElementById('emptyState').style.display = 'none';
        const wrap = document.getElementById('draftsWrap');
        wrap.insertAdjacentHTML('afterbegin', cardHtml(d));
        fitAnswer(wrap.firstElementChild.querySelector('.draft-answer'));
        countDrafts();
    }

    function countDrafts() {
        const n = document.querySelectorAll('[data-draft]').length;
        document.getElementById('postAllCount').textContent = n;
        document.getElementById('btnPostAll').style.display = n ? '' : 'none';
        document.getElementById('emptyState').style.display = n ? 'none' : '';
        return n;
    }

    btnGenerate?.addEventListener('click', async () => {
        stopped = false;
        // The button's own markup is left alone: it carries the unanswered
        // badge, and stashing innerHTML to restore it later put the stale
        // count back at the end of every run.
        btnGenerate.disabled = true;
        btnStop.style.display = '';
        progress.style.display = 'flex';
        let made = 0;

        try {
            while (!stopped) {
                progressText.textContent = made
                    ? `${made} answered — asking the next question…`
                    : 'Asking the AI…';
                const data = await api('{{ route('anisenso-community.ai-answers.generate') }}', 'POST', { limit: 1 });

                if (!data.success) { toastr.error(data.message || 'Could not generate answers.'); break; }
                (data.drafts || []).forEach(d => { addCard(d); made++; });

                const left = Number(data.remaining ?? 0);
                document.getElementById('unansweredBadge').textContent = left;

                if (!data.count) {                       // nothing generated this round
                    if (!made) toastr.info(data.message || 'No new questions to answer.');
                    break;
                }
                if (left <= 0) break;
            }
            if (made) toastr.success(made + (made === 1 ? ' answer' : ' answers') + ' ready for review.');
            else if (stopped) toastr.info('Stopped.');
        } catch (_) {
            toastr.error('Network error — try again.');
        }

        stopped = false;
        btnStop.style.display = 'none';
        progress.style.display = 'none';
        btnGenerate.disabled = false;
    });

    btnStop?.addEventListener('click', () => {
        stopped = true;
        progressText.textContent = 'Finishing the answer in flight, then stopping…';
    });

    /* ---------------- per-card actions (delegated) ----------------
     * Cards arrive after this script runs, so nothing here binds per element. */
    function cardFor(id) { return document.querySelector('[data-draft="' + id + '"]'); }
    function answerOf(id) { return cardFor(id)?.querySelector('.draft-answer')?.value ?? ''; }

    document.addEventListener('click', async (e) => {
        const save = e.target.closest('.btn-save');
        if (save) {
            const id = save.getAttribute('data-id');
            save.disabled = true;
            try {
                const data = await api('/anisenso-community-ai-answers?id=' + id, 'PUT', { answerBody: answerOf(id) });
                data.success ? toastr.success(data.message) : toastr.error(data.message);
            } catch (_) { toastr.error('Network error — try again.'); }
            save.disabled = false;
            return;
        }

        const post = e.target.closest('.btn-post');
        if (post) { postDraft(post.getAttribute('data-id')); return; }

        const dismiss = e.target.closest('.btn-dismiss');
        if (dismiss) {
            if (!confirm('Discard this answer without posting?')) return;
            const id = dismiss.getAttribute('data-id');
            try {
                const data = await api('/anisenso-community-ai-answers?id=' + id, 'DELETE');
                if (data.success) { toastr.success(data.message); cardFor(id)?.remove(); countDrafts(); }
                else { toastr.error(data.message); }
            } catch (_) { toastr.error('Network error — try again.'); }
        }
    });

    async function postDraft(id) {
        const card = cardFor(id);
        if (!card) return;
        const title = card.querySelector('.fw-semibold')?.textContent?.trim() || 'Question';
        card.classList.add('is-posting');
        try {
            const data = await api('/anisenso-community-ai-answers-post?id=' + id, 'POST', { answerBody: answerOf(id) });
            if (data.success) {
                toastr.success(data.message);
                card.remove();
                countDrafts();
                notePosted(title, data.url);
            } else {
                toastr.error(data.message);
                card.classList.remove('is-posting');
            }
        } catch (_) { toastr.error('Network error — try again.'); card.classList.remove('is-posting'); }
    }

    // A posted answer is live in the community straight away — this says so,
    // and links to the topic it landed in, so it can be checked rather than
    // taken on trust.
    function notePosted(title, url) {
        const wrap = document.getElementById('recentWrap');
        wrap.style.display = '';
        const li = document.createElement('li');
        li.className = 'small text-secondary mb-1';
        li.innerHTML = '<i class="bx bx-check-circle text-success"></i> ' + esc(title) +
            ' <span class="text-muted">— just now</span>' +
            (url ? ' <a href="' + esc(url) + '" target="_blank" rel="noopener" class="ms-1">view in the community</a>' : '');
        document.getElementById('recentList').prepend(li);
    }

    document.getElementById('btnPostAll')?.addEventListener('click', async function () {
        const ids = [...document.querySelectorAll('[data-draft]')].map(c => c.getAttribute('data-draft'));
        if (!ids.length) return;
        if (!confirm('Post every reviewed answer to the community?')) return;
        this.disabled = true;
        const original = this.innerHTML;
        // One at a time, so each card leaves as its answer lands and a failure
        // in the middle is visible against the one that caused it.
        for (const id of ids) {
            this.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Posting…';
            await postDraft(id);
        }
        this.disabled = false;
        this.innerHTML = original;
        countDrafts();
    });

    fitAll(document);
</script>
@endsection
