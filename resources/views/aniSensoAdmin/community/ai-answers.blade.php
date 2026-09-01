@extends('layouts.master')

@section('title') Community — AI Answers @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .aiadraft-card { border:1px solid #eef0f2; }
    .aiadraft-card.is-posting { opacity:.55; pointer-events:none; }
    .aiadraft-card.is-new { animation: aiaIn .32s cubic-bezier(.22,1,.36,1); }
    .aiadraft-card.is-dirty { border-color:#f4a82a; box-shadow:0 0 0 .12rem rgba(244,168,42,.16); }
    @keyframes aiaIn { from { opacity:0; transform:translateY(10px); } to { opacity:1; transform:none; } }
    @media (prefers-reduced-motion: reduce) { .aiadraft-card.is-new { animation:none; } }

    .aiadraft-q { background:#f8f9fa; border-radius:.5rem; padding:.75rem 1rem; }
    .aia-badge-model { font-size:.7rem; }

    /* ---- The answer, read rather than glimpsed ----
       A 150px box on a six-paragraph answer meant scrolling inside a scroll,
       so the field now grows to what it holds. Two heights: a tall default
       that shows most answers whole, and a full one for the long ones. Both
       ease, so a row opening does not jump the page under the cursor.
       Bootstrap's own textarea.form-control min-height carries the same
       specificity as a bare descendant selector and loads after this, which
       is why the class is named here. */
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
    @media (prefers-reduced-motion: reduce) { .aiadraft-fold, .aia-chevron { transition:none; } }

    .aia-head { cursor:pointer; }
    .aia-progress { font-size:.85rem; }
    .aia-lines { font-size:.72rem; color:#98a4b6; }

    /* ---- The two shelves ---- */
    .aia-tabs .nav-link {
        border:1px solid #e6e8ec; border-radius:999px; padding:.35rem .95rem; margin-right:.4rem;
        font-size:12.5px; font-weight:500; color:#495057; background:#fff; cursor:pointer;
    }
    .aia-tabs .nav-link:hover { background:#eef2ff; color:#2c3e8c; }
    .aia-tabs .nav-link.active { background:#556ee6; border-color:#556ee6; color:#fff; }
    .aia-tabs .nav-link .badge { font-size:10.5px; font-weight:600; background:#eef1f6; color:#495057; }
    .aia-tabs .nav-link.active .badge { background:rgba(255,255,255,.85); color:#2c3e8c; }

    /* ---- Asking again ---- */
    .aia-chip {
        border:1px solid #e6e8ec; border-radius:999px; background:#fff; color:#495057;
        font-size:12px; padding:.25rem .7rem; cursor:pointer;
    }
    .aia-chip:hover { background:#eef2ff; color:#2c3e8c; border-color:#c9d4ff; }
    #rerunPrev {
        white-space:pre-wrap; max-height:180px; overflow:auto; font-size:12.5px;
        background:#f8f9fa; border-radius:.5rem; padding:.6rem .8rem; color:#74788d;
    }
    .aia-gone { font-size:11.5px; }
</style>

{{-- The mode layer, last in the head so it answers after the rules
     above it. --}}
@include('aniSensoAdmin.partials.dark')

@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1') Ani-Senso @endslot
        @slot('li_2') Community @endslot
        @slot('title') AI Answers @endslot
    @endcomponent

    @include('aniSensoAdmin.community.partials.shelf', ['cmHere' => 'ai'])

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
                        <div class="d-flex gap-2 flex-wrap" id="pendingActions">
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

                    {{-- Two shelves: what is waiting to be read, and what the
                         community is already reading. A posted answer is the
                         one most likely to need a second look, and until now
                         the only way back to it was through the app. --}}
                    <div class="aia-tabs d-flex flex-wrap mb-3" id="aiaTabs">
                        <a class="nav-link active" data-shelf="pending">
                            <i class="bx bx-edit-alt"></i> Waiting for review
                            <span class="badge ms-1" id="tabPendingCount">{{ $drafts->count() }}</span>
                        </a>
                        <a class="nav-link" data-shelf="posted">
                            <i class="bx bx-check-circle"></i> Posted answers
                            <span class="badge ms-1" id="tabPostedCount">{{ $postedCount }}</span>
                        </a>
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

                    {{-- ---------------- waiting for review ---------------- --}}
                    <div id="shelfPending">
                        <div id="draftsWrap">
                            @foreach($drafts as $draft)
                                @include('aniSensoAdmin.community.partials.ai-answer-card', [
                                    'draft' => $draft,
                                    'assistantName' => $assistantName,
                                    'posted' => false,
                                ])
                            @endforeach
                        </div>

                        <div class="text-center text-secondary py-5" id="emptyState" @if($drafts->isNotEmpty()) style="display:none;" @endif>
                            <i class="bx bx-message-rounded-check" style="font-size:2.5rem;"></i>
                            <p class="mb-0 mt-2">No answers waiting for review.</p>
                            <p class="small">Click <strong>Answer new questions</strong> to generate answers for unanswered community questions.</p>
                        </div>
                    </div>

                    {{-- ---------------- already in the community ---------------- --}}
                    <div id="shelfPosted" style="display:none;">
                        <div class="d-flex align-items-center gap-2 mb-3 flex-wrap">
                            <div class="input-group" style="max-width:420px;">
                                <span class="input-group-text bg-white" style="border-right:0;"><i class="bx bx-search text-secondary"></i></span>
                                <input type="search" class="form-control" id="postedSearch" placeholder="Search posted answers…" autocomplete="off" style="border-left:0;">
                            </div>
                            <button type="button" class="btn btn-light btn-sm" id="postedReload"><i class="bx bx-refresh"></i> Refresh</button>
                            <small class="text-secondary ms-auto">
                                Editing here changes what the community is reading, straight away.
                            </small>
                        </div>

                        <div id="postedWrap"></div>

                        {{-- No d-flex here: it is display:flex !important and
                             would beat the inline display:none, leaving an
                             empty pager under a single page of answers. --}}
                        <div class="align-items-center justify-content-between gap-2 mt-2" id="postedPager" style="display:none;">
                            <small class="text-secondary" id="postedRange"></small>
                            <div class="btn-group btn-group-sm">
                                <button type="button" class="btn btn-outline-secondary" id="postedPrev"><i class="bx bx-chevron-left"></i> Newer</button>
                                <button type="button" class="btn btn-outline-secondary" id="postedNext">Older <i class="bx bx-chevron-right"></i></button>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- ---------------- ask again ----------------
         A rewrite is one instruction about one answer, so the answer it is
         about is shown right here rather than left behind on the card. --}}
    <div class="modal fade" id="rerunModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title mb-0"><i class="bx bx-refresh me-1"></i> Ask again</h5>
                        <small class="text-secondary" id="rerunSub"></small>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label class="form-label small text-secondary mb-1">What should be different this time?</label>
                    <div class="d-flex flex-wrap gap-1 mb-2" id="rerunChips">
                        <button type="button" class="aia-chip">Make it shorter</button>
                        <button type="button" class="aia-chip">Give step-by-step instructions</button>
                        <button type="button" class="aia-chip">Include rates, days and prices</button>
                        <button type="button" class="aia-chip">Answer in simple Tagalog</button>
                        <button type="button" class="aia-chip">Warmer, more encouraging tone</button>
                        <button type="button" class="aia-chip">Add a safety warning</button>
                        <button type="button" class="aia-chip">Use more emojis and spacing</button>
                        <button type="button" class="aia-chip">Suggest cheaper, local options</button>
                    </div>
                    <textarea class="form-control" id="rerunInstruction" rows="3"
                              placeholder="e.g. Mas maikli, at ilagay ang dami ng abono kada ektarya."></textarea>
                    <p class="text-secondary small mt-2 mb-1">Leave it blank and it simply tries to do better.</p>

                    <details class="mt-3">
                        <summary class="small text-secondary">The answer it is rewriting</summary>
                        <div id="rerunPrev" class="mt-2"></div>
                    </details>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" id="rerunGo">
                        <i class="bx bx-bot"></i> <span id="rerunGoLabel">Ask again</span>
                    </button>
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
        if (!e.target.classList?.contains('draft-answer')) return;
        fitAnswer(e.target);
        // A posted answer that has been touched is not what the community is
        // reading until Save is pressed; the card says so.
        const card = e.target.closest('.aiadraft-card');
        if (card?.dataset.shelf === 'posted') card.classList.add('is-dirty');
    });

    /* Folding: the head folds the row, the chevron beside the answer lets one
     * long answer off its height cap without touching the others. */
    document.addEventListener('click', (e) => {
        const head = e.target.closest('.aia-head');
        if (head && !e.target.closest('button') && !e.target.closest('a')) {
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
        const cards = [...document.querySelectorAll('.aiadraft-card')].filter(c => c.offsetParent !== null);
        const foldingUp = cards.some(c => !c.classList.contains('is-folded'));
        cards.forEach(c => c.classList.toggle('is-folded', foldingUp));
        this.innerHTML = foldingUp
            ? '<i class="bx bx-expand-vertical"></i> Open all'
            : '<i class="bx bx-collapse-vertical"></i> Fold all';
    });

    /* ---------------- the two shelves ---------------- */
    let shelf = 'pending';
    document.getElementById('aiaTabs').addEventListener('click', (e) => {
        const tab = e.target.closest('.nav-link');
        if (!tab) return;
        document.querySelectorAll('#aiaTabs .nav-link').forEach(t => t.classList.remove('active'));
        tab.classList.add('active');
        shelf = tab.getAttribute('data-shelf');
        document.getElementById('shelfPending').style.display = shelf === 'pending' ? '' : 'none';
        document.getElementById('shelfPosted').style.display = shelf === 'posted' ? '' : 'none';
        // Generating and Post-all belong to the review shelf only.
        document.getElementById('btnGenerate').style.display = shelf === 'pending' ? '' : 'none';
        document.getElementById('btnPostAll').style.display =
            (shelf === 'pending' && document.querySelectorAll('#draftsWrap [data-draft]').length) ? '' : 'none';
        if (shelf === 'posted' && !postedLoaded) loadPosted();
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

    function cardHtml(d, posted) {
        const answer = esc(d.answerBody);
        const gone = posted && d.live === false;
        return `
        <div class="card aiadraft-card mb-3 is-new" data-draft="${d.id}" data-shelf="${posted ? 'posted' : 'pending'}">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start gap-2 mb-2 aia-head">
                    <div class="d-flex align-items-start gap-2">
                        <i class="bx bx-chevron-down aia-chevron text-secondary" style="font-size:1.25rem;"></i>
                        <div>
                            <div class="fw-semibold text-dark">${esc(d.questionTitle)}</div>
                            <div class="text-secondary small">
                                in ${esc(d.groupName)}${d.askedBy ? ' · asked by ' + esc(d.askedBy) : ''}
                                ${d.postedAt ? ' · posted ' + esc(d.postedAt) : ''}
                                ${d.model ? ' · <span class="badge bg-light text-dark aia-badge-model">' + esc(d.model) + '</span>' : ''}
                                ${gone ? ' · <span class="badge bg-warning text-dark aia-gone">no longer in the community</span>' : ''}
                            </div>
                        </div>
                    </div>
                    ${posted ? '' : `<button type="button" class="btn btn-sm btn-soft-danger btn-dismiss" data-id="${d.id}" title="Discard">
                        <i class="bx bx-trash"></i>
                    </button>`}
                </div>
                <div class="aiadraft-fold"><div>
                    ${d.questionBody ? `<div class="aiadraft-q text-secondary small mb-3">${esc(d.questionBody).replace(/\n/g, '<br>')}</div>` : ''}
                    <div class="d-flex justify-content-between align-items-center mb-1 flex-wrap gap-2">
                        <label class="form-label small text-secondary mb-0">${esc(ASSISTANT)}'s answer (editable)</label>
                        <div class="d-flex align-items-center gap-2">
                            <span class="aia-lines"></span>
                            <button type="button" class="btn btn-sm btn-light btn-tidy"
                                    title="Strip the markdown — the community shows *asterisks* and ## literally">
                                <i class="bx bx-brush"></i> Clean up
                            </button>
                            <button type="button" class="btn btn-sm btn-soft-primary btn-rerun" data-id="${d.id}">
                                <i class="bx bx-refresh"></i> Ask again
                            </button>
                            <button type="button" class="btn btn-sm btn-light btn-tall"><i class="bx bx-expand-vertical"></i> Expand</button>
                        </div>
                    </div>
                    <div class="aiadraft-a mb-2">
                        <textarea class="form-control draft-answer">${answer}</textarea>
                    </div>
                    <div class="d-flex gap-2 justify-content-end align-items-center flex-wrap">
                        ${d.postUrl ? `<a href="${esc(d.postUrl)}" target="_blank" rel="noopener" class="small me-auto">${posted ? 'see it in the community' : 'see the question in the community'}</a>` : ''}
                        ${posted ? `
                            <button type="button" class="btn btn-sm btn-soft-danger btn-unpost" data-id="${d.id}">
                                <i class="bx bx-x-circle"></i> Take down
                            </button>
                            <button type="button" class="btn btn-sm btn-success btn-save-posted" data-id="${d.id}">
                                <i class="bx bx-save"></i> Save to community
                            </button>`
                        : `
                            <button type="button" class="btn btn-sm btn-soft-secondary btn-save" data-id="${d.id}">
                                <i class="bx bx-save"></i> Save edits
                            </button>
                            <button type="button" class="btn btn-sm btn-success btn-post" data-id="${d.id}">
                                <i class="bx bx-send"></i> Post to community
                            </button>`}
                    </div>
                </div></div>
            </div>
        </div>`;
    }

    function addCard(d) {
        document.getElementById('emptyState').style.display = 'none';
        const wrap = document.getElementById('draftsWrap');
        wrap.insertAdjacentHTML('afterbegin', cardHtml(d, false));
        fitAnswer(wrap.firstElementChild.querySelector('.draft-answer'));
        countDrafts();
    }

    function countDrafts() {
        const n = document.querySelectorAll('#draftsWrap [data-draft]').length;
        document.getElementById('postAllCount').textContent = n;
        document.getElementById('tabPendingCount').textContent = n;
        document.getElementById('btnPostAll').style.display = (n && shelf === 'pending') ? '' : 'none';
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

        const savePosted = e.target.closest('.btn-save-posted');
        if (savePosted) {
            const id = savePosted.getAttribute('data-id');
            savePosted.disabled = true;
            try {
                const data = await api('/anisenso-community-ai-answers-posted-update?id=' + id, 'PUT', { answerBody: answerOf(id) });
                if (data.success) { toastr.success(data.message); cardFor(id)?.classList.remove('is-dirty'); }
                else { toastr.error(data.message); }
            } catch (_) { toastr.error('Network error — try again.'); }
            savePosted.disabled = false;
            return;
        }

        const unpost = e.target.closest('.btn-unpost');
        if (unpost) {
            if (!confirm('Take this answer off the community? It goes back to the review shelf, so nothing is lost.')) return;
            const id = unpost.getAttribute('data-id');
            unpost.disabled = true;
            try {
                const data = await api('/anisenso-community-ai-answers-unpost?id=' + id, 'DELETE');
                if (data.success) {
                    toastr.success(data.message);
                    cardFor(id)?.remove();
                    if (data.draft) addCard(data.draft);          // straight back onto the review shelf
                    bumpPostedCount(-1);
                    if (!document.querySelectorAll('#postedWrap [data-draft]').length) loadPosted();
                } else { toastr.error(data.message); unpost.disabled = false; }
            } catch (_) { toastr.error('Network error — try again.'); unpost.disabled = false; }
            return;
        }

        const tidy = e.target.closest('.btn-tidy');
        if (tidy) {
            const card = tidy.closest('.aiadraft-card');
            const ta = card?.querySelector('.draft-answer');
            if (!ta) return;
            tidy.disabled = true;
            try {
                const data = await api('/anisenso-community-ai-answers-tidy', 'POST', { answerBody: ta.value });
                if (data.success) {
                    if (data.changed) {
                        ta.value = data.answerBody;
                        fitAnswer(ta);
                        if (card.dataset.shelf === 'posted') card.classList.add('is-dirty');
                        toastr.success(data.message);
                    } else {
                        toastr.info(data.message);
                    }
                } else { toastr.error(data.message); }
            } catch (_) { toastr.error('Network error — try again.'); }
            tidy.disabled = false;
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
        card.classList.add('is-posting');
        try {
            const data = await api('/anisenso-community-ai-answers-post?id=' + id, 'POST', { answerBody: answerOf(id) });
            if (data.success) {
                toastr.success(data.message);
                // Off the review shelf the moment it is live, and onto the
                // other one, where it can still be edited or taken down.
                card.remove();
                countDrafts();
                bumpPostedCount(1);
                postedLoaded = false;
                if (shelf === 'posted') loadPosted();
            } else {
                toastr.error(data.message);
                card.classList.remove('is-posting');
            }
        } catch (_) { toastr.error('Network error — try again.'); card.classList.remove('is-posting'); }
    }

    function bumpPostedCount(by) {
        const el = document.getElementById('tabPostedCount');
        el.textContent = Math.max(0, (parseInt(el.textContent, 10) || 0) + by);
    }

    document.getElementById('btnPostAll')?.addEventListener('click', async function () {
        const ids = [...document.querySelectorAll('#draftsWrap [data-draft]')].map(c => c.getAttribute('data-draft'));
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

    /* ---------------- posted answers ---------------- */
    let postedLoaded = false;
    const posted = { start: 0, per: 10, search: '', total: 0 };

    async function loadPosted() {
        const wrap = document.getElementById('postedWrap');
        wrap.innerHTML = '<div class="text-center py-4"><i class="bx bx-loader-alt bx-spin fs-3 text-secondary"></i></div>';
        document.getElementById('postedPager').style.display = 'none';
        try {
            const url = '/anisenso-community-ai-answers-posted?start=' + posted.start + '&length=' + posted.per
                + (posted.search ? '&searchFilter=' + encodeURIComponent(posted.search) : '');
            const data = await api(url, 'GET');
            if (!data.success) { wrap.innerHTML = '<p class="text-secondary">Could not read the posted answers.</p>'; return; }

            postedLoaded = true;
            posted.total = data.total;
            document.getElementById('tabPostedCount').textContent = data.total;

            if (!data.rows.length) {
                wrap.innerHTML = `<div class="text-center text-secondary py-5">
                    <i class="bx bx-check-circle" style="font-size:2.5rem;"></i>
                    <p class="mb-0 mt-2">${posted.search ? 'Nothing matches that.' : 'Nothing has been posted yet.'}</p>
                </div>`;
                return;
            }
            wrap.innerHTML = data.rows.map(d => cardHtml(d, true)).join('');
            fitAll(wrap);
            wrap.querySelectorAll('.aiadraft-card').forEach(c => c.classList.remove('is-new'));

            if (posted.total > posted.per) {
                document.getElementById('postedRange').textContent =
                    `${posted.start + 1}–${Math.min(posted.start + posted.per, posted.total)} of ${posted.total}`;
                document.getElementById('postedPrev').disabled = posted.start === 0;
                document.getElementById('postedNext').disabled = posted.start + posted.per >= posted.total;
                document.getElementById('postedPager').style.display = 'flex';
            }
        } catch (_) {
            wrap.innerHTML = '<p class="text-secondary">Could not read the posted answers.</p>';
        }
    }

    let postedTyping = null;
    document.getElementById('postedSearch').addEventListener('input', function () {
        posted.search = this.value;
        posted.start = 0;
        clearTimeout(postedTyping);
        postedTyping = setTimeout(loadPosted, 350);
    });
    document.getElementById('postedReload').addEventListener('click', loadPosted);
    document.getElementById('postedPrev').addEventListener('click', () => { posted.start = Math.max(0, posted.start - posted.per); loadPosted(); });
    document.getElementById('postedNext').addEventListener('click', () => { posted.start += posted.per; loadPosted(); });

    /* ---------------- ask again ---------------- */
    let rerunId = null;
    const rerunModal = document.getElementById('rerunModal');

    document.addEventListener('click', (e) => {
        const btn = e.target.closest('.btn-rerun');
        if (!btn) return;
        rerunId = btn.getAttribute('data-id');
        const card = cardFor(rerunId);
        document.getElementById('rerunSub').textContent =
            card?.querySelector('.fw-semibold')?.textContent?.trim() || '';
        document.getElementById('rerunInstruction').value = '';
        document.getElementById('rerunPrev').textContent = answerOf(rerunId);
        document.getElementById('rerunGoLabel').textContent =
            card?.dataset.shelf === 'posted' ? 'Ask again (then Save to publish)' : 'Ask again';
        bootstrap.Modal.getOrCreateInstance(rerunModal).show();
    });

    document.getElementById('rerunChips').addEventListener('click', (e) => {
        const chip = e.target.closest('.aia-chip');
        if (!chip) return;
        const box = document.getElementById('rerunInstruction');
        box.value = (box.value.trim() ? box.value.trim().replace(/\.?$/, '. ') : '') + chip.textContent.trim() + '.';
        box.focus();
    });

    document.getElementById('rerunGo').addEventListener('click', async function () {
        if (!rerunId) return;
        const card = cardFor(rerunId);
        const label = document.getElementById('rerunGoLabel');
        const original = label.textContent;
        this.disabled = true;
        label.textContent = 'Asking…';
        try {
            const data = await api('/anisenso-community-ai-answers-regenerate?id=' + rerunId, 'POST', {
                instruction: document.getElementById('rerunInstruction').value,
                answerBody: answerOf(rerunId),
            });
            if (data.success) {
                const ta = card?.querySelector('.draft-answer');
                if (ta) { ta.value = data.answerBody; fitAnswer(ta); }
                if (card?.dataset.shelf === 'posted') {
                    card.classList.add('is-dirty');
                    toastr.info('New answer ready — press "Save to community" to publish it.');
                } else {
                    toastr.success('Answered again.');
                }
                bootstrap.Modal.getInstance(rerunModal)?.hide();
            } else {
                toastr.error(data.message || 'Could not answer again.');
            }
        } catch (_) { toastr.error('Network error — try again.'); }
        this.disabled = false;
        label.textContent = original;
    });

    fitAll(document);
</script>
@endsection
