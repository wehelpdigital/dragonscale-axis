@extends('layouts.master')

@section('title') Community — AI Answers @endsection

@section('css')
<link href="{{ URL::asset('build/libs/toastr/build/toastr.min.css') }}" rel="stylesheet" type="text/css" />
<style>
    .aiadraft-q { background:#f8f9fa; border-radius:.5rem; padding:.75rem 1rem; }
    .aiadraft-a textarea { min-height:150px; font-size:.925rem; line-height:1.5; }
    .aiadraft-card { border:1px solid #eef0f2; }
    .aiadraft-card.is-posting { opacity:.55; pointer-events:none; }
    .aia-badge-model { font-size:.7rem; }
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
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-primary" id="btnGenerate" {{ $aiUsable ? '' : 'disabled' }}>
                                <i class="bx bx-bot"></i> Answer new questions
                                <span class="badge bg-light text-dark ms-1" id="unansweredBadge">{{ $unansweredCount }}</span>
                            </button>
                            @if($drafts->isNotEmpty())
                                <button type="button" class="btn btn-success" id="btnPostAll">
                                    <i class="bx bx-send"></i> Post all ({{ $drafts->count() }})
                                </button>
                            @endif
                        </div>
                    </div>

                    @unless($aiUsable)
                        <div class="alert alert-warning mb-3">
                            <i class="bx bx-error-circle me-1"></i>
                            The AniSenso AI isn't configured yet. Set a provider, model and API key in
                            <a href="{{ route('anisenso-ai-settings.index') }}">AI Settings</a> before generating answers.
                        </div>
                    @endunless

                    <div id="draftsWrap">
                        @forelse($drafts as $draft)
                            <div class="card aiadraft-card mb-3" data-draft="{{ $draft->id }}">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start gap-2 mb-2">
                                        <div>
                                            <div class="fw-semibold text-dark">
                                                {{ $draft->questionTitle ?: 'Untitled question' }}
                                            </div>
                                            <div class="text-secondary small">
                                                in {{ optional($draft->post)->group->name ?? 'a group' }}
                                                @if(optional($draft->post)->author) · asked by {{ optional($draft->post->author)->full_name ?: 'a member' }} @endif
                                                @if($draft->model) · <span class="badge bg-light text-dark aia-badge-model">{{ $draft->model }}</span> @endif
                                            </div>
                                        </div>
                                        <button type="button" class="btn btn-sm btn-soft-danger btn-dismiss" data-id="{{ $draft->id }}" title="Discard">
                                            <i class="bx bx-trash"></i>
                                        </button>
                                    </div>

                                    @if($draft->questionBody)
                                        <div class="aiadraft-q text-secondary small mb-3">{!! nl2br(e(\Illuminate\Support\Str::limit($draft->questionBody, 600))) !!}</div>
                                    @endif

                                    <label class="form-label small text-secondary mb-1">{{ $assistantName }}'s answer (editable)</label>
                                    <div class="aiadraft-a mb-2">
                                        <textarea class="form-control draft-answer">{{ $draft->answerBody }}</textarea>
                                    </div>

                                    <div class="d-flex gap-2 justify-content-end">
                                        <button type="button" class="btn btn-sm btn-soft-secondary btn-save" data-id="{{ $draft->id }}">
                                            <i class="bx bx-save"></i> Save edits
                                        </button>
                                        <button type="button" class="btn btn-sm btn-success btn-post" data-id="{{ $draft->id }}">
                                            <i class="bx bx-send"></i> Post to community
                                        </button>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="text-center text-secondary py-5" id="emptyState">
                                <i class="bx bx-message-rounded-check" style="font-size:2.5rem;"></i>
                                <p class="mb-0 mt-2">No answers waiting for review.</p>
                                <p class="small">Click <strong>Answer new questions</strong> to generate answers for unanswered community questions.</p>
                            </div>
                        @endforelse
                    </div>

                    @if($recentPosted->isNotEmpty())
                        <hr class="my-4">
                        <h6 class="text-secondary mb-2">Recently posted</h6>
                        <ul class="list-unstyled mb-0">
                            @foreach($recentPosted as $p)
                                <li class="small text-secondary mb-1">
                                    <i class="bx bx-check-circle text-success"></i>
                                    {{ $p->questionTitle ?: 'Question' }}
                                    <span class="text-muted">— in {{ optional($p->post)->group->name ?? 'a group' }}, {{ $p->postedAt?->format('M j, Y g:i A') }}</span>
                                </li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
<script src="{{ URL::asset('build/libs/toastr/build/toastr.min.js') }}"></script>
<script>
    const CSRF = "{{ csrf_token() }}";
    const jsonHeaders = { 'X-CSRF-TOKEN': CSRF, 'Content-Type': 'application/json', Accept: 'application/json' };

    async function api(url, method, body) {
        const res = await fetch(url, { method, headers: jsonHeaders, body: body ? JSON.stringify(body) : undefined });
        return res.json();
    }

    // Generate answers for unanswered questions
    const btnGenerate = document.getElementById('btnGenerate');
    btnGenerate?.addEventListener('click', async () => {
        const original = btnGenerate.innerHTML;
        btnGenerate.disabled = true;
        btnGenerate.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Answering…';
        try {
            const data = await api('{{ route('anisenso-community.ai-answers.generate') }}', 'POST');
            if (data.success) {
                toastr.success(data.message);
                if (data.count > 0) { setTimeout(() => location.reload(), 900); return; }
            } else {
                toastr.error(data.message || 'Could not generate answers.');
            }
        } catch (_) { toastr.error('Network error — try again.'); }
        btnGenerate.disabled = false;
        btnGenerate.innerHTML = original;
    });

    function cardFor(id) { return document.querySelector('[data-draft="' + id + '"]'); }
    function answerOf(id) { return cardFor(id)?.querySelector('.draft-answer')?.value ?? ''; }

    // Save edits
    document.querySelectorAll('.btn-save').forEach((btn) => {
        btn.addEventListener('click', async () => {
            const id = btn.getAttribute('data-id');
            btn.disabled = true;
            try {
                const data = await api('/anisenso-community/ai-answers/' + id, 'PUT', { answerBody: answerOf(id) });
                data.success ? toastr.success(data.message) : toastr.error(data.message);
            } catch (_) { toastr.error('Network error — try again.'); }
            btn.disabled = false;
        });
    });

    // Post one
    document.querySelectorAll('.btn-post').forEach((btn) => {
        btn.addEventListener('click', () => postDraft(btn.getAttribute('data-id')));
    });

    async function postDraft(id) {
        const card = cardFor(id);
        if (!card) return;
        card.classList.add('is-posting');
        try {
            const data = await api('/anisenso-community/ai-answers/' + id + '/post', 'POST', { answerBody: answerOf(id) });
            if (data.success) { toastr.success(data.message); card.remove(); afterRemoval(); }
            else { toastr.error(data.message); card.classList.remove('is-posting'); }
        } catch (_) { toastr.error('Network error — try again.'); card.classList.remove('is-posting'); }
    }

    // Post all
    document.getElementById('btnPostAll')?.addEventListener('click', async function () {
        if (!confirm('Post every reviewed answer to the community?')) return;
        this.disabled = true;
        const original = this.innerHTML;
        this.innerHTML = '<i class="bx bx-loader-alt bx-spin"></i> Posting…';
        try {
            const data = await api('{{ route('anisenso-community.ai-answers.post-all') }}', 'POST');
            data.success ? toastr.success(data.message) : toastr.error(data.message);
            setTimeout(() => location.reload(), 900);
        } catch (_) { toastr.error('Network error — try again.'); this.disabled = false; this.innerHTML = original; }
    });

    // Dismiss
    document.querySelectorAll('.btn-dismiss').forEach((btn) => {
        btn.addEventListener('click', async () => {
            if (!confirm('Discard this answer without posting?')) return;
            const id = btn.getAttribute('data-id');
            try {
                const data = await api('/anisenso-community/ai-answers/' + id, 'DELETE');
                if (data.success) { toastr.success(data.message); cardFor(id)?.remove(); afterRemoval(); }
                else { toastr.error(data.message); }
            } catch (_) { toastr.error('Network error — try again.'); }
        });
    });

    function afterRemoval() {
        if (!document.querySelector('[data-draft]')) setTimeout(() => location.reload(), 600);
    }
</script>
@endsection
