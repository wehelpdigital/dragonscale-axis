{{-- One answer waiting for review.

     The twin of cardHtml() on the page's script: a card generated in this
     run and a card that was already waiting when the page loaded have to be
     the same thing, or every handler needs two versions of itself. --}}
@php
    $post = $draft->post;
    $postUrl = $post
        ? rtrim(config('anisystem.url'), '/') . '/app/community/groups/' . $post->groupId . '#post-' . $post->id
        : null;
@endphp
<div class="card aiadraft-card mb-3" data-draft="{{ $draft->id }}">
    <div class="card-body">
        <div class="d-flex justify-content-between align-items-start gap-2 mb-2 aia-head">
            <div class="d-flex align-items-start gap-2">
                <i class="bx bx-chevron-down aia-chevron text-secondary" style="font-size:1.25rem;"></i>
                <div>
                    <div class="fw-semibold text-dark">{{ $draft->questionTitle ?: 'Untitled question' }}</div>
                    <div class="text-secondary small">
                        in {{ optional($post)->group->name ?? 'a group' }}
                        @if(optional($post)->author) · asked by {{ optional($post->author)->full_name ?: 'a member' }} @endif
                        @if($draft->model) · <span class="badge bg-light text-dark aia-badge-model">{{ $draft->model }}</span> @endif
                    </div>
                </div>
            </div>
            <button type="button" class="btn btn-sm btn-soft-danger btn-dismiss" data-id="{{ $draft->id }}" title="Discard">
                <i class="bx bx-trash"></i>
            </button>
        </div>

        <div class="aiadraft-fold"><div>
            @if($draft->questionBody)
                <div class="aiadraft-q text-secondary small mb-3">{!! nl2br(e(\Illuminate\Support\Str::limit($draft->questionBody, 600))) !!}</div>
            @endif

            <div class="d-flex justify-content-between align-items-center mb-1">
                <label class="form-label small text-secondary mb-0">{{ $assistantName }}'s answer (editable)</label>
                <div class="d-flex align-items-center gap-2">
                    <span class="aia-lines"></span>
                    <button type="button" class="btn btn-sm btn-light btn-tall"><i class="bx bx-expand-vertical"></i> Expand</button>
                </div>
            </div>
            <div class="aiadraft-a mb-2">
                <textarea class="form-control draft-answer">{{ $draft->answerBody }}</textarea>
            </div>

            <div class="d-flex gap-2 justify-content-end align-items-center">
                @if($postUrl)
                    <a href="{{ $postUrl }}" target="_blank" rel="noopener" class="small me-auto">see the question in the community</a>
                @endif
                <button type="button" class="btn btn-sm btn-soft-secondary btn-save" data-id="{{ $draft->id }}">
                    <i class="bx bx-save"></i> Save edits
                </button>
                <button type="button" class="btn btn-sm btn-success btn-post" data-id="{{ $draft->id }}">
                    <i class="bx bx-send"></i> Post to community
                </button>
            </div>
        </div></div>
    </div>
</div>
